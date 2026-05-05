<?php
namespace Avito\Export\Push\Engine\Steps\Stamp;

class Collection extends EO_Repository_Collection
{
	/** @return array<string, Collection> */
	public function groupByType() : array
	{
		$result = [];

		foreach ($this->getAll() as $model)
		{
			$type = $model->getType();

			if (!isset($result[$type]))
			{
				$result[$type] = new static();
			}

			$result[$type]->add($model);
		}

		return $result;
	}

	public function primaries() : array
	{
		$result = [];

		foreach ($this->getAll() as $model)
		{
			$result[$model->getElementId()] = $model->getPrimary();
		}

		return $result;
	}

	public function filterStatus(string $status) : Collection
	{
		$result = new static();

		foreach ($this->getAll() as $model)
		{
			if ($model->getStatus() !== $status) { continue; }

			$result->add($model);
		}

		return $result;
	}

	/** @return Collection[] */
	public function chunk(int $size) : array
	{
		$result = [];

		foreach (array_chunk($this->getAll(), $size) as $chunk)
		{
			$collection = new static();

			foreach ($chunk as $model)
			{
				$collection->add($model);
			}

			$result[] = $collection;
		}

		return $result;
	}

	public function increaseRepeat() : void
	{
		foreach ($this->getAll() as $model)
		{
			$model->increaseRepeat();
		}
	}

	public function queryFilter() : ?array
	{
		$elementsByRegion = [];

		foreach ($this->getAll() as $stamp)
		{
			$regionId = $stamp->getRegionId();

			if (!isset($elementsByRegion[$regionId]))
			{
				$elementsByRegion[$regionId] = [];
			}

			$elementsByRegion[$regionId][] = $stamp->getElementId();
		}

		$partials = [];

		foreach ($elementsByRegion as $regionId => $elementIds)
		{
			$partials[] = [
				'=ELEMENT_ID' => $elementIds,
				'=REGION_ID' => $regionId,
			];
		}

		if (empty($partials)) { return null; }

		if (count($partials) === 1)
		{
			return reset($partials);
		}

		return [ 'LOGIC' => 'OR' ] + $partials;
	}
}