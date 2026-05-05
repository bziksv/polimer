<?php
namespace Avito\Export\Feed\Setup;

use Avito\Export\Concerns;
use Avito\Export\Feed\Source;

class TagMap
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

	public function select() : Source\Data\SourceSelect
	{
		return $this->once('sources', function() {
			$result = new Source\Data\SourceSelect();

			foreach ($this->map as $link)
			{
				if (!isset($link['TYPE'], $link['FIELD'])) { continue; }

				$result->add($link['TYPE'], $link['FIELD']);
			}

			return $result;
		});
	}

	public function one(string $name) : ?array
	{
		$result = null;

		foreach ($this->map as $one)
		{
			if ($one['CODE'] === $name)
			{
				$result = $one;
				break;
			}
		}

		return $result;
	}

	protected function parse(array $map) : array
	{
		$result = [];

		foreach ($map as $value)
		{
			$behavior = $value['BEHAVIOR'] ?? null;

			if ($behavior === 'TEMPLATE')
			{
				$value['TYPE'] = Source\Registry::TEMPLATE;
				$value['FIELD'] = $value['VALUE'];
			}
			else if ($behavior === 'TEXT')
			{
				$value['TEXT'] = $value['VALUE'];
			}
			else if (preg_match('/^([A-Z_]+)\.([\w.]+)$/', $value['VALUE'], $matches))
			{
				$value['TYPE'] = $matches[1];
				$value['FIELD'] = $matches[2];
			}
			else
			{
				$value['TEXT'] = $value['VALUE'];
			}

			$result[] = array_diff_key($value, ['VALUE' => true, 'BEHAVIOR' => true]);
		}

		return $result;
	}
}