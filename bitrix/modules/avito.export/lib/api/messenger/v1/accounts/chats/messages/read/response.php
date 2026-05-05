<?php
namespace Avito\Export\Api\Messenger\V1\Accounts\Chats\Messages\Read;

use Avito\Export\Api;

class Response extends Api\Response
{
	public function success() : bool
	{
		return (bool)$this->requireValue('ok');
	}
}