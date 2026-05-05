<?php

namespace Avito\Export\Trading\Entity\SaleCrm;

use Bitrix\Main;
use Avito\Export\Trading\Entity as TradingEntity;

class Listener extends TradingEntity\Sale\Listener
{
	protected static function isAdminRequest() : bool
	{
		$request = Main\Application::getInstance()->getContext()->getRequest();
		$path = $request->getRequestedPage();

		if (
			preg_match('#/crm\.(order|deal)\..+?/#', $path) // is components namespace crm.order or crm.deal
			&& preg_match('#ajax\.php$#', $path) // ajax page
		)
		{
			$result = true;
		}
		else if (static::isAdminController($request))
		{
			$result = true;
		}
		else
		{
			$result = parent::isAdminRequest();
		}

		return $result;
	}

	protected static function isAdminController(Main\Request $request) : bool
	{
		return (
			$request->getRequestedPage() === '/bitrix/services/main/ajax.php'
			&& is_string($request->get('action'))
			&& preg_match('#^crm\.(order|deal|api)\.#', $request->get('action'))
		);
	}

	public function uninstall() : void
	{
		$this->uninstallSale();
		parent::uninstall();
	}

	protected function uninstallSale() : void
	{
		$saleListener = new TradingEntity\Sale\Listener($this->environment);
		$saleListener->uninstall();
	}
}