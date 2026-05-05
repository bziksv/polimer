<?php
namespace Avito\Export\Structure\Transportation;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\Factory;
use Avito\Export\Structure\Category;

class WaterTransport implements Category, CategoryLevel
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
		    'Condition' => new Dictionary\Listing\Condition(),
		    'Availability' => new Dictionary\Listing\Availability(),
	    ]);
    }

    public function children() : array
    {
        return $this->once('children', static function() {
	        return [
		        new WaterTransport\MotorBoatsAndMotors(),
		        new WaterTransport\JetSkis(),
		        new WaterTransport\BoatsAndYachts(),
		        new WaterTransport\Paddleboats(),
	        ];
        });
    }
}