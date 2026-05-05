<?php
namespace Avito\Export\Logger;

use Avito\Export;
use Avito\Export\DB\Facade\BatchDelete;
use Avito\Export\Psr;
use Bitrix\Main;

class Logger extends Psr\Logger\AbstractLogger
{
	protected $setupType;
	protected $setupId;
	protected $used = [];
	protected $touchAllowed = false;
	protected $deleteAllowed = false;
	protected $flushDelayed = false;
	protected $flushQueue = [];
	protected $loggerTableClass = Table::class;

	public function __construct(string $setupType, int $setupId)
	{
		$this->setupType = $setupType;
		$this->setupId = $setupId;
	}

	public function reset() : void
	{
		$this->flushDelayed = false;
		$this->touchAllowed = false;
		$this->deleteAllowed = false;
		$this->used = [];
	}

	public function delayFlush(bool $dir = true) : void
	{
		$this->flushDelayed = $dir;
	}

	/** @noinspection PhpUnused */
	public function allowTouch(bool $dir = true) : void
	{
		$this->touchAllowed = $dir;
	}

	public function allowDelete(bool $dir = true) : void
	{
		$this->deleteAllowed = $dir;
	}

	public function flush() : void
	{
		$this->write($this->flushQueue);
		$this->flushQueue = [];
		$this->used = [];
		$this->delayFlush(false);
	}

	public function used(string $entityType, array $entities) : void
	{
		$this->used[$entityType] = $entities;
	}

	public function log($level, $message, array $context = []) : void
	{
		if (!$this->needLog($level)) { return; }

		[$message, $context] = $this->stringifyMessage($message, $context);

		$item = [
			'LEVEL' => $level,
			'MESSAGE' => $message,
			'CONTEXT' => $context,
		];
		$item = $this->fillColumns($item);
		$item = $this->fillSign($item);

		if ($this->flushDelayed)
		{
			$this->flushQueue[$item['SIGN']] = $item;
			return;
		}

		$this->write([
			$item['SIGN'] => $item,
		]);
	}

	protected function stringifyMessage($message, array $context) : array
	{
		if ($message instanceof \Throwable)
		{
			$context['TRACE'] = Main\Diag\ExceptionHandlerFormatter::format($message);
			$context['TRACE'] = str_replace(
				rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR,
				'',
				$context['TRACE']
			);

			$message = $message->getMessage();
		}
		else
		{
			$message = (string)$message;
		}

		return [ $message, $context ];
	}

	protected function needLog($level) : bool
	{
		$levels = LogLevel::order();
		$configured = Export\Config::getOption('export_log_level', Psr\Logger\LogLevel::INFO);

		$currentOrder = array_search($level, $levels, true);
		$configuredOrder = array_search($configured, $levels, true);

		return ($currentOrder !== false && $currentOrder <= $configuredOrder);
	}

	public function clear() : void
	{
		$facade = new BatchDelete($this->loggerTableClass);
		$facade->run([
			'filter' => [
				'=SETUP_TYPE' => $this->setupType,
				'=SETUP_ID' => $this->setupId,
			],
		]);
	}

	public function removeAll(string $entityType, array $context = []) : void
	{
		$additionalFilter = !empty($context['REGION_ID']) ? [ '=REGION_ID' => $context['REGION_ID'] ] : [];

		$batch = new BatchDelete($this->loggerTableClass);
		$batch->run([
			'filter' => [
				'=SETUP_TYPE' => $this->setupType,
				'=SETUP_ID' => $this->setupId,
				'=ENTITY_TYPE' => $entityType,
			] + $additionalFilter,
		]);
	}

	public function remove(string $entityType, array $entityIds, array $context = []) : void
	{
		$additionalFilter = !empty($context['REGION_ID']) ? [ '=REGION_ID' => $context['REGION_ID'] ] : [];

		foreach (array_chunk($entityIds, 500) as $chunkIds)
		{
			$batch = new BatchDelete($this->loggerTableClass);
			$batch->run([
				'filter' => [
					'=SETUP_TYPE' => $this->setupType,
					'=SETUP_ID' => $this->setupId,
					'=ENTITY_TYPE' => $entityType,
					'=ENTITY_ID' => $chunkIds,
				] + $additionalFilter,
			]);
		}
	}

	protected function write(array $queue) : void
	{
		$exists = $this->storageExists($queue);
		$existMap = array_flip($exists);
		$toAdd = array_diff_key($queue, $existMap);
		$toUpdate = array_intersect_key($queue, $existMap);
		$toDelete = array_diff_key($existMap, $queue);

		$this->storageInsert(array_values($toAdd));
		$this->storageTouch(array_keys($toUpdate));
		$this->storageDelete(array_keys($toDelete));
	}

	protected function fillColumns(array $item) : array
	{
		$defaults = [
			'SETUP_TYPE' => $this->setupType,
			'SETUP_ID' => $this->setupId,
			'ENTITY_ID' => 0,
			'TIMESTAMP_X' => new Main\Type\DateTime(),
		];
		$columns = [
			'ENTITY_TYPE' => true,
			'ENTITY_ID' => true,
			'REGION_ID' => true,
		];

		$item += array_intersect_key($item['CONTEXT'], $columns);
		$item += $defaults;
		$item['CONTEXT'] = array_diff_key($item['CONTEXT'], $columns);

		return $item;
	}

