<?php
namespace Avito\Export\Structure\PersonalBelongings\Props;

use Avito\Export\Concerns;
use Avito\Export\Dictionary\Listing\Listing;

class Nds implements Listing
{
    use Concerns\HasLocale;

    public function values() : array
    {
        return [
            self::getLocale('WITH_OUT'),
            self::getLocale('0'),
            self::getLocale('10'),
            self::getLocale('20'),
        ];
    }
}