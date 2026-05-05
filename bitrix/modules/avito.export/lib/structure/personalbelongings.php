<?php
namespace Avito\Export\Structure;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Feed;

class PersonalBelongings implements Category, CategoryWithTags
{
    use Concerns\HasOnce;
    use Concerns\HasLocale;

    public function name() : string
    {
        return self::getLocale('NAME');
    }

	public function tags() : array
	{
		return (new TagFactory())->make([
			'VideoFileURL' => new Feed\Tag\VideoFileUrl(),
		]);
	}

    public function dictionary() : Dictionary\Dictionary
    {
	    return new Dictionary\Fixed([
		    'AdType' => new Dictionary\Listing\AdType(),
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
                new PersonalBelongings\ClothingShoesAccessories(),
                new PersonalBelongings\ChildrenClothingAndShoes(),
                new PersonalBelongings\GoodsForChildrenAndToys(),
                new PersonalBelongings\WatchesAndJewelry(),
	            new PersonalBelongings\BeautyAndHealth(),
            ];
        });
    }
}