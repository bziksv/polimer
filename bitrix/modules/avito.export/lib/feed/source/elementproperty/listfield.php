<?php
namespace Avito\Export\Feed\Source\ElementProperty;

use Avito\Export\Concerns;
use Avito\Export\Feed\Source\Field;
use Bitrix\Iblock;

class ListField extends Field\EnumField implements Field\Autocompletable
{
	use Concerns\HasOnce;

	public function variants() : array
	{
		return $this->once('variants', function() {
			return $this->queryValues();
		});
	}

	public function autocomplete() : bool
	{
		return count($this->variants()) >= static::AUTOCOMPLETE_THRESHOLD;
	}

	public function display(array $values) : array
	{
		if (empty($values)) { return []; }

		return $this->queryValues([
			'filter' => [ '=ID' => $values ],
			'limit' => count($values),
		]);
	}

	public function suggest(string $query) : array
	{
		return $this->queryValues([
			'filter' => [
				[
					'LOGIC' => 'OR',
					[ '%VALUE' => $query ],
					[ '%XML_ID' => $query ],
					[ '%ID' => $query ],
				],
			],
			'limit' => static::SUGGEST_LIMIT,
		]);
	}

	protected function queryValues(array $parameters = []) : array
	{
		$parameters += [
			'select' => [ 'ID', 'VALUE' ],
			'filter' => [],
			'order' => [
				'SORT' => 'asc',
				'ID' => 'asc',
			],
			'limit' => static::AUTOCOMPLETE_THRESHOLD,
		];
		$parameters['filter'] = [
			'=PROPERTY_ID' => $this->id(),
			$parameters['filter'],
		];

		$query = Iblock\PropertyEnumerationTable::getList($parameters);

		return $query->fetchAll();
	}
}