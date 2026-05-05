// @flow

import {Skeleton} from "../../../../../js/plugin/skeleton";
import {TemplateEditor} from "./templateeditor";

// noinspection JSUnresolvedVariable
const $ = window.AvitoJQuery ?? window.$;

export class Row extends Skeleton {

	static defaults = {
		hintElement: '[data-entity="hint"]',
		codeElement: '[data-entity="code"]',
		nameElement: '[data-entity="name"]',
		tagElement: '[data-entity="tag"]',
		valueElement: '[data-entity="value"]',
		formatElement: '[data-entity="format"]',
		templateElement: '[data-entity="template"]',
		deleteElement: '[data-entity="delete"]',
		required: false,
		multiple: false,

		onDelete: () => {},

		lang: {},
	}

	clone() : Row {
		const element = this.$el.clone(false, false);
		const result = new Row(element, this.options);

		result.clearClone();

		return result;
	}

	destroy() : void {
		this.unbind();
		super.destroy();
	}

	bind() : void {
		this.handleDeleteClick(true);
		this.handleValueChange(true);
	}

	unbind() : void {
		this.handleDeleteClick(false);
		this.handleValueChange(false);
	}

	handleDeleteClick(dir: boolean) : void {
		const element = this.getElement('delete');

		element[dir ? 'on' : 'off']('click', this.onDeleteClick);
	}

	handleValueChange(dir: boolean) : void {
		const element = this.getElement('value');

		element[dir ? 'on' : 'off']('change', this.onValueChange);
	}

	onDeleteClick = () : void => {
		this.options.onDelete(this);
	}

	onValueChange = () : void => {
		this.resolveFormat();
	}

	isActive() : boolean {
		return !this.$el.hasClass('avito--hidden');
	}

	isMultiple() : boolean {
		return this.options.multiple !== false;
	}

	isRequired() : boolean {
		return this.options.required !== false;
	}

	activate(force: boolean = false) : void {
		if (!force && this.isActive()) { return; }

		this.$el.removeClass('avito--hidden');
		this.bind();
		this.bootValue();
		this.bootTemplate();
		this.bootHint();
		this.enableControls(true);
	}

	deactivate(force: boolean = false) : void {
		if (!force && !this.isActive()) { return; }

		this.destroyValue();
		this.destroyTemplate();
		this.unbind();
		this.enableControls(false);
		this.$el.addClass('avito--hidden');
	}

	name() : string {
		const element = this.getElement('name');

		return element.text();
	}

	reset() : void {
		for (const control of this.controls()) {
			if (!control.is('input, select')) { continue; }
			if (control.prop('type') === 'hidden') { continue; }

			if (control.is('select')) {
				control.find('option').prop('selected', false);
			} else if (['radio', 'checkbox'].indexOf(control.prop('type')) !== -1) {
				control.prop('checked', false);
			} else {
				control.val('');
			}
		}
	}

	focus() : void {
		for (const control of this.controls()) {
			if (!control.is('input, select')) { continue; }
			if (control.prop('type') === 'hidden') { continue; }

			control.focus(); // todo focus on select2

			break;
		}
	}

	allowDelete(dir: boolean) : void {
		const element = this.getElement('delete');

		element.prop('disabled', !dir);
		element.toggleClass('avito--hidden', !dir);
	}

	enableControls(dir: boolean) : void {
		for (const control of this.controls()) {
			control.prop('disabled', !dir);
		}
	}

	updateControlName(baseName: string) : void {
		for (const contr of this.controls()) {
			const currentName = contr.attr('name');
			const nameLastMatches = currentName != null ? /\[\w+]$/.exec(currentName) : null;

			if (nameLastMatches == null) { continue; }

			const newName = baseName + nameLastMatches[0];

			contr.attr('name', newName);
		}
	}

	controls() : Array {
		return [
			this.getElement('code'),
			this.getElement('tag'),
			this.getElement('value'),
			this.getElement('format'),
		];
	}

	bootHint() : void {
		this.getElement('hint').each((index, element) => {
			BX.hint_replace(element, element.getAttribute('data-hint'));
		});
	}

	bootValue() : void {
		const element = this.getElement('value');

		if (!element.is('select')) { return; }

		element.select2({
			tags: true,
			selectOnClose: true,
			placeholder: this.getLang('VALUE_PLACEHOLDER'),
			insertTag: (data: Array, tag: string) => {
				data.push(tag); // Insert the tag at the end of the results
			},
		});

		// patch focus for jquery3.6

		element.on('select2:open', () => {
			setTimeout(() => {
				const search = $('.select2-container--open .select2-search__field').last().get(0);
				search?.focus();
			}, 100);
		});
	}

	destroyValue() : void {
		const element = this.getElement('value');

		if (!element.is('select')) { return; }

		element.select2('destroy');
		element.off('select2:open');
	}

	bootTemplate() : void {
		const element = this.getElement('template');

		if (element.length === 0) { return; }

		this._templateEditor = new TemplateEditor(element, {
			sources: () => this.templateSources(),
			value: () => {
				const option = this.getElement('value').find('option').filter(':selected').get(0);

				if (option == null) { return ''; }

				const field = option?.getAttribute('value');

				if (field == null) { return option.textContent; }

				return field.length > 0 ? '{=' + field + '}' : '';
			},
			tagName: this.getElement('name').text(),
			lang: this.options.lang,
			onSave: (value, format) => this.saveTemplate(value, format),
		});
	}

	templateSources() : Array {
		const valueElement = this.getElement('value');
		const groups = valueElement.find('optgroup');
		const result = [];

		for (let groupIndex = 0; groupIndex < groups.length; groupIndex++) {
			const group = $(groups[groupIndex]);
			const options = group.find('option');
			const items = [];

			for (let optionIndex = 0; optionIndex < options.length; optionIndex++) {
				const option = $(options[optionIndex]);

				items.push({
					ID: option.val(),
					VALUE: option.text(),
				});
			}

			result.push({
				TITLE: group.attr('label'),
				ITEMS: items,
			});
		}

		return result;
	}

	saveTemplate(text: string, format: string) : void {
		this.saveTemplateValue(text, format);
		this.setFormat(format);
	}

	saveTemplateValue(text: string, format: string) : void {
		const value = this.getElement('value');
		const option = document.createElement('option');

		option.textContent = text;
		option.setAttribute('data-format', format);
		value.append(option);

		option.selected = true;
	}

	destroyTemplate() : void {
		if (this._templateEditor == null) { return; }

		this._templateEditor.destroy();
		this._templateEditor = null;
	}

	resolveFormat() : void {
		const option = this.getElement('value').find('option').filter(':selected');
		const formatAttribute = option.data('format');
		let format;

		if (formatAttribute != null) {
			format = formatAttribute;
		} else if (option.attr('value') != null) {
			format = 'FIELD';
		} else {
			format = 'TEXT';
		}

		this.setFormat(format);
	}

	setFormat(value: ?string) : void {
		const options = this.getElement('format');

		options.each((index, option) => {
			option.checked = option.value === value;
		});
	}

	clearClone() : void {
		const valueElement = this.getElement('value');
		const valueNext = valueElement.next();

		if (valueElement.hasClass('select2-hidden-accessible')) {
			valueElement
				.removeClass('select2-hidden-accessible')
				.removeAttr('data-select2-id')
				.removeAttr('aria-hidden')
				.removeAttr('tabindex');

			valueElement.find('optgroup')
				.removeAttr('data-select2-id');

			valueElement.find('option')
				.removeAttr('data-select2-id');
		}

		if (valueNext.hasClass('select2')) {
			valueNext.remove();
		}
	}
}