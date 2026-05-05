<?php
namespace Avito\Export\Push\Engine\Steps\Submitter;

use Avito\Export\Push;
use Avito\Export\Api;
use Avito\Export\Glossary;
use Avito\Export\Logger;
use Avito\Export\Concerns;
use Avito\Export\Watcher;
use Avito\Export\Push\Engine\Steps;
use Avito\Export\Push\Engine\Steps\Stamp;
use Avito\Export\DB;
use Bitrix\Main;

class Stocks implements Action
{
	use Concerns\HasLocale;

	protected $step;
	protected $logger;

	public function __construct(Steps\Submitter $step)
	{
		$this->step = $step;
		$this->logger = new Logger\Logger(Glossary::SERVICE_PUSH, $step->getPush()->getId());
	}

	public function process(Steps\Stamp\Collection $queue) : void
	{
		foreach ($queue->chunk(200) as $queueChunk)
		{
			$this->processChunk($queueChunk);

			if ($this->step->getController()->isTimeExpired())
			{
				throw new Watcher\Exception\TimeExpired($this->step);
			}
		}
	}

	protected function processChunk(Steps\Stamp\Collection $queue) : void
	{
		try
		{
			$deleted = $queue->filterStatus(Stamp\RepositoryTable::STATUS_DELETE);

			$this->bootLogger($queue);

			$stocks = $this->stocks($queue);
			$response = $this->submit($stocks);

			$this->commit($queue, $response);
			$this->releaseDeleted($deleted);

			$this->flushLogger();
		}
		catch (\Throwable $exception)
		{
			$this->increaseRepeat($queue);
			throw $exception;
		}
	}

	protected function bootLogger(Steps\Stamp\Collection $queue) : void
	{
		$used = [];

		foreach ($queue as $stamp)
		{
			$used[] = [
				'ENTITY_ID' => $stamp->getElementId(),
				'REGION_ID' => $stamp->getRegionId(),
			];
		}

		$this->logger->allowTouch();
		$this->logger->allowDelete();
		$this->logger->delayFlush();
		$this->logger->used(Glossary::ENTITY_STOCKS, $used);
	}

	protected function flushLogger() : void
	{
		$this->logger->flush();
	}

	protected function stocks(Stamp\Collection $queue) : array
	{
		$result = [];

		foreach ($queue as $row)
		{
			$result[] = [
				'item_id' => (int)$row->getServicePrimary()->getServiceId(),
				'quantity' => $this->normalizeQuantity($row->getValue()),
			];
		}

		return $result;
	}

	protected function normalizeQuantity($value) : int
	{
		return $value !== Push\Engine\Steps\Stamp\RepositoryTable::VALUE_NULL
			? min(999999, max(0, (int)$value))
			: 0;
	}

	protected function submit(array $stocks) : Api\StockManagement\V1\Stocks\Response
	{
		$settings = $this->step->getPush()->getSettings()->commonSettings();

		$client = new Api\StockManagement\V1\Stocks\Request();
		$client->token($settings->token());
		$client->stocks($stocks);

		return $client->execute();
	}

	protected function commit(Stamp\Collection $queue, Api\StockManagement\V1\Stocks\Response $response) : void
	{
		$mapResponse = $this->mapResponseStocks($response);

		foreach ($queue as $row)
		{
			$primary = $row->getServicePrimary()->getServiceId();
			$stock = $mapResponse[$primary] ?? null;
			$logContext = [
				'ENTITY_TYPE' => Glossary::ENTITY_STOCKS,
				'ENTITY_ID' => $row->getElementId(),
				'REGION_ID' => $row->getRegionId(),
			];

			$row->setSubmittedAt(new Main\Type\DateTime());

			if ($stock === null)
			{
				$message = self::getLocale('STOCK_MISSING');

				$row->setStatus(Stamp\RepositoryTable::STATUS_FAILED);
				$this->logger->error($message, $logContext);
			}
			else if (!$stock->success())
			{
				$message = implode(', ', $stock->errors()) ?: self::getLocale('STOCK_ERROR_UNDEFINED');

				$row->setStatus(Stamp\RepositoryTable::STATUS_FAILED);
				$this->logger->error($message, $logContext);
			}
			else if ($row->getValue() === Stamp\RepositoryTable::VALUE_NULL)
			{
				$message = self::getLocale('STOCK_NULLIFIED');

				$row->setStatus(Stamp\RepositoryTable::STATUS_READY);
				$this->logger->warning($message, $logContext);
			}
			else
			{
				$message = self::getLocale('STOCK_SEND', [
					'#COUNT#' => $this->normalizeQuantity($row->getValue()),
				]);

				$row->setStatus(Stamp\RepositoryTable::STATUS_READY);
				$this->logger->info($message, $logContext);
			}
		}

		$queue->save(true);
	}

	protected function releaseDeleted(Stamp\Collection $queue) : void
	{
		$filter = $queue->queryFilter();

		if ($filter === null) { return; }

		$batch = new DB\Facade\BatchDelete(Stamp\RepositoryTable::class);
		$batch->run([
			'filter' => [
				'=PUSH_ID' => $this->step->getPush()->getId(),
				$filter,
			],
		]);
	}

	/**
	 * @param Api\StockManagement\V1\Stocks\Response $response
	 *
	 * @return array<string, Api\StockManagement\V1\Stocks\Response\Stock>
	 */
	protected function mapResponseStocks(Api\StockManagement\V1\Stocks\Response $response) : array
	{
		$result = [];

		/** @var Api\StockManagement\V1\Stocks\Response\Stock $stock */
		foreach ($response->stocks() as $stock)
		{
			$result[$stock->itemId()] = $stock;
		}

		return $result;
	}

	protected function increaseRepeat(Stamp\Collection $queue) : void
	{
		$queue->increaseRepeat();
		$queue->save(true);
	}
}