import {Skeleton} from "../../../plugin/skeleton";

// noinspection JSUnresolvedVariable
const $ = window.AvitoJQuery ?? window.$;

// noinspection JSUnusedGlobalSymbols
export class CourierTime extends Skeleton {

	static defaults = {
		dateElement: '[data-entity="date"]',
		timeElement: '[data-entity="time"]',

		lang: {},
		langPrefix: 'AVITO_EXPORT_ADMIN_COURIER_TIME_'
	}

	constructor(element: HTMLElement, options: Object = {}) {
		super(element, options);
		this.bind();
	}

	bind() : void {
		const anchor = this.eventAnchor();
		const date = this.getElement('date');

		anchor.on('avitoCourierTime', this.onCourierTime);
		anchor.on('avitoCourierAddressReset', this.onAddressReset);
		date.on('change', this.onDateChange);
	}

	onCourierTime = (evt, data) : void => {
		const dateOption = this.dateOptions(data.options);

		if (dateOption == null) {
			this.resetTime();
		} else {
			this.timeOptions(dateOption['INTERVALS']);
		}
	}

	onAddressReset = () : void => {
		this.resetDate();
		this.resetTime();
	}

	onDateChange = (evt) : void => {
		const options = this.searchIntervals(evt.target.value);

		if (options == null) {
			this.resetTime();
		} else {
			this.timeOptions(options);
		}
	}

	dateOptions(options: Array) : ?Object {
		const dateSelect = this.getElement('date');
		const selected = dateSelect.val();
		const selectedOption = this.searchOption(options, selected);

		dateSelect.prop('disabled', false);
		dateSelect.empty();
		this.renderPlaceholder(dateSelect, this.getLang('CHOOSE_DATE'), selectedOption != null);
		this.renderOptions(dateSelect, options, selected);

		this._dateOptions = options;

		return selectedOption;
	}

	resetDate() : void {
		const dateSelect = this.getElement('date');

		dateSelect.prop('disabled', true);
		dateSelect.find('option').prop('selected', false);
		this.renderPlaceholder(dateSelect, this.getLang('INPUT_ADDRESS'));
	}

	searchIntervals(selectedDate: ?string) : ?Array {
		const option = this.searchOption(this._dateOptions, selectedDate);

		return option?.['INTERVALS'];
	}

	searchOption(options: Array, selected: ?string) : ?Object {
		if (selected == null || selected === '') { return null; }

		let result = null;

		for (const option of options) {
			if (option['ID'] === selected) {
				result = option;
				break;
			}
		}

		return result;
	}

	timeOptions(options: Array) : ?Object {
		const timeSelect = this.getElement('time');
		const selected = timeSelect.val();
		const selectedOption = this.searchOption(options, selected);

		timeSelect.prop('disabled', false);
		timeSelect.empty();
		this.renderPlaceholder(timeSelect, this.getLang('CHOOSE_TIME'), selectedOption != null);
		this.renderOptions(timeSelect, options, selected);

		return selectedOption;
	}

	resetTime() : void {
		const timeSelect = this.getElement('time');

		timeSelect.prop('disabled', true);
		timeSelect.find('option').prop('selected', false);
		this.renderPlaceholder(timeSelect, '');
	}

	renderPlaceholder(select: JQuery, message: string, hasSelected: boolean = false) : void {
		const option = document.createElement('option');
		option.value = '';
		option.textContent = message;
		option.disabled = true;
		option.selected = !hasSelected;

		select.append(option);
	}
	
	renderOptions(select: JQuery, variants: Array, selected: ?string = null) : void {
		for (const variant of variants) {
			const option = document.createElement('option');
			option.value = variant['ID'];
			option.textContent = variant['VALUE'];
			option.selected = (variant['ID'] === selected);

			select.append(option);
		}
	}

	eventAnchor() : JQuery {
		const form = this.$el.closest('form');

		return form.length > 0 ? form : $(document);
	}
}