<?php
/**
 * Обязательная галочка согласия на обработку ПД.
 *
 * @var string $consentName
 * @var bool   $consentChecked
 * @var bool   $consentInvalid
 */
if (!isset($consentName) || $consentName === '') {
	$consentName = 'RULE';
}
$consentInvalid = !empty($consentInvalid);
$consentChecked = (
	$_SERVER['REQUEST_METHOD'] === 'POST'
	&& (($_POST[$consentName] ?? '') === 'Y')
);
$consentId = 'polimer-consent-' . preg_replace('/[^a-z0-9_-]/i', '-', $consentName);
?>
<div class="rule polimer-consent-rule<?= $consentInvalid ? ' is-invalid' : '' ?>">
	<input
		type="checkbox"
		class="polimer-consent-checkbox"
		id="<?= htmlspecialcharsbx($consentId) ?>"
		name="<?= htmlspecialcharsbx($consentName) ?>"
		value="Y"
		required
		<?= $consentChecked ? ' checked' : '' ?>
	>
	<label class="polimer-consent-label" for="<?= htmlspecialcharsbx($consentId) ?>">
		Я даю <a href="/info/polimer-soglasie-obrabotki-pd/">согласие</a>
		на обработку персональных данных в соответствии с нашей
		<a href="/info/polimer-politika-personalnyh-dannyh/">Политикой обработки персональных данных</a>.
	</label>
	<?php if ($consentInvalid): ?>
		<span class="polimer-field-error">Отметьте согласие на обработку персональных данных</span>
	<?php endif; ?>
</div>
