<?php
namespace Avito\Export\Feed\Source\GroupProperty\Feature;

use Avito\Export\Concerns;
use Avito\Export\Feed\Source\Context;
use Avito\Export\Feed\Source\Registry;
use Bitrix\Iblock;

class Filter implements Feature
{
	use Concerns\HasLocale;

	public function id() : string
	{
		return 'FILTER';
	}

	public function title() : string
	{
		return self::getLocale('TITLE');
	}

	public function properties(Context $context) : array
	{
		$result = [
			Registry::IBLOCK_PROPERTY => $this->iblockProperties($context->iblockId())
		];

		if ($context->hasOffers())
		{
			$result[Registry::OFFER_PROPERTY] = $this->iblockProperties($context->offerIblockId());
		}

		return $result;
	}

	protected function iblockProperties(int $iblockId) : array
	{
		$iterator = Iblock\SectionPropertyTable::getList([
			'select' => [ 'PROPERTY_ID' ],
			'filter' => [
				'=IBLOCK_ID' => $iblockId,
				'=SMART_FILTER' => 'Y',
				'=PROPERTY.ACTIVE' => 'Y',
			],
			'order' => [
				'PROPERTY.SORT' => 'ASC',
				'PROPERTY.ID' => 'ASC',
			],
		]);

		return array_column($iterator->fetchAll(), 'PROPERTY_ID', 'PROPERTY_ID');
	}
}