<?php
namespace Avito\Export\Api\Messenger\V3\Model\Message;

use Avito\Export\Api;

class Image extends Api\Response
{
	public function sizes() : array
	{
		return $this->requireValue('sizes');
	}
}