<?
define("NEED_AUTH", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

if (is_string($_REQUEST["backurl"]) && mb_strpos($_REQUEST["backurl"], "/") === 0)
{
	LocalRedirect($_REQUEST["backurl"]);
}

$APPLICATION->SetTitle("Login");
?>
<p>Sie sind angemeldet und autorisiert.</p>

<p><a href="<?=SITE_DIR?>">Home</a></p>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>