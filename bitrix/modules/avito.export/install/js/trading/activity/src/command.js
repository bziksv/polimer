// noinspection JSUnresolvedReference

import {Skeleton} from "./skeleton";

export class CommandActivity extends Skeleton {

	static defaults = Object.assign({}, Skeleton.defaults, {
		confirm: null,
	});

	activate() : void {
		if (!this.confirm()) { return; }

		this.view.showLoading();

		this.sendCommand(this.options.url)
			.then((response) => this.parseCommandResponse(response))
			.then(() => {
				this.view.hideLoading();
				this.view.reload();
			})
			.catch((error) => {
				this.view.hideLoading();
				this.view.showError(error);
			});
	}

	confirm() : boolean {
		const message = this.options.confirm;

		if (message == null) { return true; }

		return confirm(message);
	}

	sendCommand(url) : Promise {
		return new Promise(function(resolve, reject) {
			// noinspection SpellCheckingInspection
			BX.ajax({
				url: url,
				method: 'POST',
				data: {
					sessid: BX.bitrix_sessid(),
				},
				dataType: 'json',
				onsuccess: resolve,
				onfailure: reject,
			});
		});
	}

	parseCommandResponse(response) : void {
		if (response == null || typeof response !== 'object') {
			throw new Error('ajax response missing');
		}

		if (response.status == null) {
			throw new Error('ajax response status missing');
		}

		if (response.status === 'error') {
			throw new Error(response.message);
		}
	}

}