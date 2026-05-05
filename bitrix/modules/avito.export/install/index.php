<?php
/**
 * @noinspection PhpMissingReturnTypeInspection
 * @noinspection ReturnTypeCanBeDeclaredInspection
 */
use Bitrix\Main;
use Bitrix\Main\ModuleManager;
use Avito\Export\Feed;
use Avito\Export\Exchange;
use Avito\Export\Api;

Main\Localization\Loc::loadMessages(__FILE__);

class avito_export extends CModule
{
	public $MODULE_ID = 'avito.export';
	public $MODULE_NAME;
	public $MODULE_DESCRIPTION;
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $PARTNER_NAME;
	public $PARTNER_URI;

	public function __construct()
	{
		$arModuleVersion = null;

		include __DIR__ . '/version.php';

		if (isset($arModuleVersion) && is_array($arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion['VERSION'];
			$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		}

		$this->MODULE_NAME = GetMessage('AVITO_EXPORT_MODULE_NAME');
		$this->MODULE_DESCRIPTION = GetMessage('AVITO_EXPORT_MODULE_DESCRIPTION');

		$this->PARTNER_NAME = GetMessage('AVITO_EXPORT_PARTNER_NAME');
		$this->PARTNER_URI = GetMessage('AVITO_EXPORT_PARTNER_URI');
	}

	public function DoInstall()
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
				$this->InstallAvitoFeed();
				$this->InstallAvitoExchange();
				$this->InstallAvitoToken();

				$APPLICATION->IncludeAdminFile('', __DIR__ . '/step1.php');
			}
			else
			{
				throw new Main\SystemException(GetMessage('AVITO_EXPORT_MODULE_NOT_REGISTERED'));
			}
		}
		catch (Exception $exception)
		{
			$result = false;
			$APPLICATION->ThrowException($exception->getMessage());
		}

		return $result;
	}

	protected function checkRequirements()
	{
		// require php version

		$requirePhp = '7.2.0';

		if (CheckVersion(PHP_VERSION, $requirePhp) === false)
		{
			throw new Main\SystemException(GetMessage('AVITO_EXPORT_INSTALL_REQUIRE_PHP', ['#VERSION#' => $requirePhp]));
		}

		// required modules

		$requireModules = [
			'main' => '19.0.325',
			'iblock' => '18.6.850',
		];

		if (class_exists(ModuleManager::class))
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

			throw new Main\SystemException(GetMessage('AVITO_EXPORT_INSTALL_REQUIRE_MODULE', [
				'#MODULE#' => $moduleName,
				'#VERSION#' => $moduleVersion,
			]));
		}
	}

	public function InstallDB()
	{
		Avito\Export\DB\Controller::createTables();
	}

	public function InstallEvents()
	{
		Avito\Export\Event\Controller::updateRegular();
	}

	public function InstallAgents()
	{
		Avito\Export\Agent\Controller::updateRegular();
	}

	public function InstallFiles()
	{
		CopyDirFiles(__DIR__ . '/admin', $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/admin', true, true);
		CopyDirFiles(__DIR__ . '/components', $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/components/' . $this->MODULE_ID, true, true);
		CopyDirFiles(__DIR__ . '/images', $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/images/' . $this->MODULE_ID, true, true);
		CopyDirFiles(__DIR__ . '/tools', $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/tools/' . $this->MODULE_ID, true, true);
		CopyDirFiles(__DIR__ . '/js', $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/js/' . str_replace('.', '', $this->MODULE_ID), true, true);
		CopyDirFiles(__DIR__ . '/themes', $_SERVER['DOCUMENT_ROOT']. BX_ROOT . '/themes', true, true);
	}

	private function InstallAvitoFeed()
	{
		$query = Feed\Setup\RepositoryTable::getList();

		while ($feed = $query->fetchObject())
		{
			$feed->activate();
		}
	}

	private function InstallAvitoExchange()
	{
		$query = Exchange\Setup\RepositoryTable::getList();

		while ($feed = $query->fetchObject())
		{
			$feed->activate();
		}
	}

	private function InstallAvitoToken()
	{
		$query = Api\OAuth\TokenTable::getList();

		while ($token = $query->fetchObject())
		{
			$token->installAgent();
		}
	}

	/** @noinspection SpellCheckingInspection */
	public function DoUninstall()
	{
		global $APPLICATION, $step;

		$step = (int)$step;

		if ($step < 2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage('AVITO_EXPORT_UNINSTALL'), __DIR__ . '/unstep1.php');
		}
		elseif ($step === 2)
		{
			if (Main\Loader::includeModule($this->MODULE_ID))
			{
				$request = Main\Application::getInstance()->getContext()->getRequest();
				$isSaveData = $request->get('savedata') === 'Y';

				if (!$isSaveData)
				{
					$this->UnInstallDB();
				}

				$this->UnInstallEvents();
				$this->UnInstallAgents();
				$this->UnInstallFiles();
			}

			Main\ModuleManager::unRegisterModule($this->MODULE_ID);
		}
	}

	public function UnInstallDB()
	{
		Avito\Export\DB\Controller::dropTables();
	}

	public function UnInstallEvents()
	{
		Avito\Export\Event\Controller::deleteAll();
	}

	public function UnInstallAgents()
	{
		Avito\Export\Agent\Controller::deleteAll();
	}

	public function UnInstallFiles()
	{
		DeleteDirFiles(__DIR__ . '/admin', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin');
		DeleteDirFilesEx(BX_ROOT . '/components/avito.export/');
		DeleteDirFilesEx(BX_ROOT . '/images/avito.export/');
		DeleteDirFilesEx(BX_ROOT . '/tools/avito.export/');
		DeleteDirFilesEx(BX_ROOT . '/js/avitoexport/');
		DeleteDirFiles(__DIR__ . '/themes', $_SERVER['DOCUMENT_ROOT']. '/bitrix/themes');
	}
}
