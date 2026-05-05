// @flow

import {Transport} from "./transport";
import {Item} from "./item";
import {compileTemplate, htmlToElement} from "./utils";

// noinspection JSUnresolvedVariable
const $ = window.AvitoJQuery ?? window.$;

export class Suggest {

	static defaults = {
		transport: null,
		state: null,
		attribute: null,
		multiSelectElement: 'select[multiple]',
		multiSelectTemplate: '<select multiple>#OPTIONS#</select>',
		selectElement: 'select:not([multiple])',
		selectTemplate: '<select>#OPTIONS#</select>',
		inputElement: 'input',
		inputTemplate: '<input type="text" autocomplete="off" />',
		values: {},
	};

	constructor(item: Item, element: HTMLElement, options: Object = {}) {
		this.item = item;
		this.el = element;
		this.options = Object.assign({}, this.constructor.defaults, options);
		this._stored = null;
		this._variants = null;
		this._state = this.options.state;
		this._focusPrevent = false;

		this.bind();
	}

	bind() : void {
		this.handleInput(true);
	}

	unbind() : void {
		this.handleInput(false);
	}

	handleInput(dir: boolean, input: HTMLInputElement|HTMLSelectElement = null) : void {
		this.handleFocus(dir, input);
		this.handleChange(dir, input);
	}

	handleFocus(dir: boolean, input: HTMLInputElement|HTMLSelectElement = null) : void {
		if (input == null) { input = this.input(); }

		input?.[dir ? 'addEventListener' : 'removeEventListener']('focus', this.onFocus);

		if (input.tagName.toLowerCase() === 'select') {
			$(input)[dir ? 'on' : 'off']('select2:opening', this.onFocus);
		}
	}

	handleChange(dir: boolean, input: HTMLInputElement|HTMLSelectElement = null) : void {
		if (input == null) { input = this.input(); }

		input?.[dir ? 'addEventListener' : 'removeEventListener']('change', this.onChange);
	}

	onFocus = () => {
		if (this._focusPrevent) { return; }

		// noinspection JSIgnoredPromiseFromCall
		this.load();
	}

	onChange = () => {
		if (!this.hasVariants()) { return; }

		this.normalize();
		this.item.refresh();
	}

	boot() : void {
		const input = this.input();

		this.bootControl(input);
	}

	load() : Promise {
		const values = this.values();

		if (!this.isChanged(values)) { return Promise.resolve(); }

		this.store(values);
		this.clear();
		this._state?.loading();

		return this.query(values)
			.then((response) => {
				this.render(response.variants, response.multiple);
				this._state?.waiting();
			})
			.catch((error) => {
				this._state?.error(error)
				this.release();
			});
	}

	isChanged(values: Object) : boolean {
		if (this._stored == null) { return true; }

		let result = false;

		for (const key of Object.keys(values)) {
			const value = values[key];
			const stored = this._stored[key];

			if (!this.compareStored(value, stored)) {
				result = true;
				break;
			}
		}

		return result;
	}

	compareStored(first: string|string[], second: string|string[]) {
		const firstArray = Array.isArray(first);
		const secondArray = Array.isArray(second);

		if (firstArray !== secondArray) { return false; }

		if (firstArray) {
			if (first.length !== second.length) { return false; }

			for (const key in first) {
				if (!first.hasOwnProperty(key)) { continue; }

				if (!this.compareStored(first[key], second[key])) {
					return false;
				}
			}

			return true;
		}

		return (first ?? '').trim().toLowerCase() === (second ?? '').trim().toLowerCase();
	}

	store(values: Object) : void {
		this._stored = values;
	}

	release() : void {
		this._stored = null;
	}

	query(values) : Promise {
		return this.transport().fetch('variants', {
			attribute: this.options.attribute,
			values: values,
		});
	}

	transport() : Transport {
		const option = this.options.transport;

		if (!(option instanceof Transport)) {
			throw new Error('transport must be instance of Transport');
		}

		return option;
	}

	clear() : void {
		const input = this.input();
		const tagName = input?.tagName?.toLowerCase();

		if (tagName !== 'select') { return; }

		input
			.querySelectorAll('option')
			.forEach((option) => {
				if (!option.selected) { option.remove(); }
			});
	}

	redraw(variants: Array) : void {
		this.store(this.values());
		this.render(variants);
	}

