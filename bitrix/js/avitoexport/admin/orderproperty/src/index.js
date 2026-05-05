import {Skeleton} from "../../../plugin/skeleton";
import {Fetcher} from "./fetcher";

// noinspection JSUnresolvedVariable
const $ = window.AvitoJQuery ?? window.$;
const fetcher = new Fetcher();

// noinspection JSUnusedGlobalSymbols
export class OrderProperty extends Skeleton {

	static defaults = {
		refreshUrl: null,
		refreshTimeout: 100,

		personTypeId: null,
		personTypeElement: 'select[name="PERSON_TYPE"]',

		inputElement: 'select',
		optionElement: 'option',
	}

	constructor(element: HTMLElement, options: Object = {}) {
		super(element, options);
		this.bind();
	}

	bind() : void {
		this.handlePersonTypeChange(true);
	}

	unbind() : void {
		this.handlePersonTypeChange(false);
	}

	handlePersonTypeChange(dir: boolean) : void {
		const input = this.getFormInput('personType');

		input[dir ? 'on' : 'off']('change', this.onPersonTypeChange);
	}

	onPersonTypeChange = () : void => {
		this.refreshDelay();
	}

	refreshDelay() : void {
		this._refreshDelay = setTimeout(this.refresh, this.options.refreshTimeout);
	}

	refresh = () : void => {
		const personTypeId = this.getPersonTypeId();

		if (this.isEmptyValue(personTypeId)) {
			this.draw([]);
			return;
		}

		clearTimeout(this._refreshDelay);

		fetcher.load(this.options.refreshUrl, personTypeId)
			.then(this.draw);
	}

	draw = (values: Array) : void => {
		const previous = this.$el.val();

		this.clear();
		this.appendNew(values, previous);
	}

	clear() {
		this.getElement('option')
			.filter((index: number, option: HTMLOptionElement) => !this.isEmptyValue(option.value))
			.remove();
	}

	appendNew(values: Array, selected: ?string) {
		for (const value of values) {
			const option = this.createOption(value);

			this.$el.append(option);

			if (selected != null && String(selected) === String(option.value)) {
				option.selected = true;
			}
		}
	}

	createOption(profile: Object) : HTMLOptionElement {
		const option = document.createElement('option');

		option.value = profile.ID;
		option.textContent = profile.VALUE;

		return option;
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
}