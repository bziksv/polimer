<?php

namespace Darneo\Ozon\Api\v1;

use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Darneo\Ozon\Api\Base;
use Darneo\Ozon\Api\Config;
use Darneo\Ozon\Main\Helper\MethodTracker;

class Warehouse extends Base
{
    public function list(): array
    {
        $uri = new Uri(Config::HOST . '/v1/warehouse/list');
        $result = $this->httpClient->post($uri->getUri());
        $result = Json::decode($result);

        $method = MethodTracker::internalMethod();
        $logId = $this->writeToLog($uri->getUri(), [], $result, $method);
        $result = array_merge($result, ['__system_log_id' => $logId]);

        return $result;
    }
}
