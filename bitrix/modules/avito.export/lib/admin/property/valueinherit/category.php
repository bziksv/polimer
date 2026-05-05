<?php

namespace Avito\Export\Admin\Property\ValueInherit;

use Bitrix\Main;
use Bitrix\Iblock;
use Avito\Export\Admin;
use Avito\Export\Concerns;
use Avito\Export\Admin\Property\FormCategory;
use Avito\Export\Feed\Source;

class Category
{
	use Concerns\HasOnceStatic;

	public static function properties(int $iblockId) : array
	{
		$cacheKey = implode(':', ['properties', $iblockId]);

		return static::onceStatic($cacheKey, static function() use ($iblockId) {
			$context = Source\ContextPool::iblockInstance($iblockId);
			$productIblockId = $context->productIblockId();

			if ($productIblockId !== null)
			{
				$result = array_merge(
					static::fetchElement($iblockId, FormCategory\Registry::ELEMENT),
					static::fetchElement($productIblockId, FormCategory\Registry::PRODUCT_ELEMENT),
					static::fetchSection($productIblockId, FormCategory\Registry::PRODUCT_SECTION)
				);
			}
			else
			{
				$result = array_merge(
					static::fetchElement($iblockId, FormCategory\Registry::ELEMENT),
					static::fetchSection($iblockId, FormCategory\Registry::SECTION)
				);
			}

			return $result;
		});
	}

	public static function sectionProperties(int $iblockId) : array
	{
		$cacheKey = implode(':', ['sectionProperties', $iblockId]);

		return static::onceStatic($cacheKey, static function() use ($iblockId) {
			return static::fetchSection($iblockId, FormCategory\Registry::SECTION_SELF);
		});
	}

	public static function parentValue(int $elementId, int $iblockId, string $fromPropertyId)
	{
		$cacheKey = implode(':', ['categoryParentValue', $elementId, $iblockId, $fromPropertyId]);

		return static::onceStatic($cacheKey, static function() use ($elementId, $iblockId, $fromPropertyId) {
			$result = null;
			$foundFrom = false;
			$fromType = null;

			foreach (static::properties($iblockId) as [$type, $propertyId])
			{
				if ($fromPropertyId === (string)$propertyId)
				{
					$foundFrom = true;
					$fromType = $type;
					continue;
				}

				if (!$foundFrom || $type === $fromType) { continue; }

				$formCategory = FormCategory\Registry::make($type);
				$stored = $formCategory->elementValues($propertyId, [ $elementId ]);

				if (empty($stored[$elementId])) { continue; }

				$result = $stored[$elementId];
				break;
			}

			return $result;
		});
	}

	protected static function fetchElement(int $iblockId, string $type) : array
	{
		$cacheKey = implode(':', ['elementProperties', $iblockId, $type]);

		return static::onceStatic($cacheKey, static function() use ($iblockId, $type) {
			if (!Main\Loader::includeModule('iblock')) { return []; }

			$result = [];

			$query = Iblock\PropertyTable::getList([
				'select' => [ 'ID' ],
				'filter' => [
					'=IBLOCK_ID' => $iblockId,
					'=ACTIVE' => true,
					'=USER_TYPE' => Admin\Property\CategoryProperty::USER_TYPE,
				],
				'order' => [
					'SORT' => 'ASC',
					'ID' => 'ASC',
				],
			]);

			while ($row = $query->fetch())
			{
				$result[] = [
					$type,
					$row['ID'],
				];
			}

			return $result;
		});
	}

	protected static function fetchSection(int $iblockId, string $type) : array
	{
		global $USER_FIELD_MANAGER;

		$userFields = $USER_FIELD_MANAGER->GetUserFields('IBLOCK_' . $iblockId . '_SECTION', 0, LANGUAGE_ID);
		$result = [];

		foreach ($userFields as $field)
		{
			if ($field['USER_TYPE_ID'] !== Admin\Property\CategoryField::USER_TYPE_ID) { continue; }

			$result[] = [
				$type,
				$field['FIELD_NAME'],
			];
		}

		return $result;
	}
}