<?php
namespace Avito\Export\Feed\Source\Field;

class DateField extends Field
{
	protected function defaults() : array
	{
		return [
			'TYPE' => 'DateTime',
		];
	}

	public function conditions() : array
	{
		return [
			Condition::EQUAL,
			Condition::NOT_EQUAL,
			Condition::MORE_THEN,
			Condition::LESS_THEN,
			Condition::LESS_OR_EQUAL,
			Condition::MORE_OR_EQUAL,
		];
	}

	public function filter(string $compare, $value) : array
	{
		$compareRule = Condition::some($compare);
		$timestamp = MakeTimeStamp($value, FORMAT_DATETIME);

		if ($timestamp === false) { return []; }

		return [
			$compareRule['QUERY'] . $this->filterName() => ConvertTimeStamp($timestamp, 'FULL'),
		];
	}
}