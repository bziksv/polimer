<?php
/** @noinspection PhpUnused */
namespace Avito\Export\Admin\UserField;

use Avito\Export\Concerns;

class OrderPricesType extends StringType
{
	use Concerns\HasLocale;

	public static function getAdminListViewHtml(array $userField, ?array $additionalParameters) : string
	{
		$value = Helper\ComplexValue::asSingle($userField, $additionalParameters);

		if (!isset($value['PRICE_FORMATTED'])) { return '&nbsp;'; }

		$result = $value['PRICE_FORMATTED'];
		$diffFields = [
			'COMMISSION',
			'DISCOUNT',
		];

		foreach ($diffFields as $diffField)
		{
			if (empty($value[$diffField])) { continue; }

			$result .= sprintf(
				'<br /><small>%s: -%s</small>',
				self::getLocale($diffField),
				$value[$diffField . '_FORMATTED']
			);
		}

		return $result;
	}
}