<?php
namespace Avito\Export\Api\OAuth\RefreshToken;

use Avito\Export\Agent as ModuleAgent;
use Avito\Export\Concerns;
use Avito\Export\Exchange;
use Avito\Export\Logger;
use Avito\Export\Api\OAuth;
use Avito\Export\Glossary;
use Bitrix\Main;

class Agent extends ModuleAgent\Base
{
	use Concerns\HasLocale;

	public static function install(OAuth\Token $token) : void
	{
		/** @noinspection PhpCastIsUnnecessaryInspection */
		static::register([
			'method' => 'refresh',
			'next_exec' => $token->getExpires(),
			'arguments' => [ (string)$token->getClientId(), (int)$token->getServiceId() ],
		]);
	}

	public static function refresh(string $clientId, int $serviceId) : bool
	{
		$token = static::searchToken($clientId, $serviceId);

		if ($token === null) { return false; }

		return static::processToken($token);
	}

	protected static function searchToken(string $clientId, int $serviceId) : ?OAuth\Token
	{
		$query = OAuth\TokenTable::getList([
			'filter' => [
				'=CLIENT_ID' => $clientId,
				'=SERVICE_ID' => $serviceId,
			],
		]);

		return $query->fetchObject();
	}

	protected static function processToken(OAuth\Token $token) : bool
	{
		$controller = new Controller($token);

		try
		{
			$newToken = $controller->run();
			$setup = $controller->usedSetup();

			static::clearLog($setup);
			static::nextPeriod($newToken);
		}
		catch (\Throwable $exception)
		{
			$setup = $controller->usedSetup();

			if ($setup === null) { return false; }

			static::log($token, $setup, $exception);
			static::repeatPeriod();
		}

		return true;
	}

	protected static function clearLog(Exchange\Setup\Model $exchange = null) : void
	{
		if ($exchange === null) { return; }

		$logger = new Logger\Logger(Glossary::SERVICE_PUSH, $exchange->getId());
		$logger->removeAll(Glossary::ENTITY_TOKEN);
	}

	protected static function nextPeriod(OAuth\Token $token) : void
	{
		global $pPERIOD;

		$expires = $token->getExpires();
		$now = new Main\Type\DateTime();
		$diff = $expires->getTimestamp() - $now->getTimestamp();
		$min = 60 * 60; // 1 hour

		$pPERIOD = max($min, $diff);
	}

	protected static function log(OAuth\Token $token, Exchange\Setup\Model $exchange, \Throwable $exception) : void
	{
		$logger = new Logger\Logger(Glossary::SERVICE_PUSH, $exchange->getId());
		$logger->warning(self::getLocale('ERROR', [
			'#MESSAGE#' => $exception->getMessage(),
		]), [
			'ENTITY_TYPE' => Glossary::ENTITY_TOKEN,
			'ENTITY_ID' => $token->getServiceId(),
		]);
	}

	protected static function repeatPeriod() : void
	{
		global $pPERIOD;

		$pPERIOD = 10 * 60; // 10 minutes
	}
}