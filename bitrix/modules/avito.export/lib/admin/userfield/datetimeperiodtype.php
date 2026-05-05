<?php
/** @noinspection PhpUnused */
namespace Avito\Export\Admin\UserField;

use Avito\Export\Data;

class DateTimePeriodType extends StringType
{
	public static function getAdminListViewHtml(array $userField, ?array $additionalParameters) : string
	{
		$value = Helper\ComplexValue::asSingle($userField, $additionalParameters);

		return Data\DateTimePeriod::format(
			Data\DateTime::cast($value['FROM'] ?? null),
			Data\DateTime::cast($value['TO'] ?? null)
		);
	}
}