<?php
namespace Avito\Export\Push\Engine\Steps;

use Avito\Export\Api;
use Avito\Export\Config;
use Avito\Export\DB;
use Avito\Export\Glossary;
use Avito\Export\Logger;
use Avito\Export\Push;
use Avito\Export\Concerns;
use Avito\Export\Utils\ArrayHelper;
use Bitrix\Main;

class Mapper extends Step
{
	use Concerns\HasLocale;

	public const TYPE = 'mapper';

	protected const MAX_URI_LENGTH = 8000;

	protected $logger;

	public function __construct(Push\Engine\Controller $controller)
	{
		parent::__construct($controller);
		$this->logger = new Logger\Logger(Glossary::SERVICE_PUSH, $this->getPush()->getId());
	}

	public function getName() : string
	{
		return static::TYPE;
	}

	public function start(string $action, $offset = null) : void
	{
		do
		{
			$stampQueue = $this->stampQueue();

			if (empty($stampQueue)) { break; }

			$fetchQueue = $this->filterKnown($stampQueue);
			$primaryMap = $this->loadPrimaryMap($fetchQueue);

			$this->commit($fetchQueue, $primaryMap);

			$this->logUnknown($fetchQueue, $primaryMap);
			$this->failUnknown($stampQueue, $primaryMap);
		}
		while (true);
	}

	protected function stampQueue() : array
	{
		$query = Stamp\RepositoryTable::getList([
			'select' => [
				'ELEMENT_ID',
				'REGION_ID',
				'PRIMARY',
				'TYPE',
				'SERVICE_ID' => 'SERVICE_PRIMARY.SERVICE_ID',
				'SERVICE_TIMESTAMP_X' => 'SERVICE_PRIMARY.TIMESTAMP_X',
			],
			'filter' => [
				'=PUSH_ID' => $this->controller->getSetup()->getId(),
				'=STATUS' => Stamp\RepositoryTable::STATUS_WAIT,
				[
					'LOGIC' => 'OR',
					[ '=SERVICE_PRIMARY.SERVICE_ID' => false ],
					[ '=SERVICE_PRIMARY.SERVICE_ID' => PrimaryMap\RepositoryTable::SERVICE_ID_NULL ],
					[ '<=SERVICE_PRIMARY.TIMESTAMP_X' => $this->actualizeAllDate() ],
				],
			],
			'limit' => max(1, (int)Config::getOption('push_submit_mapper_limit', 100)),
		]);

		return $query->fetchAll();
	}

	protected function filterKnown(array $stampQueue) : array
	{
		$compareFormat = 'Y-m-d H:i:s';
		$limitCompare = $this->actualizeMissingDate()->format($compareFormat);

		foreach ($stampQueue as $key => $row)
		{
			if ($row['SERVICE_ID'] !== PrimaryMap\RepositoryTable::SERVICE_ID_NULL)
			{
				continue;
			}

			/** @var Main\Type\DateTime $serviceUpdated */
			$serviceUpdated = $row['SERVICE_TIMESTAMP_X'];

			if ($serviceUpdated !== null && $serviceUpdated->format($compareFormat) > $limitCompare)
			{
				unset($stampQueue[$key]);
			}
		}

		return $stampQueue;
	}

	protected function actualizeAllDate() : Main\Type\DateTime
	{
		$startDate = $this->getParameter('INIT_TIME') ?: (new Main\Type\DateTime());

		return (clone $startDate)->add('-P1D');
	}

	protected function actualizeMissingDate() : Main\Type\DateTime
	{
		$startDate = $this->getParameter('INIT_TIME') ?: (new Main\Type\DateTime());
        $value = max(1, (int)Config::getOption('push_repeat_unpublished', 6));

		return (clone $startDate)->add("-PT{$value}H");
	}

	protected function loadPrimaryMap(array $stampQueue) : array
	{
		if (empty($stampQueue)) { return []; }

		$result = [];
		foreach ($this->chunkStampQueue($stampQueue) as $stampQueueChunk)
		{
			$response = $this->queryServiceMap($stampQueueChunk);
			$result += $this->compilePrimaryMap($response);
		}

		return $result;
	}

