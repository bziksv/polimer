<?php
namespace Yandex\Market\Components;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Yandex\Market\Api;
use Yandex\Market\Api\Categories\Tree\Model;
use Yandex\Market\Psr;
use Yandex\Market\Reference\Assert;
use Yandex\Market\Ui\Access;
use Yandex\Market\Ui\Iblock\CategoryForm;
use Yandex\Market\Ui\Iblock\CategoryValue;

Loc::loadMessages(__FILE__);

/** @noinspection PhpUnused */
class AdminPropertyCategory extends \CBitrixComponent
{
    const VALUE_GLUE = ' / ';
    const VALUE_ESCAPE = '/';

    public function executeComponent()
    {
        $this->includeComponentTemplate();

        return $this->arResult['HTML'];
    }

    /** @noinspection PhpUnused */
    public function categoriesAction(array $payload, array $property = null)
    {
        Assert::nonEmptyString($payload['query'], 'query');

        $query = $this->parseSearchQuery($payload['query']);
        $auth = $this->apiAuth($property);
        $checked = [];

        $reducer = new Model\TreeReducer(function ($carry, Model\Category $category, array $parents) use ($query, &$checked) {
            $id = $category->getId();

            if (isset($carry[$id]) || isset($checked[$id])) { return $carry; }
            if (!$this->matchQuery($category, $parents, $query)) { return $carry; }

            $childrenChain = $this->flatChildren($category, $parents);
            $childrenMatched = [];

            foreach ($childrenChain as list($child, $childParents))
            {
                if ($child === $category || $this->matchQuery($child, $childParents, $query))
                {
                    $childrenMatched[] = [$child, $childParents];
                }

                $checked[$child->getId()] = true;
            }

            if (empty($childrenMatched)) { $childrenMatched = $childrenChain; }

            foreach ($childrenMatched as list($child, $childParents))
            {
                $carry[$child->getId()] = $this->formatCategory($child, $childParents);
            }

            return $carry;
        });

        return array_values($reducer->reduce($this->rootCategory($auth), []));
    }

    private function parseSearchQuery($query)
    {
        $result = [
            'CHAIN' => null,
            'WORD' => null,
        ];

        if (is_numeric($query))
        {
            return $result + [ 'ID' => (int)$query ];
        }

        if (preg_match('/^(.*)\[(\d+)]/', $query, $matches))
        {
            list(, $query, $id) = $matches;

            $result['ID'] = (int)$id;
        }

        $result['CHAIN'] = array_map('trim', explode(self::VALUE_GLUE, $query));
        $result['WORD'] = trim(array_pop($result['CHAIN']));

        return $result;
    }

    private function apiAuth(array $property = null)
    {
        if (!empty($property['API_KEY']))
        {
            return new Api\Reference\ApiKey($property['API_KEY']);
        }

        list($auth) = Api\Reference\AuthRepository::any();

        return $auth;
    }

    private function rootCategory(Api\Reference\Auth $auth)
    {
        return (new Api\Categories\Tree\Request($auth))->execute()->getRoot();
    }

    private function matchQuery(Model\Category $category, array $parents, array $query)
    {
        if (isset($query['ID'])) { return ($category->getId() === $query['ID']); }
        if ($query['WORD'] === null) { return false; }

        if (mb_stripos($category->getName(), $query['WORD']) === false) { return false; }

        do
        {
            $word = array_pop($query['CHAIN']);

            if ($word === null) { return true; }

            $found = false;

            while ($parent = array_pop($parents))
            {
                if (mb_stripos($parent->getName(), $word) !== false)
                {
                    $found = true;
                    break;
                }
            }
        }
        while ($found);

        return false;
    }

    private function flatChildren(Model\Category $category, array $parents)
    {
        $children = $category->getChildren();

        if ($children->count() === 0) { return [ [ $category, $parents] ]; }

        $partials = [];
        $parents[] = $category;

        foreach ($children as $child)
        {
            $partials[] = $this->flatChildren($child, $parents);
        }

        return array_merge(...$partials);
    }

    private function formatCategory(Model\Category $category, array $parents)
    {
        $nameChain = array_map(static function(Model\Category $category) { return $category->getName(); }, $parents);
        $nameChain[] = $category->getName();
        $nameChain = array_map(function($name) { return $this->escapeCategory($name); }, $nameChain);

        return sprintf('%s [%s]', implode(static::VALUE_GLUE, $nameChain), $category->getId());
    }

    private function escapeCategory($name)
    {
        return str_replace(static::VALUE_ESCAPE, '\\' . static::VALUE_ESCAPE, $name);
    }

