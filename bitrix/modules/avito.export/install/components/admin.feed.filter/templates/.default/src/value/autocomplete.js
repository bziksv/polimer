import {ValueSkeleton} from "./skeleton";

export class ValueAutocomplete extends ValueSkeleton {

	type() : string {
		return 'autocomplete';
	}

	boot() : void {
		this.$el.select2(this.selectOptions());
	}

	destroy() {
		this.$el.select2('destroy');
	}

	reflow() : void {
		if (this.$el.prop('multiple') === this._multiple) { return; }

		this.$el.select2('destroy');
		this.$el.prop('multiple', this._multiple);
		this.$el.select2(this.selectOptions());
	}

	template() : string {
		// noinspection HtmlUnknownAttribute
		return `<select class="adm-input-wrap adm-input-wrap-calendar" ${this._multiple ? 'multiple' : ''} data-entity="value"></select>`;
	}

	selectOptions() : Object {
		return {
			minimumInputLength: 1,
			ajax: {
				transport: this.ajaxTransport(),
			},
		};
	}

	ajaxTransport() {
		return (params, success, failure) => {
			this._transport.fetch('suggest', {
				query: params.data.term,
				field: this._field['ID'],
			})
				.then((options: Array) => this.convertOptions(options))
				.then(success)
				.catch((error) => { failure(error.message) });
		};
	}

	convertOptions(options: Array) : Object {
		const items = [];

		for (const option of options) {
			items.push({
				id: option['ID'],
				text: option['VALUE'],
			});
		}

		return {
			results: items,
		};
	}
}