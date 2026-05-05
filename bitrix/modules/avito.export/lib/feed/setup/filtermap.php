<?php
namespace Avito\Export\Feed\Setup;

use Avito\Export\Concerns;

class FilterMap
{
	use Concerns\HasOnce;

	protected $map;

	public function __construct(array $map)
	{
		$this->map = $this->parse($map);
	}

	public function all() : array
	{
		return $this->map;
	}

	public function sources() : array
	{
		$result = [];

		foreach ($this->groups() as $group)
		{
			foreach ($group['ITEMS'] as $type => $conditions)
			{
				if (!isset($result[$type]))
				{
					$result[$type] = [];
				}

				array_push($result[$type], ...$conditions);
			}
		}

		return $result;
	}

	public function groups() : array
	{
		$result = [];
		$group = [
			'LOGIC' => 'AND',
			'ITEMS' => [],
		];
		$groupGlue = 'AND';
		$previous = null;

		foreach ($this->map as $link)
		{
			$glue = ($link['GLUE'] ?? 'AND');

			if ($groupGlue !== $glue)
			{
				$result[] = $group;

				$group = [
					'LOGIC' => $glue,
					'ITEMS' => [],
				];
				$groupGlue = $glue;
			}

			$group = $this->groupAdd($group, $previous);

			if ($glue === 'OR')
			{
				$group = $this->groupAdd($group, $link);
				$previous = null;
			}
			else
			{
				$previous = $link;
			}
		}

		$group = $this->groupAdd($group, $previous);
		$result[] = $group;

		return array_filter($result, static function(array $group) { return !empty($group['ITEMS']); });
	}

	protected function groupAdd(array $group, array $link = null) : array
	{
		if ($link === null) { return $group; }

		if (!isset($group['ITEMS'][$link['TYPE']]))
		{
			$group['ITEMS'][$link['TYPE']] = [];
		}

		$group['ITEMS'][$link['TYPE']][] = [
			'FIELD' => $link['FIELD'],
			'COMPARE' => $link['COMPARE'],
			'VALUE' => $link['VALUE'],
		];

		return $group;
	}

	protected function parse(array $map) : array
	{
		$result = [];

		foreach ($map as $value)
		{
			if (!preg_match('/^([A-Z_]+)\.([\w.]+)$/', $value['FIELD'], $matches)) { continue; }

			if (!isset($value['VALUE'])) { continue; }

			if (is_string($value['VALUE']) && trim($value['VALUE']) === '') { continue; }

			$value['TYPE'] = $matches[1];
			$value['FIELD'] = $matches[2];

			$result[] = $value;
		}

		return $result;
	}
}