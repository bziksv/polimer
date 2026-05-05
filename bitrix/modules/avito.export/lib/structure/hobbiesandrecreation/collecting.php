<?php
namespace Avito\Export\Structure\HobbiesAndRecreation;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure;

class Collecting implements Structure\Category, Structure\CategoryLevel
{
	use Concerns\HasOnce;
	use Concerns\HasLocale;

	public function name() : string
	{
		return self::getLocale('NAME');
	}

	public function categoryLevel() : ?string
	{
		return Structure\CategoryLevel::CATEGORY;
	}

	public function dictionary() : Dictionary\Dictionary
	{
		return new Dictionary\Compound([
			new Dictionary\XmlTree('hobbiesandrecreation/collecting/goodstype.xml', [
				'known' => [
					Structure\CategoryLevel::GOODS_TYPE,
				]
			]),
			new Dictionary\Decorator(
				new Dictionary\XmlTree('hobbiesandrecreation/collecting/coins.xml'),
				[
					'wait' => [ 'GoodsType' => self::getLocale('COINS') ],
					'rename' => [
						'strana' => 'Country',
						'monetary_unit' => 'Unit',
						'nominal' => 'Nominal',
					],
				]
			),
			new Dictionary\Decorator(
				new Dictionary\XmlTree('hobbiesandrecreation/collecting/banknoty.xml'),
				[
					'wait' => [
						'ProductType' => [
							self::getLocale('BANKNOTE'),
							self::getLocale('BOND'),
							self::getLocale('STOCK'),
						]
					],
					'rename' => [
						'strana' => 'Country',
						'monetary_unit' => 'Unit',
						'nominal' => 'Nominal',
					],
				]
			),
			new Dictionary\Fixed([ 'Condition' => new Dictionary\Listing\Condition() ]),
		]);
	}

	public function children() : array
	{
		return [];
	}
}