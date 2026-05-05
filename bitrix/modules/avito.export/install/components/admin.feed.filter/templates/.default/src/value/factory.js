import {ValueSelect} from "./select";
import {ValueDateTime} from "./datetime";
import {ValueText} from "./text";
import {ValueAutocomplete} from "./autocomplete";

export class ValueFactory {
	static make(element: HTMLElement, type: string) {
		if (type === 'autocomplete') {
			return new ValueAutocomplete(element);
		}

		if (type === 'select') {
			return new ValueSelect(element);
		}

		if (type === 'datetime') {
			return new ValueDateTime(element);
		}

		return new ValueText(element);
	}
}