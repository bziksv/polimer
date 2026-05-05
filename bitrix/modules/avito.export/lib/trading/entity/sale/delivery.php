<?php
namespace Avito\Export\Trading\Entity\Sale;

use Avito\Export\Concerns;
use Bitrix\Sale;

class Delivery
{
	use Concerns\HasOnce;

	protected $environment;

	public function __construct(Container $environment)
	{
		$this->environment = $environment;
	}

	public function defaultVariant() : int
	{
		return Sale\Delivery\Services\Manager::getEmptyDeliveryServiceId();
	}

	public function variants() : array
	{
		return $this->once('variants', function() {
			$parentGroups = [];

			foreach (Sale\Delivery\Services\Manager::getActiveList(true) as $id => $fields)
			{
				$delivery = Sale\Delivery\Services\Manager::createObject($fields);

				if ($delivery === null) { continue; }

				$name = $delivery->getName();
				$parent = $delivery->getParentService();
				$parentId = $parent ? $parent->getId() : 0;

				if (!isset($parentGroups[$parentId]))
				{
					$parentGroups[$parentId] = [];
				}

				$parentGroups[$parentId][] = [
					'ID' => $id,
					'VALUE' => sprintf('[%s] %s', $id, $name),
					'GROUP' => $parent ? $parent->getName() : null,
				];
			}

			return !empty($parentGroups) ? array_merge(...$parentGroups) : [];
		});
	}
}