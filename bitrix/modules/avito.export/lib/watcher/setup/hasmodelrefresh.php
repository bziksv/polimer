<?php
namespace Avito\Export\Watcher\Setup;

use Bitrix\Main;
use Avito\Export\Watcher;

/**
 * @method string watcherType()
 */
trait HasModelRefresh
{
	public function hasFullRefresh() : bool
	{
		return $this->getRefreshPeriod() !== null;
	}

	public function hasRefreshTime() : bool
	{
		return $this->getRefreshTime() !== null;
	}

	public function getRefreshPeriod() : ?int
	{
		$period = parent::getRefreshPeriod();
		$result = null;

		if ($period > 0)
		{
			$result = $period;
		}

		return $result;
	}

	public function getRefreshTime() : ?array
	{
		$value = parent::getRefreshTime();
		$result = null;

		if ($value !== '' && preg_match('/^(\d{1,2})(?::(\d{1,2}))?$/', $value, $matches))
		{
			$result = [
				(int)$matches[1], // hour
				(int)$matches[2], // minutes
				0, // seconds
			];
		}

		return $result;
	}

	public function getRefreshNextExec() : Main\Type\DateTime
	{
		$interval = $this->getRefreshPeriod();
		$time = $this->getRefreshTime();
		$now = new Main\Type\DateTime();
		$nowTimestamp = $now->getTimestamp();

		$date = new Main\Type\DateTime();

		if ($time !== null && $interval > 0)
		{
			$date->setTime(...$time);

			if ($date->getTimestamp() > $nowTimestamp)
			{
				$date->add('-P1D');
			}

			while ($date->getTimestamp() <= $nowTimestamp)
			{
				$date->add('PT' . $interval . 'S');
			}
		}
		else
		{
			$date->add('PT' . $interval . 'S');
		}

		return $date;
	}

	public function handleRefresh(bool $direction) : void
	{
		$refreshPeriod = $this->getRefreshPeriod();

		$params = [
			'method' => 'start',
			'arguments' => [$this->watcherType(), $this->getId()],
		];

		if ($direction && $refreshPeriod > 0)
		{
			$params['interval'] = $refreshPeriod;
			$params['next_exec'] = $this->getRefreshNextExec()->toString();

			Watcher\Agent\Refresh::register($params);
		}
		else
		{
			Watcher\Agent\Routine::removeState($this->watcherType(), $this->getId(), 'refresh');
			Watcher\Agent\Refresh::unregister($params);
			Watcher\Agent\Refresh::unregister([
				'method' => 'process',
				'arguments' => [$this->watcherType(), $this->getId()],
			]);
		}
	}

	public function refreshStart(bool $hard = false) : void
	{
		$parameters = [
			'method' => 'process',
			'arguments' => [$this->watcherType(), $this->getId()],
			'interval' => 5,
		];

		if ($hard)
		{
			Watcher\Agent\Routine::removeState($this->watcherType(), $this->getId(), 'refresh');
			Watcher\Agent\Refresh::unregister($parameters);
		}

		Watcher\Agent\Refresh::register($parameters);
	}
}