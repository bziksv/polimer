<?php
namespace Avito\Export\Feed\Source\GroupProperty\Feature;

use Avito\Export\Concerns;
use Avito\Export\Utils;
use Avito\Export\Feed\Source;
use Bitrix\Iblock;

class DetailComponent implements Feature
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

	public function properties(Source\Context $context) : array
	{
		$codes = $this->codes($context);

		$result = [
			Source\Registry::IBLOCK_PROPERTY => $this->iblockProperties($context->iblockId(), $codes[Source\Registry::IBLOCK_FIELD])
		];

		if ($context->hasOffers())
		{
			$result[Source\Registry::OFFER_PROPERTY] = $this->iblockProperties($context->offerIblockId(), $codes[Source\Registry::OFFER_FIELD]);
		}

		return $result;
	}

	protected function iblockProperties(int $iblockId, array $codes) : array
	{
		if (empty($codes)) { return []; }

		$iterator = Iblock\PropertyTable::getList([
			'select' => [ 'ID' ],
			'filter' => [
				'=IBLOCK_ID' => $iblockId,
				'=ACTIVE' => 'Y',
				'!=PROPERTY_TYPE' => Iblock\PropertyTable::TYPE_FILE,
				$this->propertyCodesFilter($codes),
			],
			'order' => [
				'SORT' => 'ASC',
				'ID' => 'ASC',
			],
		]);

		return array_column($iterator->fetchAll(), 'ID', 'ID');
	}

	protected function propertyCodesFilter(array $codes) : array
	{
		$numericCodes = array_filter($codes, static function($code) { return is_numeric($code); });

		if (!empty($numericCodes))
		{
			$result = [
				'LOGIC' => 'OR',
				[ '=CODE' => $codes ],
				[ '=ID' => $numericCodes ],
			];
		}
		else
		{
			$result = [
				'=CODE' => $codes,
			];
		}

		return $result;
	}

	protected function codes(Source\Context $context) : array
	{
		$propertyCodesMap = $this->catalogComponentCodesPropertyMap();

		$parametersFinder = $this->parametersFinder();
		$componentParameters = $parametersFinder->values(
			$context,
			array_merge(...array_values($propertyCodesMap))
		);

		return $this->catalogComponentCodes($propertyCodesMap, $componentParameters);
	}

	protected function parametersFinder() : Utils\Component\Parameters
	{
		return new Utils\Component\Parameters();
	}

	protected function catalogComponentCodes(array $propertyCodesMap, array $componentParameters) : array
	{
		$codes = [];

		foreach ($propertyCodesMap as $type => $names)
		{
			$used = [];

			foreach ($names as $name)
			{
				$value = $componentParameters[$name] ?? null;

				if (!is_array($value)) { continue; }

				$used += array_flip($value);
			}

			$codes[$type] = array_keys($used);
		}

		return $codes;
	}

	protected function catalogComponentCodesPropertyMap() : array
	{
		return [
			Source\Registry::IBLOCK_FIELD => [
				'DETAIL_PROPERTY_CODE',
			],
			Source\Registry::OFFER_FIELD => [
				'DETAIL_OFFERS_PROPERTY_CODE',
				'OFFER_TREE_PROPS',
			],
		];
	}
}