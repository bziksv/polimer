<?php
namespace Avito\Export\Feed\Source\GroupProperty\Feature;

use Avito\Export\Concerns;
use Avito\Export\Feed\Source\Context;
use Avito\Export\Feed\Source\Registry;
use Bitrix\Main;
use Bitrix\Iblock;
use Bitrix\Catalog;

class Detail implements Feature
{
	use Concerns\HasLocale;

	public function id() : string
	{
		return 'CARD';
	}

	public function title() : string
	{
		return self::getLocale('TITLE');
	}

	public function properties(Context $context) : array
	{
		$features = $this->features($context->hasCatalog());
		$result = [
			Registry::IBLOCK_PROPERTY => $this->iblockProperties($context->iblockId(), $features)
		];

		if ($context->hasOffers())
		{
			$result[Registry::OFFER_PROPERTY] = $this->iblockProperties($context->offerIblockId(), $features);
		}

		return $result;
	}

	protected function iblockProperties(int $iblockId, array $features) : array
	{
		$iterator = Iblock\PropertyFeatureTable::getList([
			'select' => [ 'PROPERTY_ID' ],
			'filter' => [
				'=PROPERTY.IBLOCK_ID' => $iblockId,
				'=PROPERTY.ACTIVE' => 'Y',
				'!=PROPERTY.PROPERTY_TYPE' => Iblock\PropertyTable::TYPE_FILE,
				'=FEATURE_ID' => $features,
				'=IS_ENABLED' => 'Y',
			],
			'order' => [
				'PROPERTY.SORT' => 'ASC',
				'PROPERTY.ID' => 'ASC',
			],
		]);

		return array_column($iterator->fetchAll(), 'PROPERTY_ID', 'PROPERTY_ID');
	}

	protected function features(bool $hasCatalog) : array
	{
		$result = [
			Iblock\Model\PropertyFeature::FEATURE_ID_DETAIL_PAGE_SHOW,
		];

		if ($hasCatalog && Main\Loader::includeModule('catalog'))
		{
			$result[] = Catalog\Product\PropertyCatalogFeature::FEATURE_ID_OFFER_TREE_PROPERTY;
		}

		return $result;
	}
}