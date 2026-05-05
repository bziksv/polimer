<?php
namespace Avito\Export\Api\Messenger\V3\Model\Message;

use Avito\Export\Api;

class Link extends Api\Response
{
	public function preview() : ?Link\Preview
	{
		return $this->getModel('preview', Link\Preview::class);
	}

	public function text() : string
	{
		return $this->requireValue('text');
	}

	public function url() : string
	{
		return $this->requireValue('url');
	}
}