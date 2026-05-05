<?php
namespace Avito\Export\Structure\ForHomeAndGarden\RepairAndConstruction;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\Custom;
use Avito\Export\Structure\TagFactory;

class PrefabricatedStructuresAndLogCabins extends Custom
{
	use Concerns\HasLocale;

	public function categoryLevel() : ?string
	{
		return CategoryLevel::GOODS_TYPE;
	}

	public function tags() : array
	{
		return (new TagFactory())->make([
			'HouseExtras' => [ 'multiple' => true, 'wrapper' => true ],
		]);
	}

	public function dictionary() : Dictionary\Dictionary
	{
		return new Dictionary\XmlTree('forhomeandgarden/prefabricatedstructuresandlogcabins.xml', [
			'known' => [
				CategoryLevel::GOODS_SUB_TYPE,
				CategoryLevel::HOUSE_TYPE,
			],
		]);
	}

}