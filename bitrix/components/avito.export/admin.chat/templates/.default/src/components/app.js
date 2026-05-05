import { ChatList } from "./chatlist";
import { ChatArea } from "./chatarea";
import { Error } from "./error";
import { store } from "../store";

const App = {
    components: {
        ChatList,
        ChatArea,
        Error,
    },
    beforeMount() {
		store.component = this.component;
		store.setupId = this.setupId;
		store.userId = this.userId;
		store.chatId = this.chatId;
        store.checkTimestamp = this.checkTimestamp;
	},
    setup() {
        return { store };
    },
	// language=Vue
    template: `
		<ChatList />
		<ChatArea />
        <Error />
    `
};

export { App };