import {Messages} from "./messages";
import {Input} from "./input";
import {ChatHeader} from "./chatheader";
import {store} from "../store";

const ChatArea = {
    components: {
        Messages,
        Input,
	    ChatHeader,
    },
	setup() {
		return { store }
	},
    // language=Vue
    template: `
		<div class="avito-chat-area show">
			<ChatHeader v-if="store.chat"/>
			<Messages />
			<Input />
		</div>
    `
};

export { ChatArea };
