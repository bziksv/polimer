<?php
namespace Avito\Export\Trading\Queue;

use Avito\Export;
use Avito\Export\Trading;
use Avito\Export\Watcher;
use Bitrix\Main;

class Repeater
{
	private const INTERVAL_DELAY = [ 60, 600, 3600, 7200, 21600 ];

	protected $setupCache = [];

	public function nearestInterval() : ?int
	{
		$result = null;

		$query = Table::getList([
			'filter' => [
				'<=EXEC_COUNT' => $this->repeatLimit(),
			],
			'limit' => 1,
			'select' => [ 'EXEC_DATE' ],
			'order' => [ 'EXEC_DATE' => 'ASC' ]
		]);

		if ($row = $query->fetch())
		{
			/** @var Main\Type\Date $execDate */
			$execDate = $row['EXEC_DATE'];
			$result = max(0, $execDate->getTimestamp() - time());
		}

		return $result;
	}

	public function processQueue() : void
	{
		$limitResource = new Watcher\Engine\LimitResource();

		$query = Table::getList([
			'filter' => [
				'<=EXEC_DATE' => new Main\Type\DateTime(),
				'<=EXEC_COUNT' => $this->repeatLimit(),
			],
			'order' => [ 'ID' => 'ASC' ],
			'limit' => $this->processLimit(),
		]);

		while ($row = $query->fetch())
		{
			$limitResource->tick();

			if ($limitResource->isExpired()) { return; }

			$this->processRow($row);
		}
	}

	protected function processRow(array $row) : void
	{
		try
		{
			$setup = $this->setup((int)$row['SETUP_ID']);

			$procedure = new Export\Trading\Action\Procedure($setup, (string)$row['PATH'], (array)$row['DATA']);
			$procedure->run();

			Table::delete($row['ID']);
		}
		catch (Main\ObjectNotFoundException $exception)
		{
			Table::delete($row['ID']);
		}
		catch (\Throwable $exception)
		{
			if (isset($procedure))
			{
				$procedure->logException($exception);
			}

			if (
				$row['EXEC_COUNT'] >= $this->repeatLimit()
				|| ($exception instanceof Export\Api\Exception\HttpError && $exception->badFormatted())
			)
			{
				Table::delete($row['ID']);
				return;
			}

			$interval = $this->nextInterval((int)$row['INTERVAL']);

			$nextExecDate = new Main\Type\DateTime();
			$nextExecDate->add(sprintf('PT%sS', $interval));

			Table::update($row['ID'], [
				'EXEC_DATE' => $nextExecDate,
				'EXEC_COUNT' => $row['EXEC_COUNT'] + 1,
				'INTERVAL' => $interval,
			]);
		}
	}

	protected function setup(int $id) : Trading\Setup\Model
	{
		if (!array_key_exists($id, $this->setupCache))
		{
			$this->setupCache[$id] = Trading\Setup\Model::getById($id);
		}

		return $this->setupCache[$id];
	}

	protected function processLimit() : int
	{
		return max(1, (int)Export\Config::getOption('trading_repeat_process', 10));
	}

	protected function repeatLimit() : int
	{
		return (int)Export\Config::getOption('trading_repeat_limit', 5);
	}

	protected function nextInterval(int $interval) : int
	{
		$result = $interval;
		$found = false;

		foreach (static::INTERVAL_DELAY as $delay)
		{
			$result = $delay;

			if ($found) { break; }

			$found = ($interval <= $delay);
		}

		return $result;
	}
}