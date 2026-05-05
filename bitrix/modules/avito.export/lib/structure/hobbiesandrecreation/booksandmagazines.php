<?php
namespace Avito\Export\Structure\HobbiesAndRecreation;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\Category;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\Factory;

class BooksAndMagazines implements Category, CategoryLevel
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
		return new Dictionary\Fixed([ 'Condition' => new Dictionary\Listing\Condition() ]);
	}

	public function children() : array
	{
		return $this->once('children', static function() {
			self::includeLocale();

			return (new Factory(self::getLocalePrefix()))->make([
				'Magazines, newspapers, brochures',
				'Books' => [
					'dictionary' => new Dictionary\Compound([
						new Dictionary\XmlTree('hobbiesandrecreation/booksandmagazines/books.xml', [
							'known' => [
								CategoryLevel::BOOK_TYPE,
							]
						]),
						new Dictionary\Decorator(
							new Dictionary\XmlTree('hobbiesandrecreation/booksandmagazines/lifestyle_knigi_hudozhestvennaya_literatura.xml'),
							[
								'wait' => [ 'BookType' => self::getLocale('BOOKS_FICTION') ],
								'rename' => [
									'avtor_knigi_hudozhestvennaya_literatura' => 'Author',
									'populyarnaya_seriya_knigi_hudozhestvennaya_literatura' => 'Series',
								],
							]
						),
						new Dictionary\Decorator(
							new Dictionary\XmlTree('hobbiesandrecreation/booksandmagazines/lifestyle_katalog_avtorov_knigi_dlya_detej.xml'),
							[
								'wait' => [ 'BookType' => self::getLocale('BOOKS_FOR_CHILDREN') ],
								'rename' => [
									'avtor_knigi_dlya_detej' => 'Author',
									'populyarnaya_seriya_knigi_dlya_detej' => 'Series',
								],
							]
						),
					]),
				],
				'Academic books',
			]);
		});
	}
}