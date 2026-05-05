<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Das Unternehmen");
?><p>Das Unternehmen "Möbel+" gibt es seit 1962, angefangen haben wir als eine kleine Tischlerfirma, heute zählen wir zu den Marktführern im Bereich der Möbelherstellung.
</p><p>
Zur Zeit haben wir insgesamt über 1000 Möbelmodelle, die von unseren Designern fürs Büro oder für den Haushalt entworfen wurden. Dennoch versuchen wir unsere Produkte einmalig zu gestalten. Wir stellen Möbel für die Küche oder das Kinderzimmer, für das Wohn- oder Schlafzimmer, für das Bad oder das Büro her. Design und Größe können dabei vom Kunden frei bestimmt werden.</p>
<h3>Produkte</h3>
<?$APPLICATION->IncludeComponent("bitrix:furniture.catalog.index", "", array(
	"IBLOCK_TYPE" => "products",
	"IBLOCK_ID" => "#PRODUCTS_IBLOCK_ID#",
	"IBLOCK_BINDING" => "section",
	"CACHE_TYPE" => "A",
	"CACHE_TIME" => "36000000",
	"CACHE_GROUPS" => "N"
	),
	false
);?>
<h3>Dienstleistungen</h3>
<?$APPLICATION->IncludeComponent("bitrix:furniture.catalog.index", "", array(
	"IBLOCK_TYPE" => "products",
	"IBLOCK_ID" => "#SERVICES_IBLOCK_ID#",
	"IBLOCK_BINDING" => "element",
	"CACHE_TYPE" => "A",
	"CACHE_TIME" => "36000000",
	"CACHE_GROUPS" => "N"
	),
	false
);?>
</p><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>