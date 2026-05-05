const LoaderList = {
	// language=Vue
	template: `
		<div class="avito-chat-list-element" 
		     v-for="(n, index) in 5"
             :key="index">
			<div class="avito-chat-context-preview-root">
				<div class="avito-chat-list-loading"></div>
			</div>
			<div class="avito-chat-channel-preview-info">
				<div class="avito-chat-name avito-chat-list-loading"></div>
				<div class="avito-chat-title avito-chat-list-loading"></div>
				<div class="avito-chat-last-message avito-chat-list-loading"></div>
			</div>
		</div>
    `
};

export { LoaderList };
