<?php
namespace Avito\Export\Feed\Source\Template\Engine\Functions;

class FunctionCeil extends FunctionRound
{
	protected function applyRound(float $value) : float
	{
		return ceil($value);
	}
}