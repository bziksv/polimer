<?php
namespace Avito\Export\Components;

/** @noinspection PhpUnused */
class AdminPropertyCategory extends \CBitrixComponent
{
	public function executeComponent()
	{
		$this->includeComponentTemplate();

		return $this->arResult['HTML'];
	}
}