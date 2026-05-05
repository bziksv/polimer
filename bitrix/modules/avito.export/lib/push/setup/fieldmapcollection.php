<?php
namespace Avito\Export\Push\Setup;

use Avito\Export\Assert;
use Avito\Export\Concerns;

class FieldMapCollection
{
	use Concerns\HasCollection;

	public static function fromSettings(array $settings) : FieldMapCollection
	{
		$collection = [];

		foreach ($settings as $target => $map)
		{
			if (!is_array($map)) { continue; }

			foreach ($map as $iblockId => $fields)
			{
				foreach ((array)$fields as $field)
				{
					if (is_array($field))
					{
						if (!isset($field['TYPE'], $field['FIELD'])) { continue; }

						$fieldValue = [
							'TARGET' => $target,
							'TYPE' => $field['TYPE'],
							'FIELD' => $field['FIELD'],
						];
					}
					else
					{
						$parts = explode('.', (string)$field, 2);

						if ($parts[0] === '' || count($parts) !== 2) { continue; }

						$fieldValue = [
							'TARGET' => $target,
							'TYPE' => $parts[0],
							'FIELD' => $parts[1],
						];
					}

					if (!isset($collection[$iblockId]))
					{
						$collection[$iblockId] = [];
					}

					$collection[$iblockId][] = $fieldValue;
				}
			}
		}

		return new static($collection);
	}

	public function __construct(array $collection)
	{
		$this->collection = $this->compile($collection);
	}

	public function byIblockId(int $iblockId) : FieldMap
	{
		$fieldMap = $this->collection[$iblockId] ?? null;

		Assert::notNull($fieldMap, '$this->collection[$iblockId]');

		return $fieldMap;
	}

	protected function compile(array $collection) : array
	{
		$result = [];

		foreach ($collection as $iblockId => $map)
		{
			$result[$iblockId] = new FieldMap($map);
		}

		return $result;
	}
}