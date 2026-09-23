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

$labelByIcon = [
	'yandex' => 'Яндекс',
	'vkontakte' => 'ВКонтакте',
	'odnoklassniki' => 'Одноклассники',
	'google' => 'Google',
	'facebook' => 'Facebook',
	'mailru2' => 'Mail.ru',
	'mymailru' => 'Mail.ru',
];

$hiddens = '';
foreach ($arPost as $key => $value) {
	if (!preg_match('|OPENID_IDENTITY|', $key)) {
		$hiddens .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />' . "\n";
	}
}
?>
<script>
function BxSocServPopup(id)
{
	var content = BX('bx_socserv_form_' + id);
	if (content)
	{
		var popup = BX.PopupWindowManager.create('socServPopup' + id, BX('bx_socserv_icon_' + id), {
			autoHide: true,
			closeByEsc: true,
			angle: {offset: 24},
			content: content,
			offsetTop: 3
		});

		popup.show();

		var input = BX.findChild(content, {'tag': 'input', 'attribute': {'type': 'text'}}, true);
		if (input)
		{
			input.focus();
		}

		var button = BX.findChild(content, {'tag': 'input', 'attribute': {'type': 'submit'}}, true);
		if (button)
		{
			button.className = 'btn btn-primary';
		}
	}
}
</script>

<div class="bx-authform-social bx-authform-social--labeled">
	<ul>
<?
foreach ($arAuthServices as $service):
	$onclick = (!empty($service['ONCLICK']) ? $service['ONCLICK'] : "BxSocServPopup('" . $service['ID'] . "')");
	$icon = (string)($service['ICON'] ?? '');
	$label = $labelByIcon[$icon] ?? (string)($service['NAME'] ?? $service['ID']);
?>
		<li>
			<a
				id="bx_socserv_icon_<?=$service['ID']?>"
				class="bx-authform-social-btn"
				href="javascript:void(0)"
				onclick="<?= \Bitrix\Main\Text\HtmlFilter::encode($onclick) ?>"
				title="<?= \Bitrix\Main\Text\HtmlFilter::encode('Войти через ' . $label) ?>"
			>
				<span class="<?= \Bitrix\Main\Text\HtmlFilter::encode($icon) ?> bx-authform-social-icon" aria-hidden="true"></span>
				<span class="bx-authform-social-label"><?= \Bitrix\Main\Text\HtmlFilter::encode($label) ?></span>
			</a>
	<?if (empty($service['ONCLICK']) && !empty($service['FORM_HTML'])):?>
			<div id="bx_socserv_form_<?=$service['ID']?>" class="bx-authform-social-popup">
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
