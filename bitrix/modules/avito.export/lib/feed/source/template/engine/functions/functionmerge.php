<?php
namespace Avito\Export\Feed\Source\Template\Engine\Functions;

use Bitrix\Iblock;
use Bitrix\Main;

if (!Main\Loader::includeModule('iblock')) { return; }

class FunctionMerge extends Iblock\Template\Functions\FunctionBase
{
	public function calculate(array $parameters) : array
	{
		return $this->parametersToArray($parameters);
	}
}