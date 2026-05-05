import {store} from "../store";

const ScrollButton = {
	methods: {
		scrollBottom: function() {
			const container = document.querySelector('.avito-chat-messages');
			container.scrollTo({ top: 0, behavior: 'smooth' });
		}
	},
	setup() {
		return { store }
	},
	// language=Vue
	template: `
		<div :class="'avito-chat-message-history-scroll-button' + (!store.scrollButton ? ' hide' : '')" @click="scrollBottom">
			<div class="avito-chat-message-history-scroll-button-content">
				<svg viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"
					class="avito-chat-messages-history-scroll-button-icon"
					style="width: 20px; height: 20px;">
				<path
					d="M13.138 5.529c.26.26.26.682 0 .943L8.47 11.138a.667.667 0 01-.943 0L2.862 6.472a.667.667 0 11.942-.943L8 9.724l4.195-4.195c.26-.26.682-.26.943 0z"
					fill="currentColor"></path>
				</svg>
			  	<div :class="'avito-chat-messages-history-scroll-button-circle' + (store.scrollButtonCircle ? ' show' : '')"></div>
			</div>
		</div>
	`
};

export {ScrollButton};
