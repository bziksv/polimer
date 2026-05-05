export class HttpError extends Error {

	constructor(message: string, response: string) {
		super(message);

		this.response = response;
	}

}