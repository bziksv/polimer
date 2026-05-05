<?php
namespace Avito\Export\Feed\Source;

use Avito\Export\Event;

class ListenerProxy extends Event\Base
{
	/** @var FetcherPool */
	protected static $fetcherPool;
	protected static $listenerCache = [];

	public static function bind(string $type, array $handler) : void
	{
		$handler = static::convertHandler($type, $handler);

		static::register($handler);
	}

	public static function unbind(string $type, array $handler) : void
	{
		$handler = static::convertHandler($type, $handler);

		static::unregister($handler);
	}

	protected static function convertHandler(string $type, array $handler) : array
	{
		if (!isset($handler['arguments'])) { $handler['arguments'] = []; }

		$method = $handler['method'] ?? $handler['event'];

		array_unshift($handler['arguments'], $type, $method);

		return array_merge($handler, [
			'method' => 'call',
		]);
	}

	public static function call(string $type, string $method, ...$arguments) : void
	{
		try
		{
			$listener = static::listener($type);

			$listener->{$method}(...$arguments);
		}
		catch (\Throwable $exception)
		{
			trigger_error($exception->getMessage(), E_USER_WARNING);
		}
	}

	protected static function listener(string $type) : Listener
	{
		if (!isset(static::$listenerCache[$type]))
		{
			static::$listenerCache[$type] = static::fetcherPool()->some($type)->listener();
		}

		return static::$listenerCache[$type];
	}

	protected static function fetcherPool() : FetcherPool
	{
		if (static::$fetcherPool === null)
		{
			static::$fetcherPool = new FetcherPool();
		}

		return static::$fetcherPool;
	}
}