<?php
namespace Yandex\Market\Api\Partner\UpdateOrderNumber;

use Yandex\Market;
use Yandex\Market\Reference\Assert;

/**
 * @method Response execute()
 */
class Request extends Market\Api\Partner\Reference\Request
{
	protected $orderId;
    protected $externalOrderId;

    public function getMethod()
    {
        return \Bitrix\Main\Web\HttpClient::HTTP_POST;
    }

	public function getPath()
	{
		return '/campaigns/' . $this->getCampaignId() . '/orders/' . $this->getOrderId() .'/external-id';
	}

    public function getQuery()
    {
        return [
            'externalOrderId' => $this->getExternalOrderId()
        ];
    }

	public function setOrderId($orderId)
	{
		$this->orderId = $orderId;
	}

    public function setExternalOrderId($externalOrderId)
    {
        $this->externalOrderId = $externalOrderId;
        return $this;
    }

    public function getOrderId()
    {
        Assert::notNull($this->orderId, 'orderId');
        return (string)$this->orderId;
    }
    public function getExternalOrderId()
    {
        Assert::notNull($this->externalOrderId, 'externalOrderId');
        return (string)$this->externalOrderId;
    }


    public function getQueryFormat()
    {
        return self::DATA_TYPE_JSON;
    }
}