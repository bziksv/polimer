<?php

namespace Darneo\Ozon\Export\Filter;

use Darneo\Ozon\Export\Helper\Compare;

class Base
{
    protected int $propertyId;
    protected string $compareType;
    protected string $compareValue;

    public function __construct(int $propertyId, $compareType, $compareValue)
    {
        $this->propertyId = $propertyId;
        $this->compareType = $compareType;
        $this->compareValue = $compareValue;
    }

    protected function getPref(): string
    {
        $pref = match ($this->compareType) {
            Compare::EQUAL => '=',
            Compare::NOT_EQUAL => '!=',
            Compare::LIKE => '%',
            Compare::NOT_LIKE => '!%',
            Compare::EMPTY => '',
            Compare::NOT_EMPTY => '!=',
            Compare::MORE => '>',
            Compare::MORE_OR_EQUAL => '>=',
            Compare::LESS => '<',
            Compare::LESS_OR_EQUAL => '<=',
            default => '',
        };

        return $pref;
    }
}