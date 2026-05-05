const EmptyMessages = {
	computed: {
		localize() {
			return {
				TITLE: BX.message("EMPTY_MESSAGES_TITLE"),
			}
		}
	},
	// language=Vue
	template: `
		<div class="avito-chat-empty-messages">
			<div class="avito-chat-empty-messages-placeholder"></div>
			<div class="avito-chat-empty-messages-title">{{localize.TITLE}}</div>
		</div>
    `
};

export { EmptyMessages };
