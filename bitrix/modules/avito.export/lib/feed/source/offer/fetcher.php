<?php
namespace Avito\Export\Feed\Source\Offer;

use Avito\Export\Concerns;
use Avito\Export\Feed\Source;

class Fetcher extends Source\Element\Fetcher
	implements Source\FetcherMergable
{
	use Concerns\HasLocale;

	public function listener() : Source\Listener
	{
		return new Source\NoValue\Listener();
	}

	public function title() : string
	{
		return self::getLocale('TITLE');
	}

	public function fields(Source\Context $context) : array
	{
		return $this->once('fields', function() use ($context) {
			$iblockId = $context->offerIblockId();

			if ($iblockId === null) { return []; }

			return $this->elementFields($iblockId);
		});
	}

	public function select(array $fields) : array
	{
		return [
			'ELEMENT' => $fields,
			'OFFER' => $fields,
		];
	}

	public function filter(array $conditions, Source\Context $context) : array
	{
		return [
			'OFFER' => Source\Routine\QueryFilter::make($conditions, $this->fields($context)),
		];
	}

	public function values(array $elements, array $parents, array $siblings, array $select, Source\Context $context) : array
	{
		$result = [];

		foreach ($elements as $elementId => $element)
		{
			$result[$elementId] = $this->fieldValues($element, $select);
		}

		return $result;
	}

	protected function elementsSearchIblockIds(Source\Context $context) : array
	{
		if ($context->hasOffers())
		{
			return [
				$context->iblockId(),
				$context->offerIblockId(),
			];
		}

		return [
			$context->iblockId(),
		];
	}

	public function merge(array $sourceValues, array $mergeMap) : array
	{
		return Source\Routine\ValueMerger::merge($sourceValues, $mergeMap);
	}
}