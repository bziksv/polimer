<?php
namespace Avito\Export\Structure\Electronics;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\Factory;
use Avito\Export\Structure\Category;

class ComputerProducts implements Category, CategoryLevel
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

			$customFactory = new Factory(self::getLocalePrefix());
			$componentsFactory = new Factory(self::getLocalePrefix());
			$componentsFactory->categoryLevel(CategoryLevel::PRODUCTS_TYPE);

			return $customFactory->make([
				'Acoustics',
				'Webcams',
				'Joysticks and rudders',
				'Keyboards and mice',
				'Monitors and spare parts' => [
					'categoryLevel' => $customFactory->currentCategoryLevel(),
					'oldNames' => $customFactory->itemTitle('Monitors'),
					'children' => $componentsFactory->make([
						'Monitors' => [
							'dictionary' => new Dictionary\Decorator(new Dictionary\XmlTree('electronics/computerproducts/monitors.xml'), [
								'rename' => [
									'brend'	=> 'Brand',
									'model'	=> 'Model',
								],
							]),
						],
						'Spare parts',
					]),
				],
				'Portable hard drives',
				'TV tuners',
				'Network Equipment',
				'Flash drives and memory cards',
				'Accessories',
				'Components' => [
					'categoryLevel' => $customFactory->currentCategoryLevel(),
					'children' => $componentsFactory->make([
						'CD,DVD and Blu-ray drives',
						'Power Supplies',
						'Video cards' => [
							'dictionary' => new Dictionary\XmlCascade('electronics/gpus.xml'),
						],
						'Cooling systems',
						'Controllers',
						'Processors' => [
							'dictionary' => new Dictionary\XmlCascade('electronics/processors.xml'),
						],
						'Main memory' => [
							'dictionary' => new Dictionary\Compound([
								new Dictionary\XmlCascade('electronics/operativnaya_pamyat.xml'),
								new Dictionary\Fixed([
									'Quantity' => [],
								]),
							]),
						],
						'Motherboards' => [
							'dictionary' => new Dictionary\XmlCascade('electronics/materinskie_platy.xml'),
						],
						'Hulls' => [
							'oldNames' => self::getLocale('HULLS_OLD'),
						],
						'Sound cards',
						'Hard drives' => [
							'dictionary' => new Dictionary\XmlCascade('electronics/hard_drives.xml'),
						],
					]),
				],
			]);
		});
	}
}