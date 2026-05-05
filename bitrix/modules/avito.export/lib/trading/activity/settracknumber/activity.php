<?php
namespace Avito\Export\Trading\Activity\SetTrackNumber;

use Avito\Export\Concerns;
use Avito\Export\Data;
use Avito\Export\Trading;
use Avito\Export\Api;
use Avito\Export\Trading\Activity\Reference as TradingReference;

class Activity extends TradingReference\FormActivity
{
	use Concerns\HasLocale;

	public function title(Api\OrderManagement\Model\Order $order) : string
	{
		$suffix = '';

		if (mb_strpos($this->name, 'fix') === 0)
		{
			$suffix = '_FIX';
		}
		else if ($order->schedules()->setTrackingNumberTill() === null)
		{
			$suffix = '_CHECK';
		}

		return self::getLocale('TITLE' . $suffix, null, $this->name);
	}

	public function note(Api\OrderManagement\Model\Order $order) : ?string
	{
		$suffix = '';
		$until = $order->schedules()->setTrackingNumberTill();

		if (mb_strpos($this->name, 'fix') === 0)
		{
			$suffix = '_FIX';
		}
		else if ($until === null)
		{
			$suffix = '_CHECK';
		}

		return self::getLocale('NOTE' . $suffix, [
			'#UNTIL#' => $until !== null ? Data\DateTime::format($until) : '',
			'#TRACK#' => $order->delivery()->trackingNumber(),
			'#SERVICE_URL#' => $this->service->urlManager()->orderView($order->id()),
		], '');
	}

	public function path() : string
	{
		return 'send/track';
	}

	public function payload(array $values) : array
	{
		return $values;
	}

	public function fields(Trading\Entity\Sale\Order $saleOrder, Api\OrderManagement\Model\Order $externalOrder) : array
	{
		return [
			'trackingNumber' => [
				'TYPE' => 'string',
				'NAME' => static::getLocale('TRACKING_NUMBER'),
				'MANDATORY' => 'Y',
			],
		];
	}

	public function values(Trading\Entity\Sale\Order $saleOrder, Api\OrderManagement\Model\Order $externalOrder) : array
	{
		return [
			'trackingNumber' => $externalOrder->delivery()->trackingNumber() ?: $saleOrder->trackingNumber(),
		];
	}
}
