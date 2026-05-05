<?php
namespace Avito\Export\Api\OrderManagement\V1\Orders;

use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Web\HttpClient;

class MockTransport extends HttpClient
{
	public function getError() : array
	{
		return [];
	}

	public function query($method, $url, $entityBody = null) : bool
	{
		return true;
	}

	/*
	 * "buyerInfo": {
            "fullName": "test",
            "phoneNumber": "+71111111111"
        },
        "courierInfo": {
            "address": "testaddress",
            "comment": "1234"
        },
        "terminalInfo": {
            "address": "testaddress",
            "code": "1234"
        }
	 * */

	public function getResult() : string
	{
		$text = '{
    "hasMore": false,
    "orders": [
        {
            "availableActions": [
                {
                    "name": "reject",
                    "required": false
                },
                {
                    "name": "setMarkings",
                    "required": false
                },
                {
                    "name": "getCourierDeliveryRange",
                    "required": false
                },
                {
                    "name": "setCourierDeliveryRange",
                    "required": true
                }
            ],
            "createdAt": "2023-07-24T06:01:19Z",
            "delivery": {
                "courierInfo": {
                    "address": "Москва, Смоленский бульвар, 22\/14",
                    "comment": "Тестовый заказ не приезжать"
                },
                "serviceName": "Яндекс� Доставка. В течение дня",
                "serviceType": "courier"
            },
            "id": "50000000072443451",
            "items": [
                {
                    "avitoId": "2664793807",
                    "chatId": "u2i-b990Ah_6Ibmp20aSff2MEA",
                    "count": 1,
                    "id": "233-318",
                    "location": "Москва",
                    "prices": {
                        "commission": 0.33,
                        "price": 15,
                        "total": 14.67
                    },
                    "title": "Туфли Полет Валькирии"
                }
            ],
            "prices": {
                "commission": 0.33,
                "price": 15,
                "total": 14.67
            },
            "schedules": [],
            "status": "on_confirmation",
            "updatedAt": "2023-07-24T06:02:07Z"
        }
    ]
}';

		return Encoding::convertEncoding($text, LANG_CHARSET, 'UTF-8');
	}

	public function getContentType() : string
	{
		return 'application/json';
	}
}