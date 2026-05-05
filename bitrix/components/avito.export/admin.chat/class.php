<?php
/** @noinspection PhpUnused */
namespace Avito\Export\Components;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Avito\Export\Api\OrderManagement\Model;
use Avito\Export\Exchange;
use Avito\Export\Trading;
use Avito\Export\Trading\Entity as TradingEntity;
use Avito\Export\Trading\Service as TradingService;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) { die(); }

class Chat extends \CBitrixComponent
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

    public function executeComponent() : void
    {
        try
        {
            $this->loadModules();
            $this->includeComponentTemplate();
        }
        catch (Main\SystemException $exception)
        {
	        \CAdminMessage::ShowMessage([
		        'TYPE' => 'ERROR',
		        'MESSAGE' => $exception->getMessage(),
		        'HTML' => true,
	        ]);
        }
    }

    public function onPrepareComponentParams($arParams) : array
    {
        $arParams['SETUP_ID'] = (int)$arParams['SETUP_ID'];
        $arParams['USER_ID'] = (int)$arParams['USER_ID'];

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
}