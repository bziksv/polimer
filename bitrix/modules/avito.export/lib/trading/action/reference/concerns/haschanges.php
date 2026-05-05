<?php
namespace Avito\Export\Trading\Action\Reference\Concerns;

use Bitrix\Main;

trait HasChanges
{
	protected $needSave = false;

	protected function testChanged(Main\Result $result) : void
	{
		if (!$result->isSuccess()) { return; }

		$data = $result->getData();

		if (!isset($data['CHANGED']) || !empty($data['CHANGED']))
		{
			$this->needSave = true;
		}
	}
}