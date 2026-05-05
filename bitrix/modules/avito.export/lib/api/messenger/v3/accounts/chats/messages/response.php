<?php
namespace Avito\Export\Api\Messenger\V3\Accounts\Chats\Messages;

use Avito\Export\Api;

class Response extends Api\Response
{
	public function messages() : Api\Messenger\V3\Model\Messages
	{
		return $this->requireCollection('messages', Api\Messenger\V3\Model\Messages::class);
	}
}