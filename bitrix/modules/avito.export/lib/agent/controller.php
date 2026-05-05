<?php

namespace Avito\Export\Agent;

use Avito\Export\Config;
use Avito\Export\Utils\Tools;
use Bitrix\Main;
use CAgent;

class Controller
{
	public const SEARCH_RULE_STRICT = 'strict';
	public const SEARCH_RULE_SOFT = 'soft';

	public static function isRegistered(string $className, ?array $agentParams) : bool
	{
		$agentDescription = static::getAgentDescription($className, $agentParams);
		$searchRule = $agentParams['search'] ?? static::SEARCH_RULE_STRICT;
		$registeredAgent = static::getRegisteredAgent($agentDescription, $searchRule);

		return $registeredAgent !== null;
	}

	public static function register(string $className, ?array $agentParams) : void
	{
		$agentDescription = static::getAgentDescription($className, $agentParams);
		$searchRule = $agentParams['search'] ?? static::SEARCH_RULE_STRICT;
		$registeredAgent = static::getRegisteredAgent($agentDescription, $searchRule);

		static::saveAgent($agentDescription, $registeredAgent);
	}

	protected static function getAgentDescription(string $className, ?array $agentParams) : array
	{
		$method = $agentParams['method'] ?? 'run';

		if (!method_exists($className, $method))
		{
			throw new Main\NotImplementedException('Method ' . $method . ' not defined in ' . $className
				. ' and cannot be registered as agent');
		}

		$agentFnCall = static::getAgentCall($className, $method, $agentParams['arguments'] ?? null);

		return array(
			'name' => $agentFnCall,
			'sort' => isset($agentParams['sort']) ? (int)$agentParams['sort'] : 100,
			'interval' => isset($agentParams['interval']) ? (int)$agentParams['interval'] : 86400,
			'next_exec' => $agentParams['next_exec'] ?? '',
		);
	}

	/** ¬озвращает строку дл€ вызова метода callAgent класса через eval */
	public static function getAgentCall(string $className, string $method, array $arguments = null) : string
	{
		return static::getFunctionCall(
			$className,
			'callAgent',
			isset($arguments) ? array($method, $arguments) : array($method)
		);
	}

	protected static function getFunctionCall(string $className, string $method, array $arguments = null) : string
	{
		$argumentsString = '';

		if (is_array($arguments))
		{
			$isFirstArgument = true;

			foreach ($arguments as $argument)
			{
				if (!$isFirstArgument)
				{
					$argumentsString .= ', ';
				}

				$argumentsString .= var_export($argument, true);

				$isFirstArgument = false;
			}
		}

		return $className . '::' . $method . '(' . $argumentsString . ');';
	}

	protected static function getRegisteredAgent(array $agentDescription, string $searchRule = self::SEARCH_RULE_STRICT) : ?array
	{
		$result = null;
		$variants = array_unique([
			$agentDescription['name'],
			str_replace(PHP_EOL, '', $agentDescription['name']), // new line removed after edit agent in admin form
		]);

		if ($searchRule === static::SEARCH_RULE_SOFT)
		{
			foreach ($variants as &$variant)
			{
				$variant = preg_replace_callback('/::callAgent\((["\']\w+?["\'])(?:(, array\s*\(.*)(\)\s*))?\)/s', static function ($matches) {
					return isset($matches[2], $matches[3])
						? '::callAgent(' . $matches[1] . $matches[2] . '%' . $matches[3] . ')'
						: '::callAgent(' . $matches[1] . '%)';
				}, $variant);
			}
			unset($variant);
		}

		foreach ($variants as $variant)
		{
			$query = CAgent::GetList([], [
				'NAME' => $variant
			]);

			if ($row = $query->Fetch())
			{
				$result = $row;
				break;
			}
		}

		return $result;
	}

