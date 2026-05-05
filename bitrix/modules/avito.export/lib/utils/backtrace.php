<?php
namespace Avito\Export\Utils;

class BackTrace
{
	public static function format(array $trace) : string
	{
		$result = '';

		foreach ($trace as $traceNum => $traceInfo)
		{
			$traceLine = '#' . $traceNum . ': ';

			if (array_key_exists('class', $traceInfo))
			{
				$traceLine .= $traceInfo['class'] . $traceInfo['type'];
			}

			if (array_key_exists('function', $traceInfo))
			{
				$traceLine .= $traceInfo['function'];
			}

			$traceLine .= "\n\t" . $traceInfo['file'] . ':' . $traceInfo['line'];

			$result .= $traceLine . "\n";
		}

		return $result;
	}
}