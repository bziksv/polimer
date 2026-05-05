<?php

namespace Darneo\Ozon\Api\v3;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Darneo\Ozon\Api\Base;
use Darneo\Ozon\Api\Config;
use Darneo\Ozon\Main\Helper\MethodTracker;

class Product extends Base
{
    public function import(array $item): array
    {
        if ($this->isTest()) {
            $result = [Loc::getMessage('DARNEO_OZON_API_IS_TEST_MESSAGE')];
        } else {
            $uri = new Uri(Config::HOST . '/v3/product/import');
            $dataPost = [
                'items' => [$item]
            ];
            $encode = Json::encode($dataPost);
            $result = $this->httpClient->post($uri->getUri(), $encode);
            $result = Json::decode($result);

            $method = MethodTracker::internalMethod();
            $logId = $this->writeToLog($uri->getUri(), $dataPost, $result, $method);
            $result = array_merge($result, ['__system_log_id' => $logId]);
        }

        return $result;
    }

    public function infoStocks(array $productIds, string $lastId = '', $visibility = 'ALL'): array
    {
        $uri = new Uri(Config::HOST . '/v3/product/info/stocks');
        $dataPost = [
            'filter' => [
                'visibility' => $visibility,
                'product_id' => $productIds,
            ],
            'last_id' => $lastId,
            'limit' => 1000,
        ];
        $encode = Json::encode($dataPost);
        $result = $this->httpClient->post($uri->getUri(), $encode);
        $result = Json::decode($result);

        $method = MethodTracker::internalMethod();
        $logId = $this->writeToLog($uri->getUri(), $dataPost, $result, $method);
        $result = array_merge($result, ['__system_log_id' => $logId]);

        return $result;
    }
}
