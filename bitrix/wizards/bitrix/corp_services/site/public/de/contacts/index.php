<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Kontakte");
?>
<p>Wenden Sie Sich an die Fachleute unserer Bank und Sie werden unverzüglich und professionell beraten.</p>

<p>Sie können Telefon - oder Emailkontakt mit unserer Bank aufnehmen, oder aber einen Termin in unserem Büro ausmachen. Wir würden uns in jedem Fall freuen, Ihnen zu helfen und Ihre Fragen zu beantworten. </p>

<br />
  <p>Telefon/Fax: <b>49-1111-2222-3333</b></p>
  <p>Internet: <a href="http://www.musterbank.de">www.musterbank.de</a></p>
  <p>E-Mail Privatkunden: <a href="mailto:privatkunden@musterbank.de">privatkunden@musterbank.de</a></p>
  <p>E-Mail Firmenkunden: <a href="mailto:firmenkunden@musterbank.de">firmenkunden@musterbank.de</a></p>


 
<h2>Die Bankzentrale in Düsseldorf</h2>

<p><?$APPLICATION->IncludeComponent("bitrix:map.google.view", ".default", array(
	"KEY" => "ABQIAAAAOSNukcWVjXaGbDo6npRDcxS1yLxjXbTnpHav15fICwCqFS-qhhSby0EyD6rK_qL4vuBSKpeCz5cOjw",
	"INIT_MAP_TYPE" => "NORMAL",
	"MAP_DATA" => "a:4:{s:10:\"google_lat\";d:51.22548537163288;s:10:\"google_lon\";d:6.773285865783691;s:12:\"google_scale\";i:15;s:10:\"PLACEMARKS\";a:1:{i:0;a:3:{s:4:\"TEXT\";i:111;s:3:\"LON\";d:6.773285865783691;s:3:\"LAT\";d:51.22470595990539;}}}",
	"MAP_WIDTH" => "600",
	"MAP_HEIGHT" => "500",
	"CONTROLS" => array(
		0 => "LARGE_MAP_CONTROL",
		1 => "MINIMAP",
		2 => "HTYPECONTROL",
		3 => "SCALELINE",
	),
	"OPTIONS" => array(
		0 => "ENABLE_SCROLL_ZOOM",
		1 => "ENABLE_DBLCLICK_ZOOM",
		2 => "ENABLE_DRAGGING",
	),
	"MAP_ID" => ""
	),
	false
);?></p>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>