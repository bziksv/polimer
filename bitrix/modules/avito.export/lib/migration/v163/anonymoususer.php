<?php
namespace Avito\Export\Migration\V163;

use Bitrix\Main;
use Bitrix\Sale;
use Avito\Export;

/** @noinspection PhpUnused */
class AnonymousUser implements Export\Migration\Patch
{
	use Export\Concerns\HasLocale;

	public function version() : string
	{
		return '1.6.3';
	}

	public function run() : void
	{
		$trading = $this->queryTrading();

		if ($trading === null) { return; }

		$environment = $trading->getEnvironment();
		$anonymousUser = $environment->anonymousUser();

		if ($anonymousUser->id() !== null) { return; }

		$sites = $trading->getExchange()->fillFeed()->allSites();
		$installUser = $anonymousUser->install((string)reset($sites));

		if (!$installUser->isSuccess() || !Main\Loader::includeModule('sale')) { return; }

		/** @noinspection PhpCastIsUnnecessaryInspection */
		$saleUserId = (int)\CSaleUser::GetAnonymousUserID();
		$anonymousUserId = $installUser->getId();

		if ($saleUserId <= 0) { return; }

		$this->migrateBuyerProfile($saleUserId, $anonymousUserId);
	}

	protected function queryTrading() : ?Export\Trading\Setup\Model
	{
		$result = null;

		$queryExchange = Export\Exchange\Setup\RepositoryTable::getList([
			'filter' => [ '=USE_TRADING' => true ],
			'limit' => 1,
		]);

		if ($exchange = $queryExchange->fetchObject())
		{
			$result = $exchange->getTrading();
		}

		return $result;
	}

	protected function migrateBuyerProfile(int $userFrom, int $userTo) : void
	{
		$query = Sale\Internals\UserPropsTable::getList([
			'select' => [ 'ID', 'NAME' ],
			'filter' => [ '=USER_ID' => $userFrom ],
		]);

		while ($profile = $query->fetch())
		{
			if (!$this->matchBuyerProfile($profile)) { continue; }

			Sale\Internals\UserPropsTable::update($profile['ID'], [
				'USER_ID' => $userTo,
			]);
		}
	}

	protected function matchBuyerProfile(array $profile) : bool
	{
		if ($profile['NAME'] === self::getLocale('BUYER_PROFILE')) { return true; }

		$values = Sale\OrderUserProperties::getProfileValues($profile['ID']);

		if (!is_array($values)) { return false; }

		$result = false;

		foreach ($values as $value)
		{
			if (mb_stripos($value, self::getLocale('VALUE_MARKER')) !== false)
			{
				$result = true;
				break;
			}
		}

		return $result;
	}
}