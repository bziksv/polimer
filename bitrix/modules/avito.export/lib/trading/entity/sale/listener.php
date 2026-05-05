<?php
namespace Avito\Export\Trading\Entity\Sale;

use Avito\Export\Api;
use Avito\Export\Assert;
use Avito\Export\Config;
use Avito\Export\Push;
use Avito\Export\Trading;
use Bitrix\Main;
use Bitrix\Sale;

class Listener extends EventHandler
{
	public const STATE_BEFORE = 1;
	public const STATE_AFTER = 2;

	protected static $orders = [];
	protected static $eventsFired = [];
	protected static $changes = [];

	protected $environment;

	/** @noinspection PhpUnused */
	public static function onBeforeSaleOrderSetField(Main\Event $event) : ?Main\EventResult
	{
		if (!static::isAdminRequest()) { return null; }

		/** @var Sale\Order $order */
		$order = $event->getParameter('ENTITY');
		$name = $event->getParameter('NAME');
		$value = $event->getParameter('VALUE');

		if ($name === 'STATUS_ID')
		{
			return static::process($order, 'send/status', [ 'status' => (string)$value ]);
		}

		if ($name === 'CANCELED' && $value === 'Y')
		{
			if (static::hasPaidPayment($order) || static::hasShippedShipment($order)) { return null; }

			return static::process($order, 'send/status', [ 'status' => Status::CANCELLED ]);
		}

		return null;
	}

