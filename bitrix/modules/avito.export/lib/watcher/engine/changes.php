<?php

namespace Avito\Export\Watcher\Engine;

use Avito\Export\DB\Facade\BatchDelete;
use Avito\Export\DB\Facade\BatchInsert;
use Avito\Export\Watcher\Agent;
use Bitrix\Main;

class Changes
{
	protected static $changeQueue = [];
	protected static $queueInitialized = false;

	public static function register(string $setupType, int $setupId, string $entityType, int $entityId) : void
	{
		static::addQueueChange($setupType, $setupId, $entityType, $entityId);
		static::initializeQueue();
	}

	protected static function initializeQueue() : void
	{
		if (static::$queueInitialized) { return; }

		static::$queueInitialized = true;
		static::bindFlush();
	}

	protected static function bindFlush() : void
	{
		$eventManager = Main\EventManager::getInstance();

		$eventManager->addEventHandler('main', 'OnAfterEpilog', [static::class, 'flush']);
		register_shutdown_function([static::class, 'flush']);
	}

	public static function flush() : void
	{
		if (empty(static::$changeQueue)) { return; }

		$types = [];

		foreach (array_chunk(static::$changeQueue, 100) as $flushChunk)
		{
			$flushChunk = static::markChangesTimestamp($flushChunk);
			$types += array_column($flushChunk, 'SETUP_TYPE', 'SETUP_TYPE');

			$batch = new BatchInsert(Agent\ChangesTable::class);
			$batch->run($flushChunk, []);
		}

		static::$changeQueue = [];
		static::registerAgent(array_keys($types));
	}

	protected static function addQueueChange(string $setupType, int $setupId, string $entityType, int $entityId) : void
	{
		$key = implode('_', [
			$setupType,
			$setupId,
			$entityType,
			$entityId,
		]);

		if (!isset(static::$changeQueue[$key]))
		{
			static::$changeQueue[$key] = [
				'SETUP_TYPE' => $setupType,
				'SETUP_ID' => $setupId,
				'ENTITY_TYPE' => $entityType,
				'ENTITY_ID' => $entityId,
			];
		}
	}

	protected static function markChangesTimestamp(array $changes) : array
	{
		$result = $changes;
		$dateTime = new Main\Type\DateTime();

		foreach ($result as &$change)
		{
			$change['TIMESTAMP_X'] = $dateTime;
		}
		unset($change);

		return $result;
	}

	public static function releaseAll(string $setupType, int $setupId) : void
	{
		$batch = new BatchDelete(Agent\ChangesTable::class);

		$batch->run([
			'filter' => [
				'=SETUP_TYPE' => $setupType,
				'=SETUP_ID' => $setupId,
			],
		]);
	}

	protected static function registerAgent(array $types) : void
	{
		foreach ($types as $type)
		{
			Agent\Changes::register([
				'method' => 'process',
				'arguments' => [ $type ],
				'sort' => 200,
			]);
		}
	}
}