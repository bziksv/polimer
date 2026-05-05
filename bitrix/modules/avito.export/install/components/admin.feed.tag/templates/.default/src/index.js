// @flow

import {Skeleton} from "../../../../../js/plugin/skeleton";
import {AddButton} from "./addbutton";
import {Row} from "./row";

// noinspection JSUnusedGlobalSymbols
export class Tags extends Skeleton {

	static defaults = {
		baseName: null,
		nextIndex: 0,
		rowElement: '[data-entity="row"]',
		addButtonElement: '[data-entity="addButton"]',
	}

	constructor(element: HTMLElement, options: Object = {}) {
		super(element, options);
		this._nextIndex = this.options.nextIndex;

		this.bootRows();
		this.bootAddButton();
	}

	bootRows() {
		const elements = this.getElement('row');
		const rows = [];

		elements.each((index, element) => {
			const row = new Row(element, {
				onDelete: (row: Row) => this.delete(row),
				lang: this.options.lang,
			});

			if (row.isActive()) {
				row.activate(true);
			}

			rows.push(row);
		});

		this._rows = rows;
	}

	bootAddButton() {
		const button = this.getElement('addButton');

		this.addButton = new AddButton(button, {
			rows: this.rowsForAdd,
			onSelect: (row: Row) => this.add(row),
		});
	}

	rowsForAdd = () : Row[] => {
		const result = [];
		const found = [];

		for (const row of this.rows()) {
			const name = row.name();

			if (row.isActive() && !row.isMultiple()) { continue; }
			if (found.indexOf(name) !== -1) { continue; }

			result.push(row);
			found.push(name);
		}

		return result;
	}

	add(row: Row) : void {
		if (!row.isActive()) {
			this.activateRow(row);
		} else if (!row.isMultiple()) {
			throw new Error('cant add few rows for non-multiple row');
		} else {
			this.insertRow(row);
			this.reflowDelete(row.name());
		}
	}

	activateRow(row: Row) : void {
		const index = this._nextIndex++;
		const newName = this.options.baseName + `[${index}]`;

		row.updateControlName(newName);
		row.reset();
		row.activate();
		row.focus()
	}

	insertRow(row: Row) : void {
		const typeRows = this.typeRows(row.name());
		const sourceRow = typeRows.length > 0 ? typeRows[typeRows.length - 1] : row;
		const newRow = sourceRow.clone();
		const index = this._nextIndex++;
		const newName = this.options.baseName + `[${index}]`;

		newRow.updateControlName(newName);
		newRow.$el.insertAfter(sourceRow.$el);
		newRow.reset();
		newRow.activate(true);
		newRow.focus();

		this._rows.push(newRow);
	}

	delete(row: Row) : void {
		const name = row.name();
		const typeRows = this.typeRows(name, true);

		if (typeRows.length > 1) {
			this.removeRow(row);
			this.reflowDelete(name);
		} else if (row.isRequired()) {
			throw new Error('cant remove last required row');
		} else {
			row.deactivate();
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

	reflowDelete(name: string) : void {
		const rows = this.typeRows(name, true);
		const hasFew = rows.length > 1;

		for (const row of rows) {
			row.allowDelete(hasFew || !row.isRequired());
		}
	}

	rows(onlyActive: boolean = false) : Row[] {
		if (!onlyActive) { return this._rows; }

		const result = [];

		for (const row of this._rows) {
			if (!row.isActive()) { continue; }

			result.push(row);
		}

		return result;
	}

	typeRows(name: string, onlyActive: boolean = false) : Row[] {
		const result = [];

		for (const row of this._rows) {
			if (onlyActive && !row.isActive()) { continue; }

			if (row.name() === name) {
				result.push(row);
			}
		}

		return result;
	}
}