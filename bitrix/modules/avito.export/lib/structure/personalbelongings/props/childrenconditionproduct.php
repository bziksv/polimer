<?php
namespace Avito\Export\Structure\PersonalBelongings\Props;

use Avito\Export\Concerns;
use Avito\Export\Dictionary\Listing\Listing;

class ChildrenConditionProduct implements Listing
{
    use Concerns\HasLocale;

    public function values() : array
    {
        return [
            self::getLocale('NEW'),
            self::getLocale('USED'),
        ];
    }
}