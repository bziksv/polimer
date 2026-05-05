<?php
namespace Avito\Export\Feed\Source;

use Avito\Export\Assert;
use Avito\Export\Config;
use Bitrix\Main;

class FetcherPool
{
	protected $pool = [];
	protected $loaded = [];

	public function __construct()
	{
		$this->pool = $this->sanitize($this->systemSources() + $this->userSources());
	}

	protected function systemSources() : array
	{
		return [
			Registry::IBLOCK_FIELD => new Element\Fetcher(),
			Registry::IBLOCK_PROPERTY => new ElementProperty\Fetcher(),
			Registry::SEO_FIELD => new ElementSeo\Fetcher(),
			Registry::OFFER_FIELD => new Offer\Fetcher(),
			Registry::OFFER_PROPERTY => new OfferProperty\Fetcher(),
			Registry::SECTION_PROPERTY => new Section\Fetcher(),
			Registry::CATALOG_FIELD => new Product\Fetcher(),
			Registry::PRICE_FIELD => new Price\Fetcher(),
			Registry::STORE_FIELD => new Store\Fetcher(),
			Registry::REGION => new Region\Fetcher(),
			Registry::TEMPLATE => new Template\Fetcher(),
			Registry::GROUP_PROPERTY => new GroupProperty\Fetcher(),
			Registry::AVITO_PROPERTY => new AvitoProperty\Fetcher(),
		];
	}

	protected function userSources() : array
	{
		$result = [];
		$event = new Main\Event(Config::getModuleName(), 'onFeedSourceBuild');
		$event->send();

		foreach ($event->getResults() as $eventResult)
		{
			if ($eventResult->getType() !== Main\EventResult::SUCCESS) { continue; }

			$eventData = $eventResult->getParameters();

			Assert::isArray($eventData, '$eventData');

			foreach ($eventData as $type => $className)
			{
				Assert::typeOf($className, Fetcher::class, sprintf('$eventData[%s]', $type));

				$result[$type] = $className;
			}
		}

		return $result;
	}

	/** @return array<string, Fetcher> */
	public function all() : array
	{
		$this->preloadAll();

		return $this->pool;
	}

	public function some(string $type) : Fetcher
	{
		if (!isset($this->pool[$type]))
		{
			return new NoValue\Fetcher();
		}

		$result = $this->pool[$type];

		$this->preload($type, $result);

		return $result;
	}

	/**
	 * @param array<string, Fetcher> $fetcherMap
	 *
	 * @return array<string, Fetcher>
	 */
	protected function sanitize(array $fetcherMap) : array
	{
		foreach ($fetcherMap as $type => $fetcher)
		{
			foreach ($fetcher->modules() as $module)
			{
				if (!Main\ModuleManager::isModuleInstalled($module))
				{
					unset($fetcherMap[$type]);
					break;
				}
			}
		}

		return $fetcherMap;
	}

	protected function preloadAll() : void
	{
		foreach ($this->pool as $type => $fetcher)
		{
			$this->preload($type, $fetcher);
		}
	}

	protected function preload(string $type, Fetcher $fetcher) : void
	{
		if (isset($this->loaded[$type])) { return; }

		foreach ($fetcher->modules() as $module)
		{
			if (!Main\Loader::includeModule($module))
			{
				throw new Main\SystemException(sprintf('cant load module %s', $module));
			}
		}

		$this->loaded[$type] = true;
	}
}