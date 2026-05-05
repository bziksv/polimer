<?php
namespace Avito\Export\Structure\PersonalBelongings\Clothing;

use Avito\Export\Assert;
use Avito\Export\Dictionary;
use Avito\Export\Structure;
use Avito\Export\Structure\PersonalBelongings\Props;

class Bags implements Structure\Category, Structure\CategoryLevel
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

	/** @noinspection SpellCheckingInspection */
	public function dictionary() : Dictionary\Dictionary
    {
	    return new Dictionary\Compound([
		    new Dictionary\XmlTree('personalbelongings/bags_backpacks_and_suitcases.xml', [
			    'known' => [
				    Structure\CategoryLevel::APPAREL,
				    Structure\CategoryLevel::APPAREL_TYPE,
			    ],
		    ]),
		    new Dictionary\Fixed([
			    'Gender' => new Props\Gender(),
		    ]),

		    new Dictionary\XmlTree('personalbelongings/bags_model.xml'),
	    ]);
    }

    public function children() : array
    {
        return $this->children;
    }
}