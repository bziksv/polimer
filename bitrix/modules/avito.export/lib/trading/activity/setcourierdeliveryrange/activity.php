<?php
namespace Avito\Export\Trading\Activity\SetCourierDeliveryRange;

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
		return 'send/setCourierDeliveryRange';
	}

    public function note(Api\OrderManagement\Model\Order $order) : ?string
    {
        return self::getLocale('NOTE');
    }

	public function uiOptions() : ?array
	{
		return [
			'height' => 320,
		];
	}

	public function payload(array $values) : array
	{
        $payload = array_diff_key($values, [ 'datetime' => true ]);
		$payload += $this->timeInterval($values['datetime']);
		$payload['phone'] = Data\Phone::sanitize($values['phone']);

		return $payload;
	}

    public function timeInterval(string $dateSerialized) : array
    {
        $interval = explode('|', $dateSerialized);

        return [
	        'startDate' => Data\DateTime::cast($interval[0]),
	        'endDate' => Data\DateTime::cast($interval[1]),
	        'intervalType' => $interval[2],
        ];
    }

	public function fields(Trading\Entity\Sale\Order $saleOrder, Api\OrderManagement\Model\Order $externalOrder) : array
	{
		return [
            'address' => [
                'TYPE' => 'courierAddress',
                'NAME' => self::getLocale('ADDRESS'),
                'MANDATORY' => 'Y',
                'SETTINGS' => [
					'SIZE' => 30,
                    'ORDER_ID' => $externalOrder->id(),
                    'TRADING_ID' => $this->exchangeId,
                ],
            ],
			'addressDetails' => [
				'TYPE' => 'string',
				'NAME' => self::getLocale('ADDRESS_DETAILS'),
                'SETTINGS' => [
	                'SIZE' => 35,
                    'ROWS' => 3,
                ]
			],
            'datetime' => [
                'TYPE' => 'courierTime',
                'NAME' => self::getLocale('DATETIME'),
                'MANDATORY' => 'Y',
            ],
            'senderName' => [
                'TYPE' => 'string',
                'NAME' => self::getLocale('NAME'),
                'MANDATORY' => 'Y',
	            'SETTINGS' => [
		            'SIZE' => 30,
	            ],
            ],
            'phone' => [
                'TYPE' => 'string',
                'NAME' => self::getLocale('PHONE'),
                'MANDATORY' => 'Y',
            ]
		];
	}

	public function values(Trading\Entity\Sale\Order $saleOrder, Api\OrderManagement\Model\Order $externalOrder) : array
	{
		$state = Trading\State\Repository::forOrder($externalOrder->id());
		$values = $state->get('COURIER_DELIVERY_RANGE');

		if (is_array($values)) { return $values; }

        $values = \CUserOptions::GetOption('AVITO_EXPORT', 'COURIER_DELIVERY_RANGE');

		if (is_array($values)) { return $values; }

        return [];
	}
}
