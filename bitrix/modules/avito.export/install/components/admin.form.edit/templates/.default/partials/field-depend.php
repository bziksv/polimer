<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Bitrix\Main;

/** @var $component \Avito\Export\Components\AdminFormEdit */

if (isset($field['DEPEND']))
{
	Main\UI\Extension::load('avitoexport.ui.input.dependfield');

	$rowAttributes['id'] = 'avito-depend-' . $component->randString(4);
	$rowAttributes['class'] = 'depend-field';

	if ($field['DEPEND_HIDDEN'])
	{
		$rowAttributes['class'] .= ' is--hidden';
	}
	?>
	<script>
		BX.ready(function() {
			new BX.AvitoExport.Ui.Input.Dependfield(
				document.getElementById('<?= $rowAttributes['id'] ?>'), <?= Main\Web\Json::encode([
					'depend' => $field['DEPEND'],
					'lang' => [],
				], JSON_INVALID_UTF8_IGNORE) ?>);
		});
	</script>
	<?php
}