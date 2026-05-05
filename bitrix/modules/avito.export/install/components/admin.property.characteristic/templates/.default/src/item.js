// @flow

import {Suggest} from "./suggest";
import {Field} from "./field";

export class Item {

	static defaults = {
		transport: null,
		state: null,
		attribute: null,
		attributeElement: '[data-entity="attribute"]',
		valueElement: '[data-entity="value"]',
		deleteElement: '[data-entity="delete"]',
	};

	constructor(field: Field, element: HTMLElement, options: Object = {}) {
		this.field = field;
		this.el = element;
		this.options = Object.assign({}, this.constructor.defaults, options);

		this.resolveAttribute();
		this.initSuggest();
		this.bind();
	}

	destroy() : void {
		this.unbind();

		this.options = null;
		this.el = null;
	}

	bind() : void {
		this.handleDeleteClick(true);
	}

	unbind() : void {
		this.handleDeleteClick(false);
	}

	handleDeleteClick(dir: boolean) : void {
		const button = this.el.querySelector(this.options.deleteElement);

		button?.[dir ? 'addEventListener' : 'removeEventListener']('click', this.onDeleteClick);
	}

	onDeleteClick = () => {
		this.field.delete(this);
	}

	resolveAttribute() : void {
		if (this.options.attribute != null) { return; }

		const element = this.el.querySelector(this.options.attributeElement);

		this.options.attribute = (element instanceof HTMLInputElement ? element.value : element.textContent).trim();
	}

	initSuggest() : void {
		const element = this.el.querySelector(this.options.valueElement);

		this.suggest = new Suggest(this, element, {
			transport: this.options.transport,
			state: this.options.state,
			attribute: this.options.attribute,
			values: () => this.collectValues(),
		});
	}

	boot() : void {
		this.suggest.boot();
	}

	autoload() : void {
		// noinspection JSIgnoredPromiseFromCall
		this.load();
	}

	load() : Promise {
		return this.suggest.load();
	}

	collectValues() : Object {
		return this.field.values(this);
	}

	attribute() : string {
		return this.options.attribute;
	}

	redraw(variants: Array) : void {
		this.suggest.redraw(variants);
	}

	value() : string|string[] {
		return this.suggest.selected();
	}

	refresh() : void {
		this.field.refresh(this);
	}

	allowDelete() : void {
		const button = this.el.querySelector(this.options.deleteElement);
		if (button === null) { return; }

		button.disabled = false;
	}

	disableDelete() : void {
		const button = this.el.querySelector(this.options.deleteElement);
		if (button === null) { return; }

		button.disabled = true;
	}
}