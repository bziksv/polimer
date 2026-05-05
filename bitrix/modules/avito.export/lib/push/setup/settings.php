<?php
namespace Avito\Export\Push\Setup;

use Avito\Export\Concerns;
use Avito\Export\Data\Number;
use Avito\Export\Exchange;
use Avito\Export\Feed;
use Avito\Export\Feed\Source;
use Avito\Export\Glossary;
use Avito\Export\Admin\UserField\BooleanType;
use Avito\Export\Watcher\Setup\RefreshFacade;
use Bitrix\Main;

class Settings extends Exchange\Setup\SettingsSkeleton
{
	use Concerns\HasOnce;
	use Concerns\HasLocale;

	protected $values;
	protected $settingsBridge;

	public function __construct(Exchange\Setup\SettingsBridge $settingsBridge, array $values = [])
	{
		parent::__construct($values);
		$this->settingsBridge = $settingsBridge;
	}

	public function commonSettings() : Exchange\Setup\Settings
	{
		return $this->settingsBridge->commonSettings();
	}

	public function fieldMapCollection(Feed\Setup\Model $feed) : FieldMapCollection
	{
		return $this->once('sources-' . $feed->getId(), function() use ($feed) {
			return FieldMapCollection::fromSettings(array_filter(
				$this->stocksFieldMap()
				+ $this->pricesFieldMap($feed)
				+ $this->avitoIdFieldMap($feed)
			));
		});
	}

	protected function stocksFieldMap() : array
	{
		$quantity = $this->value('QUANTITY_FIELD');
		$result = [
			Glossary::ENTITY_STOCKS => $quantity,
		];

		if (!is_array($quantity)) { return $result; }

		foreach ($quantity as $iblockId => $fields)
		{
			if (is_array($fields) && !$this->onlyTotalQuantity($fields))
			{
				$result[Glossary::ENTITY_STOCKS_LIMIT][$iblockId] = [ Source\Registry::CATALOG_FIELD . '.TRACE_QUANTITY' ];
			}
		}

		return $result;
	}

	protected function pricesFieldMap(Feed\Setup\Model $feed) : array
	{
		if (!$this->usePrices()) { return []; }

		$iblockSources = [];

		foreach ($feed->getIblock() as $iblockId)
		{
			$tagMap = $feed->getTagMap($iblockId);
			$priceMap = $tagMap->one('Price');

			if ($priceMap === null) { continue; }

			$iblockSources[$iblockId] = [ $priceMap ];
		}

		return [
			Glossary::ENTITY_PRICE => $iblockSources,
		];
	}

	protected function avitoIdFieldMap(Feed\Setup\Model $feed) : array
	{
		$iblockSources = [];

		foreach ($feed->getIblock() as $iblockId)
		{
			$tagMap = $feed->getTagMap($iblockId);
			$avitoIdMap = $tagMap->one('AvitoId');

			if ($avitoIdMap === null) { continue; }

			$iblockSources[$iblockId] = [ $avitoIdMap ];
		}

		return [
			Glossary::ENTITY_AVITO_ID => $iblockSources,
		];
	}

	public function usePrices() : bool
	{
		return (string)$this->value('USE_PRICES') === BooleanType::VALUE_Y;
	}

	public function autoUpdate() : bool
	{
		return (string)$this->value('AUTO_UPDATE') === BooleanType::VALUE_Y;
	}

	public function refreshPeriod() : ?int
	{
		return Number::cast($this->value('REFRESH_PERIOD'));
	}

	public function refreshTime() : ?array
	{
		$value = $this->value('REFRESH_TIME');
		$result = null;

		if ($value !== '' && preg_match('/^(\d{1,2})(?::(\d{1,2}))?$/', $value, $matches))
		{
			$result = [
				(int)$matches[1], // hour
				(int)$matches[2], // minutes
				0, // seconds
			];
		}

		return $result;
	}

	public function fields() : array
	{
		return
			$this->quantityFields()
			+ $this->priceFields()
			+ $this->agentFields();
	}

	protected function quantityFields() : array
	{
		$catalogType = Main\ModuleManager::isModuleInstalled('catalog') ? '_CATALOG' : '_START';

		return [
			'QUANTITY_FIELD' => [
				'TYPE' => 'fetcherField',
				'MANDATORY' => 'Y',
				'MULTIPLE' => 'Y',
				'NAME' => self::getLocale('QUANTITY_FIELD'),
				'GROUP' => self::getLocale('GROUP_PUSH'),
				'GROUP_DESCRIPTION' => self::getLocale('GROUP_PUSH_DESCRIPTION' . $catalogType),
				'SETTINGS' => [
					'DEFAULT_VALUE' => Source\Registry::CATALOG_FIELD . '.QUANTITY',
					'IBLOCK_METHOD' => 'feed',
					'IBLOCK_MULTIPLE' => 'Y',
					'FEED_FIELD' => 'FEED_ID',
					'FILTERED' => [
						Source\Registry::CATALOG_FIELD => [ 'QUANTITY' ],
						Source\Registry::REGION => [ 'STORE' ],
					],
					'DISABLED' => [
						Source\Registry::IBLOCK_FIELD,
						Source\Registry::OFFER_FIELD,
						Source\Registry::SEO_FIELD,
						Source\Registry::SECTION_PROPERTY,
						Source\Registry::PRICE_FIELD,
						Source\Registry::GROUP_PROPERTY,
						Source\Registry::AVITO_PROPERTY,
					],
					'TYPE' => [ 'N', 'S' ],
				],
			],
		];
	}

	protected function priceFields() : array
	{
		return [
			'USE_PRICES' => [
				'TYPE' => 'boolean',
				'NAME' => self::getLocale('USE_PRICES'),
				'HELP_MESSAGE' => self::getLocale('USE_PRICES_HELP'),
				'SETTINGS' => [
					'DEFAULT_VALUE' => BooleanType::VALUE_N,
				],
			],
		];
	}

	protected function agentFields() : array
	{
		return [
			'AUTO_UPDATE' => [
				'TYPE' => 'boolean',
				'NAME' => self::getLocale('AUTO_UPDATE'),
				'HELP_MESSAGE' => self::getLocale('AUTO_UPDATE_HELP'),
				'GROUP' => self::getLocale('GROUP_AGENT'),
				'GROUP_DESCRIPTION' => self::getLocale('GROUP_AGENT_DESCRIPTION'),
				'SETTINGS' => [
					'DEFAULT_VALUE' => BooleanType::VALUE_Y,
				],
			],
			'REFRESH_PERIOD' => RefreshFacade::periodField([
				'TYPE' => 'enumeration',
				'NAME' => self::getLocale('REFRESH_PERIOD'),
				'HELP_MESSAGE' => self::getLocale('REFRESH_PERIOD_HELP'),
				'SETTINGS' => [
					'DEFAULT_VALUE' => RefreshFacade::PERIOD_SIX_HOURS,
				],
			]),
			'REFRESH_TIME' => RefreshFacade::timeField([
				'TYPE' => 'time',
				'NAME' => self::getLocale('REFRESH_TIME'),
			]),
		];
	}

	protected function onlyTotalQuantity(array $quantity) : bool
	{
		$quantityField = Source\Registry::CATALOG_FIELD . '.QUANTITY';

		return count($quantity) === 1 && reset($quantity) === $quantityField;
	}
}