// @flow

export class MassiveEdit {

	static defaults = {
		propertyId: null,
		iblockId: null,
		language: null,
		prefixSelected: null,
	};

	constructor(tableId: string, options: Object = {}) {
		this.tableId = tableId;
		this.options = Object.assign({}, this.constructor.defaults, options);
	}

	handleActionDone(dir: boolean) : void {
		BX[dir ? 'addCustomEvent' : 'removeCustomEvent']('avitoExportMassiveEditDone', this.onActionDone);
	}

	handleModalClose(dir: boolean) : void {
		BX[dir ? 'addCustomEvent' : 'removeCustomEvent'](this.modal(), 'onWindowClose', this.onWindowClose);
	}

	onActionDone = () => {
		BX.closeWait(); // opened inside submit, but not closed
		this.modal().Close();
		this.grid().reload();

		this.releaseModal();

		this.handleActionDone(false);
		this.handleModalClose(false);
	}

	onWindowClose = () => {
		this.releaseModal();

		this.handleActionDone(false);
		this.handleModalClose(false);
	}

	open() {
		const modal = this.modal();

		modal.Show();

		this.handleModalClose(true);
		this.handleActionDone(true);
	}

	modal() : BX.CAdminDialog {
		if (this._modal == null) {
			this._modal = this.createModal();
		}

		return this._modal;
	}

	releaseModal() : void {
		this._modal = null;
	}

	createModal() : BX.CAdminDialog {
		return new BX.CAdminDialog({
			content_url: this.url(),
			content_post: {
				selected: this.selectedRows(),
				property: this.options.propertyId,
				iblockId: this.options.iblockId,
			},
			width: 800,
			height: 500,
			resizable: true,
			buttons: [
				BX.CAdminDialog.btnSave,
				BX.CAdminDialog.btnCancel,
			],
		});
	}

	isForAllChecked() : boolean {
		return this.grid()?.getActionsPanel()?.getForAllCheckbox()?.checked;
	}

	selectedRows() : Array {
		const selected = this.grid().getRows().getSelectedIds();
		const prefix = this.options.prefixSelected;

		if (this.isForAllChecked()) {
			throw new Error(BX.message('AVITO_EXPORT_UI_ADMIN_MASSIVE_EDIT_FOR_ALL_NOT_SUPPORTED'));
		}

		if (prefix == null) { return selected; }

		for (let key in selected) {
			if (!selected.hasOwnProperty(key)) { continue; }

			selected[key] = prefix + selected[key];
		}

		return selected;
	}

	url() : string {
		return `/bitrix/tools/avito.export/massiveedit/modal.php?bxpublic=Y&lang=${this.options.language}`;
	}

	grid() : BX.Main.grid {
		return BX.Main.gridManager.getById(this.tableId).instance;
	}
}