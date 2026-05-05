// noinspection JSUnresolvedVariable

import ReferenceField from "../reference/field";

export default class Field extends ReferenceField {
	static messages = {}

	static create(id, settings) {
		const instance = new Field();
		instance.initialize(id, settings);

		return instance;
	}

	useTitle() {
		return true;
	}

	render(payload) {
		const html = this.build(payload);

		this._wrapper.insertAdjacentHTML('beforeend', html);
	}

	build(payload) {
		return `<div class="ui-entity-editor-content-block">
			<div class="ui-entity-editor-content-block-text">
				${payload['VALUE']}
			</div>
		</div>`;
	}
}