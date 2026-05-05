<?php
namespace Avito\Export\Feed\Source\Template\Engine\Node;

use Avito\Export\Feed\Source\Template\Engine;
use Bitrix\Main;
use Bitrix\Iblock;

if (!Main\Loader::includeModule('iblock')) { return; }

class FunctionField extends Iblock\Template\NodeFunction
{
	public function getParameters() : array
	{
		return $this->parameters;
	}

	/**
	 * @return mixed
	 * @noinspection PhpReturnDocTypeMismatchInspection
	 */
	public function process(Iblock\Template\Entity\Base $entity)
	{
		$functionObject = Engine\Functions\Fabric::make($this->functionName);

		if ($functionObject instanceof Iblock\Template\Functions\FunctionBase)
		{
			$arguments = $functionObject->onPrepareParameters($entity, $this->parameters);

			return $functionObject->calculate($arguments);
		}

		return '';
	}
}