<?php
namespace Avito\Export\Admin\Property;

use Bitrix\Main;
use Avito\Export\Concerns;
use Avito\Export\Structure;

class CategoryProvider
{
	use Concerns\HasLocale;
	use Concerns\HasOnceStatic;

	public const VALUE_GLUE = ' / ';
	public const VALUE_ESCAPE = '/';

	/** @var Structure\Index */
	protected static $categoryIndex;

	public static function exportFields() : array
	{
		return [
			[
				'ID' => 'category',
				'TITLE' => self::getLocale('EXPORT_FIELD_CATEGORY', null, 'category'),
			],
			[
				'ID' => 'goodsType',
				'TITLE' => self::getLocale('EXPORT_FIELD_GOODS_TYPE', null, 'goodsType'),
			],
		];
	}

	public static function exportValue($value, string $field)
	{
		if (is_array($value['VALUE'])) { $value['VALUE'] = implode(static::VALUE_GLUE, $value['VALUE']); }

		$path = trim($value['VALUE']);

		if ($path === '') { return null; }

		$tags = static::valueTags($path) ?? static::fallbackTags($path);

		if ($field === 'category')
		{
			return $tags;
		}

		if ($field === 'goodsType')
		{
			return $tags[Structure\CategoryLevel::GOODS_TYPE] ?? null;
		}

		throw new Main\ArgumentException(sprintf('unknown avito category embedded field %s', $field));
	}

	protected static function valueTags(string $value) : ?array
	{
		return self::onceStatic('valueTags', static function($value) {
			try
			{
				$nameFinder = new Structure\NameFinder();
				$chain = $nameFinder->path(static::categoryIndex(), $value);
				$tags = [];

				foreach ($chain as $category)
				{
					if (!($category instanceof Structure\CategoryLevel)) { continue; }

					$level = $category->categoryLevel();

					if ($level === null) { continue; }

					$tags[$level] = $category->name();
				}
			}
			catch (Main\ArgumentException $exception)
			{
				trigger_error($exception->getMessage(), E_USER_WARNING);
				$tags = null;
			}

			return $tags;
		}, $value);
	}

	protected static function fallbackTags(string $value) : array
	{
		$partials = explode(static::VALUE_GLUE, $value);

		if (empty($partials)) { return []; }

		return array_filter([
			Structure\CategoryLevel::CATEGORY => $partials[1] ?? null,
			Structure\CategoryLevel::GOODS_TYPE => $partials[max(count($partials) - 1, 2)] ?? null,
		]);
	}

	/** @noinspection PhpVariableIsUsedOnlyInClosureInspection */
	public static function search(string $query, array $parameters = []) : array
	{
		$found = new \SplObjectStorage();

		$visitor = new Structure\TreeReducer(static function(array $carry, Structure\Category $category, array $chain) use ($query, $found, $parameters) {
			if (count($category->children()) > 0) { return $carry; }

			if (mb_stripos($category->name(), $query) !== false)
			{
				$match = true;
			}
			else
			{
				$match = false;

				/** @var Structure\Category $parent */
				foreach ($chain as $parent)
				{
					if (mb_stripos($parent->name(), $query) !== false)
					{
						$match = true;
						break;
					}
				}
			}

			if ($match)
			{
				$parentChain = [];

				foreach ($chain as $parent)
				{
					if (!$found->contains($parent))
					{
						$found->attach($parent, true);
						$carry[] = static::categoryToVariant($parent, $parentChain, $parameters);
					}

					$parentChain[] = $parent;
				}

				$carry[] = static::categoryToVariant($category, $chain, $parameters);
			}

			return $carry;
		});

		return $visitor->reduce(static::categoryIndex(), []);
	}

	protected static function categoryToVariant(Structure\Category $category, array $chain, array $parameters = []) : array
	{
		$depth = count($chain);
		$nameChain = array_map(static function(Structure\Category $category) { return $category->name(); }, $chain);
		$nameChain[] = $category->name();
		$nameChain = array_map(static function(string $name) { return static::escapeValue($name); }, $nameChain);

		return [
			'ID' => implode(static::VALUE_GLUE, $nameChain),
			'VALUE' => ($depth > 0 ? str_repeat('..' , $depth) : '') . $category->name(),
			'DISABLED' => $depth === 0,
			'DEPTH' => $depth,
		];
	}

	protected static function escapeValue(string $name) : string
	{
		return str_replace(static::VALUE_ESCAPE, '\\' . static::VALUE_ESCAPE, $name);
	}

	protected static function categoryIndex() : Structure\Category
	{
		if (static::$categoryIndex === null)
		{
			static::$categoryIndex = new Structure\DictionaryCategoryProxy(new Structure\Index());
		}

		return static::$categoryIndex;
	}
}