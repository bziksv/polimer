// @flow

import {Skeleton} from "../../../plugin/skeleton";

// noinspection JSUnusedGlobalSymbols
export class LabelSelect extends Skeleton {

	static defaults = {
		valueElement: '.js-avito-label-select__value',
		selectElement: 'select',
	}

	constructor(element: HTMLElement, options: Object = {}) {
		super(element, options);
		this.bind();
	}

	destroy() : void {
		this.unbind();
		super.destroy();
	}

	bind() : void {
		this.handleChange(true);
	}

	unbind() : void {
		this.handleChange(false);
	}

	handleChange(dir: boolean) : void {
		const select = this.getElement('select');

		select[dir ? 'on' : 'off']('change', this.onChange);
	}

	onChange = () : void => {
		this.update();
	}

	update() : void {
		const select = this.getElement('select');
		const valueElement = this.getElement('value');
		let options = select.find('option').filter(':selected');

		if (options.length === 0) { options = select.find('option').eq(0); }

		valueElement.html(
			options
				.map((index, option) => { return option.textContent; })
				.get()
				.join(', ')
		);
	}
}