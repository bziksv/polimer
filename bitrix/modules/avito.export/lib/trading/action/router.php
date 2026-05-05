<?php
namespace Avito\Export\Trading\Action;

use Avito\Export\Assert;
use Avito\Export\Trading;
use Bitrix\Main;

class Router
{
	private const MAP = [
		'order/accept' => OrderAccept\Action::class,
		'order/status' => OrderStatus\Action::class,
		'send/status' => SendStatus\Action::class,
		'send/marking' => SendMarking\Action::class,
		'send/track' => SendTrack\Action::class,
		'send/deliveryTerms' => SendDeliveryTerms\Action::class,
        'send/setCourierDeliveryRange' => SendSetCourierDeliveryRange\Action::class,
		'send/acceptReturnOrder' => SendAcceptReturnOrder\Action::class,
	];

	public static function make(Trading\Setup\Model $trading, string $path, array $parameters) : Reference\Action
	{
		$actionClass = static::actionClass($path);
		$commandClass = static::commandClass($actionClass);
		$command = static::compileCommand($commandClass, $parameters);

		return new $actionClass($trading, $command);
	}

	/** @return class-string<Reference\Action> */
	private static function actionClass(string $path) : string
	{
		if (!isset(static::MAP[$path]))
		{
			throw new Main\ArgumentException(sprintf('unknown %s action path', $path));
		}

		$className = static::MAP[$path];

		Assert::isSubclassOf($className, Reference\Action::class);

		return $className;
	}

	/** @return class-string<Reference\Command> */
	private static function commandClass(string $actionClass) : string
	{
		$reflection = new \ReflectionClass($actionClass);
		$className = $reflection->getNamespaceName() . '\\Command';

		Assert::isSubclassOf($className, Reference\Command::class);

		return $className;
	}

	private static function compileCommand(string $commandClass, array $parameters) : Reference\Command
	{
		$reflection = new \ReflectionClass($commandClass);
		$constructor = $reflection->getConstructor();
		$constructorParameters = $constructor !== null ? $constructor->getParameters() : [];
		$instanceParameters = [];

		foreach ($constructorParameters as $constructorParameter)
		{
			$name = $constructorParameter->getName();
			$value = $parameters[$name] ?? null;

			if ($value === null && !array_key_exists($name, $parameters))
			{
				if ($constructorParameter->isDefaultValueAvailable())
				{
					$value = $constructorParameter->getDefaultValue();
				}
				else
				{
					throw new Main\ArgumentException(sprintf('Command %s required %s', $commandClass, $name));
				}
			}

			$instanceParameters[] = $value;
		}

		return $reflection->newInstanceArgs($instanceParameters);
	}
}