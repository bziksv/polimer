<?php
namespace Avito\Export\Migration\V170;

use Avito\Export;
use Bitrix\Main;

/** @noinspection PhpUnused */
class InstallChat implements Export\Migration\Patch
{
	public function version() : string
	{
		return '1.7.0';
	}

	public function run() : void
	{
		$this->createTables();
		$this->activateChat();
		$this->bindCrm();
	}

	protected function createTables() : void
	{
		Export\DB\Controller::createTables([
			Export\Exchange\Setup\RepositoryTable::class,
			Export\Chat\Unread\MessageTable::class,
			Export\Trading\Entity\SaleCrm\Internals\WaitChatTable::class,
			Export\Trading\State\RepositoryTable::class,
			Export\Logger\Table::class,
		]);
	}

	protected function activateChat() : void
	{
		$query = Export\Exchange\Setup\RepositoryTable::getList();

		while ($exchange = $query->fetchObject())
		{
			try
			{
				/** @noinspection PhpUnusedLocalVariableInspection */
				$token = $exchange->settingsBridge()->commonSettings()->token();

				$exchange->setUseChat(true);
				$exchange->setChatSettings([]);
				$exchange->getChat()->activate();
				$exchange->save();
			}
			catch (Main\ArgumentException $exception)
			{
				$exchange->setUseChat(false);
				$exchange->setChatSettings([]);
				$exchange->save();
			}
		}
	}

	protected function bindCrm() : void
	{
		$query = Export\Exchange\Setup\RepositoryTable::getList([
			'filter' => [ '=USE_TRADING' => true ],
			'limit' => 1,
		]);

		if ($exchange = $query->fetchObject())
		{
			$trading = $exchange->getTrading();

			if ($trading === null) { return; }

			$environment = $trading->getEnvironment();

			if (!($environment instanceof Export\Trading\Entity\SaleCrm\Container)) { return; }

			foreach ($trading->getEnvironment()->listeners() as $listener)
			{
				$listener->install();
			}
		}
	}
}