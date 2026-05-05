<?php
namespace Avito\Export\Push\Setup;

use Avito\Export\Feed;
use Avito\Export\Push;
use Avito\Export\Exchange;
use Avito\Export\Concerns;
use Avito\Export\Watcher;
use Avito\Export\Glossary;

class Model
{
	use Concerns\HasLocale;
	use Watcher\Setup\HasModelRefresh;
	use Watcher\Setup\HasModelChanges;

	protected $exchange;
	protected $settings;

	public static function getById(int $exchangeId) : Model
	{
		$exchange = Exchange\Setup\Model::getById($exchangeId);

		return $exchange->getPush();
	}

	public function __construct(Exchange\Setup\Model $exchange, Settings $settings)
	{
		$this->exchange = $exchange;
		$this->settings = $settings;
	}

	public function getId() : ?int
	{
		return $this->exchange->getId();
	}

	public function getExchange() : Exchange\Setup\Model
	{
		return $this->exchange;
	}

	public function activate() : void
	{
		$this->handleChanges($this->getAutoUpdate());
		$this->handleRefresh($this->hasFullRefresh());
	}

	public function deactivate() : void
	{
		$this->handleChanges(false);
		$this->handleRefresh(false);
	}

	public function getSettings() : Settings
	{
		return $this->settings;
	}

	public function getAutoUpdate() : bool
	{
		return $this->getSettings()->autoUpdate();
	}

	public function getRefreshPeriod() : ?int
	{
		return $this->getSettings()->refreshPeriod();
	}

	public function getRefreshTime() : ?array
	{
		return $this->getSettings()->refreshTime();
	}

	protected function watcherType() : string
	{
		return Glossary::SERVICE_PUSH;
	}

	protected function bindChanges(Watcher\Watcher $watcher) : void
	{
		$feed = $this->exchange->fillFeed();

		if ($feed === null) { return; }

		$fieldMapCollection = $this->getSettings()->fieldMapCollection($feed);

		foreach ($feed->getIblock() as $iblockId)
		{
			$fieldMap = $fieldMapCollection->byIblockId($iblockId);

			$watcher->watch(
				$fieldMap->select()->sources(),
				$feed->iblockContext($iblockId)
			);
		}
	}
}