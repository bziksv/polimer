import {store} from "../store";

const Error = {
    setup() {
        return { store };
    },
    // language=Vue
    template: `
		<div class="avito-chat-error-container pop" v-if="store.error">
          <div class="avito-chat-error-text">{{ store.error }}</div>
          <div class="avito-chat-error-close-button" @click="store.deleteError()">&#x2715;</div>
		</div>
    `
};

export { Error };