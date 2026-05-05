<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

/** @var $arResult array */
/** @var $tab array */
/** @var $fields array */
/** @var $component Avito\Export\Components\AdminFormEdit */

include __DIR__ . '/warning.php';

$fieldActiveGroup = null;

foreach ($fields as $fieldKey)
{
	if (!isset($arResult['SPECIAL_FIELDS_MAP'][$fieldKey]))
	{
		$field = $component->getField($fieldKey);

		if (isset($field['GROUP']) && $field['GROUP'] !== $fieldActiveGroup)
		{
			$fieldActiveGroup = $field['GROUP'];
			$foundFieldKey = false;
			$hasVisibleFields = false;

			foreach ($fields as $siblingKey)
			{
				if ($siblingKey === $fieldKey) { $foundFieldKey = true; }
				if (!$foundFieldKey) { continue; }

				$siblingField = $component->getField($siblingKey);

				if (isset($siblingField['GROUP']) && $siblingField['GROUP'] !== $fieldActiveGroup) { break; }

				if (empty($siblingField['DEPEND_HIDDEN']))
				{
					$hasVisibleFields = true;
					break;
				}
			}

			?>
			<tr class="heading <?= $hasVisibleFields ? '' : 'is--hidden' ?>">
				<td colspan="2"><?=$field['GROUP'];?></td>
			</tr>
			<?php
			if (isset($field['GROUP_DESCRIPTION']))
			{
				?>
				<tr class="avito-group-description <?= $hasVisibleFields ? '' : 'is--hidden' ?>">
					<td class="adm-detail-content-cell-l" width="40%" align="right" valign="top">&nbsp;</td>
					<td class="adm-detail-content-cell-r" width="60%">
						<div style="max-width: 800px;"><?= $field['GROUP_DESCRIPTION'] ?></div>
					</td>
				</tr>
				<?php
			}
		}

		include __DIR__ . '/field.php';
	}
	else
	{
		$specialKey = $arResult['SPECIAL_FIELDS_MAP'][$fieldKey];

		if (!isset($arResult['SPECIAL_FIELDS_SHOWN'][$specialKey]))
		{
			$arResult['SPECIAL_FIELDS_SHOWN'][$specialKey] = true;
			$specialFields = $arResult['SPECIAL_FIELDS'][$specialKey];

			require __DIR__ . '/special-' . $specialKey . '.php';
		}
	}
}

if (isset($tab['DATA']['NOTE']))
{
	?>
	<tr>
		<td class="adm-detail-content-cell-l" width="40%" align="right" valign="top">&nbsp;</td>
		<td class="adm-detail-content-cell-r" width="60%">
			<?php
			\CAdminMessage::ShowMessage([
				'TYPE' => 'OK',
				'MESSAGE' => $tab['DATA']['NOTE'],
				'DETAILS' => $tab['DATA']['NOTE_DESCRIPTION'] ?? null,
				'HTML' => true,
			]);
			?>
		</td>
	</tr>
	<?php
}
