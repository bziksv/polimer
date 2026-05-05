import {Type} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {Store} from 'ui.vue3.vuex';

import {Core} from 'im.v2.application.core';
import {Logger} from 'im.v2.lib.logger';
import {Utils} from 'im.v2.lib.utils';
import {callBatch} from 'im.v2.lib.rest';
import {EventType, RestMethod} from 'im.v2.const';
import {MessageService} from 'im.v2.provider.service';

import {FileService} from './file-service';

import type {FileFromDisk} from './file-service';
import type {ImModelDialog} from 'im.v2.model';

type Message = {
	temporaryId: string,
	chatId: number,
	dialogId: string,
	authorId: number,
	text: string,
	params: Object,
	withFile: boolean,
	unread: boolean,
	sending: boolean
};

export class SendingService
{
	#store: Store;
	#dialogId: string;

	#fileService: FileService;

	constructor(params: {dialogId: string})
	{
		const {dialogId} = params;
		this.#dialogId = dialogId;
		this.#store = Core.getStore();
		this.#fileService = new FileService({dialogId: this.#dialogId});
	}

	sendMessage(params: {text: string, fileId: string, temporaryMessageId: string}): Promise
	{
		const {text = '', fileId = '', temporaryMessageId} = params;
		if (!Type.isStringFilled(text) && !Type.isStringFilled(fileId))
		{
			return;
		}
		Logger.warn(`SendingService: sendMessage`, params);

		const message = this.#prepareMessage({text, fileId, temporaryMessageId});

		return this.#handlePagination().then(() => {
			return this.#addMessageToModels(message);
		}).then(() => {
			this.#sendScrollEvent({force: true});
			this.#sendMessageToServer(message);
		});
	}

	sendFilesFromInput(fileEvent: Event)
	{
		const files = [...fileEvent.target.files];
		if (files.length === 0)
		{
			return;
		}

		this.#fileService.checkDiskFolderId().then(() => {
			files.forEach((file: File) => {
				const temporaryMessageId = Utils.text.getUuidV4();
				const temporaryFileId = `${temporaryMessageId}|${Utils.text.getUuidV4()}`;

				this.#fileService.uploadFile(temporaryFileId, file).then(() => {
					this.sendMessage({temporaryMessageId: temporaryMessageId, fileId: temporaryFileId});
				});
			});
		});
	}

	sendFilesFromDisk(files: {[string]: FileFromDisk})
	{
		Object.values(files).forEach(file => {
			const temporaryMessageId = Utils.text.getUuidV4();
			const realFileId = file.id.slice(1);
			const temporaryFileId = `${temporaryMessageId}|${realFileId}`;

			this.#fileService.addFileFromDiskToModel(temporaryFileId, file).then(() => {
				return this.sendMessage({temporaryMessageId: temporaryMessageId, fileId: temporaryFileId});
			}).then(() => {
				this.#fileService.commitFile({
					temporaryFileId: temporaryFileId,
					temporaryMessageId: temporaryMessageId,
					realFileId: realFileId,
					fromDisk: true
				});
			});
		});
	}

	destroy()
	{
		this.#fileService.destroy();
	}

	#prepareMessage(params: {text: string, fileId: string, temporaryMessageId: string}): Message
	{
		const {text, fileId, temporaryMessageId} = params;

		const messageParams = {};
		if (fileId)
		{
			messageParams.FILE_ID = [fileId];
		}

		const temporaryId = temporaryMessageId || Utils.text.getUuidV4();

		return {
			temporaryId,
			chatId: this.#getDialog().chatId,
			dialogId: this.#getDialog().dialogId,
			authorId: Core.getUserId(),
			text,
			params: messageParams,
			withFile: !!fileId,
			unread: false,
			sending: true
		};
	}

	#handlePagination(): Promise
	{
		if (!this.#getDialog().hasNextPage)
		{
			return Promise.resolve();
		}

		Logger.warn('SendingService: sendMessage: there are unread pages, move to chat end');
		const messageService = new MessageService({chatId: this.#getDialog().chatId});
		return messageService.loadContext(this.#getDialog().lastMessageId).then(() => {
			this.#sendScrollEvent();
		}).catch(error => {
			console.error('SendingService: loadContext error', error);
		});
	}

	#addMessageToModels(message: Message): Promise
	{
		this.#addMessageToRecent(message);

		this.#store.dispatch('dialogues/clearLastMessageViews', {dialogId: this.#getDialog().dialogId});

		return this.#store.dispatch('messages/add', message);
	}

	#addMessageToRecent(message: Message)
	{
		const recentItem = this.#store.getters['recent/get'](this.#getDialog().dialogId);
		if (!recentItem || message.text === '')
		{
			return false;
		}

		this.#store.dispatch('recent/update', {
			id: this.#getDialog().dialogId,
			fields: {
				message: {
					id: message.temporaryId,
					text: message.text,
					authorId: message.authorId,
					status: recentItem.message.status,
				}
			}
		});
	}

	#sendMessageToServer(element: Message)
	{
		if (element.withFile)
		{
			return;
		}

		const query = {
			[RestMethod.imMessageAdd]: {
				template_id: element.temporaryId,
				dialog_id: element.dialogId
			},
			[RestMethod.imV2ChatRead]: {
				dialogId: element.dialogId,
				onlyRecent: true
			}
		};
		if (element.text)
		{
			query[RestMethod.imMessageAdd].message = element.text;
		}

		callBatch(query).then(result => {
			Logger.warn('SendingService: sendMessage result -', result[RestMethod.imMessageAdd]);
			this.#updateMessageId({oldId: element.temporaryId, newId: result[RestMethod.imMessageAdd]});
		}).catch(error => {
			this.#updateMessageError(element.temporaryId);
			console.error('SendingService: sendMessage error -', error);
		});
	}

	#updateMessageId(params: {oldId: string, newId: number})
	{
		const {oldId, newId} = params;
		this.#store.dispatch('messages/updateWithId', {
			id: oldId,
			fields: {
				id: newId
			}
		});
		this.#store.dispatch('dialogues/update', {
			dialogId: this.#getDialog().dialogId,
			fields: {
				lastId: newId,
				lastMessageId: newId
			}
		});
	}

	#updateMessageError(messageId: string)
	{
		this.#store.dispatch('messages/update', {
			id: messageId,
			fields: {
				error: true
			}
		});
	}

	#sendScrollEvent(params: {force: boolean} = {})
	{
		const {force = false} = params;
		EventEmitter.emit(EventType.dialog.scrollToBottom, {
			chatId: this.#getDialog().chatId,
			force
		});
	}

	#getDialog(): ?ImModelDialog
	{
		return this.#store.getters['dialogues/get'](this.#dialogId);
	}
}