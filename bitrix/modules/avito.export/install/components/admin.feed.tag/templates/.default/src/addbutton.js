// @flow

import {Skeleton} from "../../../../../js/plugin/skeleton";

export class AddButton extends Skeleton {

	static defaults = {
		rows: () => [],
		onSelect: () => {},
	};

	constructor(element: HTMLElement, options: Object = {}) {
		super(element, options);
		this._menu = null;

		this.bind();
	}

	destroy() : void {
		this.unbind();
		super.destroy();
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
		this.open();
	}

	open() : void {
		const menu = this.menu();
		const items = this.items();

		menu.setItems(items);
		!menu.visible() && menu.Show()
	}

	menu() : BX.CMenu {
		if (this._menu == null) {
			this._menu = new BX.CMenu({
				parent: this.el,
				ATTACH_MODE: 'right',
			});
		}

		return this._menu;
	}

	items() : Array {
		const result = [];

		for (const row of this.options.rows()) {
			result.push({
				TEXT: row.name(),
				ONCLICK: () => this.options.onSelect(row),
			});
		}

		return result;
	}

}