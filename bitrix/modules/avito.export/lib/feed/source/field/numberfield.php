<?php
namespace Avito\Export\Feed\Source\Field;

class NumberField extends Field
{
	protected function defaults() : array
	{
		return [
			'TYPE' => 'N',
		];
	}
}