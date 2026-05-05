const LoaderMessages = {
    // language=Vue
    template: `
		<div class="avito-chat-message-container"
		     v-for="n in 3"
             :class="n % 2 === 0 ? 'avito-chat-message-direction-out' : 'avito-chat-message-direction-in'"
             :key="n">
            <div class="avito-chat-message-loader">
              <div class="avito-chat-name avito-chat-list-loading"></div>
              <div class="avito-chat-title avito-chat-list-loading"></div>
              <div class="avito-chat-last-message avito-chat-list-loading"></div>
            </div>
		</div>
    `
};

export { LoaderMessages };
