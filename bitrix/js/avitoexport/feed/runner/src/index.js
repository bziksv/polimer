// @flow

import {Skeleton} from "../../../plugin/skeleton";
import {compileTemplate} from "../../../plugin/utils";
import {HttpError} from "./httperror";
import {ParseError} from "./parseerror";

// noinspection JSUnresolvedVariable
const $ = window.AvitoJQuery ?? window.$;

export class Runner extends Skeleton {

	static defaults = {
		runElement: '.js-export-form__run',
		stopElement: '.js-export-form__stop',
		copyElement: '.js-export-copy_to_clipboard',
		linkFileElement: '.js-link-file',
		messageElement: '.js-export-form__message',
		timerHolderElement: '.js-export-form__timer-holder',
		timerElement: '.js-export-form__timer',
		errorTemplate: '<span>#TITLE#</span> <span>#MESSAGE#</span> <textarea>#DEBUG#</textarea>',
		lang: {
			COPY_TO_BUFFER: 'The link is copied to the clipboard: ',
			JS_ERROR: 'JavaScript error',
			HTTP_ERROR: 'HTTP error',
			PARSE_ERROR: 'Response parse error',
		}
	}
	
	constructor(element: HTMLElement, options: Object = {}) {
		super(element, options);
		this.bind();
		this.initVars();
	}

	initVars() : void {
		this._formData = null;
		this._query = null;

		this._queryTimeout = null;
		this._state = null;
		this._timerInterval = null;
	}

	bind() : void {
		this.handleRunClick(true);
		this.handleStopClick(true);
	}

	bindCopy() : void {
		this.handleCopyClick(true);
	}

	unbind() : void {
		this.handleRunClick(false);
		this.handleStopClick(false);
	}

	handleRunClick(dir) : void {
		const button = this.getElement('run');

		button[dir ? 'on' : 'off']('click', this.onRunClick);
	}

	handleStopClick(dir) : void {
		const button = this.getElement('stop');

		button[dir ? 'on' : 'off']('click', this.onStopClick);
	}

	handleCopyClick(dir) : void {
		const button = this.getElement('copy');

		button[dir ? 'on' : 'off']('click', this.onCopyClick);
	}

	onRunClick = (event) => {
		this.toggleButtons(true);
		this.showMessage();
		this.startTimer();
		this.query('run')

		event.preventDefault();
	}

	onStopClick = (event) => {
		this.query('stop');

		event.preventDefault();
	}

	onCopyClick = (event) => {
		event.preventDefault();
		const link = event.currentTarget.previousElementSibling.value;
		navigator.clipboard.writeText(link)
			.then(() => {
				this.showMessageCopyLink(this.getLang('LINK_COPIED') + ' ' + link);
			})
			.catch((err) => {
				console.log(err);
				throw err;
			}
		);
	}

	showMessageCopyLink(text : string = '') : void {
		alert(text);
	}

	query(action : string) {
		this.queryDelayedCancel();
		this.queryAbort();

		this._query = this.makeQuery(action);

		(new Promise((resolve, reject) => {
			this._query.then(resolve, reject);
		}))
			.then(this.queryEnd, this.queryStop)
			.catch(this.queryError);
	}

	getFormData() : Array {
		if (this._formData === null) {
			this._formData = this.$el.serializeArray();
		}

		return this._formData.slice();
	}

	clearFormData() : void {
		this._formData = null;
	}

	getState() : Object {
		return this._state;
	}

	getFormValue(field : string) : Array {
		const formData = this.getFormData();
		let result = [];

		for (let i = formData.length - 1; i >= 0; i--) {
			if (formData[i].name === field) {
				result = formData[i].value;
				break;
			}
		}

		return result;
	}

	setState(state : Object) : void {
		this._state = state;
	}

	clearState() : void {
		this._state = null;
	}

	makeQuery(action : string) : Object {
		const state = this.getState();
		
		let config = {
			url: this.$el.attr('action'),
			type: 'post',
			data: this.getFormData()
		};

		config.data.push({
			name: 'action',
			value: action
		});

		if (state !== null) {
			for (let stateKey in state) {
				if (state.hasOwnProperty(stateKey)) {
					config.data.push({
						name: stateKey,
						value: state[stateKey]
					});
				}
			}
		}

		return $.ajax(config);
	}

