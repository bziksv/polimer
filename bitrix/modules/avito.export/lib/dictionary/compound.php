<?php
namespace Avito\Export\Dictionary;

class Compound implements Dictionary
{
	protected $dictionaries;
	protected $useParent;
	protected $requestRefresh = false;

	/**
	 * @param Dictionary[] $dictionaries
	 * @param array $parameters
	 */
	public function __construct(array $dictionaries, array $parameters = [])
	{
		$this->dictionaries = $dictionaries;
		$this->useParent = $parameters['parent'] ?? true;
	}

	public function setTypeRequestRefresh(bool $type) : void
	{
		$this->requestRefresh = $type;
	}

	public function useParent() : bool
	{
		return $this->useParent;
	}

	public function known(array $attributes) : array
	{
		$partials = [];

		foreach ($this->dictionaries as $dictionary)
		{
			$partials[] = $dictionary->known($attributes);
		}

		return !empty($partials) ? array_merge(...$partials) : [];
	}

	public function attributes(array $values = []) : array
	{
		$partials = [];
		$lastException = null;

		foreach ($this->dictionaries as $dictionary)
		{
			try
			{
				$partials[] = $dictionary->attributes($values);
			}
			/** @noinspection PhpRedundantCatchClauseInspection */
			catch (Exceptions\AttributeRequired|Exceptions\UnknownValue $exception)
			{
				$lastException = $exception;
			}
		}

		$result = !empty($partials) ? array_merge(...$partials) : [];
		$result = array_diff($result, array_keys($values));
		$result = array_unique($result);
		$result = array_values($result);

		if (empty($result) && $lastException !== null)
		{
			throw $lastException;
		}

		return $result;
	}

	public function variants(string $attribute, array $values = []) : ?array
	{
		$emptyFixedResult = null;
		$result = null;
		$lastException = null;

		foreach ($this->dictionaries as $dictionary)
		{
			try
			{
				$result = $dictionary->variants($attribute, $values);

				if ($result === null && !empty($values) && $dictionary instanceof XmlCascade)
				{
					$emptyFixedResult[$attribute] = $values[$attribute];
				}

				if ($result !== null) { break; }
			}
			/** @noinspection PhpRedundantCatchClauseInspection */
			catch (Exceptions\AttributeRequired|Exceptions\UnknownValue $exception)
			{
				$lastException = $exception;
			}

			if ($emptyFixedResult !== null && $this->requestRefresh)
			{
				$value = reset($emptyFixedResult);
				$emptyAttributeName = key($emptyFixedResult);

				throw new Exceptions\EmptyChildTag($emptyAttributeName, $value);
			}
		}

		if ($result === null && $lastException !== null)
		{
			throw $lastException;
		}

		return $result;
	}
}