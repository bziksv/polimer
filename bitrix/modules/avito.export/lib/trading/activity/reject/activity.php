<?php
namespace Avito\Export\Trading\Activity\Reject;

use Avito\Export\Api;
use Avito\Export\Concerns;
use Avito\Export\Trading\Activity as TradingActivity;

class Activity extends TradingActivity\Transition\Activity
{
	use Concerns\HasLocale;

	public function title(Api\OrderManagement\Model\Order $order) : string
	{
		return self::getLocale('TITLE', null, $this->name);
	}

	public function confirm() : ?string
	{
		return self::getLocale('CONFIRM');
	}

	public function order() : int
	{
		return 1000;
	}
}
