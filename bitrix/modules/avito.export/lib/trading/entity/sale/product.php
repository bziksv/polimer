<?php
namespace Avito\Export\Trading\Entity\Sale;

use Bitrix\Iblock;
use Bitrix\Catalog;
use Avito\Export\Assert;
use Avito\Export\Concerns;
use Avito\Export\Feed\Source;
use Avito\Export\Feed\Setup as FeedSetup;
use Avito\Export\Feed\Engine\Steps\Offer\Table as FeedExportedTable;
use Avito\Export\Utils;

class Product
{
	use Concerns\HasOnce;

	protected $environment;
	protected $fetcherPool;

	public function __construct(Container $environment)
	{
		$this->environment = $environment;
		$this->fetcherPool = new Source\FetcherPool();
	}

	public function sites(array $iblockSiteMap, array $productIds) : array
	{
		$result = [];
		$iblockIds = $this->productIblocks($productIds);
		$iblockIds = $this->catalogIblocks($iblockIds);

		foreach ($iblockIds as $iblockId)
		{
			if (!isset($iblockSiteMap[$iblockId])) { continue; }

			$siteId = $iblockSiteMap[$iblockId];

			$result[$siteId] = $siteId;
		}

		return array_values($result);
	}

	protected function productIblocks(array $productIds) : array
	{
		if (empty($productIds)) { return []; }

		$query = Iblock\ElementTable::getList([
			'filter' => [ '=ID' => $productIds ],
			'group' => [ 'IBLOCK_ID' ],
			'select' => [ 'IBLOCK_ID' ],
		]);

		return array_column($query->fetchAll(), 'IBLOCK_ID');
	}

	protected function catalogIblocks(array $iblockIds) : array
	{
		if (empty($iblockIds)) { return []; }

		$result = [];

		foreach ($iblockIds as $iblockId)
		{
			$catalog = \CCatalogSku::GetInfoByIBlock($iblockId);
			$catalogType = $catalog['CATALOG_TYPE'] ?? null;

			if ($catalogType === \CCatalogSku::TYPE_OFFERS)
			{
				$productIblockId = (int)$catalog['PRODUCT_IBLOCK_ID'];

				$result[$productIblockId] = $productIblockId;
			}
			else
			{
				$result[$iblockId] = $iblockId;
			}
		}

		return array_values($result);
	}

	public function find(FeedSetup\Model $feed, array $externalIds) : array
	{
		$result = $this->findExported($feed, $externalIds);
		$result += $this->findStored($feed, array_diff($externalIds, array_keys($result)));

		return $result;
	}

	protected function findExported(FeedSetup\Model $feed, array $externalIds) : array
	{
		if (empty($externalIds)) { return []; }

		$result = [];

		$query = FeedExportedTable::getList([
			'filter' => [
				'=FEED_ID' => $feed->getId(),
				'=PRIMARY' => $externalIds,
				'!=STATUS' => FeedExportedTable::STATUS_FAIL,
			],
			'select' => [
				'PRIMARY',
				'ELEMENT_ID',
			],
		]);

		while ($row = $query->fetch())
		{
			$result[$row['PRIMARY']] = $row['ELEMENT_ID'];
		}

		return $result;
	}

	protected function findStored(FeedSetup\Model $feed, array $externalIds) : array
	{
		$iblockIds = $feed->getIblock();

		if (!is_array($iblockIds)) { return []; }

		$externalMap = $this->truncateRegionIds($feed, $externalIds);
		$result = [];

		foreach ($iblockIds as $iblockId)
		{
			if (empty($externalMap)) { break; }

			$tagMap = $feed->getTagMap($iblockId);
			$idSource = $tagMap->one('Id');

			if ($idSource === null) { continue; }

			$found = $this->mapStored($iblockId, $idSource['TYPE'], $idSource['FIELD'], array_values($externalMap));

			foreach ($externalMap as $externalId => $storedId)
			{
				if (!isset($found[$storedId])) { continue; }

				$result[$externalId] = $found[$storedId];
			}

			$externalMap = array_diff_key($externalMap, $result);
		}

		return $result;
	}

	protected function truncateRegionIds(FeedSetup\Model $feed, array $externalIds) : array
	{
		/** @noinspection PhpCastIsUnnecessaryInspection */
		$regionIblockId = (int)$feed->getRegion();

		if ($regionIblockId <= 0) { return array_combine($externalIds, $externalIds); }

		$result = [];

		foreach ($externalIds as $externalId)
		{
			$delimiterPosition = mb_strrpos($externalId, '-');

			if ($delimiterPosition === false)
			{
				$result[$externalId] = $externalId;
			}
			else
			{
				$result[$externalId] = mb_substr($externalId, 0, $delimiterPosition);
			}
		}

		return $result;
	}

