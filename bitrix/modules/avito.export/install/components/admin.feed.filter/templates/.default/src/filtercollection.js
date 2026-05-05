// @flow
import {Skeleton} from "../../../../../js/plugin/skeleton";
import {Filter} from "./filter";

export class FilterCollection extends Skeleton {

    static defaults = {
        filterElement: '[data-entity="filter"]',
        addFilterElement: '[data-entity="addFilter"]'
    }

    _filters = [];

    constructor(element: HTMLElement, options: Object = {}) {
        super(element, options);

        this.bootFilters();
        this.bootAddButton();
    }

    bootFilters() : void {
        const elements = this.getElement('filter');
        const filters = [];

        elements.each((index, element) => {
            const filter = new Filter(element, {
                component: this.options.component,
                signedParameters: this.options.signedParameters,
                fields: this.options.fields,
                baseName: `${this.options.baseName}[${index}]`,
                lang: this.options.lang,
                onDelete: (filter: Filter) => this.delete(filter)
            });

	        filter.boot();

            filters.push(filter);
        });

        this._filters = filters;
	    this._nextIndex = filters.length;
    }

    bootAddButton() : void {
        const element = this.getElement('addFilter');

        element.on('click', () => this.add());
    }

    add() : void {
        const lastFilter = this._filters[this._filters.length - 1];

        if (lastFilter == null) {
            throw new Error('at least one filter must be exists');
        }

        const newFilter = lastFilter.clone();

        this.insertFilter(newFilter, lastFilter.$el);
        this.reflowDelete();
    }

    delete(filter: Filter) : void {
        if (this._filters.length <= 1) { return; }

		this.removeFilter(filter);
        this.reflowDelete();
    }

    insertFilter(filter: Filter, anchor: $) : void {
        filter.$el.insertAfter(anchor);
	    filter.updateControlName(this.options.baseName + '[' + this._nextIndex + ']');
	    filter.reset();
	    filter.boot();

        this._filters.push(filter);

        ++this._nextIndex;
    }

    removeFilter(filter: Filter) : void {
        const filterElement = filter.$el;
        const filterIndex = this._filters.indexOf(filter);

        if (filterIndex === -1) { throw new Error('cant delete unknown filter'); }

        filter.destroy();
        filterElement.remove();

        this._filters.splice(filterIndex, 1);
    }

    reflowDelete() : void {
        const hasFew = this._filters.length > 1;

        for (const filter of this._filters) {
            filter.allowDelete(hasFew);
        }
    }
}