<?php
namespace Avito\Export\Trading\Activity\SetDeliveryTerms;

use Avito\Export\Concerns;
use Avito\Export\Api;
use Avito\Export\Data;
use Avito\Export\Trading;
use Avito\Export\Trading\Activity\SetDeliveryCost;

class Activity extends SetDeliveryCost\Activity
{
	use Concerns\HasLocale;

	public function note(Api\OrderManagement\Model\Order $order) : ?string
	{
		$until = $order->schedules()->setTermsTill();
		$courier = $order->delivery()->courierInfo();

		return self::getLocale('NOTE', [
			'#UNTIL#' => $until !== null ? Data\DateTime::format($until) : '',
			'#SERVICE_NAME#' => $order->delivery()->serviceName(),
			'#ADDRESS#' => $courier !== null ? $courier->address() : '',
			'#COMMENT#' => $courier !== null ? $courier->comment() : '',
		]);
	}

	public function fields(Trading\Entity\Sale\Order $saleOrder, Api\OrderManagement\Model\Order $externalOrder) : array
	{
		return parent::fields($saleOrder, $externalOrder) + [
			'deliveryDate' => [
				'TYPE' => 'date',
				'NAME' => static::getLocale('DELIVERY_DATE'),
				'MANDATORY' => 'Y',
			],
		];
	}
}
