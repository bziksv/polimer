<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
	die();
}

/**
 * @var array $arParams
 * @global CMain $APPLICATION
 */

$APPLICATION->SetAdditionalCSS('/bitrix/components/bitrix/socserv.auth.form/templates/flat/style.css');

CUtil::InitJSCore(['popup']);

$arAuthServices = $arPost = [];
if (is_array($arParams['~AUTH_SERVICES'])) {
	$arAuthServices = $arParams['~AUTH_SERVICES'];
}
if (is_array($arParams['~POST'])) {
	$arPost = $arParams['~POST'];
}

$hiddens = '';
foreach ($arPost as $key => $value) {
	if (!preg_match('|OPENID_IDENTITY|', $key)) {
		$hiddens .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />' . "\n";
	}
}
?>
<div class="bx-authform-social bx-authform-social--dev">
	<ul>
<?
foreach ($arAuthServices as $service):
?>
		<li>
			<span
				id="bx_socserv_icon_<?=$service['ID']?>"
				class="<?= \Bitrix\Main\Text\HtmlFilter::encode($service['ICON']) ?> bx-authform-social-icon"
				title="<?= \Bitrix\Main\Text\HtmlFilter::encode($service['NAME']) ?> — в разработке"
				role="img"
				aria-label="<?= \Bitrix\Main\Text\HtmlFilter::encode($service['NAME']) ?>"
			></span>
	<?if (empty($service['ONCLICK']) && !empty($service['FORM_HTML'])):?>
			<div id="bx_socserv_form_<?=$service['ID']?>" class="bx-authform-social-popup" hidden>
				<form action="<?=$arParams['AUTH_URL']?>" method="post">
					<?=$service['FORM_HTML']?>
					<?=$hiddens?>
					<input type="hidden" name="auth_service_id" value="<?=$service['ID']?>" />
				</form>
			</div>
	<?endif?>
		</li>
<?
endforeach;
?>
	</ul>
</div>
