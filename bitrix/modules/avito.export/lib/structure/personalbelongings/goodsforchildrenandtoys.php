<?php
namespace Avito\Export\Structure\PersonalBelongings;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\CategoryWithTags;
use Avito\Export\Structure\Factory;
use Avito\Export\Structure\Category;
use Avito\Export\Structure\TagFactory;

class GoodsForChildrenAndToys implements Category, CategoryLevel, CategoryWithTags
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
			'FurnitureAdditions' => [ 'multiple' => true, 'wrapper' => true ],
			'FurnitureFrame' => [ 'multiple' => true, 'wrapper' => true ],
			'SeatMaterial' => [ 'multiple' => true, 'wrapper' => true ],
			'CradleAdditions' => [ 'multiple' => true, 'wrapper' => true ],
		]);
	}

    public function dictionary() : Dictionary\Dictionary
    {
		return new Dictionary\Fixed([
            'Condition' => new Props\ChildrenConditionProduct(),
            'NDS' => new Props\Nds()
        ]);
    }

    public function children() : array
    {
        return $this->once('children', static function() {
            self::includeLocale();

            $customFactory = new Factory(self::getLocalePrefix());

	        /** @noinspection SpellCheckingInspection */
	        return $customFactory->make([
                'Car seats' => [
	                'dictionary' => new Dictionary\Compound([
		                new Dictionary\XmlTree('personalbelongings/goodsforchildrentandtoys/carseats.xml', [
			                'known' => [
				                CategoryLevel::CAR_SEATS,
				                CategoryLevel::ACCESSORY_TYPE,
			                ]
		                ]),

		                new Dictionary\Decorator(new Dictionary\XmlTree('personalbelongings/goodsforchildrentandtoys/carseats_brands.xml'), [
			                'wait' => [ 'CarSeats' => self::getLocale('CAR_SEATS_INFANT_CARRIERS') ],
			                'rename' => [ 'brend_avtomobilnie_kresla_lifestyle' => 'InfantCarriersBrand' ]
		                ]),
		                new Dictionary\Decorator(new Dictionary\XmlTree('personalbelongings/goodsforchildrentandtoys/carseats_brands.xml'), [
			                'wait' => [ 'CarSeats' => self::getLocale('CAR_SEATS_CAR_SEATS') ],
			                'rename' => [ 'brend_avtomobilnie_kresla_lifestyle' => 'CarSeatsBrand' ]
		                ]),
		                new Dictionary\Decorator(new Dictionary\XmlTree('personalbelongings/goodsforchildrentandtoys/carseats_brands.xml'), [
			                'wait' => [ 'CarSeats' => self::getLocale('CAR_SEATS_BOOSTERS') ],
			                'rename' => [ 'brend_avtomobilnie_kresla_lifestyle' => 'BoostersBrand' ]
		                ]),
	                ])
                ],
                'Bicycles and scooters' => [
	                'dictionary' => new Dictionary\XmlTree('personalbelongings/goodsforchildrentandtoys/bicyclesandscooters.xml', [
		                'known' => [
			                CategoryLevel::BICYCLES_AND_SCOOTERS,
		                ]
	                ]),
                ],
		        'Children Furniture' => [
			        'dictionary' => new Dictionary\Compound([
				        new Dictionary\XmlTree('personalbelongings/goodsforchildrentandtoys/childrenfurniture.xml', [
							'known' => [
								CategoryLevel::GOODS_SUB_TYPE,
								CategoryLevel::KIDS_FURNITURE_TYPE,
								CategoryLevel::NURSERY_FURNITURE_TYPE,
								CategoryLevel::CHILDRENS_BED_TYPE,
							]
				        ]),
			        ]),
			        'tags' => [
						'ChangeTableMaterial' => [ 'multiple' => true, 'wrapper' => true ],
			        ]
		        ],
                'Baby strollers' => [
                    'dictionary' => new Dictionary\Compound([
						new Dictionary\XmlTree('personalbelongings/goodsforchildrentandtoys/babystrollers.xml', [
							'known' => [
								CategoryLevel::TYPE,
							]
						]),
                        new Dictionary\XmlTree('personalbelongings/goodsforchildrentandtoys/babystrollers_brand.xml'),
	                    new Dictionary\Fixed([
		                    'Color' => new Props\Color(),
		                    'Type' => new Props\Type(),
	                    ]),
                    ])
                ],
                'Toys' => [
					'dictionary' => new Dictionary\XmlTree('personalbelongings/goodsforchildrentandtoys/toys.xml', [
						'known' => [
							CategoryLevel::TOYS,
							CategoryLevel::TYPE_OF_TOY,
							CategoryLevel::TYPE_OF_TRANSPORT,
						]
					]),
                ],
                'Bedding',
                'Feeding Products',
                'Bathing Products',
                'School Supplies',
            ]);
        });
    }
}