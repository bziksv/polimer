const ImageMessage = {
	props: {
		content: Object,
		hasQuote: Boolean,
	},
	setup(props){
		const keys = Object.keys(props.content.sizes);
		const lastIndex = keys.length - 1;
		const image = props.content.sizes[keys[lastIndex]];

		return {
			image
		}
	},
	methods: {
		imageOpen() {
			const link = this.content.sizes[Object.keys(this.content.sizes)[0]];
			window.open(link);
		},
	},
	// language=Vue
	template: `
		<div class="avito-chat-message-image" @click="imageOpen">
			<div :class="'avito-chat-message-image-preview-wrap' + (hasQuote ? ' quote' : '')">
			  	<div class="avito-chat-message-image-preview" 
			         :style="'background-image: linear-gradient(rgba(0, 0, 0, 0.05), rgba(0, 0, 0, 0.05)), url(' + image +');'"
			    ></div>
			</div>
		</div>
	`
}

export { ImageMessage };