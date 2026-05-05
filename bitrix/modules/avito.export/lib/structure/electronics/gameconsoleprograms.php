<?php
namespace Avito\Export\Structure\Electronics;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\Factory;
use Avito\Export\Structure\Category;

class GameConsolePrograms implements Category, CategoryLevel
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

	/** @noinspection SpellCheckingInspection */
	public function children() : array
	{
		return $this->once('children', static function() {
			self::includeLocale();

			$factory = (new Factory(self::getLocalePrefix()));

			return $factory->make([
				'Game consoles and accessories' => [
					'categoryLevel' => $factory->currentCategoryLevel(),
					'oldNames' => $factory->itemTitle('Game consoles'),
					'children' => $factory->clone()
						->categoryLevel(CategoryLevel::PRODUCT_TYPE)
						->make([
							'Game consoles' => [
								'dictionary' => new Dictionary\XmlTree('electronics/pristavki.xml', [
									'known' => [
										CategoryLevel::GOODS_SUB_TYPE,
									],
								]),
							],
							'Accessories',
							'Spare parts',
						]),
				],
				'Games for consoles' => [
					'dictionary' => new Dictionary\XmlTree('electronics/games/games_for_consoles.xml', [
						'known' => [
							CategoryLevel::TYPE,
						],
					]),
				],
				'Computer Games',
				'Programs',
			]);
		});
	}
}