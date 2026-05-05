// @flow

import {Skeleton} from "../../../../plugin/skeleton";

// noinspection JSUnresolvedVariable
const $ = window.AvitoJQuery ?? window.$;

// noinspection JSUnusedGlobalSymbols
export class Dependfield extends Skeleton {

	static defaults = {
		depend: null,
		headingElement: '.heading',
		formElement: 'form',
		inputElement: 'input, select, textarea',
		siblingElement: 'tr',
		descriptionElement: '.avito-group-description, .avito-field-intro, .avito-field-additional',
		tabContentElement: '.adm-detail-content',
		lang: {}
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
		this.handleDependChange(true);
	}

	unbind() : void {
		this.handleDependChange(false);
	}

	handleDependChange(dir: boolean) : void {
		const fields = this.getDependElements();

		fields[dir ? 'on' : 'off']('change', this.onDependChange);
	}

	onDependChange = () : void => {
		this.updateField();
	}

	updateField() : void {
		const isMatch = this.resolveDependRules();

		if (this.alreadyView(isMatch)) { return; }

		this.toggleView(isMatch);
		this.toggleHeaderView(isMatch);
		this.toggleTab(isMatch);
		this.fireChange();
	}

	resolveDependRules() : boolean {
		const rules = this.options.depend;
		let rule,
			fields = this.getDependFields(),
			fieldKey,
			field,
			fieldValue,
			isDependAny = this.isDependAny(),
			result = !isDependAny;

		for (fieldKey in fields) {
			if (!fields.hasOwnProperty(fieldKey)) { continue; }

			field = fields[fieldKey];
			fieldValue = this.getFieldValue(field, fieldKey);
			rule = rules[fieldKey];

			if (this.isMatchRule(rule, fieldValue) === isDependAny) {
				result = isDependAny;
				break;
			}
		}
		return result;
	}

	getFieldValue(field, name) {
		let result;

		if (this.isHiddenField(field)) { return null; }

		switch (this.getFieldType(field, name))
		{
			case 'complex':
				result = this.getComplexValue(field, name);
				break;

			case 'hidden':
				if (field.length > 1) { // is checkbox sibling
					result = this.getFieldValue(field.slice(1));
				} else {
					result = field.val();
				}
				break;

			case 'checkbox':
				result = [];
				field.each(function() { if (this.checked) { result.push(this.value); } });
				break;

			case 'radio':
				field.each(function() { if (this.checked) { result = this.value; } });
				break;

			default:
				result = field.val();
				break;
		}

		return result;
	}

	isHiddenField(field) : boolean {
		const row = this.getElement('sibling', field, 'closest');
		return row.hasClass('is--hidden');
	}

	getFieldType(field, name) : string {
		const selfName = field.data('name');
		let result = (field.prop('tagName') || '').toLowerCase();

		if (result === 'input') {
			result = (field.prop('type') || '').toLowerCase();
		}

		if (selfName != null && selfName !== name && selfName.indexOf('[' + name + ']') === 0) {
			result = 'complex';
		}

		return result;
	}

	getComplexValue(field, baseName) {
		let childIndex,
			child,
			childFullName,
			childName,
			nameStart = '[' + baseName + ']',
			result = {};

		for (childIndex = 0; childIndex < field.length; childIndex++) {
			child = field.eq(childIndex);
			childFullName = child.data('name');

			if (childFullName == null || childFullName.indexOf(nameStart) !== 0) { continue; }

			childName = childFullName.substring(nameStart.length);
			result[childName] = this.getFieldValue(child, childFullName);
		}

		return result;
	}

	isMatchRule(rule, value) : boolean {
		let isEmpty,
			result = true;

		switch (rule['RULE']) {
			case 'EMPTY':
				isEmpty = this.testIsEmpty(value);
				result = (isEmpty === rule['VALUE']);
				break;

			case 'ANY':
				result = this.applyRuleAny(rule['VALUE'], value);
				break;

			case 'EXCLUDE':
				result = !this.applyRuleAny(rule['VALUE'], value);
				break;
		}

		return result;
	}

	testIsEmpty(value) : boolean {
		let result = true;

		if (Array.isArray(value)) {
			for (const one of value) {
				if (!this.testIsEmpty(one)) {
					result = false;
					break;
				}
			}
		} else if ($.isPlainObject(value)) {
			for (const key in value) {
				if (!value.hasOwnProperty(key)) { continue; }

				if (!this.testIsEmpty(value[key])) {
					result = false;
					break;
				}
			}
		} else {
			result = (!value || value === '0');
		}

		return result;
	}

