<?php
namespace Avito\Export\Api;

use Avito\Export\Assert;
use Avito\Export\Utils\Field;

abstract class Response
{
	protected $values;
	protected $model = [];
	protected $collection = [];

	public function __construct(array $values)
	{
		$this->values = $values;
	}

	public function rawData() : array
	{
		return $this->values;
	}

	protected function getValue(string $key)
	{
		return Field::getChainValue($this->values, $key);
    }

	protected function requireValue(string $key)
	{
		$value = $this->getValue($key);

		Assert::notNull($value, sprintf('response[%s]', $key));

		return $value;
	}

	/**
	 * @template T
	 *
	 * @param string $key
	 * @param class-string<T> $className
	 *
	 * @return T|null
	 */
	protected function getModel(string $key, string $className) : ?Response
	{
		if (!isset($this->model[$key]))
		{
			$this->model[$key] = $this->buildModel($key, $className);
		}

		return $this->model[$key];
	}

	/**
	 * @template T
	 *
	 * @param string $key
	 * @param class-string<T> $className
	 *
	 * @return T
	 */
	protected function requireModel(string $key, string $className) : Response
	{
		$model = $this->getModel($key, $className);

		Assert::notNull($model, sprintf('response[%s]', $key));

		return $model;
	}

	protected function buildModel(string $key, string $className) : ?Response
	{
		$value = $this->getValue($key);

		if ($value === null) { return null; }

		Assert::isSubclassOf($className, self::class);

		return new $className($value);
	}

	/**
	 * @template T
	 *
	 * @param string $key
	 * @param class-string<T> $className
	 *
	 * @return T|null
	 */
	protected function getCollection(string $key, string $className) : ?ResponseCollection
	{
		if (!isset($this->collection[$key]))
		{
			$this->collection[$key] = $this->buildCollection($key, $className);
		}

		return $this->collection[$key];
	}

	/**
	 * @template T
	 *
	 * @param string $key
	 * @param class-string<T> $className
	 *
	 * @return T
	 */
	protected function requireCollection(string $key, string $className) : ResponseCollection
	{
		$collection = $this->getCollection($key, $className);

		Assert::notNull($collection, sprintf('response[%s]', $key));

		return $collection;
	}

	protected function buildCollection(string $key, string $className) : ?ResponseCollection
	{
		$value = $this->getValue($key);

		if ($value === null) { return null; }

		Assert::isSubclassOf($className, ResponseCollection::class);

		return new $className($value);
	}
}