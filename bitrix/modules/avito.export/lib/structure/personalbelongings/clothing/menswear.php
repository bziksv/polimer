<?php
namespace Avito\Export\Structure\PersonalBelongings\Clothing;

use Avito\Export\Assert;
use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure;

class MensWear implements Structure\Category, Structure\CategoryLevel, Structure\CategoryWithTags
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

	public function categoryLevel() : ?string
	{
		return Structure\CategoryLevel::GOODS_TYPE;
	}

	public function tags() : array
	{
		return (new Structure\TagFactory())->make([
			'MaterialsOdezhda' => [ 'multiple' => true, 'wrapper' => true ],
		]);
	}

    public function dictionary() : Dictionary\Dictionary
    {
	    /** @noinspection SpellCheckingInspection */
	    return new Dictionary\Compound([
		    new Dictionary\XmlTree('personalbelongings/clothingshoesaccessories/mens_wear/mens_wear.xml', [
			    'known' => [
				    Structure\CategoryLevel::APPAREL,
				    Structure\CategoryLevel::APPAREL_TYPE,
				    Structure\CategoryLevel::GOODS_SUB_TYPE,
			    ]
		    ]),
		    new Dictionary\XmlTree('personalbelongings/clothingshoesaccessories/materials_odezhda.xml'),
		    new Dictionary\XmlTree('personalbelongings/clothingshoesaccessories/mens_wear/size.xml'),
	    ]);
    }

    public function children() : array
    {
        return $this->children;
    }
}