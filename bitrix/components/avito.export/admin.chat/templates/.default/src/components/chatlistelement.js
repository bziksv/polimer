import {store} from "../store";

const ChatListElement = {
    props: ['chatInfo'],
    setup() {
        return { store, noTitle: BX.message("PRODUCT_NO_TITLE") };
    },
    // language=Vue
    template: `
          <div class="avito-chat-list-element" :class="chatInfo.id === store.chatId ? 'avito-chat-list-element-selected' : ''" :data-chat-id="chatInfo.id">
          		<div class="avito-chat-context-preview-root">
		          <div :style="chatInfo.img ? { backgroundImage: 'url(' + chatInfo.img + ')' } : {}"></div>
	            </div>
	            <div :class="'avito-chat-channel-preview-info ' + (chatInfo.type !== 'system' ? chatInfo.direction : '') + (chatInfo.read ? ' read' : '')">
	              <div class="avito-chat-name">{{ chatInfo.name }}</div>
	              <div class="avito-chat-title">{{ chatInfo.title ? chatInfo.title : noTitle }}</div>
	              <div class="avito-chat-last-message">{{ chatInfo.message }}</div>
	            </div>
          </div>
    `
};

export { ChatListElement };