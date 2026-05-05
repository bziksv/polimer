<?php
namespace Avito\Export\Feed\Source\Field;

class EnumField extends Field
{
	public function variants() : array
	{
		return $this->parameter('VARIANTS', []);
	}

	public function conditions() : array
	{
		return [
			Condition::AT_LIST,
			Condition::NOT_AT_LIST,
		];
	}

	protected function defaults() : array
	{
		return [
			'TYPE' => 'L',
		];
	}
}