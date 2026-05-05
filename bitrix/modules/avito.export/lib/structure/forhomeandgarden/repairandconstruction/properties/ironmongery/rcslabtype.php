<?php
namespace Avito\Export\Structure\ForHomeAndGarden\RepairAndConstruction\Properties\Ironmongery;

use Avito\Export\Concerns;
use Avito\Export\Dictionary\Listing\Listing;

class RCSlabType implements Listing
{
	use Concerns\HasLocale;

	public function values() : array
	{
		return [
			self::getLocale('SLAB'),
			self::getLocale('AIRFIELD_SLAB'),
			self::getLocale('SLAB_FOR_RAILWAY_TRACKS'),
			self::getLocale('SLAB_FOR_STREETCAR_TRACKS'),
			self::getLocale('ROAD_SLAB'),
			self::getLocale('ELEVATION_SLAB'),
			self::getLocale('BOARD_FOR_CANOPIES'),
			self::getLocale('BOARDS_FOR_LOGGIAS_AND_BALCONIES'),
			self::getLocale('FOOTING_SLAB'),
			self::getLocale('FOOT_AND_ANCHOR_BOARD'),
			self::getLocale('PARAPET_SLAB'),
			self::getLocale('BOARD_TRANSITIONAL'),
			self::getLocale('PLATE_FLAT'),
			self::getLocale('PLATE_UNDERLAYING'),
			self::getLocale('WALL_BOARD'),
			self::getLocale('PAVING_SLAB'),
			self::getLocale('STABILIZING_BOARD'),
			self::getLocale('FOUNDATION_SLAB'),
			self::getLocale('SURFACE_SLABS'),
		];
	}
}
