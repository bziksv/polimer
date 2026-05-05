<?php
namespace Avito\Export\Api\Validator;

use Avito\Export\Api;
use Avito\Export\Concerns;

class FormatArray extends Validator
{
	use Concerns\HasLocale;

	public function test() : void
	{
		if (!is_array($this->data))
		{
			throw new Api\Exception\ResponseFormat(self::getLocale('MESSAGE', [
				'#CONTENT#' => is_scalar($this->data) ? mb_substr($this->data, 0, 30) : gettype($this->data),
			]));
		}
	}
}