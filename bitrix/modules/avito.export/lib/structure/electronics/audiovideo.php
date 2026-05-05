<?php
namespace Avito\Export\Structure\Electronics;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\Factory;
use Avito\Export\Structure\Category;

class AudioVideo implements Category, CategoryLevel
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
		return new Dictionary\Fixed([
			'Condition' => new Dictionary\Listing\Condition(),
		]);
	}

	public function children() : array
	{
		return $this->once('children', static function() {
			self::includeLocale();

			$factory = new Factory(self::getLocalePrefix());

			return $factory->make([
				'MP3 Players',
				'Acoustics, Speakers, Subwoofers',
				'Video, DVD and Blu-ray players',
				'Video Cameras',
				'Cables & Adapters',
				'Microphones',
				'Music & Movies',
				'Music centers, stereos',
				'Headphones',
				'Televisions and projectors' => [
					'dictionary' => new Dictionary\Compound([
						new Dictionary\Fixed([
							'ProductsType' => [
								self::getLocale('PRODUCTS_TYPE_TV'),
								self::getLocale('PRODUCTS_TYPE_PROJECTORS'),
								self::getLocale('PRODUCTS_TYPE_OTHER'),
							],
						]),
						new Dictionary\Decorator(
							new Dictionary\XmlTree('electronics/tv.xml'),
							[ 'wait' => [ 'ProductsType' => self::getLocale('PRODUCTS_TYPE_TV') ] ]
						),
					]),
				],
				'Amplifiers and receivers' => [
					'dictionary' => new Dictionary\XmlTree('electronics/audiovideo/amplifiersandreceivers.xml', [
						'known' => [
							CategoryLevel::PRODUCT_TYPE,
							CategoryLevel::SUB_PRODUCT_TYPE,
						]
					]),
				],
				'Accessories',
			]);
		});
	}
}