	protected function fillSign(array $item) : array
	{
		$signValues = [];
		$columns = [
			'ENTITY_TYPE',
			'ENTITY_ID',
			'REGION_ID',
			'LEVEL',
			'MESSAGE',
		];

		foreach ($columns as $column)
		{
			$signValues[$column] = (string)($item[$column] ?? '');
		}

		if (!empty($item['CONTEXT']))
		{
			$signValues['CONTEXT'] = $item['CONTEXT'];
		}

		$sign = md5(serialize($signValues));
		$item['SIGN'] = $sign;

		return $item;
	}

	protected function storageExists(array $queue) : array
	{
		$filter = [
			'=SETUP_TYPE' => $this->setupType,
			'=SETUP_ID' => $this->setupId,
		];

		if ($this->deleteAllowed)
		{
			$queueGroup = Export\Utils\ArrayHelper::groupBy($queue, 'ENTITY_TYPE');
			$usedTypes = array_unique(array_merge(
				array_keys($queueGroup),
				array_keys($this->used)
			));
			$partials = [];

			foreach ($usedTypes as $type)
			{
				$typeQueue = $queueGroup[$type] ?? [];
				$typeUsed = $this->used[$type] ?? [];
				$nullRegion = -1;
				$typePartials = [
					$nullRegion => [],
				];

				foreach ($typeQueue as $row)
				{
					$regionId = $row['REGION_ID'] ?? $nullRegion;

					if (!isset($typePartials[$regionId])) { $typePartials[$regionId] = []; }

					$typePartials[$regionId][$row['ENTITY_ID']] = $row['ENTITY_ID'];
				}

				foreach ($typeUsed as $row)
				{
					if (is_array($row))
					{
						$regionId = $row['REGION_ID'] ?? $nullRegion;
						$entityId = $row['ENTITY_ID'];
					}
					else
					{
						$regionId = $nullRegion;
						$entityId = $row;
					}

					if (!isset($typePartials[$regionId])) { $typePartials[$regionId] = []; }

					$typePartials[$regionId][$entityId] = $entityId;
				}

				foreach ($typePartials as $regionId => $entityIds)
				{
					if ($regionId === $nullRegion)
					{
						$regionFilter = [];
					}
					else
					{
						$regionFilter = [ '=REGION_ID' => $regionId ];

						if (!empty($typePartials[$nullRegion]))
						{
							$entityIds = array_diff_key($entityIds, $typePartials[$nullRegion]);
						}
					}

					if (empty($entityIds)) { continue; }

					$partials[] = [
						'=ENTITY_TYPE' => $type,
						'=ENTITY_ID' => array_values($entityIds),
					] + $regionFilter;
				}
			}

			if (empty($partials)) { return []; }

			if (count($partials) > 1)
			{
				$filter[] = array_merge(
					[ 'LOGIC' => 'OR' ],
					array_values($partials)
				);
			}
			else
			{
				$filter += $partials[0];
			}
		}
		else
		{
			if (empty($queue)) { return []; }

			$filter['=SIGN'] = array_keys($queue);
		}

		$query = Table::getList([
			'filter' => $filter,
			'select' => [ 'SIGN' ],
		]);

		return array_column($query->fetchAll(), 'SIGN');
	}

	protected function storageInsert(array $rows) : void
	{
		foreach (array_chunk($rows, 500) as $rowsChunk)
		{
			$storageResult = Table::addMulti($rowsChunk, true);

			$this->handleStorageResult($storageResult);
		}
	}

	protected function storageTouch(array $signs) : void
	{
		if (!$this->touchAllowed) { return; }

		foreach (array_chunk($signs, 500) as $signsChunk)
		{
			$primaries = array_map(function(string $sign) {
				return [
					'SETUP_TYPE' => $this->setupType,
					'SETUP_ID' => $this->setupId,
					'SIGN' => $sign,
				];
			}, $signsChunk);

			$storageResult = Table::updateMulti($primaries, [
				'TIMESTAMP_X' => new Main\Type\DateTime(),
			], true);

			$this->handleStorageResult($storageResult);
		}
	}

	protected function storageDelete(array $signs) : void
	{
		if (!$this->deleteAllowed || empty($signs)) { return; }

		$batch = new BatchDelete($this->loggerTableClass);

		$batch->run([
			'filter' => [
				'=SETUP_TYPE' => $this->setupType,
				'=SETUP_ID' => $this->setupId,
				'=SIGN' => $signs,
			],
		]);
	}

	protected function handleStorageResult(Main\Result $storageResult) : void
	{
		if ($storageResult->isSuccess()) { return; }

		$message = implode(PHP_EOL, $storageResult->getErrorMessages());

		trigger_error($message, E_USER_WARNING);
	}
}
