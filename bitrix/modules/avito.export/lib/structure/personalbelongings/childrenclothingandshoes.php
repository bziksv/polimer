<?php
namespace Avito\Export\Structure\PersonalBelongings;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\Category;
use Avito\Export\Structure\CategoryLevel;

class ChildrenClothingAndShoes implements Category, CategoryLevel
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
				'Condition' => new Props\ChildrenConditionProduct(),
				'NDS' => new Props\Nds()
			])
		]);
    }

    public function children() : array
    {
        return $this->once('children', static function() {
            return [
	            new ChildrenClothingAndShoes\ForBoys([
					'name' => self::getLocale('FOR_BOYS'),
				]),
	            new ChildrenClothingAndShoes\ForGirls([
					'name' => self::getLocale('FOR_GIRLS'),
				]),
            ];
        });
    }
}