<?php
namespace Avito\Export\Structure\Electronics;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\Factory;
use Avito\Export\Structure\Category;

class Laptops implements Category, CategoryLevel
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
			new Dictionary\Decorator(
				new Dictionary\XmlCascade('electronics/laptops/laptops.xml'),
				[
					'rename' => [
						'proizvoditel' => 'Vendor',
						'model_na_yam' => 'Model',
						'lineyka_protsessora' => 'ProcessorLine',
						'protsessor' => 'Processor',
						'kolichestvo_yader_protsessora' => 'ProcessorCores',
						'tip_videokarty' => 'VideocardType',
						'videokarta' => 'Videocard',
						'obem_videopamyati' => 'VRamSize',
						'diagonal_ekrana' => 'ScreenSize',
						'razreshenie_ekrana' => 'ScreenRes',
					],
				]
			),
			new Dictionary\XmlTree('electronics/laptops/drive_size.xml'),
			new Dictionary\XmlTree('electronics/laptops/ram_size.xml'),
			new Dictionary\XmlTree('electronics/laptops/common.xml'),
			new Dictionary\Fixed([
				'Condition' => new Dictionary\Listing\Condition(),
			])
		]);
	}

	public function children() : array
	{
		return [];
	}
}