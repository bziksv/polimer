<?php
namespace Avito\Export\Structure\PersonalBelongings;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\Category;
use Avito\Export\Structure\CategoryLevel;

class ClothingShoesAccessories implements Category, CategoryLevel
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
	        new Dictionary\Decorator(
		        new Dictionary\XmlCascade('personalbelongings/brendy_fashion.xml'),
		        [ 'rename' => [ 'brand' => 'Brand' ] ]
	        ),
	        new Dictionary\Fixed([
		        'Color' => new Props\Color(),
		        'Condition' => new Clothing\Condition()
	        ])
        ]);
    }

    public function children() : array
    {
        return $this->once('children', static function() {
			return [
				new Clothing\WomanWear([ 'name' => self::getLocale('WOMEN_S_CLOTHING') ]),
				new Clothing\MensWear([ 'name' => self::getLocale('MEN_S_CLOTHING') ]),
				new Clothing\WomanShoes([ 'name' => self::getLocale('WOMEN_SHOES') ]),
				new Clothing\ManShoes([ 'name' => self::getLocale('MEN_FOOTWEAR') ]),
				new Clothing\Bags([ 'name' => self::getLocale('BAGS_BACKPACKS') ]),
				new Clothing\Accessories([ 'name' => self::getLocale('ACCESSORIES') ])
			];
		});
    }
}