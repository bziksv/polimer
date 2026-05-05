<?php
namespace Avito\Export\Admin\UseCase\MassiveEdit;

use Avito\Export\Admin;
use Avito\Export\Concerns;
use Avito\Export\Event as ModuleEvent;
use Bitrix\Main\UI\Extension;
use Bitrix\Main;
use Bitrix\Iblock;

class Event extends ModuleEvent\Regular
{
	use Concerns\HasLocale;

	public static function getHandlers() : array
	{
		return [
			[
				'module' => 'main',
				'event' => 'OnAdminListDisplay',
				'method' => 'OnAdminListDisplay',
				'sort' => 1000,
			],
		];
	}

	/** @noinspection PhpUnused */
	public static function OnAdminListDisplay(\CAdminList $list) : void
	{
		if (!static::isTargetList($list)) { return; }

		$iblockId = static::iblockId();

		if ($iblockId === null || $iblockId <= 0) { return; }

		$isFirst = true;

		foreach (static::characteristicProperties($iblockId) as $property)
		{
			Extension::load('avitoexport.ui.admin.massiveedit');

			$actionCode = 'avito_change_category' . ($isFirst ? '' : '_' . $property['ID']);
			$options = [
				'iblockId' => $iblockId,
				'propertyId' => (int)$property['ID'],
				'language' => LANGUAGE_ID,
			];

			if (static::isOnlySectionList($list))
			{
				$options['prefixSelected'] = 'S';
			}

			$list->arActions[$actionCode] = [
				'name' => self::getLocale('ACTION', [ '#NAME#' => $property['NAME'] ], $property['NAME']),
				'type' => 'customJs',
				'js' => sprintf(
					'BX.AvitoExport.Ui.Admin.massiveEditOpen("%s", %s)',
					htmlspecialcharsbx($list->table_id),
					Main\Web\Json::encode($options)
				),
			];

			$isFirst = false;
		}
	}

	protected static function isTargetList(\CAdminList $list) : bool
	{
		return (
			mb_strpos($list->table_id, 'tbl_iblock_element_') === 0 // elements
			|| mb_strpos($list->table_id, 'tbl_iblock_list_') === 0 // categories and elements
			|| static::isOnlySectionList($list)
		);
	}

	protected static function isOnlySectionList(\CAdminList $list) : bool
	{
		return mb_strpos($list->table_id, 'tbl_iblock_section_') === 0;
	}

	protected static function iblockId() : ?int
	{
		$request = Main\Application::getInstance()->getContext()->getRequest();
		$iblockId = $request->get('IBLOCK_ID');

		if ($iblockId === null) { return null; }

		return (int)$iblockId;
	}

	protected static function characteristicProperties(int $iblockId) : array
	{
		if (!Main\Loader::includeModule('iblock')) { return []; }

		$query = Iblock\PropertyTable::getList([
			'filter' => [
				'=IBLOCK_ID' => $iblockId,
				'=ACTIVE' => true,
				'=USER_TYPE' => Admin\Property\CharacteristicProperty::USER_TYPE,
			],
			'select' => [
				'ID',
				'NAME',
			],
		]);

		return $query->fetchAll();
	}
}

