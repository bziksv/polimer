<?
IncludeModuleLangFile(__FILE__);

include_once $_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/arturgolubev.ecommerce/lib/installation.php';

Class arturgolubev_ecommerce extends CModule
{
	const MODULE_ID = 'arturgolubev.ecommerce';
	var $MODULE_ID = 'arturgolubev.ecommerce'; 
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $strError = '';

	function __construct()
	{
		$arModuleVersion = array();
		include(dirname(__FILE__)."/version.php");
		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		$this->MODULE_NAME = GetMessage("arturgolubev.ecommerce_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("arturgolubev.ecommerce_MODULE_DESC");

		$this->PARTNER_NAME = GetMessage("arturgolubev.ecommerce_PARTNER_NAME");
		$this->PARTNER_URI = GetMessage("arturgolubev.ecommerce_PARTNER_URI");
	}

	function InstallDB($arParams = array())
	{
		RegisterModuleDependences('main', 'OnEpilog', self::MODULE_ID, 'CArturgolubevEcommerce', 'ProtectEpilogStart');
		RegisterModuleDependences('main', 'OnEndBufferContent', self::MODULE_ID, 'CArturgolubevEcommerce', 'onBufferContent');
		RegisterModuleDependences('form', 'onAfterResultAdd', self::MODULE_ID, 'CArturgolubevEcommerce', 'onFormResultAdd');
		
		$eventManager = \Bitrix\Main\EventManager::getInstance();
        $eventManager->registerEventHandler('sale', '\Bitrix\Sale\Internals\Basket::OnAfterAdd', self::MODULE_ID, 'CArturgolubevEcommerce', 'onBasketAddD7', 500);
        $eventManager->registerEventHandler('sale', '\Bitrix\Sale\Internals\Basket::OnUpdate', self::MODULE_ID, 'CArturgolubevEcommerce', 'onBasketUpdD7', 500);
        $eventManager->registerEventHandler('sale', '\Bitrix\Sale\Internals\Basket::OnBeforeDelete', self::MODULE_ID, 'CArturgolubevEcommerce', 'onBasketBeforeDeleteD7', 500);
        $eventManager->registerEventHandler('sale', '\Bitrix\Sale\Internals\Basket::OnAfterDelete', self::MODULE_ID, 'CArturgolubevEcommerce', 'onBasketAfterDeleteD7', 500);
		
        $eventManager->registerEventHandler('sale', 'OnSaleOrderBeforeSaved', self::MODULE_ID, 'CArturgolubevEcommerce', 'onBeforeOrderSaveD7', 500);
        $eventManager->registerEventHandler('sale', 'OnSaleOrderSaved', self::MODULE_ID, 'CArturgolubevEcommerce', 'onOrderAddD7', 500);
		
		// new!
		// $eventManager = \Bitrix\Main\EventManager::getInstance();
        // $eventManager->registerEventHandler('sale', 'OnSaleOrderBeforeSaved', 'arturgolubev.ecommerce', 'CArturgolubevEcommerce', 'onBeforeOrderSaveD7', 500);
		
		//old
		// RegisterModuleDependences('sale', 'OnBasketAdd', 'arturgolubev.ecommerce', 'CArturgolubevEcommerce', 'onBasketAdd');
		// RegisterModuleDependences('sale', 'OnOrderAdd', 'arturgolubev.ecommerce', 'CArturgolubevEcommerce', 'onOrderAdd');
		// RegisterModuleDependences('sale', 'OnBeforeBasketDelete', 'arturgolubev.ecommerce', 'CArturgolubevEcommerce', 'onBasketDelete');
		// RegisterModuleDependences('sale', 'OnBasketDelete', 'arturgolubev.ecommerce', 'CArturgolubevEcommerce', 'onBasketDeleteAfter');
		
		return true;
	}

	function UnInstallDB($arParams = array())
	{
		UnRegisterModuleDependences('main', 'OnEpilog', self::MODULE_ID, 'CArturgolubevEcommerce', 'ProtectEpilogStart');
		UnRegisterModuleDependences('main', 'OnEndBufferContent', self::MODULE_ID, 'CArturgolubevEcommerce', 'onBufferContent');
		UnRegisterModuleDependences('form', 'onAfterResultAdd', self::MODULE_ID, 'CArturgolubevEcommerce', 'onFormResultAdd');
		
		$eventManager = \Bitrix\Main\EventManager::getInstance();
        $eventManager->unRegisterEventHandler('sale', '\Bitrix\Sale\Internals\Basket::OnAfterAdd', self::MODULE_ID, 'CArturgolubevEcommerce', 'onBasketAddD7');
        $eventManager->unRegisterEventHandler('sale', '\Bitrix\Sale\Internals\Basket::OnUpdate', self::MODULE_ID, 'CArturgolubevEcommerce', 'onBasketUpdD7');
        $eventManager->unRegisterEventHandler('sale', '\Bitrix\Sale\Internals\Basket::OnBeforeDelete', self::MODULE_ID, 'CArturgolubevEcommerce', 'onBasketBeforeDeleteD7');
        $eventManager->unRegisterEventHandler('sale', '\Bitrix\Sale\Internals\Basket::OnAfterDelete', self::MODULE_ID, 'CArturgolubevEcommerce', 'onBasketAfterDeleteD7');
		
        $eventManager->unRegisterEventHandler('sale', 'OnSaleOrderBeforeSaved', self::MODULE_ID, 'CArturgolubevEcommerce', 'onBeforeOrderSaveD7');
        $eventManager->unRegisterEventHandler('sale', 'OnSaleOrderSaved', self::MODULE_ID, 'CArturgolubevEcommerce', 'onOrderAddD7');
		
		return true;
	}

	function InstallEvents()
	{
		return true;
	}

	function UnInstallEvents()
	{
		return true;
	}

	function InstallFiles($arParams = array())
	{
		if (is_dir($p = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.self::MODULE_ID.'/install/components'))
		{
			if ($dir = opendir($p))
			{
				while (false !== $item = readdir($dir))
				{
					if ($item == '..' || $item == '.')
						continue;
					CopyDirFiles($p.'/'.$item, $_SERVER['DOCUMENT_ROOT'].'/bitrix/components/'.$item, $ReWrite = True, $Recursive = True);
				}
				closedir($dir);
			}
		}
		
		$mPath = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID;
		
		CopyDirFiles($mPath."/install/tools", $_SERVER["DOCUMENT_ROOT"]."/bitrix/tools",true,true);
		CopyDirFiles($mPath."/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js",true,true);
		CopyDirFiles($mPath."/install/gadgets", $_SERVER["DOCUMENT_ROOT"]."/bitrix/gadgets",true,true);
		CopyDirFiles($mPath."/install/themes", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes", true, true);
		
		if(class_exists('agInstaHelperEcommerce')){
			agInstaHelperEcommerce::addGadgetToDesctop("WATCHER");
		}
		
		return true;
	}

	function UnInstallFiles()
	{
		if (is_dir($p = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.self::MODULE_ID.'/install/components'))
		{
			if ($dir = opendir($p))
			{
				while (false !== $item = readdir($dir))
				{
					if ($item == '..' || $item == '.' || !is_dir($p0 = $p.'/'.$item))
						continue;

					$dir0 = opendir($p0);
					while (false !== $item0 = readdir($dir0))
					{
						if ($item0 == '..' || $item0 == '.')
							continue;
						DeleteDirFilesEx('/bitrix/components/'.$item.'/'.$item0);
					}
					closedir($dir0);
				}
				closedir($dir);
			}
		}
		
		DeleteDirFilesEx("/bitrix/tools/".self::MODULE_ID);
		DeleteDirFilesEx("/bitrix/js/".self::MODULE_ID);

		
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".self::MODULE_ID."/install/themes/.default/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes/.default");
		DeleteDirFilesEx("/bitrix/themes/.default/icons/".self::MODULE_ID."/");
		
		return true;
	}

	function DoInstall()
	{		
		$this->InstallFiles();
		$this->InstallDB();
		RegisterModule(self::MODULE_ID);
		
		if (class_exists('agInstaHelperEcommerce')){
			agInstaHelperEcommerce::IncludeAdminFile(GetMessage("MOD_INST_OK"), "/bitrix/modules/".self::MODULE_ID."/install/success_install.php");
		}
	}

	function DoUninstall()
	{
		UnRegisterModule(self::MODULE_ID);
		$this->UnInstallDB();
		$this->UnInstallFiles();
	}
}
?>
