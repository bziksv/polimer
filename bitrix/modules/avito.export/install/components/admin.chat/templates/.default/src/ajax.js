import {store} from "./store";
import {Errors} from "./errors";

export class Ajax {
    static async chats() {
        return await BX.ajax.runComponentAction(store.component, 'chats', {
            data: {
                setupId: store.setupId,
                userId: store.userId,
	            limit: store.limitChat,
                offset: store.offsetChat,
            },
        }).then(response => { return Errors.check(response) });
    }

	static async chatById(chatId) {
		return await BX.ajax.runComponentAction(store.component, 'chatById', {
			data: {
				setupId: store.setupId,
				userId: store.userId,
				chatId: chatId,
			},
		}).then(response => { return Errors.check(response) });
	}

    static async messagesByChatId(chatId) {
        return await BX.ajax.runComponentAction(store.component, 'messagesByChatId', {
            data: {
                setupId: store.setupId,
                userId: store.userId,
                chatId: chatId,
	            limit: store.limitMessage,
	            offset: store.offsetMessage,
            },
        }).then(response => { return Errors.check(response) });
    }

    static async sendMessage(message) {
        return await BX.ajax.runComponentAction(store.component, 'sendMessage', {
            data: {
                setupId: store.setupId,
                userId: store.userId,
                chatId: store.chatId,
                message: message,
            },
        }).then(response => { return Errors.check(response) });
    }

	static async readChat(chatId) {
        return await BX.ajax.runComponentAction(store.component, 'readChat', {
            data: {
                setupId: store.setupId,
                userId: store.userId,
                chatId: chatId,
            },
        }).then(response => { return Errors.check(response) });
    }

    static async deleteMessage(messageId) {
        return await BX.ajax.runComponentAction(store.component, 'deleteMessage', {
            data: {
                setupId: store.setupId,
                userId: store.userId,
                chatId: store.chatId,
                messageId: messageId,
            },
        }).then(response => { return Errors.check(response) });
    }

	static async checkNewMessage() {
		return await BX.ajax.runComponentAction(store.component, 'checkNewMessages', {
			data: {
				setupId: store.setupId,
                checkTimestamp: store.checkTimestamp,
			},
		}).then(response => { return Errors.check(response) });
	}
}