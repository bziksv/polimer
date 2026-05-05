<?php
namespace Avito\Export\Feed\Source\ElementProperty;

use Avito\Export\Concerns;
use Avito\Export\Feed\Source\Field;
use Bitrix\Iblock;

class ElementField extends Field\EnumField implements Field\Autocompletable
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
		if (is_numeric($query))
		{
			$filter = [
				[
					'LOGIC' => 'OR',
					[ '%NAME' => $query ],
					[ '%ID' => $query ],
				],
			];
		}
		else
		{
			$filter = [
				'%NAME' => $query,
			];
		}

		return $this->queryValues([
			'filter' => $filter,
			'limit' => static::SUGGEST_LIMIT,
		]);
	}

	protected function queryValues(array $parameters = []) : array
	{
		$result = [];
		$linkIblockId = (int)$this->parameter('LINK_IBLOCK_ID');

		$parameters += [
			'select' => [ 'ID', 'NAME' ],
			'filter' => [],
			'order' => [
				'SORT' => 'asc',
				'ID' => 'asc',
			],
			'limit' => static::AUTOCOMPLETE_THRESHOLD,
		];

		if ($linkIblockId > 0)
		{
			$parameters['filter'] = [
				'=IBLOCK_ID' => $this->parameter('LINK_IBLOCK_ID'),
				$parameters['filter'],
			];
		}

		$query = Iblock\ElementTable::getList($parameters);

		while ($row = $query->fetch())
		{
			$result[] = [
				'ID' => $row['ID'],
				'VALUE' => sprintf('[%s] %s', $row['ID'], $row['NAME']),
			];
		}

		return $result;
	}
}