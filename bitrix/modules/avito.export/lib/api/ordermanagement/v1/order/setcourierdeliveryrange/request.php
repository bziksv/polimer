<?php
namespace Avito\Export\Api\OrderManagement\V1\Order\SetCourierDeliveryRange;

use Avito\Export\Api;
use Avito\Export\Data;
use Bitrix\Main;

class Request extends Api\RequestWithToken
{
	protected $query = [];

	public function url() : string
	{
		return 'https://api.avito.ru/order-management/1/order/setCourierDeliveryRange';
	}

	public function method() : string
	{
		return Main\Web\HttpClient::HTTP_POST;
	}

	public function orderId(string $orderId) : void
	{
		$this->query['orderId'] = $orderId;
	}

	public function address(string $address) : void
	{
		$this->query['address'] = $address;
	}

    public function addressDetails(string $addressDetail) : void
    {
        $this->query['addressDetail'] = $addressDetail;
    }

    public function startDate(Main\Type\DateTime $startDate) : void
    {
        $this->query['startDate'] = Data\DateTime::stringify($startDate);
    }

    public function endDate(Main\Type\DateTime $endDate) : void
    {
        $this->query['endDate'] = Data\DateTime::stringify($endDate);
    }

    public function intervalType(string $intervalType) : void
    {
        $this->query['intervalType'] = $intervalType;
    }

    public function name(string $name) : void
    {
        $this->query['name'] = $name;
    }

    public function phone(string $phone) : void
    {
        $this->query['phone'] = $phone;
    }

	public function query() : ?array
	{
		return $this->query;
	}

//	protected function buildTransport() : Main\Web\HttpClient
//	{
//		return new MockTransport();
//	}

    protected function validationQueue($data, Main\Web\HttpClient $transport) : Api\Validator\Queue
    {
        return parent::validationQueue($data, $transport)
            ->add(new Api\Validator\SuccessTrue($data, $transport));
    }

	protected function buildResponse($data) : Api\Response
	{
		return new Response($data);
	}
}
