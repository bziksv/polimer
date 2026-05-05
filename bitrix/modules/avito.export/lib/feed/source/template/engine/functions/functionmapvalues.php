<?php
namespace Avito\Export\Feed\Source\Template\Engine\Functions;

use Bitrix\Iblock;
use Bitrix\Main;

if (!Main\Loader::includeModule('iblock')) { return; }

class FunctionMapValues extends Iblock\Template\Functions\FunctionBase
{
	/** @noinspection TypeUnsafeComparisonInspection */
	public function calculate(array $parameters)
	{
		$compare = $this->parameterToScalar(array_shift($parameters));
		$index = 0;
		$found = false;
		$result = null;

		if (count($parameters) % 2 === 1)
		{
			$result = $this->parameterToScalar(array_pop($parameters));
		}

		foreach ($parameters as $parameter)
		{
			if ($index % 2 === 0)
			{
				$found = $this->parameterToScalar($parameter) == $compare;
			}
			else if ($found)
			{
				$result = $this->parameterToScalar($parameter);
				break;
			}

			++$index;
		}

		return $result;
	}

	protected function parameterToScalar($parameter)
	{
		return is_array($parameter) ? reset($parameter) : $parameter;
	}
}