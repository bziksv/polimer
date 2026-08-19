<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Prime\PhoneAuth\Config;

/** @global CMain $APPLICATION */
/** @global CUser $USER */

$moduleId = 'prime.phoneauth';

Loc::loadMessages(__FILE__);

if (!$USER->IsAdmin()) {
	return;
}

Loader::includeModule($moduleId);

$note = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid()) {
	foreach (['enabled', 'call_auth_enabled', 'test_confirm'] as $key) {
		Option::set($moduleId, $key, !empty($_POST[$key]) && $_POST[$key] === 'Y' ? 'Y' : 'N');
	}
	Option::set($moduleId, 'verify_number', trim((string)($_POST['verify_number'] ?? '')));
	Option::set($moduleId, 'webhook_ips', trim((string)($_POST['webhook_ips'] ?? '')));

	$postedSecrets = $_POST['webhook_secret'] ?? [];
	if (!is_array($postedSecrets)) {
		$postedSecrets = [];
	}
	foreach (Config::sites() as $site) {
		$lid = $site['lid'];
		if (!empty($_POST['regenerate_secret'][$lid])) {
			Config::generateWebhookSecret($lid);
			continue;
		}
		$posted = trim((string)($postedSecrets[$lid] ?? ''));
		if ($posted !== '') {
			Config::setWebhookSecret($posted, $lid);
		} else {
			Config::getWebhookSecret($lid);
		}
	}

	$note = Loc::getMessage('PRIME_PHONEAUTH_SAVED');
}

$aTabs = [
	[
		'DIV' => 'edit1',
		'TAB' => Loc::getMessage('PRIME_PHONEAUTH_TAB'),
		'TITLE' => Loc::getMessage('PRIME_PHONEAUTH_TAB_TITLE'),
	],
];

$tabControl = new CAdminTabControl('primePhoneauthTabControl', $aTabs);

if ($note !== '') {
	CAdminMessage::ShowNote($note);
}

$get = static function (string $name, string $default = '') use ($moduleId): string {
	return (string) Option::get($moduleId, $name, $default);
};

$checked = static function (string $name, string $default = 'N') use ($get): string {
	return $get($name, $default) === 'Y' ? ' checked' : '';
};

$webhookRows = Config::novofonWebhookUrls();
$currentHost = (string)($_SERVER['HTTP_HOST'] ?? '');

$tabControl->Begin();
?>
<form method="post" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($moduleId) ?>&lang=<?= LANGUAGE_ID ?>">
	<?= bitrix_sessid_post() ?>
	<?php $tabControl->BeginNextTab(); ?>
	<tr>
		<td width="40%"><?= Loc::getMessage('PRIME_PHONEAUTH_ENABLED') ?>:</td>
		<td><input type="checkbox" name="enabled" value="Y"<?= $checked('enabled', 'Y') ?>></td>
	</tr>
	<tr>
		<td valign="top">
			<?= Loc::getMessage('PRIME_PHONEAUTH_CALL_AUTH') ?>:<br>
			<small><?= Loc::getMessage('PRIME_PHONEAUTH_CALL_AUTH_HINT') ?></small>
		</td>
		<td><input type="checkbox" name="call_auth_enabled" value="Y"<?= $checked('call_auth_enabled', 'N') ?>></td>
	</tr>
	<tr>
		<td valign="top">
			<?= Loc::getMessage('PRIME_PHONEAUTH_TEST_CONFIRM') ?>:<br>
			<small><?= Loc::getMessage('PRIME_PHONEAUTH_TEST_CONFIRM_HINT') ?></small>
		</td>
		<td><input type="checkbox" name="test_confirm" value="Y"<?= $checked('test_confirm', 'N') ?>></td>
	</tr>
	<tr>
		<td valign="top">
			<?= Loc::getMessage('PRIME_PHONEAUTH_VERIFY_NUMBER') ?>:<br>
			<small><?= Loc::getMessage('PRIME_PHONEAUTH_VERIFY_NUMBER_HINT') ?></small>
		</td>
		<td><input type="text" name="verify_number" size="40" value="<?= htmlspecialcharsbx($get('verify_number')) ?>"></td>
	</tr>
	<tr>
		<td valign="top">
			<?= Loc::getMessage('PRIME_PHONEAUTH_WEBHOOK_IPS') ?>:<br>
			<small><?= Loc::getMessage('PRIME_PHONEAUTH_WEBHOOK_IPS_HINT') ?></small>
		</td>
		<td><input type="text" name="webhook_ips" size="40" value="<?= htmlspecialcharsbx($get('webhook_ips', '37.139.38.215')) ?>"></td>
	</tr>
	<tr class="heading"><td colspan="2"><?= Loc::getMessage('PRIME_PHONEAUTH_WEBHOOK_URL') ?></td></tr>
	<tr>
		<td colspan="2">
			<small><?= Loc::getMessage('PRIME_PHONEAUTH_WEBHOOK_URL_HINT') ?></small>
		</td>
	</tr>
	<?php foreach ($webhookRows as $row): ?>
	<tr>
		<td valign="top">
			<?= htmlspecialcharsbx($row['name']) ?> (<?= htmlspecialcharsbx($row['lid']) ?>):<br>
			<small><?= Loc::getMessage('PRIME_PHONEAUTH_WEBHOOK_SECRET') ?></small>
		</td>
		<td>
			<input type="text" name="webhook_secret[<?= htmlspecialcharsbx($row['lid']) ?>]" size="44" value="<?= htmlspecialcharsbx($row['secret']) ?>" readonly style="width:420px;background:#f6f6f6;">
			<br>
			<input type="submit" name="regenerate_secret[<?= htmlspecialcharsbx($row['lid']) ?>]" value="<?= htmlspecialcharsbx(Loc::getMessage('PRIME_PHONEAUTH_WEBHOOK_REGEN')) ?>">
			<br><br>
			<code style="display:block;padding:8px 10px;background:#fff;border:1px solid #d0d0d0;word-break:break-all;"><?= htmlspecialcharsbx($row['url']) ?></code>
			<?php
			$siteHost = strtolower((string)parse_url($row['url'], PHP_URL_HOST));
			$nowHost = strtolower(preg_replace('/:\d+$/', '', $currentHost) ?? '');
			if ($currentHost !== '' && $siteHost !== '' && $siteHost !== $nowHost):
				$localUrl = Config::absoluteWebhookUrl($currentHost, $row['lid']);
			?>
			<small><?= Loc::getMessage('PRIME_PHONEAUTH_WEBHOOK_URL_LOCAL') ?> <?= htmlspecialcharsbx($currentHost) ?>:</small>
			<code style="display:block;padding:8px 10px;background:#f7f7f7;border:1px solid #e0e0e0;word-break:break-all;margin-top:4px;"><?= htmlspecialcharsbx($localUrl) ?></code>
			<?php endif; ?>
		</td>
	</tr>
	<?php endforeach; ?>
	<?php $tabControl->Buttons(); ?>
	<input type="submit" name="save" value="Сохранить" class="adm-btn-save">
	<?php $tabControl->End(); ?>
</form>
