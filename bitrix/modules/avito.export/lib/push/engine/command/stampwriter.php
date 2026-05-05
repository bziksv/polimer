<?php
namespace Avito\Export\Push\Engine\Command;

use Avito\Export\Config;
use Avito\Export\DB;
use Avito\Export\Push;
use Avito\Export\Utils\ArrayHelper;
use Bitrix\Main;

class StampWriter
{
	protected $setup;

	public function __construct(Push\Setup\Model $setup)
	{
		$this->setup = $setup;
	}

	/**
	 * @param array $feedOffers
	 * @param Push\Engine\Data\TargetValues[] $targetValues
	 *
	 * @return void
	 */
	public function write(array $feedOffers, array $targetValues) : void
	{
		$feedOffers = $this->signFeedOffers($feedOffers);
		$primaryMap = ArrayHelper::column($feedOffers, 'PRIMARY');

		[$storedPrimaryMap, $storedValues] = $this->stored(array_unique(array_column($feedOffers, 'ELEMENT_ID')));
		[$changedPrimaryMap, $changedValues] = $this->changed($primaryMap, $targetValues, $storedPrimaryMap, $storedValues);
		$sameValues = $this->same($targetValues, $changedValues);

		$this->insert($this->makeRows($feedOffers, $changedPrimaryMap, $changedValues));
		$this->touch(array_intersect_key($feedOffers, $sameValues));
	}

	protected function signFeedOffers(array $feedOffers) : array
	{
		$result = [];

		foreach ($feedOffers as $feedOffer)
		{
			$sign = $feedOffer['ELEMENT_ID'] . '-' . $feedOffer['REGION_ID'];

			$result[$sign] = $feedOffer;
		}

		return $result;
	}

    protected function same(array $targetValues, array $changedValues) : array
    {
        $result = [];

		/** @var Push\Engine\Data\TargetValues $values */
        foreach ($targetValues as $key => $values)
        {
            if (!isset($changedValues[$key]))
            {
                $result[$key] = $values;
            }
            else if (count($changedValues[$key]) !== $values->count())
            {
                $result[$key] = array_diff_key($values->toArray(), $changedValues[$key]);
            }
        }

        return $result;
    }

	protected function stored(array $elementIds) : array
	{
		if (empty($elementIds)) { return [ [], [] ]; }

		$values = [];
		$primaryMap = [];

		$query = Push\Engine\Steps\Stamp\RepositoryTable::getList([
			'filter' => [
				'=PUSH_ID' => $this->setup->getId(),
				'=ELEMENT_ID' => $elementIds,
				[
					'LOGIC' => 'OR',
					[ '=STATUS' => Push\Engine\Steps\Stamp\RepositoryTable::STATUS_WAIT ],
					[
						'=STATUS' => Push\Engine\Steps\Stamp\RepositoryTable::STATUS_READY,
						'>=SUBMITTED_AT' => $this->repeatReadyDate(),
					],
					[
						'=STATUS' => Push\Engine\Steps\Stamp\RepositoryTable::STATUS_FAILED,
						'>=SUBMITTED_AT' => $this->repeatFailedDate(),
					],
					[
						'=STATUS' => Push\Engine\Steps\Stamp\RepositoryTable::STATUS_UNPUBLISHED,
						'>=SUBMITTED_AT' => $this->repeatUnpublishedDate(),
					],
				],
			],
			'select' => [
				'ELEMENT_ID',
				'REGION_ID',
				'TYPE',
				'PRIMARY',
				'VALUE',
			],
		]);

		while ($row = $query->fetch())
		{
			$sign = $row['ELEMENT_ID'] . '-' . $row['REGION_ID'];

			if (!isset($values[$sign])) { $values[$sign] = []; }

			$values[$sign][$row['TYPE']] = $row['VALUE'];
			$primaryMap[$sign] = $row['PRIMARY'];
		}

		return [$primaryMap, $values];
	}

	protected function changed(array $primaryMap, array $targetValues, array $storedPrimaryMap, array $storedValues) : array
	{
		// primary changes

		$changedPrimaryMap = array_diff_assoc($primaryMap, $storedPrimaryMap);
		$changedValues = array_intersect_key($targetValues, $changedPrimaryMap);

		// value changes

		foreach ($targetValues as $sign => $values)
		{
			if (isset($changedValues[$sign])) { continue; }

			foreach ($values as $type => $value)
			{
				$stored = $storedValues[$sign][$type] ?? null;

				if ((string)$stored === (string)$value) { continue; }

				if (!isset($changedValues[$sign])) { $changedValues[$sign] = []; }

				$changedPrimaryMap[$sign] = $primaryMap[$sign];
				$changedValues[$sign][$type] = $value;
			}
		}

		return [$changedPrimaryMap, $changedValues];
	}

	protected function makeRows(array $feedOffers, array $primaryMap, array $targetValues) : array
	{
		$result = [];
		$common = [
			'PUSH_ID' => $this->setup->getId(),
			'TIMESTAMP_X' => new Main\Type\DateTime(),
			'SUBMITTED_AT' => new Main\Type\DateTime(),
		];

		foreach ($targetValues as $sign => $values)
		{
			if (!isset($feedOffers[$sign])) { continue; }

			$feedOffer = $feedOffers[$sign];

			foreach ($values as $type => $value)
			{
				$result[] = $common + [
					'ELEMENT_ID' => $feedOffer['ELEMENT_ID'],
					'REGION_ID' => $feedOffer['REGION_ID'],
					'TYPE' => $type,
					'PRIMARY' => $primaryMap[$sign],
					'VALUE' => $value,
					'STATUS' => Push\Engine\Steps\Stamp\RepositoryTable::STATUS_WAIT,
					'REPEAT' => 0,
				];
			}
		}

		return $result;
	}

	protected function insert(array $rows) : void
	{
		foreach (array_chunk($rows, 500) as $rowsChunk)
		{
			$batch = new DB\Facade\BatchInsert(Push\Engine\Steps\Stamp\RepositoryTable::class);
			$batch->run($rowsChunk, [
				'PRIMARY',
				'VALUE',
				'STATUS',
				'REPEAT',
				'TIMESTAMP_X',
			]);
		}
	}

	protected function touch(array $changedOffers) : void
	{
		foreach (array_chunk($changedOffers, 500) as $changedChunk)
		{
			foreach (ArrayHelper::groupBy($changedChunk, 'REGION_ID') as $regionId => $regionChunk)
			{
				$batch = new DB\Facade\BatchUpdate(Push\Engine\Steps\Stamp\RepositoryTable::class);
				$batch->run([
					'filter' => [
						'=PUSH_ID' => $this->setup->getId(),
						'=ELEMENT_ID' => array_column($regionChunk, 'ELEMENT_ID'),
						'=REGION_ID' => $regionId,
					],
				], [
					'TIMESTAMP_X' => new Main\Type\DateTime(),
				]);
			}
		}
	}

	protected function repeatFailedDate() : Main\Type\DateTime
	{
		$value = max(0, (int)Config::getOption('push_repeat_failed', 1));

		return (new Main\Type\DateTime())->add("-P{$value}D");
	}

    protected function repeatUnpublishedDate() : Main\Type\DateTime
    {
        $value = max(1, (int)Config::getOption('push_repeat_unpublished', 6));

        return (new Main\Type\DateTime())->add("-PT{$value}H");
    }

	protected function repeatReadyDate() : Main\Type\DateTime
	{
		$value = max(1, (int)Config::getOption('push_repeat_ready', 7));

		return (new Main\Type\DateTime())->add("-P{$value}D");
	}
}