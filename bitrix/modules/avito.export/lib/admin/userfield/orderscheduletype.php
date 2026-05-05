<?php
/** @noinspection PhpUnused */
namespace Avito\Export\Admin\UserField;

use Avito\Export\Concerns;
use Avito\Export\Data;
use Bitrix\Main;

class OrderScheduleType extends StringType
{
	use Concerns\HasLocale;

	public static function getAdminListViewHtml(array $userField, ?array $additionalParameters) : string
	{
		$value = Helper\ComplexValue::asSingle($userField, $additionalParameters);

		if (!is_array($value)) { return ''; }

		$shopAction = static::searchShopAction($value);

		if ($shopAction !== null)
		{
			/** @var Main\Type\DateTime $date */
			[$action, $date] = $shopAction;

			return sprintf('%s<br /><small>%s</small>', Data\DateTime::format($date), self::getLocale($action));
		}

		$deliveryDate = Data\DateTime::cast($value['DELIVERY_DATE']);
		$period = $deliveryDate !== null
			? Data\DateTime::format($deliveryDate)
			: Data\DateTimePeriod::format(
				Data\DateTime::cast($value['DELIVERY_FROM']),
				Data\DateTime::cast($value['DELIVERY_TO'])
			);

		if ($period !== '')
		{
			return sprintf('%s<br /><small>%s</small>', $period, self::getLocale('DELIVERY'));
		}

		return '';
	}

	protected static function searchShopAction(array $value) : ?array
	{
		$result = null;
		$shopActions = [
			'SET_TERMS_TILL',
			'CONFIRM_TILL',
			'SET_TRACKING_NUMBER_TILL',
			'SHIP_TILL',
		];

		foreach ($shopActions as $shopAction)
		{
			if (!isset($value[$shopAction])) { continue; }

			$date = Data\DateTime::cast($value[$shopAction]);

			if ($date === null || Data\DateTime::compare($date, new Main\Type\DateTime()) === -1) { continue; }

			$result = [
				$shopAction,
				$date,
			];
		}

		return $result;
	}
}