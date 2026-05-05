<?php
namespace Avito\Export\Api\Messenger\V3\Model\Message;

use Avito\Export\Api;

class Item extends Api\Response
{
	public function imageUrl() : string
	{
		return $this->requireValue('image_url');
	}

	public function itemUrl() : string
	{
		return $this->requireValue('item_url');
	}

	public function priceString() : ?string
	{
		return $this->getValue('price_string');
	}

	public function title() : ?string
	{
		return $this->requireValue('title');
	}
}