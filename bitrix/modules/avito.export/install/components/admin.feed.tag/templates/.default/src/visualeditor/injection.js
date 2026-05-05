export function makeInjection() {
	return class Injection extends BXHtmlEditor.Taskbar {

		constructor(editor, sources: Array, lang: Object) {
			super(editor);

			this.id = 'avito_injection';
			this.title = lang['VISUAL_EDITOR_INJECTION_TITLE'];
			this.templateId = this.editor.templateId;
			this.uniqueId = 'taskbar_' + this.editor.id + '_' + this.id;
			this.searchPlaceholder = lang['VISUAL_EDITOR_INJECTION_SEARCH'];
			this.avitoSources = sources;

			this.Init();
		}

		Init() : void {
			const [groups, items] = this.treeParams();

			this.BuildSceleton();
			this.BuildTree(groups, items);
		}

		HandleElementEx(wrap, dd, params) : void {
			this.editor.SetBxTag(dd, {
				tag: 'php_protected',
				params: params,
			});
		};

		treeParams() : Object {
			const groups = [];
			const items = [];

			for (const group of this.avitoSources) {
				groups.push({
					name: group['TITLE'],
					path: '',
					title: group['TITLE'],
				});

				for (const item of group['ITEMS']) {
					items.push({
						value: this.isSourceField(item['ID'])
							? '{=' +  item['ID'] + '}'
							: item['ID'],
						path: group['TITLE'],
						title: item['VALUE'],
					});
				}
			}

			return [groups, items];
		}

		isSourceField(id: string) : boolean {
			return /^[A-Z0-9_]+\.[A-Z0-9_]+/.test(id);
		}
	}
}