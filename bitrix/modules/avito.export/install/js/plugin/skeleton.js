import {compileTemplate} from "./utils";

// noinspection JSUnresolvedVariable
const $ = window.AvitoJQuery ?? window.$;

export class Skeleton {

	static defaults = {
		lang: {},
		langPrefix: null
	};

	constructor(element: HTMLElement, options: Object = {}) {
		this.$el = $(element);
		this.el = this.$el[0];
		this.options = Object.assign({}, this.constructor.defaults, this.$el.data(), options);
	}

	destroy() {
		this.el = null;
	}
	
	getElement(key, context, method) : JQuery {
		const selector = this.getElementSelector(key);
	
		return this.getNode(selector, context, method || 'find');
	}
	
	getElementSelector(key: string) : string {
		const optionKey = key + 'Element';
	
		return this.options[optionKey];
	}
	
	getTemplate(key: string) : string {
		const optionKey = key + 'Template';
		const option = this.options[optionKey];
		const optionFirstSymbol = option.substring(0, 1);
		let result;
	
		if (optionFirstSymbol === '.' || optionFirstSymbol === '#') {
			result = this.getNode(option).html();
		} else {
			result = option;
		}
	
		return result;
	}
	
	getNode(selector, context, method = 'find') : JQuery {
		if (selector.substring(0, 1) === '#') { // is id query
			context = $(document);
		} else if (!context) {
			context = this.$el;
		}
	
		return context[method](selector);
	}
	
	getLang(key, replaces) : string {
		let langKey;
		let result;
	
		if (this.options.lang != null && key in this.options.lang) {
			result = this.options.lang[key];
		} else {
			langKey = this.options.langPrefix + key;
			result = BX.message(langKey) || '';
		}
	
		if (result && replaces) {
			result = compileTemplate(result, replaces);
		}
	
		return result;
	}
}
