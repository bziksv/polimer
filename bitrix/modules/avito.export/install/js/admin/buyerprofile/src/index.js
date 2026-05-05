import {Skeleton} from "../../../plugin/skeleton";

// noinspection JSUnresolvedVariable
const $ = window.AvitoJQuery ?? window.$;

// noinspection JSUnusedGlobalSymbols
export class BuyerProfile extends Skeleton {

	static defaults = {
		editUrl: null,
		refreshUrl: null,
		refreshTimeout: 2000,

		personTypeId: null,
		personTypeElement: 'select[name="PERSON_TYPE"]',

		userId: null,

		inputElement: 'select',
		optionElement: 'option',

		lang: {},
		langPrefix: 'AVITO_EXPORT_ADMIN_BUYER_PROFILE_'
	}

	constructor(element: HTMLElement, options: Object = {}) {
		super(element, options);
		this.bind();
	}

	bind() : void {
		this.handleClick(true);
		this.handlePersonTypeChange(true);
	}

	unbind() : void {
		this.handleClick(false);
		this.handlePersonTypeChange(false);
	}

	handleClick(dir: boolean) : void {
		this.$el[dir ? 'on' : 'off']('click', this.onClick);
	}

	handlePersonTypeChange(dir: boolean) : void {
		const input = this.getFormInput('personType');

		input[dir ? 'on' : 'off']('change', this.onPersonTypeChange);
	}

	onClick = () : void => {
		this.openEditWindow();
		this.refreshDelay();
	}

	onPersonTypeChange = () : void => {
		this.refreshDelay(100);
	}

	openEditWindow() : Window {
		const editUrl = this.getEditUrl();

		return this.openWindow(editUrl);
	}

	getEditUrl() : string {
		const query = this.getEditQuery();
		let url = this.options.editUrl;

		if (query !== '') {
			url +=
				(url.indexOf('?') === -1 ? '?' : '&')
				+ query;
		}

		return url;
	}

	getEditQuery() : string {
		const inputValue = this.getInput().val();
		const query = [];

		query.push('personTypeId=' + encodeURIComponent(this.getPersonTypeId()));
		query.push('userId=' + encodeURIComponent(this.getUserId()));

		if (!this.isEmptyValue(inputValue)) {
			query.push('id=' + encodeURIComponent(inputValue));
		}

		return query.join('&');
	}

	refreshDelay(timeout: number = this.options.refreshTimeout) : void {
		this._refreshDelay = setTimeout(this.refresh, timeout);
	}

	refresh = () : void => {
		const personTypeId = this.getPersonTypeId();
		const userId = this.getUserId();

		if (this.isEmptyValue(personTypeId)) {
			this.refreshInput([]);
			return;
		}

		clearTimeout(this._refreshDelay);

		$.ajax({
			url: this.options.refreshUrl,
			type: 'POST',
			data: {
				personTypeId: personTypeId,
				userId: userId,
			},
			dataType: 'json',
		})
			.then(this.refreshEnd);
	}

	refreshEnd = (response: Object) : void => {
		if (!response || response.status !== 'ok') {
			alert(this.getLang('REFRESH_FAIL', {
				'MESSAGE': response.message ?? '',
			}));

			return;
		}

		this.refreshInput(response.enum);
	}

	refreshInput(values: Array) : void {
		const input = this.getInput();
		const options = this.getElement('option', input);
		const existValueIds = [];
		let firstFound = null;

		// create new

		for (const value of values) {
			let option = this.searchOption(options, value.ID);

			if (option == null) {
				option = this.createOption(input, value);
			} else if (option.textContent !== value.VALUE) {
				option.textContent = value.VALUE;
			}

			if (firstFound == null) {
				firstFound = option;
			}

			existValueIds.push(value.ID);
		}

		// delete non-exists

		for (let optionIndex = 0; optionIndex < options.length; optionIndex++) {
			const option = options[optionIndex];

			if (existValueIds.indexOf(option.value) === -1 && !this.isEmptyValue(option.value)) {
				option.parentElement.removeChild(option);
			}
		}

		// auto select first

		if (firstFound !== null && this.isEmptyValue(input.val())) {
			this.selectOption(firstFound);
		}
	}

	searchOption(options: $, tokenId: number) : HTMLOptionElement {
		let result;

		for (let i = 0; i < options.length; i++) {
			const option = options[i];

			if (String(option.value) === String(tokenId)) {
				result = option;
				break;
			}
		}

		return result;
	}

	createOption(input: $, profile: Object) : HTMLOptionElement {
		const option = document.createElement('option');

		option.value = profile.ID;
		option.textContent = profile.VALUE;

		input.append(option);

		return option;
	}

	selectOption(option: HTMLOptionElement) : void {
		option.selected = true;
	}

	getInput() : $ {
		const parent = this.$el.parent();

		return this.getElement('input', parent);
	}

	isEmptyValue(value) : boolean {
		return value === null || value === '';
	}

	getForm() : $ {
		return $(this.el.form);
	}

	getFormInput(name: string) : $ {
		const form = this.getForm();

		return this.getElement(name, form);
	}

	getPersonTypeId() : ?string {
		const input = this.getFormInput('personType');
		let result;

		if (input.length > 0) {
			result = input.val();
		} else {
			result = this.options.personTypeId;
		}

		return result;
	}

	getUserId() : string {
		return this.options.userId;
	}

	openWindow(url: string) : Window {
		const result = window.open(url, '_blank');

		result.focus();

		return result;
	}
}