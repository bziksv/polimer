<?php
namespace Avito\Export\Api\Messenger\V3\Model\Message;

use Avito\Export\Api;

class Quote extends Api\Response
{
	public function content() : Content
	{
		return $this->requireModel('content', Content::class);
	}

	public function type() : string
	{
		return $this->requireValue('type');
	}

	public function authorId() : int
	{
		return $this->requireValue('author_id');
	}

	public function created() : int
	{
		return $this->requireValue('created');
	}

	public function id() : string
	{
		return $this->requireValue('id');
	}
}