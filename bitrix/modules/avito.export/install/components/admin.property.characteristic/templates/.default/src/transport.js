export class Transport {

	constructor(component: string) {
		this.component = component;
		this._middleware = [];
	}

	middleware(callback: () => {}) {
		this._middleware.push(callback);
	}

	fetch(action: string, data: Object = {}) : Promise {
		return new Promise((resolve, reject) => {
			BX.ajax.runComponentAction(this.component, action, {
				mode: 'ajax',
				data: this.prepare(action, data),
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

	prepare(action: string, data: Object) : Object {
		for (const middleware of this._middleware) {
			data = middleware(action, data);
		}

		return data;
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