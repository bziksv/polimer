export const store = window.AvitoVue3.reactive({
    component: String,
    setupId: String,
    userId: String,
	chats: [],
	selectChat: [],
    messages: [],
	error: null,
    chatId: null,
	chat: null,
	otherUserUrl: String,
    isMessagesLoading: true,
	showError: false,
    messageMenuId: null,
    limitChat: 50,
    offsetChat: 0,
    limitMessage: 50,
    offsetMessage: 0,
	isLoadingMessageScroll: false,
	stopMessageScroll: false,
	scrollButton: false,
	scrollButtonCircle: false,
	checkMessagesSending: false,

    unshiftMessage(message) {
		if (this.messages.length > 0) {
			const firstMessage = this.messages[0];
			message.rendering_avatar = firstMessage.author_id !== message.author_id;
			message.delimiter = false;
		}
        this.messages.unshift(message);
    },
	pushChatMessage(message, read = true) {
		this.chats[message.chat_id].updated = message.created;
		this.chats[message.chat_id].read = read;
		this.chats[message.chat_id].message = message.chat_message.text;
	},
	pushMessages(messages) {
		const firstMessage = messages[0];
		const lastMessage = this.messages[this.messages.length - 1];
		lastMessage.rendering_avatar = lastMessage.author_id !== firstMessage.author_id;

		for (let message of messages) {
			if (lastMessage.delimiter === message.delimiter) {
				lastMessage.delimiter = false;
			}
			this.messages.push(message);
		}
	},
	readMessages() {
		store.messages.forEach((message, index) => {
			if (!message.isRead) {
				store.messages[index].isRead = true;
			}
		});
	},
	pushChats(chat) {
		this.chats.push(chat);
	},
	deleteError() {
		this.error = null;
	},
	pushError(error) {
		this.error = error;
	},
	sortMessages() {
		store.messages.sort((a, b) => {
			return b.created - a.created;
		});
	},
	sortChats() {
		const keys = Object.keys(this.chats);
		let sortedObj = {};

		keys.sort((a, b) => {
			return this.chats[b].updated - this.chats[a].updated;
		});

		keys.forEach((key) => {
			sortedObj[key] = this.chats[key];
		});

		this.chats = sortedObj;
	},
    setMessages(messages) {
        this.messages = messages;
    },
    setChatId(chatId) {
        this.chatId = chatId;
		this.chat = this.chats[this.chatId];
		for (const userId in this.chat.users) {
			if (parseInt(userId) === this.userId) { continue; }
			this.otherUserUrl = this.chat.users[userId].url;
		}
    },
	setShowError(bool) {
		this.showError = bool;
	},
	author(authorId) {
		if (authorId <= 0) { return null; }
		return this.chats[this.chatId]?.users[authorId];
	},
	clearDataForMessages() {
		this.isLoadingMessageScroll = false;
		this.stopMessageScroll = false;
		this.scrollButtonCircle = false;
		this.offsetMessage = 0;
	},
})