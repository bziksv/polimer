<?php
namespace Avito\Export\Push\Setup;

use Avito\Export\Concerns;
use Avito\Export\Feed\Source;

class FieldMap
{
	use Concerns\HasOnce;

	protected $map;

	public function __construct(array $map)
	{
		$this->map = $map;
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
		foreach ($this->map as $one)
		{
			if ($one['TARGET'] === $name)
			{
				return $one;
			}
		}
		return null;
	}
}