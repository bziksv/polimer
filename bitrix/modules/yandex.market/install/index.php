<?php

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class yandex_market extends CModule
{
    var $MODULE_ID = 'yandex.market';
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $PARTNER_NAME;
	var $PARTNER_URI;

    function __construct()
    {
        $arModuleVersion = null;

        include __DIR__ . '/version.php';

        if (isset($arModuleVersion) && is_array($arModuleVersion))
        {
	        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
	        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

	    $this->MODULE_NAME = GetMessage('YANDEX_MARKET_MODULE_NAME');
	    $this->MODULE_DESCRIPTION = GetMessage('YANDEX_MARKET_MODULE_DESCRIPTION');

        $this->PARTNER_NAME = GetMessage('YANDEX_MARKET_PARTNER_NAME');
        $this->PARTNER_URI = GetMessage('YANDEX_MARKET_PARTNER_URI');
    }

    function GetModuleRightList()
	{
		$arr = [
			'reference_id' => ['D', 'PT', 'PE', 'R', 'W'],
			'reference' => [
				'[D] ' . GetMessage('YANDEX_MARKET_RIGHTS_DENIED'),
				'[PT] ' . GetMessage('YANDEX_MARKET_RIGHTS_PROCESS_TRADING'),
				'[PE] ' . GetMessage('YANDEX_MARKET_RIGHTS_PROCESS_EXPORT'),
				'[R] ' . GetMessage('YANDEX_MARKET_RIGHTS_READ'),
				'[W] ' . GetMessage('YANDEX_MARKET_RIGHTS_WRITE')
			]
		];
		return $arr;
	}

    function DoInstall()
    {
        global $APPLICATION;

        $result = true;

        try
        {
	        $this->checkRequirements();

	        Main\ModuleManager::registerModule($this->MODULE_ID);

	        if (Main\Loader::includeModule($this->MODULE_ID))
	        {
		        $this->InstallDB();
		        $this->InstallEvents();
		        $this->InstallAgents();
		        $this->InstallFiles();
		        $this->InstallCheckoutDB();
		    }
		    else
		    {
		        throw new Main\SystemException(GetMessage('YANDEX_MARKET_MODULE_NOT_REGISTERED'));
		    }
	    }
	    catch (\Exception $exception)
	    {
	        $result = false;
	        $APPLICATION->ThrowException($exception->getMessage());
	    }

	    return $result;
    }

    function DoUninstall()
    {
		global $APPLICATION, $step;

		$step = (int)$step;

		if ($step < 2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage('YANDEX_MARKER_UNINSTALL'), __DIR__ . '/unstep1.php');
		}
		else if ($step === 2)
		{
			if (Main\Loader::includeModule($this->MODULE_ID))
			{
				$request = Main\Application::getInstance()->getContext()->getRequest();
				$isSaveData = $request->get('savedata') === 'Y';

				if (!$isSaveData)
				{
					$this->UnInstallDB();
				}

				$this->UnInstallButtonTextOptions();
				$this->UnInstallEvents();
				$this->UnInstallAgents();
				$this->UnInstallFiles();
				$this->UnInstallMenu();
			}

			Main\ModuleManager::unRegisterModule($this->MODULE_ID);
		}
    }

    function InstallDB()
    {
		Market\Reference\Storage\Controller::createTable();
    }

    function UnInstallDB()
    {
        Market\Reference\Storage\Controller::dropTable();
    }

    function UnInstallButtonTextOptions()
    {
        Main\Config\Option::delete($this->MODULE_ID, ['name' => 'YAKIT_BUTTON_TEXT']);
        Main\Config\Option::delete($this->MODULE_ID, ['name' => 'PRODUCT_BUTTON_TEXT']);
    }

    function InstallEvents()
    {
		Market\Migration\Event::reset();
		$this->InstallCheckoutEvents();
		Market\Products\Prices\Installer::install();
		Market\Products\Availability\Installer::install();
    }

    function UnInstallEvents()
    {
        Market\Reference\Event\Controller::deleteAll();
        $this->UnInstallCheckoutEvents();
        Market\Products\Prices\Installer::uninstall(false);
        Market\Products\Availability\Installer::uninstall(false);
    }

    private function InstallCheckoutEvents()
    {
        $moduleId = $this->MODULE_ID;
        $handlerClass = '\\Yandex\\Market\\Checkout\\Handlers';
        $deliveryViewClass = '\\Yandex\\Market\\Checkout\\OrderDeliveryView';

        RegisterModuleDependences('main', 'OnBeforeProlog', $moduleId, $handlerClass, 'appendYandexCheckoutJs');
        RegisterModuleDependences('sale', 'OnSaleStatusOrderChange', $moduleId, $handlerClass, 'onSaleStatusOrderChange');

        RegisterModuleDependences('main', 'OnAdminSaleOrderView', $moduleId, $deliveryViewClass, 'OnAdminSaleOrderView');
        RegisterModuleDependences('main', 'OnEndBufferContent', $moduleId, $deliveryViewClass, 'onEndBufferContent');

        foreach ($this->getCheckoutMailGateEventNames() as $eventName) {
            UnRegisterModuleDependences('sale', $eventName, $moduleId, $handlerClass, 'onSaleOrderSavedMailGate');
            RegisterModuleDependences('sale', $eventName, $moduleId, $handlerClass, 'onSaleOrderSavedMailGate', 1);
        }
    }

    private function UnInstallCheckoutEvents()
    {
        $moduleId = $this->MODULE_ID;
        $handlerClass = '\\Yandex\\Market\\Checkout\\Handlers';
        $deliveryViewClass = '\\Yandex\\Market\\Checkout\\OrderDeliveryView';

        UnRegisterModuleDependences('main', 'OnBeforeProlog', $moduleId, $handlerClass, 'appendYandexCheckoutJs');
        UnRegisterModuleDependences('sale', 'OnSaleOrderSaved', $moduleId, $handlerClass, 'onSaleOrderSaved');
        UnRegisterModuleDependences('sale', 'OnSaleStatusOrderChange', $moduleId, $handlerClass, 'onSaleStatusOrderChange');

        UnRegisterModuleDependences('main', 'OnAdminSaleOrderView', $moduleId, $deliveryViewClass, 'OnAdminSaleOrderView');
        UnRegisterModuleDependences('main', 'OnEndBufferContent', $moduleId, $deliveryViewClass, 'onEndBufferContent');

        foreach ($this->getCheckoutMailGateEventNames() as $eventName) {
            UnRegisterModuleDependences('sale', $eventName, $moduleId, $handlerClass, 'onSaleOrderSavedMailGate');
        }
    }

    private function getCheckoutMailGateEventNames()
    {
        return \Yandex\Market\Checkout\Handlers::getMailGateEventNames();
    }

    function InstallAgents()
    {
        Market\Reference\Agent\Controller::updateRegular();
    }

    function UnInstallAgents()
    {
		Market\Reference\Agent\Controller::deleteAll();
    }

    function InstallFiles()
    {
        CopyDirFiles(__DIR__ . '/admin', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin', true, true);
        $this->InstallCheckoutFiles();
        CopyDirFiles(__DIR__ . '/components', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/components/yandex.market', true, true);
        CopyDirFiles(__DIR__ . '/css', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/css/yandex.market', true, true);
        CopyDirFiles(__DIR__ . '/js', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/js/yandex.market', true, true);
        CopyDirFiles(__DIR__ . '/images', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/images/yandex.market', true, true);
	    CopyDirFiles(__DIR__ . '/themes', $_SERVER['DOCUMENT_ROOT']. '/bitrix/themes', true, true);
        CopyDirFiles(__DIR__ . '/services', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/services/yandex.market', true, true);
        CopyDirFiles(__DIR__ . '/tools', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/tools/yandex.market', true, true);
    }

    function UnInstallFiles()
    {
        $this->UnInstallCheckoutFiles();
        DeleteDirFiles(__DIR__ . '/admin', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin');
		DeleteDirFilesEx('bitrix/components/yandex.market');
		DeleteDirFilesEx('bitrix/css/yandex.market');
		DeleteDirFilesEx('bitrix/js/yandex.market');
        DeleteDirFilesEx('bitrix/images/yandex.market');
	    DeleteDirFilesEx('bitrix/services/yandex.market');
	    DeleteDirFilesEx('bitrix/tools/yandex.market');
		DeleteDirFiles(__DIR__ . '/themes', $_SERVER['DOCUMENT_ROOT']. '/bitrix/themes');
		DeleteDirFilesEx('bitrix/themes/.default/icons/yandex.market');
    }

	function UnInstallMenu()
	{
		\CUserOptions::DeleteOption('yandex.market', 'menu_business');
	}

    function InstallCheckoutDB()
    {
        $this->migrateCheckoutOptions();

        $this->createCheckoutUser();
        $this->createCheckoutPaymentSystem();
        $this->updateCheckoutPaymentSystemId();
        $this->createCheckoutDeliveryService();
        $this->createCheckoutOrderProperties();

        if (!Main\Config\Option::get($this->MODULE_ID, 'STATUS_ON_PLACED')) {
            Main\Config\Option::set($this->MODULE_ID, 'STATUS_ON_PLACED', 'P');
        }
        if (!Main\Config\Option::get($this->MODULE_ID, 'STATUS_ON_CANCEL')) {
            Main\Config\Option::set($this->MODULE_ID, 'STATUS_ON_CANCEL', 'C');
        }
        if (!Main\Config\Option::get($this->MODULE_ID, 'STATUS_ON_DELIVERED')) {
            Main\Config\Option::set($this->MODULE_ID, 'STATUS_ON_DELIVERED', 'F');
        }
    }

    private function migrateCheckoutOptions()
    {
        try {
            $connection = Main\Application::getConnection();
            $oldOptions = $connection->query("SELECT NAME, VALUE FROM b_option WHERE MODULE_ID='yastore.checkout'");
            while ($row = $oldOptions->fetch()) {
                $current = Main\Config\Option::get($this->MODULE_ID, $row['NAME'], null);
                if ($current !== null && $current !== '') {
                    continue;
                }
                Main\Config\Option::set($this->MODULE_ID, $row['NAME'], $row['VALUE']);
            }
        } catch (\Exception $e) {
        }
    }

    function InstallCheckoutFiles()
    {
        $checkoutInstallDir = __DIR__ . '/checkout';

        if (!is_dir($checkoutInstallDir)) {
            return;
        }

        $docRoot = $_SERVER['DOCUMENT_ROOT'];

        if (is_dir($checkoutInstallDir . '/js')) {
            CopyDirFiles($checkoutInstallDir . '/js/yastore.checkout/', $docRoot . '/bitrix/js/yastore.checkout/', true, true);
        }
        if (is_dir($checkoutInstallDir . '/css')) {
            CopyDirFiles($checkoutInstallDir . '/css/yastore.checkout/', $docRoot . '/bitrix/css/yastore.checkout/', true, true);
        }
        if (is_dir($checkoutInstallDir . '/components')) {
            CopyDirFiles($checkoutInstallDir . '/components/yastore', $docRoot . '/bitrix/components/yastore', true, true);
        }
        if (is_dir($checkoutInstallDir . '/templates')) {
            CopyDirFiles($checkoutInstallDir . '/templates/yandex_kit/', $docRoot . '/bitrix/php_interface/include/sale_payment/yandex_kit/', true, true);
        }

        $this->installCheckoutPublicDir($checkoutInstallDir);
        $this->registerCheckoutUrlRewrite();
    }

    private function installCheckoutPublicDir($checkoutInstallDir)
    {
        $sourceFile = $checkoutInstallDir . '/public/yastore.checkout/index.php';
        $sourceHtaccess = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/api/.htaccess';
        if (!file_exists($sourceHtaccess)) {
            $sourceHtaccess = $checkoutInstallDir . '/api/.htaccess';
        }

        foreach ($this->getCheckoutDocumentRoots() as $docRoot) {
            $targetDir = $docRoot . '/yastore.checkout/';

            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            if (file_exists($sourceFile)) {
                copy($sourceFile, $targetDir . 'index.php');
            }

            if (file_exists($sourceHtaccess)) {
                copy($sourceHtaccess, $targetDir . '.htaccess');
            }
        }
    }

    function UnInstallCheckoutFiles()
    {
        foreach ($this->getCheckoutDocumentRoots() as $docRoot) {
            $targetFile = $docRoot . '/yastore.checkout/index.php';
            if (file_exists($targetFile)) {
                unlink($targetFile);
            }
            $targetHtaccess = $docRoot . '/yastore.checkout/.htaccess';
            if (file_exists($targetHtaccess)) {
                unlink($targetHtaccess);
            }
        }

        $this->unregisterCheckoutUrlRewrite();
    }

    /**
     * Уникальные публичные корни активных сайтов (DOC_ROOT + DIR, многосайтовость).
     *
     * @return string[]
     */
    private function getCheckoutDocumentRoots()
    {
        $roots = [];
        $fallback = rtrim(str_replace('\\', '/', (string)$_SERVER['DOCUMENT_ROOT']), '/');
        if ($fallback !== '') {
            $roots[$fallback] = $fallback;
        }

        try {
            if (!class_exists('Bitrix\Main\SiteTable')) {
                return array_values($roots);
            }

            $rs = Main\SiteTable::getList([
                'filter' => ['=ACTIVE' => 'Y'],
                'select' => ['LID', 'DIR', 'DOC_ROOT'],
            ]);
            while ($site = $rs->fetch()) {
                $publicRoot = $this->resolveCheckoutSitePublicRoot($site);
                if ($publicRoot !== '') {
                    $roots[$publicRoot] = $publicRoot;
                }
            }
        } catch (\Exception $e) {
        }

        return array_values($roots);
    }

    /**
     * @param array $site
     * @return string
     */
    private function resolveCheckoutSitePublicRoot(array $site)
    {
        $appRoot = rtrim(str_replace('\\', '/', (string)(
            class_exists('Bitrix\Main\Application')
                ? (string)Main\Application::getDocumentRoot()
                : (string)$_SERVER['DOCUMENT_ROOT']
        )), '/');

        $docRoot = trim(str_replace('\\', '/', (string)($site['DOC_ROOT'] ?? '')));
        if ($docRoot === '') {
            $docRoot = $appRoot;
        } elseif (!preg_match('#^(/|[A-Za-z]:/)#', $docRoot)) {
            $docRoot = $appRoot . '/' . ltrim($docRoot, '/');
        }
        $docRoot = rtrim($docRoot, '/');

        $dir = trim(str_replace('\\', '/', (string)($site['DIR'] ?? '/')), '/');
        if ($dir === '') {
            return $docRoot;
        }

       if (strcasecmp(basename($docRoot), $dir) === 0) {
            return $docRoot;
        }

        return $docRoot . '/' . $dir;
    }

    /**
     * @param array $site
     * @return string
     */
    private function resolveCheckoutUrlPath(array $site)
    {
        $appRoot = rtrim(str_replace('\\', '/', (string)(
            class_exists('Bitrix\Main\Application')
                ? (string)Main\Application::getDocumentRoot()
                : (string)$_SERVER['DOCUMENT_ROOT']
        )), '/');

        $urlDocRoot = trim(str_replace('\\', '/', (string)($site['DOC_ROOT'] ?? '')));
        if ($urlDocRoot === '') {
            $urlDocRoot = $appRoot;
        } elseif (!preg_match('#^(/|[A-Za-z]:/)#', $urlDocRoot)) {
            $urlDocRoot = $appRoot . '/' . ltrim($urlDocRoot, '/');
        }
        $urlDocRoot = rtrim($urlDocRoot, '/');

        $publicRoot = $this->resolveCheckoutSitePublicRoot($site);
        $suffix = '/yastore.checkout';

        if ($publicRoot === $urlDocRoot) {
            return $suffix;
        }

        if ($urlDocRoot !== '' && strpos($publicRoot, $urlDocRoot . '/') === 0) {
            return substr($publicRoot, strlen($urlDocRoot)) . $suffix;
        }

        $dir = '/' . trim(str_replace('\\', '/', (string)($site['DIR'] ?? '/')), '/');
        if ($dir === '/') {
            return $suffix;
        }

        return rtrim($dir, '/') . $suffix;
    }

    private function registerCheckoutUrlRewrite()
    {
        try {
            if (!class_exists('Bitrix\Main\SiteTable')) {
                return;
            }
            $rs = Main\SiteTable::getList([
                'filter' => ['=ACTIVE' => 'Y'],
                'select' => ['LID', 'DIR', 'DOC_ROOT'],
            ]);
            while ($site = $rs->fetch()) {
                $urlPath = $this->resolveCheckoutUrlPath($site);
                Main\UrlRewriter::add($site['LID'], [
                    'CONDITION' => '#^' . preg_quote($urlPath, '#') . '/#',
                    'RULE' => '',
                    'ID' => 'yastore:checkout',
                    'PATH' => $urlPath . '/index.php',
                ]);
            }
        } catch (\Exception $e) {
        }
    }

    private function unregisterCheckoutUrlRewrite()
    {
        try {
            if (!class_exists('Bitrix\Main\SiteTable')) {
                return;
            }
            $rs = Main\SiteTable::getList([
                'filter' => ['=ACTIVE' => 'Y'],
                'select' => ['LID', 'DIR', 'DOC_ROOT'],
            ]);
            while ($site = $rs->fetch()) {
                $urlPath = $this->resolveCheckoutUrlPath($site);
                Main\UrlRewriter::delete($site['LID'], [
                    'CONDITION' => '#^' . preg_quote($urlPath, '#') . '/#',
                ]);
              
                Main\UrlRewriter::delete($site['LID'], [
                    'CONDITION' => '#^/yastore.checkout/#',
                ]);
            }
        } catch (\Exception $e) {
        }
    }

    private function createCheckoutUser()
    {
        try {
            $user = new \CUser;
            $uniqString = md5(time() . uniqid());

            $arFields = [
                'NAME' => 'YastoreCheckoutUser',
                'LOGIN' => 'yastore_checkout_user_' . substr($uniqString, 0, 5),
                'EMAIL' => 'yastore_' . substr($uniqString, 0, 5) . '@yandex.ru',
                'PASSWORD' => $uniqString,
                'CONFIRM_PASSWORD' => $uniqString,
                'ACTIVE' => 'Y',
                'GROUP_ID' => [2],
            ];

            $newUserId = $user->Add($arFields);
            if (intval($newUserId) > 0) {
                Main\Config\Option::set($this->MODULE_ID, 'YASTORE_USER_ID', $newUserId);
            }
        } catch (\Exception $e) {
        }
    }

    private function createCheckoutPaymentSystem()
    {
        try {
            if (!Main\Loader::includeModule('sale')) {
                return;
            }

            $this->copyCheckoutPaymentHandler();

            $db = Main\Application::getConnection();
            $actionFile = '/bitrix/php_interface/include/sale_payment/yandex_kit';
            $existingPaySystemAction = $db->query("
                SELECT PAY_SYSTEM_ID
                FROM b_sale_pay_system_action
                WHERE ACTION_FILE = '" . $db->getSqlHelper()->forSql($actionFile) . "'
                ORDER BY PAY_SYSTEM_ID DESC
                LIMIT 1
            ")->fetch();

            $paySystemId = null;

            if ($existingPaySystemAction) {
                $paySystemId = $existingPaySystemAction['PAY_SYSTEM_ID'];
            } else {
                $fields = [
                    'NAME' => 'YCP',
                    'PSA_NAME' => 'YCP',
                    'ACTIVE' => 'N',
                    'SORT' => 1000,
                    'DESCRIPTION' => 'Служебная платежная система для YCP',
                    'ACTION_FILE' => $actionFile,
                    'NEW_WINDOW' => 'N',
                    'XML_ID' => 'YANDEX_KIT_PAYMENT',
                    'ENTITY_REGISTRY_TYPE' => \Bitrix\Sale\Registry::REGISTRY_TYPE_ORDER,
                    'HAVE_PREPAY' => 'N',
                    'HAVE_RESULT' => 'N',
                    'HAVE_ACTION' => 'N',
                    'HAVE_PAYMENT' => 'N',
                    'HAVE_RESULT_RECEIVE' => 'Y',
                ];

                $result = \Bitrix\Sale\PaySystem\Manager::add($fields);

                if ($result->isSuccess()) {
                    $actionId = $result->getId();
                    if ($actionId) {
                        \Bitrix\Sale\PaySystem\Manager::update($actionId, [
                            'PAY_SYSTEM_ID' => $actionId,
                            'PARAMS' => serialize(['BX_PAY_SYSTEM_ID' => $actionId])
                        ]);
                        $createdAction = $db->query("
                            SELECT PAY_SYSTEM_ID
                            FROM b_sale_pay_system_action
                            WHERE ID = " . intval($actionId) . "
                            LIMIT 1
                        ")->fetch();

                        $paySystemId = ($createdAction && $createdAction['PAY_SYSTEM_ID'])
                            ? $createdAction['PAY_SYSTEM_ID']
                            : $actionId;
                    }
                }
            }

            if ($paySystemId) {
                Main\Config\Option::set($this->MODULE_ID, 'YANDEX_KIT_PAY_SYSTEM_ID', $paySystemId);
            }
        } catch (\Exception $e) {
        }
    }

    private function updateCheckoutPaymentSystemId()
    {
        try {
            if (!Main\Loader::includeModule('sale')) {
                return;
            }
            $db = Main\Application::getConnection();
            $actionFile = '/bitrix/php_interface/include/sale_payment/yandex_kit';
            $existingPaySystemAction = $db->query("
                SELECT PAY_SYSTEM_ID
                FROM b_sale_pay_system_action
                WHERE ACTION_FILE = '" . $db->getSqlHelper()->forSql($actionFile) . "'
                ORDER BY PAY_SYSTEM_ID DESC
                LIMIT 1
            ")->fetch();

            if ($existingPaySystemAction) {
                Main\Config\Option::set($this->MODULE_ID, 'YANDEX_KIT_PAY_SYSTEM_ID', $existingPaySystemAction['PAY_SYSTEM_ID']);
            }
        } catch (\Exception $e) {
        }
    }

    private function copyCheckoutPaymentHandler()
    {
        try {
            $sourceDir = __DIR__ . '/checkout/templates/yandex_kit/';
            $targetDir = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/sale_payment/yandex_kit/';

            if (!is_dir($sourceDir)) {
                return;
            }

            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            $files = ['handler.php', '.description.php', 'template.php', 'lang/ru/handler.php'];
            foreach ($files as $file) {
                $sourceFile = $sourceDir . $file;
                $targetFile = $targetDir . $file;
                if (file_exists($sourceFile)) {
                    $targetDirPath = dirname($targetFile);
                    if (!is_dir($targetDirPath)) {
                        mkdir($targetDirPath, 0755, true);
                    }
                    copy($sourceFile, $targetFile);
                }
            }
        } catch (\Exception $e) {
        }
    }

    private function createCheckoutDeliveryService()
    {
        try {
            if (!Main\Loader::includeModule('sale')) {
                return;
            }

            require_once __DIR__ . '/../lib/checkout/delivery/yandexkitdelivery.php';

            $db = Main\Application::getConnection();
            $newClassName = '\\Yandex\\Market\\Checkout\\Delivery\\YandexKitDelivery';
            $existingDelivery = $db->query("SELECT ID, CLASS_NAME FROM b_sale_delivery_srv WHERE NAME = 'YCP' LIMIT 1")->fetch();

            if ($existingDelivery) {
                Main\Config\Option::set($this->MODULE_ID, 'YANDEX_KIT_DELIVERY_ID', $existingDelivery['ID']);
                if ($existingDelivery['CLASS_NAME'] !== $newClassName) {
                    \Bitrix\Sale\Delivery\Services\Manager::update((int)$existingDelivery['ID'], [
                        'CLASS_NAME' => $newClassName,
                    ]);
                }
                return;
            }

            $result = \Bitrix\Sale\Delivery\Services\Manager::add([
                'NAME' => 'YCP',
                'ACTIVE' => 'N',
                'SORT' => 1000,
                'DESCRIPTION' => 'Служебная служба доставки для YCP',
                'CODE' => 'YANDEX_KIT_DELIVERY',
                'CLASS_NAME' => $newClassName,
            ]);

            if ($result->isSuccess()) {
                Main\Config\Option::set($this->MODULE_ID, 'YANDEX_KIT_DELIVERY_ID', $result->getId());
            }
        } catch (\Exception $e) {
        }
    }

    private function createCheckoutOrderProperties()
    {
        try {
            if (!Main\Loader::includeModule('sale')) {
                return;
            }

            $personTypes = \Bitrix\Sale\PersonType::getList([
                'filter' => ['ACTIVE' => 'Y'],
                'select' => ['ID', 'NAME']
            ])->fetchAll();

            if (empty($personTypes)) {
                return;
            }

            $propertiesToCreate = [
                ['CODE' => 'EXTERNAL_ORDER_ID', 'NAME' => 'Внешний ID заказа (Яндекс)', 'TYPE' => 'STRING', 'SORT' => 100],
                ['CODE' => 'YANDEX_ORDER_ID', 'NAME' => 'Внешний ID Заказа (Яндекс ID)', 'TYPE' => 'STRING', 'SORT' => 110],
                ['CODE' => 'YANDEX_ORDER_NUM', 'NAME' => 'Заказ № (Яндекс)', 'TYPE' => 'STRING', 'SORT' => 90],
                ['CODE' => 'DELIVERY_TYPE', 'NAME' => 'Тип доставки', 'TYPE' => 'STRING', 'SORT' => 200],
                ['CODE' => 'DELIVERY_SERVICE', 'NAME' => 'Служба доставки', 'TYPE' => 'STRING', 'SORT' => 210],
                ['CODE' => 'DELIVERY_DATE', 'NAME' => 'Дата доставки', 'TYPE' => 'STRING', 'SORT' => 217],
                ['CODE' => 'DELIVERY_STATUS', 'NAME' => 'Статус доставки', 'TYPE' => 'STRING', 'SORT' => 218],
                ['CODE' => 'DELIVERY_BARCODE_URL', 'NAME' => 'Ссылка на штрихкод', 'TYPE' => 'STRING', 'SORT' => 219],
                ['CODE' => 'DELIVERY_TRACKING_URL', 'NAME' => 'Ссылка для отслеживания', 'TYPE' => 'STRING', 'SORT' => 220],
                ['CODE' => 'PAYMENT_METHOD', 'NAME' => 'Тип оплаты', 'TYPE' => 'STRING', 'SORT' => 250],
                ['CODE' => 'PAYMENT_METHOD_DETAIL', 'NAME' => 'Детали оплаты (карта/СБП/...)', 'TYPE' => 'STRING', 'SORT' => 260],
                ['CODE' => 'STREET', 'NAME' => 'Улица', 'TYPE' => 'STRING', 'SORT' => 310],
                ['CODE' => 'BUILDING', 'NAME' => 'Дом', 'TYPE' => 'STRING', 'SORT' => 320],
                ['CODE' => 'APARTMENT', 'NAME' => 'Квартира', 'TYPE' => 'STRING', 'SORT' => 330],
                ['CODE' => 'ENTRANCE', 'NAME' => 'Подъезд', 'TYPE' => 'STRING', 'SORT' => 340],
                ['CODE' => 'FLOOR', 'NAME' => 'Этаж', 'TYPE' => 'STRING', 'SORT' => 350],
                ['CODE' => 'INTERCOM', 'NAME' => 'Домофон', 'TYPE' => 'STRING', 'SORT' => 360],
                ['CODE' => 'PICKUP_POINT_ID', 'NAME' => 'ID пункта выдачи', 'TYPE' => 'STRING', 'SORT' => 400],
            ];

            foreach ($personTypes as $personType) {
                $personTypeId = $personType['ID'];

                $propsGroup = \Bitrix\Sale\Internals\OrderPropsGroupTable::getList([
                    'filter' => ['PERSON_TYPE_ID' => $personTypeId],
                    'select' => ['ID'],
                    'limit' => 1
                ])->fetch();

                if (!$propsGroup) {
                    $groupResult = \Bitrix\Sale\Internals\OrderPropsGroupTable::add([
                        'PERSON_TYPE_ID' => $personTypeId,
                        'NAME' => 'Свойства заказа',
                        'SORT' => 100,
                    ]);
                    if (!$groupResult->isSuccess()) {
                        continue;
                    }
                    $propsGroupId = $groupResult->getId();
                } else {
                    $propsGroupId = $propsGroup['ID'];
                }

                foreach ($propertiesToCreate as $propData) {
                    $existing = \Bitrix\Sale\Internals\OrderPropsTable::getList([
                        'filter' => ['PERSON_TYPE_ID' => $personTypeId, 'CODE' => $propData['CODE']],
                        'select' => ['ID'],
                        'limit' => 1
                    ])->fetch();

                    if ($existing) {
                        continue;
                    }

                    \Bitrix\Sale\Internals\OrderPropsTable::add([
                        'PERSON_TYPE_ID' => $personTypeId,
                        'PROPS_GROUP_ID' => $propsGroupId,
                        'NAME' => $propData['NAME'],
                        'CODE' => $propData['CODE'],
                        'TYPE' => $propData['TYPE'],
                        'REQUIRED' => 'N',
                        'USER_PROPS' => 'N',
                        'IS_LOCATION' => 'N',
                        'IS_EMAIL' => 'N',
                        'IS_PROFILE_NAME' => 'N',
                        'IS_PAYER' => 'N',
                        'IS_LOCATION4TAX' => 'N',
                        'IS_FILTERED' => 'N',
                        'IS_ZIP' => 'N',
                        'IS_PHONE' => 'N',
                        'IS_ADDRESS' => 'N',
                        'ACTIVE' => 'Y',
                        'UTIL' => 'Y',
                        'SORT' => $propData['SORT'],
                        'DEFAULT_VALUE' => '',
                        'DESCRIPTION' => '',
                        'SETTINGS' => 'a:0:{}',
                        'ENTITY_REGISTRY_TYPE' => 'ORDER',
                    ]);
                }
            }
        } catch (\Exception $e) {
        }
    }

    function checkRequirements()
    {
        // require php version

		$requirePhp = '5.6.0';

        if (CheckVersion(PHP_VERSION, $requirePhp) === false)
        {
			throw new \Exception(GetMessage('YANDEX_MARKET_INSTALL_REQUIRE_PHP', [ '#VERSION#' => $requirePhp ]));
        }

        // require simplexml extension

		if (!class_exists('\\SimpleXMLElement'))
		{
			throw new \Exception(GetMessage('YANDEX_MARKET_INSTALL_REQUIRE_SIMPLEXML'));
		}

        // required modules

        $requireModules = [
			'main' => '15.5.0',
			'iblock' => '15.0.0'
		];

        if (class_exists('\\Bitrix\\Main\\ModuleManager'))
        {
			foreach ($requireModules as $moduleName => $moduleVersion)
			{
				$currentVersion = Main\ModuleManager::getVersion($moduleName);

				if ($currentVersion !== false && CheckVersion($currentVersion, $moduleVersion))
				{
					unset($requireModules[$moduleName]);
				}
			}
        }

        if (!empty($requireModules))
        {
	        $moduleVersion = reset($requireModules);
			$moduleName = key($requireModules);

            throw new \Exception(GetMessage('YANDEX_MARKET_INSTALL_REQUIRE_MODULE', [
                '#MODULE#' => $moduleName,
                '#VERSION#' => $moduleVersion
            ]));
        }
    }
}