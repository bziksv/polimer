<?php
namespace Avito\Export\Feed\Source\GroupProperty\Feature;

use Avito\Export\Concerns;
use Avito\Export\Feed\Source\Registry;
use Avito\Export\Utils;

class ListComponent extends DetailComponent
{
	use Concerns\HasLocale;

	public function id() : string
	{
		return 'LIST';
	}

	public function title() : string
	{
		return self::getLocale('TITLE');
	}

	protected function parametersFinder() : Utils\Component\Parameters
	{
		$result = new Utils\Component\Parameters();
		$result->templatePriority([
			'SECTION_PAGE_URL',
			'LIST_PAGE_URL',
			'DETAIL_PAGE_URL',
		]);

		return $result;
	}

	protected function catalogComponentCodesPropertyMap() : array
	{
		return [
			Registry::IBLOCK_FIELD => [
				'LIST_PROPERTY_CODE',
			],
			Registry::OFFER_FIELD => [
				'LIST_OFFERS_PROPERTY_CODE',
			],
		];
	}
}