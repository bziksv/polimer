// @flow

import {Skeleton} from "./skeleton";
import {makeInjection} from "./visualeditor/injection";

// noinspection JSUnresolvedVariable
const $ = window.AvitoJQuery ?? window.$;

export class TemplateEditor extends Skeleton {

	static defaults = {
		value: '',
		sources: [],

		dialogWidth: 800,
		dialogHeight: 600,
		dialogTemplate: `<div data-entity="editor"></div>`,

		editorElement: '[data-entity="editor"]',
		editorTemplate: '#avito-editor-template',
		visualEditorId: 'avitoeditor',

		lang: {},

		onSave: (value: String) => {},
	}

	constructor(element: HTMLElement, options: Object = {}) {
		super(element, options);

		this.bind();
	}

	destroy() : void {
		this.unbind();
	}

	bind() : void {
		this.handleOpenClick(true);
	}

	unbind() : void {
		this.handleOpenClick(false);
		this.handleCloseClick(false);
	}

	handleOpenClick(dir: boolean) : void {
		this.$el[dir ? 'on' : 'off']('click', this.onOpenClick);
	}

	handleCloseClick(dir: boolean) : void {
		if (!dir && !this.hasDialog()) { return; }

		BX[dir ? 'addCustomEvent' : 'removeCustomEvent'](this.dialog(), 'onWindowClose', this.onDialogClose);
	}

	onOpenClick = () : void => {
		this.open();
	}

	onSaveClick = () : void => {
		this.save();
		this.dialog().Close();
	}

	onCloseClick = () : void => {
		this.dialog().Close();
	}

	onDialogClose = () : void => {
		const dialog = this.dialog();

		this.destroyDialog();
		this.handleCloseClick(dialog, false);
	}

	open() : void {
		this.openDialog();
		this.bootEditor();
		this.bootTaskbar();
		this.bootInjection();
		this.hideTaskbarSwitcher();
	}

	openDialog() : void {
		const dialog = this.dialog();
		const content = this.getTemplate('dialog');

		dialog.SetContent(content);
		dialog.Show();

		this.handleCloseClick(true);
	}

	destroyDialog() : void {
		this.dialogContent().html('');
	}

	bootEditor() : void {
		const content = this.dialogContent();
		const editor = this.getElement('editor', content);
		const initialValue = this.initialValue();
		const template = this.editorTemplateHtml();

		editor.attr('id', 'bx-html-editor-' + this.options.visualEditorId);
		editor.html(template);

		this.editorInitialValue(initialValue);
	}

	editorTemplateHtml() : string {
		return (
			this.getTemplate('editor')
				.replace(/<\\\/script>/g, '</script>')
		);
	}

	editorInitialValue(value: ?string) : void {
		const visualEditor = this.visualEditor();

		if (visualEditor?.sandbox?.inited) {
			visualEditor.SetContent(value, true);
		} else {
			const callback = () => {
				visualEditor.SetContent(value, true);
				BX.removeCustomEvent(visualEditor, 'OnCreateIframeAfter', callback);
			};

			BX.addCustomEvent(visualEditor, 'OnCreateIframeAfter', callback);
		}
	}

	bootTaskbar() : void {
		const visualEditor = this.visualEditor();

		if (visualEditor.taskbarManager != null) { return; }

		visualEditor.taskbarManager = new BXHtmlEditor.TaskbarManager(visualEditor, true);
		visualEditor.dom.taskbarCont.style.display = '';
		visualEditor.showTaskbars = true;
	}

	bootInjection() : void {
		const sources = this.sources();
		const visualEditor = this.visualEditor();
		const injectionControl = makeInjection();
		const componentsTaskbar = new injectionControl(visualEditor, sources, this.options.lang);

		visualEditor.taskbarManager.AddTaskbar(componentsTaskbar);
		visualEditor.taskbarManager.ShowTaskbar(componentsTaskbar.GetId());
		visualEditor.taskbarManager.Show(false);
	}

	initialValue() : string {
		const option = this.options.value;

		if (typeof option === 'function') {
			return option();
		}

		return option;
	}

	hideTaskbarSwitcher() : void {
		this.visualEditor().taskbarManager.pTopCont.style.display = 'none';
	}

	save() : void {
		const value = this.visualEditor().GetContent();
		const format = value.indexOf('{=') !== -1 ? 'TEMPLATE' : 'TEXT';

		this.options.onSave(value, format);
	}

	hasDialog() : boolean {
		return this._dialog != null;
	}

	dialog() : BX.CAdminDialog {
		if (this._dialog == null) {
			this._dialog = this.createDialog();
		}

		return this._dialog;
	}

	dialogContent() : $ {
		const dialog = this.dialog();

		return $(dialog.PARTS.CONTENT_DATA);
	}

	createDialog() : BX.CAdminDialog {
		return new BX.CAdminDialog({
			title: this.getLang('VISUAL_EDITOR_MODAL_TITLE', {
				TAG: this.options.tagName,
			}),
			draggable: true,
			resizable: true,
			width: this.options.width,
			height: this.options.height,
			buttons: [
				Object.assign({}, BX.CAdminDialog.btnSave, {
					action: this.onSaveClick,
				}),
				Object.assign({}, BX.CAdminDialog.btnCancel, {
					action: this.onCloseClick,
				}),
			],
		});
	}

	sources() : Array {
		const option = this.options.sources;

		if (typeof option === 'function') {
			return option();
		}

		return option;
	}

	visualEditor() : BXEditor {
		return BXHtmlEditor.Get(this.options.visualEditorId);
	}
}