	protected static function saveAgent(array $agent, ?array $registeredAgent) : void
	{
		global $APPLICATION;

		$agentData = array(
			'NAME' => $agent['name'],
			'MODULE_ID' => Config::getModuleName(),
			'SORT' => $agent['sort'],
			'ACTIVE' => 'Y',
			'AGENT_INTERVAL' => $agent['interval'],
			'IS_PERIOD' => 'N',
			'USER_ID' => 0,
		);

		if (!empty($agent['next_exec']))
		{
			$agentData['NEXT_EXEC'] = $agent['next_exec'] instanceof Main\Type\DateTime ?
				ConvertTimeStamp($agent['next_exec']->getTimestamp(), 'FULL') : $agent['next_exec'];
		}

		if (!isset($registeredAgent)) // добавл€ем агент, если отсутствует
		{
			$saveResult = CAgent::Add($agentData);
		}
		else
		{
			$saveResult = CAgent::Update($registeredAgent['ID'], $agentData);
		}

		if (!$saveResult)
		{
			$exception = $APPLICATION->GetException();

			throw new Main\SystemException('agent ' . $agent['name'] . ' register error' . ($exception ?
					': ' . $exception->GetString() : ''));
		}
	}

	public static function unregister(string $className, ?array $agentParams) : void
	{
		$agentDescription = static::getAgentDescription($className, $agentParams);
		$searchRule = $agentParams['search'] ?? static::SEARCH_RULE_STRICT;
		$previousId = null;

		do
		{
			$registeredAgent = static::getRegisteredAgent($agentDescription, $searchRule);

			if ($registeredAgent === null) { break; }

			if ($previousId === $registeredAgent['ID'])
			{
				throw new Main\SystemException(sprintf('cant delete agent with id %s', $previousId));
			}

			static::deleteAgent($registeredAgent);
			$previousId = $registeredAgent['ID'];

			if ($searchRule !== static::SEARCH_RULE_SOFT) { break; }
		}
		while (true);
	}

	protected static function deleteAgent(array $registeredRow) : void
	{
		$deleteResult = CAgent::Delete($registeredRow['ID']);

		if (!$deleteResult)
		{
			throw new Main\SystemException('agent ' . $registeredRow['NAME'] . ' not deleted');
		}
	}

	public static function updateRegular() : void
	{
		$baseClassName = Regular::getClassName();

		$classList = Tools::getClassList($baseClassName);
		$agentList = static::getClassAgents($classList);
		$registeredList = static::getRegisteredAgents($baseClassName);

		static::saveAgents($agentList, $registeredList);
		static::deleteAgents($agentList, $registeredList);
	}

	protected static function getClassAgents(array $classList) : array
	{
		$agentList = array();

		/** @var Regular $className */
		foreach ($classList as $className)
		{
			$normalizedClassName = $className::getClassName();
			$agents = $className::getAgents();

			foreach ($agents as $agent)
			{
				$agentDescription = static::getAgentDescription($normalizedClassName, $agent);
				$agentKey = strtolower($agentDescription['name']);

				$agentList[$agentKey] = $agentDescription;
			}
		}

		return $agentList;
	}

	protected static function getRegisteredAgents(string $baseClassName, bool $isBaseNamespace = false) : array
	{
		$registeredList = array();
		$namespaceLower = strtolower(Config::getNamespace());
		$query = CAgent::GetList(array(), array(
			'NAME' => $namespaceLower . '%',
		));

		while ($agentRow = $query->Fetch())
		{
			$agentCallParts = explode('::', $agentRow['NAME']);
			$agentClassName = trim($agentCallParts[0]);

			if ($isBaseNamespace
				|| $agentClassName === ''
				|| !class_exists($agentClassName)
				|| is_subclass_of($agentClassName, $baseClassName))
			{
				$agentKey = strtolower($agentRow['NAME']);
				$registeredList[$agentKey] = $agentRow;
			}
		}

		return $registeredList;
	}

	protected static function saveAgents(array $agentList, array $registeredList) : void
	{
		foreach ($agentList as $agentKey => $agent)
		{
			static::saveAgent($agent, $registeredList[$agentKey] ?? null);
		}
	}

	protected static function deleteAgents(array $agentList, array $registeredList) : void
	{
		foreach ($registeredList as $agentKey => $agentRow)
		{
			if (!isset($agentList[$agentKey]))
			{
				static::deleteAgent($agentRow);
			}
		}
	}

	public static function deleteAll() : void
	{
		$namespace = Config::getNamespace();
		$registeredList = static::getRegisteredAgents($namespace, true);

		static::deleteAgents([], $registeredList);
	}
}

