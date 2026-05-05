<?php
namespace Avito\Export\Feed\Source\ElementProperty;

use Avito\Export\Concerns;
use Avito\Export\Feed\Source\Field;

class DateTimeField extends Field\DateField
{
	use Concerns\HasOnce;

	public function filter(string $compare, $value) : array
	{
		$compareRule = Field\Condition::some($compare);
		$timestamp = MakeTimeStamp($value, FORMAT_DATETIME);

		if ($timestamp === false) { return []; }

		return [
			$compareRule['QUERY'] . $this->filterName() => FormatDate('Y-m-d H:i:s', $timestamp),
		];
	}
}