<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Iblock;
use Bitrix\Main;

$arResult['IBLOCK_DATA'] = [];

if (!empty($arResult['ITEM']['IBLOCK']))
{
	$iblockIds = [];

	foreach ($arResult['ITEM']['IBLOCK'] as $iblockId)
	{
		if (!empty($iblockId))
		{
			$iblockId = (int)$iblockId;

			if ($iblockId > 0)
			{
				$iblockIds[] = $iblockId;
			}
		}
	}

	if (!empty($iblockIds) && Main\Loader::includeModule('iblock'))
	{
		$query = Iblock\IblockTable::getList([
			'filter' => [
				'=ID' => $iblockIds,
			],
			'select' => [
				'ID',
				'NAME',
			],
		]);

		while ($iblock = $query->fetch())
		{
			$arResult['IBLOCK_DATA'][$iblock['ID']] = $iblock;
		}
	}
}
