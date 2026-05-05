<?php
namespace Avito\Export\Admin\Page;

use Bitrix\Main;
use Avito\Export\Api;
use Avito\Export\Admin;
use Avito\Export\Concerns;
use Avito\Export\Config;
use Avito\Export\Data;
use Avito\Export\Exchange;
use Avito\Export\Trading;

class DocumentsGrid extends Grid
{
	use Concerns\HasLocale;

	protected const MAX_NAV_SIZE = 20;

	/** @var array<int, int> */
	protected $ordersMap;
	/** @var array<int, \Bitrix\Main\Type\DateTime>*/
	protected $ordersUpdated;
	/** @var int */
	protected $totalCount;
	/** @var Exchange\Setup\Model[] */
	protected $setupCollection = [];
	/** @var Exchange\Setup\Model */
	protected $setup;
	/** @var Trading\Setup\Model */
	protected $trading;
	/** @var Trading\Entity\Sale\Container */
	protected $environment;
	/** @var Trading\Service\Container */
	protected $service;

	public function getGridId() : string
	{
		return Config::LANG_PREFIX . 'DOCUMENTS';
	}

	protected function loadFields() : array
	{
        return $this->extendFields([
            'NUMBER' => [
                'TYPE' => 'primary',
                'NAME' => self::getLocale('FIELD_ID'),
                'FILTERABLE' => true,
                'DEFAULT' => true,
	            'SETTINGS' => [
					'URL_FIELD' => 'SERVICE_URL',
	            ],
            ],
	        'ORDER_ID' => [
		        'TYPE' => 'primary',
		        'NAME' => self::getLocale('FIELD_ORDER_ID'),
		        'DEFAULT' => true,
		        'SETTINGS' => [
			        'URL_FIELD' => 'ADMIN_URL',
		        ],
	        ],
	        'BASKET' => [
		        'TYPE' => 'orderItem',
		        'NAME' => self::getLocale('FIELD_BASKET'),
		        'DEFAULT' => true,
		        'MULTIPLE' => 'Y',
		        'SETTINGS' => [
					'SETUP_ID' => $this->setup->getId(),
		        ],
	        ],
	        'PRICES' => [
		        'TYPE' => 'orderPrices',
		        'NAME' => self::getLocale('FIELD_PRICES'),
		        'DEFAULT' => true,
	        ],
	        'DELIVERY' => [
		        'TYPE' => 'orderDelivery',
		        'NAME' => self::getLocale('FIELD_DELIVERY'),
		        'DEFAULT' => true,
	        ],
	        'SCHEDULE' => [
				'TYPE' => 'orderSchedule',
				'NAME' => self::getLocale('FIELD_SCHEDULE'),
				'DEFAULT' => true,
		    ],
            'STATUS' => [
	            'TYPE' => 'orderStatus',
                'NAME' => self::getLocale('FIELD_STATUS'),
                'FILTERABLE' => true,
                'DEFAULT' => true,
	            'VALUES' => array_map(function(string $status) {
					return [
						'ID' => $status,
						'VALUE' => $this->service->status()->statusTitle($status),
					];
	            }, $this->service->status()->statuses()),
            ],
            'CREATED_AT' => [
                'TYPE' => 'dateTime',
                'NAME' => self::getLocale('FIELD_CREATED_AT'),
            ],
            'UPDATED_AT' => [
                'TYPE' => 'dateTime',
                'NAME' => self::getLocale('FIELD_UPDATED_AT'),
            ],
	        'SYNCED' => [
		        'TYPE' => 'boolean',
		        'NAME' => self::getLocale('FIELD_SYNCED'),
	        ],
        ], [], [
			'FILTERABLE' => false,
			'SORTABLE' => false,
	        'SELECTABLE' => true,
	        'DEFAULT' => false,
        ]);
	}

	protected function loadAssets() : void
	{
		Main\UI\Extension::load('avitoexport.trading.activity');
	}

	protected function request(array $queryParameters = []) : Api\OrderManagement\V1\Orders\Response
	{
		$client = new Api\OrderManagement\V1\Orders\Request();
		$client->token($this->trading->getSettings()->commonSettings()->token());

		$this->applyRequestPage($client, $queryParameters);
		$this->applyRequestFilter($client, $queryParameters);

		return $client->execute();
	}

	protected function requestOrders(array $orderIds) : Api\OrderManagement\Model\Orders
	{
		if (empty($orderIds)) { return new Api\OrderManagement\Model\Orders([]); }

		$response = $this->request([
			'filter' => [ 'NUMBER' => $orderIds, ],
			'offset' => 0,
			'limit' => 1,
		]);

		return $response->orders();
	}

