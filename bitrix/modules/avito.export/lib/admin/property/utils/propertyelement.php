<?php

namespace Avito\Export\Admin\Property\Utils;

use Bitrix\Main;

class PropertyElement
{
	public static function findElementId(array $controlName) : ?int
	{
		return self::parseIdFromUrl()
			?? self::parseIdFromControl($controlName);
	}

	protected static function parseIdFromUrl() : ?int
	{
		$request = Main\Application::getInstance()->getContext()->getRequest();

		if (
			$request->getRequestedPage() === '/bitrix/admin/iblock_element_edit.php'
			|| $request->getRequestedPage() === '/bitrix/admin/iblock_subelement_edit.php'
		)
		{
			$id = $request->get('ID');
			$id = is_numeric($id) ? (int)$id : 0;

			return $id > 0 ? $id : null;
		}

		return null;
	}

	protected static function parseIdFromControl(array $controlName) : ?int
	{
		if (!isset($controlName['FORM_NAME']) || !self::isTargetForm($controlName['FORM_NAME']))
		{
			return null;
		}

		if (preg_match('/^FIELDS\[E?(\d+)]/', $controlName['VALUE'], $matches))
		{
			[, $elementId] = $matches;

			return (int)$elementId;
		}

		return null;
	}

	protected static function isTargetForm(string $form) : bool
	{
		return mb_strpos($form, 'form_tbl_iblock_element_') === 0
			|| mb_strpos($form, 'form_tbl_iblock_list_') === 0;
	}
}