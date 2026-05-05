export  class ParseError extends Error {

	constructor(
		error: Error,
		response: string,
    ) {
		super(error.message);
		this.response = response;
		this.previousError = error;
	}

}