	showMessage(text : string = '') : void {
		this.getElement('message').html(text || '');
	}

	startTimer() : void {
		const timerElement = this.getElement('timer');
		const timerHolderElement = this.getElement('timerHolder');

		timerHolderElement.show();
		timerElement.text('00:00');

		clearTimeout(this._timerInterval);

		this._timerStart = new Date();
		this._timerInterval = setTimeout(this.tickTimer, 1000);
	}

	tickTimer = () => {
		const startDate = this._timerStart;
		const timerElement = this.getElement('timer');
		const nowDate = new Date();
		const diff = (nowDate - startDate) / 1000;
		let seconds = '' + parseInt(diff % 60, 10);
		let minutes = '' + Math.floor(diff / 60);

		if (minutes.length === 1) {
			minutes = '0' + minutes;
		}

		if (seconds.length === 1) {
			seconds = '0' + seconds;
		}

		timerElement.text(minutes + ':' + seconds);

		this._timerInterval = setTimeout(this.tickTimer, 1000);
	}

	stopTimer() : void {
		clearTimeout(this._timerInterval);
	}

	queryAbort() : void {
		if (this._query == null) { return; }

		this._query.abort('manual');
	}

	queryDelayed(action, delay) {
		this.queryDelayedCancel();

		this._queryTimeout = setTimeout(
			$.proxy(this.query, this, action),
			(parseInt(delay, 10) || 1) * 1000
		);
	}

	queryDelayedCancel() : void {
		clearTimeout(this._queryTimeout);
	}

	queryEnd = (response: string) : void => {
		const data = this.parseResponse(response);

		this._query = null;

		this.showMessage(data.message);

		switch (data.status) {
			case 'progress':
				this.queryDelayed('run', this.getFormValue('TIME_SLEEP'));
				this.setState(data.state);
				break;

			default:
				if (data.status === 'ok') {
					this.bindCopy();
				}

				this.clearFormData();
				this.clearState();
				this.stopTimer();
				this.toggleButtons(false);
				break;
		}
	}

	queryStop = (xhr, textStatus: string, errorThrown: string) : void => {
		this._query = null;

		if ((textStatus ?? xhr.statusText) === 'manual') { return; }

		if (xhr.status !== 200) {
			throw new HttpError(`HTTP ${xhr.status}`, xhr.responseText);
		}

		throw new HttpError(errorThrown || textStatus, xhr.responseText);
	}

	queryError = (error: Error) : void => {
		const template = this.getTemplate('error');
		let variables;

		if (error instanceof ParseError) {
			variables = {
				'TITLE': this.getLang('PARSE_ERROR'),
				'MESSAGE': error.previousError.message,
				'DEBUG': BX.util.htmlspecialchars(error.response),
			};
		} else if (error instanceof HttpError) {
			variables = {
				'TITLE': this.getLang('HTTP_ERROR'),
				'MESSAGE': error.message,
				'DEBUG': BX.util.htmlspecialchars(error.response),
			};
		} else if (error instanceof Error) {
			variables = {
				'TITLE': this.getLang('JS_ERROR'),
				'MESSAGE': error.message,
				'DEBUG': BX.util.htmlspecialchars(error.trace),
			};
		} else {
			variables = {
				'TITLE': 'Unknown error',
				'MESSAGE': error,
				'DEBUG': '',
			};
		}

		this.stopTimer();
		this.toggleButtons(false);
		this.clearFormData();
		this.clearState();
		this.showMessage(compileTemplate(template, variables));
	}

	parseResponse(response: string|Object) : Object {
		try {
			const data = typeof response === 'string' ? JSON.parse(response) : response;

			if (!$.isPlainObject(data)) {
				throw new Error('not valid response');
			}

			return data;
		} catch (error) {
			throw new ParseError(
				error,
				typeof response !== 'string' ? JSON.stringify(response) : response
			);
		}
	}

	toggleButtons(dir: boolean) : void {
		const run = this.getElement('run');
		const stop = this.getElement('stop');

		run.prop('disabled', dir);
		stop.prop('disabled', !dir);
	}
}