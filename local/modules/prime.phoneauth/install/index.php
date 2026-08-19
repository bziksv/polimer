<?php

use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class prime_phoneauth extends CModule
{
	public $MODULE_ID = 'prime.phoneauth';
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME;
	public $MODULE_DESCRIPTION;
	public $MODULE_GROUP_RIGHTS = 'N';
	public $PARTNER_NAME;
	public $PARTNER_URI;

	public function __construct()
	{
		$arModuleVersion = [];
		include __DIR__ . '/version.php';

		$this->MODULE_VERSION = $arModuleVersion['VERSION'];
		$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		$this->MODULE_NAME = Loc::getMessage('PRIME_PHONEAUTH_MODULE_NAME');
		$this->MODULE_DESCRIPTION = Loc::getMessage('PRIME_PHONEAUTH_MODULE_DESC');
		$this->PARTNER_NAME = Loc::getMessage('PRIME_PHONEAUTH_PARTNER_NAME');
		$this->PARTNER_URI = Loc::getMessage('PRIME_PHONEAUTH_PARTNER_URI');
	}

	public function DoInstall()
	{
		$this->InstallDB();
		$this->InstallEvents();
		$this->InstallUserFields();
		return true;
	}

	public function DoUninstall()
	{
		$this->UnInstallEvents();
		$this->UnInstallDB();
		return true;
	}

	public function InstallDB()
	{
		if (!ModuleManager::isModuleInstalled($this->MODULE_ID)) {
			ModuleManager::registerModule($this->MODULE_ID);
		}

		if (class_exists('\Bitrix\Main\ModuleTable')) {
			\Bitrix\Main\ModuleTable::getEntity()->cleanCache();
		}

		if (\Bitrix\Main\Loader::includeModule($this->MODULE_ID)) {
			\Prime\PhoneAuth\Challenge::installTable();
		}

		return true;
	}

	public function UnInstallDB()
	{
		\Bitrix\Main\Config\Option::delete($this->MODULE_ID);
		ModuleManager::unRegisterModule($this->MODULE_ID);

		if (class_exists('\Bitrix\Main\ModuleTable')) {
			\Bitrix\Main\ModuleTable::getEntity()->cleanCache();
		}

		return true;
	}

	public function InstallUserFields()
	{
		$this->addUserField([
			'FIELD_NAME' => 'UF_PHONE_CONFIRMED',
			'USER_TYPE_ID' => 'boolean',
			'XML_ID' => 'UF_PHONE_CONFIRMED',
			'SORT' => 500,
			'SHOW_FILTER' => 'I',
			'SHOW_IN_LIST' => 'Y',
			'EDIT_IN_LIST' => 'Y',
			'SETTINGS' => [
				'DEFAULT_VALUE' => 0,
				'DISPLAY' => 'CHECKBOX',
				'LABEL' => ['Нет', 'Да'],
				'LABEL_CHECKBOX' => 'Да',
			],
			'EDIT_FORM_LABEL' => ['ru' => 'Телефон подтверждён', 'en' => 'Phone confirmed'],
			'LIST_COLUMN_LABEL' => ['ru' => 'Телефон подтверждён', 'en' => 'Phone confirmed'],
		]);

		$this->addUserField([
			'FIELD_NAME' => 'UF_PHONE_NORM',
			'USER_TYPE_ID' => 'string',
			'XML_ID' => 'UF_PHONE_NORM',
			'SORT' => 510,
			'SHOW_FILTER' => 'S',
			'SHOW_IN_LIST' => 'N',
			'EDIT_IN_LIST' => 'N',
			'SETTINGS' => [
				'SIZE' => 20,
				'ROWS' => 1,
				'MAX_LENGTH' => 20,
			],
			'EDIT_FORM_LABEL' => ['ru' => 'Телефон (нормализованный)', 'en' => 'Phone normalized'],
		]);

		return true;
	}

	protected function addUserField(array $fields): void
	{
		$rs = \CUserTypeEntity::GetList([], [
			'ENTITY_ID' => 'USER',
			'FIELD_NAME' => $fields['FIELD_NAME'],
		]);
		if ($rs && $rs->Fetch()) {
			return;
		}

		$fields = array_merge([
			'ENTITY_ID' => 'USER',
			'MULTIPLE' => 'N',
			'MANDATORY' => 'N',
			'IS_SEARCHABLE' => 'N',
		], $fields);

		$entity = new \CUserTypeEntity();
		$entity->Add($fields);
	}

	public function InstallEvents()
	{
		$this->UnInstallEvents();

		$em = EventManager::getInstance();
		$em->registerEventHandler('main', 'OnBeforeUserRegister', $this->MODULE_ID, '\\Prime\\PhoneAuth\\Handlers', 'onBeforeUserRegister');
		$em->registerEventHandler('main', 'OnBeforeUserAdd', $this->MODULE_ID, '\\Prime\\PhoneAuth\\Handlers', 'onBeforeUserAdd');
		$em->registerEventHandler('main', 'OnAfterUserAdd', $this->MODULE_ID, '\\Prime\\PhoneAuth\\Handlers', 'onAfterUserAdd');
		$em->registerEventHandler('main', 'OnAfterUserRegister', $this->MODULE_ID, '\\Prime\\PhoneAuth\\Handlers', 'onAfterUserRegister', 200);
		$em->registerEventHandler('main', 'OnBeforeUserUpdate', $this->MODULE_ID, '\\Prime\\PhoneAuth\\Handlers', 'onBeforeUserUpdate');
		$em->registerEventHandler('main', 'OnProlog', $this->MODULE_ID, '\\Prime\\PhoneAuth\\Handlers', 'onProlog');
		$em->registerEventHandler('main', 'OnEndBufferContent', $this->MODULE_ID, '\\Prime\\PhoneAuth\\Frontend', 'onEndBufferContent');

		return true;
	}

	public function UnInstallEvents()
	{
		$em = EventManager::getInstance();
		$em->unRegisterEventHandler('main', 'OnBeforeUserRegister', $this->MODULE_ID, '\\Prime\\PhoneAuth\\Handlers', 'onBeforeUserRegister');
		$em->unRegisterEventHandler('main', 'OnBeforeUserAdd', $this->MODULE_ID, '\\Prime\\PhoneAuth\\Handlers', 'onBeforeUserAdd');
		$em->unRegisterEventHandler('main', 'OnAfterUserAdd', $this->MODULE_ID, '\\Prime\\PhoneAuth\\Handlers', 'onAfterUserAdd');
		$em->unRegisterEventHandler('main', 'OnAfterUserRegister', $this->MODULE_ID, '\\Prime\\PhoneAuth\\Handlers', 'onAfterUserRegister');
		$em->unRegisterEventHandler('main', 'OnBeforeUserUpdate', $this->MODULE_ID, '\\Prime\\PhoneAuth\\Handlers', 'onBeforeUserUpdate');
		$em->unRegisterEventHandler('main', 'OnProlog', $this->MODULE_ID, '\\Prime\\PhoneAuth\\Handlers', 'onProlog');
		$em->unRegisterEventHandler('main', 'OnEndBufferContent', $this->MODULE_ID, '\\Prime\\PhoneAuth\\Frontend', 'onEndBufferContent');

		return true;
	}
}