	protected function applyRequestFilter(Api\OrderManagement\V1\Orders\Request $client, array $queryParameters) : void
	{
		$filter = $queryParameters['filter'] ?? [];

		if (isset($filter['STATUS']))
		{
			$client->statuses((array)$filter['STATUS']);
		}

		if (isset($filter['NUMBER']))
		{
			$client->ids((array)$filter['NUMBER']);
		}
	}

	protected function applyRequestPage(Api\OrderManagement\V1\Orders\Request $client, array $queryParameters) : void
	{
		$page = $this->pageIndex($queryParameters);

		$client->limit((int)($queryParameters['limit'] ?? 1));
		$client->page($page + 1);
	}

	protected function loadItems(array $queryParameters = []) : array
	{
		$response = $this->request($queryParameters);
		$orders = $response->orders();

		$this->loadOrdersMap($orders);
		$this->loadOrdersUpdated($orders);

		$this->totalCount =
			$this->pageIndex($queryParameters) * $queryParameters['limit']
			+ $orders->count()
			+ ($response->hasMore() ? 1 : 0);

		return $this->makeItems($orders);
	}

	protected function loadTotalCount(array $queryParameters = []) : int
	{
		return $this->totalCount;
	}

	protected function pageIndex(array $queryParameters) : int
	{
		$offset = ($queryParameters['offset'] ?? 0);
		$limit = max(1, ($queryParameters['limit'] ?? 1));

		return (int)($offset / $limit);
	}

	protected function initPager() : array
	{
		$result = parent::initPager();

		if (isset($result['limit']) && ($result['limit'] > static::MAX_NAV_SIZE))
		{
			$page = (int)($result['offset'] / $result['limit']) + 1;

			$result['limit'] = static::MAX_NAV_SIZE;
			$result['offset'] = static::MAX_NAV_SIZE * ($page - 1);
		}

		return $result;
	}

	protected function makeItems(Api\OrderManagement\Model\Orders $orders) : array
	{
		$result = [];

		/** @var Api\OrderManagement\Model\Order $order */
		foreach ($orders as $order)
		{
			$internalId = $this->ordersMap[$order->id()] ?? null;

			$result[] = [
				'ID' => $order->id(),
				'NUMBER' => $order->number(),
				'SERVICE_URL' => $this->service->urlManager()->orderView($order->id()),
				'ORDER_ID' => $internalId,
				'ADMIN_URL' => $internalId !== null ? $this->environment->adminExtension()->orderUrl($internalId) : null,
				'STATUS' => $order->status(),
				'RETURN_STATUS' => $order->returnPolicy() !== null
					? $this->service->status()->returnStatusTitle($order->returnPolicy()->returnStatus())
					: null,
				'CREATED_AT' => $order->createdAt(),
				'UPDATED_AT' => $order->updatedAt(),
				'SCHEDULE' => [
					'CONFIRM_TILL' => $order->schedules()->confirmTill(),
					'SHIP_TILL' => $order->schedules()->shipTill(),
					'DELIVERY_FROM' => $order->schedules()->deliveryDateMin(),
					'DELIVERY_TO' => $order->schedules()->deliveryDateMax(),
					'DELIVERY_DATE' => $order->schedules()->deliveryDate(),
					'SET_TRACKING_NUMBER_TILL' => $order->schedules()->setTrackingNumberTill(),
					'SET_TERMS_TILL' => $order->schedules()->setTermsTill(),
				],
				'DELIVERY' => [
					'NAME' => $order->delivery()->serviceName(),
					'TYPE' => $order->delivery()->serviceType(),
					'TRACK' => $order->delivery()->trackingNumber(),
					'DISPATCH' => $order->delivery()->dispatchNumber(),
				],
				'PRICES' => $this->makeOrderPrices($order),
				'BASKET' => $this->makeOrderBasket($order),
				'SYNCED' => (
					isset($this->ordersUpdated[$order->id()])
					&& Data\DateTime::compare($this->ordersUpdated[$order->id()], $order->updatedAt()) !== -1
				),
				'AVAILABLE_ACTIONS' => $this->availableActions($order),
			];
		}

		return $result;
	}

	protected function makeOrderBasket(Api\OrderManagement\Model\Order $order) : array
	{
		$result = [];

		/** @var Api\OrderManagement\Model\Order\Item $itemProduct */
		foreach ($order->items() as $itemProduct)
		{
			$result[] = [
				'ID' => $itemProduct->id(),
				'COUNT' => $itemProduct->count(),
				'TITLE' => $itemProduct->title(),
				'CHAT_ID' => $itemProduct->chatId(),
				'CHAT_URL' => $itemProduct->chatId() !== null
					? $this->service->urlManager()->chatUrl($itemProduct->chatId())
					: null,
				'CHAT_ENABLE' => $this->trading->getExchange()->getUseChat(),
				'SERVICE_URL' => $this->service->urlManager()->offerPage($itemProduct->avitoId()),
			];
		}

		return $result;
	}

