import {store} from "./store";

export class Errors {
    static check(response) {
        const data = response.data;

        if (data.status === 'error') {
            store.pushError(data.message);
            return;
        }

        return response.data.data;
    }
}