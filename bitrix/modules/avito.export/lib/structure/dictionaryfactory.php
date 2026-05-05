<?php
namespace Avito\Export\Structure;

use Avito\Export\Config;
use Avito\Export\Dictionary;

class DictionaryFactory
{
	protected $messagePrefix;

	public function __construct(string $messagePrefix = '')
	{
		$this->messagePrefix = $messagePrefix;
	}

	/** @return Dictionary\Dictionary[] */
	public function make(array $map, $productType = null) : array
	{
		$result = [];

		foreach ($map as $type => $groupType)
		{
			foreach ($groupType as $param)
			{
				$wait =
					($productType !== null ? [ 'ProductType' => $this->message($productType) ] : [])
					+ $this->makeWait($type);

				if (!($param instanceof Dictionary\Dictionary))
				{
					$param = new Dictionary\XmlTree($param);
				}

				if (!empty($wait))
				{
					$param = new Dictionary\Decorator($param, [ 'wait' => $wait ]);
				}

				$result[] = $param;
			}
		}

		return $result;
	}

	public function makeWait(string $type) : array
	{
		$result = [];
		$key = null;

		foreach (explode('-->', $type) as $part)
		{
			if ($part === 'all') { continue; }

			if ($key !== null)
			{
				$result[$key] = $this->message($part);
				$key = null;
			}
			else
			{
				$key = $part;
			}
		}

		return $result;
	}

	protected function message(string $word) : string
	{
		$prefix = ($this->messagePrefix !== '' ? $this->messagePrefix . '_' : '');

		return (string)Config::getLangMessage($prefix . $word, null, $word);
	}
}