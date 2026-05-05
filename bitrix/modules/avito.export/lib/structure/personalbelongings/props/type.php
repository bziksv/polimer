<?php
namespace Avito\Export\Structure\PersonalBelongings\Props;

use Avito\Export\Concerns;
use Avito\Export\Dictionary\Listing\Listing;

class Type implements Listing
{
    use Concerns\HasLocale;

    public function values() : array
    {
        return [
            self::getLocale('CRADLE'),
            self::getLocale('STROLLER'),
            self::getLocale('TRANSFORMER'),
            self::getLocale('UNIVERSAL_2_IN_1'),
            self::getLocale('UNIVERSAL_3_IN_1'),
            self::getLocale('CANE'),
            self::getLocale('SLED'),
            self::getLocale('SLED_CARRIAGE'),
            self::getLocale('BABY_CARRYING_BAG'),
            self::getLocale('ACCESSORIES_AND_SPARE_PARTS'),
            self::getLocale('WHEELCHAIR_BIKE'),
            self::getLocale('OTHER'),
        ];
    }
}











