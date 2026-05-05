<?php
namespace Avito\Export\Api\Messenger\V1\Webhook\Subscriptions;

use Avito\Export\Api;

/**
 * @property Subscription[] $collection
 */
class Response extends Api\Response
{
	public function subscriptions() : Subscriptions
	{
		return $this->requireCollection('subscriptions', Subscriptions::class);
	}
}