<?php
namespace Avito\Export\Structure\ForBusiness;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\Category;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\CategoryWithTags;
use Avito\Export\Structure\Factory;
use Avito\Export\Structure\TagFactory;

class ReadyBusiness implements Category, CategoryLevel, CategoryWithTags
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

	public function tags() : array
	{
		return (new TagFactory())->make([
			'FranchiseTypeSupport' => [ 'multiple' => true, 'wrapper' => true ],
		]);
	}

	public function dictionary() : Dictionary\Dictionary
	{
		return new Dictionary\Compound([
			new Dictionary\Decorator(
				new Dictionary\Fixed([ 'DealGoal' => new Readybusiness\Properties\DealGoal() ]),
				[
					'wait' => [
						'!BusinessType' => [
							self::getLocale('FRANCHISES'),
							self::getLocale('SOFTWARE_FOR_BUSINESS')
						]
					]
				]
			),
			new Dictionary\Decorator(
				new Dictionary\XmlTree('forbusiness/readybusiness/common.xml'),
				[
					'wait' => [
						'!BusinessType' => [
							self::getLocale('FRANCHISES'),
							self::getLocale('SOFTWARE_FOR_BUSINESS'),
							self::getLocale('OTHER'),
						]
					]
				]
			),
		]);
	}

	public function children() : array
	{
		return $this->once('children', static function() {
			$factory = new Factory(self::getLocalePrefix());
			$factory->categoryLevel(CategoryLevel::BUSINESS_TYPE);

			return $factory->make([
				'Production' => [
					'dictionary' => new Dictionary\XmlTree('forbusiness/readybusiness/production.xml', [
						'known' => [
							CategoryLevel::BUSINESS_SUB_TYPE,
						]
					]),
				],
				'It business' => [
					'dictionary' => new Dictionary\XmlTree('forbusiness/readybusiness/itbusiness.xml', [
						'known' => [
							CategoryLevel::BUSINESS_SUB_TYPE,
						]
					]),
					'oldNames' => [
						self::getLocale('ONLINE_SHOPPING_AND_IT_OLD_NAMES'),
						self::getLocale('ONLINE_SHOPPING_AND_IT'),
					]
				],
				'Trading' => [
					'dictionary' => new Dictionary\XmlTree('forbusiness/readybusiness/trading.xml', [
						'known' => [
							CategoryLevel::BUSINESS_SUB_TYPE,
						]
					]),
				],
				'Services' => [
					'dictionary' => new Dictionary\XmlTree('forbusiness/readybusiness/services.xml', [
						'known' => [
							CategoryLevel::BUSINESS_SUB_TYPE,
						]
					]),
				],
				'Construction',
				'Agriculture'=> [
					'dictionary' => new Dictionary\XmlTree('forbusiness/readybusiness/agriculture.xml', [
						'known' => [
							CategoryLevel::BUSINESS_SUB_TYPE,
						]
					]),
					'oldNames' => self::getLocale('AGRICULTURE_OLD_NAMES')
				],
				'Entertainment Sector' => [
					'dictionary' => new Dictionary\XmlTree('forbusiness/readybusiness/entertainmentsector.xml', [
						'known' => [
							CategoryLevel::BUSINESS_SUB_TYPE,
						]
					]),
					'oldNames' => self::getLocale('ENTERTAINMENT')
				],
				'Catering' => [
					'dictionary' => new Dictionary\XmlTree('forbusiness/readybusiness/catering.xml', [
						'known' => [
							CategoryLevel::BUSINESS_SUB_TYPE,
						]
					]),
					'oldNames' => self::getLocale('CATERING_OLD_NAMES')
				],
				'Autobusiness' => [
					'dictionary' => new Dictionary\XmlTree('forbusiness/readybusiness/autobusiness.xml', [
						'known' => [
							CategoryLevel::BUSINESS_SUB_TYPE,
						]
					]),
					'oldNames' => self::getLocale('AUTOBUSINESS_OLD_NAMES')
				],
				'Beauty and Health' => [
					'dictionary' => new Dictionary\XmlTree('forbusiness/readybusiness/beautyandhealth.xml', [
						'known' => [
							CategoryLevel::BUSINESS_SUB_TYPE,
						]
					]),
					'oldNames' => self::getLocale('BEAUTY_AND_CARE')
				],
				'Tourism' => [
					'oldNames' => self::getLocale('TOURISM_OLD_NAMES')
				],
				'Franchises' => [
					'dictionary' => new Dictionary\XmlTree('forbusiness/readybusiness/franchises.xml', [
						'known' => [
							CategoryLevel::GOODS_SUB_TYPE,
							CategoryLevel::FRANCHISE_SUB_TYPE,
						]
					]),
				],
				'Rental Business' => [
					'dictionary' => new Dictionary\XmlTree('forbusiness/readybusiness/rentalbusiness.xml', [
						'known' => [
							CategoryLevel::BUSINESS_SUB_TYPE,
						]
					]),
				],
				'Order Pickup Points' => [
					'oldNames' => self::getLocale('STORES_AND_ORDERING_OUTLETS')
				],
				'Software for business' => [
					'dictionary' => new Dictionary\XmlTree('forbusiness/readybusiness/softwareforbusiness.xml', [
						'known' => [
							CategoryLevel::BUSINESS_SUB_TYPE,
						]
					]),
				],
				'Other',
			]);
		});
	}
}