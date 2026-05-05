<?php
namespace Avito\Export\Api\Validator;

use Avito\Export\Api;
use Avito\Export\Concerns;

class SuccessTrue extends Validator
{
	use Concerns\HasLocale;

	public function test() : void
	{
		if (!isset($this->data['success']) || $this->data['success'] !== true)
		{
			throw new Api\Exception\ResponseMessage(self::getLocale('MESSAGE'));
		}
	}
}