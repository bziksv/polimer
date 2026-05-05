<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\GenerateDigital;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;
use Yandex\Market\Logger\Trading\Logger as TradingLogger;
use Yandex\Market\Glossary;
use Yandex\Market\Logger\Level;
use Yandex\Market\Logger\Trading\Audit;
use Yandex\Market\Catalog;

class Action extends TradingService\MarketplaceDbs\Action\SendDigital\Action
{
	use Market\Reference\Concerns\HasOnce;
	use Market\Reference\Concerns\HasMessage;
	use TradingService\Common\Concerns\Action\HasItemIdMatch;

	/** @var Request */
	protected $request;
	/** @var TradingService\MarketplaceDbs\Provider */
	protected $provider;

    /**
     * @var TradingLogger
     */
    private TradingLogger $tradingLogger;

	protected function createRequest(array $data)
	{
		return new Request($data);
	}

	public function process()
	{
        try {
            if ($this->isOrderOutOfProcessing()) {
                return;
            }

            $orderId = $this->request->getOrderId();
            $codes = $this->reserveCodes();
            $items = $this->makeItems($codes);

            $this->logSentKeys($items);

            $this->sendDigitalGoods($orderId, $items);
            $this->shipCodes($codes);

            $this->resolveOrderMarker(true);
        }
		catch (Main\SystemException $exception)
		{
			if (isset($codes)) { $this->failCodes($codes); }

			$sendResult = new Main\Result();
			$sendResult->addError(new Main\Error(
				$exception->getMessage(),
				$exception->getCode()
			));

			$this->resolveOrderMarker(false, $sendResult);

			throw $exception;
		}
	}

    /**
     * Логирует отправку ключей по каждому товару из $items.
     * $items: массив с полями id_element, id_element_code.
     */
    protected function logSentKeys(array $items): void
    {
        $logger = $this->logger();
        $message = self::getMessage('DG_SEND', ['#ORDER_ID#' => (string)$this->request->getOrderId()]);

        foreach ($items as $item) {
            if (!isset($item['id_element'])) {
                continue;
            }

            $logger->log(
                Level::INFO,
                $message,
                [
                    'ENTITY_TYPE' => Catalog\Glossary::ENTITY_SKU,
                    'ENTITY_ID' => (string)$item['id_element'],
                    'AUDIT' => Audit::DG_SEND,
                    'CODE' => isset($item['id_element_code']) ? (string)$item['id_element_code'] : '',
                ]
            );
        }
    }

    protected function logger(): TradingLogger
    {
        $opts       = $this->provider->getOptions();
        $setupId    = (int)$opts->getSetupId();
        $businessId = (int)$opts->getBusinessId();
        $campaignId = (int)$opts->getCampaignId();

        $logger = new TradingLogger(Glossary::SERVICE_TRADING, $setupId);

        if (method_exists($opts, 'getLogLevel')) {
            $logger->setLevel($opts->getLogLevel());
        }

        if ($businessId > 0) { $logger->setContext('BUSINESS_ID', $businessId); }
        if ($campaignId > 0) { $logger->setContext('CAMPAIGN_ID', $campaignId); }

        $logger->allowBatch();
        return $this->tradingLogger = $logger;
    }

	protected function isOrderOutOfProcessing()
	{
		$statusService = $this->provider->getStatus();
		$stored = explode(':', (string)$statusService->getStored($this->request->getOrderId()));

		return $stored[0] !== '' && $stored[0] !== $statusService::STATUS_PROCESSING;
	}

	protected function reserveCodes()
	{
		return $this->getDigital()->reserve($this->getOrder(), $this->makeBasketQuantities());
	}

	protected function makeItems(array $codes)
	{
		$basketMap = $this->mapBasket();
		$basketMap = array_flip($basketMap);
		$result = [];

		foreach ($codes as $code)
		{
			if (!isset($basketMap[$code['BASKET_CODE']])) { continue; }

			$result[] = [
				'id' => $basketMap[$code['BASKET_CODE']],
                'id_element' => $code['IBLOCK_ELEMENT_ID'],
                'id_element_code' => $code['ID'],
				'code' => $code['CODE'],
				'slip' => $code['SLIP'],
				'activate_till' => $code['ACTIVATE_TILL'],
			];
		}

		return $result;
	}

	protected function shipCodes(array $codes)
	{
		$this->getDigital()->ship($this->getOrder(), $codes);
	}

	protected function failCodes(array $codes)
	{
		$this->getDigital()->fail($this->getOrder(), $codes);
	}

	protected function makeBasketQuantities()
	{
		$basketMap = $this->mapBasket();
		$result = [];

		/** @var TradingService\Marketplace\Model\Order\Item $externalItem */
		foreach ($basketMap as $basketCode)
		{
			$basketData = $this->getOrder()->getBasketItemData($basketCode)->getData();

			$result[$basketCode] = $basketData['QUANTITY'];
		}

		return $result;
	}

	protected function mapBasket()
	{
		return $this->once('mapBasket', null, function() {
			$externalOrder = $this->getExternalOrder();
			$items = $externalOrder->getItems();
			$basketMap = $this->getExternalItemsBasketMap($items, $externalOrder);

			/** @var TradingService\Marketplace\Model\Order\Item $item */
			foreach ($items as $item)
			{
				$id = $item->getId();

				if (!isset($basketMap[$id]))
				{
					throw new Main\SystemException(self::getMessage('BASKET_MISSING', [
						'#SKU#' => $item->getOfferId(),
						'#NAME#' => $item->getOfferName(),
					]));
				}

				$basketCode = $basketMap[$id];
				$basketData = $this->getOrder()->getBasketItemData($basketCode)->getData();

				if (!Market\Data\Quantity::equal($item->getCount(), $basketData['QUANTITY']))
				{
					throw new Main\SystemException(self::getMessage('QUANTITY_MISMATCH', [
						'#SKU#' => $item->getOfferId(),
						'#NAME#' => $item->getOfferName(),
						'#REQUIRED_QUANTITY#' => $item->getCount(),
						'#BASKET_QUANTITY#' => $basketData['QUANTITY'],
					]));
				}

				$basketMap[$item->getId()] = $basketCode;
			}

			return $basketMap;
		});
	}

	/** @return TradingEntity\Reference\Digital */
	protected function getDigital()
	{
		return $this->once('getDigital', null, function() {
			$deliveryId = $this->request->getShopDeliveryId();
			$deliveryOption = $this->provider->getOptions()->getDeliveryOptions()->getItemByServiceId($deliveryId);

			Market\Reference\Assert::notNull($deliveryOption, 'deliveryOption');
			Market\Reference\Assert::notNull($deliveryOption->getDigitalAdapter(), 'deliveryOption->getDigitalAdapter()');

			$digital = $this->environment->getDigitalRegistry()->makeDigital(
				$deliveryOption->getDigitalAdapter(),
				$deliveryOption->getDigitalSettings()
			);
			$digital->load();

			return $digital;
		});
	}
}