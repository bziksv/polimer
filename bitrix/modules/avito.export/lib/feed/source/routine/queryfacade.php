<?php
namespace Avito\Export\Feed\Source\Routine;

use Avito\Export\Feed\Source\Data;
use Avito\Export\Feed\Source\FetcherPool;
use Avito\Export\Feed\Source\ContextPool;
use Avito\Export\Feed\Source\Registry;
use Bitrix\Main\Loader;

class QueryFacade
{
	public static function sourceSelect(array $select) : Data\SourceSelect
	{
		$result = new Data\SourceSelect();

		foreach ($select as $type => $fields)
		{
			foreach ((array)$fields as $field)
			{
				$result->add($type, $field);
			}
		}

		return $result;
	}

	public static function fetch(int $iblockId, array $elementIds, Data\SourceSelect $sourceSelect) : array
	{
		$queryBuilder = new QueryBuilder(new FetcherPool());
		$context = ContextPool::iblockInstance($iblockId);

		$queryBuilder->bootSources($sourceSelect, $context);

		$rows = self::loadElements($iblockId, $elementIds, $sourceSelect->fields(Registry::IBLOCK_FIELD));

		if (empty($rows)) { return []; }

		return $queryBuilder->fetch($sourceSelect, $rows, [], $context);
	}

	private static function loadElements(int $iblockId, array $elementIds, array $select) : array
	{
		if (empty($elementIds) || !Loader::includeModule('iblock')) { return []; }

		$result = [];

		$query = \CIBlockElement::GetList(
			[],
			[ 'IBLOCK_ID' => $iblockId, '=ID' => $elementIds ],
			false,
			false,
			array_merge([ 'IBLOCK_ID', 'ID' ], $select)
		);

		while ($row = $query->Fetch())
		{
			$result[$row['ID']] = $row;
		}

		return $result;
	}
}