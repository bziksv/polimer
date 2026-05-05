import {ValueSkeleton} from "./skeleton";

export class ValueDateTime extends ValueSkeleton {

	type() : string {
		return 'datetime';
	}

	boot() : void {
		this.handleCalendarClick(true);
	}

	destroy() {
		this.handleCalendarClick(false);
	}

	handleCalendarClick(dir: boolean) : void {
		const icon = this.$el.next('.adm-calendar-icon');

		icon[dir ? 'on' : 'off']('click', this.onCalendarClick);
	}

	onCalendarClick = (evt) : void => {
		BX.calendar({
			node: evt.currentTarget,
			field: this.$el[0],
			form: '',
			bTime: true,
			bHideTime: true,
		});
	}

	template() : string {
		return `<div class="adm-input-wrap adm-input-wrap-calendar" data-entity="valueAnchor">
			<input class="adm-input adm-input-calendar" type="text" data-entity="value" />
			<span class="adm-calendar-icon"></span>
		</div>`;
	}
}