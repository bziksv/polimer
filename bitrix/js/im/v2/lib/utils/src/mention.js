import {Dom} from 'main.core';
import {EventEmitter} from 'main.core.events';

import {EventType, MessageMentionType} from 'im.v2.const';

export const MentionUtil = {
	clickHandler(event: PointerEvent)
	{
		if (!Dom.hasClass(event.target, 'bx-im-mention'))
		{
			return;
		}

		if (
			event.target.dataset.type === MessageMentionType.user
			|| event.target.dataset.type === MessageMentionType.chat
		)
		{
			EventEmitter.emit(EventType.mention.openChatInfo, {
				event,
				dialogId: event.target.dataset.value
			});
		}
		else if (event.target.dataset.type === MessageMentionType.dialog)
		{
			EventEmitter.emit(EventType.dialog.goToMessageContext, {
				messageId: Number.parseInt(event.target.dataset.messageId, 10),
				dialogId: event.target.dataset.dialogId.toString(),
			});
		}
	}
};