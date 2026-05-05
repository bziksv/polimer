// @flow

import {Skeleton} from "../../../../../js/plugin/skeleton";
import {Transport} from "./transport";

// noinspection JSUnresolvedVariable
const $ = window.AvitoJQuery ?? window.$;

// noinspection JSUnusedGlobalSymbols
export class Category extends Skeleton {

	static defaults = {
		component: null,
		language: 'ru',
		allowClear: true,
	}

	constructor(element: HTMLElement, options: Object = {}) {
		super(element, options);
		this.options = Object.assign({}, this.constructor.defaults, options);
		this.transport = new Transport(this.options.component);

		this.bootSelect();
		this.patchFocus();
	}

	bootSelect() {
		this.$el.select2({
			//dropdownCssClass: 'increasedzindexclass', missing at minimal version of select2 (conflict with acrit.core)
			language: this.options.language,
			minimumInputLength: 2,
			placeholder: this.getLang('VALUE_PLACEHOLDER'),
			allowClear: this.options.allowClear,
			selectOnClose: true,
			ajax: {
				cache: true,
				delay: 250,
				transport: this.ajaxTransport,
			},
			templateSelection: this.templateSelection,
		});
	}

	patchFocus() {
		this.$el.on('select2:open', () => {
			setTimeout(() => {
				const search = $('.select2-container--open .select2-search__field').last().get(0);
				search?.focus();
			}, 100);
		});
	}

	ajaxTransport = (params: Object, success: () => {}, failure: () => {}) : void => {
		this.transport
			.fetch('suggest', {
				query: params.data.q,
			})
			.then((options: Array) => this.convertOptions(options))
			.then((variants: Array) => { success(variants) })
			.catch((error) => { failure(error.message) });
	}

	convertOptions(options: Array) : Object {
		const variants = [];

		for (const option of options) {
			variants.push({
				id: option['ID'],
				text: option['VALUE'],
				disabled: option['DISABLED'],
			})
		}

		return {
			results: variants,
		}
	}

	templateSelection = (variant: Object) : string => {
		return variant.id || variant.text;
	}
}