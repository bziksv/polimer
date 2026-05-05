<?php
namespace Avito\Export\Feed\Source\Template\Engine\Functions;

use Bitrix\Iblock;
use Bitrix\Main;

if (!Main\Loader::includeModule('iblock')) { return; }

class FunctionEquals extends Iblock\Template\Functions\FunctionBase
{
	/** @noinspection TypeUnsafeComparisonInspection */
	public function calculate(array $parameters)
	{
		$resultKey = ($this->parameterToScalar($parameters[0]) == $this->parameterToScalar($parameters[1]) ? 2 : 3);

		return $this->parameterToScalar($parameters[$resultKey] ?? null);
	}

	protected function parameterToScalar($parameter)
	{
		return is_array($parameter) ? reset($parameter) : $parameter;
	}
}