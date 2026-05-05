<?php
namespace Avito\Export\Api\Validator;

use Avito\Export\Api;

class MessageError extends Validator
{
	public function test() : void
	{
		$status = $this->transport->getStatus();

		if ($status === 200) { return; }

		if (isset($this->data['message']))
		{
			throw new Api\Exception\HttpError($status, $this->data['message']);
		}

		if (isset($this->data['result']['status']) && $this->data['result']['status'] === false)
		{
			throw new Api\Exception\HttpError($status, $this->data['result']['message'] ?? 'UNKNOWN_RESULT');
		}
	}
}