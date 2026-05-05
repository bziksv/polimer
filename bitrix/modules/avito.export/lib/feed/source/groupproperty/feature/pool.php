<?php
namespace Avito\Export\Feed\Source\GroupProperty\Feature;

use Avito\Export\Concerns;
use Bitrix\Main;

class Pool
{
	use Concerns\HasCollection;

	public function __construct()
	{
		if ($this->isIblockEnabled())
		{
			$this->collection = [
				new Detail(),
				new ListFeature(),
				new Filter(),
			];
		}
		else
		{
			$this->collection = [
				new DetailComponent(),
				new ListComponent(),
				new Filter(),
			];
		}
	}

	protected function isIblockEnabled() : bool
	{
		return Main\Config\Option::get('iblock', 'property_features_enabled') === 'Y';
	}
}