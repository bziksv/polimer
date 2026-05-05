<?php
namespace Avito\Export\Feed\Source\Template\Engine\Functions;

class FunctionFloor extends FunctionRound
{
	protected function applyRound(float $value) : float
	{
		return floor($value);
	}
}