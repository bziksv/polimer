import {Field} from "./field";

export class SingleField extends Field {

	static defaults = Object.assign({}, Field.defaults, {
		itemTemplate:
			'<tr>' +
				'<td class="bx-avito-export-characteristic__label" data-entity="attribute">#ATTRIBUTE_VALUE#</td>' +
				'<td class="bx-avito-export-characteristic__value" data-entity="value"><select class="bx-avito-export-characteristic__value-control" name="#VALUE_NAME#"></select></td>' +
				'<td class="bx-avito-export-characteristic__actions"><button class="bx-avito-export-characteristic__delete" type="button" data-entity="delete">#DELETE_TITLE#</button></td>' +
			'</tr>',
	})

	itemVariables(attribute: string, index: string): Object {
		return Object.assign({}, super.itemVariables(attribute), {
			ATTRIBUTE_VALUE: attribute,
			VALUE_NAME: `${this.options.valueName}[${attribute}]`,
		});
	}

}