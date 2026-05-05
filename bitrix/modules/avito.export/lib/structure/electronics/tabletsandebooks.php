<?php
namespace Avito\Export\Structure\Electronics;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\Factory;
use Avito\Export\Structure\Category;

class TabletsAndEBooks implements Category, CategoryLevel
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
		return new Dictionary\Compound([
			new Dictionary\Fixed([
				'Condition' => new Dictionary\Listing\Condition(),
				'ProductsType' => new Tablets\ProductsType(),
			]),
			new Dictionary\Decorator(
				new Dictionary\XmlCascade('electronics/tablets.xml'),
				[ 'wait' => [ 'ProductsType' => self::getLocale('PRODUCT_TYPE_TABLET') ],	]
			)
		]);
	}

	public function children() : array
	{
		return $this->once('children', static function() {
			self::includeLocale();

			return (new Factory(self::getLocalePrefix()))->make([
				'Tablets',
				'EBooks',
				'Batteries',
				'Headsets and Earphones',
				'Docking Stations',
				'Chargers',
				'Cables and Adapters',
				'Modems and Routers',
				'Stylus',
				'Covers',
				'Other',
			]);
		});
	}
}