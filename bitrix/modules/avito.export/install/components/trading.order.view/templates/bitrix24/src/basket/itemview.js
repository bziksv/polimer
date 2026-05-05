// noinspection JSUnresolvedVariable

import { htmlToElement, pascalCase, kebabCase } from "../utils";

export default class ItemView {
	static defaults = {
		messages: {},
		name: null,
		title: null,
		actions: [],
		onChange: null,
	}

	constructor(options) {
		this.options = Object.assign({}, this.constructor.defaults, options);
	}

	destroy() {
		this.options = {};
	}

	getMessage(key) {
		return this.options.messages[key] || key;
	}

	render(item, basket) {
		const columns = Object.keys(basket.COLUMNS);

		this.el = htmlToElement(`<tr>
			${columns.map((key) => this.renderColumn(item, key)).join('')}
		</tr>`, 'tbody');
	}

	mount(point) {
		point.appendChild(this.el);
	}

	renderColumn(item, key) {
		const method = 'column' + pascalCase(key);

		return (
			method in this
				? this[method](item, key)
				: this.columnDefault(item, key)
		);
	}

	// noinspection JSUnusedGlobalSymbols
	columnName(item, key) {
		const links = {
			'SERVICE_URL': 'share',
			'CHAT_URL': 'chat',
		};

		return `<td class="for--${kebabCase(key)}">
			${Object.keys(links)
				.map((link) => {
					if (!item[link]) { return ''; }
					
					return `<a class="avito-export-link-icon" href="${item[link]}" target="_blank"><!--
						--><img 
								class="avito-export-link-icon__icon"
			                   	src="/bitrix/js/avitoexport/trading/i/${links[link]}_icon.svg" 
			                   	alt=""
		                    /><!--
					--></a>`;
				})
				.join('')}
			${this.valueFormatted(item, key)}
		</td>`;
	}

	// noinspection JSUnusedGlobalSymbols
	columnPrice(item, key) {
		return `<td class="for--${kebabCase(key)}">
			${this.valueFormatted(item, key)}<br />
			<small>${this.getMessage('COMMISSION')}: -${this.valueFormatted(item, 'COMMISSION')}</small>
			${this.priceDiscounts(item)}
		</td>`;
	}

	priceDiscounts(item) {
		let result = '';

		if (item['DISCOUNTS'].length > 0) {
			result = `<details>
				<summary><small>${this.getMessage('DISCOUNT')}: -${this.valueFormatted(item, 'DISCOUNT')}</small></summary>
				${item['DISCOUNTS']
					.map((discount) => `<small>${discount['TYPE']}:&nbsp;-${discount['VALUE_FORMATTED']}</small><br>`)
					.join('')}
			</details>`;
		} else if (item['DISCOUNT'] > 0) {
			result = `<small>${this.getMessage('DISCOUNT')}: -${this.valueFormatted(item, 'DISCOUNT')}</small>`;
		}

		return result;
	}

	// noinspection JSUnusedGlobalSymbols
	columnQuantity(item, key) {
		return `<td class="for--${kebabCase(key)}">${this.valueFormatted(item, key)} ${this.getMessage('QUANTITY_UNIT')}</td>`;
	}

	columnDefault(item, key) {
		return `<td class="for--${kebabCase(key)}">${this.valueFormatted(item, key)}</td>`;
	}

	valueFormatted(item, key) {
		const formattedKey = key + '_FORMATTED';
		let result = '';

		if (item[formattedKey] != null) {
			result = item[formattedKey];
		} else if (item[key] != null) {
			result = item[key];
		}

		return result !== '' ? result : '&mdash;';
	}

	value(item, key) {
		return item[key];
	}
}