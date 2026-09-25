<?php

namespace Yandex\Market\Migration;

use Bitrix\Main;
use Yandex\Market;

class Event
{
	public static function canRestore($exception)
	{
		return false;
	}

	public static function check()
	{
		$result = !Version::check('event');

		if ($result)
		{
			Version::update('event');

			static::reset();
		}

		return $result;
	}

	public static function reset()
	{
		Market\Reference\Event\Controller::deleteAll();
		Market\Reference\Event\Controller::updateRegular();

		static::truncateExportTrackTable();
		static::restoreExportEvents();
	}

	protected static function truncateExportTrackTable()
	{
		$connection = Main\Application::getConnection();
		$tables = [
			Market\Watcher\Track\BindTable::getTableName(),
			Market\Watcher\Track\SourceTable::getTableName(),
			Market\Watcher\Track\StampTable::getTableName(),
			Market\Watcher\Track\ChangesTable::getTableName(),
		];

		foreach ($tables as $table)
		{
			$connection->truncateTable($table);
		}
	}

	protected static function restoreExportEvents()
	{
		$setupList = Market\Export\Setup\Model::loadList();

		foreach ($setupList as $setup)
		{
			$setup->updateListener();
		}
	}

}