<?php
namespace Avito\Export\Api\Messenger\V2\Model\Chat\Context;

use Avito\Export\Api;

class Value extends Api\Response
{
	public function id() : int
	{
		return $this->requireValue('id');
	}

	public function priceString() : string
	{
		return $this->requireValue('price_string');
	}

	public function statusId() : int
	{
		return $this->requireValue('status_id');
	}

	public function title() : ?string
	{
		return $this->getValue('title');
	}

	public function url() : ?string
	{
		return $this->getValue('url');
	}

	public function userId() : int
	{
		return $this->requireValue('user_id');
	}

	public function images() : ?Value\Images
	{
		return $this->getModel('images', Value\Images::class);
	}
}