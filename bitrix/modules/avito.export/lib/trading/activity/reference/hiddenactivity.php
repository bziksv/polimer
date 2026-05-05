<?php
namespace Avito\Export\Trading\Activity\Reference;

use Avito\Export\Api;

abstract class HiddenActivity extends Activity
{
	public function title(Api\OrderManagement\Model\Order $order) : string
	{
		return 'dummy';
	}
}