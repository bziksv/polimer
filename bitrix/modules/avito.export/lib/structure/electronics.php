<?php
namespace Avito\Export\Structure;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Feed;

class Electronics implements Category, CategoryCompatible, CategoryWithTags
{
	use Concerns\HasOnce;
	use Concerns\HasLocale;

	public function name() : string
	{
		return self::getLocale('NAME');
	}

	public function oldNames() : array
	{
		return [
			self::getLocale('NAME_OLD'),
		];
	}

	public function tags() : array
	{
		return (new TagFactory())->make([
			'VideoFileURL' => new Feed\Tag\VideoFIleUrl(),
		]);
	}

	public function dictionary() : Dictionary\Dictionary
	{
		return new Dictionary\Fixed([
			'AdType' => new Electronics\Properties\AdType(),
			'DeliverySubsidy' => new Dictionary\Listing\DeliverySubsidy(),
			'WeightForDelivery' => [],
			'LengthForDelivery' => [],
			'HeightForDelivery' => [],
			'WidthForDelivery' => [],
			'VideoFileURL' => [],
		]);
	}

	public function children() : array
	{
		return $this->once('children', static function() {
			return [
				new Electronics\Phone(),
				new Electronics\AudioVideo(),
				new Electronics\ComputerProducts(),
				new Electronics\PhotoEquipment(),
				new Electronics\GameConsolePrograms(),
				new Electronics\OfficeEquipmentAndSupplies(),
				new Electronics\TabletsAndEBooks(),
				new Electronics\Laptops(),
				new Electronics\DesktopComputers(),
			];
		});
	}
}