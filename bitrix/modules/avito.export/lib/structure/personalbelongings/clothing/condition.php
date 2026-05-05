<?php
namespace Avito\Export\Structure\PersonalBelongings\Clothing;

use Avito\Export\Concerns;
use Avito\Export\Dictionary\Listing\Listing;

class Condition implements Listing
{
    use Concerns\HasLocale;

    public function values() : array
    {
        return [
            self::getLocale('NEW_WITH_TAG'),
            self::getLocale('EXCELLENT'),
            self::getLocale('GOOD'),
            self::getLocale('SATISFACTORY'),
        ];
    }
}