const LoaderScroll = {
	mounted() {
		const element = document.querySelector('.avito-chat-preloader-circle-row');
		element.scrollIntoView({ block: 'nearest', inline: 'nearest' });
	},
	// language=Vue
	template: `
	  <div class="avito-chat-preloader-circle-row">
      	<div class="avito-chat-preloader-circle avito-chat-preloader-circle-rotate"></div>
      </div>
    `
};

export { LoaderScroll };
