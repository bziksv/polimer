<?php
namespace Avito\Export\Structure\PersonalBelongings\ChildrenClothingAndShoes;

use Avito\Export\Assert;
use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure;

class ForBoys implements Structure\Category, Structure\CategoryLevel, Structure\CategoryWithTags
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
			'Functions' => [ 'multiple' => true, 'wrapper' => true ],
		]);
	}

    public function dictionary() : Dictionary\Dictionary
    {
	    /** @noinspection SpellCheckingInspection */
	    return new Dictionary\Compound([
		    new Dictionary\XmlTree('personalbelongings/childrenclothingandshoes/for_boys/for_boys.xml', [
				'known' => [
					Structure\CategoryLevel::APPAREL,
					Structure\CategoryLevel::APPAREL_TYPE,
					Structure\CategoryLevel::APPAREL_SUB_TYPE,
				],
		    ]),
		    new Dictionary\Decorator(
			    new Dictionary\XmlTree('personalbelongings/childrenclothingandshoes/for_boys/size.xml'),
			    [
					'wait' => [
						'Apparel' => [
							self::getLocale('PANTS'),
							self::getLocale('OUTERWEAR'),
							self::getLocale('JUMPSUITS'),
							self::getLocale('UNDERWEAR_AND_HOME_CLOTHES'),
							self::getLocale('SWEATERS_AND_HOODIES'),
							self::getLocale('TSHIRTS'),
							self::getLocale('SHIRTS'),
							self::getLocale('JACKETS_AND_SUITS'),
							self::getLocale('BEACH_WEAR'),
							self::getLocale('OTHER'),
						]
					],
			    ]
		    ),
		    new Dictionary\Decorator(
			    new Dictionary\XmlTree('personalbelongings/childrenclothingandshoes/for_boys/size_shoes.xml'),
			    [ 'wait' => [ 'Apparel' => self::getLocale('SHOES') ], ]
		    )
	    ]);
    }
    public function children() : array
    {
        return $this->children;
    }
}