<?php
namespace Avito\Export\Feed\Source\Template\Engine\Functions;

use Bitrix\Iblock;
use Bitrix\Main;

if (!Main\Loader::includeModule('iblock')) { return; }

class FunctionCharacteristic extends Iblock\Template\Functions\FunctionBase
{
	public function calculate(array $parameters)
	{
		[$values, $descriptions, $name] = $parameters;

		if (!is_array($descriptions) || !is_array($values)) { return null; }

		$nameIndex = array_search($name, $descriptions, true);

		if ($nameIndex === false) { return null; }

		return $values[$nameIndex] ?? null;
	}
}
