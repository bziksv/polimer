import {htmlToElement} from "../utils";

export class Activity {

	static make(activity: Object, editor) : HTMLElement {
		const button = htmlToElement(`<button 
			class="ui-btn ui-btn-sm ${activity.REQUIRED ? 'ui-btn-primary' : ''}" 
			type="button"
		>${activity.TITLE}</button>`);
		const view = new BX.AvitoExport.Trading.Activity.View.CrmTab(button, {
			editor: editor,
		});

		BX.AvitoExport.Trading.Activity.Factory.make(activity.BEHAVIOR, view, Object.assign({}, activity.UI_OPTIONS ?? {}, {
			'title': activity.TITLE,
			'confirm': activity.CONFIRM || null, // null converted to string in component parameters
			'url': activity.URL,
		}));

		return button;
	}

}