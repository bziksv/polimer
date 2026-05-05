<?php
namespace Avito\Export\Structure\PersonalBelongings;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\CategoryWithTags;
use Avito\Export\Structure\Factory;
use Avito\Export\Structure\Category;
use Avito\Export\Structure\TagFactory;

class WatchesAndJewelry implements Category, CategoryLevel, CategoryWithTags
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

	public function tags() : array
	{
		return (new TagFactory())->make([
			'Functions' => [ 'multiple' => true, 'wrapper' => true ],
			'InsertStone' => [ 'multiple' => true, 'wrapper' => true ],
		]);
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
                'Condition' => new Dictionary\Listing\Condition(),
	            'AdType' => new Dictionary\Listing\AdType(),
            ]),
	        new Dictionary\Decorator(
		        new Dictionary\XmlTree('personalbelongings/watchesandjewelry/size.xml'), [
					'wait' => [
						'GoodsType' => self::getLocale('JEWELRY'),
						'GoodsSubType' => self::getLocale('GOODS_SUB_TYPE_JEWELRY')
		            ]
		        ]
	        )
        ]);
    }

    public function children() : array
    {
        return $this->once('children', static function() {
            self::includeLocale();

            return (new Factory(self::getLocalePrefix()))->make([
                'Costume jewelry' => [
					'dictionary' => new Dictionary\XmlTree('personalbelongings/watchesandjewelry/costumejewelry.xml', [
						'known' => [
							CategoryLevel::GOODS_SUB_TYPE,
						],
					])
                ],
	            'Watch' => [
					'dictionary' => new Dictionary\XmlTree('personalbelongings/watchesandjewelry/watches.xml', [
						'known' => [
							CategoryLevel::PRODUCT_TYPE,
							CategoryLevel::PRODUCT_SUB_TYPE,
						],
					])
	            ],
	            'Jewelry' => [
					'dictionary' => new Dictionary\XmlTree('personalbelongings/watchesandjewelry/jewelry.xml', [
						'known' => [
							CategoryLevel::GOODS_SUB_TYPE,
						],
					])
	            ],
            ]);
        });
    }
}