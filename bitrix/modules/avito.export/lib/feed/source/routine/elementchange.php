<?php

namespace Avito\Export\Feed\Source\Routine;

use Avito\Export\Feed;
use Avito\Export\Exchange;
use Avito\Export\Watcher;
use Avito\Export\Glossary;
use Bitrix\Main;

class ElementChange
{
	/** @var array */
	protected static $registered = [];
	/** @var array */
	protected static $iblockFeedsMap;
	/** @var array */
	protected static $iblockPushMap;

	public static function register(int $elementId, int $iblockId) : void
	{
		if (isset(static::$registered[$elementId])) { return; }

		static::$registered[$elementId] = true;

		static::registerFeeds($elementId, $iblockId);
		static::registerPush($elementId, $iblockId);
	}

	public static function needRegister(?int $elementId) : bool
	{
		return $elementId > 0 && !isset(static::$registered[$elementId]);
	}

	public static function isTargetIblock(int $iblockId, ?int $offerIblockId, $elementIblockId) : bool
	{
		return (
			$elementIblockId !== null
			&& (
				(int)$elementIblockId === $iblockId
				|| (int)$elementIblockId === $offerIblockId
			)
		);
	}

	public static function isTargetElement(int $iblockId, ?int $offerIblockId, $elementId): bool
	{
		if (!Main\Loader::includeModule('iblock')) { return false; }

		$elementIblockId = \CIBlockElement::GetIBlockByID($elementId) ?: null;

		return static::isTargetIblock($iblockId, $offerIblockId, $elementIblockId);
	}

	protected static function registerFeeds(int $elementId, int $iblockId) : void
	{
		foreach (static::iblockFeeds($iblockId) as $feedId)
		{
			Watcher\Engine\Changes::register(
				Glossary::SERVICE_FEED,
				$feedId,
				Glossary::ENTITY_OFFER,
				$elementId
			);
		}
	}

	protected static function iblockFeeds(int $iblockId) : array
	{
		$map = static::iblockFeedsMap();

		return $map[$iblockId] ?? [];
	}

	protected static function iblockFeedsMap() : array
	{
		if (static::$iblockFeedsMap !== null) { return static::$iblockFeedsMap; }

		$result = [];

		$query = Feed\Setup\RepositoryTable::getList([
			'filter' => ['=AUTO_UPDATE' => true],
			'select' => ['ID', 'IBLOCK'],
		]);

		while ($setup = $query->fetchObject())
		{
			foreach ($setup->getIblock() as $iblockId)
			{
				if (!isset($result[$iblockId]))
				{
					$result[$iblockId] = [];
				}

				$result[$iblockId][] = $setup->getId();
			}
		}

		static::$iblockFeedsMap = $result;

		return $result;
	}

	protected static function registerPush(int $elementId, int $iblockId) : void
	{
		foreach (static::iblockPush($iblockId) as $pushId)
		{
			Watcher\Engine\Changes::register(
				Glossary::SERVICE_PUSH,
				$pushId,
				Glossary::ENTITY_OFFER,
				$elementId
			);
		}
	}

	protected static function iblockPush(int $iblockId) : array
	{
		$map = static::iblockPushMap();

		return $map[$iblockId] ?? [];
	}

	protected static function iblockPushMap() : array
	{
		if (static::$iblockPushMap === null)
		{
			static::$iblockPushMap = static::loadIblockPushMap();
		}

		return static::$iblockPushMap;
	}

	protected static function loadIblockPushMap() : array
	{
		$feedMap = [];

		$queryExchange = Exchange\Setup\RepositoryTable::getList([
			'filter' => [ '=USE_PUSH' => true ],
		]);

		while ($exchange = $queryExchange->fetchObject())
		{
			if (!$exchange->settingsBridge()->pushSettings()->autoUpdate()) { continue; }

			$feedId = $exchange->getFeedId();

			if (!isset($feedMap[$feedId])) { $feedMap[$feedId] = []; }

			$feedMap[$feedId][] = $exchange->getId();
		}

		if (empty($feedMap)) { return []; }

		$result = [];

		$queryFeed = Feed\Setup\RepositoryTable::getList([
			'filter' => ['=ID' => array_keys($feedMap)],
			'select' => ['ID', 'IBLOCK'],
		]);

		/** @var Feed\Setup\Model $feed */
		while ($feed = $queryFeed->fetchObject())
		{
			foreach ($feed->getIblock() as $iblockId)
			{
				if (!isset($result[$iblockId])) { $result[$iblockId] = []; }

				foreach ($feedMap[$feed->getId()] as $exchangeId)
				{
					$result[$iblockId][] = (int)$exchangeId;
				}
			}
		}

		return $result;
	}
}