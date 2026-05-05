<?php
namespace Avito\Export\Trading\Entity\Sale;

use Avito\Export\Concerns;
use Bitrix\Sale;

class Property
{
	use Concerns\HasOnce;

	protected $environment;

	public function __construct(Container $environment)
	{
		$this->environment = $environment;
	}

	public function variants(?int $personTypeId) : array
	{
		if ($personTypeId === null || $personTypeId <= 0) { return []; }

		return $this->once('variants-' . $personTypeId, function() use ($personTypeId) {
			$result = [];

			$query = Sale\Internals\OrderPropsTable::getList([
				'select' => [ 'ID', 'NAME', 'CODE' ],
				'filter' => [
					'=PERSON_TYPE_ID' => $personTypeId,
					'=ACTIVE' => 'Y',
				],
				'order' => [
					'SORT' => 'asc',
					'ID' => 'asc',
				],
			]);

			while ($propertyRow = $query->fetch())
			{
				$result[] = [
					'ID' => $propertyRow['ID'],
					'CODE' => $propertyRow['CODE'],
					'VALUE' => $propertyRow['NAME'],
				];
			}

			return $result;
		});
	}
}