<?php
namespace Avito\Export\Api\Messenger\V1\Webhook\Subscriptions;

use Avito\Export\Api;

class Subscription extends Api\Response
{
	public function url() : string
	{
		return $this->requireValue('url');
	}

	public function version() : string
	{
		return $this->requireValue('version');
	}
}