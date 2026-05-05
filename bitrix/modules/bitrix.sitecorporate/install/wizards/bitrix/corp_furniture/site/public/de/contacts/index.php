<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Kontakte");
?>
<p>Haben Sie Fragen? Wenden Sie Sich an uns: ob Telefonanruf, E-Mail-Anfrage oder persönlicher Besuch bei uns im Büro - wir werden uns freuen, Sie zu beraten.</p>

<h2>Telefon</h2>

<ul> 
	<li>Telefon/Fax:
		<ul> 
			<li><b>(0911) 911-0000</b></li>
		</ul>
	</li>
 
	<li>Telefon:
		<ul> 
			<li><b>(0911) 911-0001</b></li>
			<li><b>(0911) 911-0002</b></li>
		</ul>
	</li>
</ul>

<h2>Email</h2>

<ul> 
  <li><a href="mailto:postmaster@mustermoebelplus.de">postmaster@mustermoebelplus.de</a></li>
</ul>

<h2>Zentralstelle in Nürnberg</h2>

<p><?$APPLICATION->IncludeComponent("bitrix:map.google.view", ".default", array(
	"INIT_MAP_TYPE" => "ROADMAP",
	"MAP_DATA" => "a:4:{s:10:\"google_lat\";d:49.45050872173893;s:10:\"google_lon\";d:11.080484390258789;s:12:\"google_scale\";i:15;s:10:\"PLACEMARKS\";a:0:{}}",
	"MAP_WIDTH" => "600",
	"MAP_HEIGHT" => "500",
	"CONTROLS" => array(
		0 => "SCALELINE",
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