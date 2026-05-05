<?php
namespace Avito\Export\Structure\ForHomeAndGarden\HomeAppliances;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\Category;
use Avito\Export\Structure\CategoryLevel;

class RefrigeratorsAndFreezers implements Category, CategoryLevel
{
	use Concerns\HasLocale;

	public function name() : string
	{
		return self::getLocale('NAME');
	}

	public function categoryLevel() : ?string
	{
		return CategoryLevel::GOODS_TYPE;
	}

	/** @noinspection SpellCheckingInspection */
	public function dictionary() : Dictionary\Dictionary
	{
		return new Dictionary\Compound([
			new Dictionary\XmlTree('forhomeandgarden/homeappliances/refrigeratorsandfreezers/goodssubtype.xml', [
				'known' => [
					CategoryLevel::GOODS_SUB_TYPE,
					CategoryLevel::PRODUCT_SUB_TYPE,
				],
			]),
			new Dictionary\Decorator(new Dictionary\XmlTree('forhomeandgarden/homeappliances/refrigeratorsandfreezers/holodilniki_novyj.xml'), [
				'wait' => [
					'GoodsSubType' => self::getLocale('GOODS_SUB_TYPE_REFRIGERATOR'),
				],
			]),
		]);
	}

	public function children() : array
	{
		return [];
	}
}