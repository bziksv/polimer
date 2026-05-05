<?php
namespace Avito\Export\Feed\Engine\Data;

use Avito\Export\Feed\Tag;
use Bitrix\Main;

class TagValues extends Main\Result
{
	protected $format;
	protected $values = [];
	protected $mixedTags = [
		Tag\Param::NAME,
		Tag\Characteristic::NAME,
	];

	public function remove(string $name) : void
	{
		if (isset($this->values[$name]) || array_key_exists($name, $this->values))
		{
			unset($this->values[$name]);
		}

		foreach ($this->mixedTags as $mixedTag)
		{
			if (!isset($this->values[$mixedTag]) || !is_array($this->values[$mixedTag])) { continue; }

			if (isset($this->values[$mixedTag][$name]) || array_key_exists($name, $this->values[$mixedTag]))
			{
				unset($this->values[$mixedTag][$name]);
			}
		}
	}

	public function getFew(array $names) : array
	{
		$result = [];

		foreach ($names as $name)
		{
			$value = $this->get($name);

			if ($value === null) { continue; }

			$result[$name] = $value;
		}

		return $result;
	}

	public function get(string $name)
	{
		return (
			$this->values[$name]
			?? $this->values[Tag\Characteristic::NAME][$name]
			?? $this->values[Tag\Param::NAME][$name]
			?? null
		);
	}

	public function set(string $name, $value) : void
	{
		if (isset($this->values[$name]) || array_key_exists($name, $this->values))
		{
			$this->values[$name] = $value;
			return;
		}

		$foundMixed = false;

		foreach ($this->mixedTags as $mixedTag)
		{
			if (!isset($this->values[$mixedTag]) || !is_array($this->values[$mixedTag])) { continue; }

			if (isset($this->values[$mixedTag][$name]) || array_key_exists($name, $this->values[$mixedTag]))
			{
				$foundMixed = true;
				$this->values[$mixedTag][$name] = $value;
				break;
			}
		}

		if (!$foundMixed)
		{
			$this->values[$name] = $value;
		}
	}

	public function getRaw(string $name)
	{
		return $this->values[$name] ?? null;
	}

	public function setRaw(string $name, $value) : void
	{
		$this->values[$name] = $value;
	}

	public function asArray() : array
	{
		return $this->values;
	}

	public function setFormat(Tag\Format $format) : void
	{
		$this->format = $format;
	}

	public function getFormat() : ?Tag\Format
	{
		return $this->format;
	}

	public function spawnTags(array $names) : void
	{
		$mixedTypes = [
			Tag\Characteristic::NAME,
			Tag\Param::NAME,
		];

		foreach ($names as $name)
		{
			if (isset($this->values[$name])) { continue; }

			foreach ($mixedTypes as $mixedType)
			{
				if (!isset($this->values[$mixedType][$name])) { continue; }

				$this->values[$name] = $this->values[$mixedType][$name];
				unset($this->values[$mixedType][$name]);
				break;
			}
		}
	}
}