	protected function makeOrderPrices(Api\OrderManagement\Model\Order $order) : array
	{
		$prices = [
			'PRICE' => $order->prices()->price(),
			'COMMISSION' => $order->prices()->commission(),
			'DISCOUNT' => $order->prices()->discount(),
		];

		foreach ($prices as $type => $value)
		{
			if ($value === null) { continue; }

			$prices[$type . '_FORMATTED'] = $this->environment->currency()->format($value);
		}

		return $prices;
	}

	protected function loadOrdersMap(Api\OrderManagement\Model\Orders $items) : void
	{
		$this->ordersMap = $this->environment->orderRegistry()->searchFew($items->ids());
	}

	protected function loadOrdersUpdated(Api\OrderManagement\Model\Orders $items) : void
	{
		$this->ordersUpdated = array_fill_keys($items->ids(), null);

		if (empty($this->ordersUpdated)) { return; }

		$iterator = Trading\State\RepositoryTable::getList([
			'select' => [ 'ORDER_ID', 'VALUE' ],
			'filter' => [
				'=ORDER_ID' => $items->ids(),
				'=NAME' => 'UPDATED_AT',
			],
		]);
		while ($record = $iterator->fetch())
		{
			$this->ordersUpdated[$record['ORDER_ID']] = Data\DateTime::cast($record['VALUE']);
		}
	}

	protected function getActionsBuild($item): array
	{
		$result = [];
		$loaded = isset($this->ordersMap[$item['ID']]);

		if (!$loaded)
		{
			$result[] = [
				'ACTION' => 'orderAccept',
				'TEXT' => self::getLocale('ACTION_ORDER_ACCEPT'),
			];
		}
		else if (!$item['SYNCED'])
		{
			$result[] = [
				'ACTION' => 'orderStatus',
				'TEXT' => self::getLocale('ACTION_ORDER_STATUS'),
			];
		}

		if ($loaded && !empty($item['AVAILABLE_ACTIONS']))
		{
			foreach ($item['AVAILABLE_ACTIONS'] as $action)
			{
				$result[] = [
					'ONCLICK' => sprintf(
						'BX.AvitoExport.Trading.Activity.Factory.make("%s", %s, %s).activate();',
						$action['BEHAVIOR'],
						sprintf(
							'new BX.AvitoExport.Trading.Activity.View.Grid(%s)',
							Main\Web\Json::encode([
								'gridId' => $this->getGridId(),
							])
						),
						Main\Web\Json::encode([
							'title' => $action['TITLE'],
							'confirm' => $action['CONFIRM'],
							'url' => Admin\Path::moduleUrl('trading_activity', [
								'lang' => LANGUAGE_ID,
								'name' => $action['NAME'],
								'externalId' => $item['ID'],
								'externalNumber' => $item['NUMBER'],
								'orderId' => $item['ORDER_ID'],
								'setupId' => $this->setup->getId(),
							]),
						])
					),
					'TEXT' => $action['TITLE'],
				];
			}
		}

        return $result;
	}

	protected function handleAction($action, $data) : void
	{
		if ($action === 'orderAccept')
		{
			$this->processOrderProcedure($data, [
				'order/accept',
				'order/status',
			]);
		}
		elseif ($action === 'orderStatus')
		{
			$this->processOrderProcedure($data, [
				'order/status',
			]);
		}
		else
		{
			parent::handleAction($action, $data);
		}
	}

	protected function processOrderProcedure($data, array $actions) : void
	{
		if (empty($data['ID'])) { return; }

		$orders = $this->requestOrders((array)$data['ID']);

		foreach ($orders as $order)
		{
			foreach ($actions as $path)
			{
				$procedure = new Trading\Action\Procedure($this->trading, $path, [ 'order' => $order ]);
				$procedure->run();
			}
		}
	}

	public function renderPage() : void
	{
		global $APPLICATION;

		$this->loadSetupCollection();
		$this->resolveSetup();
		$this->prepareStuff();

		if ($this->hasAjaxRequest()) { $APPLICATION->RestartBuffer(); }

		if ($this->hasRequestAction())
		{
			$this->processAction();
		}

		$this->checkReadAccess();
		$this->loadModules();

		$this->showSetupSelector();
		$this->show();

		if ($this->hasAjaxRequest()) { die(); }
	}

	protected function prepareStuff() : void
	{
		$this->trading = $this->setup->getTrading();
		$this->environment = $this->trading->getEnvironment();
		$this->service = $this->trading->getService();
	}

	protected function loadSetupCollection() : void
	{
		$query = Exchange\Setup\RepositoryTable::getList([
			'filter' => [ '=USE_TRADING' => true ],
		]);

		while ($exchange = $query->fetchObject())
		{
			$this->setupCollection[$exchange->getId()] = $exchange;
		}
	}

