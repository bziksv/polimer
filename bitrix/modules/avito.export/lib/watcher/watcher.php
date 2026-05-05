<?php
namespace Avito\Export\Watcher;

use Avito\Export\DB\Facade\BatchDelete;
use Avito\Export\Feed\Source;

class Watcher
{
	protected $entityType;
	protected $entityId;
	protected $watches = [];
	protected $fetcherPool;

	public function __construct(string $entityType, int $entityId, Source\FetcherPool $fetcherPool = null)
	{
		$this->entityType = $entityType;
		$this->entityId = $entityId;
		$this->fetcherPool = $fetcherPool ?? new Source\FetcherPool();
	}

	public function watch(array $sources, Source\Context $context) : void
	{
		$this->watches[$context->iblockId()] = [
			$sources,
			$context,
		];
	}

	public function flush() : void
	{
		$exists = $this->exists();
		$iblockIds = array_unique(array_merge(
			array_keys($exists),
			array_keys($this->watches)
		));

		foreach ($iblockIds as $iblockId)
		{
			$previous = $exists[$iblockId] ?? [];

			if (isset($this->watches[$iblockId]))
			{
				[$new, $context] = $this->watches[$iblockId];
			}
			else
			{
				$new = [];
				$context = new Source\Context($iblockId);
			}

			$this->registerNew($new, $previous, $context);
			$this->deleteOld(array_diff($previous, $new), $context);
		}
	}

	protected function registerNew(array $sources, array $exists, Source\Context $context) : void
	{
		foreach ($sources as $source)
		{
			$this->toggleListener($source, $context, true);

			if (!in_array($source, $exists, true))
			{
				$this->add($source, $context);
			}
		}
	}

	protected function deleteOld(array $sources, Source\Context $context) : void
	{
		$this->deleteFew($sources);

		foreach ($this->filterNobodyUsed($sources, $context) as $source)
		{
			$this->toggleListener($source, $context, false);
		}
	}

	protected function exists() : array
	{
		$result = [];

		$query = RegistryTable::getList([
			'filter' => [
				'=ENTITY_TYPE' => $this->entityType,
				'=ENTITY_ID' => $this->entityId,
			],
			'select' => [ 'ID', 'IBLOCK_ID', 'SOURCE' ],
		]);

		while ($row = $query->fetch())
		{
			if (!isset($result[$row['IBLOCK_ID']]))
			{
				$result[$row['IBLOCK_ID']] = [];
			}

			$result[$row['IBLOCK_ID']][$row['ID']] = $row['SOURCE'];
		}

		return $result;
	}

	protected function filterNobodyUsed(array $sources, Source\Context $context) : array
	{
		if (empty($sources)) { return []; }

		$notUsed = array_fill_keys($sources, true);

		$query = RegistryTable::getList([
			'filter' => [
				'=IBLOCK_ID' => $context->iblockId(),
				'=SOURCE' => $sources,
			],
			'select' => [ 'SOURCE' ],
			'group' => [ 'SOURCE' ],
		]);

		while ($row = $query->fetch())
		{
			$notUsed[$row['SOURCE']] = false;
		}

		return array_keys(array_filter($notUsed));
	}

	protected function add(string $source, Source\Context $context) : void
	{
		$saveResult = RegistryTable::add([
			'ENTITY_TYPE' => $this->entityType,
			'ENTITY_ID' => $this->entityId,
			'IBLOCK_ID' => $context->iblockId(),
			'SOURCE' => $source,
		]);

		if (!$saveResult->isSuccess())
		{
			trigger_error(implode(', ', $saveResult->getErrorMessages()), E_USER_WARNING);
		}
	}

	protected function deleteFew(array $sources) : void
	{
		if (empty($sources)) { return; }

		$batch = new BatchDelete(RegistryTable::class);
		$batch->run([
			'filter' => [ '=ID' => array_keys($sources) ]
		]);
	}

	protected function toggleListener(string $source, Source\Context $context, bool $direction) : void
	{
		$fetcher = $this->fetcherPool->some($source);

		foreach ($fetcher->listener()->handlers($context) as $handler)
		{
			if ($direction)
			{
				Source\ListenerProxy::bind($source, $handler);
			}
			else
			{
				Source\ListenerProxy::unbind($source, $handler);
			}
		}
	}
}