	applyRuleAny(ruleValue, formValue) : boolean {
		const isRuleMultiple = Array.isArray(ruleValue);
		const isFormMultiple = Array.isArray(formValue);
		let formIndex,
			formItem,
			result = false;

		if (isFormMultiple && isRuleMultiple) {
			for (formIndex = formValue.length - 1; formIndex >= 0; --formIndex) {
				formItem = formValue[formIndex];

				if (this.testInArray(formItem, ruleValue)) {
					result = true;
					break;
				}
			}
		} else if (isFormMultiple) {
			result = this.testInArray(ruleValue, formValue);
		} else if (isRuleMultiple) {
			result = this.testInArray(formValue, ruleValue);
		} else {
			// noinspection EqualityComparisonWithCoercionJS
			result = (formValue == ruleValue);
		}

		return result;
	}

	testInArray(needle, haystack) : boolean {
		let result = false;

		for (let i = haystack.length - 1; i >= 0; i--) {
			// noinspection EqualityComparisonWithCoercionJS
			if (haystack[i] == needle) {
				result = true;
				break;
			}
		}

		return result;
	}

	isDependAny() : boolean {
		return this.options.depend['LOGIC'] === 'OR';
	}

	alreadyView(isMatch: boolean) : boolean {
		return this.$el.hasClass('is--hidden') === !isMatch;
	}

	toggleView(isMatch: boolean) : void {
		this.$el.toggleClass('is--hidden', !isMatch);
		this.getElement('description', this.$el, 'next').toggleClass('is--hidden', !isMatch);
	}

	toggleHeaderView(isMatch: boolean) : void {
		const heading = this.getHeading();

		if (isMatch) {
			heading.removeClass('is--hidden');
			return;
		}

		if (heading.hasClass('is--hidden')) { return; }

		const siblingRows = this.getSiblingsUnderHeading(heading).not(this.getElementSelector('description'));
		const hasVisibleSiblings = (siblingRows.not('.is--hidden').length > 0);

		heading.toggleClass('is--hidden', !hasVisibleSiblings);
		this.getElement('description', heading, 'next').toggleClass('is--hidden', !hasVisibleSiblings);
	}

	getHeading() : JQuery {
		const heading = this.getElement('heading', this.$el, 'prevAll');

		return heading.first();
	}

	getSiblingsUnderHeading(heading : JQuery) : JQuery {
		const headerSelector = this.getElementSelector('heading');
		const fieldSelector = this.getElementSelector('sibling');
		let sibling = heading,
			result = $();

		do {
			sibling = sibling.next();

			if (sibling.is(headerSelector)) { break; }

			if (sibling.is(fieldSelector)) {
				result = result.add(sibling);
			}
		} while (sibling.length !== 0);

		return result;
	}

	toggleTab(isMatch: boolean) : void {
		const tabContent = this.getElement('tabContent', this.$el, 'closest');
		const tabButton = this.tabButton(tabContent);

		if (tabButton == null) { return; }

		if (isMatch) {
			tabButton.removeClass('is--hidden');
			return;
		}

		if (tabButton.hasClass('is--hidden')) { return; }

		const siblings = this.getElement('sibling', this.$el, 'siblings');
		const hasVisibleSiblings = (siblings.not('.is--hidden').length > 0);

		tabButton.toggleClass('is--hidden', !hasVisibleSiblings);
	}

	tabButton(tabContent: JQuery) : ?JQuery {
		const id = tabContent.attr('id');

		if (id == null) { return null; }

		return $(`#tab_cont_${id}`);
	}

	fireChange() : void {
		const input = this.getElement('input');
		input.trigger('change');
	}

	getDependElements() : JQuery {
		const fields = this.getDependFields();

		let fieldKey,
			field,
			result = $();

		for (fieldKey in fields) {
			if (!fields.hasOwnProperty(fieldKey)) { continue; }

			field = fields[fieldKey];
			result = result.add(field);
		}

		return result;
	}

	getDependFields() : Array {
		let keys = Object.keys(this.options.depend),
			keyIndex,
			key,
			field,
			fields = {};

		for (keyIndex = 0; keyIndex < keys.length; keyIndex++) {
			key = keys[keyIndex];

			if (key === 'LOGIC') { continue; }

			field = this.getField(key);

			if (field) {
				fields[key] = field;
			}
		}

		return fields;
	}

	getField(selector: string) : JQuery {
		let result;

		if (selector.substring(0, 1) === '#') {
			result = $(selector);
		} else {
			result = this.getFormField(selector);
		}

		return result;
	}

	getFormField(name: string) : JQuery {
		const form = this.getElement('form', this.$el, 'closest');
		const isForm = form.is('form');
		const nameMultiple = name + '[]';

		let variants,
			variantIndex,
			variant,
			result;

		if (isForm && form[0][name] != null) {
			result = $(form[0][name]);
		} else if (isForm && form[0][nameMultiple] != null) {
			result = $(form[0][nameMultiple]);
		} else {
			variants = [
				'[data-name="' + name + '"]',
				'[data-name^="[' + name + ']"]',
			];

			for (variantIndex = 0; variantIndex < variants.length; variantIndex++) {
				variant = variants[variantIndex];
				result = form.find(variant);

				if (result.length > 0) { break; }
			}
		}

		return result;
	}
}