const LocationMessage = {
	props: {
		content: Object,
	},
	setup(props) {
		let linkMap = `https://yandex.ru/maps/?pt=${props.content.lon},${props.content.lat}&z=17&l=map`;
		let linkImage = `https://static-maps.yandex.ru/1.x/?l=map&pt=${props.content.lon},${props.content.lat},comma&z=14&size=252,190`

		return { linkMap, linkImage }
	},
	// language=Vue
	template: `
	  <div class="avito-chat-message-location">
		<a :href="linkMap" target="_blank" class="avito-chat-message-location-link" :title="content.title">
          <div v-if="linkImage" class="avito-chat-message-image">
            <div :class="'avito-chat-message-image-preview-wrap'">
              <div class="avito-chat-message-image-preview"
                   :style="'background-image: url(' + linkImage +');'"
              ></div>
            </div>
          </div>
		  <div :class="'avito-chat-message-location-text' + (linkMap ? ' image' : '')">{{ content.title }}</div>
		</a>
	  </div>
	`
}

export { LocationMessage };