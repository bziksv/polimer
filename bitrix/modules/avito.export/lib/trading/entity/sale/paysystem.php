<?php
namespace Avito\Export\Trading\Entity\Sale;

use Avito\Export\Concerns;
use Bitrix\Sale;

class PaySystem
{
	use Concerns\HasOnce;

	protected $environment;

	public function __construct(Container $environment)
	{
		$this->environment = $environment;
	}

	public function defaultVariant() : ?int
	{
		return $this->once('defaultVariant', function() {
			$result = null;
			$variants = [
				[ '=ACTION_FILE' => 'bill' ],
				[ '=IS_CASH' => 'N' ],
				[],
			];
			$commonFilter = [
				'=ACTIVE' => 'Y',
				'=ENTITY_REGISTRY_TYPE' => Sale\Payment::getRegistryType(),
				'!=ACTION_FILE' => 'inner',
			];

			foreach ($variants as $variantFilter)
			{
				$query = Sale\PaySystem\Manager::getList([
					'select' => ['ID'],
					'filter' => $commonFilter + $variantFilter,
					'order' => ['SORT' => 'ASC', 'NAME' => 'ASC'],
					'limit' => 1,
				]);

				if ($row = $query->fetch())
				{
					$result = (int)$row['ID'];
					break;
				}
			}

			return $result;
		});
	}

	public function variants() : array
	{
		return $this->once('variants', function() {
			$result = [];

			$query = Sale\PaySystem\Manager::getList([
				'filter' => [
					'=ACTIVE' => 'Y',
					'=ENTITY_REGISTRY_TYPE' => Sale\Payment::getRegistryType(),
				],
				'order' => ['SORT' => 'ASC', 'NAME' => 'ASC'],
				'select' => ['ID', 'NAME']
			]);

			while ($row = $query->fetch())
			{
				$result[] = [
					'ID' => $row['ID'],
					'VALUE' => sprintf('[%s] %s', $row['ID'], $row['NAME']),
				];
			}

			return $result;
		});
	}
}