    /** @noinspection PhpUnused */
    public function reloadAction(array $payload, array $property = null)
    {
        $parentValue = $this->parentValue($payload['form'], $property);
        $parentCategory = !empty($parentValue['CATEGORY']) ? (string)$parentValue['CATEGORY'] : '';
        $parentParameters = !empty($parentValue['PARAMETERS']) ? array_column($parentValue['PARAMETERS'], 'VALUE', 'ID') : [];
        $parameters = null;

        if ((string)$payload['category'] !== '')
        {
            $parentParameters = [];
            $parameters = $this->queryParameters($this->parseCategoryId($payload['category']), $this->apiAuth($property));
        }
        else if ($parentCategory !== '')
        {
            $parameters = $this->queryParameters($this->parseCategoryId($parentCategory), $this->apiAuth($property));
        }

        return [
            'parentCategory' => $parentCategory,
            'parentParameters' => $parentParameters,
            'parameters' => $parameters,
        ];
    }

    protected function parentValue(array $form = null, array $property = null)
    {
        if (!isset($form['type'])) { return null; }

        Assert::nonEmptyString($form['type'], 'form[type]');

        if (!isset($form['payload']) || !is_array($form['payload'])) { $form['payload'] = []; }
        if (!isset($form['fields']) || !is_array($form['fields'])) { $form['fields'] = []; }

        $formAdapter = CategoryForm\Factory::restore($form['type'], $form['payload'], $property);
        $parentLoader = $formAdapter->parentValue($form['fields']);

        return CategoryValue\Facade::compile($parentLoader);
    }

    /** @noinspection PhpUnused */
    public function parametersAction(array $payload, array $property = null)
    {
        $categoryValue = (string)$payload['category'];

        if ($categoryValue === '')
        {
            throw new Main\SystemException(Loc::getMessage('YANDEX_MARKET_CATEGORY_PROPERTY_CATEGORY_EMPTY'));
        }

        $categoryId = $this->parseCategoryId($categoryValue);

        return [
            'parameters' => $this->queryParameters($categoryId, $this->apiAuth($property)),
        ];
    }

    public function property(array $componentParameters)
    {
        if (!isset($componentParameters['PROPERTY_TYPE'], $componentParameters['PROPERTY_ID'])) { return null; }

        $id = (int)$componentParameters['PROPERTY_ID'];

        if ($componentParameters['PROPERTY_TYPE'] === 'element')
        {
            $this->requireModule('iblock');

            if ($id === 0 && isset($componentParameters['PROPERTY_IBLOCK']))
            {
                return [
                    'IBLOCK_ID' => (int)$componentParameters['PROPERTY_IBLOCK'],
                ];
            }

            $property = \CIBlockProperty::GetByID($id)->Fetch();

            if (!$property)
            {
                throw new Main\ArgumentException(sprintf('property %s not found', $id));
            }

            return $property + [
                    'API_KEY' => isset($property['USER_TYPE_SETTINGS']['API_KEY']) ? trim($property['USER_TYPE_SETTINGS']['API_KEY']) : null,
                ];
        }

        if ($componentParameters['PROPERTY_TYPE'] === 'section')
        {
            $userField = Main\UserFieldTable::getRow([
                'filter' => [ '=ID' => $id ],
            ]);

            if ($userField === null)
            {
                throw new Main\ArgumentException(sprintf('user field %s not found', $id));
            }

            if (!preg_match('/^IBLOCK_(\d+)_SECTION$/', $userField['ENTITY_ID'], $matches))
            {
                throw new Main\ArgumentException(sprintf('user field entity id %s unknown', $userField['ENTITY_ID']));
            }

            return $userField + [
                    'IBLOCK_ID' => (int)$matches[1],
                    'API_KEY' => isset($userField['SETTINGS']['API_KEY']) ? trim($userField['SETTINGS']['API_KEY']) : null,
                ];
        }

        if ($componentParameters['PROPERTY_TYPE'] === 'userField')
        {
            return [
                'USER_FIELD' => $id,
                'API_KEY' => null,
            ];
        }

        throw new Main\ArgumentException('PROPERTY_TYPE must be one of element or section');
    }

    /** @noinspection PhpSameParameterValueInspection */
    private function requireModule($name)
    {
        if (!Main\Loader::includeModule($name))
        {
            throw new Main\SystemException("{$name} module not loaded");
        }
    }

