<?php
namespace Avito\Export\Api\Validator;

use Avito\Export\Api;

class ResponseError extends Validator
{
	public function test() : void
	{
		if (!isset($this->data['error'])) { return; }

		if (is_array($this->data['error']))
		{
			$message = $this->data['error']['message'];
			$code = $this->data['error']['code'];
		}
		else
		{
			$message = $this->data['error_description'] ?? '';
			$code = $this->data['error'];
		}

		$text = sprintf('[%s] %s', $code, $message);
		$status = $this->transport->getStatus();

		throw (
			$status !== 200
				? new Api\Exception\HttpError($status, $text)
				: new Api\Exception\ResponseMessage($text)
		);
	}
}