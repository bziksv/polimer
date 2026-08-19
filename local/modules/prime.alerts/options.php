<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Prime\Alerts\EmailPolicy;
use Prime\Alerts\ProfileBanner;
use Prime\Alerts\Theme;

/** @global CMain $APPLICATION */
/** @global CUser $USER */

$moduleId = 'prime.alerts';

Loc::loadMessages(__FILE__);

if (!$USER->IsAdmin()) {
	return;
}

Loader::includeModule($moduleId);

$note = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid()) {
	$boolKeys = [
		'enabled',
		'policy_enabled',
		'policy_register',
		'policy_order',
		'notice_everywhere',
		'profile_banner',
	];
	foreach ($boolKeys as $key) {
		Option::set($moduleId, $key, !empty($_POST[$key]) && $_POST[$key] === 'Y' ? 'Y' : 'N');
	}

	$strKeys = [
		'support_email',
		'support_phone',
		'extra_domains',
		'notice_title_signup',
		'notice_title_checkout',
		'error_text_signup',
		'error_text_checkout',
		'profile_banner_title',
		'color_scheme',
	];
	foreach ($strKeys as $key) {
		$value = trim((string)($_POST[$key] ?? ''));
		if ($key === 'color_scheme') {
			$value = Theme::normalize($value);
		}
		Option::set($moduleId, $key, $value);
	}

	// HTML bodies — keep markup, trim outer whitespace only
	Option::set($moduleId, 'notice_text_signup', trim((string)($_POST['notice_text_signup'] ?? '')));
	Option::set($moduleId, 'notice_text_checkout', trim((string)($_POST['notice_text_checkout'] ?? '')));
	Option::set($moduleId, 'profile_banner_text', trim((string)($_POST['profile_banner_text'] ?? '')));

	$note = Loc::getMessage('PRIME_ALERTS_SAVED');
}

$aTabs = [
	[
		'DIV' => 'edit1',
		'TAB' => Loc::getMessage('PRIME_ALERTS_TAB'),
		'TITLE' => Loc::getMessage('PRIME_ALERTS_TAB_TITLE'),
	],
];

$tabControl = new CAdminTabControl('primeAlertsTabControl', $aTabs);

if ($note !== '') {
	CAdminMessage::ShowNote($note);
}

$get = static function (string $name, string $default = '') use ($moduleId): string {
	return (string) Option::get($moduleId, $name, $default);
};

$checked = static function (string $name, string $default = 'N') use ($get): string {
	return $get($name, $default) === 'Y' ? ' checked' : '';
};

$noticeTitleSignup = $get('notice_title_signup');
$noticeTextSignup = $get('notice_text_signup');
$noticeTitleCheckout = $get('notice_title_checkout');
$noticeTextCheckout = $get('notice_text_checkout');
$errorSignup = $get('error_text_signup');
$errorCheckout = $get('error_text_checkout');
$profileBannerTitle = $get('profile_banner_title');
$profileBannerText = $get('profile_banner_text');

