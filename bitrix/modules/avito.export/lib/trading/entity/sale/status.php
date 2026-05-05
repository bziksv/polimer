<?php
namespace Avito\Export\Trading\Entity\Sale;

use Avito\Export\Concerns;
use Bitrix\Sale;

class Status
{
	use Concerns\HasLocale;
	use Concerns\HasOnce;

	public const CANCELLED = 'CANCELLED';
	public const ALLOW_DELIVERY = 'ALLOW_DELIVERY';
	public const DEDUCTED = 'DEDUCTED';
	public const PAID = 'PAID';
	public const NEW_STATUS = 'N';
	public const FINISHED = 'F';

	protected $environment;

	public function __construct(Container $environment)
	{
		$this->environment = $environment;
	}

	public function variants() : array
	{
		return $this->once('variants', function() {
			return array_merge(
				$this->orderVariants(),
				$this->predefinedVariants()
			);
		});
	}

	protected function orderVariants() : array
	{
		$result = [];
		$query = Sale\Internals\StatusTable::getList([
			'order' => [ 'SORT' => 'asc' ],
			'filter' => [ '=TYPE' => 'O', '=STATUS_LANG.LID' => LANGUAGE_ID ],
			'select' => [ 'ID', 'STATUS_LANG_NAME' => 'STATUS_LANG.NAME' ],
		]);

		while ($row = $query->Fetch())
		{
			$result[] = [
				'ID' => $row['ID'],
				'VALUE' => sprintf('[%s] %s', $row['ID'], $row['STATUS_LANG_NAME']),
			];
		}

		return $result;
	}

	protected function predefinedVariants() : array
	{
		$types = [
			static::ALLOW_DELIVERY,
			static::DEDUCTED,
			static::PAID,
			static::CANCELLED,
		];

		return array_map(static function(string $type) {
			return [
				'ID' => $type,
				'VALUE' => self::getLocale($type),
			];
		}, $types);
	}
}