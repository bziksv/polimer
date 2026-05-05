<?php
namespace Avito\Export\Trading\Action\Reference;

use Avito\Export\Trading;

abstract class Action
{
	protected $trading;
	protected $settings;
	protected $command;
	protected $environment;
	protected $service;
	protected $logger;

	public function __construct(Trading\Setup\Model $trading, Command $command)
	{
		$this->trading = $trading;
		$this->command = $command;
		$this->settings = $trading->getSettings();
		$this->logger = $trading->makeLogger();
		$this->environment = $trading->getEnvironment();
		$this->service = $trading->getService();
	}

	abstract public function process() : void;
}