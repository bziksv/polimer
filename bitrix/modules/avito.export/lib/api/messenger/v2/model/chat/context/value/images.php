<?php
namespace Avito\Export\Api\Messenger\V2\Model\Chat\Context\Value;

use Avito\Export\Api;

class Images extends Api\Response
{
	public function main() : array
	{
		return $this->requireValue('main');
	}

	public function count() : int
	{
		return $this->requireValue('count');
	}
}