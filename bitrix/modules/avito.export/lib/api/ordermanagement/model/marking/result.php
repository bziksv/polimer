<?php
namespace Avito\Export\Api\OrderManagement\Model\Marking;

use Avito\Export\Api;

class Result extends Api\Response
{
	public function error() : ?string
	{
		return $this->getValue('error');
	}

	public function itemId() : string
	{
		return (string)$this->requireValue('itemId');
	}

	public function orderId() : string
	{
		return (string)$this->requireValue('orderId');
	}

	public function success() : bool
	{
		return (bool)$this->getValue('success');
	}
}