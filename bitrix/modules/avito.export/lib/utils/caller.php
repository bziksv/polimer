<?php
namespace Avito\Export\Utils;

class Caller
{
	public static function argumentsHash(...$arguments) : string
	{
		$partials = [];

		foreach ($arguments as $argument)
		{
			if ($argument === null || is_scalar($argument))
			{
				$partials[] = (string)$argument;
			}
			else if (is_object($argument))
			{
				$partials[] = 'o_' . spl_object_id($argument);
			}
			else if (is_array($argument))
			{
				$serialized = serialize($argument);

				if (mb_strlen($serialized) > 32)
				{
					$serialized = md5($serialized);
				}

				$partials[] = 'a_' . $serialized;
			}
			else if (is_resource($argument))
			{
				$partials[] = 'r_' . (int)$argument;
			}
		}

		return implode(':', $partials);
	}
}