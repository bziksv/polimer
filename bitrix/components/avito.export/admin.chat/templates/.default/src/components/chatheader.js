import {store} from "../store";

const ChatHeader = {
	methods: {
		toggle: function() {
			const list = document.querySelector('.avito-chat-list');
			const area = document.querySelector('.avito-chat-area');
			list.classList.replace('hide', 'show');
			area.classList.replace('show', 'hide');
			store.scrollButton = false;
		},
	},
	setup() {
		return { store, noTitle: BX.message("PRODUCT_NO_TITLE"), }
	},
	// language=Vue
	template: `
      <div class="avito-chat-header">
		<div class="avito-chat-header-wrap">
			<div class="avito-chat-header-button" @click="toggle">
				<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="avito-chat-header-button-icon"
				style="width: 24px; height: 24px;"><path
				d="m7.414 13 5.293 5.293a1 1 0 0 1-1.414 1.414l-7-7a1 1 0 0 1 0-1.414l7-7a1 1 0 1 1 1.414 1.414L7.414 11H19a1 1 0 1 1 0 2H7.414Z"
				fill="currentColor"></path></svg>
			</div>
			<a :href="store.chat.url ? store.chat.url : store.otherUserUrl" target="_blank">
				<div class="avito-chat-header-preview">
					<div :style="store.chat.img ? { backgroundImage: 'url(' + store.chat.img + ')' } : {}"></div>
				</div>
			</a>
			<div class="avito-chat-header-info">
				<a :href="store.otherUserUrl" class="avito-chat-header-link" target="_blank">
					<div class="avito-chat-header-info-name">{{ store.chat.name }}</div>
				</a>
				<a v-if="store.chat.url" :href="store.chat.url" class="avito-chat-header-link" target="_blank">
					<div class="avito-chat-header-info-title">{{ store.chat.title }}</div>
                </a>
				<span v-else class="avito-chat-header-link">
				    <div class="avito-chat-header-info-title --unavailable">{{ noTitle }}</div>
				</span>
			</div>
		</div>
      </div>
	`
};

export { ChatHeader };
