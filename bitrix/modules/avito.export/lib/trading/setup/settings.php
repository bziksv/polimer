<?php
namespace Avito\Export\Trading\Setup;

use Avito\Export\Admin\UserField;
use Avito\Export\Concerns;
use Avito\Export\Data\Number;
use Avito\Export\Exchange;
use Avito\Export\Trading\Entity as TradingEntity;
use Avito\Export\Trading\Service as TradingService;
use Avito\Export\Utils\DependField;

class Settings extends Exchange\Setup\SettingsSkeleton
	implements TradingEntity\Sale\PropertyMapper
{
	use Concerns\HasLocale;

	protected $settingsBridge;
	protected $tradingEnvironment;
	protected $tradingService;

	public function __construct(Exchange\Setup\SettingsBridge $settingsBridge, array $values)
	{
		parent::__construct($values);

		$this->settingsBridge = $settingsBridge;
		$this->tradingEnvironment = TradingEntity\Registry::environment();
		$this->tradingService = TradingService\Registry::service();
	}

	public function commonSettings() : Exchange\Setup\Settings
	{
		return $this->settingsBridge->commonSettings();
	}

	public function personType() : int
	{
		return (int)$this->requireValue('PERSON_TYPE');
	}

	public function delivery() : ?int
	{
		return Number::cast($this->value('DELIVERY'));
	}

	public function paySystem() : ?int
	{
		return Number::cast($this->value('PAY_SYSTEM'));
	}

	public function commissionPaySystem() : ?int
	{
		return Number::cast($this->value('COMMISSION_PAY_SYSTEM'));
	}

	public function buyerProfile() : ?int
	{
		return Number::cast($this->value('BUYER_PROFILE'));
	}

	public function propertyId(string $type) : ?int
	{
		return Number::cast($this->value('PROPERTY_' . $type));
	}

	public function statusIn(string $externalStatus) : array
	{
		$name = 'STATUS_IN_' . $externalStatus;

		return array_filter(
			array_map(static function($status) { return (string)$status; }, (array)$this->value($name)),
			static function ($status) { return $status !== ''; }
		);
	}

	public function statusOut(string $status) : ?string
	{
		$result = null;
		$variants = [
			TradingService\Status::TRANSITION_CONFIRM,
			TradingService\Status::TRANSITION_PERFORM,
			TradingService\Status::TRANSITION_RECEIVE,
			TradingService\Status::TRANSITION_REJECT,
		];

		foreach ($variants as $variant)
		{
			$name = 'STATUS_OUT_' . $variant;
			$value = (array)$this->value($name);

			if (in_array($status, $value, true))
			{
				$result = $variant;
				break;
			}
		}

		return $result;
	}

	public function fields(array $sites = null) : array
	{
		if ($this->tradingEnvironment === null) { return []; }

		$personTypeEnum = $this->tradingEnvironment->personType()->variants($sites);
		$personTypeDefault = $this->tradingEnvironment->personType()->legalDefault($sites) ?? $personTypeEnum[0]['ID'] ?? null;

		return
			$this->orderFields($personTypeEnum, $personTypeDefault)
			+ $this->scheduleFields($personTypeDefault)
			+ $this->deliveryFields($personTypeDefault)
			+ $this->utilFields($personTypeDefault)
			+ $this->statusFields()
			+ $this->transitionFields();
	}

	protected function orderFields(array $personTypeEnum, int $personTypeDefault = null) : array
	{
		return [
			'PERSON_TYPE' => [
				'TYPE' => 'enumeration',
				'NAME' => self::getLocale('PERSON_TYPE'),
				'VALUES' => $personTypeEnum,
				'MANDATORY' => 'Y',
				'SETTINGS' => [
					'DEFAULT_VALUE' => $personTypeDefault,
				],
			],
			'DELIVERY' => [
				'TYPE' => 'enumeration',
				'NAME' => self::getLocale('DELIVERY'),
				'VALUES' => $this->tradingEnvironment->delivery()->variants(),
				'SETTINGS' => [
					'DEFAULT_VALUE' => $this->tradingEnvironment->delivery()->defaultVariant(),
				],
			],
			'PAY_SYSTEM' => [
				'TYPE' => 'enumeration',
				'NAME' => self::getLocale('PAY_SYSTEM'),
				'VALUES' => $this->tradingEnvironment->paySystem()->variants(),
				'SETTINGS' => [
					'DEFAULT_VALUE' => $this->tradingEnvironment->paySystem()->defaultVariant(),
				],
			],
			'COMMISSION_PAY_SYSTEM' => [
				'TYPE' => 'enumeration',
				'NAME' => self::getLocale('COMMISSION_PAY_SYSTEM'),
				'VALUES' => $this->tradingEnvironment->paySystem()->variants(),
			],
			'BUYER_PROFILE' => [
				'TYPE' => 'buyerProfile',
				'NAME' => self::getLocale('BUYER_PROFILE'),
				'SETTINGS' => [
					'USER_ID' => $this->tradingEnvironment->anonymousUser()->id(),
					'PERSON_TYPE_FIELD' => 'TRADING_SETTINGS[PERSON_TYPE]',
					'PERSON_TYPE_DEFAULT' => $personTypeDefault,
				],
			],
		];
	}

	protected function scheduleFields(int $personTypeDefault = null) : array
	{
		$result = [];
		$scheduleFields = [
			'CONFIRM_TILL' => self::getLocale('CONFIRM_TILL'),
			'DELIVERY_DATE_MIN' => self::getLocale('DELIVERY_DATE_MIN'),
			'DELIVERY_DATE_MAX' => self::getLocale('DELIVERY_DATE_MAX'),
			'SHIP_TILL' => self::getLocale('SHIP_TILL'),
			'SET_TERMS_TILL' => self::getLocale('SET_TERMS_TILL'),
			'SET_TRACKING_NUMBER_TILL' => self::getLocale('SET_TRACKING_NUMBER_TILL'),
		];

		$depended = [
			'DELIVERY_DATE' => true,
			'SET_TERMS_TILL' => true,
			'SET_TRACKING_NUMBER_TILL' => true,
		];

		foreach ($scheduleFields as $scheduleField => $title)
		{
			$result['PROPERTY_SCHEDULE_' . $scheduleField] = [
				'TYPE' => 'orderProperty',
				'NAME' => $title,
                'CODE' => $scheduleField,
				'GROUP' => self::getLocale('GROUP_SCHEDULE'),
				'SETTINGS' => [
					'PERSON_TYPE_FIELD' => 'TRADING_SETTINGS[PERSON_TYPE]',
					'PERSON_TYPE_DEFAULT' => $personTypeDefault,
				],
			];

			if (isset($depended[$scheduleField]))
			{
				$result['PROPERTY_SCHEDULE_' . $scheduleField] += [
					'DEPEND' => [
						'USE_DBS' => [
							'RULE' => DependField::RULE_ANY,
							'VALUE' => UserField\BooleanType::VALUE_Y,
						],
					],
				];
			}
		}

		return $result;
	}

	protected function deliveryFields(int $personTypeDefault = null) : array
	{
		$result = [];

		$result['USE_DBS'] = [
			'TYPE' => 'boolean',
			'NAME' => self::getLocale('USE_DBS'),
			'GROUP' => self::getLocale('GROUP_DELIVERY'),
			'SETTINGS' => [
				'DEFAULT_VALUE' => UserField\BooleanType::VALUE_Y,
			],
		];

		$deliveryFields = [
			'NAME' => self::getLocale('NAME'),
			'PHONE' => self::getLocale('PHONE'),
			'ADDRESS' => self::getLocale('ADDRESS'),
		];

        $deliveryDefaultValuesMap = [
            'NAME' => [
                'FIO',
                'CONTACT_PERSON',
            ],
            'PHONE' => [
                'PHONE_NUMBER',
                'MOBILE',
                'MOBILE_PHONE',
            ],
        ];

		foreach ($deliveryFields as $deliveryField => $title)
		{
			$result['PROPERTY_DELIVERY_' . $deliveryField] = [
				'TYPE' => 'orderProperty',
				'NAME' => $title,
                'CODE' => $deliveryField,
				'GROUP' => self::getLocale('GROUP_DELIVERY'),
				'SETTINGS' => [
					'PERSON_TYPE_FIELD' => 'TRADING_SETTINGS[PERSON_TYPE]',
					'PERSON_TYPE_DEFAULT' => $personTypeDefault,
				],
				'DEPEND' => [
					'USE_DBS' => [
						'RULE' => DependField::RULE_ANY,
						'VALUE' => UserField\BooleanType::VALUE_Y,
					],
				],
			];

            if (!empty($deliveryDefaultValuesMap[$deliveryField]))
            {
                $result['PROPERTY_DELIVERY_' . $deliveryField]['SETTINGS']['DEFAULT_VALUES_MAP'] = $deliveryDefaultValuesMap[$deliveryField];
            }
		}

		return $result;
	}
	protected function utilFields(int $personTypeDefault = null) : array
	{
		$result = [
			'PROPERTY_ORDER_NUMBER_AVITO' => [
				'TYPE' => 'orderProperty',
				'NAME' => self::getLocale('GROUP_INTERNAL_ORDER_NUMBER_AVITO'),
                'CODE' => 'ORDER_NUMBER_AVITO',
				'GROUP' => self::getLocale('GROUP_INTERNAL'),
				'SETTINGS' => [
					'PERSON_TYPE_FIELD' => 'TRADING_SETTINGS[PERSON_TYPE]',
					'PERSON_TYPE_DEFAULT' => $personTypeDefault,
	                'DEFAULT_VALUES_MAP' => [
	                    'ORDER_NUMBER',
	                    'ORDER_ID',
	                ],
				],
			],
			'PROPERTY_DELIVERY_DISPATCH_NUMBER' => [
				'TYPE' => 'orderProperty',
				'NAME' => self::getLocale('GROUP_INTERNAL_DELIVERY_DISPATCH_NUMBER'),
				'CODE' => 'DELIVERY_DISPATCH_NUMBER',
				'GROUP' => self::getLocale('GROUP_INTERNAL'),
				'SETTINGS' => [
					'PERSON_TYPE_FIELD' => 'TRADING_SETTINGS[PERSON_TYPE]',
					'PERSON_TYPE_DEFAULT' => $personTypeDefault,
				],
			],
		];

		return $result;
	}

	protected function statusFields() : array
	{
		$statusService = $this->tradingService->status();
		$statusDefaults = $statusService->statusDefaults();
		$result = [];

		foreach ($statusService->incomingStatuses() as $status)
		{
			$result['STATUS_IN_' . $status] = [
				'TYPE' => 'enumeration',
				'NAME' => $statusService->statusTitle($status),
				'VALUES' => $this->tradingEnvironment->status()->variants(),
				'GROUP' => self::getLocale('GROUP_STATUS_IN'),
				'SETTINGS' => [
					'DEFAULT_VALUE' => $statusDefaults[$status] ?? null,
				],
                'MULTIPLE' => 'Y',
			];
		}

		return $result;
	}

	protected function transitionFields() : array
	{
		$statusService = $this->tradingService->status();
		$transitionDefaults = $statusService->transitionDefaults();
		$result = [];

		$depended = [
			TradingService\Status::TRANSITION_PERFORM => true,
			TradingService\Status::TRANSITION_RECEIVE => true,
		];

		foreach ($statusService->transitions() as $transition)
		{
			$result['STATUS_OUT_' . $transition] = [
				'TYPE' => 'enumeration',
				'NAME' => $statusService->transitionTitle($transition),
				'VALUES' => $this->tradingEnvironment->status()->variants(),
				'GROUP' => self::getLocale('GROUP_STATUS_OUT'),
				'SETTINGS' => [
					'DEFAULT_VALUE' => $transitionDefaults[$transition] ?? null,
				],
                'MULTIPLE' => 'Y',
			];

			if (isset($depended[$transition]))
			{
				$result['STATUS_OUT_' . $transition] += [
					'DEPEND' => [
						'USE_DBS' => [
							'RULE' => DependField::RULE_ANY,
							'VALUE' => UserField\BooleanType::VALUE_Y,
						],
					],
				];
			}
		}

		return $result;
	}
}