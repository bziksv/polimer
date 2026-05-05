<?php
namespace Avito\Export\Trading\Entity\SaleCrm;

use Avito\Export\Trading\Entity\Sale as TradingSale;

class Container extends TradingSale\Container
{
	protected function requiredModules() : array
	{
		return array_merge(parent::requiredModules(), [ 'crm' ]);
	}

	public function adminExtension() : TradingSale\AdminExtension
	{
		return $this->instance(AdminExtension::class);
	}

	public function listeners() : array
	{
		return [
			$this->instance(Listener::class),
			$this->instance(TradingSale\AdminExtension::class),
			$this->instance(Compatible\TradeBindingPreserver::class),
			$this->instance(AdminExtension::class),
			$this->instance(ChatBinder::class),
		];
	}

	public function contactRegistry() : ContactRegistry
	{
		return $this->instance(ContactRegistry::class);
	}

	public function orderRegistry() : TradingSale\OrderRegistry
	{
		return $this->instance(OrderRegistry::class);
	}

	public function chatRegistry() : ChatRegistry
	{
		return $this->instance(ChatRegistry::class);
	}
}