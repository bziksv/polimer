<?php
/** @noinspection PhpUnused */
namespace Avito\Export\Admin\UserField;

use Bitrix\Main;
use Bitrix\Iblock;
use Bitrix\Catalog;

class IblockType extends EnumerationType
{
	public const VALUES_ALL = 'A';
	public const VALUES_CATALOG = 'Y';
	public const VALUES_SIMPLE = 'N';

	protected static $values = [];

	public static function GetList($userField) : \CDBResult
	{
		$needCatalog = $userField['SETTINGS']['VALUES_CATALOG'] ?? self::VALUES_ALL;
		$values = static::getValues($needCatalog);

		$result = new \CDBResult();
		$result->InitFromArray($values);

		return $result;
	}

	protected static function getValues(string $needCatalog = self::VALUES_ALL) : array
	{
		if (static::$values[$needCatalog] === null)
		{
			static::$values[$needCatalog] = static::loadValues($needCatalog);
		}

		return static::$values[$needCatalog];
	}

	protected static function loadValues(string $needCatalog = self::VALUES_ALL) : array
	{
		$result = static::getIblocks();

		if ($needCatalog !== self::VALUES_ALL)
		{
			$catalogs = static::getCatalogs();

			if ($needCatalog === self::VALUES_CATALOG)
			{
				$catalogs = array_intersect($catalogs, [ 'PRODUCT' ]);
				$catalogs = array_intersect_key($result, $catalogs);

				if (!empty($catalogs))
				{
					$result = $catalogs;
				}
			}
			else if ($needCatalog === self::VALUES_SIMPLE)
			{
				$result = array_diff_key($result, $catalogs);
			}
		}

		return array_values($result);
	}

	protected static function getIblocks() : array
	{
		if (!Main\Loader::includeModule('iblock')) { return []; }

		$result = [];

		$query = Iblock\IblockTable::getList([
			'filter' => [ '=ACTIVE' => 'Y' ],
			'select' => [ 'ID', 'NAME' ],
		]);

		while ($row = $query->fetch())
		{
			$title = '[' . $row['ID'] . '] ' . $row['NAME'];

			$result[$row['ID']] = [
				'ID' => $row['ID'],
				'VALUE' => $title,
			];
		}

		return $result;
	}

	protected static function getCatalogs() : array
	{
		if (!Main\Loader::includeModule('catalog')) { return []; }

		$result = [];

		$queryCatalogList = Catalog\CatalogIblockTable::getList([
			'select' => [ 'IBLOCK_ID', 'PRODUCT_IBLOCK_ID' ],
		]);

		while ($catalog = $queryCatalogList->fetch())
		{
			$iblockId = (int)$catalog['IBLOCK_ID'];
			$productIblockId = (int)$catalog['PRODUCT_IBLOCK_ID'];

			if ($productIblockId > 0 && $productIblockId !== $iblockId)
			{
				$result[$productIblockId] = 'PRODUCT';
				$result[$iblockId] = 'OFFERS';
			}
			else
			{
				$result[$iblockId] = 'PRODUCT';
			}
		}

		return $result;
	}

	public static function convertNameToId($name) : string
	{
		$result = str_replace(['[', ']', '-', '__'], '_', $name);
		$result = trim($result, '_');

		return $result;
	}

	public static function GetEditFormHTMLMulty($userField, $htmlControl) : string
	{
		$result = '';
		$baseId = static::convertNameToId($htmlControl['NAME']);
		$queryOptions = call_user_func(
			[ $userField['USER_TYPE']['CLASS_NAME'], 'getList' ],
			$userField
		);

		while ($option = $queryOptions->Fetch())
		{
			$optionHtmlId = $baseId . '_' . $option['ID'];
			/** @noinspection TypeUnsafeArraySearchInspection */
			$isChecked = !empty($htmlControl['VALUE']) && in_array($option['ID'], $htmlControl['VALUE']);

			$result .=
				'<div>'
				. '<input class="adm-designed-checkbox" type="checkbox" name="' . $htmlControl['NAME'] . '" value="' . $option['ID'] . '" ' . ($isChecked ? 'checked' : '') . ' id="' . $optionHtmlId . '">'
				. '<label class="adm-designed-checkbox-label" for="' . $optionHtmlId . '"></label>'
				. '<label for="' . $optionHtmlId . '"> ' . $option['VALUE'] . '</label>'
				. '</div>';
		}

		return $result;
	}
}