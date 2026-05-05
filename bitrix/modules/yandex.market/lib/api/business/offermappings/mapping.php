<?php
namespace Yandex\Market\Api\Business\OfferMappings;

use Yandex\Market\Api\Reference;

class Mapping extends Reference\Model
{
    public function getMarketCategoryId()
    {
        return (int)$this->getField('marketCategoryId');
    }
    public function getMarketCategoryName()
    {
        return (string)$this->getField('marketCategoryName');
    }
}