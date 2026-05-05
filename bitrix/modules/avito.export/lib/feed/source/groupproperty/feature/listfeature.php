<?php
namespace Avito\Export\Feed\Source\GroupProperty\Feature;

use Avito\Export\Concerns;
use Bitrix\Iblock;

class ListFeature extends Detail
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

	protected function features(bool $hasCatalog) : array
	{
		return [
			Iblock\Model\PropertyFeature::FEATURE_ID_LIST_PAGE_SHOW,
		];
	}
}