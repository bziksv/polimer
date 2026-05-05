<?php
/** @noinspection PhpUnused */
namespace Avito\Export\Admin\UserField;

use Avito\Export\Concerns;

class OrderStatusType extends EnumerationType
{
	use Concerns\HasLocale;

	public static function GetAdminListViewHTML($userField, $htmlControl) : string
	{
		$result = parent::GetAdminListViewHTML($userField, $htmlControl);

		if (!empty($userField['ROW']['RETURN_STATUS']))
		{
			return sprintf('%s<br /><small>%s</small>', $result, $userField['ROW']['RETURN_STATUS']);
		}

		return $result;
	}
}