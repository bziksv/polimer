<?
define("NEED_AUTH", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

if (is_string($_REQUEST["backurl"]) && mb_strpos($_REQUEST["backurl"], "/") === 0)
{
	LocalRedirect($_REQUEST["backurl"]);
}

$APPLICATION->SetTitle("Autorisierung");
?>
<p>Sie haben sich erfolgreich registriert und angemeldet.</p>
 
<p>Benutzen Sie die  administrative Toolbar im oberen Teil des Bildschirms für einen schnellen Zugriff auf die Funktionen der Strukturverwaltung  und Content Management der Website. Die Tasten der oberen Toolbar unterscheiden sich in Abhängigkeit von den Websitebereichen.  Vorgesehen sind bestimmte Aktivitäten zur Verwaltung von statischen Inhalten der Seite, dynamischen Veröffentlichungen (Nachrichten, Katalog, Fotogalerie) usw.</p>
 
<p><a href="<?=SITE_DIR?>">Zurück zur Homepage</a></p>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>