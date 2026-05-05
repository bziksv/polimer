<?php
namespace Avito\Export\Trading\Entity\Sale;

use Bitrix\Sale;
use Avito\Export\Assert;

class OrderRegistry
{
	public const XML_ID_PREFIX = 'AVITO_';

	protected $environment;

	public function __construct(Container $environment)
	{
		$this->environment = $environment;
	}

	public function create(string $siteId, int $personType) : Order
	{
		$orderClassName = $this->saleOrderClassName();
		$saleOrder = $orderClassName::create(
			$siteId,
			$this->environment->anonymousUser()->id(),
			$this->environment->currency()->id()
		);
		$saleOrder->setPersonTypeId($personType);

		return $this->makeOrder($this->environment, $saleOrder);
	}

	public function search(string $externalId) : ?int
	{
		$platform = $this->environment->platform();

		Assert::notNull($platform->id(), '$platform->id()');

		$query = Sale\TradingPlatform\OrderTable::getList([
			'filter' => [
				'=TRADING_PLATFORM_ID' => $platform->id(),
				'=EXTERNAL_ORDER_ID' => $externalId,
			],
			'select' => [ 'ORDER_ID' ],
		]);

		if ($order = $query->fetch())
		{
			return (int)$order['ORDER_ID'];
		}

		return null;
	}

	public function searchBroken(string $externalId) : ?int
	{
		$query = Sale\Order::getList([
			'filter' => [
				'=XML_ID' => static::XML_ID_PREFIX . $externalId
			],
			'select' => [ 'ID' ],
		]);

		if ($order = $query->fetch())
		{
			return (int)$order['ID'];
		}

		return null;
	}

	public function searchFew(array $externalIds) : array
	{
		if (empty($externalIds)) { return []; }

		$map = [];
		$platform = $this->environment->platform();

		$query = Sale\TradingPlatform\OrderTable::getList([
			'filter' => [
				'=TRADING_PLATFORM_ID' => $platform->id(),
				'=EXTERNAL_ORDER_ID' => $externalIds,
			],
			'select' => [
				'ORDER_ID',
				'EXTERNAL_ORDER_ID',
			]
		]);
		while ($order = $query->fetch())
		{
			$map[$order['EXTERNAL_ORDER_ID']] = $order['ORDER_ID'];
		}

		return $map;
	}

	public function loadByExternalId(string $externalId) : ?Order
	{
		$orderId = $this->search($externalId);

		if ($orderId === null) { return null; }

		return $this->load($orderId);
	}

	public function load(int $orderId) : ?Order
	{
		$listenerOrder = Listener::getOrder($orderId);

		if ($listenerOrder !== null)
		{
			return $this->makeOrder($this->environment, $listenerOrder, Listener::orderState($orderId));
		}

		$orderClassName = $this->saleOrderClassName();
		$saleOrder = $orderClassName::load($orderId);

		if ($saleOrder === null) { return null; }

		return $this->makeOrder($this->environment, $saleOrder);
	}

	/**
	 * @param int $orderId
	 *
	 * @return array{EXTERNAL_ORDER_ID: string, SETUP_ID: int|null, PARAMS: array}|null
	 */
	public function searchPlatform(int $orderId) : ?array
	{
		$result = null;

		$query = Sale\TradingPlatform\OrderTable::getList([
			'filter' => [
				'=ORDER_ID' => $orderId,
				'=TRADING_PLATFORM_ID' => $this->environment->platform()->id(),
			],
			'select' => [ 'TRADING_PLATFORM_ID', 'EXTERNAL_ORDER_ID', 'PARAMS' ],
			'limit' => 1,
		]);

		if ($row = $query->fetch())
		{
			$result = [
				'EXTERNAL_ORDER_ID' => (string)$row['EXTERNAL_ORDER_ID'],
				'SETUP_ID' => isset($row['PARAMS']['SETUP_ID']) ? (int)$row['PARAMS']['SETUP_ID'] : null,
				'PARAMS' => $row['PARAMS']
			];
		}

		return $result;
	}

	protected function makeOrder(Container $environment, Sale\Order $order, int $listenerState = null) : Order
	{
		return new Order($environment, $order, $listenerState);
	}

	/**
	 * @return Sale\Order
	 * @noinspection PhpDocSignatureInspection
	 */
	protected function saleOrderClassName() : string
	{
		$registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);

		return $registry->getOrderClassName();
	}
}