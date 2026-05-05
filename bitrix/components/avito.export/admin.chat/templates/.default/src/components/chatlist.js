import {ChatListElement} from "./chatlistelement";
import {LoaderList} from "./loaders/loaderlist";
import {LoaderScroll} from "./loaders/loaderscroll";
import {store} from "../store";
import {Ajax} from "../ajax";

const ChatList = {
    data() {
        return {
			isLoading: true,
	        isLoadingScroll: false,
	        stopScroll: false,
	        _checkMessagesScheduleTimeout: null,
	        _checkMessagesStartFrame: null,
	        _checkMessagesStartTimeout: null,
        }
    },
	components: {
		ChatListElement,
		LoaderList,
		LoaderScroll,
	},
	setup() {
		return { store }
	},
	mounted(){
		Ajax.chats()
			.then(async chats => {
				if (store.chatId !== null) {
					if (chats[store.chatId] == null) {
						await Ajax.chatById(store.chatId)
							.then(chat => {
								chats = Object.assign({[store.chatId]: chat}, chats);
							})
					}
				}
				store.chats = chats;
			})
			.then(() => {
				this.isLoading = false;
				if (store.chatId !== null) {
					store.setChatId(store.chatId);
					this.messagesByChatId(store.chatId);
				}
				else {
					const firstChat = Object.keys(store.chats)[0];
					store.setChatId(firstChat);
					this.messagesByChatId(firstChat);
				}
			})
	},
	methods: {
		handleScroll: function(event) {
			const target = event.currentTarget;
			if (
				target.offsetHeight + target.scrollTop >= target.scrollHeight
				&& !this.isLoadingScroll
				&& !this.stopScroll
			) {
				store.offsetChat += store.limitChat;
				this.isLoadingScroll = true;
				Ajax.chats()
					.then(data => {
						this.isLoadingScroll = false;
						if (Object.keys(data).length > 0) {
							for (const [id, chat] of Object.entries(data)) {
								store.chats[id] = chat;
							}
						}
						else {
							this.stopScroll = true;
						}
					})
			}
		},
		selectChat: function (event) {
			if (store.isMessagesLoading || store.isLoadingMessageScroll || this.isLoadingScroll) { return; }
			const chatId = event.currentTarget.getAttribute('data-chat-id');
			this.toggleChat();
			if (store.chatId === chatId) { return; }
			store.isMessagesLoading = true;
			store.setChatId(chatId);
			store.clearDataForMessages();
			this.messagesByChatId(chatId);
		},
		toggleChat: function() {
			const list = document.querySelector('.avito-chat-list');
			const area = document.querySelector('.avito-chat-area');
			list.classList.replace('show', 'hide');
			area.classList.replace('hide', 'show');
		},
		messagesByChatId: function(chatId) {
			Ajax.messagesByChatId(chatId)
				.then(data => {
					store.setMessages(data);
					store.isMessagesLoading = false;
					this.clearCheckMessages();
				})
				.then(() => Ajax.readChat(chatId).then(data => {store.chats[chatId].read = true;}))
				.then(() => {
					this.scheduleCheckMessages();
				});
		},

		scheduleCheckMessages: function() {
			this._checkMessagesScheduleTimeout = setTimeout(this.startCheckMessages.bind(this), 10000);
		},

		startCheckMessages: function(chatId) {
			this._checkMessagesStartFrame = requestAnimationFrame(this.checkMessages.bind(this));
			this._checkMessagesStartTimeout = setTimeout(this.checkMessages.bind(this), 600000);
		},

		clearCheckMessages: function() {
			clearTimeout(this._checkMessagesScheduleTimeout);
			clearTimeout(this._checkMessagesStartTimeout);
			cancelAnimationFrame(this._checkMessagesStartFrame);
		},

		checkMessages: function() {
			this.clearCheckMessages();

			Ajax.checkNewMessage()
				.then(data => {
					let readMessages = false;
					store.checkTimestamp = data.checkTimestamp;
					if (data?.messages != null && data.messages.length > 0) {

						for (let message of data.messages) {

							let isOwnMessage = false;

							for (let storeMessage of store.messages) {
								if (storeMessage.id === message.id) {
									isOwnMessage = true;
									storeMessage.isRead = message.isRead;
									break;
								}
							}

							if (!isOwnMessage && store.chatId === message.chat_id) {
								store.scrollButtonCircle = true;
								store.unshiftMessage(message);
								store.pushChatMessage(message);
								store.readMessages();
								readMessages = true;
							} else if (store.chats[message.chat_id] == null) {
								Ajax.chatById(message.chat_id)
									.then(chat => {
										store.chats = Object.assign({[message.chat_id]: chat}, store.chats);
									})
							} else if (store.chatId !== message.chat_id) {
								store.pushChatMessage(message, false);
							}
						}

						store.sortChats();
					}

					return readMessages;
				})
				.then((readMessages) => {
					if (readMessages) {
						Ajax.readChat(store.chatId);
					}

					this.scheduleCheckMessages();
				})
				.catch(() => {
					this.scheduleCheckMessages();
				});
		},
	},
	// language=Vue
    template: `
		<div class="avito-chat-list hide" @scroll="handleScroll">
			<LoaderList v-if="isLoading"/>
			<ChatListElement 
				v-if="!isLoading && store.chats !== null" 
				v-for="chat in store.chats" :chatInfo="chat" :key="chat.id" 
				@click="selectChat"/>
			<LoaderScroll v-if="isLoadingScroll"/>
		</div>
    `
};

export { ChatList };
