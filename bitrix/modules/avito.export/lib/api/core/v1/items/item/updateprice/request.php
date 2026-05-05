<?php
namespace Avito\Export\Api\Core\V1\Items\Item\UpdatePrice;

use Avito\Export\Api;
use Avito\Export\Assert;
use Bitrix\Main;

/**
 * @method Response execute()
 */
class Request extends Api\RequestWithToken
{
    protected $query = [];
	/** @var int */
    protected $itemId;

    public function url() : string
	{
		Assert::notNull($this->itemId, 'itemId');

		return 'https://api.avito.ru/core/v1/items/' . $this->itemId . '/update_price';
	}

	public function method() : string
	{
		return Main\Web\HttpClient::HTTP_POST;
	}

    public function itemId(int $itemId) : void
    {
        $this->itemId = $itemId;
    }

    public function price(int $price): void
    {
        $this->query['price'] = $price;
    }

	public function query() : ?array
	{
        Assert::notNull($this->query['price'], 'price');

		return $this->query;
	}

	/*protected function buildTransport() : Main\Web\HttpClient
	{
		return new ErrorMockTransport();
	}*/

	protected function buildResponse($data) : Api\Response
	{
		return new Response($data);
	}
}
