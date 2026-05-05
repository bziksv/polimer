import {Behavior} from "./behavior";

export class SectionSelf extends Behavior {

	static defaults = {
		name: null,
		parentName: null,
		iblockId: null,
	}

	values(action: string, data: Object) : Object {
		const form = this.form();

		return {
			value: this.inputValue(form, this.options.name),
			parentId: this.inputValue(form, this.options.parentName),
			iblockId: this.options.iblockId,
			name: this.options.name,
		};
	}

}