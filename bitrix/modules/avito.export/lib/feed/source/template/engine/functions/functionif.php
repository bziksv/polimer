<?php
namespace Avito\Export\Feed\Source\Template\Engine\Functions;

use Bitrix\Iblock;
use Bitrix\Main;

if (!Main\Loader::includeModule('iblock')) { return; }

class FunctionIf extends Iblock\Template\Functions\FunctionBase
{
	public function calculate(array $parameters)
	{
		$condition = $parameters[0] ?? null;
		$resultKey = $this->isPositiveValue($condition) ? 1 : 2;

		return $parameters[$resultKey] ?? null;
	}

	protected function isPositiveValue($value) : bool
	{
		if (!is_scalar($value))
		{
			$result = !empty($value);
		}
		else if (is_numeric($value))
		{
			$result = (float)$value > 0;
		}
		else if ($value === 'false' || $value === 'N')
		{
			$result = false;
		}
		else
		{
			$result = (bool)$value;
		}

		return $result;
	}
}