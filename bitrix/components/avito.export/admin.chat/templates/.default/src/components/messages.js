import {LoaderMessages} from "./loaders/loadermessages";
import {LoaderScroll} from "./loaders/loaderscroll";
import {ScrollButton} from "./scrollbutton";
import {EmptyMessages} from "./emptymessages";
import {Message} from "./message";
import {store} from "../store";
import {Ajax} from "../ajax";

const Messages = {
    components: {
        Message,
        LoaderMessages,
	    LoaderScroll,
	    ScrollButton,
	    EmptyMessages,
    },
	mounted() {
		document.addEventListener('click', this.hideContextMenu);
	},
	methods: {
		handleScroll: function(event) {
			const target = event.currentTarget;
			const scrollTop = Math.abs(target.scrollTop);
			store.scrollButton = scrollTop > 1;
			if (
				scrollTop > 1
				&& target.offsetHeight + scrollTop >= target.scrollHeight - 10
				&& !store.isLoadingMessageScroll
				&& !store.stopMessageScroll
			) {

				store.offsetMessage += store.limitMessage;
				store.isLoadingMessageScroll = true;
				Ajax.messagesByChatId(store.chatId)
					.then(data => {
						store.isLoadingMessageScroll = false;
						if (data.length > 0) {
							store.pushMessages(data);
						} else {
							store.stopMessageScroll = true;
						}
					})
			}
		},
		hideContextMenu: function(event) {
			const actions = document.querySelector('.avito-chat-message-actions.show');
			if (actions == null || actions.contains(event.target)) { return; }
			actions.classList.remove('show');
			actions.classList.add('hide');
			store.messageMenuId = null;
		}
	},
    setup() {
        return { store }
    },
    // language=Vue
    template: `
      <div class="avito-chat-messages" @scroll="handleScroll">
        <LoaderMessages v-if="store.isMessagesLoading"/>
        <EmptyMessages v-if="!store.isMessagesLoading && store.messages.length === 0"/>
        <Message 
	        v-if="!store.isMessagesLoading" 
	        v-for="message in store.messages" 
	        :messageData="message" 
	        :key="message.id" 
	        :author="store.author(message.author_id)" 
        />
        <LoaderScroll v-if="store.isLoadingMessageScroll"/>
      </div>
      <ScrollButton/>
    `
};

export { Messages };
