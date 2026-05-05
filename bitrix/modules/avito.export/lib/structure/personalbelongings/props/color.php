<?php
namespace Avito\Export\Structure\PersonalBelongings\Props;

use Avito\Export\Concerns;
use Avito\Export\Dictionary\Listing\Listing;

class Color implements Listing
{
    use Concerns\HasLocale;

    public function values() : array
    {
        return [
            self::getLocale('WHITE'),
            self::getLocale('BLACK'),
            self::getLocale('GRAY'),
            self::getLocale('RED'),
            self::getLocale('BEIGE'),
            self::getLocale('BLUE'),
            self::getLocale('SILVER'),
            self::getLocale('BROWN'),
            self::getLocale('ORANGE'),
            self::getLocale('GOLDEN'),
            self::getLocale('YELLOW'),
            self::getLocale('GREEN'),
            self::getLocale('LIGHT_BLUE'),
            self::getLocale('VIOLET'),
            self::getLocale('PINK'),
            self::getLocale('MULTICOLOURED'),
            self::getLocale('BORDEAUX'),
        ];
    }
}