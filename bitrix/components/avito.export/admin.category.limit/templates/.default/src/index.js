// @flow

import {Skeleton} from "../../../../../js/plugin/skeleton";
import {Row} from "./row";

// noinspection JSUnusedGlobalSymbols
export class CategoryLimit extends Skeleton {

	static defaults = {
		component: null,
		signedParameters: null,
		baseName: null,
		nextIndex: 0,
		newRow: null,
		rowElement: '[data-entity="row"]',
		addButtonElement: '[data-entity="addButton"]',
	}

	/** @var Row[] */
	_rows = [];

	constructor(element: HTMLElement, options: Object = {}) {
		super(element, options);

		this.bootRows();
		this.bootAddButton();
	}

	bootRows() : void {
		const elements = this.getElement('row');
		const rows = [];
		let previous = null;

		elements.each((index, element) => {
			const row = new Row(element, {
				onDelete: (row: Row) => this.delete(row),
				lang: this.options.lang,
			});

			row.boot();

			rows.push(row);
			previous = row;
		});

		this._rows = rows;
		this._nextIndex = rows.length;
	}

	delete(row: Row) : void {
		if (this._rows.length > 1) {
			this.removeRow(row);
		} else {
			this.deactivateRow(row);
		}
	}

	removeRow(row: Row) : void {
		const rowElement = row.$el;
		const rowIndex = this._rows.indexOf(row);

		if (rowIndex === -1) { throw new Error('cant delete unknown row'); }

		row.destroy();
		rowElement.remove();

		this._rows.splice(rowIndex, 1);
	}

	activateRow(row: Row) : void {
		row.$el.removeClass('avito--hidden');
		row.enableControls();
	}

	deactivateRow(row: Row) : void {
		row.$el.addClass('avito--hidden');
		row.disableControls();
	}

	bootAddButton() : void {
		const element = this.getElement('addButton');

		element.on('click', () => this.add());
	}

	add() : void {
		const lastRow = this._rows[this._rows.length - 1];

		if (lastRow == null) {
			throw new Error('at least row must be exists');
		}

		if (lastRow.$el.hasClass('avito--hidden')) {
			this.activateRow(lastRow);
		} else {
			this.insertRow(lastRow.clone(), lastRow.$el);
		}
	}

	insertRow(row: Row, anchor: $) : void {
		row.$el.insertAfter(anchor);
		row.updateControlName(this.options.baseName + '[' + this._nextIndex + ']');
		row.clear();
		row.boot();

		this._rows.push(row);
		++this._nextIndex;
	}
}