<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 */

//one css for all system.auth.* forms
$APPLICATION->SetAdditionalCSS("/bitrix/css/main/system.auth/flat/style.css");
?>
<style>
	.bx-filter-param-text {
		margin-left: 5px;
	}
</style>

<div class="bx-authform">

<?
if(!empty($arParams["~AUTH_RESULT"])):
	$text = str_replace(array("<br>", "<br />"), "\n", $arParams["~AUTH_RESULT"]["MESSAGE"]);
?>
	<div class="alert alert-danger"><?=nl2br(htmlspecialcharsbx($text))?></div>
<?endif?>

<?
if($arResult['ERROR_MESSAGE'] <> ''):
	$text = str_replace(array("<br>", "<br />"), "\n", $arResult['ERROR_MESSAGE']);
?>
	<div class="alert alert-danger"><?=nl2br(htmlspecialcharsbx($text))?></div>
<?endif?>

	<h3 class="bx-title"><?=GetMessage("AUTH_PLEASE_AUTH")?></h3>

<?
$phoneAuthOn = false;
if (\Bitrix\Main\Loader::includeModule('prime.phoneauth')) {
	$phoneAuthOn = \Prime\PhoneAuth\Config::isEnabled();
}
$authTabsCount = 2 + ($phoneAuthOn ? 1 : 0);
?>

<div class="auth">
<div class="prime-phoneauth-tabs prime-phoneauth-tabs--<?= (int)$authTabsCount ?>" role="tablist">
	<button type="button" class="is-active" data-tab="password" role="tab">По логину</button>
	<? if ($phoneAuthOn): ?>
	<button type="button" data-tab="phone" role="tab">По телефону</button>
	<? endif ?>
	<button type="button" data-tab="social" role="tab">Соцсети</button>
</div>

<div class="prime-phoneauth-panel is-active" data-panel="password">

	<form name="form_auth" method="post" target="_top" action="<?=$arResult["AUTH_URL"]?>">

		<input type="hidden" name="AUTH_FORM" value="Y" />
		<input type="hidden" name="TYPE" value="AUTH" />
<?if ($arResult["BACKURL"] <> ''):?>
		<input type="hidden" name="backurl" value="<?=$arResult["BACKURL"]?>" />
<?endif?>
<?foreach ($arResult["POST"] as $key => $value):?>
		<input type="hidden" name="<?=$key?>" value="<?=$value?>" />
<?endforeach?>

		<div class="bx-authform-formgroup-container">
			<div class="bx-authform-label-container"><?=GetMessage("AUTH_LOGIN")?></div>
			<div class="bx-authform-input-container">
				<input type="text" name="USER_LOGIN" maxlength="255" value="<?=$arResult["LAST_LOGIN"]?>" />
			</div>
		</div>
		<div class="bx-authform-formgroup-container">
			<div class="bx-authform-label-container"><?=GetMessage("AUTH_PASSWORD")?></div>
			<div class="bx-authform-input-container">
<?if($arResult["SECURE_AUTH"]):?>
				<div class="bx-authform-psw-protected" id="bx_auth_secure" style="display:none"><div class="bx-authform-psw-protected-desc"><span></span><?echo GetMessage("AUTH_SECURE_NOTE")?></div></div>

<script type="text/javascript">
document.getElementById('bx_auth_secure').style.display = '';
</script>
<?endif?>
				<input type="password" name="USER_PASSWORD" maxlength="255" autocomplete="off" />
			</div>
		</div>

<?if($arResult["CAPTCHA_CODE"]):?>
		<input type="hidden" name="captcha_sid" value="<?echo $arResult["CAPTCHA_CODE"]?>" />

		<div class="bx-authform-formgroup-container dbg_captha">
			<div class="bx-authform-label-container">
				<?echo GetMessage("AUTH_CAPTCHA_PROMT")?>
			</div>
			<div class="bx-captcha"><img src="/bitrix/tools/captcha.php?captcha_sid=<?echo $arResult["CAPTCHA_CODE"]?>" width="180" height="40" alt="CAPTCHA" /></div>
			<div class="bx-authform-input-container">
				<input type="text" name="captcha_word" maxlength="50" value="" autocomplete="off" />
			</div>
		</div>
<?endif;?>

<?if ($arResult["STORE_PASSWORD"] == "Y"):?>
		<div class="bx-authform-formgroup-container">
			<div class="checkbox">
				<label class="bx-filter-param-label">
					<input type="checkbox" id="USER_REMEMBER" name="USER_REMEMBER" value="Y" />
					<span class="bx-filter-param-text"><?=GetMessage("AUTH_REMEMBER_ME")?></span>
				</label>
			</div>
		</div>
