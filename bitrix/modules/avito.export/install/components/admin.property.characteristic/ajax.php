<?php
namespace Avito\Export\Components;

use Bitrix\Main;
use Avito\Export;
use Avito\Export\Admin\Property\FormCategory;
use Avito\Export\Dictionary\Exceptions;

/** @noinspection PhpUnused */
class AdminPropertyCharacteristicAjax extends Main\Engine\Controller
{
	/** @noinspection PhpUnused */
	public function attributesAction(array $category, array $values = []) : array
	{
		return $this->actionWrapper(function() use ($category, $values) {
			$this->loadModule();

			$chain = $this->category($category, $values);
			$dictionary = $this->dictionary($chain);

			$values = $this->categoryTagValues($chain) + $values;
			return $dictionary->attributes($values);
		});
	}

	/** @noinspection PhpUnused */
	public function variantsAction(array $category, string $attribute, array $values = []) : array
	{
		return $this->actionWrapper(function() use ($category, $attribute, $values) {
			$this->loadModule();

			$chain = $this->category($category, $values);
			$dictionary = $this->dictionary($chain);
			$values = $this->categoryTagValues($chain) + $values;

			return [
				'variants' => $this->dictionaryVariants($dictionary, $attribute, $values),
				'multiple' => $this->isTagMultiple(new Export\Structure\CategoryChain($chain), $attribute),
			];
		});
	}

	protected function dictionaryVariants(Export\Dictionary\Dictionary $dictionary, string $attribute, array $values = []) : array
	{
		try
		{
			return (array)$dictionary->variants($attribute, $values);
		}
		catch (Exceptions\EmptyChildTag $exception)
		{
			return [];
		}
	}

	protected function isTagMultiple(Export\Structure\CategoryChain $categoryChain, string $attribute) : bool
	{
		$tags = $categoryChain->tags();

		if (!isset($tags[$attribute])) { return false; }

		return $tags[$attribute]->multiple();
	}

	/** @noinspection PhpUnused */
	public function refreshAction(array $category, string $from, array $values = []) : array
	{
		return $this->actionWrapper(function() use ($category, $from, $values) {
			$this->loadModule();

			$chain = $this->category($category);
			$dictionary = $this->dictionary($chain, 'change');
			$categoryValues = $this->categoryTagValues($chain);

			$isFoundFrom = false;
			$result = [];

			foreach ($values as $name => $value)
			{
				$variants = [];

				try
				{
					if ($name === $from)
					{
						$isFoundFrom = true;
						continue;
					}

					if (!$isFoundFrom) { continue; }

					$variants = (array)$dictionary->variants($name, $categoryValues + $values);
				}
				catch (Exceptions\EmptyChildTag $exception)
				{
					continue;
				}

				if (!empty($variants) && !in_array($value, $variants, true))
				{
					$values[$name] = reset($variants);
				}

				$result[$name] = $variants;
			}

			return $result;
		});
	}

	protected function actionWrapper(callable $function) : array
	{
		try
		{
			return [
				'status' => 'ok',
				'data' => call_user_func($function),
			];
		}
		catch (Main\SystemException $exception)
		{
			return [
				'status' => 'error',
				'message' => $exception->getMessage(),
			];
		}
	}

	protected function loadModule() : void
	{
		if (!Main\Loader::includeModule('avito.export'))
		{
			throw new Main\SystemException('Module avito.export is required');
		}
	}

	/**
	 * @param array $form
	 * @param array $values
	 *
	 * @return Export\Structure\Category[]
	 */
	protected function category(array $form, array $values = []) : array
	{
		$categoryProxy = new Export\Structure\DictionaryCategoryProxy(new Export\Structure\Index());
		$finder = new Export\Structure\NameFinder();
		$chain = $finder->path($categoryProxy, $this->categoryValue($form));

		if (empty($chain)) { return $chain; }

		$nested = $finder->tags(end($chain), $values);

		if (!empty($nested))
		{
			array_push($chain, ...$nested);
		}

		return $chain;
	}

	protected function categoryValue(array $form) : string
	{
		$firstException = null;

		if (empty($form))
		{
			throw new Main\SystemException($this->lang('CATEGORY_PROPERTIES_MISSING'));
		}

		foreach ($form as $categoryOptions)
		{
			$behavior = FormCategory\Registry::make($categoryOptions['type']);

			try
			{
				return $behavior->value($categoryOptions);
			}
			catch (Main\ArgumentException $exception)
			{
				if ($firstException === null)
				{
					$firstException = $exception;
				}
			}
		}

		throw $firstException ?? new Main\SystemException($this->lang('CANT_FIND_CATEGORY_VALUE'));
	}

	/**
	 * @param Export\Structure\Category[] $chain
	 * @param string                      $typeRequest
	 *
	 * @return Export\Dictionary\Dictionary
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function dictionary(array $chain, string $typeRequest = '') : Export\Dictionary\Dictionary
	{
		$partials = [];

		if (!empty($chain))
		{
			$partials[] = new Export\Dictionary\CategoryLevel(end($chain));
		}

		foreach (array_reverse($chain) as $one)
		{
			$dictionary = $one->dictionary();

			if ($typeRequest === 'change' && $dictionary instanceof Export\Dictionary\Compound)
			{
				$dictionary->setTypeRequestRefresh(true);
			}

			$partials[] = $dictionary;

			if (!$dictionary->useParent()) { break; }
		}

		if (empty($partials))
		{
			throw new Main\SystemException('cant load dictionaries from category chain');
		}

		return count($partials) > 1 ? new Export\Dictionary\Compound($partials) : reset($partials);
	}

	protected function lang(string $key) : string
	{
		return Main\Localization\Loc::getMessage('AVITO_EXPORT_ADMIN_PROPERTY_CHARACTERISTIC_' . $key) ?: $key;
	}

	protected function categoryTagValues(array $chain) : array
	{
		$tags = [];

		foreach ($chain as $category)
		{
			if (!($category instanceof Export\Structure\CategoryLevel)) { continue; }

			$level = $category->categoryLevel();

			if ($level === null) { continue; }

			$tags[$level] = $category->name();
		}

		return $tags;
	}
}