	protected function resolveSetup() : void
	{
		$requested = $this->request->get('setup');
		$stored = \CUserOptions::GetOption($this->getGridId(), 'SETUP');

		if ($requested !== null)
		{
			$requested = (int)$requested;

			if (!isset($this->setupCollection[$requested]))
			{
				throw new Admin\Exception\UserException(self::getLocale('REQUESTED_SETUP_NOT_FOUND', [
					'#ID#' => $requested,
				]));
			}

			if ($requested !== (int)$stored)
			{
				\CUserOptions::SetOption($this->getGridId(), 'SETUP', $requested);
			}

			$this->setup = $this->setupCollection[$requested];
		}
		else if (isset($stored, $this->setupCollection[$stored]))
		{
			$this->setup = $this->setupCollection[$stored];
		}
		else
		{
			if (empty($this->setupCollection))
			{
				throw new Admin\Exception\UserException(
					self::getLocale('SETUP_MISSING'),
					self::getLocale('SETUP_MISSING_DETAILS', [
						'#URL#' => Admin\Path::moduleUrl('exchange', [ 'lang' => LANGUAGE_ID ]),
					])
				);
			}

			$this->setup = reset($this->setupCollection);
		}
	}

	/**
	 * @noinspection HtmlUnknownTarget
	 * @noinspection JSUnresolvedReference
	 */
	protected function showSetupSelector() : void
	{
		if (count($this->setupCollection) <= 1) { return; }

		global $APPLICATION;

		$dropdownItems = array_map(function(Exchange\Setup\Model $setup) {
			global $APPLICATION;

			return [
				'text' => sprintf('[%s] %s', $setup->getId(), $setup->getName()),
				'link' => $APPLICATION->GetCurPageParam(http_build_query([ 'setup' => $setup->getId() ]), [ 'setup' ]),
				'selected' => $setup === $this->setup,
			];
		}, $this->setupCollection);
		$dropdownItems = array_filter($dropdownItems, static function(array $item) { return !$item['selected']; });
		$dropdownItems = array_values($dropdownItems);

		$html = sprintf(
			'<div class="crm-interface-toolbar-button-container">
				<button class="ui-btn ui-btn-dropdown ui-btn-light-border" type="button" id="avito-setup-selector">
					%s
				</button>
			</div>',
			sprintf('[%s] %s', $this->setup->getId(), $this->setup->getName())
		);
		$html .= sprintf(
			'<script>
				BX.ready(function() {
					const button = BX("avito-setup-selector");
					const items = JSON.parse(\'%s\');
					
					if (!button || !items) { return; }
					
					items.forEach(function(item) {
						item.onclick = function() { window.location.href = item.link; };
					});
					
					const menu = new BX.PopupMenuWindow({
						bindElement: button,
						items: items,
					});
			
					button.addEventListener("click", function() { menu.show(); });
				});
			</script>',
			Main\Web\Json::encode($dropdownItems)
		);

		if (defined('SITE_TEMPLATE_ID') && SITE_TEMPLATE_ID === 'bitrix24')
		{
			/** @noinspection SpellCheckingInspection */
			$APPLICATION->AddViewContent('inside_pagetitle', $html);
		}
		else
		{
			echo '<div style="float: right; padding-top: 3px">';
			echo $html;
			echo '</div>';
		}
	}

	/** @noinspection DuplicatedCode */
	protected function availableActions(Api\OrderManagement\Model\Order $order) : array
	{
		$availableActions = $order->availableActions();

		if ($availableActions === null) { return []; }

		$result = [];

		/** @var Api\OrderManagement\Model\Order\AvailableAction $action */
		foreach ($order->availableActions() as $action)
		{
			try
			{
				$activity = Trading\Activity\Registry::make($action->name(), $this->service, $this->environment, $this->trading->getId());

				if ($activity instanceof Trading\Activity\Reference\HiddenActivity) { continue; }

				$result[] = [
					'NAME' => $action->name(),
					'TITLE' => $activity->title($order),
					'CONFIRM' => $activity instanceof Trading\Activity\Reference\CommandActivity ? $activity->confirm() : null,
					'ORDER' => $activity->order(),
					'BEHAVIOR' => Trading\Activity\Registry::activityType($activity),
					'REQUIRED' => $action->required(),
				];
			}
			catch (Main\SystemException $exception)
			{
				trigger_error($exception->getMessage(), E_USER_WARNING);
				continue;
			}
		}

		uasort($result, static function($aButton, $bButton) {
			$aOrder = $aButton['ORDER'] - ($aButton['REQUIRED'] ? 1000 : 0);
			$bOrder = $bButton['ORDER'] - ($bButton['REQUIRED'] ? 1000 : 0);

			if ($aOrder === $bOrder) { return 0; }

			return ($aOrder < $bOrder ? -1 : 1);
		});

		return $result;
	}
}
