<?php
namespace Avito\Export\Api\Messenger\V3\Model\Message;

use Avito\Export\Api;

class Call extends Api\Response
{
	public function status() : string
	{
		return $this->requireValue('status');
	}

	public function targetUserId() : int
	{
		return $this->requireValue('target_user_id');
	}
}