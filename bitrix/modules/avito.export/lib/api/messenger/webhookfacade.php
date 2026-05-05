<?php
namespace Avito\Export\Api\Messenger;

use Avito\Export\Api\OAuth\Token;

class WebhookFacade
{
	public static function isSubscribed(string $url, Token $token) : bool
	{
		$subscriptions = static::subscriptions($token);

		if ($subscriptions->isEmpty()) { return false; }

		$isSubscribed = false;

		/** @var V1\Webhook\Subscriptions\Subscription $subscription */
		foreach ($subscriptions as $subscription)
		{
			if ($url === $subscription->url())
			{
				$isSubscribed = true;
				break;
			}
		}

		return $isSubscribed;
	}

	public static function subscriptions(Token $token) : V1\Webhook\Subscriptions\Subscriptions
	{
		$subscriptionRequest = new V1\Webhook\Subscriptions\Request();
		$subscriptionRequest->token($token);

		return $subscriptionRequest->execute()->subscriptions();
	}

	public static function subscribe(string $url, Token $token) : void
	{
		$webhookRequest = new V3\Webhook\Request();

		$webhookRequest->token($token);
		$webhookRequest->userUrl($url);
		$webhookRequest->execute();
	}

	public static function unsubscribe(string $url, Token $token) : void
	{
		$unsubscriptionRequest = new V1\Webhook\Unsubscribe\Request();
		$unsubscriptionRequest->token($token);
		$unsubscriptionRequest->userUrl($url);
		$unsubscriptionRequest->execute();
	}
}