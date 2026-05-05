<?php
namespace Avito\Export\Feed\Source\Field;

abstract class Field
{
	protected $parameters;

	public function __construct(array $parameters = [])
	{
		$this->parameters = $parameters + $this->defaults();
	}

	public function copy(array $parameters = []) : Field
	{
		$newParameters = $parameters + $this->parameters;

		return new static($newParameters);
	}

	public function id() : string
	{
		return (string)$this->parameter('ID');
	}

	public function type() : string
	{
		return $this->parameter('TYPE', 'S');
	}

	public function name() : string
	{
		return $this->parameter('NAME', $this->id());
	}

	public function filterable() : bool
	{
		return (bool)$this->parameter('FILTERABLE', true);
	}

	public function selectable() : bool
	{
		return (bool)$this->parameter('SELECTABLE', true);
	}

	public function conditions() : array
	{
		return [
			Condition::EQUAL,
			Condition::NOT_EQUAL,
			Condition::MORE_THEN,
			Condition::LESS_THEN,
			Condition::LESS_OR_EQUAL,
			Condition::MORE_OR_EQUAL,
			Condition::AT_LIST,
			Condition::NOT_AT_LIST,
		];
	}

	public function filter(string $compare, $value) : array
	{
		$compareRule = Condition::some($compare);

		return [
			$compareRule['QUERY'] . $this->filterName() => $value,
		];
	}

	protected function filterName() : string
	{
		$filterable = $this->parameter('FILTERABLE');

		return is_string($filterable) ? $filterable : $this->id();
	}

	protected function defaults() : array
	{
		return [];
	}

	public function parameter($name, $default = null)
	{
		return $this->parameters[$name] ?? $default;
	}
}