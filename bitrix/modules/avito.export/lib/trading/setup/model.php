<?php
namespace Avito\Export\Trading\Setup;

use Avito\Export\Assert;
use Avito\Export\Exchange;
use Avito\Export\Glossary;
use Avito\Export\Logger;
use Avito\Export\Psr;
use Avito\Export\Trading\Action;
use Avito\Export\Trading\Entity;
use Avito\Export\Trading\Service;
use Avito\Export\Trading\State;

class Model
{
	protected $exchange;
	protected $settings;
	protected $environment;
	protected $service;

	public static function getById(int $exchangeId) : Model
	{
		$exchange = Exchange\Setup\Model::getById($exchangeId);
		$trading = $exchange->getTrading();

		Assert::notNull($trading, 'trading');

		return $trading;
	}

	public function __construct(Exchange\Setup\Model $exchange, Settings $settings)
	{
		$this->exchange = $exchange;
		$this->settings = $settings;
		$this->environment = Entity\Registry::environment();
		$this->service = Service\Registry::service();
	}

	public function getId() : ?int
	{
		return $this->exchange->getId();
	}

	public function getExchange() : Exchange\Setup\Model
	{
		return $this->exchange;
	}

	public function getSettings() : Settings
	{
		return $this->settings;
	}

	public function getEnvironment() : Entity\Sale\Container
	{
		return $this->environment;
	}

	public function getService() : Service\Container
	{
		return $this->service;
	}

	public function makeLogger() : Psr\Logger\LoggerInterface
	{
		$result = new Logger\Logger(Glossary::SERVICE_TRADING, $this->getId());
		$result->allowTouch();

		return $result;
	}

	public function activate() : void
	{
		$this->toggleAgents(true);
		$this->installListener();
		$this->installPlatform();
		$this->installUser();
		$this->installCleaner();
	}

	public function deactivate() : void
	{
		$this->toggleAgents(false);

		if (!$this->someoneUsedEnvironment())
		{
			$this->uninstallListener();
			$this->uninstallCleaner();
		}
	}

	protected function toggleAgents(bool $direction) : void
	{
		$agents = [
			Action\OrderAccept\Agent::class,
			Action\OrderStatus\Agent::class,
		];

		/** @var Action\OrderAccept\Agent|Action\OrderStatus\Agent $agent */
		foreach ($agents as $agent)
		{
			if ($direction)
			{
				$agent::register([
					'method' => 'start',
					'arguments' => [ (int)$this->getId() ],
				]);
			}
			else
			{
				$agent::unregister([
					'method' => 'start',
					'arguments' => [ (int)$this->getId() ],
				]);

				$agent::unregister([
					'method' => 'process',
					'arguments' => [ (int)$this->getId() ],
				]);
			}
		}
	}

	protected function someoneUsedEnvironment() : bool
	{
		$query = Exchange\Setup\RepositoryTable::getList([
			'select' => [ 'ID' ],
			'filter' => [
				'!=ID' => $this->getId(),
				'=USE_TRADING' => true,
			],
			'limit' => 1,
		]);

		return (bool)$query->fetch();
	}

	protected function installListener() : void
	{
		foreach ($this->environment->listeners() as $listener)
		{
			$listener->install();
		}
	}

	protected function uninstallListener() : void
	{
		foreach ($this->environment->listeners() as $listener)
		{
			$listener->uninstall();
		}
	}

	protected function installPlatform() : void
	{
		$installResult = $this->environment->platform()->install();

		Assert::result($installResult);
	}

	protected function installUser() : void
	{
		$anonymousUser = $this->environment->anonymousUser();

		if ($anonymousUser->id() !== null) { return; }

		$allSites = $this->getExchange()->fillFeed()->allSites();

		$installResult = $anonymousUser->install((string)reset($allSites));

		Assert::result($installResult);
	}

	protected function installCleaner() : void
	{
		State\Cleaner::install();
	}

	protected function uninstallCleaner() : void
	{
		State\Cleaner::uninstall();
	}
}