import {Behavior} from "./behavior";

export class ProductElement extends Behavior {

	static defaults = {
		skuName: null,
		skuIblockId: null,
		property: null,
	}

	values(action: string, data: Object) : Object {
		const form = this.form();

		return {
			sku: this.inputValue(form, this.options.skuName),
			skuIblockId: this.options.skuIblockId,
			property: this.options.property,
		};
	}

}