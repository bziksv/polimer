<?php
namespace Avito\Export\Structure\Electronics\Phone;

use Avito\Export\Assert;
use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure;

class Mobile implements Structure\Category, Structure\CategoryCompatible, Structure\CategoryLevel, Structure\CategoryWithTags
{
	use Concerns\HasLocale;

	protected $name;
	protected $oldNames;
	protected $children;

	public function __construct(array $parameters)
	{
		Assert::notNull($parameters['name'], '$parameters[name]');

		$this->name = $parameters['name'];
		$this->oldNames = $parameters['oldNames'] ?? [];
		$this->children = $parameters['children'] ?? [];
	}

	public function categoryLevel() : ?string
	{
		return Structure\CategoryLevel::GOODS_TYPE;
	}

	public function name() : string
	{
		return $this->name;
	}

	public function oldNames() : array
	{
		return $this->oldNames;
	}

	public function tags() : array
	{
		return (new Structure\TagFactory())->make([
			'Set' => [ 'multiple' => true, 'wrapper' => true ],
			'DeviceFlaws' => [ 'multiple' => true, 'wrapper' => true ],
			'CameraFlaws' => [ 'multiple' => true, 'wrapper' => true ],
			'BatteryFlaws' => [ 'multiple' => true, 'wrapper' => true ],
			'SensorsFlaws' => [ 'multiple' => true, 'wrapper' => true ],
			'FunctionsFlaws' => [ 'multiple' => true, 'wrapper' => true ],
		]);
	}

	public function dictionary() : Dictionary\Dictionary
	{
		return new Dictionary\Compound([
			new Dictionary\XmlCascade('electronics/phones.xml'),
			new Dictionary\Fixed([
				'IMEI' => [],
			]),
			new Dictionary\XmlTree('electronics/phones/common.xml'),
		]);
	}

	public function children() : array
	{
		return $this->children;
	}
}