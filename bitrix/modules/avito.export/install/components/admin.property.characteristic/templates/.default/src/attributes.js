import {Transport} from "./transport";

export class Attributes {

	static defaults = {
		transport: null,
		state: null,
		lang: {
			ERROR_ATTRIBUTE: 'No next attributes'
		},
		onChoose: () => {},
	};

	constructor(element: HTMLElement, options: Object = {}) {
		this.el = element;
		this.options = Object.assign({}, this.constructor.defaults, options);
		this._state = this.options.state;
		this._menu = null;
	}

	suggest(values: Object) : void {
		this._state?.loading()
		this.disableButton();

		return this.query(values)
			.then((attributes) => this.render(attributes))
			.then(() => {
				this.enableButton();
				this._state?.waiting()
			})
			.catch((error) => {
				this.enableButton();
				this._state?.error(error)
			});
	}

	query(values: Object) : Promise {
		return this.transport().fetch('attributes', {
			values: values,
		});
	}

	transport() : Transport {
		const option = this.options.transport;

		if (!(option instanceof Transport)) {
			throw new Error('transport must be instance of Transport');
		}

		return option;
	}

	render(attributes: Array) : void {
		if (attributes.length === 0) { throw new Error(this.options.lang['ERROR_ATTRIBUTE']); }

		const menu = this.menu();
		const items = this.menuItems(attributes);

		menu.setItems(items);
		menu.Show();
	}

	select(attribute: string) : void {
		this.options.onChoose(attribute);
	}

	menu() : BX.CMenu {
		if (this._menu == null) {
			this._menu = new BX.CMenu({
				parent: this.el,
			});
		}

		return this._menu;
	}

	menuItems(attributes: Array) : Array {
		const result = [];

		for (const attribute of attributes) {
			result.push({
				TEXT: attribute,
				ONCLICK: () => { this.select(attribute); }
			});
		}

		return result;
	}

	disableButton() : void {
		this.el.disabled = true;
	}

	enableButton() : void {
		this.el.disabled = false;
	}
}