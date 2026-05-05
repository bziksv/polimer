<?php
namespace Avito\Export\Api\OrderManagement\V1\Markings;

use Avito\Export\Api;

class Response extends Api\Response
{
	public function results() : Api\OrderManagement\Model\Marking\Results
	{
		return $this->requireCollection('results', Api\OrderManagement\Model\Marking\Results::class);
	}
}