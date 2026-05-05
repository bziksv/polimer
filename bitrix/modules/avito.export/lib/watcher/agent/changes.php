<?php
namespace Avito\Export\Watcher\Agent;

use Avito\Export;
use Avito\Export\Watcher;
use Bitrix\Main;

class Changes extends Export\Agent\Base
{
	public static function getDefaultParams() : array
	{
		return [
			'interval' => 5,
		];
	}

	public static function process(string $setupType) : bool
	{
		$needRepeat = false;
		$readyIds = [];

		while ($setupId = static::nextSetupId($setupType, $readyIds))
		{
			$needRepeat = true;
			$processor = Factory::makeProcessor('change', $setupType, $setupId);
			$state = $processor->state();

			$changes = static::setupChanges($setupType, $setupId, $state['INIT_TIME']);
			$changesByType = static::groupChangesByType($changes);

			$interrupted = $processor->run(Watcher\Engine\Controller::ACTION_CHANGE, [
				'CHANGES' => $changesByType,
				'USE_TMP' => false,
			]);

			if ($interrupted) { break; }

			$readyIds[] = $setupId;
			static::releaseChanges($setupType, $setupId, $changesByType);
		}

		return $needRepeat;
	}

	protected static function releaseChanges(string $setupType, int $setupId, array $changesBySource) : void
	{
		foreach ($changesBySource as $entityType => $entities)
		{
			if (empty($entities)) { continue; }

			$batch = new Export\DB\Facade\BatchDelete(ChangesTable::class);
			$batch->run([
				'filter' => [
					'=SETUP_TYPE' => $setupType,
					'=SETUP_ID' => $setupId,
					'=ENTITY_TYPE' => $entityType,
					'=ENTITY_ID' => $entities,
				],
			]);
		}
	}

	protected static function nextSetupId(string $setupType, array $skipIds = []) : ?int
	{
		$result = null;
		$queryParameters = [
			'select' => [ 'SETUP_ID' ],
			'filter' => [ '=SETUP_TYPE' => $setupType ],
			'order' => [ 'TIMESTAMP_X' => 'asc' ],
			'limit' => 1
		];

		if (!empty($skipIds))
		{
			$queryParameters['filter']['!=SETUP_ID'] = $skipIds;
		}

		$query = ChangesTable::getList($queryParameters);

		if ($row = $query->fetch())
		{
			$result = (int)$row['SETUP_ID'];
		}

		return $result;
	}

	protected static function setupChanges($setupType, $setupId, Main\Type\DateTime $startDate): array
	{
		$result = [];
		$limit = Export\Config::getOption('export_changes_limit', 500);

		$query = ChangesTable::getList([
			'filter' => [
				'=SETUP_TYPE' => $setupType,
				'=SETUP_ID' => $setupId,
				'<=TIMESTAMP_X' => $startDate,
			],
			'select' => [
				'ENTITY_TYPE',
				'ENTITY_ID'
			],
			'order' => [
				'TIMESTAMP_X' => 'asc'
			],
			'limit' => $limit
		]);

		while ($row = $query->fetch())
		{
			$result[] = $row;
		}

		return $result;
	}

	protected static function groupChangesByType($changes): array
	{
		$result = [];

		foreach ($changes as $change)
		{
			if (!isset($result[$change['ENTITY_TYPE']]))
			{
				$result[$change['ENTITY_TYPE']] = [];
			}

			$result[$change['ENTITY_TYPE']][] = $change['ENTITY_ID'];
		}

		return $result;
	}
}