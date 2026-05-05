<?php
namespace Avito\Export\Push\Engine\Steps;

use Avito\Export\Config;
use Avito\Export\Glossary;
use Avito\Export\Watcher;
use Avito\Export\Logger;
use Avito\Export\DB;

class Submitter extends Step
{
	public const TYPE = 'submitter';

	public function getName() : string
	{
		return static::TYPE;
	}

	public function start(string $action, $offset = null) : void
	{
		do
		{
			$stampQueue = $this->stampQueue();
			$processQueue = $this->filterQueue($stampQueue);

			foreach ($processQueue->groupByType() as $type => $typeQueue)
			{
				$controller = Submitter\Factory::make($type, $this);
				$controller->process($typeQueue);

				if ($this->controller->isTimeExpired())
				{
					throw new Watcher\Exception\TimeExpired($this);
				}
			}
		}
		while ($stampQueue->count() > 0);
	}

	protected function stampQueue() : Stamp\Collection
	{
		$query = Stamp\RepositoryTable::getList([
			'filter' => [
				'=PUSH_ID' => $this->controller->getSetup()->getId(),
				'=STATUS' => [
					Stamp\RepositoryTable::STATUS_WAIT,
					Stamp\RepositoryTable::STATUS_DELETE,
				],
			],
			'limit' => max(1, (int)Config::getOption('push_submit_limit', 500)),
		]);

		$result = $query->fetchCollection();
		$result->fillServicePrimary();

		return $result;
	}

	protected function filterQueue(Stamp\Collection $stampQueue) : Stamp\Collection
	{
		[$processQueue, $failedQueue] = $this->localPrimaryCollision($stampQueue, new Stamp\Collection());
		[$processQueue, $failedQueue] = $this->storePrimaryCollision($processQueue, $failedQueue);

		$this->deleteFiltered($failedQueue);
		$this->clearLog($failedQueue);

		return $processQueue;
	}

	protected function localPrimaryCollision(Stamp\Collection $stampQueue, Stamp\Collection $failedQueue) : array
	{
		$used = array_flip($stampQueue->filterStatus(Stamp\RepositoryTable::STATUS_WAIT)->primaries());

		return $this->compilePrimaryCollision($stampQueue, $failedQueue, $used);
	}

	protected function storePrimaryCollision(Stamp\Collection $stampQueue, Stamp\Collection $failedQueue) : array
	{
		$used = $this->usedPrimaries($stampQueue->filterStatus(Stamp\RepositoryTable::STATUS_DELETE)->primaries());

		return $this->compilePrimaryCollision($stampQueue, $failedQueue, $used);
	}

	protected function compilePrimaryCollision(Stamp\Collection $stampQueue, Stamp\Collection $failedQueue, array $used) : array
	{
		$processQueue = new Stamp\Collection();

		foreach ($stampQueue as $stamp)
		{
			$primary = $stamp->getPrimary();

			if (
				isset($used[$primary])
				&& (string)$used[$primary] !== (string)$stamp->getElementId()
				&& $stamp->getStatus() === Stamp\RepositoryTable::STATUS_DELETE
			)
			{
				$stamp->setStatus(Stamp\RepositoryTable::STATUS_FAILED);
				$failedQueue->add($stamp);
				continue;
			}

			$processQueue->add($stamp);
		}

		return [$processQueue, $failedQueue];
	}

	protected function usedPrimaries(array $primaries) : array
	{
		if (empty($primaries)) { return []; }

		$result = [];

		$query = Stamp\RepositoryTable::getList([
			'filter' => [
				'=PUSH_ID' => $this->controller->getSetup()->getId(),
				'=PRIMARY' => array_values($primaries),
				'=STATUS' => [
					Stamp\RepositoryTable::STATUS_READY,
					Stamp\RepositoryTable::STATUS_FAILED,
					Stamp\RepositoryTable::STATUS_UNPUBLISHED,
				],
			],
			'select' => [ 'PRIMARY', 'ELEMENT_ID' ],
		]);

		while ($row = $query->fetch())
		{
			$result[$row['PRIMARY']] = $row['ELEMENT_ID'];
		}

		return $result;
	}

	protected function deleteFiltered(Stamp\Collection $failedQueue) : void
	{
		$filter = $failedQueue->queryFilter();

		if ($filter === null) { return; }

		$batch = new DB\Facade\BatchDelete(Stamp\RepositoryTable::class);
		$batch->run([
			'filter' => [
				'=PUSH_ID' => $this->controller->getSetup()->getId(),
				$filter,
			],
		]);
	}

	protected function clearLog(Stamp\Collection $failedQueue) : void
	{
		$groups = [];

		foreach ($failedQueue as $stamp)
		{
			$type = $stamp->getType();

			if (!isset($groups[$type])) { $groups[$type] = []; }

			$groups[$type][] = [
				'ENTITY_ID' => $stamp->getElementId(),
				'REGION_ID' => $stamp->getRegionId(),
			];
		}

		if (empty($groups)) { return; }

		$logger = new Logger\Logger(Glossary::SERVICE_PUSH, $this->controller->getSetup()->getId());
		$logger->allowDelete();

		foreach ($groups as $type => $ids)
		{
			$logger->used($type, $ids);
		}

		$logger->flush();
	}
}