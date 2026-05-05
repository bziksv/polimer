<?php
namespace Avito\Export\Dictionary\Sync;

use Avito\Export\Concerns;
use Avito\Export\Event as ModuleEvent;
use Avito\Export\Admin;

class Event extends ModuleEvent\Regular
{
	use Concerns\HasLocale;

	public static function getHandlers() : array
	{
		return [
			[
				'module' => 'iblock',
				'event' => 'OnAfterIBlockPropertyAdd',
				'method' => 'onAfterIBlockPropertyAdd',
			],
			[
				'module' => 'iblock',
				'event' => 'OnAfterIBlockPropertyDelete',
				'method' => 'onAfterIBlockPropertyDelete',
			],
			[
				'module' => 'iblock',
				'event' => 'OnAfterIBlockPropertyUpdate',
				'method' => 'onAfterIBlockPropertyUpdate',
			],
		];
	}

	/** @noinspection PhpUnused */
	public static function onAfterIBlockPropertyAdd(array $arFields) : void
	{
		self::syncPropertyAgent($arFields);
	}

	/** @noinspection PhpUnused */
	public static function onAfterIBlockPropertyDelete(array $arFields) : void
	{
		self::syncPropertyAgent($arFields);
	}

	/** @noinspection PhpUnused */
	public static function onAfterIBlockPropertyUpdate(array $arFields) : void
	{
		self::syncPropertyAgent($arFields);
	}

	private static function syncPropertyAgent(array $arFields) : void
	{
		if (!self::isOurProperty($arFields)) { return; }

		if (self::characteristicsPropertyExists())
		{
			Agent::install();
		}
		else
		{
			Agent::uninstall();
		}
	}

	private static function isOurProperty(array $arFields) : bool
	{
		return (
			isset($arFields['USER_TYPE'])
			&& $arFields['USER_TYPE'] === Admin\Property\CharacteristicProperty::USER_TYPE
		);
	}

	private static function characteristicsPropertyExists() : bool
	{
		$query = \CIBlockProperty::GetList([], [
			'USER_TYPE' => Admin\Property\CharacteristicProperty::USER_TYPE,
			'ACTIVE' => 'Y',
		]);

		return $query->Fetch() !== false;
	}
}

