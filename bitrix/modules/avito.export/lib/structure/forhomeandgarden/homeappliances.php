<?php
namespace Avito\Export\Structure\ForHomeAndGarden;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\Category;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\Factory;

class HomeAppliances implements Category, CategoryLevel
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
		return new Dictionary\Fixed([ 'Condition' => new Dictionary\Listing\Condition() ]);
	}

	public function children() : array
	{
		return $this->once('children', static function() {
			self::includeLocale();

			$factory = new Factory(self::getLocalePrefix());

			return $factory->make([
				'For home' => [
					'dictionary' => new Dictionary\Compound([
						new Dictionary\XmlTree('forhomeandgarden/homeappliances/forhome.xml', [
							'known' => [
								CategoryLevel::PRODUCT_TYPE,
								CategoryLevel::GOODS_SUB_TYPE,
								CategoryLevel::PRODUCT_SUB_TYPE,
							]
						]),
						new Dictionary\Decorator(new Dictionary\XmlTree('forhomeandgarden/homeappliances/stiralnye_mashiny.xml'), [
							'wait' => [
								CategoryLevel::GOODS_SUB_TYPE => self::getLocale('WASHING_MACHINES'),
							],
							'rename' => [
								'proizvoditel' => 'Brand',
								'model' => 'Model',
							]
						]),
						new Dictionary\Decorator(new Dictionary\XmlTree('forhomeandgarden/homeappliances/forhome/vertikalnye_pylesosy.xml'), [
							'wait' => [
								CategoryLevel::GOODS_SUB_TYPE => self::getLocale('VACUUM_CLEANERS'),
								CategoryLevel::PRODUCT_SUB_TYPE => self::getLocale('VACUUM_CLEANERS_VERTICAL'),
							],
							'rename' => [
								'proizvoditel' => 'Brand',
								'model' => 'Model',
							]
						]),
						new Dictionary\Decorator(new Dictionary\XmlTree('forhomeandgarden/homeappliances/forhome/napolnye_pylesosy.xml'), [
							'wait' => [
								CategoryLevel::GOODS_SUB_TYPE => self::getLocale('VACUUM_CLEANERS'),
								CategoryLevel::PRODUCT_SUB_TYPE => self::getLocale('VACUUM_CLEANERS_FLOOR'),
							],
							'rename' => [
								'proizvoditel' => 'Brand',
								'model' => 'Model',
							]
						]),
						new Dictionary\Decorator(new Dictionary\XmlTree('forhomeandgarden/homeappliances/forhome/robotypylesosy.xml'), [
							'wait' => [
								CategoryLevel::GOODS_SUB_TYPE => self::getLocale('VACUUM_CLEANERS'),
								CategoryLevel::PRODUCT_SUB_TYPE => self::getLocale('VACUUM_CLEANERS_ROBOT'),
							],
							'rename' => [
								'proizvoditel' => 'Brand',
								'model' => 'Model',
							]
						])
					])
				],
				'Climate control equipment' => [
					'dictionary' => new Dictionary\XmlTree('forhomeandgarden/homeappliances/climatecontrolequipment.xml', [
						'known' => [
							CategoryLevel::PRODUCT_TYPE,
							CategoryLevel::GOODS_SUB_TYPE,
						]
					]),
				],
				'For individual care' => [
					'dictionary' => new Dictionary\XmlTree('forhomeandgarden/homeappliances/forindividualcare.xml', [
						'known' => [
							CategoryLevel::PRODUCT_TYPE,
						]
					]),
				],
				'For kitchen' => [
					'dictionary' => new Dictionary\XmlTree('forhomeandgarden/homeappliances/forkitchen.xml', [
						'known' => [
							CategoryLevel::PRODUCT_TYPE,
							CategoryLevel::GOODS_SUB_TYPE,
							CategoryLevel::PRODUCT_SUB_TYPE,
						]
					]),
				],
				'Other',
			]);
		});
	}
}