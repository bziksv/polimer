<?php
namespace Avito\Export\Push\Engine\Steps;

use Avito\Export\Push;
use Avito\Export\Feed;
use Avito\Export\Logger;
use Avito\Export\Watcher;
use Avito\Export\Glossary;

abstract class Step implements Watcher\Engine\Step
{
	/** @var Push\Engine\Controller */
	protected $controller;
	protected $logger;

	public function __construct(Push\Engine\Controller $controller)
	{
		$this->controller = $controller;
		$this->logger = new Logger\Logger(Glossary::SERVICE_PUSH, $controller->getSetup()->getId());
	}

	public function after(string $action) : void
	{
		if ($action === Watcher\Engine\Controller::ACTION_CHANGE)
		{
			$this->afterChange();
		}
		else if ($action === Watcher\Engine\Controller::ACTION_REFRESH)
		{
			$this->afterRefresh();
		}
	}

	public function afterChange() : void
	{
		// nothing by default
	}

	public function afterRefresh() : void
	{
		// nothing by default
	}

	public function getParameter(string $name, $default = null)
	{
		return $this->controller->getParameter($name, $default);
	}

	public function getController() : Push\Engine\Controller
	{
		return $this->controller;
	}

	public function getPush() : Push\Setup\Model
	{
		return $this->controller->getSetup();
	}

	public function getFeed() : Feed\Setup\Model
	{
		return $this->getPush()->getExchange()->fillFeed();
	}
}