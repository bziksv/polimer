export class Transport {

	constructor(component: string, signedParameters: string) {
		this.component = component;
		this.signedParameters = signedParameters;
	}

	fetch(action: string, data: Object = {}) : Promise {
		return new Promise((resolve, reject) => {
			// noinspection JSUnresolvedFunction
			BX.ajax.runComponentAction(this.component, action, {
				mode: 'ajax',
				signedParameters: this.signedParameters,
				data: data,
			})
				.then((response) => {
					const data = response.data;

					if (data.status === 'ok') {
						resolve(data.data);
					} else if (data.status === 'error') {
						reject(new Error(data.message));
					} else {
						reject(new Error('unknown response format'));
					}
				})
				.catch((response) => { reject(this.queryError(response)); });
		});
	}

	// noinspection DuplicatedCode
	queryError(response: Object) : Error {
		if (response.status !== 'error') {
			return new Error('unknown response');
		}

		if (!Array.isArray(response.errors) || response.errors.length === 0) {
			return new Error('error response does not contain errors');
		}

		const error = response.errors[response.errors.length - 1];

		return new Error(error.message);
	}

}