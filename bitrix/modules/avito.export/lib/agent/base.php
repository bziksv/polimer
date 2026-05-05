<?php

namespace Avito\Export\Agent;

use Bitrix\Main;

abstract class Base
{
	public static function isRegistered(array $agentParams = null) : bool
	{
		$className = static::getClassName();

		$agentParams = !isset($agentParams)
			? static::getDefaultParams()
			: array_merge(static::getDefaultParams(), $agentParams);

		return Controller::isRegistered($className, $agentParams);
	}

	/**
	 * Добавляем агент
	 *
	 * @param $agentParams array|null параметры агента, ключи:
	 *                     method => string # название метода (необязательно)
	 *                     arguments => array # параметры вызова метода (необязательно)
	 *                     interval => integer, # интервал запуска, в секундах (необязательно)
	 *                     sort => integer, # сортировка, по-умолчанию — 100 (необязательно)
	 *                     next_exec => string, # дата в формате Y-m-d H:i:s (необязательно)
	 *
	 * @throws Main\NotImplementedException
	 * @throws Main\SystemException
	 * */
	public static function register(array $agentParams = null) : void
	{
		$className = static::getClassName();

		$agentParams = !isset($agentParams)
			? static::getDefaultParams()
			: array_merge(static::getDefaultParams(), $agentParams);

		Controller::register($className, $agentParams);
	}

	public static function getClassName() : string
	{
		return '\\' . static::class;
	}

	/**
	 * @return array описания агента для выполнения по умолчанию (метод run), ключи:
	 *               method => string # название метода (необязательно)
	 *               arguments => array # параметры вызова метода (необязательно)
	 *               interval => integer, # интервал запуска, в секундах (необязательно)
	 *               sort => integer, # сортировка, по-умолчанию — 100 (необязательно)
	 *               next_exec => string, # дата в формате Y-m-d H:i:s (необязательно)
	 * */

	public static function getDefaultParams() : array
	{
		return [];
	}

	public static function unregister(array $agentParams = null) : void
	{
		$className = static::getClassName();

		$agentParams = !isset($agentParams)
			? static::getDefaultParams()
			: array_merge(static::getDefaultParams(), $agentParams);

		Controller::unregister($className, $agentParams);
	}

	public static function callAgent(string $method, array $arguments = null) : ?string
	{
		$className = static::getClassName();
		$result = '';

		if (is_array($arguments))
		{
			$callResult = call_user_func_array(array($className, $method), $arguments);
		}
		else
		{
			$callResult = call_user_func(array($className, $method));
		}

		if ($callResult !== false)
		{
			if (is_array($callResult))
			{
				$arguments = $callResult;
			}

			$result = Controller::getAgentCall($className, $method, $arguments);
		}

		return $result;
	}

	/**
	 * @noinspection PhpMissingReturnTypeInspection
	 * @noinspection ReturnTypeCanBeDeclaredInspection
	 */
	public static function run()
	{
		return false;
	}
}
