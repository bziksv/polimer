<?php
namespace Avito\Export\Api\Messenger\V1\Model;

use Avito\Export\Api;

class Content extends Api\Response
{
	public function text() : string
	{
		return $this->requireValue('text');
	}
}