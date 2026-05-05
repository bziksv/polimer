<?php
namespace Avito\Export\Structure\Transportation\PartsAndAccessories\Parts;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure;

class ForMotorcycles implements Structure\Category, Structure\CategoryLevel
{
    use Concerns\HasLocale;

	public function categoryLevel() : ?string
	{
		return Structure\CategoryLevel::PRODUCT_TYPE;
	}

	public function name() : string
	{
		return self::getLocale('NAME');
	}

	public function children() : array
	{
		return [];
	}

	public function dictionary() : Dictionary\Dictionary
    {
	    return new Dictionary\Compound(
		    (new Structure\DictionaryFactory(self::getLocalePrefix()))->make([
			    'all' => [
				    'other_tags' => 'transportation/partsandaccessories/parts/producttype/for_motorcycles/for_motorcycles.xml',
				    'Brand' => new Dictionary\XmlTree('transportation/partsandaccessories/parts/partsbrands.xml'),
				    'Originality' => new Dictionary\XmlTree('transportation/partsandaccessories/parts/originality.xml'),
			    ],
		    ])
	    );
    }
}
