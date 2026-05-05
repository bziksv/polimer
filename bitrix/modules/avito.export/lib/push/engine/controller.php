<?php
namespace Avito\Export\Push\Engine;

use Bitrix\Main;
use Avito\Export\Push;
use Avito\Export\Watcher;

class Controller implements Watcher\Engine\Controller
{
	protected $setup;
	protected $parameters;
	protected $steps;
	protected $limitResource;

	public function __construct(Push\Setup\Model $setup, array $parameters = [])
	{
		$this->setup = $setup;
		$this->parameters = $parameters;
		$this->steps = [
			new Steps\Collector($this),
			new Steps\Mapper($this),
			new Steps\Submitter($this),
		];
		$this->limitResource = new Watcher\Engine\LimitResource([
			'START_TIME' => $this->getParameter('START_TIME'),
			'TIME_LIMIT' => $this->getParameter('TIME_LIMIT'),
		]);
	}

	public function export(string $action = self::ACTION_FULL) : void
	{
		$this->loadModules();
		$this->runStepper($action);
	}

	protected function loadModules() : void
	{
		if (!Main\Loader::includeModule('iblock'))
		{
			throw new Main\SystemException('cant load iblock module');
		}
	}

	protected function runStepper(string $action) : void
	{
		$stepper = new Watcher\Engine\Stepper($this->steps);

		$stepper->process($action, $this->getParameter('STEP'), $this->getParameter('OFFSET'));
	}

	public function getParameter(string $name, $default = null)
	{
		return $this->parameters[$name] ?? $default;
	}

	public function getSetup() : Push\Setup\Model
	{
		return $this->setup;
	}

	public function isTimeExpired() : bool
	{
		$this->limitResource->tick();

		return $this->limitResource->isExpired();
	}
}
