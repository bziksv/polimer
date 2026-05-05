import {compileTemplate, htmlToElement} from "./utils";

export class State {

	static defaults = {
		stateTemplate: '<span class="bx-avito-export-characteristic__state"></span>',
		loadingTemplate: '#LOADING#...',
		waitingTemplate: '',
		errorTemplate: '#MESSAGE#',
		lang: {
			LOADING: 'Loading',
		},
	};

	constructor(element: HTMLElement, options: Object = {}) {
		this.el = element;
		this.options = Object.assign({}, this.constructor.defaults, options);
		this._stateHolder = null;
	}

	loading() : void {
		this.state('loading');
	}

	waiting() : void {
		this.state('waiting');
	}

	error(reason: Error) : void {
		this.state('error', {
			MESSAGE: reason.message,
		});
	}

	state(type: string, vars: Object = {}) : void {
		const html = this.compileTemplate(type, vars);
		const holder = this.stateHolder(type);

		this.markTypeClass(holder, type);
		this.replaceHtml(holder, html);
	}

	compileTemplate(type: string, vars: Object = {}) : string {
		const template = this.options[type + 'Template'];
		let html;

		html = compileTemplate(template, vars);
		html = compileTemplate(html, this.options.lang);

		return html;
	}

	markTypeClass(holder: HTMLElement, type: string) : void {
		const states = [
			'loading',
			'waiting',
			'error',
		];

		for (const state of states) {
			if (state === type) {
				holder.classList.add(state);
			} else {
				holder.classList.remove(state);
			}
		}
	}

	replaceHtml(holder: HTMLElement, html: string) : void {
		holder.innerHTML = html;
	}

	stateHolder() : HTMLElement {
		if (this._stateHolder != null) { return this._stateHolder; }

		const html = this.options.stateTemplate;
		const element = htmlToElement(html);

		this.el.after(element);
		this._stateHolder = element;

		return this._stateHolder;
	}
}