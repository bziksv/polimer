import {Behavior} from "./behavior";

export class MassiveEdit extends Behavior {

	static defaults = {
		name: null,
	}

	values(action: string, data: Object) : Object {
		const form = this.form();

		return {
			value: this.inputValue(form, this.options.name),
		};
	}

}