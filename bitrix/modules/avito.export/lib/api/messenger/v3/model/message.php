<?php
namespace Avito\Export\Api\Messenger\V3\Model;

use Avito\Export\Api;

class Message extends Api\Response
{
	public function authorId() : int
	{
		return $this->requireValue('author_id');
	}

	public function created() : int
	{
		return $this->requireValue('created');
	}

	public function direction() : string
	{
		return $this->requireValue('direction');
	}

	public function id() : string
	{
		return $this->requireValue('id');
	}

	/** has the message been read */
	public function isRead() : bool
	{
		return (bool)$this->requireValue('isRead');
	}

	/** read timestamp, if read */
	public function read() : ?int
	{
		return $this->getValue('read');
	}

	/** Enum: "text" "image" "link" "item" "location" "call" "deleted" */
	public function type() : string
	{
		return $this->requireValue('type');
	}

	public function content() : Message\Content
	{
		return $this->requireModel('content', Message\Content::class);
	}

	public function quote() : ?Message\Quote
	{
		return $this->getModel('quote', Message\Quote::class);
	}
}