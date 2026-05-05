<?php
namespace Avito\Export\Api\Messenger\V3\Model\Message;

use Avito\Export\Api;

class Location extends Api\Response
{
	/** Enum: "house" "street" */
	public function kind() : string
	{
		return $this->requireValue('kind');
	}

	public function lat() : float
	{
		return $this->requireValue('lat');
	}

	public function lon() : float
	{
		return $this->requireValue('lon');
	}

	public function text() : string
	{
		return $this->requireValue('text');
	}

	public function title() : string
	{
		return $this->requireValue('title');
	}
}