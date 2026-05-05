<?php
namespace Avito\Export\Api\Messenger\V2\Model;

use Avito\Export\Api;

class Chat extends Api\Response
{
	public function id() : string
	{
		return $this->requireValue('id');
	}

	public function created() : int
	{
		return $this->requireValue('created');
	}

	public function updated() : int
	{
		return $this->requireValue('updated');
	}

	public function context() : Chat\Context
	{
		return $this->requireModel('context', Chat\Context::class);
	}

	public function lastMessage() : ?Chat\LastMessage
	{
		return $this->getModel('last_message', Chat\LastMessage::class);
	}

	public function users() : Chat\Users
	{
		return $this->requireCollection('users', Chat\Users::class);
	}
}