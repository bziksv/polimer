<?php

namespace Avito\Export\Data\Iblock;

use Avito\Export\Concerns;
use Bitrix\Main;
use Bitrix\Iblock;

class PrimarySection
{
	use Concerns\HasOnceStatic;

	public static function forElements(array $elementToSectionMap, int $iblockId) : array
	{
		if (!Main\Loader::includeModule('iblock')) { return $elementToSectionMap; }
		if (self::keepIblockSectionIdEnabled($iblockId)) { return $elementToSectionMap; }

		$elementIds = array_keys($elementToSectionMap);
		$sections = self::linkedSections($elementIds);

		foreach ($elementToSectionMap as $elementId => $iblockSectionId)
		{
			if (empty($sections[$elementId])) { continue; }

			$elementToSectionMap[$elementId] = self::calculatePrimary($sections[$elementId], (int)$iblockSectionId);
		}

		return $elementToSectionMap;
	}

	public static function forLinkedSections(array $sectionIds, int $iblockSectionId, int $iblockId) : ?int
	{
		if (!Main\Loader::includeModule('iblock')) { return $iblockSectionId; }
		if (self::keepIblockSectionIdEnabled($iblockId)) { return $iblockSectionId; }

		$sections = self::loadSections($sectionIds, $iblockSectionId);

		return self::calculatePrimary($sections, $iblockSectionId);
	}

	protected static function keepIblockSectionIdEnabled(int $iblockId) : bool
	{
		return self::onceStatic('iblock_' . $iblockId, static function($iblockId) {
			$iblockInfo = \CIBlock::GetArrayByID($iblockId);

			$keepIblockSectionId = $iblockInfo["FIELDS"]["IBLOCK_SECTION"]["DEFAULT_VALUE"]["KEEP_IBLOCK_SECTION_ID"] ?? 'N';

			return $keepIblockSectionId === 'Y';
		}, $iblockId);
	}

	protected static function loadSections(array $sectionIds, int $iblockSectionId) : array
	{
		if ($iblockSectionId > 0)
		{
			$sectionIds[] = $iblockSectionId;
		}

		if (empty($sectionIds)) { return []; }

		$result = [];

		$query = Iblock\SectionTable::getList([
			'filter' => [
				'=ID' => $sectionIds
			],
			'select' => [
				'ID',
				'DEPTH_LEVEL',
				'LEFT_MARGIN',
				'RIGHT_MARGIN'
			]
		]);

		while ($row = $query->fetch())
		{
			$result[$row['ID']] = $row;
		}

		return $result;
	}

	protected static function linkedSections(array $elementIds) : array
	{
		if (empty($elementIds)) { return []; }

		$result = [];

		$query = \CIBlockElement::GetElementGroups($elementIds, true, [
			'IBLOCK_ELEMENT_ID',
			'ID',
			'DEPTH_LEVEL',
			'LEFT_MARGIN',
			'RIGHT_MARGIN'
		]);

		while ($section = $query->Fetch())
		{
			$elementId = $section['IBLOCK_ELEMENT_ID'];

			if (!isset($result[$elementId])) { $result[$elementId] = []; }

			$result[$elementId][$section['ID']] = $section;
		}

		return $result;
	}

	protected static function calculatePrimary(array $sections, int $defaultSectionId) : ?int
	{
		if (empty($sections))
		{
			return $defaultSectionId > 0 ? $defaultSectionId : null;
		}

		if (count($sections) > 1)
		{
			uasort($sections, static function(array $sectionA, array $sectionB) {
				return $sectionA['LEFT_MARGIN'] <=> $sectionB['LEFT_MARGIN'];
			});
		}

		if (!isset($sections[$defaultSectionId]))
		{
			$defaultSectionId = (int)min(array_keys($sections));
		}

		$result = $sections[$defaultSectionId];

		foreach ($sections as $section)
		{
			if (
				$section['LEFT_MARGIN'] > $result['LEFT_MARGIN']
				&& $section['RIGHT_MARGIN'] < $result['RIGHT_MARGIN']
				&& $section['DEPTH_LEVEL'] > $result['DEPTH_LEVEL']
			)
			{
				$result = $section;
			}
		}

		return (int)$result['ID'];
	}
}