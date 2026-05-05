<?php
namespace Avito\Export\Components;

/** @noinspection PhpUnused */
class AdminPropertyCharacteristic extends \CBitrixComponent
{
	public function executeComponent()
	{
		$template = $this->arParams['MULTIPLE'] === 'Y' ? 'multiple' : 'single';

		$this->includeComponentTemplate($template);

		return $this->arResult['HTML'];
	}
}