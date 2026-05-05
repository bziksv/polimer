<?php

namespace Avito\Export\Feed\Engine;

use Bitrix\Main;
use Avito\Export\Feed;
use Avito\Export\Watcher;

class Controller implements Watcher\Engine\Controller
{
	public const ACTION_RESTART = 'restart';

	/** @var Feed\Setup\Model */
	protected $feed;
	/** @var Writer\File */
	protected $writer;
	/** @var Steps\Step[] */
	protected $steps;
	/** @var Watcher\Engine\LimitResource */
	protected $limitResource;

	protected $parameters;

	public function __construct(Feed\Setup\Model $feed, array $parameters = [])
	{
		$this->feed = $feed;
		$this->parameters = $parameters;
		$this->steps = [
			new Steps\Root($this),
			new Steps\Offer($this),
		];
		$this->limitResource = new Watcher\Engine\LimitResource([
			'START_TIME' => $this->getParameter('START_TIME'),
			'TIME_LIMIT' => $this->getParameter('TIME_LIMIT'),
		]);
	}

	public function loadModules() : void
	{
		if (!Main\Loader::includeModule('iblock'))
		{
			throw new Main\SystemException('cant load iblock module');
		}
	}

	public function getFeed(): Feed\Setup\Model
	{
		return $this->feed;
	}

	public function getWriter(): Writer\File
	{
		if ($this->writer === null)
		{
			$this->writer = $this->loadWriter();
		}

		return $this->writer;
	}

	protected function loadWriter(): Writer\File
	{
		$filePath = $this->getFeed()->getFileAbsolutePath();
		$useTmp = $this->getParameter('USE_TMP') ?? false;

		return new Writer\File($filePath, $useTmp);
	}

	public function getParameter(string $name, $default = null)
	{
		return $this->parameters[$name] ?? $default;
	}

	public function clear($isStrict = false) : void
	{
		$this->loadModules();

		foreach ($this->steps as $step)
		{
			$step->clear($isStrict);
		}
	}

	private function wait() : void
	{
		foreach ($this->steps as $step)
		{
			$step->wait();
		}
	}

	public function export(string $action = self::ACTION_FULL) : void
	{
		try
		{
			$this->loadModules();

			if ($action === static::ACTION_FULL && $this->getParameter('STEP') === null)
			{
				$this->clear();
			}

			if ($action === static::ACTION_RESTART && $this->getParameter('STEP') === null)
			{
				$this->wait();
			}

			if (!$this->getWriter()->lock())
			{
				throw new Watcher\Exception\LockFailed();
			}

			$this->runStepper($action);
			$this->finalize();
			$this->getWriter()->unlock();
		}
		catch (Watcher\Exception\TimeExpired $exception)
		{
			$this->getWriter()->unlock();
			throw $exception;
		}
	}

	protected function runStepper(string $action) : void
	{
		$stepper = new Watcher\Engine\Stepper($this->steps);

		$stepper->process($action, $this->getParameter('STEP'), $this->getParameter('OFFSET'));
	}

	public function finalize(): void
	{
		/** @var Steps\Root $rootStep */
		$rootStep = $this->steps[0];
		$rootStep->finalize();
	}

	public function isTimeExpired(): bool
	{
		$this->limitResource->tick();

		return $this->limitResource->isExpired();
	}
}