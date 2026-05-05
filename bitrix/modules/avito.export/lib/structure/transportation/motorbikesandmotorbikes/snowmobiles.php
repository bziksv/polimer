<?php
namespace Avito\Export\Structure\Transportation\MotorBikesAndMotorBikes;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure;

class Snowmobiles implements Structure\Category, Structure\CategoryLevel
{
    use Concerns\HasOnce;
    use Concerns\HasLocale;

	public function categoryLevel() : ?string
	{
		return Structure\CategoryLevel::VEHICLE_TYPE;
	}

	public function name() : string
	{
		return self::getLocale('NAME');
	}

	public function dictionary() : Dictionary\Dictionary
	{
		$dictionaryFactory = new Structure\DictionaryFactory(self::getLocalePrefix());

		return new Dictionary\Compound($dictionaryFactory->make([
			'Condition-->ADDITIONAL_CONDITION_USED' => $this->groupTagsAdditional(),
			'Condition-->ADDITIONAL_CONDITION_FOR_SPARE_PARTS' => $this->groupTagsAdditional(),
			'all' => [
				'tags_make_and_model' => new Dictionary\Decorator(new Dictionary\XmlTree('transportation/partsandaccessories/motorbikesandmotorbikes/snowmobiles/snegohod.xml'), [
					'rename' => [
						'mark' => 'Make',
						'model' => 'Model',
					],
				]),
				'other_tags' => new Dictionary\XmlTree('transportation/partsandaccessories/motorbikesandmotorbikes/snowmobiles/snegohod_tags.xml', [
					'known' => [
						Structure\CategoryLevel::TYPE,
					],
				]),
				'Year' => 'transportation/partsandaccessories/motorbikesandmotorbikes/props/snowmobiles_years.xml',
			],
		]));
	}

	public function groupTagsAdditional() : array
	{
		return [
			new Dictionary\Fixed([
				'Kilometrage' => []
			]),
		];
	}

	public function children() : array
	{
		return [];
	}
}



