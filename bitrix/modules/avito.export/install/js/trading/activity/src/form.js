import {Skeleton} from "./skeleton";

const Ui = BX.namespace('AvitoExport.Ui');

export class FormActivity extends Skeleton {

	static defaults = Object.assign({}, Skeleton.defaults, {
		title: null,
		dialogTitle: null,
		width: 400,
		height: 250,
	})

	activate() : void {
		const form = this.createForm();

		form.activate()
			.then(() => this.view.reload());
	}

	createForm() {
		return new Ui.ModalForm(this.$el, {
			title: this.options.dialogTitle || this.options.title,
			saveTitle: this.getLang('ACTIVITY_SUBMIT'),
			url: this.options.url,
			width: this.options.width,
			height: this.options.height,
		});
	}

}