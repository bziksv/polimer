import {Ajax} from "../../ajax";
import {store} from "../../store";

const Actions = {
    props: {
        message: Object,
    },
	setup() {
		return { store };
	},
	computed: {
		localize() {
			return {
				TEXT: BX.message("MESSAGE_DELETED"),
				TITLE: BX.message("MESSAGE_DELETE_TITLE"),
			}
		}
	},
    methods: {
        deleteMessage: function () {
            Ajax.deleteMessage(this.message.id).then(() => {
                this.message.content.text = this.localize.TEXT;
                this.message.type = 'deleted';
                this.message.deletable = false;
	            store.messageMenuId = null;
            });
        },
    },
	// language=Vue
    template: `
        <div class="avito-chat-context-menu">
            <button type="button" class="avito-chat-menu-item" @click="deleteMessage">
              <span>{{ localize.TITLE }}</span>
            </button>
        </div>
    `,
}

export { Actions };