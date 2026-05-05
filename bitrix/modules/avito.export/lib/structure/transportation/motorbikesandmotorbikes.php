<?php
namespace Avito\Export\Structure\Transportation;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\Factory;
use Avito\Export\Structure\Category;

class MotorbikesAndMotorbikes implements Category, CategoryLevel
{
    use Concerns\HasOnce;
    use Concerns\HasLocale;

	public function categoryLevel() : ?string
	{
		return CategoryLevel::CATEGORY;
	}

	public function name() : string
    {
        return self::getLocale('NAME');
    }

    public function dictionary() : Dictionary\Dictionary
    {
	    return new Dictionary\Fixed([
			'Availability' => new Dictionary\Listing\Availability(),
		    'Condition' => new Dictionary\Listing\Condition([
			    'ADDITIONAL_VALUES' => [
				    self::getLocale('ADDITIONAL_CONDITION_FOR_SPARE_PARTS')
			    ]
		    ])
	    ]);
    }

    public function children() : array
    {
        return $this->once('children', static function() {
            self::includeLocale();

	        return (new Factory(self::getLocalePrefix()))->make([
				new MotorBikesAndMotorBikes\MopedsAndScooters(),
		        new MotorBikesAndMotorBikes\AllTerrainVehicles(),
		        new MotorBikesAndMotorBikes\Quadrocycles(),
		        new MotorBikesAndMotorBikes\Motorbikes(),
		        'Karting' => [
			        'categoryLevel' => CategoryLevel::VEHICLE_TYPE,
			        'dictionary' => new Dictionary\Fixed([
				        'VIN' => [],
			        ]),
		        ],
		        new MotorBikesAndMotorBikes\Snowmobiles(),
	        ]);
		});
    }
}
