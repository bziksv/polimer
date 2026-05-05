// @flow

import {Skeleton} from "../../../../plugin/skeleton";

// noinspection JSUnresolvedVariable
const $ = window.AvitoJQuery ?? window.$;

// noinspection JSUnusedGlobalSymbols
export class OAuthToken extends Skeleton {

	static defaults = {
		startElement: '.js-oauth-token__start',
		selectElement: 'select',

		exchangeUrl: '/bitrix/tools/avito.export/oauth/exchange.php',
		refreshUrl: '/bitrix/tools/avito.export/oauth/refresh.php',

		clientIdElement: 'input[name="COMMON_SETTINGS[CLIENT_ID]"]',
		clientPasswordElement: 'input[name="COMMON_SETTINGS[CLIENT_PASSWORD]"]',

		lang: {},
		langPrefix: 'AVITO_EXPORT_ADMIN_USER_FIELD_OAUTH_TOKEN_TYPE_',
	}

	constructor(element: HTMLElement, options: Object = {}) {
		super(element, options);
		this.bind();
	}

	destroy() : void {
		this.unbind();
		super.destroy();
	}

	bind() : void {
		this.handleClientIdChange(true);
		this.handleClick(true);
	}

	unbind() : void {
		this.handleClientIdChange(false);
		this.handleClick(false);
	}

	handleClick(dir: boolean) : void {
		const button = this.getElement('start');

		button[dir ? 'on' : 'off']('click', this.onClick);
	}

	handleClientIdChange(dir: boolean) : void {
		const input = this.formInput('clientId');

		input[dir ? 'on' : 'off']('change', this.onClientIdChange);
	}

	onClick = () : void => {
		this.exchange();
	}

	onClientIdChange = () : void => {
		this.refresh();
	}

	exchange() : void {
		$.ajax({
			url: this.options.exchangeUrl,
			type: 'POST',
			data: {
				clientId: this.clientId(),
				clientSecret: this.clientPassword(),
			},
			dataType: 'json',
		})
			.then(this.exchangeEnd, this.exchangeStop);
	}

	exchangeStop = (xhr, textStatus: string, errorThrown: string) : void => {
		this.showError(this.getLang('EXCHANGE_HTTP_ERROR', {
			'REASON':  xhr.status !== 200
				? `HTTP ${xhr.status}`
				: (errorThrown || textStatus)
		}));
	}

	exchangeEnd = (response: Object) => {
		const isSuccess = response?.success;

		if (isSuccess === true) {
			this.updateTokens([
				{ ID: response.id, VALUE: response.name },
			]);
		} else if (isSuccess === false) {
			this.showError(this.getLang('EXCHANGE_FAIL', {
				'REASON': response.message,
			}));
		} else {
			this.showError(this.getLang('EXCHANGE_UNKNOWN_FAIL'));
		}
	}

	refresh() : void {
		const clientId = this.clientId();

		if (clientId == null || clientId === '') {
			this.updateTokens([]);
			return;
		}

		$.ajax({
			url: this.options.refreshUrl,
			type: 'POST',
			data: {
				clientId: this.clientId(),
			},
			dataType: 'json',
		})
			.then(this.refreshEnd, this.refreshStop);
	}

	refreshStop = (xhr, textStatus: string, errorThrown: string) : void => {
		this.showError(this.getLang('REFRESH_HTTP_ERROR', {
			'REASON':  xhr.status !== 200
				? `HTTP ${xhr.status}`
				: (errorThrown || textStatus)
		}));
	}

	refreshEnd = (response: Object) : void => {
		const isSuccess = response?.success;

		if (isSuccess === true) {
			this.updateTokens(response.variants);
		} else if (isSuccess === false) {
			this.showError(this.getLang('REFRESH_FAIL', {
				'REASON': response.message,
			}));
		} else {
			this.showError(this.getLang('REFRESH_UNKNOWN_FAIL'));
		}
	}

	updateTokens(variants: Array) : void {
		const select = this.getElement('select');

		this.clearSelect(select);
		this.drawSelect(select, variants);
	}

	clearSelect(select: $) : void {
		select.find('option')
			.filter((index, option) => option.value !== '')
			.remove();
	}

	drawSelect(select: $, variants: Array) : void {
		let lastOption;

		for (const variant of variants) {
			const option = $('<option />');

			option.prop('value', variant['ID']);
			option.text(variant['VALUE']);

			select.append(option);
			lastOption = option;
		}

		lastOption?.prop('selected', true);
	}

	showError(message: string) : void {
		alert(message);
	}

	clientId() : string {
		return this.formInput('clientId').val();
	}

	clientPassword() : string {
		return this.formInput('clientPassword').val();
	}

	formInput(type: string) : $ {
		const form = this.$el.closest('form');

		return this.getElement(type, form);
	}
}
