<?php
namespace Avito\Export\Feed\Source\Routine;

use Avito\Export\Feed\Setup;
use Avito\Export\Feed\Source;

class QueryBuilder
{
	protected $fetcherPool;

	public function __construct(Source\FetcherPool $fetcherPool)
	{
		$this->fetcherPool = $fetcherPool;
	}

	public function bootSources(Source\Data\SourceSelect $tagSources, Source\Context $context) : void
	{
		$loopSources = $tagSources->clone();
		$loopCount = 0;

		while (!$loopSources->isEmpty())
		{
			$sourcesStamp = $tagSources->clone();

			foreach ($loopSources->sources() as $type)
			{
				$fetcher = $this->fetcherPool->some($type);
				$select = $loopSources->fields($type);

				$fetcher->extend($select, $tagSources, $context);
			}

			$loopSources = $tagSources->diff($sourcesStamp);

			if (++$loopCount > 50)
			{
				trigger_error('infinite loop break on build source select', E_USER_WARNING);
				break;
			}
		}
	}

	public function compileFilters(Setup\FilterMap $filterMap, Source\Context $context, array $changesFilter = null) : array
	{
		$command = new QueryBuilder\Filter($this->fetcherPool);

		return $command->compile($filterMap, $context, $changesFilter);
	}

	public function fetch(Source\Data\SourceSelect $tagSources, array $elements, array $parents, Source\Context $context, array $fetched = []) : array
	{
		$result = $fetched;
		$types = $tagSources->sources();
		$sources = array_combine($types, array_map(
			function(string $type) { return $this->fetcherPool->some($type); },
			$types
		));

		uasort($sources, static function(Source\Fetcher $sourceA, Source\Fetcher $sourceB) {
			return $sourceA->order() <=> $sourceB->order();
		});

		foreach ($sources as $type => $fetcher)
		{
			$select = $tagSources->fields($type);

			foreach ($fetcher->values($elements, $parents, $result, $select, $context) as $elementId => $values)
			{
				if (!isset($result[$elementId])) { $result[$elementId] = []; }
				if (!isset($result[$elementId][$type])) { $result[$elementId][$type] = []; }

				$result[$elementId][$type] += $values;
			}
		}

		return $result;
	}
}