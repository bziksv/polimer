// @flow

import {Transport} from "./transport";
import {State} from "./state";
import {Item} from "./item";
import {Attributes} from "./attributes";
import {compileTemplate, htmlToElement} from "./utils";
import {Factory as FormCategory} from "./formcategory/factory";

import "./field.css";
import "./select2.css";

export class Field {

	static defaults = {
		component: null,
		categoryOptions: [],
		lang: {},

		valueName: null,
		attributeName: null,

		itemElement: 'tr',
		itemTemplate:
			'<tr>' +
				'<td class="bx-avito-export-characteristic__label"><input type="hidden" name="#ATTRIBUTE_NAME#" value="#ATTRIBUTE_VALUE#" data-entity="attribute" />#ATTRIBUTE_VALUE#</td>' +
				'<td class="bx-avito-export-characteristic__value" data-entity="value"><select class="bx-avito-export-characteristic__value-control" name="#VALUE_NAME#"></select></td>' +
				'<td class="bx-avito-export-characteristic__actions"><button class="bx-avito-export-characteristic__delete" type="button" data-entity="delete">#DELETE_TITLE#</button></td>' +
			'</tr>',

		addElement: 'input[type="button"]',
	};

	constructor(element: HTMLElement, options: Object = {}) {
		this.el = element;
		this.options = Object.assign({}, this.constructor.defaults, this.nodeOptions(), options);
		this._attributes = null;
		this._newIndex = 0;
		this._transport = new Transport(this.options.component);
		this._transport.middleware(this.transportCategory);
		this._state = new State(this.addButton(), { lang: this.options.lang });

		this.boot();
		this.bind();
	}

	nodeOptions() : Object {
		const result = {};

		for (const key in this.el.dataset) {
			if (!this.el.dataset.hasOwnProperty(key)) { continue; }

			let value = this.el.dataset[key];

			if (value === 'true') {
				value = true;
			} else if (value === 'false') {
				value = false;
			} else if (value.indexOf('{') === 0 || value.indexOf('[') === 0) {
				value = JSON.parse(value);
			}

			result[key] = value;
		}

		return result;
	}

	destroy() : void {
		this.unbind();
	}

	bind() : void {
		this.handleAddClick(true);
	}

	unbind() : void {
		this.handleAddClick(false);
	}

	handleAddClick(dir: boolean) : void {
		const button = this.addButton();

		if (button == null || !button.matches(this.options.addElement)) { return; }

		button[dir ? 'addEventListener' : 'removeEventListener']('click', this.onAddClick);
	}

	onAddClick = (evt) => {
		const values = this.values();

		this.attributes(evt.target).suggest(values);
	}

	boot() : void {
		const elements = this.el.querySelectorAll(this.options.itemElement);
		const items = [];

		for (const element of elements) {
			const item = new Item(this, element, this.itemDefaults());

			item.boot();
			items.push(item);
		}

		this.items = items;
	}

	transportCategory = (action: string, data: Object) : Object => {
		const partials = [];

		for (const options of this.options.categoryOptions) {
			const behavior = FormCategory.make(options.type, this.el, options);
			const values = Object.assign({}, { type: options.type }, behavior.values(action, data));

			partials.push(values);
		}

		return Object.assign({}, data, {
			category: partials,
		});
	}

	attributes(anchor: HTMLElement) : Attributes {
		if (this._attributes != null) { return this._attributes; }

		this._attributes = new Attributes(anchor, {
			transport: this._transport,
			state: this._state,
			lang: this.options.lang,
			onChoose: (attribute) => this.add(attribute),
		});

		return this._attributes;
	}

	add(attribute: string) : Item {
		const item = this.createItem(attribute);

		item.autoload();
		this.reflowDelete();
	}

	createItem(attribute: string) {
		const index = 'n' + (++this._newIndex);
		const html = compileTemplate(this.options.itemTemplate, this.itemVariables(attribute, index));
		const element = htmlToElement(html, 'table');
		const item = new Item(this, element, Object.assign({}, this.itemDefaults(), {
			attribute: attribute,
		}));

		this.el.insertAdjacentElement('beforeend', element);
		item.boot();
		this.items.push(item);

		return item;
	}

	itemVariables(attribute: string, index: string) : Object {
		return {
			DELETE_TITLE: this.options.lang['DELETE'] ?? '',
		};
	}

	itemDefaults() : Object {
		return {
			transport: this._transport,
			state: this._state,
			lang: this.options.lang,
		};
	}

	deleteOnChange(item: Item) : Number {
		let index = this.items.indexOf(item);
		this.delete(item);
		return index--;
	}

	delete(item: Item) : void {
		this.destroyItem(item);
		this.reflowDelete();
		this._state?.waiting();
	}

	destroyItem(item: Item) : void {
		const index = this.items.indexOf(item);

		if (index === -1) { throw new Error('unknown item'); }

		item.el.remove();
		item.destroy();

		this.items.splice(index, 1);
	}

	reflowDelete() : void {
		const count = this.items.length;
		let index = 0;

		for (const item of this.items) {
			if (index === count - 1) {
				item.allowDelete();
			} else {
				item.disableDelete();
			}

			++index;
		}
	}

	values(until: Item = null) : Object {
		const result = {};

		for (const item of this.items) {
			if (until === item) { break; }

			result[item.attribute()] = item.value();
		}

		return result;
	}

	addButton() : HTMLElement {
		return this.el.nextElementSibling;
	}

	refresh(from: Item) : void {
		this._transport
			.fetch('refresh', {
				values: this.values(),
				from: from.attribute(),
			})
			.then((data) => {
				let isFromFound = false;
				let iterable = new Map(this.items.map((valueMap, indexMapx) => {
					return [indexMapx, valueMap];
				}));

				for (let [index, item] of iterable) {
					if (item === from) {
						isFromFound = true;
						continue;
					}

					if (!isFromFound) { continue; }

					const attribute = item.attribute();

					if (typeof data[attribute] === 'undefined') {
						index = this.deleteOnChange(item);
					} else {
						item.redraw(data[attribute] ?? false);
					}
				}
			})
			.catch((error) => {
				this._state.error(error);
			});
 	}
}