if ($noticeTitleSignup === '') {
	$noticeTitleSignup = EmailPolicy::getDefaultNoticeTitle('signup');
}
if ($noticeTextSignup === '') {
	$noticeTextSignup = EmailPolicy::getDefaultNoticeText('signup');
}
if ($noticeTitleCheckout === '') {
	$noticeTitleCheckout = EmailPolicy::getDefaultNoticeTitle('checkout');
}
if ($noticeTextCheckout === '') {
	$noticeTextCheckout = EmailPolicy::getDefaultNoticeText('checkout');
}
if ($errorSignup === '') {
	$errorSignup = EmailPolicy::getDefaultErrorText('signup');
}
if ($errorCheckout === '') {
	$errorCheckout = EmailPolicy::getDefaultErrorText('checkout');
}
if ($profileBannerTitle === '') {
	$profileBannerTitle = ProfileBanner::getDefaultTitle();
}
if ($profileBannerText === '') {
	$profileBannerText = ProfileBanner::getDefaultText();
}
?>
<form method="post" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($moduleId) ?>&lang=<?= LANGUAGE_ID ?>">
	<?= bitrix_sessid_post() ?>
	<?php $tabControl->Begin(); ?>
	<?php $tabControl->BeginNextTab(); ?>

	<tr>
		<td><?= Loc::getMessage('PRIME_ALERTS_ENABLED') ?>:</td>
		<td width="60%"><input type="checkbox" name="enabled" value="Y"<?= $checked('enabled', 'Y') ?>></td>
	</tr>
	<tr>
		<td valign="top">
			<?= Loc::getMessage('PRIME_ALERTS_COLOR_SCHEME') ?>:<br>
			<small><?= Loc::getMessage('PRIME_ALERTS_COLOR_SCHEME_HINT') ?></small>
		</td>
		<td>
			<style>
				.pa-themes{
					display:grid;
					grid-template-columns:repeat(3, minmax(200px, 1fr));
					gap:12px;
					max-width:720px;
				}
				.pa-theme{display:block;width:100%;cursor:pointer;margin:0;}
				.pa-theme input{position:absolute;opacity:0;pointer-events:none;}
				.pa-theme__card{
					display:flex;flex-direction:column;height:100%;
					border:2px solid #ddd;border-radius:8px;overflow:hidden;
					background:#fff;
				}
				.pa-theme input:checked + .pa-theme__card{border-color:#2675d7;box-shadow:0 0 0 1px #2675d7;}
				.pa-theme__preview{
					height:52px;display:flex;align-items:center;justify-content:center;gap:8px;
					background:var(--bg);border-bottom:1px solid var(--border);
				}
				.pa-theme__dot{width:22px;height:22px;border-radius:50%;background:var(--accent);color:#fff;font-weight:700;font-size:14px;line-height:22px;text-align:center;}
				.pa-theme__bar{width:72px;height:10px;border-radius:2px;border:2px solid var(--accent);background:#fff;}
				.pa-theme__meta{
					padding:10px 12px 12px;
					text-align:center;
					flex:1 1 auto;
				}
				.pa-theme__name{display:block;font-weight:700;font-size:13px;color:#333;text-align:center;}
				.pa-theme__hint{display:block;font-size:11px;color:#777;margin-top:4px;line-height:1.35;text-align:center;}
			</style>
			<div class="pa-themes">
				<?php
				$currentTheme = Theme::normalize($get('color_scheme', Theme::DEFAULT));
				foreach (Theme::all() as $id => $theme):
				?>
				<label class="pa-theme">
					<input type="radio" name="color_scheme" value="<?= htmlspecialcharsbx($id) ?>"<?= $currentTheme === $id ? ' checked' : '' ?>>
					<span class="pa-theme__card" style="--accent:<?= htmlspecialcharsbx($theme['accent']) ?>;--bg:<?= htmlspecialcharsbx($theme['bg']) ?>;--border:<?= htmlspecialcharsbx($theme['border']) ?>">
						<span class="pa-theme__preview">
							<span class="pa-theme__dot">!</span>
							<span class="pa-theme__bar"></span>
						</span>
						<span class="pa-theme__meta">
							<span class="pa-theme__name"><?= htmlspecialcharsbx($theme['title']) ?></span>
							<span class="pa-theme__hint"><?= htmlspecialcharsbx($theme['hint']) ?></span>
						</span>
					</span>
				</label>
				<?php endforeach; ?>
			</div>
		</td>
	</tr>

	<tr class="heading"><td colspan="2">Политика e-mail</td></tr>
	<tr>
		<td valign="top">
			<?= Loc::getMessage('PRIME_ALERTS_POLICY_ENABLED') ?>:<br>
			<small><?= Loc::getMessage('PRIME_ALERTS_POLICY_ENABLED_HINT') ?></small>
		</td>
		<td valign="top"><input type="checkbox" name="policy_enabled" value="Y"<?= $checked('policy_enabled', 'Y') ?>></td>
	</tr>
	<tr>
		<td><?= Loc::getMessage('PRIME_ALERTS_POLICY_REGISTER') ?>:</td>
		<td><input type="checkbox" name="policy_register" value="Y"<?= $checked('policy_register', 'Y') ?>></td>
	</tr>
	<tr>
		<td><?= Loc::getMessage('PRIME_ALERTS_POLICY_ORDER') ?>:</td>
		<td><input type="checkbox" name="policy_order" value="Y"<?= $checked('policy_order', 'Y') ?>></td>
	</tr>
	<tr>
		<td valign="top">
			<?= Loc::getMessage('PRIME_ALERTS_NOTICE_EVERYWHERE') ?>:<br>
			<small><?= Loc::getMessage('PRIME_ALERTS_NOTICE_EVERYWHERE_HINT') ?></small>
		</td>
		<td valign="top"><input type="checkbox" name="notice_everywhere" value="Y"<?= $checked('notice_everywhere', 'N') ?>></td>
	</tr>
	<tr>
		<td><?= Loc::getMessage('PRIME_ALERTS_SUPPORT_EMAIL') ?>:</td>
		<td><input type="text" name="support_email" size="40" value="<?= htmlspecialcharsbx($get('support_email', 'info@vrn-ehk.ru')) ?>"></td>
	</tr>
	<tr>
		<td><?= Loc::getMessage('PRIME_ALERTS_SUPPORT_PHONE') ?>:</td>
		<td><input type="text" name="support_phone" size="40" value="<?= htmlspecialcharsbx($get('support_phone', '8-800-755-07-76')) ?>"></td>
	</tr>
	<tr>
		<td valign="top">
			<?= Loc::getMessage('PRIME_ALERTS_EXTRA_DOMAINS') ?>:<br>
			<small><?= Loc::getMessage('PRIME_ALERTS_EXTRA_DOMAINS_HINT') ?></small>
		</td>
		<td valign="top">
			<input type="text" name="extra_domains" size="50" value="<?= htmlspecialcharsbx($get('extra_domains')) ?>">
		</td>
	</tr>

	<tr class="heading"><td colspan="2"><?= Loc::getMessage('PRIME_ALERTS_PROFILE_BANNER_HEAD') ?></td></tr>
	<tr>
		<td valign="top">
			<?= Loc::getMessage('PRIME_ALERTS_PROFILE_BANNER') ?>:<br>
			<small><?= Loc::getMessage('PRIME_ALERTS_PROFILE_BANNER_HINT') ?></small>
		</td>
		<td valign="top"><input type="checkbox" name="profile_banner" value="Y"<?= $checked('profile_banner', 'Y') ?>></td>
	</tr>
	<tr>
		<td valign="top"><?= Loc::getMessage('PRIME_ALERTS_PROFILE_BANNER_TITLE') ?>:</td>
		<td><input type="text" name="profile_banner_title" size="70" value="<?= htmlspecialcharsbx($profileBannerTitle) ?>"></td>
	</tr>
	<tr>
		<td valign="top"><?= Loc::getMessage('PRIME_ALERTS_PROFILE_BANNER_TEXT') ?>:</td>
		<td><textarea name="profile_banner_text" cols="70" rows="8"><?= htmlspecialcharsbx($profileBannerText) ?></textarea></td>
	</tr>

	<tr class="heading"><td colspan="2"><?= Loc::getMessage('PRIME_ALERTS_TEXTS') ?></td></tr>
	<tr>
		<td colspan="2"><small><?= Loc::getMessage('PRIME_ALERTS_MACROS_HINT') ?></small></td>
	</tr>
	<tr>
		<td valign="top"><?= Loc::getMessage('PRIME_ALERTS_NOTICE_TITLE_SIGNUP') ?>:</td>
		<td><input type="text" name="notice_title_signup" size="70" value="<?= htmlspecialcharsbx($noticeTitleSignup) ?>"></td>
	</tr>
	<tr>
		<td valign="top"><?= Loc::getMessage('PRIME_ALERTS_NOTICE_TEXT_SIGNUP') ?>:</td>
		<td><textarea name="notice_text_signup" cols="70" rows="10"><?= htmlspecialcharsbx($noticeTextSignup) ?></textarea></td>
	</tr>
	<tr>
		<td valign="top"><?= Loc::getMessage('PRIME_ALERTS_NOTICE_TITLE_CHECKOUT') ?>:</td>
		<td><input type="text" name="notice_title_checkout" size="70" value="<?= htmlspecialcharsbx($noticeTitleCheckout) ?>"></td>
	</tr>
	<tr>
		<td valign="top"><?= Loc::getMessage('PRIME_ALERTS_NOTICE_TEXT_CHECKOUT') ?>:</td>
		<td><textarea name="notice_text_checkout" cols="70" rows="10"><?= htmlspecialcharsbx($noticeTextCheckout) ?></textarea></td>
	</tr>
	<tr>
		<td valign="top"><?= Loc::getMessage('PRIME_ALERTS_ERROR_SIGNUP') ?>:</td>
		<td><textarea name="error_text_signup" cols="70" rows="3"><?= htmlspecialcharsbx($errorSignup) ?></textarea></td>
	</tr>
	<tr>
		<td valign="top"><?= Loc::getMessage('PRIME_ALERTS_ERROR_CHECKOUT') ?>:</td>
		<td><textarea name="error_text_checkout" cols="70" rows="3"><?= htmlspecialcharsbx($errorCheckout) ?></textarea></td>
	</tr>

	<?php $tabControl->Buttons(); ?>
	<input type="submit" name="save" value="Сохранить" class="adm-btn-save">
	<?php $tabControl->End(); ?>
</form>
