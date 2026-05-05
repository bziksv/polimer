<?php
namespace Avito\Export\Dictionary;

use Avito\Export\Dictionary\File\XmlFile;
use Bitrix\Main\Application;
use Bitrix\Main\Text\Encoding;
use Avito\Export\Utils;

/* for our source */
class XmlTree implements Dictionary
{
	protected $file;
	protected $filepath;
	protected $childFiles;
	protected $useParent;
	protected $known;

	public function __construct($file, array $parameters = [])
	{
		$this->file = new File\XmlFile($file);
		$this->filepath = is_string($file) ? $file : null;
		$this->childFiles = [];
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

		foreach ($this->flatChildren($children) as [$child, $rename])
		{
			$name = $rename ?? $child->getName();

			if (!isset($attributesMap[$name])) { continue; }

			$result[] = [
				'name' => $name,
				'value' => $this->nodeValue($child),
				'children' => $this->searchKnown($child, array_diff_key($attributesMap, [ $name => true ])),
				'oldNames' => $this->nodeOldNames($child),
				'tags' => $this->searchMultiple($child, $attributesMap),
			];
		}

		return $result;
	}

	public function searchMultiple(\SimpleXMLElement $node, array $categoryTags) : array
	{
		$children = $node->children();
		if ($children === null) { return []; }

		$multiple = [];

		foreach ($this->flatChildren($children) as [$child, $rename])
		{
			if ($child['multiple'] !== null)
			{
				$multiple[$child->getName()] = [ 'multiple' => true, 'wrapper' => true ];
			}

			if (!isset($categoryTags[$child->getName()]))
			{
				$multiple += $this->searchMultiple($child, $categoryTags);
			}
		}

		return $multiple;
	}

	public function attributes(array $values = []) : array
	{
		try
		{
			$values = $this->input($values);
			$used = [];

			foreach ($this->searchLevel($values, $this->file->root()) as $tag)
			{
				$children = $tag->children();

				if ($children === null) { continue; }

				foreach ($this->flatChildren($children) as [$child, $rename])
				{
					$used[$rename ?? $child->getName()] = true;
				}
			}

			$result = array_keys(array_diff_key($used, $values));
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
		$result = null;
		$values = $this->input($values);
		$chain = $this->searchLevel($values, $this->file->root());

		foreach (array_reverse($chain) as $tag)
		{
			$children = $tag->children();

			if ($children === null) { continue; }

			foreach ($this->flatChildren($children) as [$child, $rename])
			{
				$name = $rename ?? $child->getName();

				if ($name !== $attribute) { continue; }

				if ($child['variable'] !== null)
				{
					$result = [];
					break;
				}

				if ($result === null) { $result = []; }

				$result[] = $this->nodeValue($child);
			}

			if ($result !== null) { break; }
		}

		return $this->output($result);
	}

	protected function searchLevel(array $values, \SimpleXMLElement $root) : array
	{
		$children = $root->children();

		if ($children === null) { return []; }

		$matched = [];
		$empty = [];
		$found = [];
		$children = $this->flatChildren($children);

		foreach ($children as [$child, $rename])
		{
			$name = $rename ?? $child->getName();

			if (!isset($values[$name])) { continue; }

			if ($child['wait'] !== null)
			{
				// nothing
			}
			else if (Utils\Value::isEmpty($values[$name]))
			{
				$empty[$name] = true;
			}
			else
			{
				$found[$name] = true;
			}

			if (is_array($values[$name]))
			{
				if (in_array($this->nodeValue($child), $values[$name], true))
				{
					$matched[$name] = $child;
				}
			}
			else if (
				$child['variable'] !== null
				|| (string)$values[$name] === $this->nodeValue($child)
			)
			{
				$matched[$name] = $child;
			}
		}

		if (empty($matched))
		{
			if (!empty($empty))
			{
				$name = key($empty);

				throw new Exceptions\AttributeRequired($name);
			}

			if (!empty($found))
			{
				$name = key($found);

				throw new Exceptions\UnknownValue($name, $this->output($values[$name]));
			}
		}

		$left = array_diff_key($values, $matched);
		$result = [
			$root,
		];

		foreach ($matched as $child)
		{
			$childMatched = $this->searchLevel($left, $child);

			if (!empty($childMatched))
			{
				array_push($result, ...$childMatched);
			}
			else
			{
				$result[] = $child;
			}
		}

		return $result;
	}

	/**
	 * @param \SimpleXMLElement $children
	 *
	 * @return array{\SimpleXMLElement, string|null}[]
	 */
	protected function flatChildren(\SimpleXMLElement $children) : array
	{
		$result = [];

		foreach ($children as $child)
		{
			if ($child['name'] !== null || $child['variable'] !== null)
			{
				$result[] = [$child];
			}
			else if ($child['file'] !== null)
			{
				$fileChildren = $this->childFile((string)$child['file'])->root()->children();

				if ($fileChildren === null) { continue; }

				$fileChildren = $this->flatChildren($fileChildren);
				$rename = $child['rename'] !== null ? $child->getName() : null;
				$multiple = $child['multiple'] !== null;

				/** @var \SimpleXMLElement $fileChild */
				foreach ($fileChildren as $key => [$fileChild])
				{
					if ($multiple && $key === 0 && $fileChild['multiple'] === null)
					{
						$fileChild->addAttribute('multiple', 'true');
					}
					$result[] = [$fileChild, $rename];
				}
			}
			else
			{
				$nextChildren = $child->children();

				if ($nextChildren !== null && $nextChildren->count() > 0)
				{
					$nextChildren = $this->flatChildren($nextChildren);

					foreach ($nextChildren as [$fileChild, $rename])
					{
						$result[] = [$fileChild, $rename];
					}
				}
				else
				{
					$result[] = [$child];
				}
			}
		}

		return $result;
	}

	protected function childFile(string $path) : XmlFile
	{
		if ($this->filepath !== null && mb_strpos($path, '/') !== 0)
		{
			$path = dirname($this->filepath) . DIRECTORY_SEPARATOR . $path;
		}
		if (isset($this->childFiles[$path])) { return $this->childFiles[$path]; }

		$file = new XmlFile($path);
		$this->childFiles[$path] = $file;

		return $file;
	}

	protected function nodeValue(\SimpleXMLElement $node) : string
	{
		return (string)($node['name'] ?? $node);
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