    public function checkAccess(array $property = null)
    {
        if (isset($property['USER_FIELD']))
        {
            global $USER_FIELD_MANAGER;

            if ($USER_FIELD_MANAGER->GetRights(false, $property['USER_FIELD']) < 'W')
            {
                throw new Main\AccessDeniedException(Loc::getMessage('YANDEX_MARKET_CATEGORY_PROPERTY_USER_FIELD_ACCESS_DENIED'));
            }

            return;
        }

        if (isset($property['IBLOCK_ID']))
        {
            Assert::notNull($property['IBLOCK_ID'], 'property[IBLOCK_ID]');

            $this->requireModule('iblock');

            if (!\CIBlockRights::UserHasRightTo($property['IBLOCK_ID'], $property['IBLOCK_ID'], 'element_edit'))
            {
                throw new Main\AccessDeniedException(Loc::getMessage('YANDEX_MARKET_CATEGORY_PROPERTY_IBLOCK_ACCESS_DENIED', [
                    '#IBLOCK_ID#' => $property['IBLOCK_ID'],
                ]));
            }

            return;
        }

        if (!Access::isProcessExportAllowed() && !Access::isProcessTradingAllowed())
        {
            throw new Main\AccessDeniedException(Loc::getMessage('YANDEX_MARKET_CATEGORY_PROPERTY_MODULE_ACCESS_DENIED'));
        }
    }

    protected function queryParameters($categoryId, Api\Reference\Auth $apiKey)
    {
        $request = new Api\Category\Parameters\Request($apiKey);
        $request->setCategoryId($categoryId);

        $result = [];
        /** @var Api\Category\Parameters\Model\CategoryParameter $property */
        foreach ($request->execute()->getCategoryParameters() as $property)
        {
            $result[] = [
                    'description' => $property->getFullDescription(),
                ] + $property->getFields();
        }

        return $result;
    }

    protected function parseCategoryId($categoryValue)
    {
        if (preg_match('/\[(\d+)]\s*$/', $categoryValue, $matches))
        {
            return (int)$matches[1];
        }

        throw new Main\SystemException(Loc::getMessage('YANDEX_MARKET_CATEGORY_PROPERTY_CATEGORY_INCORRECT'));
    }

    public function checkCategoryAction(array $payload, array $property = null)
    {
        $result = [];
        $iblockId = $payload['IBLOCK_ID'];
        $elementId = $payload['ELEMENT_ID'];

        $businesses = $this->businesses($iblockId);
        $offerMap = $this->mapElementIdsToOfferIds($iblockId, [$elementId]);

        foreach ($businesses as $business) {
            $skuMap = $this->mapOfferIdsToSku($business, array_keys($offerMap));
            $skuMap = $this->limitSkuCount($skuMap, $offerMap);
            $response = $this->fetchElementCategory($business, array_values($skuMap));
            $prepareCategory = $this->prepareCategoryResponse($business, $response, $skuMap);
            if (!empty($prepareCategory)) {
                $result[$business->getId()] = $prepareCategory;
            }
        }

        return $result;
    }
    private function businesses($iblockId)
    {
        $businesses = [];

        foreach (\Yandex\Market\Trading\Business\Model::loadList() as $business)
        {
            if (!$this->canUseSkuMapForIblock($business, $iblockId) || !$this->isActiveBusiness($business)) { continue; }

            $businesses[] = $business;
        }

        return $businesses;
    }
    private function canUseSkuMapForIblock(\Yandex\Market\Trading\Business\Model $business, $iblockId)
    {
        $iblockIds = $business->getOptions()->getSkuMap()->getIblockIds();

        if (empty($iblockIds) || in_array($iblockId, $iblockIds, true)) { return true; }

        if (!Main\Loader::includeModule('catalog')) { return false; }

        $catalog = \CCatalogSku::GetInfoByIBlock($iblockId);

        if ($catalog === false) { return false; }

        return (
            in_array((int)$catalog['IBLOCK_ID'], $iblockIds, true)
            || (
                !empty($catalog['PRODUCT_IBLOCK_ID'])
                && in_array((int)$catalog['PRODUCT_IBLOCK_ID'], $iblockIds, true)
            )
        );
    }
    private function isActiveBusiness(\Yandex\Market\Trading\Business\Model $business)
    {
        if ($business->getTradingCollection()->getActive() !== null)
        {
            return true;
        }

        $catalog = $business->getCatalog();

        return ($catalog !== null && $catalog->wasSubmitted() && $catalog->isActive());
    }
    private function fetchElementCategory(\Yandex\Market\Trading\Business\Model $business, array $skus)
    {
        $response = [];

        foreach (array_chunk($skus, Api\Business\OfferMappings\Request::OFFERS_LIMIT) as $skusChunk)
        {
            $request = new Api\Business\OfferMappings\Request($business->getId(), $business->getOptions()->getApiAuth(), $business->createLogger());
            $request->setOfferIds($skusChunk);
            $response = $request->execute();
            $response = $response->getOfferMappings();

        }
        return $response;
    }

