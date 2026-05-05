<?php
namespace Avito\Export\Feed\Setup;

use Bitrix\Main;
use Avito\Export\Feed;
use Avito\Export\Concerns;
use Avito\Export\Watcher;
use Avito\Export\Glossary;
use Avito\Export\Data;
use Avito\Export\Assert;

class Model extends EO_Repository
{
	use Concerns\HasLocale;
	use Watcher\Setup\HasModelRefresh;
	use Watcher\Setup\HasModelChanges;

	public static function getById(int $feedId) : Model
	{
		/** @var Model $model */
		$model = static::$dataClass::getById($feedId)->fetchObject();

		if ($model === null)
		{
			throw new Main\ObjectNotFoundException(self::getLocale('NOT_FOUND', [
				'#ID#' => $feedId,
			]));
		}

		return $model;
	}

	public function allSites() : array
	{
		return parent::getSite();
	}

	public function getSite(int $iblockId) : string
	{
		$mapIblockToSite = parent::getSite();

		Assert::notNull($mapIblockToSite[$iblockId], sprintf('$mapIblockToSite[%s]', $iblockId));

		return $mapIblockToSite[$iblockId];
	}

	public function getPrimaryDomain() : ?string
	{
		$iblocks = $this->getIblock();
		$firstIblockId = reset($iblocks);

		return $this->getDomain($firstIblockId);
	}

	public function getDomain(int $iblockId) : ?string
	{
		$siteId = $this->getSite($iblockId);
		$domain = (Data\SiteDomain::host($siteId) ?: Main\Config\Option::get('main', 'server_name'));

		if ($domain === '') { return null; }

		/** @noinspection HttpUrlsUsage */
		$protocol = $this->getHttps() ? 'https://' : 'http://';

		return $protocol . $domain;
	}

	public function getFilterCollection(int $iblockId) : FilterMapCollection
	{
		$filters = $this->getFilter();

		return new FilterMapCollection($filters[$iblockId] ?? []);
	}

	/** @deprecated */
	public function getFilterMap(int $iblockId): FilterMap
	{
		trigger_error('use getFilterCollection', E_USER_WARNING);

		return $this->getFilterCollection($iblockId)->offsetGet(0) ?? new FilterMap([]);
	}

	public function getTagMap(int $iblockId): TagMap
	{
		$tags = $this->getTags();

		return new TagMap($tags[$iblockId] ?? []);
	}

	public function getCategoryLimitMap(): CategoryLimitMap
	{
		$categoryLimit = $this->getCategoryLimit();

		return new CategoryLimitMap($categoryLimit ?? []);
	}

	/** @noinspection HttpUrlsUsage */
	public function getUrl() : string
	{
		$domain = $this->getPrimaryDomain();

		if ($domain === null)
		{
			/** @noinspection NullPointerExceptionInspection */
			$request = Main\Context::getCurrent()->getRequest();
			$domain =
				($request->isHttps() ? 'https://' : 'http://')
				. $request->getHttpHost();
		}

		return $domain . $this->getFileRelativePath();
	}

	public function getFileRelativePath(): string
	{
		return BX_ROOT . '/catalog_export/' . $this->getFileName();
	}

	public function getFileAbsolutePath(): string
	{
		$relativePath = $this->getFileRelativePath();

		return Main\IO\Path::convertRelativeToAbsolute($relativePath);
	}

	public function activate() : void
	{
		if ($this->isRefreshTooLong())
		{
			$this->handleRestart($this->hasFullRefresh());
			$this->handleRefresh(false);
			$this->handleChanges(false);
		}
		else
		{
			$this->handleRestart(false);
			$this->handleRefresh($this->hasFullRefresh());
			$this->handleChanges($this->getAutoUpdate());
		}
	}

	public function pause() : void
	{
		$this->handleRefresh(false);
		$this->handleRestart(false);

		Watcher\Engine\Changes::releaseAll(Glossary::SERVICE_FEED, $this->getId());
	}

	public function deactivate() : void
	{
		$this->handleRefresh(false);
		$this->handleChanges(false);
	}

	public function isRefreshTooLong() : bool
	{
		$path = $this->getFileAbsolutePath();
		$file = new Main\IO\File($path);

		return $file->isExists() && $file->getSize() > 5 * (10 ** 6); // more 5MB
	}

	protected function handleRestart(bool $direction) : void
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

			Feed\Agent\Restart::register($params);
		}
		else
		{
			Watcher\Agent\Routine::removeState($this->watcherType(), $this->getId(), 'restart');
			Feed\Agent\Restart::unregister($params);
			Feed\Agent\Restart::unregister([
				'method' => 'process',
				'arguments' => [$this->watcherType(), $this->getId()],
			]);
		}
	}

	protected function watcherType() : string
	{
		return Glossary::SERVICE_FEED;
	}

	protected function bindChanges(Watcher\Watcher $watcher) : void
	{
		foreach ($this->getIblock() as $iblockId)
		{
			$context = $this->iblockContext($iblockId);
			$sources = array_unique(array_merge(
				array_keys($this->getFilterCollection($iblockId)->sources()),
				$this->getTagMap($iblockId)->select()->sources()
			));

			$watcher->watch($sources, $context);
		}
	}

	public function iblockContext(int $iblockId) : Feed\Source\Context
	{
		return new Feed\Source\Context($iblockId, $this->getSite($iblockId), $this->getRegion());
	}
}