<?endif?>
		<div class="bx-authform-formgroup-container">
			<input type="submit" class="btn btn-primary" name="Login" value="<?=GetMessage("AUTH_AUTHORIZE")?>" />
		</div>
	</form>

</div>
<? if ($phoneAuthOn): ?>
<div class="prime-phoneauth-panel" data-panel="phone">
	<div class="prime-phoneauth-error" style="display:none"></div>
	<form class="prime-phoneauth-phone-form" action="#" method="post">
		<div class="bx-authform-formgroup-container">
			<div class="bx-authform-label-container">Телефон</div>
			<div class="bx-authform-input-container">
				<input type="text" name="PHONE" class="ru_phone_check" placeholder="+7-___-___-__-__" autocomplete="tel" inputmode="tel">
			</div>
		</div>
		<div class="bx-authform-formgroup-container">
			<input type="submit" class="btn btn-primary" value="Продолжить">
		</div>
	</form>
	<div class="prime-phoneauth-wait" style="display:none">
		<p data-role="message"></p>
		<p>Звоните с номера <strong data-role="from-phone"></strong></p>
		<p>Звоните на телефон: <a class="prime-phoneauth-number" data-role="call-number"></a></p>
		<ol class="prime-phoneauth-steps">
			<li>Наберите номер с того телефона, который указали</li>
			<li>Звонок сбросится сам — страница войдёт в аккаунт</li>
		</ol>
		<button type="button" class="prime-phoneauth-test" data-role="test">Я позвонил (тест)</button>
		<button type="button" class="prime-phoneauth-back" data-role="back">Другой способ входа</button>
	</div>
</div>
<? endif; ?>

<div class="prime-phoneauth-panel" data-panel="social">
	<div class="prime-auth-social prime-auth-social--dev">
		<div class="prime-auth-social__head">
			<span class="prime-auth-social__badge">В разработке</span>
		</div>
		<div class="prime-auth-social__icons">
<?if($arResult["AUTH_SERVICES"]):?>
<?
$APPLICATION->IncludeComponent("bitrix:socserv.auth.form",
	"flat",
	array(
		"AUTH_SERVICES" => $arResult["AUTH_SERVICES"],
		"AUTH_URL" => $arResult["AUTH_URL"],
		"POST" => $arResult["POST"],
	),
	$component,
	array("HIDE_ICONS"=>"Y")
);
?>
<?else:?>
			<div class="prime-auth-social__fallback">
				<span class="prime-auth-social__fallback-icon vk" title="ВКонтакте — в разработке"></span>
				<span class="prime-auth-social__fallback-icon ok" title="Одноклассники — в разработке"></span>
				<span class="prime-auth-social__fallback-icon go" title="Google — в разработке"></span>
				<span class="prime-auth-social__fallback-icon fb" title="Facebook — в разработке"></span>
			</div>
<?endif?>
		</div>
		<p class="prime-auth-social__note">Скоро можно будет войти через ВКонтакте и другие сервисы.</p>
	</div>
</div>
</div>

<?if ($arParams["NOT_SHOW_LINKS"] != "Y"):?>
	<hr class="bxe-light">

	<noindex>
		<div class="bx-authform-link-container">
			<a href="<?=$arResult["AUTH_FORGOT_PASSWORD_URL"]?>" rel="nofollow"><b><?=GetMessage("AUTH_FORGOT_PASSWORD_2")?></b></a>
		</div>
	</noindex>
<?endif?>

<?if($arParams["NOT_SHOW_LINKS"] != "Y" && $arResult["NEW_USER_REGISTRATION"] == "Y"):?>
	<noindex>
		<div class="bx-authform-link-container">
			<?=GetMessage("AUTH_FIRST_ONE")?><br />
			<a href="<?=$arResult["AUTH_REGISTER_URL"]?>" rel="nofollow"><b><?=GetMessage("AUTH_REGISTER")?></b></a>
		</div>
	</noindex>
<?endif?>

</div>

<script type="text/javascript">
<?if ($arResult["LAST_LOGIN"] <> ''):?>
try{document.form_auth.USER_PASSWORD.focus();}catch(e){}
<?else:?>
try{document.form_auth.USER_LOGIN.focus();}catch(e){}
<?endif?>
</script>

