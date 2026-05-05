// @flow

import {Skeleton} from "../../../../../js/plugin/skeleton";
import {JunctionCollection} from "./junctioncollection";
import {Row} from "./row";

export class Junction extends Skeleton {

	static GLUE_AND = 'AND';
	static GLUE_OR = 'OR';

	static defaults = {};

	collection: JunctionCollection;
	previousRow: Row;
	nextRow: Row;

	constructor(...args) {
		super(...args);
		this.bind();
	}

	destroy() {
		this.unbind();
	}

	setCollection(collection: JunctionCollection) : void {
		this.collection = collection;
	}

	setPrevious(row: Row) : void {
		this.previousRow = row;
	}

	setNext(row: Row) : void {
		this.nextRow = row;
	}

	bind() : void {
		this.handleClick(true);
	}

	unbind() : void {
		this.handleClick(false);
	}

	handleClick(dir: boolean) : void {
		this.$el[dir ? 'on' : 'off']('click', this.onClick);
	}

	onClick = () : void => {
		this.toggle();
	}

	isActive() {
		return this.$el.hasClass('is--active');
	}

	toggle() : void {
		if (!this.isActive()) {
			this.activate();
		} else {
			this.deactivate();
		}
	}

	activate() : void {
		if (this.isActive()) { return; }

		this.previousRow.setLevel(1);
		this.nextRow.setLevel(1);
		this.nextRow.setGlue(Junction.GLUE_OR);
		this.renderState(1);
	}

	deactivate() : void {
		if (!this.isActive()) { return; }

		const previousJunction = this.collection.previous(this);
		const nextJunction = this.collection.next(this);
		const previousActive = previousJunction?.isActive();
		const nextActive = nextJunction?.isActive();

		!previousActive && this.previousRow.setLevel(0);
		!nextActive && this.nextRow.setLevel(0);

		this.nextRow.setGlue(Junction.GLUE_AND);
		this.renderState(0);
	}

	renderState(state: number) : void {
		const isActive = !!state;

		this.$el.text(isActive ? this.getLang('JUNCTION_OR') : this.getLang('JUNCTION_AND'));
		this.$el.toggleClass('is--active', isActive);
	}
}