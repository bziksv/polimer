<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Avito\Export\Api;
use Avito\Export\Assert;
use Avito\Export\Trading;
use Avito\Export\Data;
use Avito\Export\Admin;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

Loc::loadMessages(__FILE__);

try
{
    if (!Main\Loader::includeModule('avito.export'))
    {
        throw new Main\SystemException(Loc::getMessage('AVITO_EXPORT_TOOLS_COURIER_ADDRESS_GET_COURIER_DELIVERY_RANGE_MODULE_REQUIRED'));
    }

    if (!Admin\Access::isReadAllowed())
    {
        throw new Main\AccessDeniedException(Loc::getMessage('AVITO_EXPORT_TOOLS_COURIER_ADDRESS_GET_COURIER_DELIVERY_RANGE_ACCESS_DENIED'));
    }

    $request = Main\Context::getCurrent()->getRequest();
    $orderId = $request->getPost('orderId');
    $exchangeId = $request->getPost('exchangeId');
    $address = $request->getPost('address');

    Assert::notNull($exchangeId, 'exchangeId');
    Assert::notNull($orderId, 'orderId');
    Assert::notNull($address, 'address');

    $trading = Trading\Setup\Model::getById($exchangeId);

    $client = new Api\OrderManagement\V1\Order\GetCourierDeliveryRange\Request();
    $client->token($trading->getSettings()->commonSettings()->token());
    $client->orderId($orderId);
    $client->address($address);

    /** @var Api\OrderManagement\V1\Order\GetCourierDeliveryRange\Response $response */
    $response = $client->execute();

    $options = [];

    /** @var Api\OrderManagement\V1\Order\GetCourierDeliveryRange\Result\DateOption $dateOption */
    foreach ($response->dateOptions() as $dateOption)
    {
        $formatDate = FormatDate(
            [
                'today' => 'today, d F',
                'tomorrow' => 'tomorrow, d F',
                'l, d F'
            ],
            $dateOption->date()
        );
        $formatDate = mb_strtoupper(mb_substr($formatDate, 0, 1)) . mb_substr($formatDate, 1);

        $option = [
			'ID' => $dateOption->date()->format('Y-m-d'),
            'VALUE' => $formatDate,
	        'INTERVALS' => [],
        ];

        /** @var Api\OrderManagement\V1\Order\GetCourierDeliveryRange\Result\TimeIntervals\TimeInterval $timeInterval */
        foreach($dateOption->timeIntervals() as $timeInterval)
        {
            $option['INTERVALS'][] = [
                'ID' => implode('|', [
	                Data\DateTime::stringify($timeInterval->startDate()),
	                Data\DateTime::stringify($timeInterval->endDate()),
	                $timeInterval->type(),
                ]),
                'VALUE' => $timeInterval->title(),
            ];
        }

        $options[] = $option;
    }

    $response = [
        'success' => true,
        'options' => $options,
    ];
}
catch (\Throwable $exception)
{
    $response = [
        'success' => false,
        'message' => $exception->getMessage(),
    ];
}

\CMain::FinalActions(Main\Web\Json::encode($response));
