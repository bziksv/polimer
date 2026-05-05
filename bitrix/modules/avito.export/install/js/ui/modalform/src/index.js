// noinspection JSUnresolvedReference

import {Skeleton} from "../../../plugin/skeleton";

// noinspection JSUnusedGlobalSymbols
export class ModalForm extends Skeleton {

	static defaults = {
		url: null,
		data: null,
		title: null,
		saveTitle: null,
		width: 400,
		height: 250,
		buttons: null,
	}

	_modal;
	_handled = {};
	_activateDeferred;

	handleFormSubmit(dir: boolean) : void {
		this.wrapHandle('formSubmit', dir, () => {
			const contentElement = this._modal.GetContent();

			$(contentElement)[dir ? 'on' : 'off']('submit', this.onFormSubmit);
		});
	}

	handleFormSave(dir: boolean) : void {
		this.wrapHandle('formSave', dir, () => {
			BX[dir ? 'addCustomEvent' : 'removeCustomEvent']('avitoExportFormSave', this.onFormSave);
		});
	}

	handleError(dir: boolean) : void {
		this.wrapHandle('error', dir, () => {
			BX[dir ? 'addCustomEvent' : 'removeCustomEvent'](this._modal, 'onWindowError', this.onError);
		});
	}

	handleClose(dir: boolean) : void {
		this.wrapHandle('close', dir, () => {
			BX[dir ? 'addCustomEvent' : 'removeCustomEvent'](this._modal, 'onWindowClose', this.onClose);
		});
	}

	wrapHandle(type: string, dir: boolean, callback: () => {}) : void {
		if (!!this._handled[type] === dir) { return; }

		if (this._modal != null) {
			callback(dir);
			this._handled[type] = dir;
		} else if (!dir) {
			this._handled[type] = false;
		}
	}

	onFormSubmit = () : void => {
		const node = this.getModal().GetForm();
		const form = $(node);

		this.prepareAjaxForm(form);
	}

	onFormSave = (data) : void => {
		this.activateEnd(data);
		this._modal.Close();
		BX.closeWait();
	}

	onError = () : void => {
		this._modal?.closeWait();
	}

	onClose = () : void => {
		this.handleFormSubmit(false);
		this.handleFormSave(false);
		this.handleError(false);
		this.handleClose(false);

		this.activateStop();
	}

	activate() : void {
		this._modal ??= this.createModal();

		this._modal.Show();

		this.handleFormSubmit(true);
		this.handleFormSave(true);
		this.handleError(true);
		this.handleClose(true);

		if (this._activateDeferred != null) {
			this._activateDeferred.reject();
		}

		return (this._activateDeferred = new $.Deferred());
	}

	activateStop() : void {
		this._activateDeferred?.reject();
		this._activateDeferred = null;
	}

	activateEnd(data) : void {
		this._activateDeferred?.resolve(data);
		this._activateDeferred = null;
	}

	getModal(): Object {
		if (this._modal == null) {
			this._modal = this.createModal();
		}

		return this._modal;
	}

	createModal() : BX.CAdminDialog {
		const options = this.getModalOptions();

		return new BX.CAdminDialog(options);
	}

	getModalOptions() : Object {
		return {
			title: this.options.title,
			width: this.options.width,
			height: this.options.height,
			content_url: this.makeModalUrl(),
			content_post: this.options.data,
			draggable: true,
			resizable: true,
			buttons: this.getModalButtons(),
		};
	}

	getModalButtons() : Array {
		return this.options.buttons != null ? this.options.buttons : this.getDefaultButtons();
	}

	getDefaultButtons() : Array {
		let saveBtn = BX.CAdminDialog.btnSave;

		if (this.options.saveTitle) {
			saveBtn = Object.assign({}, saveBtn, {
				title: this.options.saveTitle,
			});
		}

		return [
			saveBtn,
			BX.CAdminDialog.btnCancel,
		];
	}

	makeModalUrl() : string {
		const url = this.options.url || '';

		return url
			+ (url.indexOf('?') === -1 ? '?' : '&')
			+ 'view=dialog';
	}

	prepareAjaxForm(form: $) : void {
		if (form.find('input[name="ajaxForm"]').length > 0) { return; }

		form.append('<input type="hidden" name="ajaxForm" value="Y" />');
	}

}