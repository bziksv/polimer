<?php
/** @noinspection PhpUnused */
namespace Avito\Export\Admin\UserField;

class OrderDeliveryType extends StringType
{
	public static function getAdminListViewHtml(array $userField, ?array $additionalParameters) : string
	{
		$value = Helper\ComplexValue::asSingle($userField, $additionalParameters);

		$additional = [];
		if (!empty($value['TRACK']))
		{
			$additional[] = $value['TRACK'];
		}
		if (!empty($value['DISPATCH']) && $value['DISPATCH'] !== $value['TRACK'])
		{
			$additional[] = sprintf('(%s)', $value['DISPATCH']);
		}
		if (!empty($value['TYPE']))
		{
			$additional[] = $value['TYPE'];
		}

		$additional = array_map(static function($value) { return sprintf('<small>%s</small>', $value); }, $additional);

		return sprintf('%s<br />%s', $value['NAME'], implode('<br />', $additional));
	}
}