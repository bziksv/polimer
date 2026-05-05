<?php
namespace Avito\Export\Push\Engine\Steps\Submitter;

use Avito\Export\Glossary;
use Avito\Export\Push\Engine\Steps;
use Bitrix\Main;

class Factory
{
	public static function make(string $type, Steps\Submitter $step) : Action
	{
        if ($type === Glossary::ENTITY_STOCKS)
        {
            return new Stocks($step);
        }

        if ($type === Glossary::ENTITY_PRICE)
        {
            return new Prices($step);
        }

		throw new Main\ArgumentException(sprintf('unknown %s action type', $type));
	}
}