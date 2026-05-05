<?php
namespace Avito\Export\Structure;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;

class Transportation implements Category
{
    use Concerns\HasOnce;
    use Concerns\HasLocale;

    public function name() : string
    {
        return self::getLocale('NAME');
    }

    public function dictionary() : Dictionary\Dictionary
    {
	    return new Dictionary\NoValue();
    }

    public function children() : array
    {
        return $this->once('children', static function() {
            return [
	            new Transportation\PartsAndAccessories(),
	            new Transportation\WaterTransport(),
	            new Transportation\MotorbikesAndMotorbikes(),
	            new Transportation\SpecialVehicles()
            ];
        });
    }
}