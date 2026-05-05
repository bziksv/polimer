// noinspection JSUnresolvedVariable
const $ = window.AvitoJQuery ?? window.$;

export class Fetcher {

	constructor() {
		this.queries = {};
		this.loaded = {};
	}

	load(url: string, personTypeId: number) : Promise {
		return new Promise((resolve, reject) => {
			const cacheKey = `${url}-${personTypeId}`;

			if (this.loaded[cacheKey] != null) {
				resolve(this.loaded[cacheKey]);
				return;
			}

			if (this.queries[cacheKey] != null) {
				resolve(this.queries[cacheKey]);
				return;
			}

			const query = this.query(url, personTypeId);

			query
				.then((data) => this.loaded[cacheKey] = data)
				.then(resolve, reject)
				.finally(() => { this.queries[cacheKey] = null; });

			this.queries[cacheKey] = query;

			return query;
		});
	}

	query(url: string, personTypeId: number) : Promise {
		return new Promise((resolve, reject) => {
			$.ajax({
				url: url,
				type: 'POST',
				data: { personTypeId: personTypeId },
				dataType: 'json',
			})
				.then(
					this.queryEnd.bind(this, resolve, reject),
					this.queryStop.bind(this, reject)
				);
		});
	}

	queryEnd(resolve: () => {}, reject: () => {}, response: Object) : Array {
		if (!response || response.status !== 'ok') {
			reject(response.message ?? 'unknown');
			return;
		}

		resolve(response.enum);
	}

	queryStop(reject: () => {}) : void {
		reject(new Error('failed'));
	}
}