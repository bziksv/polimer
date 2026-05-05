import {Behavior} from "./behavior";

export class Section extends Behavior {

	static defaults = {
		primaryName: null,
		selectName: null,
		iblockId: null,
		property: null,
	}

	values(action: string, data: Object) : Object {
		const form = this.form();

		return {
			iblockSectionId: this.inputValue(form, this.options.primaryName) ?? 0,
			iblockSection: this.selectValue(form, this.options.selectName) ?? this.multipleValue(form, this.options.selectName) ?? [],
			iblockId: this.options.iblockId,
			property: this.options.property,
		};
	}

}