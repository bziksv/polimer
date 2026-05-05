// noinspection JSUnresolvedVariable

import ReferenceField from "../reference/field";
import ItemView from "./itemview";
import { htmlToElement, kebabCase } from "../utils";
import './basket.css';
import {Activity} from "../molecules/activity";

export default class Field extends ReferenceField {
	static messages = {}
	static defaults = {
		name: 'BASKET',
		actions: [],
	}

	static create(id, settings) {
		const instance = new Field();
		instance.initialize(id, settings);

		return instance;
	}

	constructor() {
		super();
		this.items = [];
		this.activities = [];
	}

	initialize(id, settings) : void {
		super.initialize(id, settings);
		this.handleTitleActions(true);
	}

	release() : void {
		this.handleTitleActions(false);
		super.release();
	}

	handleTitleActions(dir) : void {
		BX[dir ? 'addCustomEvent' : 'removeCustomEvent'](window, 'BX.UI.EntityEditorSection:onLayout', this.onTitleActions);
	}

	onTitleActions = (field, context) : void => {
		if (field !== this.getParent()) { return; }

		for (const activity of this.activities) {
			const button = Activity.make(activity, this._editor);

			context.customNodes.push(button);
		}
	}

	render(payload) {
		const basket = payload;
		const table = this.renderTable(basket);
		const body = table.querySelector('tbody');

		this.activities = payload.ACTIVITIES;

		this.renewItems(basket.ROWS).forEach((basketItem, index) => {
			basketItem.render(basket.ROWS[index], basket);
			basketItem.mount(body);
		});

		this._wrapper.appendChild(table);
	}

	renewItems(payloadItems) {
		this.destroyItems();

		return this.createItems(payloadItems);
	}

	destroyItems() {
		for (const item of this.items) {
			item.destroy();
		}

		this.items = [];
	}

	createItems(payloadItems) {
		let itemIndex = 0;

		this.items = [];

		for (const payloadItem of payloadItems) {
			this.items.push(this.createItem());
			++itemIndex;
		}

		return this.items;
	}

	createItem() {
		return new ItemView({
			messages: Field.messages,
		});
	}

	renderTable(basket) {
		return htmlToElement(`<div class="avito-export-basket">
			<div class="avito-export-basket-table-viewport">
				<table class="avito-export-basket-table">
					${this.renderHeader(basket)}
					<tbody></tbody>
				</table>
			</div>
			${this.renderSummary(basket)}
		</div>`);
	}

	renderHeader(basket) {
		return `<thead>
			<tr>
				${Object.keys(basket.COLUMNS)
					.map((key) => `<td class="for--${kebabCase(key)}">${basket.COLUMNS[key]}</td>`)
					.join('')}
			</tr>
		</thead>`;
	}

	renderSummary(basket) {
		return `<div class="avito-export-basket-summary">
			${basket.SUMMARY
				.map((item) => {
					return `<div class="avito-export-basket-summary__row">
						<div class="avito-export-basket-summary__label">${item['NAME']}:</div>
						<div class="avito-export-basket-summary__value">${item['VALUE_FORMATTED']}</div>
					</div>`;
				})
				.join('')}
		</div>`;
	}
}