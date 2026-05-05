<?php
namespace Avito\Export\Feed\Source\Template\Engine\Functions;

use Bitrix\Iblock;
use Bitrix\Main;

if (!Main\Loader::includeModule('iblock')) { return; }

class FunctionMin extends Iblock\Template\Functions\FunctionBase
{
	public function calculate(array $parameters)
	{
		$parameters = $this->parametersToArray($parameters);
		$parameters = array_filter($parameters, static function($parameter) { return is_numeric($parameter); });

		return !empty($parameters) ? min($parameters) : null;
	}
}