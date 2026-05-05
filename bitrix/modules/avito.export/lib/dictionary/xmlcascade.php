<?php
namespace Avito\Export\Dictionary;

use Bitrix\Main\Application;
use Bitrix\Main\Text\Encoding;
use Avito\Export\Utils;

/* for avito source */
class XmlCascade implements Dictionary
{
	protected $file;
	protected $useParent;
	protected $known;

	public function __construct($file, array $parameters = [])
	{
		$this->file = new File\XmlFile($file);
		$this->useParent = $parameters['parent'] ?? true;
		$this->known = $parameters['known'] ?? [];
	}

	public function useParent() : bool
	{
		return $this->useParent;
	}

	public function known(array $attributes) : array
	{
		$attributesMap = array_flip(array_intersect($attributes, $this->known));

		if (empty($attributesMap)) { return []; }

		return $this->output($this->searchKnown($this->file->root(), $attributesMap));
	}

	protected function searchKnown(\SimpleXMLElement $node, array $attributesMap) : array
	{
		if (empty($attributesMap)) { return []; }

		$result = [];
		$children = $node->children();

		if ($children === null) { return []; }

		foreach ($children as $child)
		{
			$name = $child->getName();

			if (!isset($attributesMap[$name])) { continue; }

			$result[] = [
				'name' => $name,
				'value' => (string)$child['name'],
				'children' => $this->searchKnown($child, array_diff_key($attributesMap, [ $name => true ])),
				'oldNames' => $this->nodeOldNames($child),
			];
		}

		return $result;
	}

	public function attributes(array $values = []) : array
	{
		try
		{
			$values = $this->input($values);
			$tag = $this->findTag($values);

			if ($tag === null) { return []; }

			$children = $tag->children();

			if ($children === null || count($children) === 0) { return []; }

			$first = $children[0];

			$result = [ $first->getName() ];
		}
		catch (Exceptions\AttributeRequired $exception)
		{
			if (array_key_exists($exception->attributeName(), $values))
			{
				throw $exception;
			}

			$result = [ $exception->attributeName() ];
		}

		return $result;
	}

	public function variants(string $attribute, array $values = []) : ?array
	{
		$values = $this->input($values);
		$tag = $this->findTag($values, $attribute);

		if ($tag === null) { return null; }

		$result = [];

		foreach ($tag->children() as $child)
		{
			$name = $child->getName();

			if ($name !== $attribute)
			{
				throw new Exceptions\AttributeRequired($name);
			}

			$result[] = (string)$child['name'];
		}

		return $this->output($result);
	}

	protected function findTag(array $values, string $until = null) : ?\SimpleXMLElement
	{
		$left = $values;
		$tag = $this->file->root();
		$result = null;

		do
		{
			if ($until === null && empty($left))
			{
				$result = $tag;
				break;
			}

			$level = $tag->children();

			if (count($level) === 0)
			{
				$result = null;
				break;
			}

			$matched = null;
			$name = null;

			foreach ($level as $child)
			{
				$name = $child->getName();

				if ($until !== null && $until === $name)
				{
					$result = $tag;
					break;
				}

				if (!isset($left[$name]) || Utils\Value::isEmpty($left[$name]))
				{
					throw new Exceptions\AttributeRequired($name);
				}

				if (is_array($left[$name]))
				{
					if (in_array((string)$child['name'], $left[$name], true))
					{
						$matched = $child;
						break;
					}
				}
				else if ((string)$left[$name] === (string)$child['name'])
				{
					$matched = $child;
					break;
				}
			}

			if ($result !== null) { break; }

			if ($matched === null)
			{
				throw new Exceptions\UnknownValue($name, $this->output($left[$name]));
			}

			$tag = $matched;
			unset($left[$name]);
		}
		while (true);

		return $result;
	}

	protected function nodeOldNames(\SimpleXMLElement $node) : array
	{
		if (empty($node['oldNames'])) { return []; }

		return explode('|', (string)$node['oldNames']);
	}

	/**
	 * @template T
	 * @param T $parameter
	 *
	 * @return T
	 */
	protected function input($parameter)
	{
		if (Application::isUtfMode()) { return $parameter; }

		return Encoding::convertEncoding($parameter, LANG_CHARSET, 'UTF-8');
	}

	/**
	 * @template T
	 * @param T $parameter
	 *
	 * @return T
	 */
	protected function output($parameter)
	{
		if (Application::isUtfMode()) { return $parameter; }

		return Encoding::convertEncoding($parameter, 'UTF-8', LANG_CHARSET);
	}
}