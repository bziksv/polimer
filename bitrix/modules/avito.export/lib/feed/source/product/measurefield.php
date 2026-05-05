<?php
namespace Avito\Export\Feed\Source\Product;

use Avito\Export\Concerns;
use Avito\Export\Feed\Source\Field;

class MeasureField extends Field\EnumField
{
	use Concerns\HasLocale;
	use Concerns\HasOnce;

	public function variants() : array
	{
		return $this->once('variants', function() {
			return $this->queryValues();
		});
	}

	protected function queryValues() : array
	{
		$result = [];

		$query = \CCatalogMeasure::getList([], [], false, false, ['ID', 'MEASURE_TITLE']);

		while ($row = $query->Fetch())
		{
			$result[] = [
				'ID' => $row['ID'],
				'VALUE' => sprintf('[%s] %s', $row['ID'], $row['MEASURE_TITLE']),
			];
		}

		return $result;
	}
}