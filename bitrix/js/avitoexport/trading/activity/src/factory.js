import type {Skeleton} from "./skeleton";
import {FormActivity} from "./form";
import {CommandActivity} from "./command";
import type {View} from "./view/view";

export class Factory {

	static make(behavior: string, view: View, options: Object = {}) : Skeleton {
		if (behavior === 'form') {
			return new FormActivity(view, options);
		} else if (behavior === 'command') {
			return new CommandActivity(view, options);
		}

		throw new Error(`unknown activity ${behavior}`);
	}

}