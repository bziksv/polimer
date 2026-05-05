<?php
namespace Avito\Export\Feed\Source\Field;

class StringField extends Field
{
	protected function defaults() : array
	{
		return [
			'TYPE' => 'S',
		];
	}

	public function conditions() : array
	{
		return [
			Condition::EQUAL,
			Condition::NOT_EQUAL,
			Condition::AT_LIST,
			Condition::NOT_AT_LIST,
			Condition::HAS_SUBSTRING,
			Condition::HAS_NOT_SUBSTRING,
		];
	}
}