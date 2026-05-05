<?php
namespace Avito\Export\Api\Messenger\V3\Model\Message\Link;

use Avito\Export\Api;

class Preview extends Api\Response
{
	public function description() : string
	{
		return $this->requireValue('description');
	}

	public function domain() : string
	{
		return $this->requireValue('domain');
	}

	public function images() : ?array
	{
		return $this->getValue('images');
	}

	public function title() : string
	{
		return $this->requireValue('title');
	}

	public function url() : string
	{
		return $this->requireValue('url');
	}
}