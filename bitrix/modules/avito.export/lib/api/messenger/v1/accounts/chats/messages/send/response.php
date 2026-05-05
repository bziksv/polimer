<?php
namespace Avito\Export\Api\Messenger\V1\Accounts\Chats\Messages\Send;

use Avito\Export\Api;

class Response extends Api\Response
{
	public function content() : Api\Messenger\V1\Model\Content
	{
		return $this->requireCollection('messages', Api\Messenger\V1\Model\Content::class);
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

	public function type() : string
	{
		return $this->requireValue('type');
	}
}