import {Ajax} from "../ajax";
import {store} from "../store";
import {svg} from "../misc/svg";

const Input = {
    data() {
		return {
			message: '',
		}
	},
	computed: {
		localize() {
			return {
				PLACEHOLDER: BX.message("MESSAGE_TEXTAREA_PLACEHOLDER"),
			}
		}
	},
	methods: {
		pressNewLine(event) {
			if (!event.shiftKey) {
				event.preventDefault();
			}
		},
		inputText(event) {
			const target = event.currentTarget;
			const currentValue = target.value;

			const styles = window.getComputedStyle(target);
			const lineHeight = parseInt(styles.getPropertyValue('line-height'), 10);
			const minHeight = parseInt(styles.getPropertyValue('min-height'), 10);

			if (currentValue === '')
			{
				target.style.height = styles.getPropertyValue('min-height');
				return;
			}

			const lineBreaks = currentValue.match(/\r?\n/g);
			const lineCount = lineBreaks ? lineBreaks.length : 0;

			target.style.height = (minHeight + lineHeight * lineCount) + 'px';
		},
		sendMessage(event) {
			if (event.shiftKey) { return; }
			if (this.message) {
				Ajax.sendMessage(this.message.trim()).then(data => {
					store.unshiftMessage(data);
				});
				this.message = '';
				const target = event.currentTarget;
				const styles = window.getComputedStyle(target);
				target.style.height = styles.getPropertyValue('min-height');
			}
		}
	},
	setup() {
		const buttonIcon = svg['send-button'];

		return { store, buttonIcon };
	},
	// language=Vue
    template: `
		<div class="avito-chat-input-container">
			<div class="avito-chat-textarea">
				<textarea 
					class="avito-chat-input" 
					type="text"
	                :placeholder="localize.PLACEHOLDER"
					rows="1"
	                maxlength="1000"
					:disabled="!store.chatId || store.isMessagesLoading"
					v-model="message" 
					@keydown.enter="pressNewLine"
					@keyup.enter="sendMessage"
					@input="inputText"
				>
				</textarea>
              	<button v-html="buttonIcon" class="avito-chat-submit-button" :disabled="!store.chatId || store.isMessagesLoading" @click="sendMessage"></button>
	        </div>
		</div>
    `
};

export { Input };
