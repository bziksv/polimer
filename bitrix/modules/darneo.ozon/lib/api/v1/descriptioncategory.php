<?php

namespace Darneo\Ozon\Api\v1;

use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Darneo\Ozon\Api\Base;
use Darneo\Ozon\Api\Config;
use Darneo\Ozon\Main\Helper\MethodTracker;

class DescriptionCategory extends Base
{
    public function tree()
    {
        $uri = new Uri(Config::HOST . '/v1/description-category/tree');
        $dataPost = [
            'language' => Config::LANG,
        ];

        $encode = Json::encode($dataPost);
        $result = $this->httpClient->post($uri->getUri(), $encode);
        $result = Json::decode($result);

        $method = MethodTracker::internalMethod();
        $logId = $this->writeToLog($uri->getUri(), $dataPost, $result, $method);
        $result = array_merge($result, ['__system_log_id' => $logId]);

        return $result;
    }

    public function attribute(string $categoryId, string $typeId, $attributeType = 'ALL'): array
    {
        $uri = new Uri(Config::HOST . '/v1/description-category/attribute');
        $dataPost = [
            'attribute_type' => $attributeType,
            'description_category_id' => $categoryId,
            'type_id' => $typeId,
            'language' => Config::LANG,
        ];
        $encode = Json::encode($dataPost);
        $result = $this->httpClient->post($uri->getUri(), $encode);
        $result = Json::decode($result);

        $method = MethodTracker::internalMethod();
        $logId = $this->writeToLog($uri->getUri(), $dataPost, $result, $method);
        $result = array_merge($result, ['__system_log_id' => $logId]);

        return $result;
    }

    public function attributeValues(int $attributeId, string $categoryId, string $typeId, string $lastValue = ''): array
    {
        $uri = new Uri(Config::HOST . '/v1/description-category/attribute/values');
        $dataPost = [
            'attribute_id' => $attributeId,
            'description_category_id' => $categoryId,
            'language' => Config::LANG,
            'last_value_id' => $lastValue ?: 0,
            'limit' => 1000,
            'type_id' => $typeId,
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
