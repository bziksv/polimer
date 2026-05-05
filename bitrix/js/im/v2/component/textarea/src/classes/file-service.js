import {BaseEvent, EventEmitter} from 'main.core.events';
import {Core} from 'im.v2.application.core';
import {FileStatus, FileType, RestMethod} from 'im.v2.const';
import {Utils} from 'im.v2.lib.utils';
import {UploadManager} from './upload-manager';
import type {ImModelMessage, ImModelDialog, ImModelUser} from 'im.v2.model';

export type FileFromDisk = {
	id: string;
	name: string;
	type: string;
	size: string;
	sizeInt: string;
	modifyBy: string;
	modifyDate: string;
	modifyDateInt: number;
	ext: string;
	fileType: string;
};

export class FileService extends EventEmitter
{
	#dialogId: string = '';
	#store;
	#restClient;
	#isRequestingDiskFolderId: boolean = false;
	#diskFolderIdRequestPromise: Promise | undefined;
	#uploadManager: UploadManager;

	static eventNamespace = 'BX.Messenger.v2.Textarea.UploadingService';

	static events = {
		sendMessageWithFile: 'sendMessageWithFile',
	};

	constructor(params: {dialogId: string})
	{
		super();
		this.setEventNamespace(FileService.eventNamespace);

		this.#store = Core.getStore();
		this.#restClient = Core.getRestClient();
		this.#uploadManager = new UploadManager();
		this.#dialogId = params.dialogId;

		this.#initUploadManager();
	}

