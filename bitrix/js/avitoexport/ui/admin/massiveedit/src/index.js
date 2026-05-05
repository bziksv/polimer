import {MassiveEdit} from "./massiveedit";

function massiveEditOpen(tableId, options = {}) {
	try {
		const controller = new MassiveEdit(tableId, options);
		controller.open();
	} catch (e) {
		showError(tableId, e.message);
	}
}

function showError(tableId: string, message: string) {
	const uiGrid = BX.Main.gridManager.getById(tableId).instance;

	uiGrid.arParams.MESSAGES = [
		{ TYPE: 'ERROR', TEXT: message }
	];

	BX.onCustomEvent(window, 'BX.Main.grid:paramsUpdated', []);
}

export {
	MassiveEdit,
	massiveEditOpen
};