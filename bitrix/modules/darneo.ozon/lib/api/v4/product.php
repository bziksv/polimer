<?php

namespace Darneo\Ozon\Api\v4;

use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Darneo\Ozon\Api\Base;
use Darneo\Ozon\Api\Config;
use Darneo\Ozon\Main\Helper\MethodTracker;

class Product extends Base
{
    public function infoLimit(): array
    {
        $uri = new Uri(Config::HOST . '/v4/product/info/limit');
        $result = $this->httpClient->post($uri->getUri());
        $result = Json::decode($result);

        $method = MethodTracker::internalMethod();
        $logId = $this->writeToLog($uri->getUri(), [], $result, $method);
        $result = array_merge($result, ['__system_log_id' => $logId]);

        return $result;
    }
}
