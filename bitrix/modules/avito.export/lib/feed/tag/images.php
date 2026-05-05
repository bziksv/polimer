<?php

namespace Avito\Export\Feed\Tag;

use Avito\Export\Concerns;
use Avito\Export\Feed;

class Images extends Tag
{
	use Concerns\HasLocale;

	protected $supported = ['S', 'F'];

	protected function defaults() : array
	{
		return [
			'name' => 'Image',
			'wrapper' => 'Images',
			'max_count' => 10,
		];
	}

	public function recommendation(Feed\Source\Context $context, Feed\Source\FetcherPool $fetcherPool) : array
	{
		return array_merge(
			$this->fieldRecommendation(),
			$this->propertyRecommendation($context, $fetcherPool)
		);
	}

	protected function fieldRecommendation() : array
	{
		return [
			[
				'TYPE' => Feed\Source\Registry::IBLOCK_FIELD,
				'FIELD' => 'DETAIL_PICTURE',
			],
			[
				'TYPE' => Feed\Source\Registry::IBLOCK_FIELD,
				'FIELD' => 'PREVIEW_PICTURE',
			],
		];
	}

	protected function propertyRecommendation(Feed\Source\Context $context, Feed\Source\FetcherPool $fetcherPool) : array
	{
		$result = [];
		$propertyTypes = [
			Feed\Source\Registry::IBLOCK_PROPERTY,
			Feed\Source\Registry::OFFER_PROPERTY,
		];

		foreach ($propertyTypes as $propertyType)
		{
			$fetcher = $fetcherPool->some($propertyType);

			foreach ($fetcher->fields($context) as $field)
			{
				if ($field->type() === 'F')
				{
					$result[] = [
						'TYPE' => $propertyType,
						'FIELD' => $field->id(),
					];
				}
			}
		}

		return $result;
	}

	public function exportMultiple(Feed\Engine\Data\TagCompiled $tag, $value, array $siblings, Feed\Source\Context $context) : void
	{
		if (empty($value)) { return; }
		if (!is_array($value)) { $value = [$value]; }

		$value = array_unique($value);

		parent::exportMultiple($tag, $value, [], $context);
	}

	public function exportSingle(Feed\Engine\Data\TagCompiled $tag, $value, array $siblings, Feed\Source\Context $context) : void
	{
		if (is_array($value)) { $value = reset($value); }
		if (empty($value)) { return; }

		if (!preg_match('#^(https?:)?//#i', $value))
		{
			$domain = $context->variable('DOMAIN');
			$value = rtrim($domain, '/') . '/' . ltrim($value, '/');
		}

		$tag->addChild(new Feed\Engine\Data\TagCompiled($this->name(), null, [
			'url' => $value,
		]));
	}
}
