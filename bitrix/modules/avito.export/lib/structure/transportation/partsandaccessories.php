<?php
namespace Avito\Export\Structure\Transportation;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\CategoryWithTags;
use Avito\Export\Structure\Factory;
use Avito\Export\Structure\Category;
use Avito\Export\Structure\TagFactory;
use Avito\Export\Feed;

class PartsAndAccessories implements Category, CategoryLevel, CategoryWithTags
{
    use Concerns\HasOnce;
    use Concerns\HasLocale;

	public function categoryLevel() : ?string
	{
		return CategoryLevel::CATEGORY;
	}

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
	    return new Dictionary\Compound([
            new Dictionary\Fixed([
                'AdType' => new Dictionary\Listing\AdType(),
                'Condition' => new Dictionary\Listing\Condition(),
	            'WeightForDelivery' => [],
	            'LengthForDelivery' => [],
	            'HeightForDelivery' => [],
	            'WidthForDelivery' => [],
	            'VideoFileURL' => [],
            ]),
        ]);
    }

    public function children() : array
    {
        return $this->once('children', static function() {
            self::includeLocale();

	        return (new Factory(self::getLocalePrefix()))->make([
				new PartsAndAccessories\Parts(),
				new PartsAndAccessories\OilsAndChemicals(),
				'Accessories' => [
					'dictionary' => new Dictionary\XmlTree('transportation/partsandaccessories/accessories.xml', [
						'known' => [
							CategoryLevel::PRODUCT_TYPE,
							CategoryLevel::ACCESSORY_TYPE,
							CategoryLevel::COVER_TYPE,
							CategoryLevel::MAT_BRAND,
							CategoryLevel::PROTECTION_TYPE,
							CategoryLevel::INSTALLATION_TYPE,
						],
					]),
				],
				'GPS navigators',
				'Audio and video equipment' => [
					'dictionary' => new Dictionary\XmlTree('transportation/partsandaccessories/audio_and_video_equipment.xml', [
						'known' => [
							CategoryLevel::EQUIPMENT_TYPE,
							CategoryLevel::AUDIO_TYPE,
						],
					]),
				],
				'Trunks and towbars' => [
					'dictionary' => new Dictionary\XmlTree('transportation/partsandaccessories/trucksandtowbars.xml', [
						'known' => [
							CategoryLevel::PRODUCT_TYPE,
						],
					]),
				],
				'Tools',
				'Trailers' => [
					'dictionary' => new Dictionary\XmlTree('transportation/partsandaccessories/trailers.xml', [
						'known' => [
							CategoryLevel::PRODUCT_TYPE,
							CategoryLevel::VEHICLE_TYPE,
							CategoryLevel::PARTS_HINDCARRIAGE_TYPE,
						],
					]),
				],
				'Antitheft devices' => [
					'dictionary' =>
						new Dictionary\Fixed([
							'DeviceType' => new PartsAndAccessories\Properties\DeviceType()
						]),
		        ],
				new PartsAndAccessories\TiresRimsAndWheels(),
				'Equipment',
	        ]);
		});
    }
}