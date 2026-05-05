<?php
namespace Avito\Export\Trading\Activity\Transition;

use Avito\Export\Api;
use Avito\Export\Concerns;
use Avito\Export\Data;
use Avito\Export\Trading\Activity as TradingActivity;

class Activity extends TradingActivity\Reference\CommandActivity
{
	use Concerns\HasLocale;

	public function title(Api\OrderManagement\Model\Order $order) : string
	{
		return self::getLocale(mb_strtoupper($this->name) . '_TITLE', null, $this->name);
	}

	public function note(Api\OrderManagement\Model\Order $order) : ?string
	{
		$variables = $this->noteVariables($order);
		$variants = [
			mb_strtoupper($this->name) . '_NOTE_' . mb_strtoupper($order->delivery()->serviceType()),
			mb_strtoupper($this->name) . '_NOTE',
		];
		$result = null;

		foreach ($variants as $variant)
		{
			$text = self::getLocale($variant, $variables, '');

			if ($text === '') { continue; }

			$result = $text;
			break;
		}

		return $result;
	}

	protected function noteVariables(Api\OrderManagement\Model\Order $order) : array
	{
		$schedule = $order->schedules()->meaningfulValues();
		$result = [
			'#SERVICE_NAME#' => $order->delivery()->serviceName(),
		];

		foreach ($schedule as $name => $date)
		{
			$result['#' . $name . '#'] = $date !== null ? Data\DateTime::format($date) : '';
			$result['#' . $name . '_DATE#'] = $date !== null ? Data\Date::format($date) : '';
		}

		if (isset($schedule['DELIVERY_DATE_MIN']) || isset($schedule['DELIVERY_DATE_MAX']))
		{
			$result['#DELIVERY_PERIOD#'] = Data\DateTimePeriod::format($schedule['DELIVERY_DATE_MIN'], $schedule['DELIVERY_DATE_MAX']);
		}

		return $result;
	}

	public function order() : int
	{
		return 100;
	}

	public function path() : string
	{
		return 'send/status';
	}

	public function payload() : array
	{
		return [
			'transition' => $this->name,
		];
	}
}
