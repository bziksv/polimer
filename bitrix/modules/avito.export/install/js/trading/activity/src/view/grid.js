// noinspection JSUnresolvedReference

import {View} from "./view";

export class Grid extends View {

	static defaults = Object.assign({}, View.defaults, {
		gridId: null,
	});

	constructor(options: Object = {}) {
		super(null, options);
	}

	showLoading() : void {
		this.grid().tableFade();
	}

	hideLoading() : void {
		this.grid().tableUnfade();
	}

	reload() : void {
		this.grid().reloadTable();
	}

	showError(error: Error|string) : void {
		const message = error instanceof Error ? error.message : error;
		const grid = this.grid();
		const parts = (message || '').split(/<br[ \/]*>/i);

		grid.arParams.MESSAGES = parts.map((part) => {
			return { TYPE: 'ERROR', TEXT: part };
		});

		BX.onCustomEvent(window, 'BX.Main.grid:paramsUpdated', []);
	}

	grid() {
		return BX.Main.gridManager.getById(this.options.gridId).instance;
	}

}