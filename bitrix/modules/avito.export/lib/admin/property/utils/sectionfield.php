<?php

namespace Avito\Export\Admin\Property\Utils;

use Bitrix\Main;

class SectionField
{
	public static function parseIblockId($userField) : int
	{
		if (preg_match('/^IBLOCK_(\d+)_SECTION$/', $userField['ENTITY_ID'], $matches))
		{
			return $matches[1];
		}

		throw new Main\ArgumentException(sprintf('iblock id not found in user field ENTITY_ID %s', $userField['ENTITY_ID']));
	}

	public static function parseUrlSectionId() : ?int
	{
		$request = Main\Application::getInstance()->getContext()->getRequest();

		if ($request->getRequestedPage() === '/bitrix/admin/iblock_section_edit.php')
		{
			$id = $request->get('ID');

			return is_numeric($id) ? (int)$id : null;
		}

		return null;
	}

	public static function chainValue(string $fieldName, int $iblockId, int $sectionId)
	{
		return self::fieldValue($fieldName, $iblockId, $sectionId) ?: self::parentValue($fieldName, $iblockId, $sectionId);
	}

	public static function parentValue(string $fieldName, int $iblockId, int $sectionId)
	{
		$result = null;

		foreach (self::sectionParents($iblockId, $sectionId) as $parentId)
		{
			$value = self::fieldValue($fieldName, $iblockId, $parentId);

			if (!empty($value))
			{
				$result = $value;
				break;
			}
		}

		return $result;
	}

	private static function fieldValue(string $fieldName, $iblockId, $sectionId)
	{
		global $USER_FIELD_MANAGER;

		return $USER_FIELD_MANAGER->GetUserFieldValue(
			sprintf('IBLOCK_%s_SECTION', $iblockId),
			$fieldName,
			$sectionId
		);
	}

	private static function sectionParents(int $iblockId, int $sectionId) : array
	{
		if (!Main\Loader::includeModule('iblock')) { return []; }

		$chain = \CIBlockSection::GetNavChain($iblockId, $sectionId, [ 'ID' ], true);
		$ids = array_column($chain, 'ID');
		array_pop($ids);

		return array_reverse($ids);
	}
}