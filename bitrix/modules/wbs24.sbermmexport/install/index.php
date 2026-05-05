<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\EventManager;

Loc::loadMessages(__FILE__);

class wbs24_sbermmexport extends CModule {
    var $MODULE_ID = "wbs24.sbermmexport";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;
    var $MODULE_GROUP_RIGHTS = "Y";

    function __construct() {
        $arModuleVersion = [];

        $path = str_replace("\\", "/", __FILE__);
        $path = substr($path, 0, strlen($path) - strlen("/index.php"));
        include($path."/version.php");

        if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        } else {
            $this->MODULE_VERSION = "1.0.0";
            $this->MODULE_VERSION_DATE = "2021.10.21";
        }

        $this->MODULE_NAME = Loc::getMessage("WBS24.SBERMMEXPORT.INSTALL_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("WBS24.SBERMMEXPORT.INSTALL_DESCRIPTION");
        $this->PARTNER_NAME = Loc::getMessage("WBS24.SBERMMEXPORT.INSTALL_COMPANY_NAME");
        $this->PARTNER_URI  = "https://wbs24.ru/";
    }

    // Install functions
    function InstallDB() {
        RegisterModule($this->MODULE_ID);
        return true;
    }

    function InstallFiles() {
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/php_interface/include/catalog_export/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/include/catalog_export", true, true);
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/tools/catalog_export/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/tools/catalog_export", true, true);
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/tools/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/tools/".$this->MODULE_ID, true, false);
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/js/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js/".$this->MODULE_ID, true, true);

        return true;
    }

    function InstallPublic() {
        return true;
    }

    function InstallEvents() {
        EventManager::getInstance()->registerEventHandler(
            "main",
            "OnProlog",
            $this->MODULE_ID,
            "Wbs24\\Sbermmexport",
            "OnPrologHandler"
        );

        return true;
    }

    // UnInstal functions
    function UnInstallDB($arParams = []) {
        UnRegisterModule($this->MODULE_ID);
        return true;
    }

    function UnInstallFiles() {
        DeleteDirFiles(__DIR__.'/php_interface/include/catalog_export/', $_SERVER['DOCUMENT_ROOT'].'/bitrix/php_interface/include/catalog_export');
        DeleteDirFiles(__DIR__.'/tools/catalog_export/', $_SERVER['DOCUMENT_ROOT'].'/bitrix/tools/catalog_export');
        DeleteDirFiles(__DIR__.'/tools/', $_SERVER['DOCUMENT_ROOT'].'/bitrix/tools/'.$this->MODULE_ID);
        DeleteDirFiles(__DIR__.'/js/', $_SERVER['DOCUMENT_ROOT'].'/bitrix/js/'.$this->MODULE_ID);

        return true;
    }

    function UnInstallPublic() {
        return true;
    }

    function UnInstallEvents() {
        EventManager::getInstance()->unRegisterEventHandler(
            "main",
            "OnProlog",
            $this->MODULE_ID,
            "Wbs24\\Sbermmexport",
            "OnPrologHandler"
        );

        return true;
    }

    function DoInstall() {
        global $APPLICATION, $step;
        $keyGoodFiles = $this->InstallFiles();
        $keyGoodDB = $this->InstallDB();
        $keyGoodEvents = $this->InstallEvents();
        $keyGoodPublic = $this->InstallPublic();
        $APPLICATION->IncludeAdminFile(
            Loc::getMessage("SPER_INSTALL_TITLE"),
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/install.php"
        );
    }

    function DoUninstall() {
        global $APPLICATION, $step;
        $keyGoodFiles = $this->UnInstallFiles();
        $keyGoodDB = $this->UnInstallDB();
        $keyGoodEvents = $this->UnInstallEvents();
        $keyGoodPublic = $this->UnInstallPublic();
        $APPLICATION->IncludeAdminFile(
            Loc::getMessage("SPER_UNINSTALL_TITLE"),
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/uninstall.php"
        );
    }
}
?>
