<?php
namespace Avito\Export\Feed\Source\Data;

class SourceSelect
{
	protected $map = [];

	public function isEmpty() : bool
	{
		return empty($this->map);
	}

	public function sources() : array
	{
		return array_keys($this->map);
	}

	public function fields(string $type) : array
	{
		return $this->map[$type] ?? [];
	}

	public function add(string $type, string $field) : void
	{
		if (!isset($this->map[$type]))
		{
			$this->map[$type] = [
				$field,
			];
		}
		else if (!in_array($field, $this->map[$type], true))
		{
			$this->map[$type][] = $field;
		}
	}

	public function clone() : self
	{
		$result = new self();
		$result->map = $this->map;

		return $result;
	}

	public function diff(self $other) : self
	{
		$result = new self();

		foreach ($this->sources() as $type)
		{
			$otherFields = array_flip($other->fields($type));

			foreach ($this->fields($type) as $field)
			{
				if (!isset($otherFields[$field]))
				{
					$result->add($type, $field);
				}
			}
		}

		return $result;
	}

	public function merge(self $other) : self
	{
		$result = $this->clone();

		foreach ($other->sources() as $type)
		{
			foreach($other->fields($type) as $field)
			{
				$result->add($type, $field);
			}
		}

		return $result;
	}
}