	protected static function hasPaidPayment(Sale\Order $order) : bool
	{
		$result = false;

		/** @var Sale\Payment $payment */
		foreach ($order->getPaymentCollection() as $payment)
		{
			if ($payment->isPaid())
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	protected static function hasShippedShipment(Sale\Order $order) : bool
	{
		$result = false;

		/** @var Sale\Shipment $shipment */
		foreach ($order->getShipmentCollection() as $shipment)
		{
			if ($shipment->isSystem()) { continue; }

			if ($shipment->isAllowDelivery() || $shipment->isShipped())
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	public static function onBeforeSaleShipmentSetField(Main\Event $event) : ?Main\EventResult
	{
		if (!static::isAdminRequest()) { return null; }

		/** @var Sale\Shipment $shipment */
		$shipment = $event->getParameter('ENTITY');
		$name = $event->getParameter('NAME');
		$value = $event->getParameter('VALUE');
		$map = [
			Status::ALLOW_DELIVERY => true,
			Status::DEDUCTED => true,
		];

		if ($value !== 'Y' || !isset($map[$name])) { return null; }

		$order = $shipment->getOrder();

		if ($order === null) { return null; }

		return static::process($order, 'send/status', [ 'status' => $name ]);
	}

	/** @noinspection PhpUnused */
	public static function onBeforeSalePaymentSetField(Main\Event $event) : ?Main\EventResult
	{
		if (!static::isAdminRequest()) { return null; }

		/** @var Sale\Payment $payment */
		$payment = $event->getParameter('ENTITY');
		$name = $event->getParameter('NAME');
		$value = $event->getParameter('VALUE');

		if ($name !== 'PAID' || $value !== 'Y') { return null; }

		$order = $payment->getOrder();

		if ($order === null) { return null; }

		return static::process($order, 'send/status', [ 'status' => Status::PAID ]);
	}

	/** @noinspection PhpUnused */
	public static function onSaleStatusOrderChange(Main\Event $event) : void
	{
		/** @var Sale\Order $order */
		$order = $event->getParameter('ENTITY');
		$value = $order->getField('STATUS_ID');

		static::process($order, 'send/status', [ 'status' => (string)$value, ], true);
	}

	/** @noinspection PhpUnused */
	public static function onSaleOrderCanceled(Main\Event $event) : void
	{
		/** @var Sale\Order $order */
		$order = $event->getParameter('ENTITY');

		if (!$order->isCanceled()) { return; }

		static::process($order, 'send/status', [ 'status' => Status::CANCELLED ], true);
	}

	/** @noinspection PhpUnused */
	public static function onShipmentAllowDelivery(Main\Event $event) : void
	{
		/** @var Sale\Shipment $shipment */
		/** @var Sale\ShipmentCollection $shipmentCollection */
		$shipment = $event->getParameter('ENTITY');
		$shipmentCollection = $shipment->getCollection();

		if ($shipmentCollection === null || !$shipmentCollection->isAllowDelivery()) { return; }

		$order = $shipment->getOrder();

		if ($order === null) { return; }

		static::process($order, 'send/status', [ 'status' => Status::ALLOW_DELIVERY ], true);
	}

	/** @noinspection PhpUnused */
	public static function onShipmentDeducted(Main\Event $event) : void
	{
		/** @var Sale\Shipment $shipment */
		/** @var Sale\ShipmentCollection $shipmentCollection */
		$shipment = $event->getParameter('ENTITY');
		$shipmentCollection = $shipment->getCollection();

		if ($shipmentCollection === null || !$shipmentCollection->isShipped()) { return; }

		$order = $shipment->getOrder();

		if ($order === null) { return; }

		static::process($order, 'send/status', [ 'status' => Status::DEDUCTED ], true);
	}

	/** @noinspection PhpUnused */
	public static function onSaleOrderPaid(Main\Event $event) : void
	{
		/** @var Sale\Order $order */
		$order = $event->getParameter('ENTITY');

		if (!$order->isPaid()) { return; }

		static::process($order, 'send/status', [ 'status' => Status::PAID ], true);
	}

	/** @noinspection PhpUnused */
	public static function onSaleOrderBeforeSaved(Main\Event $event) : ?Main\EventResult
	{
		/** @var Sale\Order $order */
		$order = $event->getParameter('ENTITY');
		$changes = static::collectChanges($order);

		if (!static::isAdminRequest() || $order->isNew())
		{
			static::$changes[$order->getInternalId()] = $changes;
			return null;
		}

		return static::processFew($order, $changes);
	}

	/** @noinspection PhpUnused */
	public static function onSaleOrderSaved(Main\Event $event) : void
	{
		/** @var Sale\Order $order */
		$order = $event->getParameter('ENTITY');
		$orderId = $order->getInternalId();

		if (empty(static::$changes[$orderId]))
		{
			return;
		}

		static::processFew($order, static::$changes[$orderId], true);
		unset(static::$changes[$orderId]);
	}

	protected static function collectChanges(Sale\Order $order) : array
	{
		return array_filter([
			'send/marking' => static::collectMarkingChanges($order),
			'send/track' => static::collectTrackingChanges($order),
		]);
	}

	/** @noinspection PhpInternalEntityUsedInspection */
	protected static function collectMarkingChanges(Sale\Order $order) : ?array
	{
		$allCodes = [];
		$hasChanges = false;

		/** @var Sale\Shipment $shipment */
		foreach ($order->getShipmentCollection() as $shipment)
		{
			if ($shipment->isSystem()) { continue; }

			/** @var Sale\ShipmentItem $shipmentItem */
			foreach ($shipment->getShipmentItemCollection() as $shipmentItem)
			{
				$basketItem = $shipmentItem->getBasketItem();

				if ($basketItem === null) { continue; }

				$codes = [];

				/** @var Sale\ShipmentItemStore $itemStore */
				foreach ($shipmentItem->getShipmentItemStoreCollection() as $itemStore)
				{
					$markingCode = (string)$itemStore->getField('MARKING_CODE');

					if ($itemStore->getFields()->isChanged('MARKING_CODE'))
					{
						$hasChanges = true;
					}

					if ($markingCode === '') { continue; }

					$codes[] = $markingCode;
				}

				if (empty($codes)) { continue; }

				$allCodes[$basketItem->getBasketCode()] = $codes;
			}
		}

		return $hasChanges && !empty($allCodes) ? [ 'codes' => $allCodes ] : null;
	}

	/** @noinspection PhpInternalEntityUsedInspection */
	protected static function collectTrackingChanges(Sale\Order $order) : ?array
	{
		$trackingNumber = null;
		$hasChanges = false;

		/** @var Sale\Shipment $shipment */
		foreach ($order->getShipmentCollection() as $shipment)
		{
			if ($shipment->isEmpty() || $shipment->isSystem()) { continue; }

			$shipmentTrackingNumber = (string)$shipment->getField('TRACKING_NUMBER');

			if ($shipment->getFields()->isChanged('TRACKING_NUMBER'))
			{
				$hasChanges = true;
			}

			if ($shipmentTrackingNumber === '') { continue; }

			$trackingNumber = $shipmentTrackingNumber;
		}

		return $hasChanges && ($trackingNumber !== null) ? [ 'trackingNumber' => $trackingNumber ] : null;
	}

	protected static function isAdminRequest() : bool
	{
		$request = Main\Application::getInstance()->getContext()->getRequest();

		return ($request->isAdminSection() && !static::is1CRequest() && static::isUserAuthorized());
	}

	protected static function is1CRequest() : bool
	{
		return !empty($_SESSION['BX_CML2_EXPORT']);
	}

	protected static function isUserAuthorized() : bool
	{
		global $USER;

		return ($USER instanceof \CUser && $USER->IsAuthorized());
	}

	protected static function processFew(Sale\Order $order, array $changes, bool $alreadySaved = false) : ?Main\EventResult
	{
		$result = null;

		foreach ($changes as $path => $parameters)
		{
			$eventResult = static::process($order, $path, $parameters, $alreadySaved);

			if ($eventResult !== null && $eventResult->getType() === Main\EventResult::ERROR)
			{
				$result = $eventResult;
				break;
			}
		}

		return $result;
	}

	protected static function process(Sale\Order $order, string $path, array $parameters = [], bool $alreadySaved = false) : ?Main\EventResult
	{
		$procedure = null;

		try
		{
			$binding = static::orderBinding($order);

			if ($binding === null) { return null; }

			if (!$alreadySaved)
			{
				if ($order->isNew()) { return null; } // wait full save process
				if (!static::pushEvent($order, $path, $parameters)) { return null; }
			}
			else if (static::popEvent($order, $path, $parameters))
			{
				return null;
			}

			static::holdOrder($order, $alreadySaved);

			$trading = static::loadTrading($binding);
			$procedure = new Trading\Action\Procedure($trading, $path, $parameters + [
				'orderId' => $order->getId(),
				'externalId' => (string)$binding->getField('EXTERNAL_ORDER_ID'),
				'externalNumber' => $binding->getField('PARAMS')['NUMBER'] ?? (string)$binding->getField('EXTERNAL_ORDER_ID'),
				'alreadySaved' => $alreadySaved,
			]);

			$procedure->run();

			if ($procedure->needSync())
			{
				Trading\Action\Facade::syncOrder($trading, (string)$binding->getField('EXTERNAL_ORDER_ID'));
			}

			static::releaseOrder($order);

			return new Main\EventResult(Main\EventResult::SUCCESS);
		}
		catch (\Throwable $exception)
		{
			static::releaseOrder($order);

			if (
				$alreadySaved && $procedure !== null
				&& !($exception instanceof Api\Exception\HttpError && $exception->badFormatted())
			)
			{
				$procedure->logException($exception);
				$procedure->repeat();
			}

			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError($exception->getMessage()),
				Config::getModuleName()
			);
		}
	}

	protected static function pushEvent(Sale\Order $order, string $path, array $parameters) : bool
	{
		$orderId = $order->getId();

		if (!isset(static::$eventsFired[$orderId]))
		{
			static::$eventsFired[$orderId] = [];
		}

		if (!isset(static::$eventsFired[$orderId][$path]))
		{
			static::$eventsFired[$orderId][$path] = [];
		}

		foreach (static::$eventsFired[$orderId][$path] as $fired)
		{
			if ($parameters === $fired)
			{
				return false;
			}
		}

		static::$eventsFired[$orderId][$path][] = $parameters;

		return true;
	}

	protected static function popEvent(Sale\Order $order, string $path, array $parameters) : bool
	{
		$orderId = $order->getId();

		if (!isset(static::$eventsFired[$orderId][$path])) { return false; }

		$result = false;

		foreach (static::$eventsFired[$orderId][$path] as $firedKey => $fired)
		{
			if ($parameters === $fired)
			{
				$result = true;
				unset(static::$eventsFired[$orderId][$path][$firedKey]);
				break;
			}
		}

		return $result;
	}

	protected static function orderBinding(Sale\Order $order) : ?Sale\TradeBindingEntity
	{
		$result = null;

		/** @var Sale\TradeBindingEntity $tradeBinding */
		foreach ($order->getTradeBindingCollection() as $tradeBinding)
		{
			$platform = $tradeBinding->getTradePlatform();

			if ($platform !== null && $platform->getCode() === Platform::CODE)
			{
				$result = $tradeBinding;
				break;
			}
		}

		return $result;
	}

	protected static function loadTrading(Sale\TradeBindingEntity $binding) : Trading\Setup\Model
	{
		/** @var array $params */
		$params = $binding->getField('PARAMS');
		$setupId = $params['SETUP_ID'] ?? null;

		Assert::notNull($setupId, 'setupId');

		return Trading\Setup\Model::getById($setupId);
	}

	public static function getOrder(int $orderId) : ?Sale\Order
	{
		return static::$orders[$orderId][0] ?? null;
	}

	public static function orderState(int $orderId) : ?int
	{
		return static::$orders[$orderId][1] ?? null;
	}

	protected static function holdOrder(Sale\Order $order, bool $alreadySaved) : void
	{
		static::$orders[$order->getId()] = [
			$order,
			$alreadySaved ? static::STATE_AFTER : static::STATE_BEFORE
		];
	}

	protected static function releaseOrder(Sale\Order $order) : void
	{
		$orderId = $order->getId();

		if (isset(static::$orders[$orderId]))
		{
			unset(static::$orders[$orderId]);
		}
	}

	public function __construct(Container $environment)
	{
		$this->environment = $environment;
	}

	public function handlers() : array
	{
		return [
			[
				'module' => 'sale',
				'event' => 'onBeforeSaleOrderSetField',
				'sort' => 1000,
			],
			[
				'module' => 'sale',
				'event' => 'onBeforeSaleShipmentSetField',
				'sort' => 1000,
			],
			[
				'module' => 'sale',
				'event' => 'onBeforeSalePaymentSetField',
				'sort' => 1000,
			],
			[
				'module' => 'sale',
				'event' => 'onSaleOrderBeforeSaved',
				'sort' => 1000,
			],
			[
				'module' => 'sale',
				'event' => 'onSaleOrderSaved',
			],
			[
				'module' => 'sale',
				'event' => 'onSaleStatusOrderChange',
			],
			[
				'module' => 'sale',
				'event' => 'onSaleOrderCanceled',
			],
			[
				'module' => 'sale',
				'event' => 'onSaleOrderPaid',
			],
			[
				'module' => 'sale',
				'event' => 'onShipmentAllowDelivery',
			],
			[
				'module' => 'sale',
				'event' => 'onShipmentDeducted',
			],
		];
	}
}