<?php
namespace Avito\Export\Concerns;

use Avito\Export\Utils\Caller;

trait HasOnce
{
	private $onceMemoized = [];

	protected function once(string $name, callable $callable, ...$arguments)
	{
		$sign = $name;
		$sign .= !empty($arguments) ? '-' . Caller::argumentsHash(...$arguments) : '';

		if (!isset($this->onceMemoized[$sign]) && !array_key_exists($sign, $this->onceMemoized))
		{
			$this->onceMemoized[$sign] = call_user_func($callable, ...$arguments);
		}

		return $this->onceMemoized[$sign];
	}
}