
const LinkMessage = {
	props: {
		content: Object,
	},
	setup(props){
		let previewImage = null;
		const content = props.content;

		if (content.preview && content.preview.images)
		{
			const keys = Object.keys(content.preview.images);
			previewImage = content.preview.images[keys[0]];
		}

		return { previewImage }
	},
	// language=Vue
	template: `
      	<div v-if="content.preview" class="avito-chat-message-link">
			<a class="avito-chat-message-link-link" :href="content.url" target="_blank">
			  	<div class="avito-chat-message-link-picture" 
			         :style="previewImage !== null ? 'background-image: url(' + previewImage +')' : ''">
			    </div>
			  	<div class="avito-chat-message-link-info">
				  	<div class="avito-chat-message-link-info-title">
					  	{{ content.preview.title }}
				    </div>
	                <div class="avito-chat-message-link-info-domain">{{ content.preview.domain }}</div>
			    </div>
			</a>
		</div>
        <div v-else class="avito-chat-message-text-link">{{ content.text }}</div>
	`
}

export { LinkMessage };