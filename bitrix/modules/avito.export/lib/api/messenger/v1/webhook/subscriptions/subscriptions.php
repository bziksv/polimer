<?php
namespace Avito\Export\Api\Messenger\V1\Webhook\Subscriptions;

use Avito\Export\Api;

class Subscriptions extends Api\ResponseCollection
{
	protected function itemClass() : string
	{
		return Subscription::class;
	}
}