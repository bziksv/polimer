<?php
namespace Avito\Export\Structure\Electronics\Tablets;

use Avito\Export\Assert;
use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure;

class Tablets implements Structure\Category
{
	use Concerns\HasLocale;

	protected $name;
	protected $children;

	public function __construct(array $parameters)
	{
		Assert::notNull($parameters['name'], '$parameters[name]');

		$this->name = $parameters['name'];
		$this->children = $parameters['children'] ?? [];
	}

	public function name() : string
	{
		return $this->name;
	}

	public function dictionary() : Dictionary\Dictionary
	{
		return new Dictionary\Compound([
			new Dictionary\XmlTree('tablets.xml'),
			new Dictionary\Fixed([
				'ProductsType' => new ProductsType()
			])
		]);
	}

	public function children() : array
	{
		return $this->children;
	}
}