<?php
namespace Avito\Export\Trading\Service;

use Avito\Export\Concerns;
use Avito\Export\Trading\Entity\Sale as TradingEntity;

class Status
{
	use Concerns\HasLocale;

	public const STATUS_ON_CONFIRMATION = 'on_confirmation';
	public const STATUS_READY_TO_SHIP = 'ready_to_ship';
	public const STATUS_IN_TRANSIT = 'in_transit';
	public const STATUS_CANCELED = 'canceled';
	public const STATUS_DELIVERED = 'delivered';
	public const STATUS_ON_RETURN = 'on_return';
	public const STATUS_IN_DISPUTE = 'in_dispute';
	public const STATUS_CLOSED = 'closed';

	public const VIRTUAL_PAID = 'paid';

	public const TRANSITION_CONFIRM = 'confirm';
	public const TRANSITION_REJECT = 'reject';
	public const TRANSITION_PERFORM = 'perform';
	public const TRANSITION_RECEIVE = 'receive';

	public const RETURN_POLICY_IN_TRANSIT = 'in_transit';
	public const RETURN_POLICY_READY = 'ready_to_pickup';

	protected $service;

	public function __construct(Container $container)
	{
		$this->service = $container;
	}

	public function statuses() : array
	{
		return [
			static::STATUS_ON_CONFIRMATION,
			static::STATUS_READY_TO_SHIP,
			static::STATUS_IN_TRANSIT,
			static::STATUS_CANCELED,
			static::STATUS_DELIVERED,
			static::STATUS_ON_RETURN,
			static::STATUS_IN_DISPUTE,
			static::STATUS_CLOSED,
		];
	}

	public function incomingStatuses() : array
	{
		return [
			static::VIRTUAL_PAID,
			static::STATUS_ON_CONFIRMATION,
			static::STATUS_READY_TO_SHIP,
			static::STATUS_IN_TRANSIT,
			static::STATUS_DELIVERED,
			static::STATUS_ON_RETURN,
			static::STATUS_IN_DISPUTE,
			static::STATUS_CANCELED,
		];
	}

	public function deliveryQueue() : array
	{
		return [
			static::STATUS_ON_CONFIRMATION,
			static::STATUS_READY_TO_SHIP,
			static::STATUS_IN_TRANSIT,
			static::STATUS_DELIVERED,
		];
	}

	public function returnQueue() : array
	{
		return [
			static::STATUS_ON_RETURN,
		];
	}

	public function statusQueue(string $status, string $stored = null) : array
	{
		$queue = $this->makeStatusQueue($status);
		$queue = $this->filterProcessedQueue($queue, $stored);

		return $queue;
	}

	protected function makeStatusQueue(string $status) : array
	{
		$deliveryQueue = $this->deliveryQueue();
		$returnQueue = $this->returnQueue();
		$positiveIndex = array_search($status, $deliveryQueue, true);
		$negativeIndex = array_search($status, $returnQueue, true);

		if ($positiveIndex !== false)
		{
			$result = array_slice($deliveryQueue, 0, $positiveIndex + 1);
		}
		else if ($negativeIndex !== false)
		{
			$result = array_slice($returnQueue, 0, $negativeIndex + 1);
		}
		else
		{
			$result = [ $status ];
		}

		return $result;
	}

	protected function filterProcessedQueue(array $queue, string $stored = null) : array
	{
		if ($stored === null) { return $queue; }

		$foundStored = false;
		$result = [];

		foreach ($queue as $queueStatus)
		{
			if ($queueStatus === $stored)
			{
				$foundStored = true;
				continue;
			}

			if (!$foundStored) { continue; }

			$result[] = $queueStatus;
		}

        if (!$foundStored) { $result = $queue; }

		return $result;
	}

	public function statusTitle(string $status) : string
	{
		return self::getLocale('STATUS_' . mb_strtoupper($status), null, $status);
	}

	public function statusAttention(string $status, array $replaces, string $deliveryType = null) : ?string
	{
		$variants = [
			'STATUS_' . mb_strtoupper($status) . '_ATTENTION_' . mb_strtoupper($deliveryType),
			'STATUS_' . mb_strtoupper($status) . '_ATTENTION',
		];
		$result = null;

		foreach ($variants as $variant)
		{
			$text = self::getLocale($variant, $replaces, '');

			if ($text === '') { continue; }

			$result = $text;
			break;
		}

		return $result;
	}

	public function statusDefaults() : array
	{
		return [
			static::STATUS_ON_CONFIRMATION => TradingEntity\Status::NEW_STATUS,
			static::STATUS_CANCELED => TradingEntity\Status::CANCELLED,
			static::STATUS_ON_RETURN => TradingEntity\Status::CANCELLED,
			static::VIRTUAL_PAID => TradingEntity\Status::PAID,
			static::STATUS_DELIVERED => TradingEntity\Status::FINISHED,
		];
	}

	public function transitions() : array
	{
		return [
			static::TRANSITION_CONFIRM,
			static::TRANSITION_REJECT,
			static::TRANSITION_PERFORM,
			static::TRANSITION_RECEIVE,
		];
	}

	public function transitionTitle(string $transition, string $version = null) : string
	{
		$key = 'TRANSITION_' . mb_strtoupper($transition);

		if ($version !== null)
		{
			$key .= '_' . mb_strtoupper($version);
		}

		return self::getLocale($key, null, $transition);
	}

	public function transitionDefaults() : array
	{
		return [
			static::TRANSITION_CONFIRM => TradingEntity\Status::ALLOW_DELIVERY,
			static::TRANSITION_REJECT => TradingEntity\Status::CANCELLED,
			static::TRANSITION_PERFORM => TradingEntity\Status::DEDUCTED,
			static::TRANSITION_RECEIVE => TradingEntity\Status::FINISHED,
		];
	}

	public function returnStatusTitle(string $status) : string
	{
		return self::getLocale('RETURN_STATUS_' . mb_strtoupper($status), null, $status);
	}

	public function returnStatuses() : array
	{
		return [
			static::RETURN_POLICY_IN_TRANSIT,
			static::RETURN_POLICY_READY,
		];
	}
}