    private function prepareCategoryResponse(
        \Yandex\Market\Trading\Business\Model $business,
        \Yandex\Market\Api\Business\OfferMappings\OfferMappingCollection $response,
        array $sku
    ): array {
        $result = [];
        $catalogId = $business->getCatalog()->getId();
        $categoryInfo = [];
        $queryAssortment = \Yandex\Market\Catalog\Run\Storage\AssortmentTable::getList([
            'filter' => [
                '=CATALOG_ID' => $catalogId,
                '=ELEMENT_ID' => $sku,
                '=STATUS' => \Yandex\Market\Catalog\Run\Storage\AssortmentTable::STATUS_PLACED,
            ],
            'select' => [
                'ELEMENT_ID',
                'CATEGORY_ID'
            ]
        ]);

        while ($assortmentRow = $queryAssortment->fetch())
        {
            $categoryInfo[$assortmentRow['ELEMENT_ID']] = $assortmentRow['CATEGORY_ID'];
        }

        foreach ($sku as $skuId => $elementId) {
            $offerMapping = $response->getItemByOfferId($skuId);

            if ($offerMapping === null || !isset($categoryInfo[$skuId])) {
                continue;
            }
            $bitrixCategoryId = $categoryInfo[$skuId];
            $marketCategoryId = $offerMapping->getMapping()->getMarketCategoryId();

            if ($bitrixCategoryId != $marketCategoryId) {
                $result[$skuId]['categoryId'] = $marketCategoryId;
                $result[$skuId]['categoryName'] = $offerMapping->getMapping()->getMarketCategoryName();
                $result[$skuId]['similar'] = false;
            }
        }

        return $result;
    }

    private function mapElementIdsToOfferIds($iblockId, array $elementIds)
    {
        if (empty($elementIds) || !Main\Loader::includeModule('catalog'))
        {
            return array_combine($elementIds, $elementIds);
        }

        $catalog = \CCatalogSku::GetInfoByIBlock($iblockId);

        if (
            $catalog === false
            || !defined('CCatalogSku::TYPE_FULL')
            || $catalog['CATALOG_TYPE'] !== \CCatalogSku::TYPE_FULL
            || empty($catalog['SKU_PROPERTY_ID'])
            || !Main\Loader::includeModule('iblock')
        )
        {
            return array_combine($elementIds, $elementIds);
        }

        $parents = array_fill_keys($elementIds, true);
        $offerMap = [];

        foreach (array_chunk($elementIds, 500) as $elementsChunk)
        {
            $query = \CIBlockElement::GetPropertyValues(
                $catalog['IBLOCK_ID'],
                [ '=PROPERTY_' . $catalog['SKU_PROPERTY_ID'] => $elementsChunk ],
                false,
                [ 'ID' => $catalog['SKU_PROPERTY_ID'] ]
            );

            while ($propertyRow = $query->Fetch())
            {
                $offerId = (int)$propertyRow['IBLOCK_ELEMENT_ID'];
                $parentId = (int)$propertyRow[$catalog['SKU_PROPERTY_ID']];

                if (!isset($parents[$parentId])) { continue; }

                $parents[$parentId] = false;
                $offerMap[$offerId] = $parentId;
            }
        }

        $standaloneParents = array_keys(array_filter($parents));

        return $offerMap + array_combine($standaloneParents, $standaloneParents);
    }
    private function mapOfferIdsToSku(\Yandex\Market\Trading\Business\Model $business, array $offerIds)
    {
        if (empty($offerIds)) { return []; }

        $trading = $business->getPrimaryTrading();
        $command = $trading->wakeupService()->getContainer()->get(
            \Yandex\Market\Trading\Service\Common\Command\SkuMap::class,
            [
                'environment' => $trading->getEnvironment(),
            ]
        );
        $skuMap = $command->make($offerIds);

        if ($skuMap === null)
        {
            return array_combine($offerIds, $offerIds);
        }

        return $skuMap;
    }
    private function limitSkuCount(array $skuMap, array $offerMap)
    {
        $limit = Api\Business\OfferMappings\Request::OFFERS_LIMIT;
        $overhead = count($skuMap) - $limit;

        if ($overhead <= 0) { return $skuMap; }

        $groups = [];

        foreach ($skuMap as $offerId => $sku)
        {
            $elementId = $offerMap[$offerId];

            if (!isset($groups[$elementId]))
            {
                $groups[$elementId] = [];
            }

            $groups[$elementId][] = $offerId;
        }

        $groupsCount = count($groups);

        if ($groupsCount === 0) { return $skuMap; }

        $elementLimit = max(1, floor($limit / $groupsCount));

        do
        {
            $hasHugeGroup = false;

            foreach ($groups as &$group)
            {
                if (count($group) <= $elementLimit) { continue; }

                $hasHugeGroup = true;
                $offerId = array_pop($group);

                unset($skuMap[$offerId]);

                if (--$overhead <= 0) { break; }
            }
            unset($group);
        }
        while ($overhead > 0 && $hasHugeGroup);

        return $skuMap;
    }
}