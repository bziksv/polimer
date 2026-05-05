import {ValueSkeleton} from "./skeleton";

export class ValueText extends ValueSkeleton {

	type() : string {
		return 'text';
	}

	boot() {
		if (!this._multiple) { return; }

		this.$el.select2(this.selectOptions());
	}

	selectOptions() : Object {
		return {
			tags: true,
			tokenSeparators: [',']
		};
	}

	destroy() {
		if (!this._multiple) { return; }

		this.$el.select2('destroy');
	}

	reflow() : void {
		const nowMultiple = this.$el.is('select');

		if (nowMultiple === this._multiple) { return; }

		if (nowMultiple) {
			this.$el.select2('destroy');
		}

		this.render();
		this.boot();
	}

	template() : string {
		return this._multiple ? `<select multiple data-entity="value"></select>` : `<input type="text" data-entity="value" />`;
	}

}