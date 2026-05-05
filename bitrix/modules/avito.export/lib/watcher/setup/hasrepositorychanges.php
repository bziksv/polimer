<?php
namespace Avito\Export\Watcher\Setup;

use Bitrix\Main\ORM;

trait HasRepositoryChanges
{
	public static function getChangesMap() : array
	{
		return [
			new ORM\Fields\BooleanField('AUTO_UPDATE', [
				'values' => ['0', '1'],
				'default_value' => '1',
			]),
		];
	}
}