<?php
namespace Avito\Export\Api\OrderManagement\Model\Marking;

use Avito\Export\Api;

class Results extends Api\ResponseCollection
{
	protected function itemClass() : string
	{
		return Result::class;
	}
}