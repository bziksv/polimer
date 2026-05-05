<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Karriere und Jobs");
?>
<?$APPLICATION->IncludeComponent("bitrix:furniture.vacancies", ".default", array(
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
<b>Kontakt</b>
<p>Weitere Information:<br />
Musterbank  AG<br />
Finanzcenter Dusseldorf<br />
Personalmanagement<br />
Konigsalle 22, Dusseldorf</p>

Tel. 0123 444-55667 <br />         
E-Mail: <a href="mailto:pm@Musterbank.de">pm@Musterbank.de</a> 
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>