	uploadFile(temporaryFileId: string, rawFile: File): Promise
	{
		return this.#uploadManager.addUploadTask(temporaryFileId, rawFile).then(uploadTask => {
			const {taskId, file, preview} = uploadTask;

			return this.addFileToModel(taskId, file, preview);
		});
	}

	addFileFromDiskToModel(combinedFileId: string, file: FileFromDisk): Promise
	{
		return this.#store.dispatch('files/add', {
			id: combinedFileId,
			chatId: this.#getChatId(),
			authorId: Core.getUserId(),
			name: file.name,
			type: Utils.file.getFileTypeByExtension(file.ext),
			extension: file.ext,
			size: file.sizeInt,
			status: FileStatus.wait,
			progress: 0,
			authorName: this.#getCurrentUser().name,
		});
	}

	#initUploadManager()
	{
		this.#uploadManager = new UploadManager();
		this.#uploadManager.subscribe(UploadManager.events.onFileUploadProgress, (event: BaseEvent) => {
			const {task} = event.getData();
			this.#updateFileProgress(task.taskId, task.progress, FileStatus.upload);
		});
		this.#uploadManager.subscribe(UploadManager.events.onFileUploadComplete, (event: BaseEvent) => {
			const {task, result} = event.getData();
			this.#updateFileProgress(task.taskId, task.progress, FileStatus.wait);
			const [temporaryMessageId] = task.taskId.split('|');

			this.commitFile({
				temporaryMessageId: temporaryMessageId,
				temporaryFileId: task.taskId,
				realFileId: result.data.file.id
			});
		});
		this.#uploadManager.subscribe(UploadManager.events.onFileUploadError, (event: BaseEvent) => {
			const {task} = event.getData();
			this.#updateFileProgress(task.taskId, 0, FileStatus.error);
		});
		this.#uploadManager.subscribe(UploadManager.events.onFileUploadCancel, (event: BaseEvent) => {
			const {taskId} = event.getData();
			this.#cancelUpload(taskId);
		});
	}

	checkDiskFolderId(): Promise<number>
	{
		if (this.#getDiskFolderId() > 0)
		{
			this.#uploadManager.setDiskFolderId(this.#getDiskFolderId());

			return Promise.resolve(this.#getDiskFolderId());
		}

		if (this.#isRequestingDiskFolderId)
		{
			return this.#diskFolderIdRequestPromise;
		}

		this.#diskFolderIdRequestPromise = this.#requestDiskFolderId();

		return this.#diskFolderIdRequestPromise;
	}

	#requestDiskFolderId(): Promise
	{
		return new Promise((resolve, reject) =>
		{
			this.#isRequestingDiskFolderId = true;

			this.#restClient.callMethod(RestMethod.imDiskFolderGet, {chat_id: this.#getChatId()}).then(response => {
				const {ID: diskFolderId} = response.data();
				this.#isRequestingDiskFolderId = false;
				this.#store.commit('dialogues/update', {
					dialogId: this.#dialogId,
					fields: {
						diskFolderId: diskFolderId,
					}
				});
				this.#uploadManager.setDiskFolderId(diskFolderId);
				resolve(diskFolderId);
			}).catch(error => {
				this.#isRequestingDiskFolderId = false;
				reject(error);
			});
		});
	}

	commitFile(params: {temporaryMessageId: string, temporaryFileId: string, realFileId: number, fromDisk: boolean})
	{
		const {temporaryMessageId, temporaryFileId, realFileId, fromDisk} = params;

		const messageWithTemplateId: ImModelMessage = this.#store.getters['messages/getMessage']({
			messageId: temporaryMessageId
		});
		if (!messageWithTemplateId)
		{
			//todo: is that possible? remove message?
			return;
		}

		const fileIdParams = {};
		if (fromDisk)
		{
			fileIdParams.disk_id = realFileId;
		}
		else
		{
			fileIdParams.upload_id = realFileId;
		}

		this.#restClient.callMethod(RestMethod.imDiskFileCommit, {
			chat_id: this.#getChatId(),
			message: messageWithTemplateId.text,
			template_id: temporaryMessageId,
			file_template_id: temporaryFileId,
			...fileIdParams
		}).catch(error => {
			console.error('fileCommit error', error);
		});
	}

	#updateFileProgress(id: string, progress: number, status: string)
	{
		this.#store.dispatch('files/update', {
			id: id,
			fields: {
				progress: (progress === 100 ? 99 : progress),
				status: status,
			}
		});
	}

	#cancelUpload(taskId: string)
	{
		this.#store.dispatch('messages/delete', {id: taskId});
		this.#uploadManager.cancel(taskId);
	}

	addFileToModel(id: string, file: File, preview: {height: string, width: string, blob: Blob}): Promise
	{
		const previewData = {};
		if (preview.blob)
		{
			previewData.image = {
				width: preview.width,
				height: preview.height,
			};

			previewData.urlPreview = URL.createObjectURL(preview.blob);
		}

		return this.#store.dispatch('files/add', {
			id: id,
			chatId: this.#getChatId(),
			authorId: Core.getUserId(),
			name: file.name,
			type: this.#getFileType(file),
			extension: this.#getFileExtension(file),
			size: file.size,
			status: FileStatus.progress,
			progress: 0,
			authorName: this.#getCurrentUser().name,
			...previewData
		});
	}

	#getDiskFolderId(): number
	{
		return this.#getDialog().diskFolderId;
	}

	#getFileType(file: File): string
	{
		let fileType = FileType.file;
		if (file.type.startsWith('image'))
		{
			fileType = FileType.image;
		}
		else if (file.type.startsWith('video'))
		{
			fileType = FileType.video;
		}

		return fileType;
	}

	#getFileExtension(file: File): string
	{
		return file.name.split('.').splice(-1)[0];
	}

	#getDialog(): ?ImModelDialog
	{
		return this.#store.getters['dialogues/get'](this.#dialogId);
	}

	#getChatId(): ?number
	{
		return this.#getDialog().chatId;
	}

	#getCurrentUser(): ImModelUser
	{
		const userId = Core.getUserId();

		return this.#store.getters['users/get'](userId);
	}

	destroy()
	{
		this.#uploadManager.destroy();
	}
}