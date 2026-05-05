<?php
namespace Avito\Export\Feed\Source\AvitoProperty;

use Bitrix\Main;
use Avito\Export\Assert;
use Avito\Export\Concerns;
use Avito\Export\Feed\Source;
use Avito\Export\Admin;

class Fetcher extends Source\FetcherSkeleton
{
	use Concerns\HasLocale;
	use Concerns\HasOnce;

	public const FIELD_CATEGORY = 'category';
	public const FIELD_GOODS_TYPE = 'goodsType';
	public const FIELD_CHARACTERISTIC = 'characteristic';

	protected $fieldMap = [];

	public function listener() : Source\Listener
	{
		return new Source\NoValue\Listener();
	}

	public function title() : string
	{
		return self::getLocale('TITLE');
	}

	public function modules() : array
	{
		return [ 'iblock' ];
	}

	public function order() : int
	{
		return 600;
	}

	public function fields(Source\Context $context) : array
	{
		return $this->once('fields', function() {
			return [
				new Source\Field\StringField([
					'ID' => static::FIELD_CATEGORY,
					'NAME' => self::getLocale('FIELD_CATEGORY'),
					'FILTERABLE' => false,
				]),
				new Source\Field\StringField([
					'ID' => static::FIELD_GOODS_TYPE,
					'NAME' => self::getLocale('FIELD_GOODS_TYPE'),
					'FILTERABLE' => false,
				]),
				new Source\Field\StringField([
					'ID' => static::FIELD_CHARACTERISTIC,
					'NAME' => self::getLocale('FIELD_CHARACTERISTICS'),
					'TYPE' => Admin\Property\CharacteristicProperty::USER_TYPE,
					'FILTERABLE' => false,
				])
			];
		});
	}

	public function extend(array $fields, Source\Data\SourceSelect $sources, Source\Context $context) : void
	{
		if (in_array(static::FIELD_CHARACTERISTIC, $fields, true))
		{
			$this->fieldMap[static::FIELD_CHARACTERISTIC] = [];

			foreach ($this->characteristicProperties($context) as $source => $propertyIds)
			{
				foreach ($propertyIds as $propertyId)
				{
					$sources->add($source, $propertyId . '.self');

					$this->fieldMap[static::FIELD_CHARACTERISTIC][] = [
						$source,
						$propertyId . '.self',
					];
				}
			}
		}

		$needCategory = in_array(static::FIELD_CATEGORY, $fields, true);
		$needGoodsType = in_array(static::FIELD_GOODS_TYPE, $fields, true);

		if ($needCategory || $needGoodsType)
		{
			$this->fieldMap[static::FIELD_CATEGORY] = [];
			$this->fieldMap[static::FIELD_GOODS_TYPE] = [];

			foreach ($this->categoryProperties($context) as $source => $propertyIds)
			{
				foreach ($propertyIds as $propertyId)
				{
					if ($needCategory)
					{
						$sources->add($source, $propertyId . '.category');

						$this->fieldMap[static::FIELD_CATEGORY][] = [
							$source,
							$propertyId . '.category',
						];
					}

					if ($needGoodsType)
					{
						$sources->add($source, $propertyId . '.goodsType');

						$this->fieldMap[static::FIELD_GOODS_TYPE][] = [
							$source,
							$propertyId . '.goodsType',
						];
					}
				}
			}
		}
	}

	public function values(array $elements, array $parents, array $siblings, array $select, Source\Context $context) : array
	{
		$result = [];

		foreach ($elements as $elementId => $element)
		{
			$result[$elementId] = [];

			foreach ($this->fieldMap as $field => $used)
			{
				$values = [];

				foreach ($used as [$siblingSource, $siblingField])
				{
					if (empty($siblings[$elementId][$siblingSource][$siblingField])) { continue; }
					if (isset($values[$siblingSource])) { continue; }

					$values[$siblingSource] = $siblings[$elementId][$siblingSource][$siblingField];
				}

				$result[$elementId][$field] = $this->obtainValue($field, $values);
			}
		}

		return $result;
	}

	protected function obtainValue(string $field, array $partials)
	{
		if (empty($partials))
		{
			return null;
		}

		$result = null;

		if ($field === static::FIELD_CATEGORY || $field === static::FIELD_GOODS_TYPE)
		{
			$result = end($partials);
		}
		else if ($field === static::FIELD_CHARACTERISTIC)
		{
			$result = [];

			foreach ($partials as $values)
			{
				$result += $values;
			}
		}

		return $result;
	}

	protected function categoryProperties(Source\Context $context) : array
	{
		$iblockId = $context->hasOffers() ? $context->offerIblockId() : $context->iblockId();
		$formProperties = Admin\Property\ValueInherit\Category::properties($iblockId);

		if (empty($formProperties))
		{
			throw new Main\SystemException(self::getLocale('MISSING_CATEGORY_PROPERTIES', [
				'#SECTION_URL#' => $this->categoryCreateSectionPropertyUrl($context),
				'#ELEMENT_URL#' => $this->categoryCreateElementPropertyUrl($context),
			]));
		}

		return $this->convertFormProperties($formProperties, $context);
	}

	/** @noinspection SpellCheckingInspection */
	protected function categoryCreateSectionPropertyUrl(Source\Context $context) : string
	{
		global $APPLICATION;

		return '/bitrix/admin/userfield_edit.php?' . http_build_query([
			'lang' => LANGUAGE_ID,
			'ENTITY_ID' => sprintf('IBLOCK_%s_SECTION', $context->iblockId()),
			'back_url' => $APPLICATION->GetCurPageParam(),
			'USER_TYPE_ID' => Admin\Property\CategoryField::USER_TYPE_ID,
		]);
	}

	protected function categoryCreateElementPropertyUrl(Source\Context $context) : string
	{
		return '/bitrix/admin/iblock_edit_property.php?' . http_build_query([
			'lang' => LANGUAGE_ID,
			'IBLOCK_ID' => $context->iblockId(),
			'ID' => 'n0',
			'admin' => 'N',
		]);
	}

	protected function characteristicProperties(Source\Context $context) : array
	{
		$iblockId = $context->hasOffers() ? $context->offerIblockId() : $context->iblockId();
		$formProperties = Admin\Property\ValueInherit\Characteristic::properties($iblockId);

		return $this->convertFormProperties($formProperties, $context);
	}

	protected function convertFormProperties(array $formProperties, Source\Context $context) : array
	{
		$result = [];
		$typeMap = [
			Admin\Property\FormCategory\Registry::PRODUCT_SECTION => Source\Registry::SECTION_PROPERTY,
			Admin\Property\FormCategory\Registry::PRODUCT_ELEMENT => Source\Registry::IBLOCK_PROPERTY,
			Admin\Property\FormCategory\Registry::SECTION => Source\Registry::SECTION_PROPERTY,
			Admin\Property\FormCategory\Registry::ELEMENT => $context->hasOffers()
				? Source\Registry::OFFER_PROPERTY
				: Source\Registry::IBLOCK_PROPERTY,
		];

		foreach ($formProperties as [$formType, $propertyId])
		{
			$type = $typeMap[$formType] ?? null;

			Assert::notNull($type, 'formTypeMapped');

			if (!isset($result[$type]))
			{
				$result[$type] = [];
			}

			$result[$type][] = $propertyId;
		}

		return array_reverse($result);
	}
}