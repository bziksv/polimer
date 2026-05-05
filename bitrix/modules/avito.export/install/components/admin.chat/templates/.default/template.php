<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

/** @var array $arParams */

use Bitrix\Main;

Main\UI\Extension::load("avitoexport.vendor.vue3");
?>
<div id="avito-chat-app" class="avito-chat-container <?= $arParams['LAYOUT'] ?? ''?>"></div>
<script>
	BX.message({
		MESSAGE_DELETED: '<?=GetMessageJS('AVITO_EXPORT_CHAT_TEMPLATE_MESSAGE_DELETED')?>',
		MESSAGE_DELETE_TITLE: '<?=GetMessageJS('AVITO_EXPORT_CHAT_TEMPLATE_MESSAGE_DELETE_TITLE')?>',
		MESSAGE_TEXTAREA_PLACEHOLDER: '<?=GetMessageJS('AVITO_EXPORT_CHAT_TEMPLATE_MESSAGE_TEXTAREA_PLACEHOLDER')?>',
		EMPTY_MESSAGES_TITLE: '<?=GetMessageJS('AVITO_EXPORT_CHAT_TEMPLATE_EMPTY_MESSAGES_TITLE')?>',
		PRODUCT_NO_TITLE: '<?=GetMessageJS('AVITO_EXPORT_CHAT_TEMPLATE_PRODUCT_NO_TITLE')?>',
	});

    BX.ready(function() {

	   BX.AvitoExport.Chat.App.data = () => {
			return <?= Main\Web\Json::encode([
				'component' => $this->getComponent()->getName(),
				'setupId' => $arParams['SETUP_ID'],
				'userId' => $arParams['USER_ID'],
				'messages' => null,
				'chatId' => Main\Context::getCurrent()->getRequest()->get('chatId'),
                'checkTimestamp' => time(),
			], JSON_INVALID_UTF8_IGNORE) ?>;
		}

	    const app = window.AvitoVue3.createApp(BX.AvitoExport.Chat.App);

        app.mount('#avito-chat-app');
    });
</script>