import {ValueSkeleton} from "./skeleton";

export class ValueSelect extends ValueSkeleton {

	type() : string {
		return 'select';
	}

	boot() {
		this.stampVariants(this.templateOptions());
		this.$el.select2(this.selectOptions());
	}

	destroy() {
		this.$el.select2('destroy');
	}

	reflow() : void {
		const needBoot = (this.$el.prop('multiple') !== this._multiple);
		const options = this.templateOptions();

		if (needBoot) {
			this.$el.select2('destroy');
			this.$el.prop('multiple', this._multiple);
		}

		if (this.changedVariants(options)) {
			this.stampVariants(options);
			this.$el.html(options);
		}

		if (needBoot) {
			this.$el.select2(this.selectOptions());
		}
	}

	selectOptions() : Object {
		return {
			tokenSeparators: [',']
		};
	}

	template() : string {
		const options = this.templateOptions();

		this.stampVariants(options);

		// noinspection HtmlUnknownAttribute
		return `<select class="adm-input-wrap adm-input-wrap-calendar" ${this._multiple ? 'multiple' : ''} data-entity="value">
			${options}
		</select>`;
	}

	templateOptions() : string {
		return this._field['VARIANTS'].map((option) => `<option value="${option['ID']}">${option['VALUE']}</option>`).join('');
	}

	changedVariants(options: string) : boolean {
		return this._renderedOptions !== options;
	}

	stampVariants(options: string) : void {
		this._renderedOptions = options;
	}
}