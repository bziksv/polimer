<?php
namespace Avito\Export\Trading\Activity\SetDeliveryCost;

use Avito\Export\Concerns;
use Avito\Export\Api;
use Avito\Export\Data;
use Avito\Export\Trading;
use Avito\Export\Trading\Activity\Reference;

class Activity extends Reference\FormActivity
{
	use Concerns\HasLocale;

	public function title(Api\OrderManagement\Model\Order $order) : string
	{
		return self::getLocale('TITLE', null, $this->name);
	}

	public function note(Api\OrderManagement\Model\Order $order) : ?string
	{
		$until = $order->schedules()->setTermsTill();
		$terminal = $order->delivery()->terminalInfo();

		return self::getLocale('NOTE', [
			'#UNTIL#' => $until !== null ? Data\DateTime::format($until) : '',
			'#SERVICE_NAME#' => $order->delivery()->serviceName(),
			'#ADDRESS#' => $terminal !== null ? $terminal->address() . ' #' . $terminal->code() : null,
		]);
	}

	public function path() : string
	{
		return 'send/deliveryTerms';
	}

	public function payload(array $values) : array
	{
		return $values;
	}

	public function fields(Trading\Entity\Sale\Order $saleOrder, Api\OrderManagement\Model\Order $externalOrder) : array
	{
		return [
			'deliveryCostRub' => [
				'TYPE' => 'string',
				'NAME' => self::getLocale('DELIVERY_COST_RUB'),
				'MANDATORY' => 'Y',
			],
		];
	}

	public function values(Trading\Entity\Sale\Order $saleOrder, Api\OrderManagement\Model\Order $externalOrder) : array
	{
		return [
			'deliveryCostRub' => $saleOrder->deliveryPrice(),
		];
	}
}
