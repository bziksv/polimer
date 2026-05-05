<?php
namespace Yandex\Market\Export\Collection\Strategy;

use Yandex\Market\Export\Collection;
use Yandex\Market\Export\CollectionProduct;
use Yandex\Market\Reference\Concerns;

class ProductFilter implements Strategy, StrategyFilterable
{
	use Concerns\HasOnce;
	use Concerns\HasMessage;

	protected $productCollection;
	protected $values;

	public function getTitle()
	{
		return self::getMessage('TITLE');
	}

	public function getFields()
	{
		return [
			'URL' => [
				'TYPE' => 'string',
				'NAME' => self::getMessage('URL'),
				'MANDATORY' => 'Y',
			],
			'PICTURE' => [
				'TYPE' => 'file',
				'NAME' => self::getMessage('PICTURE'),
                'SETTINGS' => [
                    'SIZE' => 20,
                    'LIST_WIDTH' => 200,
                    'LIST_HEIGHT' => 200,
                    'MAX_SHOW_SIZE' => 0,
                    'MAX_ALLOWED_SIZE' => 10485760,
                    'EXTENSIONS' =>
                        array (
                            'jpg' => true,
                            'gif' => true,
                            'png' => true,
                            'jpeg' => true,
                        ),
                    'TARGET_BLANK' => 'N',
                ]
			],
			'DESCRIPTION' => [
				'TYPE' => 'html',
				'NAME' => self::getMessage('DESCRIPTION'),
			],
		];
	}

	public function setValues(array $values)
	{
		$this->values = $values;
	}

	public function setProductCollection(CollectionProduct\Collection $productCollection)
	{
		$this->productCollection = $productCollection;
	}

	public function getFeedCollections()
	{
		return [
			new Collection\Data\FeedCollection($this->getFeedFields(), $this->productCollection),
		];
	}

	protected function getFeedFields()
	{
		$picture = \CFile::GetFileArray((int)$this->values['PICTURE']);

		return [
			'ID' => $this->values['COLLECTION_ID'],
			'NAME' => $this->values['NAME'],
			'URL' => $this->values['URL'],
			'PICTURE' => $picture ? \CFile::GetFileSRC($picture) : null,
			'DESCRIPTION' => $this->values['DESCRIPTION'],
		];
	}
}