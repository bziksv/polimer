<?php
namespace Avito\Export\Structure\PersonalBelongings;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\Factory;
use Avito\Export\Structure\Category;

class BeautyAndHealth implements Category, CategoryLevel
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
        return new Dictionary\NoValue();
    }

    public function children() : array
    {
        return $this->once('children', static function() {
            self::includeLocale();

            $customFactory = new Factory(self::getLocalePrefix());

            return $customFactory->make([
                'Cosmetics',
                'Perfumery',
                'Devices and accessories' => [
	                'dictionary' => new Dictionary\Fixed([
		                'Condition' => new Dictionary\Listing\Condition(),
	                ]),
                ],
                'Hygiene products',
                'Hair products',
                'Medical devices' => [
	                'dictionary' => new Dictionary\Fixed([
		                'Condition' => new Dictionary\Listing\Condition(),
	                ]),
                ],
	            'Other',
            ]);
        });
    }
}