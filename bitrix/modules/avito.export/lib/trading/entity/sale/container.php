<?php
namespace Avito\Export\Trading\Entity\Sale;

use Bitrix\Main;

class Container
{
	protected $instances = [];

	public function load() : void
	{
		foreach ($this->requiredModules() as $module)
		{
			if (!Main\Loader::includeModule($module))
			{
				throw new Main\NotSupportedException(sprintf('module %s is required', $module));
			}
		}
	}

	protected function requiredModules() : array
	{
		return [ 'sale', 'catalog', 'currency' ];
	}

	public function orderRegistry() : OrderRegistry
	{
		return $this->instance(OrderRegistry::class);
	}

	public function platform() : Platform
	{
		return $this->instance(Platform::class);
	}

	public function currency() : Currency
	{
		return $this->instance(Currency::class);
	}

	public function anonymousUser() : AnonymousUser
	{
		return $this->instance(AnonymousUser::class);
	}

	public function buyerProfile() : BuyerProfile
	{
		return $this->instance(BuyerProfile::class);
	}

	public function personType() : PersonType
	{
		return $this->instance(PersonType::class);
	}

	public function delivery() : Delivery
	{
		return $this->instance(Delivery::class);
	}

	public function paySystem() : PaySystem
	{
		return $this->instance(PaySystem::class);
	}

	public function property() : Property
	{
		return $this->instance(Property::class);
	}

	public function status() : Status
	{
		return $this->instance(Status::class);
	}

	public function adminExtension() : AdminExtension
	{
		return $this->instance(AdminExtension::class);
	}

	/** @return EventHandler[] */
	public function listeners() : array
	{
		return [
			$this->instance(Listener::class),
			$this->instance(AdminExtension::class),
		];
	}

	public function product() : Product
	{
		return $this->instance(Product::class);
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