<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Stellenangebot");
?><?$APPLICATION->IncludeComponent("bitrix:furniture.vacancies", ".default", array(
	"IBLOCK_TYPE" => "vacancies",
	"IBLOCK_ID" => "#VACANCIES_IBLOCK_ID#",
	"AJAX_MODE" => "N",
	"AJAX_OPTION_SHADOW" => "Y",
	"AJAX_OPTION_JUMP" => "N",
	"AJAX_OPTION_STYLE" => "Y",
	"AJAX_OPTION_HISTORY" => "N",
	"CACHE_TYPE" => "A",
	"CACHE_TIME" => "36000000",
	"CACHE_GROUPS" => "Y",
	"AJAX_OPTION_ADDITIONAL" => ""
	),
	false
);?>
<br />
<p>Wenn unser Stellenangebot für Sie interessant ist, können Sie uns Ihre Bewerbung per E-Mail senden:<br />
<a href="mailto:postmaster@mustermoebelplus.de">postmaster@mustermoebelplus.de</a>
</p>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>