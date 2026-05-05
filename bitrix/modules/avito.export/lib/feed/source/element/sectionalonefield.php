<?php

namespace Avito\Export\Feed\Source\Element;

use Avito\Export\Feed\Source\Field;

class SectionAloneField extends SectionField
{
    public function filter(string $compare, $value) : array
    {
	    if ($compare === Field\Condition::NOT_AT_LIST)
	    {
		    $result = [
			    '!' . $this->filterName() => $value,
		    ];
	    }
	    else
	    {
		    $result = Field\Field::filter($compare, $value);
	    }

	    return $result;
    }
}
