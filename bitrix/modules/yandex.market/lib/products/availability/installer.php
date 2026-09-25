<?php
namespace Yandex\Market\Products\Availability;

use Yandex\Market\Reference\Storage;

class Installer
{
	public static function install()
	{
		Storage\Controller::createTable([ ChangeTable::class ]);
		EventHandler::registerEvents();
		AgentFacade::sync();
	}

	public static function uninstall($saveData = false)
	{
		AgentFacade::unregister();
		EventHandler::unregisterEvents();

		if (!$saveData)
		{
			Storage\Controller::dropTable([ ChangeTable::class ]);
		}
	}
}
