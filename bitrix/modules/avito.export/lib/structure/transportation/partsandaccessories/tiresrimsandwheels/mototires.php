<?php
namespace Avito\Export\Structure\Transportation\PartsAndAccessories\TiresRimsAndWheels;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure;

class MotoTires implements Structure\Category, Structure\CategoryLevel
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
		$dictionaryFactory = new Structure\DictionaryFactory(self::getLocalePrefix());

	    return new Dictionary\Compound(
		    $dictionaryFactory->make([
			    'all' => [
				    'other_tags' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/moto_tires/moto_tires.xml',
				    'TireSectionWidth' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/moto_tires/tiresectionwidth.xml',
				    'TireAspectRatio' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/moto_tires/tireaspectratio.xml',
			    ]
			])
	    );
    }
}
