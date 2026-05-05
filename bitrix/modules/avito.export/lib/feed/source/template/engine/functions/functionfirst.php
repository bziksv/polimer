<?php
namespace Avito\Export\Feed\Source\Template\Engine\Functions;

use Avito\Export\Utils\Value;
use Bitrix\Iblock;
use Bitrix\Main;

if (!Main\Loader::includeModule('iblock')) { return; }

class FunctionFirst extends Iblock\Template\Functions\FunctionBase
{
	public function calculate(array $parameters)
	{
		$result = null;

		foreach ($parameters as $parameter)
		{
			if (!Value::isEmpty($parameter))
			{
				$result = $parameter;
				break;
			}
		}

		return $result;
	}
}