	render(variants: Array, multiple: boolean) : void {
		const input = this.input();
		const type = (multiple ? 'multiSelect' : (variants.length > 0 ? 'select' : 'input'));
		const html = this.controlHtml(type, variants);
		const selector = this.options[type + 'Element'];
		const selected = this.selected(input);
		const focused = this.isFocused(input);
		let newInput;

		if (input == null) {
			newInput = htmlToElement(html);
			this.el.insertAdjacentElement('beforeend', newInput);
		} else if (input.matches(selector)) {
			newInput = this.redrawControl(input, html);
		} else {
			newInput = this.replaceControl(input, html);
		}

		this.setInputValue(newInput, selected);

		if (focused) {
			this.focus(newInput);
		}

		this._variants = variants;
	}

	controlHtml(type: string, variants: Array) : string {
		if (type === 'input') { return this.options.inputTemplate; }

		const template = this.options[type + 'Template'];
		const options = variants
			.map((variant: string) => `<option>${BX.util.htmlspecialchars(variant)}</option>`)
			.join('');

		return compileTemplate(template, {
			OPTIONS: options,
		});
	}

	input() : HTMLInputElement|HTMLSelectElement {
		return this.el.querySelector('select, input');
	}

	isFocused(input: HTMLInputElement|HTMLSelectElement) : boolean {
		if (input.classList.contains('select2-hidden-accessible')) {
			return $(input).select2('isOpen');
		}

		return document.activeElement === input;
	}

	focus(input: HTMLInputElement|HTMLSelectElement) : void {
		this._focusPrevent = true;

		if (input.classList.contains('select2-hidden-accessible')) {
			const $input = $(input);

			$input.select2('close');
			$input.select2('open');
		} else {
			input.focus();
		}

		this._focusPrevent = false;
	}

	selected(input: HTMLInputElement|HTMLSelectElement = null) : string|string[] {
		if (input == null) { input = this.input(); }
		
		if (!input.multiple) { return input.value; }

		const selected = [];

		for (const option of input.querySelectorAll('option')) {
			if (!option.selected) { continue; }

			selected.push(option.value);
		}

		return selected;
	}

	redrawControl(input: HTMLInputElement|HTMLSelectElement, html: string) : HTMLInputElement|HTMLSelectElement {
		const newInput = htmlToElement(html);
		const contents = newInput.innerHTML.trim();

		if (contents === '') { return input; }

		input.innerHTML = contents;

		return input;
	}

	setInputValue(input: HTMLElement, selected: string|string[]) : void {
		if (input.tagName.toLowerCase() === 'select') {
			input
				.querySelectorAll('option')
				.forEach((option) => {
					option.selected = (
						Array.isArray(selected)
							? (selected.indexOf(option.value) !== -1)
							: (option.value === selected)
					);
				});
		} else {
			input.value = (Array.isArray(selected) ? selected[0] : (selected)) ?? '';
		}
 	}

	replaceControl(input: HTMLInputElement|HTMLSelectElement, html: string) : HTMLInputElement|HTMLSelectElement {
		const newInput = htmlToElement(html);

		this.handleInput(false, input);
		this.destroyControl(input);

		newInput.name = this.sanitizeName(input.name, !!newInput.multiple);
		newInput.className = input.className;

		input.after(newInput);
		input.remove();

		this.bootControl(newInput);
		this.handleInput(true, newInput);

		return newInput;
	}

	sanitizeName(name: string, multiple: boolean) : string {
		if (/\[]$/.test(name) === multiple) { return name; }

		if (multiple) { return name + '[]'; }

		return name.replace(/\[]$/, '');
	}

	destroyControl(input: HTMLInputElement|HTMLSelectElement) : void {
		if (input.tagName.toLowerCase() !== 'select') { return; }

		$(input).select2('destroy');
	}

	bootControl(input: HTMLInputElement|HTMLSelectElement) : void {
		if (input.tagName.toLowerCase() !== 'select') { return; }

		$(input).select2();
	}

	normalize() : void {
		const input = this.input();

		if (!input.multiple) {
			this.el.value = this.matched(input.value) ?? '';
			return;
		}

		for (const option of input.querySelectorAll('option')) {
			if (!option.selected) { continue; }

			option.selected = (this.matched(option.value) != null);
		}
	}

	hasVariants() : boolean {
		return this._variants != null && this._variants.length > 0;
	}

	matched(value: string) : ?string {
		if (this._variants == null) { return null; }

		let result;

		for (const variant of this._variants) {
			if (this.compare(value, variant)) {
				result = variant;
				break;
			}
		}

		return result;
	}

	compare(first: string, second: string) {
		return (first ?? '').trim().toLowerCase() === (second ?? '').trim().toLowerCase();
	}

	values() : Object {
		const option = this.options.values;

		return typeof option === 'function' ? option() : option;
	}
}