	protected function mapStored(int $iblockId, string $sourceType, string $sourceField, array $externalIds) : array
	{
		/** @var Source\FetcherInvertible $fetcher */
		$fetcher = $this->fetcherPool->some($sourceType);

		if (!($fetcher instanceof Source\FetcherInvertible)) { return []; }

		$context = new Source\Context($iblockId);
		$elements = $fetcher->elements($externalIds, $sourceField, $context);

		if ($context->hasOffers())
		{
			$skuIds = $this->filterSku(array_values($elements));
			$skuOffers = $this->mapSkuFirstOffer($skuIds, $context);

			$elements = $this->combineSkuElements($elements, $skuOffers);
		}

		return $elements;
	}

	protected function filterSku(array $elements) : array
	{
		if (empty($elements)) { return []; }

		$result = [];

		$iterator = Catalog\ProductTable::getList([
			'filter'=> [
				'=ID' => $elements,
				'=TYPE' => Catalog\ProductTable::TYPE_SKU,
			],
			'select' => [ 'ID' ],
		]);

		while ($product = $iterator->fetch())
		{
			$result[] = (int)$product['ID'];
		}

		return $result;
	}

	protected function mapSkuFirstOffer(array $skuIds, Source\Context $context) : array
	{
		if (empty($skuIds)) { return []; }

		$result = array_fill_keys($skuIds, null);

		$skuOffers = \CCatalogSku::getOffersList($skuIds, $context->iblockId());

		foreach ($skuOffers as $skuId => $offers)
		{
			$firstOffer = reset($offers);

			if ($firstOffer === false) { continue; }

			$result[$skuId] = (int)$firstOffer['ID'];
		}

		return $result;
	}

	protected function combineSkuElements(array $elements, array $skuOffers) : array
	{
		if (empty($skuOffers)) { return $elements; }

		foreach ($elements as $externalId => $elementId)
		{
			if (!array_key_exists($elementId, $skuOffers)) { continue; }

			$offerId = $skuOffers[$elementId];

			if ($offerId === null)
			{
				unset($elements[$externalId]);
			}
			else
			{
				$elements[$externalId] = $offerId;
			}
		}

		return $elements;
	}

	public function basketData(array $productIds, string $siteId) : array
	{
		$result = [];

		$elements = $this->iblockElements($productIds);
		$products = $this->catalogProducts($productIds);
		$offerElements = array_intersect_key($elements, $this->filterProductOffers($products));
		$offerParentMap = $this->offerParentMap($offerElements);
		$offerProperties = $this->offerProperties($offerElements, $siteId);
		$parents = $this->iblockElements(array_unique($offerParentMap));

		foreach ($productIds as $productId)
		{
			if (!isset($elements[$productId])) { continue; }

			$element = $elements[$productId];
			$parent = null;

			if (isset($offerParentMap[$productId]))
			{
				$parent = $parents[$offerParentMap[$productId]] ?? null;
			}

			$result[$productId] = $this->mergeBasketData(
				$this->makeBasketDefaults($element, $parent),
				$this->makeBasketProperties($offerProperties[$productId] ?? [])
			);
		}

		return $result;
	}

	protected function iblockElements(array $productIds) : array
	{
		if (empty($productIds)) { return []; }

		$result = [];

		$query = Iblock\ElementTable::getList([
			'filter' => [ '=ID' => $productIds ],
			'select' => [
				'IBLOCK_ID',
				'ID',
				'XML_ID',
				'IBLOCK_XML_ID' => 'IBLOCK.XML_ID',
			]
		]);

		while ($row = $query->Fetch())
		{
			$result[$row['ID']] = $row;
		}

		return $result;
	}

	protected function catalogProducts(array $productIds) : array
	{
		if (empty($productIds)) { return []; }

		$result = [];

		$query = Catalog\ProductTable::getList([
			'filter' => [ '=ID' => $productIds ],
			'select' => [ 'ID', 'TYPE' ],
		]);

		while ($row = $query->fetch())
		{
			$result[$row['ID']] = $row;
		}

		return $result;
	}

	protected function filterProductOffers(array $products) : array
	{
		$result = [];

		foreach ($products as $productId => $product)
		{
			if ((int)$product['TYPE'] === Catalog\ProductTable::TYPE_OFFER)
			{
				$result[$productId] = $product;
			}
		}

		return $result;
	}

