<?php
namespace Avito\Export\Trading\State;

use Avito\Export\Data;
use Avito\Export\DB;
use Bitrix\Main;

class Order
{
	protected const JSON_PREFIX = 'json:';
	protected const DATE_TIME_PREFIX = 'dateTime:';

	protected $orderId;
	/** @var Collection */
	protected $collection;

	public function __construct(int $orderId)
	{
		$this->orderId = $orderId;
	}

	public function hashChanged(string $name, array $values) : bool
	{
		return $this->changed($name, $this->hashValues($values));
	}

	protected function hashValues(array $values) : string
	{
		$partials = [];

		foreach ($values as $name => $value)
		{
			$partials[] = $name . ':' . $this->stringifyValue($value);
		}

		$string = implode(';', $partials);

		if (mb_strlen($string) > 33)
		{
			$string = md5($string);
		}

		return $string;
	}

	public function anyChanged(array $values) : bool
	{
		$result = false;

		foreach ($values as $name => $value)
		{
			if ($this->changed($name, $value))
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	public function changed(string $name, $value) : bool
	{
		$model = $this->search($name);
		$value = $this->stringifyValue($value);

		if ($model === null)
		{
			$result = ((string)$value !== '');
		}
		else
		{
			$result = ($model->getValue() !== (string)$value);

			if (!$result)
			{
				$model->setTimestampX(new Main\Type\DateTime());
			}
		}

		return $result;
	}

	public function get(string $name)
	{
		$model = $this->search($name);

		if ($model === null) { return null; }

		return $this->unpackValue($model->getValue());
	}

	public function getAll() : array
	{
		$result = [];

		foreach ($this->collection() as $model)
		{
			$result[$model->getName()] = $model->getValue();
		}

		return $result;
	}

	public function set(string $name, $value) : void
	{
		$model = $this->search($name);
		$value = $this->stringifyValue($value);

		if ($value === null || $value === '') // need delete
		{
			if ($model === null) { return; }

			$this->collection()->remove($model);
			$model->delete();
		}
		else if ($model !== null)
		{
			$model->setValue($value);
			$model->setTimestampX(new Main\Type\DateTime());
		}
		else
		{
			$model = new Model([
				'ORDER_ID' => $this->orderId,
				'NAME' => $name,
				'VALUE' => $value,
				'TIMESTAMP_X' => new Main\Type\DateTime(),
			]);

			$this->collection()->add($model);
		}
	}

	public function setHash(string $name, array $values) : void
	{
		$this->set($name, $this->hashValues($values));
	}

	public function setFew(array $values) : void
	{
		foreach ($values as $name => $value)
		{
			$this->set($name, $value);
		}
	}

	public function unset(string $name) : void
	{
		$model = $this->search($name);

		if ($model === null) { return; }

		$this->collection()->remove($model);
	}

	public function save() : void
	{
		$batch = new DB\Facade\BatchInsert(RepositoryTable::class);
		$batch->run($this->makeChangedRows(), [
			'VALUE',
			'TIMESTAMP_X',
		]);

		/** @var Main\ORM\Objectify\EntityObject $item */
		foreach ($this->collection() as $item)
		{
			$item->sysPostSave();
		}
	}

	protected function search(string $name) : ?Model
	{
		$result = null;

		foreach ($this->collection() as $model)
		{
			if ($model->getName() === $name)
			{
				$result = $model;
				break;
			}
		}

		return $result;
	}

	protected function collection() : Collection
	{
		if ($this->collection === null)
		{
			$query = RepositoryTable::getList([
				'filter' => [ '=ORDER_ID' => $this->orderId ],
			]);

			$this->collection = $query->fetchCollection();
		}

		return $this->collection;
	}

	protected function stringifyValue($value) : ?string
	{
		if ($value === null) { return null; }

		if (is_scalar($value))
		{
			return (string)$value;
		}

		if (is_array($value))
		{
			return static::JSON_PREFIX . Main\Web\Json::encode($value);
		}

		if ($value instanceof Main\Type\DateTime)
		{
			return static::DATE_TIME_PREFIX . Data\DateTime::stringify($value);
		}

		throw new Main\ArgumentException(sprintf('unknown %s value type', gettype($value)));
	}

	protected function unpackValue(?string $value)
	{
		if ($value === null || $value === '') { return $value; }

		if (mb_strpos($value, static::JSON_PREFIX) === 0)
		{
			$content = mb_substr($value, mb_strlen(static::JSON_PREFIX));

			return Main\Web\Json::decode($content);
		}

		if (mb_strpos($value, static::DATE_TIME_PREFIX) === 0)
		{
			$content = mb_substr($value, mb_strlen(static::DATE_TIME_PREFIX));

			return Data\DateTime::cast($content);
		}

		return $value;
	}

	protected function makeChangedRows() : array
	{
		$result = [];

		foreach ($this->collection() as $item)
		{
			if ($item->isValueChanged() || $item->isTimestampXChanged())
			{
				$result[] = [
					'ORDER_ID' => $item->getOrderId(),
					'NAME' => $item->getName(),
					'VALUE' => $item->getValue(),
					'TIMESTAMP_X' => $item->getTimestampX(),
				];
			}
		}

		return $result;
	}
}