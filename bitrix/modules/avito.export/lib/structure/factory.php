<?php
namespace Avito\Export\Structure;

use Avito\Export;

class Factory
{
	protected $messagePrefix;
	protected $categoryClass;
	/** @var string|null */
	protected $categoryLevel = CategoryLevel::GOODS_TYPE;

	public function __construct(string $messagePrefix, string $categoryClass = Custom::class)
	{
		$this->messagePrefix = $messagePrefix;
		$this->categoryClass = $categoryClass;
	}

	public function clone() : self
	{
		$result = new static($this->messagePrefix, $this->categoryClass);
		$result->categoryLevel = $this->categoryLevel;

		return $result;
	}

	public function itemTitles(array $codes) : array
	{
		$result = [];

		foreach ($codes as $code)
		{
			$result[] = $this->itemTitle($code);
		}

		return $result;
	}

	public function itemTitle(string $code) : string
	{
		return $this->itemName($code);
	}

	public function currentCategoryLevel() : ?string
	{
		return $this->categoryLevel;
	}

	public function categoryLevel(?string $tag) : Factory
	{
		$this->categoryLevel = $tag;

		return $this;
	}

	public function make(array $children) : array
	{
		$categoryClass = $this->categoryClass;
		$result = [];

		foreach ($children as $key => $value)
		{
			if ($value instanceof Category)
			{
				$result[] = $value;
				continue;
			}

			[$code, $parameters] = $this->sanitizeItem($key, $value);

			$name = $this->itemName($code);
			$defaultParameters = [];

			if (!empty($parameters['tags']))
			{
				$parameters['tags'] = (new TagFactory())->make($parameters['tags']);
			}

			if (empty($parameters['children']))
			{
				$defaultParameters += [ 'categoryLevel' => $this->categoryLevel ];
			}

			$result[] = new $categoryClass(
				[ 'name' => $name ]
				+ $parameters
				+ $defaultParameters
			);
		}

		return $result;
	}

	protected function sanitizeItem($key, $value) : array
	{
		if (is_numeric($key))
		{
			$code = $value;
			$parameters = [];
		}
		else
		{
			$code = $key;
			$parameters = is_array($value) ? $value : [];

			if (isset($parameters['children']))
			{
				$parameters['children'] = $this->make($parameters['children']);
			}
		}

		return [$code, $parameters];
	}

	protected function itemName(string $code) : string
	{
		$sanitized = mb_strtoupper($code);
		$sanitized = str_replace(['-', ' ', '&', ',', '\''], '_', $sanitized);
		$sanitized = preg_replace('/_{2,}/', '_', $sanitized);

		return Export\Config::getLangMessage($this->messagePrefix . '_' . $sanitized, null, $code);
	}
}