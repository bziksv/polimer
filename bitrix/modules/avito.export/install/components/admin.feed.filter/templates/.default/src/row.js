// @flow

import {Skeleton} from "../../../../../js/plugin/skeleton";
import {ValueFactory} from "./value/factory";
import {ValueSkeleton} from "./value/skeleton";

// noinspection JSUnresolvedVariable
const $ = window.AvitoJQuery ?? window.$;

export class Row extends Skeleton {

	static defaults = {
		fieldElement: '[data-entity="field"]',
		compareElement: '[data-entity="compare"]',
		valueElement: '[data-entity="value"]',
		deleteElement: '[data-entity="delete"]',
		glueElement: '[data-entity="glue"]',

		transport: null,
		fields: [],

		onDelete: () => {},

		lang: {},
	}

	constructor(element: HTMLElement, options: Object = {}) {
		super(element, options);
		this._stored = {};

		this.touchValue('field');
		this.touchValue('compare');
		this.bind();
	}

	clone() : Row {
		const element = this.$el.clone(false, false);
		const result = new Row(element, this.options);

		result.clearClone();

		return result;
	}

	destroy() : void {
		this.unbind();
		this.destroyField();
		super.destroy();
	}

	bind() : void {
		this.handleDeleteClick(true);
		this.handleFieldChange(true);
		this.handleCompareChange(true);
	}

	unbind() : void {
		this.handleDeleteClick(false);
		this.handleFieldChange(false);
		this.handleCompareChange(false);
	}

	handleDeleteClick(dir: boolean) : void {
		const element = this.getElement('delete');

		element[dir ? 'on' : 'off']('click', this.onDeleteClick);
	}

	handleFieldChange(dir: boolean) : void {
		const element = this.getElement('field');

		element[dir ? 'on' : 'off']('change keyup', this.onFieldChange);
	}

	handleCompareChange(dir: boolean) : void {
		const element = this.getElement('compare');

		element[dir ? 'on' : 'off']('change keyup', this.onCompareChange);
	}

	onDeleteClick = () : void => {
		this.options.onDelete(this);
	}

	onFieldChange = () : void => {
		if (!this.touchValue('field')) { return; }

		this.reflowCompare();
		this.reflowValue();
	}

	onCompareChange = () : void => {
		if (!this.touchValue('compare')) { return; }

		this.reflowValue();
	}

	touchValue(field: string) : boolean {
		const element = this.getElement(field);
		const value = element.val();

		if (this._stored[field] === value) { return false; }

		this._stored[field] = value;

		return true;
	}

	reflowCompare() : void {
		const filter = this.selectedField();
		const compare = this.getElement('compare');
		const options = compare.find('option');
		let firstAvailable;
		let changed = false;

		options.each((index, option: HTMLOptionElement) => {
			const disabled = filter['CONDITIONS'].indexOf(option.value) === -1;

			if (disabled) {
				if (option.selected) { changed = true; }

				option.disabled = true;
				option.selected = false;
			} else {
				if (firstAvailable == null) { firstAvailable = option; }

				option.disabled = false;
			}
		});

		if (changed && firstAvailable != null) {
			firstAvailable.selected = true;
		}
	}

	bootValue() : void {
		if (this._valueUi != null) { return; }

		const filter = this.selectedField();
		const multiple = this.isValueMultiple();

		this._valueUi = this.createValueUi(filter, multiple);
		this._valueUi.boot();
	}

	reflowValue() : void {
		const filter = this.selectedField();
		const multiple = this.isValueMultiple();

		if (this._valueUi != null && filter['TEMPLATE'] === this._valueUi.type()) {
			this._valueUi.field(filter, multiple);
			this._valueUi.reflow();
		} else {
			if (this._valueUi != null) {
				this._valueUi.destroy();
			}

			this._valueUi = this.createValueUi(filter, multiple);
			this._valueUi.render();
			this._valueUi.boot();
		}
	}

	createValueUi(filter: Object, multiple: boolean) : ValueSkeleton {
		const element = this.getElement('value');
		const valueUi = ValueFactory.make(element, filter['TEMPLATE']);

		valueUi.transport(this.options.transport);
		valueUi.field(filter, multiple);

		return valueUi;
	}

	selectedField() : Object {
		const field = this.getElement('field');

		return this.findFilter(field.val());
	}

	isValueMultiple() : boolean {
		const options = this.getElement('compare').find('option');
		let selected = options.filter(':selected')

		if (selected.length === 0) {
			selected = options.filter(':disabled').eq(0);
		}

		return selected.data('multiple') != null;
	}

	findFilter(id: string) : Object {
		let result;

		for (const filter of this.options.fields) {
			if (filter['ID'] === id) {
				result = filter;
				break;
			}
		}

		if (result == null) {
			throw new Error('cant find filter for ' + id);
		}

		return result;
	}

	reset() : void {
		this.resetField();
		this.touchValue('field');
		this.reflowCompare();
		this.resetCompare();
		this.touchValue('compare');
		this.reflowValue();
	}

	resetField() : void {
		this.getElement('field').find('option').each((index, option) => { option.selected = false });
	}

	resetCompare() : void {
		this.getElement('compare').find('option').each((index, option) => { option.selected = false });
	}

	focus() : void {
		this.getElement('field').focus();
	}

	allowDelete(dir: boolean) : void {
		const element = this.getElement('delete');

		element.prop('disabled', !dir);
		element.toggleClass('avito--hidden', !dir);
	}

	updateControlName(baseName: string) : void {
		for (const control of this.controls()) {
			const currentName = control.attr('name');
			const nameLastMatches = currentName != null ? /\[\w+](\[])?$/.exec(currentName) : null;

			if (nameLastMatches == null) { continue; }

			const newName = baseName + nameLastMatches[0];

			control.attr('name', newName);
		}
	}

	controls() : Array {
		return [
			this.getElement('glue'),
			this.getElement('field'),
			this.getElement('compare'),
			this.getElement('value'),
		];
	}

	boot() : void {
		this.bootField();
		this.bootValue();
	}

	bootField() : void {
		const element = this.getElement('field');

		if (!element.is('select') || element.hasClass('select2-hidden-accessible')) { return; }

		element.select2();

		// patch focus for jquery3.6

		element.on('select2:open', () => {
			setTimeout(() => {
				const search = $('.select2-container--open .select2-search__field').last().get(0);
				search?.focus();
			}, 100);
		});
	}

	destroyField() : void {
		const element = this.getElement('field');

		if (!element.is('select') || !element.hasClass('select2-hidden-accessible')) { return; }

		element.select2('destroy');
		element.off('select2:open');
	}

	clearClone() : void {
		const controls = [
			this.getElement('field'),
			this.getElement('value'),
		];

		for (const element of controls) {
			if (!element.is('select')) { continue; }

			const elementNext = element.next();

			if (element.hasClass('select2-hidden-accessible')) {
				element
					.removeClass('select2-hidden-accessible')
					.removeAttr('data-select2-id')
					.removeAttr('aria-hidden')
					.removeAttr('tabindex');

				element.find('optgroup')
					.removeAttr('data-select2-id');

				element.find('option')
					.removeAttr('data-select2-id');
			}

			if (elementNext.hasClass('select2')) {
				elementNext.remove();
			}
		}
	}

	setLevel(level: number) : void {
		const previousLevel = level === 1 ? 0 : 1;

		this.$el.removeClass('level--' + previousLevel);
		this.$el.addClass('level--' + level);
	}

	setGlue(glue: string) : void {
		const input = this.getElement('glue');

		input.val(glue);
	}
}