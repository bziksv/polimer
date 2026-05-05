<?php
namespace Avito\Export\Feed\Source\Template;

use Bitrix\Iblock;
use Avito\Export\Feed;

class Fetcher extends Feed\Source\FetcherSkeleton
	implements Feed\Source\FetcherCloneable, Feed\Source\FetcherDelayed
{
	protected $compiled = [];

	public function listener() : Feed\Source\Listener
	{
		return new Feed\Source\NoValue\Listener();
	}

	public function title() : string
	{
		return 'template';
	}

	public function modules() : array
	{
		return [ 'iblock' ];
	}

	public function order() : int
	{
		return 1000;
	}

	public function extend(array $fields, Feed\Source\Data\SourceSelect $sources, Feed\Source\Context $context) : void
	{
		foreach ($fields as $template)
		{
			$templateNode = $this->compile($template);
			$usedSources = $this->usedSources($templateNode->getChildren());

			foreach ($usedSources as $source => $sourceFields)
			{
				foreach ($sourceFields as $sourceField)
				{
					$sources->add($source, $sourceField);
				}
			}
		}
	}

	/**
	 * @param Iblock\Template\NodeBase[] $nodes
	 *
	 * @return array<string, string[]>
	 */
	protected function usedSources(array $nodes) : array
	{
		$result = [];

		foreach ($nodes as $node)
		{
			if ($node instanceof Engine\Node\Field)
			{
				$source = $node->getSource();
				$field = $node->getField();

				if (!isset($result[$source])) { $result[$source] = []; }

				$result[$source][] = $field;
			}
			else if ($node instanceof Engine\Node\FunctionField)
			{
				$childSources = $this->usedSources($node->getParameters());

				foreach ($childSources as $source => $fields)
				{
					if (!isset($result[$source]))
					{
						$result[$source] = $fields;
					}
					else
					{
						$result[$source] = array_unique(array_merge(
							$result[$source],
							$fields
						));
					}
				}
			}
		}

		return $result;
	}

	public function values(array $elements, array $parents, array $siblings, array $select, Feed\Source\Context $context) : array
	{
		$result = [];
		$regionSource = Feed\Source\Registry::REGION;

		foreach ($select as $template)
		{
			$templateNode = $this->compile($template);
			$usedSources = $this->usedSources($templateNode->getChildren());

			foreach ($elements as $elementId => $element)
			{
				$elementValues = $siblings[$elementId] ?? [];

				if (
					isset($usedSources[$regionSource])
					&& !empty($siblings[$elementId][$regionSource])
				)
				{
					foreach ($siblings[$elementId][$regionSource] as $regionId => $regionValues)
					{
						$cloneValues = $elementValues;
						$cloneValues[$regionSource] = $regionValues;

						$entity = new Engine\Entity($cloneValues);

						$result[$elementId][$regionId][$template] = $templateNode->process($entity);
					}
				}
				else
				{
					$entity = new Engine\Entity($elementValues);

					$result[$elementId][$template] = $templateNode->process($entity);
				}
			}
		}

		return $result;
	}

	protected function compile(string $template) : Engine\Node\Root
	{
		if (!isset($this->compiled[$template]))
		{
			$this->compiled[$template] = Engine\Engine::compile($template);
		}

		return $this->compiled[$template];
	}
}