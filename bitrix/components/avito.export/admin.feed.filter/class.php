<?php

namespace Avito\Export\Components;

/** @noinspection PhpUnused */
class AdminFeedFilter extends \CBitrixComponent
{
	public function executeComponent() : void
	{
		$this->includeComponentTemplate();
	}

	protected function listKeysSignedParameters() : array
	{
		return [
			'IBLOCK_ID',
			'SITE_ID',
		];
	}
}