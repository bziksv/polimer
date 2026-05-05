<?php
namespace Avito\Export\Api\Messenger\V2\Model\Chat;

use Avito\Export\Api;

class Context extends Api\Response
{
	public function value() : Context\Value
	{
		return $this->requireModel('value', Context\Value::class);
	}
}