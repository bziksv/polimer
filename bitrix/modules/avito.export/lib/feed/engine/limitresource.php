<?php

namespace Avito\Export\Feed\Engine;

class LimitResource
{
	protected $startTime;
	protected $timeLimit;
	protected $systemTimeLimit;
	protected $tickStartTime;
	protected $tickDuration;
	protected $isTickContainsInitialization;

	public function __construct(array $parameters = null)
	{
		$this->initializeTime($parameters);
	}

	public function isExpired() : bool
	{
		return ($this->isExpiredTime());
	}

	public function tick() : void
	{
		$allowOverride = $this->isPreviousTickContainsInitialization();

		$this->tickTime($allowOverride);
	}

	protected function isPreviousTickContainsInitialization() : bool
	{
		$result = false;

		if ($this->isTickContainsInitialization === null) // is initialization step
		{
			$this->isTickContainsInitialization = true;
		}
		else if ($this->isTickContainsInitialization === true) // is after initialization step
		{
			$this->isTickContainsInitialization = false;
			$result = true;
		}

		return $result;
	}

	protected function initializeTime($parameters): void
	{
		$timeLimitParameter = $parameters['TIME_LIMIT'] ?? null;

		$this->startTime = $parameters['START_TIME'] ?? microtime(true);
		$this->timeLimit = $this->normalizeTimeLimit($timeLimitParameter);
		$this->tickStartTime = microtime(true);
	}

	protected function isExpiredTime() : bool
	{
		$now = microtime(true);
		$nextTickFinishTime = ($now + $this->tickDuration);
		$expireTime = $this->startTime + $this->timeLimit;
		$timeGap = $this->getTimeGap();

		return ($nextTickFinishTime + $timeGap >= $expireTime);
	}

	protected function tickTime($allowOverride = false) : void
	{
		$now = microtime(true);
		$duration = $now - $this->tickStartTime;

		if ($allowOverride || $this->tickDuration === null || $duration > $this->tickDuration)
		{
			$this->tickDuration = $duration;
		}

		$this->tickStartTime = $now;
	}

	protected function getTimeGap()
	{
		$systemLimit = $this->getSystemTimeLimit();
		$result = 0;

		if ($systemLimit > 0 && $this->timeLimit + $this->tickDuration >= $systemLimit)
		{
			$result = min(max(2, $this->tickDuration * 0.5), 5);
		}

		return $result;
	}

	protected function normalizeTimeLimit($timeLimit)
	{
		$result = max(0, (int)$timeLimit);
		$isNotSetParameter = ($result === 0);
		$systemLimit = $this->getSystemTimeLimit();

		if ($systemLimit > 0 && ($isNotSetParameter || $result > $systemLimit))
		{
			$result = $systemLimit;
		}
		else if ($isNotSetParameter)
		{
			$result = 30;
		}

		return $result;
	}

	protected function getSystemTimeLimit() : int
	{
		if ($this->systemTimeLimit === null)
		{
			$this->systemTimeLimit = $this->fetchSystemTimeLimit();
		}

		return $this->systemTimeLimit;
	}

	protected function fetchSystemTimeLimit(): int
	{
		return (int)ini_get('max_execution_time');
	}
}