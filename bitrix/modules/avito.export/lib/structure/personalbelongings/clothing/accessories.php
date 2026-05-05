<?php
namespace Avito\Export\Structure\PersonalBelongings\Clothing;

use Avito\Export\Assert;
use Avito\Export\Dictionary;
use Avito\Export\Structure;
use Avito\Export\Structure\PersonalBelongings\Props;

class Accessories implements Structure\Category, Structure\CategoryLevel
{
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

	public function categoryLevel() : ?string
	{
		return Structure\CategoryLevel::GOODS_TYPE;
	}

    public function dictionary() : Dictionary\Dictionary
    {
        return new Dictionary\Compound([
            new Dictionary\XmlTree('personalbelongings/accessories.xml', [
	            'known' => [
		            Structure\CategoryLevel::APPAREL,
	            ],
            ]),
            new Dictionary\Fixed([
                'Gender' => new Props\Gender()
            ])
        ]);
    }

    public function children() : array
    {
        return $this->children;
    }
}