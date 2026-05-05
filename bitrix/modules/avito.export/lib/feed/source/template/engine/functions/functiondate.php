<?php
namespace Avito\Export\Feed\Source\Template\Engine\Functions;

use Bitrix\Iblock;
use Bitrix\Main;

if (!Main\Loader::includeModule('iblock')) { return; }

class FunctionDate extends Iblock\Template\Functions\FunctionBase
{
	public function calculate(array $parameters)
	{
		$format = $parameters[0];

		if (empty($format)) { $format = 'Y-m-d'; }

		$date = new Main\Type\DateTime();

		return $date->format($format);
	}
}
