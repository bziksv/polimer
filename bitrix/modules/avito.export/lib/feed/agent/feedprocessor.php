<?php

namespace Avito\Export\Feed\Agent;

use Avito\Export;
use Avito\Export\Feed\Setup;
use Avito\Export\Feed\Engine;

class FeedProcessor extends Export\Watcher\Agent\Processor
{
	public function __construct(string $method, int $setupId)
	{
		parent::__construct($method, Export\Glossary::SERVICE_FEED, $setupId);
	}

	protected function process(string $action, array $parameters) : void
	{
		$feed = Setup\Model::getById($this->setupId);

		$controllerExport = new Engine\Controller($feed, $parameters);
		$controllerExport->export($action);
	}
}