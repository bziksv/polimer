<?php

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Avito\Export\Admin;
use Avito\Export\Exchange;
use Avito\Export\Glossary;
use Avito\Export\Trading;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin.php';

global $APPLICATION;

Loc::loadMessages(__FILE__);

$APPLICATION->SetTitle(Loc::getMessage('AVITO_EXPORT_ADMIN_EXCHANGE_EDIT_PAGE_TITLE'));

try
{
	if (!Loader::includeModule('avito.export'))
	{
		throw new Main\SystemException(Loc::getMessage('AVITO_EXPORT_NO_MODULE'));
	}

	$request = Main\Context::getCurrent()->getRequest();
	$primary = $request->getQuery('id');
	$baseQuery = [
		'lang' => LANGUAGE_ID,
	];
	$componentParameters = [
		'TITLE' => Loc::getMessage('AVITO_EXPORT_ADMIN_EXCHANGE_EDIT_PAGE_TITLE'),
		'FORM_ID' => 'AVITO_EXPORT_ADMIN_EXCHANGE_EDIT',
		'ALLOW_SAVE' => CMain::GetGroupRight('avito.export') >= 'W',
		'PRIMARY' => $primary,
		'LIST_URL' => BX_ROOT . '/admin/avito_export_push.php?' . http_build_query($baseQuery),
		'PROVIDER_TYPE' => Admin\Component\Exchange\EditForm::class,
		'DATA_CLASS_NAME' => Exchange\Setup\RepositoryTable::class,
		'CONTEXT_MENU' => array_filter([
			[
				'ICON' => 'btn_list',
				'LINK' => BX_ROOT . '/admin/avito_export_push.php?' . http_build_query($baseQuery),
				'TEXT' => Loc::getMessage('AVITO_EXPORT_ADMIN_EXCHANGE_EDIT_PAGE_CONTEXT_MENU_LIST'),
			],
            $primary > 0 ? [
                'TEXT' => Loc::getMessage('AVITO_EXPORT_ADMIN_EXCHANGE_EDIT_PAGE_RESET_PUSH'),
                'MENU' => [
                    [
                        'LINK' => $APPLICATION->GetCurPageParam(http_build_query(
                            [ 'postAction' => Admin\Component\Exchange\EditForm::ACTION_RESET_PUSH_UNPUBLISHED, 'sessid' => bitrix_sessid() ]),
                            [ 'postAction', 'sessid' ]
                        ),
                        'TEXT' => Loc::getMessage('AVITO_EXPORT_ADMIN_EXCHANGE_EDIT_PAGE_RESET_PUSH_UNPUBLISHED'),
                    ],
                    [
                        'LINK' => $APPLICATION->GetCurPageParam(http_build_query(
                            [ 'postAction' => Admin\Component\Exchange\EditForm::ACTION_RESET_PUSH_STOCKS, 'sessid' => bitrix_sessid() ]),
                            [ 'postAction', 'sessid' ]
                        ),
                        'TEXT' => Loc::getMessage('AVITO_EXPORT_ADMIN_EXCHANGE_EDIT_PAGE_RESET_PUSH_STOCKS'),
                    ],
                ],
            ] : null,
		]),
		'PRELOAD' => [
			'FEED_ID',
			'USE_PUSH',
			'USE_TRADING',
			'USE_CHAT',
		],
		'TABS' => [
			[
				'name' => Loc::getMessage('AVITO_EXPORT_ADMIN_EXCHANGE_EDIT_PAGE_TAB_COMMON'),
				'fields' => [
					'NAME',
					'FEED_ID',
					'COMMON_SETTINGS[CLIENT_ID]',
					'COMMON_SETTINGS[CLIENT_PASSWORD]',
					'COMMON_SETTINGS[OAUTH_TOKEN]',
				],
			],
			[
				'name' => Loc::getMessage('AVITO_EXPORT_ADMIN_EXCHANGE_EDIT_PAGE_TAB_PUSH'),
				'fields' => [
					'USE_PUSH',
					'PUSH_SETTINGS[QUANTITY_FIELD]',
					'PUSH_SETTINGS[USE_PRICES]',
					'PUSH_SETTINGS[AUTO_UPDATE]',
					'PUSH_SETTINGS[REFRESH_PERIOD]',
					'PUSH_SETTINGS[REFRESH_TIME]',
				]
			],
			[
				'name' => Loc::getMessage('AVITO_EXPORT_ADMIN_EXCHANGE_EDIT_PAGE_TAB_ORDER'),
				'fields' => [
					'USE_TRADING',
					'TRADING_SETTINGS[PERSON_TYPE]',
					'TRADING_SETTINGS[DELIVERY]',
					'TRADING_SETTINGS[PAY_SYSTEM]',
					'TRADING_SETTINGS[COMMISSION_PAY_SYSTEM]',
					'TRADING_SETTINGS[BUYER_PROFILE]',
					'TRADING_SETTINGS[USE_DBS]',
					'TRADING_SETTINGS[PROPERTY_DELIVERY_NAME]',
					'TRADING_SETTINGS[PROPERTY_DELIVERY_PHONE]',
					'TRADING_SETTINGS[PROPERTY_DELIVERY_ADDRESS]',
					'TRADING_SETTINGS[PROPERTY_SCHEDULE_SET_TERMS_TILL]',
					'TRADING_SETTINGS[PROPERTY_SCHEDULE_CONFIRM_TILL]',
					'TRADING_SETTINGS[PROPERTY_SCHEDULE_SHIP_TILL]',
					'TRADING_SETTINGS[PROPERTY_SCHEDULE_SET_TRACKING_NUMBER_TILL]',
					'TRADING_SETTINGS[PROPERTY_SCHEDULE_DELIVERY_DATE_MIN]',
					'TRADING_SETTINGS[PROPERTY_SCHEDULE_DELIVERY_DATE_MAX]',
					'TRADING_SETTINGS[PROPERTY_ORDER_NUMBER_AVITO]',
					'TRADING_SETTINGS[PROPERTY_DELIVERY_DISPATCH_NUMBER]',
				]
			],
			[
				'name' => Loc::getMessage('AVITO_EXPORT_ADMIN_EXCHANGE_EDIT_PAGE_TAB_STATUS'),
				'fields' => [
					'TRADING_SETTINGS[STATUS_IN_paid]',
					'TRADING_SETTINGS[STATUS_IN_on_confirmation]',
					'TRADING_SETTINGS[STATUS_IN_ready_to_ship]',
					'TRADING_SETTINGS[STATUS_IN_in_transit]',
					'TRADING_SETTINGS[STATUS_IN_delivered]',
					'TRADING_SETTINGS[STATUS_IN_in_dispute]',
					'TRADING_SETTINGS[STATUS_IN_on_return]',
					'TRADING_SETTINGS[STATUS_IN_canceled]',
					'TRADING_SETTINGS[STATUS_OUT_confirm]',
					'TRADING_SETTINGS[STATUS_OUT_reject]',
					'TRADING_SETTINGS[STATUS_OUT_perform]',
					'TRADING_SETTINGS[STATUS_OUT_receive]',
				]
			],
			[
				'name' => Loc::getMessage('AVITO_EXPORT_ADMIN_EXCHANGE_EDIT_PAGE_TAB_CHAT'),
				'fields' => [
					'USE_CHAT',
				]
			],
		],
	];

	if ($primary === null)
	{
		$componentParameters['BUTTONS'] = [
			[ 'BEHAVIOR' => 'save' ],
		];
		$componentParameters['BTN_SAVE'] = Loc::getMessage('AVITO_EXPORT_ADMIN_EXCHANGE_EDIT_PAGE_ADD_BUTTON');
		$componentParameters['SAVE_URL'] = $APPLICATION->GetCurPageParam('saved=Y&id=#ID#');
	}

	if ($primary !== null && $request->getQuery('saved') === 'Y')
	{
		try
		{
			$exchange = Exchange\Setup\Model::getById($primary);
			$exchangeType = null;
			$exchangeFilter = null;
			$exchangeDetails = null;

			if ($exchange->getUseTrading() && $exchange->getUsePush())
			{
				$exchangeDetails = Loc::getMessage('AVITO_EXPORT_ADMIN_EXCHANGE_EDIT_PAGE_ADDED_ALL_DETAILS', [
					'#PUSH_LOG_URL#' => 'avito_export_log.php?' . http_build_query([
						'lang' => LANGUAGE_ID,
						'find_setup_id' => Glossary::SERVICE_PUSH . ':' . (int)$primary,
						'set_filter' => 'Y',
						'apply_filter' => 'Y',
					]),
					'#TRADING_LOG_URL#' => 'avito_export_log.php?' . http_build_query([
						'lang' => LANGUAGE_ID,
						'find_setup_id' => Glossary::SERVICE_TRADING . ':' . (int)$primary,
						'set_filter' => 'Y',
						'apply_filter' => 'Y',
					]),
				]);
			}
			else if ($exchange->getUseTrading())
			{
				$exchangeDetails = Loc::getMessage('AVITO_EXPORT_ADMIN_EXCHANGE_EDIT_PAGE_ADDED_TRADING_DETAILS', [
					'#LOG_URL#' => 'avito_export_log.php?' . http_build_query([
						'lang' => LANGUAGE_ID,
						'find_setup_id' => Glossary::SERVICE_TRADING . ':' . (int)$primary,
						'set_filter' => 'Y',
						'apply_filter' => 'Y',
					]),
				]);
			}
			else if ($exchange->getUsePush())
			{
				$exchangeDetails = Loc::getMessage('AVITO_EXPORT_ADMIN_EXCHANGE_EDIT_PAGE_ADDED_PUSH_DETAILS', [
					'#LOG_URL#' => 'avito_export_log.php?' . http_build_query([
						'lang' => LANGUAGE_ID,
						'find_setup_id' => Glossary::SERVICE_PUSH . ':' . (int)$primary,
						'set_filter' => 'Y',
						'apply_filter' => 'Y',
					]),
				]);
			}

			if ($exchange->getUseChat() && $exchange->getTrading() !== null)
			{
				$environment = $exchange->getTrading()->getEnvironment();
				$tokenServiceId = $exchange->settingsBridge()->commonSettings()->token()->getServiceId();

				if (
					$environment instanceof Trading\Entity\SaleCrm\Container
					&& $environment->chatRegistry()->supports()
					&& !$environment->chatRegistry()->configured($tokenServiceId)
				)
				{
					$lineId = $environment->chatRegistry()->createConfig();
					if ($lineId !== null)
					{
						$exchangeDetails .= Loc::getMessage('AVITO_EXPORT_ADMIN_EXCHANGE_EDIT_PAGE_ADDED_OPEN_LINES', [
							'#LINES_ADD_URL#' => Admin\Path::crmUrl('/services/contact_center/connector/', [
								'ID' => Trading\Entity\SaleCrm\ChatRegistry::CONNECTOR_ID,
								'LINE' => $lineId,
								'action-line' => 'create',
							]),
						]);
					}
					else
					{
						$exchangeDetails .= Loc::getMessage('AVITO_EXPORT_ADMIN_EXCHANGE_EDIT_PAGE_ADDED_OPEN_LINES', [
							'#LINES_ADD_URL#' => Admin\Path::crmUrl('/services/contact_center/')
						]);
					}
				}
			}

			$componentParameters['FORM_ACTION_URI'] = $APPLICATION->GetCurPageParam('', [ 'saved' ]);
			$componentParameters['MESSAGE'] = [
				'TYPE' => 'OK',
				'MESSAGE' => Loc::getMessage('AVITO_EXPORT_ADMIN_EXCHANGE_EDIT_PAGE_ADDED_TITLE'),
				'DETAILS' => $exchangeDetails,
				'HTML' => true,
			];
		}
		catch (Main\ObjectNotFoundException $exception)
		{
			// nothing
		}
	}

	$APPLICATION->IncludeComponent('avito.export:admin.form.edit', '', $componentParameters);
}
catch (Main\SystemException $exception)
{
	echo (new CAdminMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => $exception->getMessage(),
	]))->Show();
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';