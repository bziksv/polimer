<?php
namespace Avito\Export\Structure\Electronics\Phone;

use Avito\Export\Assert;
use Avito\Export\Dictionary;
use Avito\Export\Structure;

class Common implements Structure\Category, Structure\CategoryLevel
{
	protected $name;
	protected $categoryLevel;
	protected $children;

	public function __construct(array $parameters)
	{
		Assert::notNull($parameters['name'], '$parameters[name]');

		$this->name = $parameters['name'];
		$this->categoryLevel = $parameters['categoryLevel'] ?? null;
		$this->children = $parameters['children'] ?? [];
	}

	public function name() : string
	{
		return $this->name;
	}

	public function categoryLevel() : ?string
	{
		return $this->categoryLevel;
	}

	public function dictionary() : Dictionary\Dictionary
	{
		return new Dictionary\Fixed([
			'Condition' => new Dictionary\Listing\Condition(),
		]);
	}

	public function children() : array
	{
		return $this->children;
	}
}