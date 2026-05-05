<?php
namespace Avito\Export\Trading\Entity\Sale;

use Bitrix\Catalog;
use Bitrix\Main;
use Bitrix\Sale;
use Avito\Export\Data;
use Avito\Export\Assert;
use Avito\Export\Concerns;

class Order
{
	use Concerns\HasLocale;
	use Concerns\HasOnce;

	protected const XML_ID_PREFIX = 'avito';
	protected const UNKNOWN_PRODUCT_ID_START = 999999000;

	protected $environment;
	protected $saleOrder;
	protected $isStartField;
	protected $listenerState;
	protected $initialChanged = [];
	protected $relatedProperties = [];
	protected $afterSaveCallbacks = [];

	public function __construct(Container $environment, Sale\Order $saleOrder, int $listenerState = null)
	{
		$this->environment = $environment;
		$this->saleOrder = $saleOrder;
		$this->listenerState = $listenerState;
		/** @noinspection PhpInternalEntityUsedInspection */
		$this->initialChanged = $listenerState === Listener::STATE_AFTER
			? $saleOrder->getFields()->getChangedValues()
			: [];
	}

	public function id() : ?int
	{
		return $this->saleOrder->getId();
	}

	public function accountNumber() : ?string
	{
		return $this->saleOrder->getField('ACCOUNT_NUMBER');
	}

	public function personType() : int
	{
		return (int)$this->saleOrder->getPersonTypeId();
	}

	public function hasViewAccess(int $userId) : bool
	{
		return $this->hasStatusAccess($userId, 'view');
	}

	public function hasUpdateAccess(int $userId) : bool
	{
		return $this->hasStatusAccess($userId, 'update');
	}

	protected function hasStatusAccess(int $userId, string $action) : bool
	{
		$registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);
		$statusClass = $registry->getOrderStatusClassName();
		$current = $this->saleOrder->getField('STATUS_ID');
		$allowedStatuses = $statusClass::getStatusesUserCanDoOperations($userId, [ $action ]);

