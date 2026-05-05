<?php
namespace Avito\Export\Api\Messenger\V3\Model\Message;

use Avito\Export\Api;

class Content extends Api\Response
{
	public function text() : string
	{
		return $this->requireValue('text');
	}

	public function image() : Image
	{
		return $this->requireModel('image', Image::class);
	}

	public function call() : Call
	{
		return $this->requireModel('call', Call::class);
	}

	public function item() : Item
	{
		return $this->requireModel('item', Item::class);
	}

	public function link() : Link
	{
		return $this->requireModel('link', Link::class);
	}

	public function location() : Location
	{
		return $this->requireModel('location', Location::class);
	}
}