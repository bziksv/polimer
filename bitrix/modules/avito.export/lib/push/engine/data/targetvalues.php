<?php
namespace Avito\Export\Push\Engine\Data;

use Avito\Export\Glossary;

class TargetValues implements \IteratorAggregate, \Countable
{
	private $values;

	public function __construct(array $values = [])
	{
		$this->values = $values;
	}

	public function getIterator() : \ArrayIterator
	{
		return new \ArrayIterator($this->values);
	}

	public function toArray() : array
	{
		return $this->values;
	}

	public function count() : int
	{
		return count($this->values);
	}

	public function add(string $type, $value) : void
	{
		if ($type === Glossary::ENTITY_STOCKS)
		{
			$this->setStocks($this->getStocks() + $value);
		}
		else
		{
			$this->set($type, $value);
		}
	}

	public function set(string $type, $value) : void
	{
		$this->values[$type] = $value;
	}

	public function get(string $type)
	{
		return $this->values[$type] ?? null;
	}

	public function getStocks()
	{
		return $this->get(Glossary::ENTITY_STOCKS);
	}

	public function setStocks(float $value) : void
	{
		$this->set(Glossary::ENTITY_STOCKS, $value);
	}

	public function getPrice()
	{
		return $this->get(Glossary::ENTITY_PRICE);
	}

	public function setPrice(float $value) : void
	{
		$this->set(Glossary::ENTITY_PRICE, $value);
	}
}