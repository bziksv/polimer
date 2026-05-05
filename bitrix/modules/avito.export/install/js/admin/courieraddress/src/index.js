import {Skeleton} from "../../../plugin/skeleton";

// noinspection JSUnresolvedVariable
const $ = window.AvitoJQuery ?? window.$;

// noinspection JSUnusedGlobalSymbols
export class CourierAddress extends Skeleton {

	static defaults = {
		url: '/bitrix/tools/avito.export/courieraddress/getcourierdeliveryrange.php',
		orderId: null,
		exchangeId: null,

		errorMessageElement: '[data-entity="error-message"]',

		lang: {},
		langPrefix: 'AVITO_EXPORT_ADMIN_COURIER_ADDRESS_'
	}

	constructor(element: HTMLElement, options: Object = {}) {
		super(element, options);
		this.bind();
		this.boot();
	}

	bind() : void {
		this.handleChange(true);
	}

	unbind() : void {
		this.handleChange(false);
	}

	handleChange(dir: Boolean) : void {
		this.$el[dir ? 'on' : 'off']('change', this.onChange);
	}

	onChange = () : void => {
		const address = this.el.value.trim();

		if (address === '') {
			this.clearError();
			this.reset();
		} else {
			this.clearError();
			this.reload(address);
		}
	}

	boot() : void {
		const address = this.$el.val().trim();

		if (address === '') {
			this.reset();
		} else {
			this.reload(address);
		}
	}

	avitoError(data) : void {
		this.errorElement().text(data.message);
	}

	serverError(data) : void {
		this.errorElement().text(`${data.statusText} ${data.status}`);
	}

	clearError() : void {
		this.errorElement().text('');
	}

	errorElement() : JQuery {
		return this.getElement('errorMessage', this.$el, 'nextAll');
	}

	reload(address: string) : void {
		$.ajax({
			type: 'POST',
			url: this.options.url,
			dataType: 'json',
			data: {
				'orderId': this.options.orderId,
				'exchangeId': this.options.exchangeId,
				'address': address,
			},
			success: (data) => {
				if (data?.success !== true) {
					this.avitoError(data);
					this.reset();
					return;
				}

				this.eventAnchor().triggerHandler('avitoCourierTime', {
					options: data.options,
				});
			},
			error: (data) => {
				this.serverError(data);
				this.reset();
			}
		});
	}

	reset() : void {
		this.eventAnchor().triggerHandler('avitoCourierAddressReset');
	}

	eventAnchor() : JQuery {
		const form = this.$el.closest('form');

		return form.length > 0 ? form : $(document);
	}
}