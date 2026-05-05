<?php
namespace Avito\Export\Migration\V188;

use Avito\Export\Config;
use Avito\Export\Migration;
use Avito\Export\Glossary;
use Avito\Export\Logger;
use Avito\Export\Psr;

/** @noinspection PhpUnused */
class RestartOrderStatus implements Migration\Patch
{
	public function version() : string
	{
		return '1.8.8';
	}

	public function run() : void
	{
		foreach ($this->fails() as $id)
		{
			$this->restart($id);
		}
	}

	private function fails() : array
	{
		$query = Logger\Table::getList([
			'filter' => [
				'=SETUP_TYPE' => Glossary::SERVICE_TRADING,
				'=LEVEL' => Psr\Logger\LogLevel::ERROR,
			],
			'group' => [ 'SETUP_ID' ],
		]);

		return array_column($query->fetchAll(), 'SETUP_ID');
	}

	private function restart(int $id) : void
	{
		Config::removeOption('trading_order_status_last_' . $id);
	}
}