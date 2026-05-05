// @flow

import {Skeleton} from "../../../../../js/plugin/skeleton";

export class Row extends Skeleton {

	static defaults = {
		categoryElement: '[data-entity="category"]',
		limitElement: '[data-entity="limit"]',
		deleteElement: '[data-entity="delete"]',

		onDelete: () => {},

		lang: {},
	}

	constructor(element: HTMLElement, options: Object = {}) {
		super(element, options);
		this.bind();
	}

	clone() : Row {
		const element = this.$el.clone(false, false);
		const result = new Row(element, this.options);

		result.clearClone();

		return result;
	}

	destroy() : void {
		this.destroyCategory();
		this.unbind();

		super.destroy();
	}

	destroyCategory() {
		this._category.destroy();
	}

	boot() : void {
		this.bootCategory();
	}

	bootCategory() {
		const field = this.getElement('category');

		this._category = new BX.AvitoExport.Admin.Property.Category(field, {
			component: 'avito.export:admin.property.category',
			language: 'ru',
			allowClear: false,
			lang: {
				VALUE_PLACEHOLDER: this.getLang('VALUE_PLACEHOLDER'),
			},
		});
	}

	bind() : void {
		this.handleDeleteClick(true);
	}

	unbind() : void {
		this.handleDeleteClick(false);
	}

	handleDeleteClick(dir: boolean) : void {
		const element = this.getElement('delete');

		element[dir ? 'on' : 'off']('click', this.onDeleteClick);
	}

	onDeleteClick = () : void => {
		this.options.onDelete(this);
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

	enableControls() : void {
		for (const control of this.controls()) {
			control.prop('disabled', false);
		}
	}

	disableControls() : void {
		for (const control of this.controls()) {
			control.prop('disabled', true);
		}
	}

	controls() : Array {
		return [
			this.getElement('category'),
			this.getElement('limit'),
		];
	}

	clear() {
		for (const element of this.controls()) {
			if (element.is('select')) {
				element
					.find('option')
					.remove();
			} else {
				element.val('');
			}
		}
	}

	clearClone() : void {
		for (const element of this.controls()) {
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
}