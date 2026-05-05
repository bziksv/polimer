<?php
namespace Avito\Export\Feed\Source\Routine;

use Avito\Export\Feed\Source;
use Avito\Export\Utils;

class ElementMerger
{
	private $fetcherPool;

	public function __construct(Source\FetcherPool $fetcherPool)
	{
		$this->fetcherPool = $fetcherPool;
	}

	public function apply(array $sourceValues, array $mergeMap) : array
	{
		if (empty($mergeMap)) { return $sourceValues; }

		$typeValues = $this->groupValuesByType($sourceValues);
		$result = [];

		$types = array_keys($typeValues);
		$sources = array_combine($types, array_map(
			function(string $type) { return $this->fetcherPool->some($type); },
			$types
		));

		uasort($sources, static function(Source\Fetcher $sourceA, Source\Fetcher $sourceB) {
			return $sourceA->order() <=> $sourceB->order();
		});

		/** @var Source\Fetcher $fetcher */
		foreach ($sources as $type => $fetcher)
		{
			if ($fetcher instanceof Source\FetcherMergable)
			{
				$mergedValues = $fetcher->merge($typeValues[$type], $mergeMap);
			}
			else
			{
				$mergedValues = array_diff_key($typeValues[$type], $mergeMap);
			}

			foreach ($mergedValues as $elementId => $values)
			{
				if (isset($mergeMap[$elementId])) { continue; }

				$result[$elementId][$type] = $values;
			}
		}

		return $result;
	}

	protected function groupValuesByType(array $sourceValues) : array
	{
		$result = [];

		foreach ($sourceValues as $elementId => $elementValues)
		{
			foreach ($elementValues as $type => $values)
			{
				$result[$type][$elementId] = $values;
			}
		}

		return $result;
	}
}