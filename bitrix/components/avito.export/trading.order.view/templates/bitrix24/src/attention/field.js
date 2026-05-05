// noinspection JSUnresolvedVariable

import ReferenceField from "../reference/field";
import './attention.css';
import {htmlToElement} from "../utils";
import {Activity} from "../molecules/activity";

export default class Field extends ReferenceField {

	static defaults = {}

	static create(id, settings) {
		const instance = new Field();
		instance.initialize(id, settings);

		return instance;
	}

	render(value) {
		this.renderMessages(value['MESSAGES']);
		this.renderActivities(value['ACTIVITIES']);
	}

	renderMessages(messages) : void {
		if (!Array.isArray(messages) || messages.length === 0) { return; }

		const html = messages.map((one) => `<div class="ui-alert ui-alert-${one.type}"><div>${one.text}</div></div>`).join('');

		this._wrapper.insertAdjacentHTML('beforeend', html);
	}

	renderActivities(activities) : void {
		if (!Array.isArray(activities) || activities.length === 0) { return; }

		const fragment = htmlToElement(`<div class="avito-export-activity-buttons"></div>`);
		let alert = this._wrapper.querySelector('.ui-alert');

		if (alert === null)
		{
			alert = htmlToElement('<div class="ui-alert ui-alert-info"></div>');
			this._wrapper.insertAdjacentElement('beforeend', alert);
		}

		activities.forEach((activity) => {
			fragment.insertAdjacentElement(
				'beforeend',
				Activity.make(activity, this._editor)
			);
		});

		alert.insertAdjacentElement('beforeend', fragment);
	}
}