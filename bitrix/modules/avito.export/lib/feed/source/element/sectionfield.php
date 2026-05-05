<?php
namespace Avito\Export\Feed\Source\Element;

use Avito\Export\Concerns;
use Avito\Export\Feed\Source\Field;
use Bitrix\Iblock;

class SectionField extends Field\EnumField implements Field\Autocompletable
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
		return (count($this->variants()) >= static::AUTOCOMPLETE_THRESHOLD);
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
					[ '%NAME' => $query ],
					[ '%ID' => $query ],
				],
			],
			'limit' => static::SUGGEST_LIMIT,
		]);
	}

	protected function queryValues(array $parameters = []) : array
	{
		$result = [];

		$parameters += [
			'select' => [ 'ID', 'NAME', 'DEPTH_LEVEL' ],
			'filter' => [],
			'order' => [ 'LEFT_MARGIN' => 'asc' ],
			'limit' => static::AUTOCOMPLETE_THRESHOLD,
		];
		$parameters['filter'] =	[
			'=IBLOCK_ID' => $this->parameter('IBLOCK_ID'),
			$parameters['filter']
		];

		$query = Iblock\SectionTable::getList($parameters);

		while ($section = $query->fetch())
		{
			$prefix = ($section['DEPTH_LEVEL'] > 1 ? str_repeat('.', $section['DEPTH_LEVEL'] - 1) : '');

			$result[] = [
				'ID' => $section['ID'],
				'VALUE' => $prefix . $section['NAME'],
			];
		}

		return $result;
	}

	public function filter(string $compare, $value) : array
	{
		if ($compare === Field\Condition::AT_LIST)
		{
			$result = parent::filter($compare, $value);
			$result['INCLUDE_SUBSECTIONS'] = 'Y';
		}
		else if ($compare === Field\Condition::NOT_AT_LIST)
		{
			$sectionMargins = $this->loadSectionMargins($value);

			if ($this->sectionsHaveChildren($sectionMargins))
			{
				$result = [
					'!SUBSECTION' => $sectionMargins
				];
			}
			else
			{
				$result = [
					'!' . $this->filterName() => $value,
				];
			}
		}
		else
		{
			$result = parent::filter($compare, $value);
		}

		return $result;
	}

	protected function loadSectionMargins($sectionIds) : array
	{
		if (!is_array($sectionIds) || empty($sectionIds)) { return []; }

		$result = [];

		$querySections = Iblock\SectionTable::getList([
			'filter' => [
				'=ID' => $sectionIds
			],
			'select' => ['ID', 'LEFT_MARGIN', 'RIGHT_MARGIN']
		]);

		while ($section = $querySections->fetch())
		{
			$result[] = [
				(int)$section['LEFT_MARGIN'],
				(int)$section['RIGHT_MARGIN']
			];
		}

		return $result;
	}

	protected function sectionsHaveChildren(array $sectionMargins) : bool
	{
		foreach ($sectionMargins as $sectionMargin)
		{
			if ($sectionMargin[1] > $sectionMargin[0] + 1)
			{
				return true;
			}
		}

		return false;
	}
}