		return in_array($current, $allowedStatuses, true);
	}

	public function initialize() : void
	{
		Sale\DiscountCouponsManager::init(Sale\DiscountCouponsManagerBase::MODE_EXTERNAL);

		$this->isStartField = $this->saleOrder->isStartField();
		$this->saleOrder->setMathActionOnly(true);
	}

	public function finalize() : void
	{
		$this->saleOrder->setMathActionOnly();

		if (!$this->isStartField) { return; }

		$hasMeaningfulFields = $this->saleOrder->hasMeaningfulField();
		$finalActionResult = $this->saleOrder->doFinalAction($hasMeaningfulFields);

		if (!$finalActionResult->isSuccess())
		{
			$this->mark(
				implode(PHP_EOL, $finalActionResult->getErrorMessages()),
				'FINAL_ACTION'
			);
		}
	}

	public function save() : Main\Result
	{
		if ($this->listenerState === Listener::STATE_BEFORE)
		{
			return new Main\Result();
		}

		if ($this->listenerState === Listener::STATE_AFTER)
		{
			$orderResult = $this->saveOrderRow();

			if (!$orderResult->isSuccess()) { return $orderResult; }

			return $this->savePropertyCollection();
		}

		$saveResult = $this->saleOrder->save();

		if ($saveResult->isSuccess())
		{
			$this->callAfterSave();
		}

		return $saveResult;
	}

	protected function afterSave(callable $function, ...$arguments) : void
	{
		$this->afterSaveCallbacks[] = [
			$function,
			$arguments,
		];
	}

	protected function callAfterSave() : void
	{
		$callbacks = $this->afterSaveCallbacks;
		$this->afterSaveCallbacks = [];

		foreach ($callbacks as [$callback, $arguments])
		{
			call_user_func($callback, ...$arguments);
		}
	}

	/** @noinspection PhpInternalEntityUsedInspection */
	protected function saveOrderRow() : Main\Result
	{
		$changed = array_diff(
			$this->saleOrder->getFields()->getChangedValues(),
			$this->initialChanged
		);
		$changed = array_intersect_key($changed, Sale\Internals\OrderTable::getEntity()->getFields());

		if (empty($changed)) { return new Main\Result(); }

		return Sale\Internals\OrderTable::update($this->saleOrder->getId(), $changed);
	}

	protected function savePropertyCollection() : Main\Result
	{
		if (!$this->saleOrder->getPropertyCollection()->isChanged()) { return new Main\Result(); }

		return $this->saleOrder->getPropertyCollection()->save();
	}

	public function userId() : ?int
	{
		return Data\Number::cast($this->saleOrder->getField('USER_ID'));
	}

	public function fillUser(int $userId) : void
	{
		if ($userId <= 0) { return; }

		$basket = $this->saleOrder->getBasket();

		$this->saleOrder->setFieldNoDemand('USER_ID', $userId);

		if ($basket && $this->saleOrder->isNew())
		{
			$fuserId = Sale\Fuser::getIdByUserId($userId);
			$basket->setFUserId($fuserId);
		}
	}

	public function fillDateInsert(Main\Type\DateTime $date) : Main\Result
	{
		return $this->saleOrder->setField('DATE_INSERT', $date);
	}

	public function fillTradingPlatform(string $externalId, int $setupId, array $extParams = []) : Main\Result
	{
		/** @var Sale\TradeBindingEntity $binding */
		$binding = $this->saleOrder->getTradeBindingCollection()->createItem();

		return $binding->setFields([
			'EXTERNAL_ORDER_ID' => $externalId,
			'TRADING_PLATFORM_ID' => $this->environment->platform()->id(),
			'PARAMS' => [ 'SETUP_ID' => $setupId ] + $extParams,
		]);
	}

	public function fillXmlId(string $xmlId) : Main\Result
	{
		return $this->saleOrder->setField('XML_ID', OrderRegistry::XML_ID_PREFIX . $xmlId);
	}

	public function tradingParameter(string $key)
	{
		$parameters = $this->tradingParameters();

		return $parameters[$key] ?? null;
	}

	public function tradingParameters() : ?array
	{
		$result = null;

		/** @var Sale\TradeBindingEntity $tradeBinding */
		foreach ($this->saleOrder->getTradeBindingCollection() as $tradeBinding)
		{
			if ((int)$tradeBinding->getField('TRADING_PLATFORM_ID') !== (int)$this->environment->platform()->id()) { continue; }

			$parameters = $tradeBinding->getField('PARAMS');

			if (is_array($parameters))
			{
				$result = array_diff_key($parameters, [ 'SETUP_ID' => true ]);
			}

			break;
		}

		return $result;
	}

	public function fillStatus(string $status) : Main\Result
	{
		if ($status === Status::ALLOW_DELIVERY)
		{
			$result = $this->statusAllowDelivery();
		}
		else if ($status === Status::DEDUCTED)
		{
			$result = $this->statusShip();
		}
		else if ($status === Status::PAID)
		{
			$result = $this->statusPaid();
		}
		else if ($status === Status::CANCELLED)
		{
			$result = $this->statusCancelled();
		}
		else
		{
			$result = $this->statusCommon($status);
		}

		return $result;
	}

	protected function statusAllowDelivery() : Main\Result
	{
		$result = new Sale\Result();
		$result->setData([ 'CHANGED' => false ]);

		/** @var Sale\Shipment $shipment */
		foreach ($this->saleOrder->getShipmentCollection() as $shipment)
		{
			if ($shipment->isSystem()) { continue; }
			if ($shipment->isAllowDelivery()) { continue; }

			$shipmentResult = $shipment->allowDelivery();

			if ($shipmentResult->isSuccess())
			{
				$result->setData([ 'CHANGED' => true ]);
			}
			else
			{
				$result->addErrors($shipmentResult->getErrors());
			}
		}

		return $result;
	}

	protected function statusShip() : Main\Result
	{
		$result = new Sale\Result();
		$result->setData([ 'CHANGED' => false ]);

		/** @var Sale\Shipment $shipment */
		foreach ($this->saleOrder->getShipmentCollection() as $shipment)
		{
			if ($shipment->isSystem()) { continue; }
			if ($shipment->isShipped()) { continue; }

			$shipmentResult = $shipment->setField('DEDUCTED', 'Y');

			if ($shipmentResult->isSuccess())
			{
				$result->setData([ 'CHANGED' => true ]);
			}
			else
			{
				$result->addErrors($shipmentResult->getErrors());
			}
		}

		return $result;
	}

	protected function statusPaid() : Main\Result
	{
		$result = new Sale\Result();
		$result->setData([ 'CHANGED' => false ]);
		$needSyncPayment = false;

		/** @var Sale\Payment $payment */
		foreach ($this->saleOrder->getPaymentCollection() as $payment)
		{
			if ($payment->isPaid()) { continue; }

			if ($this->isPaymentSystemInner($payment))
			{
				$needSyncPayment = true;
				$payment->setFieldNoDemand('PAID', 'Y');
				$paymentResult = new Sale\Result();
			}
			else
			{
				$paymentResult = $payment->setPaid('Y');
			}

			if ($paymentResult->isSuccess())
			{
				$result->setData([ 'CHANGED' => true ]);
			}
			else
			{
				$result->addErrors($paymentResult->getErrors());
			}
		}

		if ($needSyncPayment)
		{
			$this->syncPaidSum();
		}

		return $result;
	}

	protected function isPaymentSystemInner(Sale\Payment $payment) : bool
	{
		$paySystem = $payment->getPaySystem();

		return $paySystem !== null && $paySystem->getField('ACTION_FILE') === 'inner';
	}

	protected function syncPaidSum() : void
	{
		$paymentCollection = $this->saleOrder->getPaymentCollection();

		if ($paymentCollection === null) { return; }

		$calculated = $paymentCollection->getPaidSum();
		$current = $this->saleOrder->getSumPaid();

		if (round($calculated, 2) !== round($current, 2))
		{
			$this->saleOrder->setFieldNoDemand('SUM_PAID', $calculated);
		}
	}

	/** @noinspection PhpInternalEntityUsedInspection */
	protected function statusCancelled() : Main\Result
	{
		$shipmentResult = $this->cancelShipment();

		if (!$shipmentResult->isSuccess()) { return $shipmentResult; }

		$paymentResult = $this->cancelPayment();

		if (!$paymentResult->isSuccess()) { return $paymentResult; }

		$result = $this->saleOrder->setField('CANCELED', 'Y');
		$result->setData([
			'CHANGED' => $this->saleOrder->getFields()->isChanged('CANCELED'),
		]);

		return $result;
	}

	protected function cancelShipment() : Main\Result
	{
		$result = new Sale\Result();

		/** @var Sale\Shipment $shipment */
		foreach ($this->saleOrder->getShipmentCollection() as $shipment)
		{
			if ($shipment->isSystem()) { continue; }

			$shipResult = $shipment->setField('DEDUCTED', 'N');

			if (!$shipResult->isSuccess())
			{
				$result->addErrors($shipResult->getErrors());
				continue;
			}

			$deliveryResult = $shipment->setField('ALLOW_DELIVERY', 'N');

			if (!$deliveryResult->isSuccess())
			{
				$result->addErrors($deliveryResult->getErrors());
			}
		}

		return $result;
	}

	protected function cancelPayment() : Main\Result
	{
		$result = new Sale\Result();

		/** @var Sale\Payment $payment */
		foreach ($this->saleOrder->getPaymentCollection() as $payment)
		{
			if (!$payment->isPaid()) { continue; }

			$paymentResult = $payment->setPaid('N');

			if (!$paymentResult->isSuccess())
			{
				$result->addErrors($paymentResult->getErrors());
			}
		}

		return $result;
	}

	/** @noinspection PhpInternalEntityUsedInspection */
	protected function statusCommon(string $status) : Main\Result
	{
		$result = $this->saleOrder->setField('STATUS_ID', $status);
		$result->setData([
			'CHANGED' => $this->saleOrder->getFields()->isChanged('STATUS_ID'),
		]);

		return $result;
	}

	public function fillTrackingNumber(string $trackingNumber) : Main\Result
	{
		$shipmentCollection = $this->saleOrder->getShipmentCollection();
		$result = new Main\Result();

		foreach ($shipmentCollection as $shipment)
		{
			if ($shipment->isSystem()) { continue; }

			$result = $shipment->setField('TRACKING_NUMBER', $trackingNumber);
			break;
		}

		return $result;
	}

	public function trackingNumber() : ?string
	{
		$shipmentCollection = $this->saleOrder->getShipmentCollection();
		$result = null;

		foreach ($shipmentCollection as $shipment)
		{
			if ($shipment->isSystem()) { continue; }

			$result = $shipment->getField('TRACKING_NUMBER');
			break;
		}

		return $result;
	}

	public function createEmptyBasket() : void
	{
		/** @var Sale\Basket $basketClassName */
		$registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);
		$basketClassName = $registry->getBasketClassName();
		$basket = $basketClassName::create($this->saleOrder->getSiteId());
		$fUserId = Sale\Fuser::getIdByUserId($this->saleOrder->getUserId());

		$basket->setFUserId($fUserId);

		$this->saleOrder->setBasket($basket);
	}

	public function addProduct(?int $productId, float $quantity, array $data = []) : void
	{
		/** @var Sale\Basket $basket */
		$basket = $this->saleOrder->getBasket();

		Assert::notNull($basket, '$basket');

		$data += [
			'CURRENCY' => $this->saleOrder->getCurrency(),
			'QUANTITY' => $quantity,
			'PRODUCT_PROVIDER_CLASS' => Catalog\Product\Basket::getDefaultProviderName(),
			'LID' => $this->saleOrder->getSiteId(),
		];

		if (isset($data['PRICE']))
		{
			$data['CUSTOM_PRICE'] = 'Y';
		}

		if (isset($data['EXTERNAL_ID']))
		{
			$orderId = $data['EXTERNAL_ORDER_ID'] ?? time();

			$data['XML_ID'] = sprintf('%s_%s_%s', static::XML_ID_PREFIX, $orderId, $data['EXTERNAL_ID']);
		}

		if ($productId === null)
		{
			$productId = static::UNKNOWN_PRODUCT_ID_START + random_int(0, 999);
			unset($data['PRODUCT_PROVIDER_CLASS']);

			$this->mark(
				sprintf('%s: %s', $data['NAME'], self::getLocale('PRODUCT_NOT_FOUND')),
				'PRODUCT_ERROR_' . ($data['EXTERNAL_ID'] ?? $data['PRODUCT_ID'])
			);
		}

		$this->createBasketItem($basket, $productId, $data);
	}

	protected function createBasketItem(Sale\Basket $basket, int $productId, array $values) : void
	{
		/** @var Sale\BasketItem $basketItem */
		$basketItem = $basket->createItem('catalog', $productId);
		$settableMap = array_flip($basketItem::getSettableFields());
		$alreadySet = [];

		// properties

		$propertyCollection = $basketItem->getPropertyCollection();

		if ($propertyCollection !== null && !empty($values['PROPS']))
		{
			$propertyCollection->redefine($values['PROPS']);
		}

		$alreadySet += [
			'PROPS' => true,
		];

		// preset

		$presetValues = array_intersect_key($values, $settableMap, [
			'PRODUCT_PROVIDER_CLASS' => true,
			'CALLBACK_FUNC' => true,
			'PAY_CALLBACK_FUNC' => true,
			'SUBSCRIBE' => true,
			'NAME' => true,
		]);

		$basketItem->setFields($presetValues);
		$alreadySet += $presetValues;

		// provider data

		$providerData = Sale\Provider::getProductData($basket, [], $basketItem);
		$basketCode = $basketItem->getBasketCode();

		if (isset($providerData[$basketCode]) && is_array($providerData[$basketCode]))
		{
			$values += array_diff_key($providerData[$basketCode], [
				'CAN_BUY' => true,
                'DISCOUNT_PRICE' => true,
			]);
		}

		// set quantity

		$setQuantityResult = $basketItem->setField('QUANTITY', $values['QUANTITY']);

		if (!$setQuantityResult->isSuccess())
		{
			$this->mark(
				sprintf('%s: %s', $values['NAME'], implode(PHP_EOL, $setQuantityResult->getErrors())),
				'PRODUCT_ERROR_' . ($values['EXTERNAL_ID'] ?? $values['PRODUCT_ID'])
			);

			/** @noinspection PhpInternalEntityUsedInspection */
			$basketItem->setFieldNoDemand('QUANTITY', $values['QUANTITY']);
		}
		else if (!$basketItem->canBuy())
		{
			$this->mark(
				sprintf('%s: %s', $values['NAME'], self::getLocale('PRODUCT_CANT_BUY')),
				'PRODUCT_ERROR_' . ($values['EXTERNAL_ID'] ?? $values['PRODUCT_ID'])
			);

			$basketItem->setField('CAN_BUY', 'Y');
			/** @noinspection PhpInternalEntityUsedInspection */
			$basketItem->setFieldNoDemand('QUANTITY', $values['QUANTITY']);
		}

		$alreadySet += [
			'QUANTITY' => true,
		];

		// other

		$otherValues = array_diff_key($values, $alreadySet);
		$otherValues = array_intersect_key($otherValues, $settableMap);

		$basketItem->setFields($otherValues);
	}

	public function itemsExternalMap() : array
	{
		$result = [];

		/** @var Sale\BasketItem $basketItem */
		foreach ($this->saleOrder->getBasket() as $basketItem)
		{
			$basketCode = $basketItem->getBasketCode();
			$externalId = $this->itemExternalId($basketCode);

			if ($externalId === null) { continue; }

			$result[$basketCode] = $externalId;
		}

		return $result;
	}

	public function itemData(string $basketCode) : array
	{
		$basket = $this->saleOrder->getBasket();

		Assert::notNull($basket, 'basket');

		/** @var Sale\BasketItem $basketItem */
		$basketItem = $basket->getItemByBasketCode($basketCode);

		Assert::notNull($basketItem, 'basketItem');

		return [
			'MARKING_CODE_GROUP' => $basketItem->getField('MARKING_CODE_GROUP'),
		];
	}

	public function itemMarkingCodes(string $basketCode) : array
	{
		$result = [];

		/** @var Sale\Shipment $shipment */
		foreach ($this->saleOrder->getShipmentCollection() as $shipment)
		{
			if ($shipment->isSystem()) { continue; }

			$shipmentItem = $shipment->getShipmentItemCollection()->getItemByBasketCode($basketCode);

			if ($shipmentItem === null) { continue; }

			/** @var Sale\ShipmentItemStore $itemStore */
			foreach ($shipmentItem->getShipmentItemStoreCollection() as $itemStore)
			{
				$markingCode = trim($itemStore->getField('MARKING_CODE'));

				if ($markingCode === '') { continue; }

				$result[] = $markingCode;
			}
		}

		return $result;
	}

	public function itemExternalId(string $basketCode) : ?string
	{
		$basket = $this->saleOrder->getBasket();

		Assert::notNull($basket, 'basket');

		/** @var Sale\BasketItem $basketItem */
		$basketItem = $basket->getItemByBasketCode($basketCode);

		Assert::notNull($basketItem, 'basketItem');

		$xmlId = (string)$basketItem->getField('XML_ID');
		[$prefix, , $externalId] = explode('_', $xmlId, 3);

		if ($prefix !== static::XML_ID_PREFIX) { return null; }

		return $externalId;
	}

	public function deliveryPrice() : ?float
	{
		$result = null;

		/** @var Sale\Shipment $shipment */
		foreach ($this->saleOrder->getShipmentCollection() as $shipment)
		{
			if ($shipment->isSystem()) { continue; }

			$result += $shipment->getPrice();
		}

		return $result;
	}

	public function fillShipment(int $deliveryId, array $data = []) : void
	{
		$shipment = $this->createOrderShipment($deliveryId, $data);

		$this->fillShipmentBasket($shipment);
	}

	protected function createOrderShipment(int $deliveryId, array $data) : Sale\Shipment
	{
		/** @var Sale\Shipment $shipment */
		$shipment = $this->saleOrder->getShipmentCollection()->createItem();

		$deliveryRow = Sale\Delivery\Services\Manager::getById($deliveryId);

		if ($deliveryRow === null)
		{
			$this->mark(self::getLocale('DELIVERY_NOT_FOUND'), 'DELIVERY_NOT_FOUND');
		}
		else
		{
			$delivery = Sale\Delivery\Services\Manager::getPooledObject($deliveryRow);

			if ($delivery !== null)
			{
				$data['DELIVERY_NAME'] = isset($data['DELIVERY_NAME'])
					? sprintf('%s (%s)', $data['DELIVERY_NAME'], $delivery->getNameWithParent())
					: $delivery->getNameWithParent();
			}

			if ($deliveryRow['ACTIVE'] !== 'Y')
			{
				$this->mark(self::getLocale('DELIVERY_NOT_ACTIVE'), 'DELIVERY_NOT_ACTIVE');
			}
		}

        $settableFields = array_flip($shipment::getAvailableFields());

        if (isset($settableFields['CURRENCY']))
        {
            $shipment->setField('CURRENCY', $data['CURRENCY'] ?? $this->saleOrder->getCurrency());
        }

        if (isset($data['PRICE_DELIVERY']))
        {
			$shipment->setFields([
				'CUSTOM_PRICE_DELIVERY' => 'Y',
				'PRICE_DELIVERY' => $data['PRICE_DELIVERY'],
			]);
        }

		$settableData = array_intersect_key($data, $settableFields);

		$shipment->setField('DELIVERY_ID', $deliveryId);
		$shipment->setFields($settableData);

		return $shipment;
	}

	protected function fillShipmentBasket(Sale\Shipment $shipment) : void
	{
		$shipmentItemCollection = $shipment->getShipmentItemCollection();

		foreach ($this->saleOrder->getBasket() as $basketItem)
		{
			$shipmentItem = $shipmentItemCollection->createItem($basketItem);

			if ($shipmentItem)
			{
				$shipmentItem->setQuantity($basketItem->getQuantity());
			}
		}
	}

	public function fillPayment(int $paySystemId, float $sum = null) : void
	{
		$paySystem = Sale\PaySystem\Manager::getObjectById($paySystemId);

		if ($paySystem === null)
		{
			$this->mark(self::getLocale('PAY_SYSTEM_NOT_FOUND'), 'PAY_SYSTEM_NOT_FOUND');
		}
		else if ($paySystem->getField('ACTIVE') !== 'Y')
		{
			$this->mark(self::getLocale('PAY_SYSTEM_NOT_ACTIVE'), 'PAY_SYSTEM_NOT_ACTIVE');
		}

		$payment = $this->saleOrder->getPaymentCollection()->createItem($paySystem);

		if ($sum === null)
		{
			$sum = $this->saleOrder->getPrice() - $this->saleOrder->getPaymentCollection()->getSum();
		}

		$payment->setField('SUM', $sum);
		$payment->setField('CURRENCY', $this->saleOrder->getCurrency());
	}

	public function properties() : array
	{
		$result = [];

		/** @var Sale\PropertyValue $property */
		foreach ($this->saleOrder->getPropertyCollection() as $property)
		{
			$result[$property->getField('ORDER_PROPS_ID')] = $property->getValue();
		}

		return $result;
	}

	public function fillProperties(array $values, PropertyMapper $mapper = null) : Main\Result
	{
		$propertyCollection = $this->saleOrder->getPropertyCollection();
		$result = new Main\Result();
		$changed = false;

		foreach ($values as $id => $value)
		{
			if ($mapper !== null)
			{
				$id = $mapper->propertyId($id);

				if ($id === null) { continue; }
			}

			/** @var Sale\PropertyValue $property */
			$property = $propertyCollection->getItemByOrderPropertyId($id);

			if ($property !== null)
			{
				$value = $this->formatPropertyValue($property, $value);

				$property->setValue($value);

				if ($property->isChanged())
				{
					$changed = true;
				}
			}
			else
			{
				$this->relatedProperties[$id] = $value;
			}
		}

		$result->setData([ 'CHANGED' => $changed ]);

		return $result;
	}

	protected function formatPropertyValue(Sale\PropertyValue $property, $value)
	{
		$propertyRow = $property->getProperty();
		$isPropertyMultiple = (isset($propertyRow['MULTIPLE']) && $propertyRow['MULTIPLE'] === 'Y');

		if (is_array($value))
		{
			foreach ($value as &$oneValue)
			{
				$oneValue = $this->sanitizeValue($oneValue);
			}
			unset($oneValue);

			if (!$isPropertyMultiple)
			{
				$value = implode(', ', $value);
			}
		}
		else
		{
			$value = $this->sanitizeValue($value);

			if ($isPropertyMultiple)
			{
				$value = [ $value ];
			}
		}

		return $value;
	}

	protected function sanitizeValue($value)
	{
		$sanitizedValue = $value;

		if ($value instanceof Main\Type\DateTime)
		{
			$sanitizedValue = ConvertTimeStamp($value->getTimestamp(), 'FULL');
		}
		elseif ($value instanceof Main\Type\Date)
		{
			$sanitizedValue = ConvertTimeStamp($value->getTimestamp());
		}

		return $sanitizedValue;
	}

	public function fillRelatedProperties() : Main\Result
	{
		$values = $this->relatedProperties;
		$this->relatedProperties = [];

		return $this->fillProperties($values);
	}

	public function unmark(string $code) : Main\Result
	{
		$allMarkers = $this->allMarkers();
		$result = new Main\Result();
		$result->setData([ 'CHANGED' => false ]);

		if (!isset($allMarkers[$code])) { return $result; }

		Sale\EntityMarker::delete($allMarkers[$code]);

		if (count($allMarkers) === 1 && $this->saleOrder->isMarked())
		{
			$this->saleOrder->setFields([
				'MARKED' => 'N',
				'REASON_MARKED' => '',
			]);
		}

		$result->setData([ 'CHANGED' => true ]);

		return $result;
	}

	public function mark(string $reason, string $code) : Main\Result
	{
		$allMarkers = $this->allMarkers();
		$result = new Main\Result();
		$result->setData([ 'CHANGED' => false ]);

		if (array_key_exists($code, $allMarkers)) { return $result; }

		$markerResult = new Sale\Result();
		$markerResult->addWarning(new Main\Error($reason, $code));

		Sale\EntityMarker::addMarker($this->saleOrder, $this->saleOrder, $markerResult);

		if (!$this->saleOrder->isMarked())
		{
			$this->saleOrder->setFields([
				'MARKED' => 'Y',
				'REASON_MARKED' => $reason,
			]);
		}

		$result->setData([ 'CHANGED' => true ]);

		return $result;
	}

	protected function allMarkers() : array
	{
		return $this->preparedMarkers() + $this->storedMarkers();
	}

	protected function preparedMarkers() : array
	{
		$markers = Sale\EntityMarker::getMarker($this->saleOrder->getInternalId(), $this->saleOrder);

		if (empty($markers)) { return []; }

		$exists = array_column($markers, 'ID', 'CODE');
		$exists += array_fill_keys(array_column($markers, 'CODE'), null);

		return $exists;
	}

	protected function storedMarkers() : array
	{
		return $this->once('storedMarkers', function() {
			$orderId = $this->saleOrder->getId();

			if ($orderId === null) { return []; }

			$result = [];

			$query = Sale\EntityMarker::getList([
				'filter' => [ 'ORDER_ID' => $orderId ],
				'select' => [ 'ID', 'CODE' ]
			]);

			while ($row = $query->fetch())
			{
				$code = (string)$row['CODE'];

				if ($code === '') { continue; }

				$result[$code] = $row['ID'];
			}

			return $result;
		});
	}

	public function siteId() : ?string
	{
		return $this->saleOrder->getSiteId();
	}

	public function fillUserDescription(?string $value) : void
	{
		$this->saleOrder->setField('USER_DESCRIPTION', $value);
	}

	public function saleOrder() : Sale\OrderBase
	{
		return $this->saleOrder;
	}
}