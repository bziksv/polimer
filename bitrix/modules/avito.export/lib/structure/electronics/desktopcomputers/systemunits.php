<?php
namespace Avito\Export\Structure\Electronics\DesktopComputers;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\Category;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\CategoryWithTags;
use Avito\Export\Structure\TagFactory;

class SystemUnits implements Category, CategoryLevel, CategoryWithTags
{
	use Concerns\HasOnce;
	use Concerns\HasLocale;

	public function name() : string
	{
		return self::getLocale('NAME');
	}

	public function categoryLevel() : ?string
	{
		return CategoryLevel::GOODS_SUB_TYPE;
	}

	public function tags() : array
	{
		return (new TagFactory())->make([
			'Type' => [ 'multiple' => true, 'wrapper' => true ],
		]);
	}

	public function dictionary() : Dictionary\Dictionary
	{
		return new Dictionary\Compound([
			new Dictionary\Fixed([
				'Brand' => [
					'Apple',
					self::getLocale('BRAND_OTHER'),
				],
			]),
			new Dictionary\Decorator(
				new Dictionary\Compound([
					new Dictionary\Fixed([
						'Type' => [
							self::getLocale('TYPE_GAME'),
							self::getLocale('TYPE_OFFICE'),
						],
					]),
					new Dictionary\XmlTree('electronics/desktop/ramsize.xml'),
					new Dictionary\Decorator(new Dictionary\XmlTree('electronics/desktop/processors_pc.xml'), [
						'rename' => [
							'Brand' => 'BrandProcessor',
							'Model' => 'ModelProcessor',
							'ProducerCode' => 'CodeProcessor',
						]
					]),
					new Dictionary\Decorator(new Dictionary\XmlTree('electronics/desktop/graphics_card_pc.xml'), [
						'rename' => [
							'Brand' => 'BrandVideocard',
							'Model' => 'ModelVideocard',
							'ProducerCode' => 'CodeVideocard',
						]
					]),
					new Dictionary\Decorator(new Dictionary\XmlTree('electronics/desktop/materinskie_platy_pc.xml'), [
						'rename' => [
							'Brand' => 'BrandMotherboard',
							'Model' => 'ModelMotherboard',
						]
					]),

				]),
				[
					'wait' => [ 'Brand' => self::getLocale('BRAND_OTHER')
				],
			])
		]);
	}

	public function children() : array
	{
		return [];
	}
}