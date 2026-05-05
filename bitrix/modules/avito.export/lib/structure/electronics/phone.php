<?php
namespace Avito\Export\Structure\Electronics;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\Factory;
use Avito\Export\Structure\Category;

class Phone implements Category, CategoryLevel
{
	use Concerns\HasOnce;
	use Concerns\HasLocale;

	public function name() : string
	{
		return self::getLocale('NAME');
	}

	public function categoryLevel() : ?string
	{
		return CategoryLevel::CATEGORY;
	}

	public function dictionary() : Dictionary\Dictionary
	{
		return new Dictionary\NoValue();
	}

	/** @noinspection SpellCheckingInspection */
	public function children() : array
	{
		return $this->once('children', static function() {
			self::includeLocale();

			$commonFactory = new Factory(self::getLocalePrefix(), Phone\Common::class);
			$accessoriesFactory = new Factory(self::getLocalePrefix(), Phone\Common::class);
			$accessoriesFactory->categoryLevel(CategoryLevel::PRODUCTS_TYPE);

			return $commonFactory->make([
				new Phone\Mobile([
					'name' => $commonFactory->itemTitle('MOBILE'),
					'oldNames' => $commonFactory->itemTitles([
						'Acer',
						'Alcatel',
						'ASUS',
						'BlackBerry',
						'BQ',
						'DEXP',
						'Explay',
						'Fly',
						'Highscreen',
						'HTC',
						'Huawei',
						'iPhone',
						'Lenovo',
						'LG',
						'Meizu',
						'Micromax',
						'Microsoft',
						'Motorola',
						'MTS',
						'Nokia',
						'Panasonic',
						'Philips',
						'Prestigio',
						'Samsung',
						'Siemens',
						'SkyLink',
						'Sony',
						'teXet',
						'Vertu',
						'Xiaomi',
						'ZTE',
						'Other brands',
					]),
				]),
				'Walkie-talkies',
				'Landlines',
				'Accessories' => [
					'categoryLevel' => CategoryLevel::GOODS_TYPE,
					'children' => $accessoriesFactory->make([
						'Batteries',
						'Headsets and headphones',
						'Chargers',
						'Cables and adapters',
						'Modems and routers',
						'Covers and films',
						'Spare parts',
					]),
				],
			]);
		});
	}
}