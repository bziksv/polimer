<?php
namespace Avito\Export\Structure\Transportation\WaterTransport;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure;

class BoatsAndYachts implements Structure\Category, Structure\CategoryLevel
{
	use Concerns\HasLocale;

	public function name() : string
	{
		return self::getLocale('NAME');
	}

	public function categoryLevel() : ?string
	{
		return Structure\CategoryLevel::VEHICLE_TYPE;
	}

	public function dictionary() : Dictionary\Dictionary
	{
		$dictionaryFactory = new Structure\DictionaryFactory(self::getLocalePrefix());

		return new Dictionary\Compound(
			$dictionaryFactory->make([
				'all' => [
					//@todo - if have space, haven't variants
					'tags_make_and_model' => 'transportation/watertransport/boats_and_yachts/katera_i_yahty.xml',
					'other_tags' => new Dictionary\XmlTree('transportation/watertransport/boats_and_yachts/katera_i_yahty_tags.xml', [
						'known' => [
							Structure\CategoryLevel::TYPE,
							Structure\CategoryLevel::BOAT_CATEGORY,
						],
					]),
					'Year' => 'transportation/watertransport/boats_and_yachts/katera_i_yahty_year.xml',
				]
			])
		);
	}

	public function children() : array
	{
		return [];
	}
}