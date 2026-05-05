<?php
namespace Avito\Export\Trading\Service;

class Container
{
	protected $instances = [];

	public function status() : Status
	{
		return $this->instance(Status::class);
	}

	public function delivery() : Delivery
	{
		return $this->instance(Delivery::class);
	}

	public function discount() : Discount
	{
		return $this->instance(Discount::class);
	}

	public function urlManager() : UrlManager
	{
		return $this->instance(UrlManager::class);
	}

	/**
	 * @template T
	 * @param class-string<T> $className
	 * @return T
	 */
	protected function instance(string $className)
	{
		if (!isset($this->instances[$className]))
		{
			$this->instances[$className] = new $className($this);
		}

		return $this->instances[$className];
	}
}