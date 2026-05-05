<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Fragen");
?>
<p><?$APPLICATION->IncludeComponent("bitrix:main.feedback", "template", array(
	"USE_CAPTCHA" => "Y",
	"OK_TEXT" => "Wir danken Ihnen für Ihre Frage. In der nächsten Zeit werden wir mit Ihnen Kontakt aufnehmen.",
	"EMAIL_TO" => "",
	"REQUIRED_FIELDS" => array(
		0 => "NAME",
		1 => "EMAIL",
		2 => "MESSAGE",
	),
	"EVENT_MESSAGE_ID" => array(
	)
	),
	false
);?></p>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>