import {svg} from "../misc/svg";

const Quote = {
    props: {
        quote: Object,
	    authorName: String,
	    messageType: String,
    },
	setup(props) {
		const quote = props.quote;
		const type = quote.type;
		let text = '';
		let image = null;
		let isPicture = false;
		let icon = svg['quote-' + type];

		if (type === 'text') {
			text = quote.content.text;
		} else if (type === 'link') {
			text = quote.content.link.url;
		} else if (type === 'image') {
			const sizes = quote.content.image.sizes;
			const firstChat = Object.keys(sizes)[0];
			image = sizes[firstChat];
			text = quote.content.text;
			isPicture = true;
		} else if (type === 'item') {
			image = quote.content.item.image_url;
			text = quote.content.item.title;
			isPicture = true;
		} else if (type === 'location') {
			text = quote.content.location.text;
		} else if (type === 'video') {
			text = quote.content.text;
		}

		return { image, text, isPicture, icon }
	},
	methods: {
		scrollToMessage: function(event) {
			const idScrollElement = this.quote.id;
			const scrollElement = document.getElementById(idScrollElement);
			if (scrollElement == null) { return; }
			scrollElement.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
		}
	},
    // language=Vue
    template: `
		<div :class="'avito-chat-message-quote' + (messageType === 'image' ? ' margin' : '')" @click="scrollToMessage">
			<div class="avito-chat-message-quote-quote-verticalLine"></div>
			<div v-if="icon" v-html="icon" class="avito-chat-message-quote-icon"></div>
            <div v-if="isPicture" class="avito-chat-massage-quote-image">
              <div class="avito-chat-massage-quote-image-preview"
                   :style="image !== null ? 'background-image: url(' + image +');' : ''">
              </div>
            </div>
            <div class="avito-chat-massage-quote-info">
              <div class="avito-chat-massage-quote-author_name">{{ authorName }}</div>
              <div class="avito-chat-massage-quote-text">{{ text }}</div>
            </div>
		</div>
    `
};

export { Quote };