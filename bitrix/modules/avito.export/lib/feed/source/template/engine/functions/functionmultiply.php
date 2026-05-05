<?php
namespace Avito\Export\Feed\Source\Template\Engine\Functions;

use Bitrix\Iblock;
use Bitrix\Main;

if (!Main\Loader::includeModule('iblock')) { return; }

class FunctionMultiply extends Iblock\Template\Functions\FunctionBase
{
	public function calculate(array $parameters) : ?float
	{
		$result = (float)array_shift($parameters);

		if (empty($result)) { return null; }

		foreach ($parameters as $parameter)
		{
			if (!is_numeric($parameter)) { continue; }

			$result *= (float)$parameter;
		}

		return $result;
	}
}