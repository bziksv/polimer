import {Tab} from "./tab";
import {View} from "./view";

export class CrmTab extends Tab {

	static defaults = Object.assign({}, View.defaults, {
		editor: null,
	});

	reload() : void {
		this.editor().reload();
	}

	tab() : $ {
		return $(this.editor()._container);
	}

	editor() : BX.UI.EntityEditor {
		return this.options.editor;
	}

}