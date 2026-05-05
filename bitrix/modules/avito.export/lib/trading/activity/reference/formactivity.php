<?php
namespace Avito\Export\Trading\Activity\Reference;

use Avito\Export\Trading;
use Avito\Export\Api;

abstract class FormActivity extends Activity
{
	abstract public function fields(Trading\Entity\Sale\Order $saleOrder, Api\OrderManagement\Model\Order $externalOrder) : array;

	abstract public function values(Trading\Entity\Sale\Order $saleOrder, Api\OrderManagement\Model\Order $externalOrder) : array;

	abstract public function path() : string;

	abstract public function payload(array $values) : array;
}