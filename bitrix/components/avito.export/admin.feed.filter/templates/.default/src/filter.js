// @flow
import {Skeleton} from "../../../../../js/plugin/skeleton";
import {JunctionCollection} from "./junctioncollection";
import {Transport} from "./transport";
import {Row} from "./row";
import {Junction} from "./junction";
import {compileTemplate} from "../../../../../js/plugin/utils";

export class Filter extends Skeleton {

    static defaults = {
        component: null,
        signedParameters: null,
        baseName: null,
        nextIndex: 0,
        fields: [],
        rowElement: '[data-entity="row"]',
        junctionElement: '[data-entity="junction"]',
        junctionTemplate: '<button class="avito-filter-junction" type="button" data-entity="junction">#TEXT#</button>',
        addButtonElement: '[data-entity="addButton"]',
        deleteFilterElement: '[data-entity="deleteFilter"]'
    }

    /** @var Row[] */
    _rows = [];
    _junctionCollection: JunctionCollection;

    constructor(element: HTMLElement, options: Object = {}) {
        super(element, options);
        this._transport = new Transport(this.options.component, this.options.signedParameters);

        this.initRows();
    }

	initRows() : void {
        const elements = this.getElement('row');
        const junctions = this.getElement('junction');
        const junctionCollection = new JunctionCollection();
        const rows = [];
        let previous = null;

        elements.each((index, element) => {
            const row = new Row(element, {
                transport: this._transport,
                onDelete: (row: Row) => this.delete(row),
                fields: this.options.fields,
                lang: this.options.lang,
            });

            if (previous !== null) {
                const junction = new Junction(junctions.eq(index - 1), {
                    lang: this.options.lang,
                });

                junction.setPrevious(previous);
                junction.setNext(row);
                junctionCollection.add(junction);
            }

            rows.push(row);
            previous = row;
        });

        this._rows = rows;
        this._junctionCollection = junctionCollection;
        this._nextIndex = rows.length;
    }

	boot() : void {
		this.bootRows();
		this.bootAddButton();
		this.bootDeleteButton();
	}

	bootRows() : void {
		for (const row of this._rows) {
			row.boot();
		}
	}

    bootAddButton() : void {
        const element = this.getElement('addButton');

        element.on('click', () => this.add());
    }

    bootDeleteButton() : void {
        const element = this.getElement('deleteFilter');

        element.on('click', () => this.options.onDelete(this));
    }

    add() : void {
        const lastRow = this._rows[this._rows.length - 1];

        if (lastRow == null) {
            throw new Error('at least one filter row must be exists');
        }

        const newRow = lastRow.clone();

        this.insertRow(newRow, lastRow.$el);
        this.insertJunction(lastRow, newRow);
        this.reflowDelete();
    }

    insertRow(row: Row, anchor: $) : void {
        row.$el.insertAfter(anchor);
		row.updateControlName(this.options.baseName + '[' + this._nextIndex + ']');
        row.reset();
        row.boot();

        this._rows.push(row);
        ++this._nextIndex;
    }

    insertJunction(previous: Row, next: Row) : void {
        const template = this.getTemplate('junction');
        const html = compileTemplate(template, {
            'TEXT': this.getLang('JUNCTION_AND'),
        });
        const junction = new Junction(html, {
            lang: this.options.lang,
        });

        junction.$el.insertAfter(previous.$el);
        junction.setPrevious(previous);
        junction.setNext(next);

        this._junctionCollection.add(junction);

        if (this._junctionCollection.previous(junction)?.isActive()) {
            junction.activate();
        }
    }

    delete(row: Row) : void {
        if (this._rows.length > 1) {
            this.removeJunction(row);
            this.removeRow(row);
            this.reflowDelete();
        } else {
            row.reset();
        }
    }

    removeJunction(row: Row) : void {
        const rowIndex = this._rows.indexOf(row);
        let junction;

        if (rowIndex === 0) {
            junction = this._junctionCollection.at(0);

            this.syncJunctionFirst(junction);
        } else {
            junction = this._junctionCollection.at(rowIndex - 1);

            this.syncJunctionBefore(junction, this._rows[rowIndex - 1]);
        }

        junction.destroy();
        junction.$el.remove();

        this._junctionCollection.remove(junction);
    }

    syncJunctionBefore(junction: Junction, previousRow: Row) : void {
        const next = this._junctionCollection.next(junction);

        if (next == null) {
            junction.deactivate();
            return;
        }

        if (!junction.isActive() && next.isActive()) {
            next.deactivate();
        }

        next.setPrevious(previousRow);
    }

    syncJunctionFirst(junction: Junction) : void {
        junction.deactivate();
    }

    removeRow(row: Row) : void {
        const rowElement = row.$el;
        const rowIndex = this._rows.indexOf(row);

        if (rowIndex === -1) { throw new Error('cant delete unknown row'); }

        row.destroy();
        rowElement.remove();

        this._rows.splice(rowIndex, 1);
    }

    reflowDelete() : void {
        const hasFew = this._rows.length > 1;

        for (const row of this._rows) {
            row.allowDelete(hasFew);
        }
    }

    allowDelete(dir: boolean) : void {
        const element = this.getElement('deleteFilter');

        element.prop('disabled', !dir);
        element.toggleClass('avito--hidden', !dir);
    }

	reset() : void {
		const rowsCopy = this._rows.slice();
		let index = 0;

		for (const row of rowsCopy) {
			if (index === 0) {
				row.reset();
			} else {
				this.delete(row);
			}

			++index;
		}
	}

    clone() : Filter {
        const element = this.$el.clone(false, false);
        const result = new Filter(element, this.options);

	    result.clearClone();

        return result;
    }

	clearClone() : void {
		for (const row of this._rows) {
			row.clearClone();
		}
	}

	updateControlName(baseName: string) : void {
		let index = 0;

		this.options.baseName = baseName;

        for (const row of this._rows) {
            row.updateControlName(`${baseName}[${index}]`);
			++index;
        }

		this._nextIndex = this._rows.length;
    }
}