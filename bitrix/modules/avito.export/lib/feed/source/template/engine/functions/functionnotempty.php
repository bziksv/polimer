<?php
namespace Avito\Export\Feed\Source\Template\Engine\Functions;

use Avito\Export\Utils\Value;
use Bitrix\Main;

if (!Main\Loader::includeModule('iblock')) { return; }

class FunctionNotEmpty extends FunctionIf
{
	protected function isPositiveValue($value) : bool
	{
		return !Value::isEmpty($value);
	}
}