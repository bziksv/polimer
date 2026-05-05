<?php
namespace Avito\Export\Trading\Action;

use Bitrix\Main;
use Avito\Export\Api;
use Avito\Export\Trading;

class Facade
{
	public static function syncOrder(Trading\Setup\Model $trading, string $externalId) : void
	{
		try
		{
			$order = Api\OrderManagement\V1\Orders\Facade::getById($trading, $externalId);
			$procedure = new Trading\Action\Procedure($trading, 'order/status', [ 'order' => $order ]);

			$procedure->run();
		}
		catch (Main\SystemException $exception)
		{
			trigger_error($exception->getMessage(), E_USER_WARNING);
			Api\OrderManagement\V1\Orders\Facade::releaseCache($externalId);
		}
	}
}