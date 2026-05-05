import {Transport} from "../transport";

// noinspection JSUnresolvedVariable
const $ = window.AvitoJQuery ?? window.$;

export class ValueSkeleton {

	constructor(element: HTMLElement) {
		this.$el = $(element);
		this._field = null;
		this._multiple = false;
	}

	boot() : void {
		// nothing by default
	}

	destroy() : void {
		// nothing by default
	}

	type() : string {
		throw new Error('not implemented');
	}

	transport(transport: Transport) : void {
		this._transport = transport;
	}

	field(field: Object, multiple: boolean) : void {
		this._field = field;
		this._multiple = multiple;
	}

	render() : void {
		const element = $(this.template());
		const newInput = this.findInput(element);
		const oldInput = this.findInput(this.$el);
		const anchor = this.findAnchor(this.$el);

		newInput.attr('name', oldInput.attr('name'));

		this.copyName(oldInput, newInput);

		anchor.replaceWith(element);
		this.$el = newInput;
	}

	findInput(element: JQuery) : JQuery {
		const selector = '[data-entity="value"]';

		return element.is(selector) ? element : element.find(selector).eq(0);
	}

	copyName(from: JQuery, to: JQuery) : void {
		const fromName = from.attr('name').replace(/\[]$/, '');

		to.attr('name', fromName + (this._multiple ? '[]' : ''));
	}

	findAnchor(element: JQuery) : JQuery {
		const anchor = element.closest('[data-entity="valueAnchor"]');

		return anchor.length > 0 ? anchor : element;
	}

	reflow() : void {
		// nothing by default
	}

	template() : string {
		return '';
	}

}

