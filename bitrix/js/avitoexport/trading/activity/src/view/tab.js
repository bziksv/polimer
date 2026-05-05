// noinspection JSUnresolvedReference

import {View} from "./view";
import type {Skeleton} from "../skeleton";

// noinspection JSUnresolvedVariable
const $ = window.AvitoJQuery ?? window.$;

export class Tab extends View {

	static defaults = Object.assign({}, View.defaults, {
		tabElement: null,
	});

	boot(activity: Skeleton) : void {
		super.boot(activity);
		this.bind();
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
		this.activity.activate();
	}

	showLoading() : void {
		const tab = this.tab();

		BX.showWait(tab[0]);
	}

	hideLoading() : void {
		const tab = this.tab();

		BX.closeWait(tab[0]);
	}

	reload() : void {
		const tab = this.tab();

		if (tab.length === 0) { return; }

		BX.onCustomEvent(tab[0], 'avitoExportActivityEnd');
	}

	showError(error: Error|string) : void {
		const message = error instanceof Error ? error.message : error;
		const SaleAdmin = BX.namespace('Sale.Admin');
		const Dialogs = BX.namespace('UI.Dialogs');

		if (SaleAdmin.OrderEditPage != null) {
			SaleAdmin.OrderEditPage.showDialog(message);
		} else if (Dialogs.MessageBox != null) {
			Dialogs.MessageBox.show({
				title: this.getLang('ACTIVITY_ERROR'),
				message: message,
				buttons: BX.UI.Dialogs.MessageBoxButtons.OK,
			});
		} else {
			alert(message);
		}
	}

	tab() : $ {
		const tabSelector = this.getElementSelector('tab');

		return tabSelector ? this.$el.closest(tabSelector) : $();
	}
}