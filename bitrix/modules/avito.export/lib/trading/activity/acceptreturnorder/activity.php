<?php
namespace Avito\Export\Trading\Activity\AcceptReturnOrder;

use Avito\Export\Concerns;
use Avito\Export\Api;
use Avito\Export\Trading;
use Avito\Export\Trading\Activity\Reference;
use Avito\Export\Data;

class Activity extends Reference\FormActivity
{
	use Concerns\HasLocale;

	public function title(Api\OrderManagement\Model\Order $order) : string
	{
		return self::getLocale('TITLE', null, $this->name);
	}

	public function path() : string
	{
		return 'send/acceptReturnOrder';
	}

	public function payload(array $values) : array
	{
		return [
			'recipientName' => $values['recipientName'],
			'recipientPhone' => Data\Phone::sanitize($values['recipientPhone']),
			'terminalNumber' => $values['terminalNumber'],
		];
	}

	public function fields(Trading\Entity\Sale\Order $saleOrder, Api\OrderManagement\Model\Order $externalOrder) : array
	{
		return [
			'recipientName' => [
				'TYPE' => 'string',
				'NAME' => self::getLocale('RECIPIENT_NAME'),
				'MANDATORY' => 'Y',
			],
			'recipientPhone' => [
				'TYPE' => 'string',
				'NAME' => self::getLocale('RECIPIENT_PHONE'),
				'MANDATORY' => 'Y',
			],
			'terminalNumber' => [
				'TYPE' => 'string',
				'NAME' => self::getLocale('TERMINAL_NUMBER'),
				'MANDATORY' => 'Y',
			],
		];
	}

	public function values(Trading\Entity\Sale\Order $saleOrder, Api\OrderManagement\Model\Order $externalOrder) : array
	{
		$state = Trading\State\Repository::forOrder($externalOrder->id());
		$values = $state->get('ACCEPT_RETURN_ORDER');

		if (is_array($values)) { return $values; }

		$values = \CUserOptions::GetOption('AVITO_EXPORT', 'ACCEPT_RETURN_ORDER');

		if (is_array($values)) { return $values; }

		return [];
	}
}