	protected function chunkStampQueue(array $stampQueue) : array
	{
		$uriLength = mb_strlen((new Api\Autoload\V2\Items\AvitoIds\Request())->url() . '?query=');
		$commaLength = mb_strlen(urlencode(','));

		$chunks = [];
		$chunkIndex = 0;
		$chunkLength = $uriLength;
		foreach ($stampQueue as $item)
		{
			$idLength = mb_strlen(urlencode($item['PRIMARY']));

			if ($chunkLength + $idLength > self::MAX_URI_LENGTH)
			{
				$chunkLength = $uriLength;
				$chunkIndex++;
			}

			$chunkLength += $idLength + $commaLength;
			$chunks[$chunkIndex][] = $item;
		}

		return $chunks;
	}

	protected function queryServiceMap(array $stampQueue) : Api\Autoload\V2\Items\AvitoIds\Response
	{
		$primaries = array_values(array_column($stampQueue, 'PRIMARY', 'PRIMARY'));
		$settings = $this->getPush()->getSettings()->commonSettings();

		$client = new Api\Autoload\V2\Items\AvitoIds\Request();
		$client->token($settings->token());
		$client->primary($primaries);

		return $client->execute();
	}

	protected function compilePrimaryMap(Api\Autoload\V2\Items\AvitoIds\Response $response) : array
	{
		$result = [];

		/** @var Api\Autoload\V2\Items\AvitoIds\Response\Item $item */
		foreach ($response->items() as $item)
		{
			$avitoId = $item->avitoId();

			if ($avitoId === null) { continue; }

			$result[$item->primary()] = $avitoId;
		}

		return $result;
	}

	protected function commit(array $stampQueue, array $primaryMap) : void
	{
		foreach (array_chunk($stampQueue, 500, true) as $stampChunk)
		{
			$batch = new DB\Facade\BatchInsert(PrimaryMap\RepositoryTable::class);
			$batch->run($this->makeRows($stampChunk, $primaryMap), [
				'SERVICE_ID',
				'TIMESTAMP_X',
			]);
		}
	}

	protected function makeRows(array $stampQueue, array $primaryMap) : array
	{
		$result = [];
		$common = [
			'PUSH_ID' => $this->getPush()->getId(),
			'TIMESTAMP_X' => new Main\Type\DateTime(),
		];

		foreach ($stampQueue as $row)
		{
			$result[] = $common + [
				'PRIMARY' => $row['PRIMARY'],
				'SERVICE_ID' => $primaryMap[$row['PRIMARY']] ?? PrimaryMap\RepositoryTable::SERVICE_ID_NULL,
			];
		}

		return $result;
	}

	/** @noinspection DisconnectedForeachInstructionInspection */
	protected function logUnknown(array $stampQueue, array $existsMap) : void
	{
		$unknownQueue = $this->splitUnknown($stampQueue, $existsMap);

		foreach (array_chunk($unknownQueue, 500) as $unknownChunk)
		{
			$this->logger->allowDelete();
			$this->logger->delayFlush();

			foreach ($unknownChunk as $row)
			{
				$this->logger->warning(self::getLocale('UNKNOWN'), [
					'ENTITY_TYPE' => $row['TYPE'],
					'ENTITY_ID' => $row['ELEMENT_ID'],
					'REGION_ID' => $row['REGION_ID'],
				]);
			}

			$this->logger->flush();
		}
	}

	protected function failUnknown(array $stampQueue, array $existsMap) : void
	{
		$unknownQueue = $this->splitUnknown($stampQueue, $existsMap);

		foreach (ArrayHelper::groupBy($unknownQueue, 'REGION_ID') as $regionId => $regionQueue)
		{
			foreach (array_chunk($regionQueue, 500) as $regionChunk)
			{
				$batch = new DB\Facade\BatchUpdate(Stamp\RepositoryTable::class);
				$batch->run([
					'filter' => [
						'=PUSH_ID' => $this->getPush()->getId(),
						'=ELEMENT_ID' => array_column($regionChunk, 'ELEMENT_ID'),
						'=REGION_ID' => $regionId,
					],
				], [
					'STATUS' => Stamp\RepositoryTable::STATUS_UNPUBLISHED,
                    'SUBMITTED_AT' => new Main\Type\DateTime(),
				]);
			}
		}
	}

	protected function splitUnknown(array $stampQueue, array $existsMap) : array
	{
		$result = [];

		foreach ($stampQueue as $row)
		{
			if (isset($existsMap[$row['PRIMARY']])) { continue; }

			$result[] = $row;
		}

		return $result;
	}
}