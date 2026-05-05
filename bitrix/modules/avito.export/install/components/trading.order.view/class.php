<?php
/** @noinspection PhpUnused */
namespace Avito\Export\Components;

use Avito\Export\Assert;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Avito\Export\Admin;
use Avito\Export\Api;
use Avito\Export\Api\OrderManagement\Model;
use Avito\Export\Data;
use Avito\Export\Exchange;
use Avito\Export\Trading;
use Avito\Export\Trading\Entity as TradingEntity;
use Avito\Export\Trading\Service as TradingService;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) { die(); }

class TradingOrderView extends \CBitrixComponent
	implements Main\Engine\Contract\Controllerable
{
	/** @var Trading\Setup\Model */
	protected $trading;
	/** @var TradingEntity\Sale\Container */
	protected $environment;
	/** @var TradingService\Container */
	protected $service;
	/** @var Model\Order */
	protected $order;
	/** @var TradingEntity\Sale\Order */
	protected $tradingOrder;

	public function configureActions() : array
	{
		return [];
	}

	public function reloadAction() : array
	{
		$this->setTemplateName('bitrix24');
		$this->executeComponent('reload');

		return (array)$this->arResult['RETURN'];
	}

	public function executeComponent(string $mode = '') : void
	{
		try
		{
			$this->loadModules();
			$this->loadStuff();
			$this->loadOrder();

			$this->checkAccess();

			$this->collectCommon();
			$this->collectProperties();
			$this->collectBasketRows();
			$this->collectBasketTotal();

            $this->collectActivities();
            $this->collectAttention();

			$this->includeComponentTemplate($mode);
		}
		catch (Main\SystemException $exception)
		{
			$this->arResult['ERROR'] = $exception->getMessage();

			$this->includeComponentTemplate('exception');
		}
	}

	public function onPrepareComponentParams($arParams) : array
	{
		$arParams['SETUP_ID'] = (int)$arParams['SETUP_ID'];
		$arParams['EXTERNAL_ID'] = (int)$arParams['EXTERNAL_ID'];

		return $arParams;
	}

	protected function loadModules() : void
	{
		$requiredModules = $this->getRequiredModules();

		foreach ($requiredModules as $requiredModule)
		{
			if (!Main\Loader::includeModule($requiredModule))
			{
				throw new Main\SystemException(Loc::getMessage('AVITO_EXPORT_MODULE_REQUIRED', [
					'#NAME#' => $requiredModule,
				]));
			}
		}
	}

	protected function getRequiredModules() : array
	{
		return [
			'avito.export',
		];
	}

	protected function requireParameter(string $name)
	{
		if (!isset($this->arParams[$name]) || $this->arParams[$name] === '')
		{
			throw new Main\ArgumentException(Loc::getMessage('AVITO_EXPORT_REQUIRE_PARAMETER', [
				'#NAME#' => $name,
			]));
		}

		return $this->arParams[$name];
	}

	protected function loadStuff() : void
	{
		$setup = Exchange\Setup\Model::getById((int)$this->requireParameter('SETUP_ID'));
		$trading = $setup->getTrading();

		Assert::notNull($trading, 'trading');

		$this->trading = $trading;
		$this->environment = $trading->getEnvironment();
		$this->service = $trading->getService();
	}

	protected function loadOrder() : void
	{
		$externalId = (int)$this->requireParameter('EXTERNAL_ID');
		$tradingOrderId = $this->environment->orderRegistry()->search($externalId);

		$this->order = Api\OrderManagement\V1\Orders\Facade::getById($this->trading, $externalId);
		$this->tradingOrder = $tradingOrderId !== null
			? $this->environment->orderRegistry()->load($tradingOrderId)
			: null;
	}

	protected function checkAccess() : void
	{
		if ($this->tradingOrder !== null)
		{
			$this->checkOrderAccess();
		}
		else
		{
			$this->checkModuleAccess();
		}
	}

	protected function checkOrderAccess() : void
	{
		global $USER;

		if (!$this->tradingOrder->hasViewAccess((int)$USER->GetID()))
		{
			throw new Main\AccessDeniedException(Loc::getMessage('AVITO_EXPORT_ORDER_ACCESS_DENIED'));
		}
	}

	protected function checkModuleAccess() : void
	{
		if (!Admin\Access::hasRights(Admin\Access::RIGHTS_READ))
		{
			throw new Main\AccessDeniedException(Loc::getMessage('AVITO_EXPORT_MODULE_ACCESS_DENIED'));
		}
	}

	protected function collectCommon() : void
	{
		$externalId = (int)$this->requireParameter('EXTERNAL_ID');

		$this->arResult['SETUP_ID'] = (int)$this->requireParameter('SETUP_ID');
		$this->arResult['EXTERNAL_ID'] = $externalId;
		$this->arResult['EXTERNAL_NUMBER'] = $this->arParams['EXTERNAL_NUMBER'] ?: $externalId;
		$this->arResult['ORDER_ID'] = $this->tradingOrder !== null ? $this->tradingOrder->id() : null;
	}

	protected function collectAttention() : void
 	{
		$note = null;

		if (!empty($this->arResult['ACTIVITIES']))
		{
			$button = reset($this->arResult['ACTIVITIES']);
			$note = $button['NOTE'] ?? null;
		}

		if (empty($note))
		{
			$note = $this->service->status()->statusAttention(
				$this->order->status(),
				$this->attentionVariables(),
				$this->order->delivery()->serviceType()
			);
		}

	    $this->arResult['ATTENTION'] = $note;
	}

	protected function attentionVariables() : array
	{
		$result = [
			'#SERVICE_NAME#' => $this->order->delivery()->serviceName(),
			'#DISPATCH_NUMBER#' => $this->order->delivery()->dispatchNumber(),
		];

		foreach ($this->order->schedules()->meaningfulValues() as $name => $date)
		{
			$result['#' . $name . '#'] = $date !== null ? Data\DateTime::format($date) : '';
		}

		return $result;
	}

	/** @noinspection HtmlUnknownTarget */
	protected function collectProperties() : void
	{
        $deliveryFields = $this->makeDeliveryFields();
		$deliveryFields += $this->makeAddressFields();

		$this->arResult['PROPERTIES'] = [
			'INFO' => [
				'NAME' => Loc::getMessage('AVITO_EXPORT_BLOCK_INFO'),
				'FIELDS' => $this->makeProperties([
					'ID' => sprintf(
						'<a href="%s" target="_blank">%s</a>',
						$this->service->urlManager()->orderView($this->order->id()),
						$this->order->number()
					),
					'CREATED_AT' => $this->order->createdAt(),
					'UPDATED_AT' => $this->order->updatedAt(),
					'STATUS' => $this->service->status()->statusTitle($this->order->status()),
					'RETURN_STATUS' => $this->order->returnPolicy() !== null
						? $this->service->status()->returnStatusTitle($this->order->returnPolicy()->returnStatus())
						: null,
				]),
			],
			'DELIVERY' => [
				'NAME' => Loc::getMessage('AVITO_EXPORT_BLOCK_DELIVERY'),
				'FIELDS' => $this->makeProperties($deliveryFields),
			],
			'BUYER' => [
				'NAME' => Loc::getMessage('AVITO_EXPORT_BLOCK_BUYER'),
				'FIELDS' => $this->makeProperties($this->makeBuyerFields()),
			],
		];
	}

    protected function makeDeliveryFields() : array
    {
		$scheduleValues = $this->order->schedules()->meaningfulValues();

        $result = [
            'DELIVERY_SERVICE_NAME' => $this->order->delivery()->serviceName(),
            'DELIVERY_SERVICE_TYPE' => $this->order->delivery()->serviceType(),
            'TRACK_NUMBER' => $this->order->delivery()->trackingNumber(),
        ];

		$dispatchNumber = $this->order->delivery()->dispatchNumber();
		if ($dispatchNumber !== null && $result['TRACK_NUMBER'] !== $dispatchNumber)
		{
			$result['DISPATCH_NUMBER'] = $dispatchNumber;
		}

		$result += array_diff_key($scheduleValues, [
			'DELIVERY_DATE_MIN' => true,
			'DELIVERY_DATE_MAX' => true,
		]);
		$result['DELIVERY_DATE'] = Data\DateTimePeriod::format(
			$scheduleValues['DELIVERY_DATE_MIN'],
			$scheduleValues['DELIVERY_DATE_MAX']
		);

        return $result;
    }

	protected function makeAddressFields() : array
	{
		$terminal = $this->order->delivery()->terminalInfo();
		$courier = $this->order->delivery()->courierInfo();
		$result = [];

		if ($terminal !== null)
		{
			$result += [
				'ADDRESS' => $terminal->address() . ' #' . $terminal->code(),
			];
		}

		if ($courier !== null)
		{
			$result += [
				'ADDRESS' => $courier->address(),
				'COMMENT' => $courier->comment(),
			];
		}

		return $result;
	}

	protected function makeBuyerFields() : array
	{
		$buyer = $this->order->delivery()->buyerInfo();

		if ($buyer === null) { return []; }

		return [
			'FULL_NAME' => $buyer->fullName(),
			'PHONE_NUMBER' => $buyer->phoneNumber(),
		];
	}

	protected function makeProperties(array $values) : array
	{
		$result = [];

		foreach ($values as $name => $value)
		{
			if ($value === null || $value === '') { continue; }

			if ($value instanceof Main\Type\DateTime)
			{
				$value = Data\DateTime::format($value);
			}
			else if ($value instanceof Main\Type\Date)
			{
				$value = Data\Date::format($value);
			}

			$result[$name] = [
				'NAME' => Loc::getMessage('AVITO_EXPORT_PROPERTY_' . $name),
				'VALUE' => $value,
			];
		}

		return $result;
	}

	protected function collectBasketRows() : void
	{
		$this->arResult['BASKET_ROWS'] = [];
		$number = 1;

		/** @var Model\Order\Item $item */
		foreach ($this->order->items() as $key => $item)
		{
			$this->arResult['BASKET_ROWS'][$key] = [
				'NUMBER' => $number,
				'NAME' => $item->title(),
				'SERVICE_URL' => $this->service->urlManager()->offerPage($item->avitoId()),
				'CHAT_ID' => $item->chatId(),
				'CHAT_URL' => $item->chatId() !== null
					? $this->service->urlManager()->chatUrl($item->chatId())
					: null,
				'CHAT_ENABLE' => $this->trading->getExchange()->getUseChat(),
				'QUANTITY' => $item->count(),
				'PRICE' => $item->prices()->price(),
				'PRICE_FORMATTED' => $this->formatCurrency($item->prices()->price()),
				'COMMISSION' => $item->prices()->commission(),
				'COMMISSION_FORMATTED' => $this->formatCurrency($item->prices()->commission()),
				'DISCOUNT' => $item->prices()->discountSum(),
				'DISCOUNT_FORMATTED' => $this->formatCurrency($item->prices()->discountSum()),
				'DISCOUNTS' => [],
			];

			$discounts = $item->discounts();

			if ($discounts !== null)
			{
				/** @var Model\Order\Item\Discount $discount */
				foreach ($discounts as $discount)
				{
					$this->arResult['BASKET_ROWS'][$key]['DISCOUNTS'][] = [
						'TYPE' => $this->service->discount()->typeTitle($discount->type()),
						'VALUE' => $discount->value(),
						'VALUE_FORMATTED' => $this->formatCurrency($discount->value()),
					];
				}
			}

			++$number;
		}
	}

	protected function collectBasketTotal() : void
	{
		$this->arResult['BASKET_TOTAL'] = [];
		$values = [
			'PRICE' => $this->order->prices()->price(),
			'COMMISSION' => $this->order->prices()->commission(),
			'DISCOUNT' => $this->order->prices()->discount(),
			'TOTAL' => $this->order->prices()->total(),
		];

		foreach ($values as $name => $value)
		{
			if ($value <= 0.0) { continue; }

			$this->arResult['BASKET_TOTAL'][] = [
				'NAME' => Loc::getMessage('AVITO_EXPORT_SUMMARY_' . $name),
				'VALUE' => $value,
				'VALUE_FORMATTED' => $this->formatCurrency($value),
			];
		}
	}

	protected function formatCurrency(?float $value) : ?string
	{
		if ($value === null) { return null; }

		return $this->environment->currency()->format($value);
	}

	protected function listKeysSignedParameters() : array
	{
		return [
			'EXTERNAL_ID',
			'SETUP_ID',
		];
	}

	/** @noinspection DuplicatedCode */
    protected function collectActivities() : void
    {
	    $this->arResult['ACTIVITIES'] = [];
		$availableActions = $this->order->availableActions();

		if ($availableActions === null) { return; }

	    $buttons = [];

		/** @var Model\Order\AvailableAction $action */
	    foreach ($availableActions as $action)
		{
			try
			{
				$name = $action->name();
				$activity = Trading\Activity\Registry::make($name, $this->service, $this->environment, $this->trading->getId());

				if ($activity instanceof Trading\Activity\Reference\HiddenActivity) { continue; }

				$buttons[$name] = [
					'NAME' => $name,
					'TITLE' => htmlspecialcharsbx($activity->title($this->order)),
					'ORDER' => $activity->order(),
					'BEHAVIOR' => Trading\Activity\Registry::activityType($activity),
					'REQUIRED' => $action->required(),
					'CONFIRM' => $activity instanceof Trading\Activity\Reference\CommandActivity ? $activity->confirm() : null,
					'NOTE' => $activity->note($this->order),
					'URL'=> Admin\Path::moduleUrl('trading_activity', [
						'lang' => LANGUAGE_ID,
						'name' => $name,
						'externalId' => $this->arResult['EXTERNAL_ID'],
						'externalNumber' => $this->arResult['EXTERNAL_NUMBER'],
						'orderId' => $this->arResult['ORDER_ID'],
						'setupId' => $this->arResult['SETUP_ID'],
					]),
					'UI_OPTIONS' => $activity->uiOptions(),
				];
			}
			catch (Main\SystemException $exception)
			{
				trigger_error($exception->getMessage(), E_USER_WARNING);
				$this->addWarning($exception->getMessage());
			}
		}

		uasort($buttons, static function($aButton, $bButton) {
			$aOrder = $aButton['ORDER'] - ($aButton['REQUIRED'] ? 1000 : 0);
			$bOrder = $bButton['ORDER'] - ($bButton['REQUIRED'] ? 1000 : 0);

			if ($aOrder === $bOrder) { return 0; }

			return ($aOrder < $bOrder ? -1 : 1);
		});

		$this->arResult['ACTIVITIES'] = $buttons;
    }

	protected function addWarning(string $message) : void
	{
		if (!isset($this->arResult['WARNINGS'])) { $this->arResult['WARNINGS'] = []; }

		$this->arResult['WARNINGS'][] = $message;
	}
}