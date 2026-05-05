<?php
namespace Avito\Export\Structure\HobbiesAndRecreation;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\Category;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\Factory;

class SportAndRecreation implements Category, CategoryLevel
{
	use Concerns\HasOnce;
	use Concerns\HasLocale;

	public function name() : string
	{
		return self::getLocale('NAME');
	}

	public function categoryLevel() : ?string
	{
		return CategoryLevel::CATEGORY;
	}

	public function dictionary() : Dictionary\Dictionary
	{
		return new Dictionary\Compound([
			new Dictionary\Fixed([ 'Condition' => new Dictionary\Listing\Condition() ]),
			new Dictionary\XmlTree('hobbiesandrecreation/sportandrecreation.xml', [
				'known' => [
					CategoryLevel::GOODS_TYPE,
					CategoryLevel::FITNESS_TYPE,
					CategoryLevel::WINTER_SPORT_TYPE,
					CategoryLevel::WATER_SPORT_TYPE,
					CategoryLevel::TOURISM_TYPE,
					CategoryLevel::GOODS_SUB_TYPE,
					CategoryLevel::PRODUCT_TYPE,
					CategoryLevel::TRAINER_TYPE,
				]
			]),
			new Dictionary\Decorator(
				new Dictionary\XmlCascade('hobbiesandrecreation/sportandrecreation/lifestyle_katalog_brendov_i_modelej_zimnij_sport_hokkej_konki.xml'),
				[
					'rename' => [
						'brend_lifestyle_zimnij_sport_hokkej_konki' => 'Brand',
						'model_lifestyle_zimnij_sport_hokkej_konki' => 'Model',
					],
					'wait' => [ 'GoodsSubType' => self::getLocale('SKATES') ],
				]
			),
			new Dictionary\Decorator(
				new Dictionary\XmlCascade('hobbiesandrecreation/sportandrecreation/lifestyle_katalog_brendov_klyushki.xml'),
				[
					'rename' => [
						'brend_klyushki' => 'Brand',
						'model_klyushki' => 'Model',
					],
					'wait' => [ 'GoodsSubType' => self::getLocale('HOCKEY_STICKS') ],
				]
			),
		]);
	}

	public function children() : array
	{
		return [];
	}
}