import {Quote} from "./quote";
import {ImageMessage} from "./message/imagemessage";
import {LinkMessage} from "./message/linkmessage";
import {LocationMessage} from "./message/locationmessage";
import {Actions} from "./message/actions";
import {store} from "../store";

const Message = {
    components: {
        Quote,
	    ImageMessage,
	    LinkMessage,
	    LocationMessage,
	    Actions,
    },
    props: {
        messageData: Object,
	    author: Object,
    },
	methods: {
		contextMenu: function () {
			if (
				store.messageMenuId === this.messageData.id
				&& store.messageMenuId !== null
			) {
				store.messageMenuId = null;
			}
			else {
				store.messageMenuId = this.messageData.id;
			}
		},
	},
    setup() {
        return { store };
    },
    // language=Vue
    template: `
		<div 
			:id="messageData.id"
			:class="'avito-chat-message-container avito-chat-message-direction-' + messageData.direction + ' avito-chat-message-type-' + messageData.type + (messageData.quote ? ' quote' : '')">
			<div v-if="messageData.rendering_avatar && messageData.type === 'system'" class="avito-chat-message-system-avatar-img"></div>
			<div v-else-if="messageData.rendering_avatar && author !== null" class="avito-chat-message-author">
			  <a class="avito-chat-message-author-avatar" :href="author.url" target="_blank" >
			    <img :src=author.avatar :alt="author.name" class="avito-chat-message-author-avatar-img">
			    <span class="avito-chat-message-author-avatar-char" v-if="author.avatarChar">{{author.avatarChar}}</span>
			  </a>
			</div>
			
			<div class="avito-chat-message">
              	<Quote v-if="messageData.quote && author !== null" 
                       :quote="messageData.quote" 
                       :messageType="messageData.type" 
                       :authorName="author.name" 
                />

              	<span v-if="messageData.type === 'text' 
        			|| messageData.type === 'deleted' 
        			|| messageData.type === 'system'
        			|| messageData.type === 'call'
        			|| messageData.type === 'video'"
                      class="avito-chat-message-text">
	              <span v-if="messageData.type === 'call'">&#9990; </span>{{ messageData.content.text }}
                </span>

				<div v-else-if="messageData.type === 'item'" class="avito-chat-message-item">
					<a class="avito-chat-message-item-link" :href="messageData.content.item.item_url" target="_blank">
					  <div class="avito-chat-message-item-picture"
					       :style="'background-image: url(' + messageData.content.item.image_url +')'">
					  </div>
					  <div class="avito-chat-message-item-info">
					    <div class="avito-chat-message-item-info-title">
					      {{ messageData.content.item.title }}
					    </div>
					    <div class="avito-chat-message-item-info-domain">avito.ru</div>
					  </div>
					</a>
				</div>

              	<ImageMessage v-else-if="messageData.type === 'image'" :content="messageData.content.image" :hasQuote="messageData.quote != null"/>
              	<LinkMessage v-else-if="messageData.type === 'link'" :content="messageData.content.link"/>
              	<LocationMessage v-else-if="messageData.type === 'location'" :content="messageData.content.location"/>

				<div :class="[
						messageData.deletable === true ? 'avito-chat-message-status hover' : 'avito-chat-message-status',
						store.messageMenuId !== messageData.id || store.messageMenuId === null ? 'show' : 'hide',
					]">
					<div class="avito-chat-message-status-mark" v-if="messageData.type !== 'deleted' && messageData.direction === 'out'">
					  <div class="mark"></div>
					  <div v-if="messageData.isRead" class="mark"></div>
					</div>
					<div class="time">{{messageData.createdTime}}</div>
				</div>

				<div v-if="messageData.deletable === true" :class="'avito-chat-message-actions ' + (store.messageMenuId === messageData.id  ? 'show' : 'hide')" @click="contextMenu">
					<div class="avito-chat-message-actions-menu">
					  <div class="avito-chat-message-actions-menu-button">
					    <span>
					      <svg viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg"
                               style="width: 24px; height: 24px; color: currentcolor;">
					        	<path fill-rule="evenodd"
                                clip-rule="evenodd"
                                d="M7.4 9a1.6 1.6 0 113.2 0 1.6 1.6 0 01-3.2 0zM2.067 9a1.6 1.6 0 113.2 0 1.6 1.6 0 01-3.2 0zm10.667 0a1.6 1.6 0 113.2 0 1.6 1.6 0 01-3.2 0z"
                                fill="currentColor">
						        </path>
					      </svg>
					    </span>
					  </div>
					</div>
					<Actions v-if="store.messageMenuId === messageData.id" :message="messageData" />
				</div>
			  
            </div>
		</div>
        <div v-if="messageData.delimiter" class="avito-chat-message-time-delimiter">{{ messageData.delimiter }}</div>
    `
};

export { Message };