	protected function groupElementsByIblock(array $offers) : array
	{
		$offersGroupByIblock = [];

		foreach ($offers as $offer)
		{
			$iblockId = (int)$offer['IBLOCK_ID'];

			if (!isset($offersGroupByIblock[$iblockId]))
			{
				$offersGroupByIblock[$iblockId] = [];
			}

			$offersGroupByIblock[$iblockId][] = (int)$offer['ID'];
		}

		return $offersGroupByIblock;
	}

	protected function offerParentMap(array $offers) : array
	{
		$result = [];

		foreach ($this->groupElementsByIblock($offers) as $iblockId => $offerIds)
		{
			$offerProductData = \CCatalogSku::getProductList($offerIds, $iblockId);

			foreach ($offerProductData as $offerId => $productData)
			{
				$result[$offerId] = (int)$productData['ID'];
			}
		}

		return $result;
	}

	protected function offerProperties(array $offers, string $siteId) : array
	{
		$result = [];

        $offersGroupByIblock = $this->groupElementsByIblock($offers);

		foreach ($offersGroupByIblock as $iblockId => $elementIds)
		{
			$propertyCodes = $this->iblockBasketPropertyCodes($iblockId, $siteId);

			if (empty($propertyCodes)) { continue; }

			$iblockCatalog = \CCatalogSku::GetInfoByIBlock($iblockId);

			if (
				empty($iblockCatalog['PRODUCT_IBLOCK_ID'])
				|| $iblockCatalog['CATALOG_TYPE'] !== \CCatalogSku::TYPE_OFFERS
			)
			{
				continue;
			}

			foreach ($elementIds as $elementId)
			{
				$result[$elementId] = \CIBlockPriceTools::GetOfferProperties(
					$elementId,
					$iblockCatalog['PRODUCT_IBLOCK_ID'],
					$propertyCodes
				);
			}
		}

		return $result;
	}

	protected function iblockBasketPropertyCodes(int $iblockId, string $siteId) : ?array
	{
		if (Catalog\Product\PropertyCatalogFeature::isEnabledFeatures())
		{
			return Catalog\Product\PropertyCatalogFeature::getBasketPropertyCodes($iblockId, [ 'CODE' => 'Y' ]);
		}

		$catalog = \CCatalogSku::GetInfoByIBlock($iblockId);
		$catalogIblockId = !empty($catalog['PRODUCT_IBLOCK_ID']) ? (int)$catalog['PRODUCT_IBLOCK_ID'] : $iblockId;

		$context = new Source\Context($catalogIblockId, $siteId);
		$parametersFinder = new Utils\Component\Parameters();
		$parameters = $parametersFinder->values($context, [
			'OFFERS_CART_PROPERTIES',
		]);

		return isset($parameters['OFFERS_CART_PROPERTIES']) && is_array($parameters['OFFERS_CART_PROPERTIES'])
			? $parameters['OFFERS_CART_PROPERTIES']
			: null;
	}

	protected function makeBasketDefaults(array $element, array $parent = null) : array
	{
		$result = [
			'PROPS' => [],
		];
		$productXmlId = (string)($element['XML_ID'] ?? '');
		$catalogXmlId = (string)($element['IBLOCK_XML_ID'] ?? '');

		if ($productXmlId !== '')
		{
			if ($parent !== null && mb_strpos($productXmlId, '#') === false)
			{
				$productXmlId = $parent['XML_ID'] . '#' . $productXmlId;
			}

			$result['PRODUCT_XML_ID'] = $productXmlId;
			$result['PROPS'][] = [
				'NAME' => 'Product XML_ID',
				'CODE' => 'PRODUCT.XML_ID',
				'VALUE' => $productXmlId,
			];
		}

		if ($catalogXmlId !== '')
		{
			$result['CATALOG_XML_ID'] = $element['IBLOCK_XML_ID'];
			$result['PROPS'][] = [
				'NAME' => 'Catalog XML_ID',
				'CODE' => 'CATALOG.XML_ID',
				'VALUE' => $element['IBLOCK_XML_ID'],
			];
		}

		return $result;
	}

	protected function makeBasketProperties(array $properties) : array
	{
		return [
			'PROPS' => $properties,
		];
	}

	protected function mergeBasketData(...$partials) : array
	{
		$result = array_shift($partials);
		$multipleFields = [
			'PROPS',
		];

		foreach ($partials as $item)
		{
			foreach ($multipleFields as $multipleField)
			{
				if (isset($item[$multipleField]) && array_key_exists($multipleField, $result))
				{
					$result[$multipleField] = array_merge(
						(array)$result[$multipleField],
						(array)$item[$multipleField]
					);
				}
			}

			$result += $item;
		}

		return $result;
	}
}