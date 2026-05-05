<?php
namespace Avito\Export\Trading\Entity\Sale;

use Avito\Export\Concerns;
use Avito\Export\Admin;
use Bitrix\Main;
use Bitrix\Sale;

class BuyerProfile
{
	use Concerns\HasLocale;
	use Concerns\HasOnce;

	protected $environment;

	public function __construct(Container $environment)
	{
		$this->environment = $environment;
	}

	public function properties(int $profileId) : array
	{
		return Sale\OrderUserProperties::getProfileValues($profileId);
	}

	public function variants(int $userId, int $personTypeId) : array
	{
		$hash = sprintf('variants-%s-%s', $userId, $personTypeId);

		return $this->once($hash, function() use ($userId, $personTypeId) {
			$result = [];

			$query = Sale\Internals\UserPropsTable::getList([
				'filter' => [
					'=USER_ID' => $userId,
					'=PERSON_TYPE_ID' => $personTypeId,
				],
				'select' => [ 'ID', 'NAME' ],
				'order' => [ 'DATE_UPDATE' => 'DESC', 'ID' => 'DESC' ]
			]);

			while ($row = $query->fetch())
			{
				$result[] = [
					'ID' => $row['ID'],
					'VALUE' => $row['NAME'],
				];
			}

			return $result;
		});
	}

	public function editUrl(int $profileId) : string
	{
		return Admin\Path::pageUrl('sale_buyers_profile_edit', [
			'id' => $profileId,
			'lang' => LANGUAGE_ID,
		]);
	}

	public function create(int $userId, int $personTypeId) : Main\ORM\Data\AddResult
	{
		$addResult = $this->insertProfile($userId, $personTypeId);

		if (!$addResult->isSuccess()) { return $addResult; }

		$properties = $this->fetchPersonTypeProperties($personTypeId);
		$values = $this->makeDefaultValues($properties);
		$errors = [];

		\CSaleOrderUserProps::DoSaveUserProfile($userId, $addResult->getId(), '', $personTypeId, $values, $errors);

		return $addResult;
	}

	protected function fetchPersonTypeProperties(int $personTypeId) : array
	{
		$orderPropsMap = [
			'IS_PROFILE_NAME' => null,
			'IS_EMAIL' => null,
			'IS_PHONE' => null,
			'IS_ZIP' => null,
			'IS_LOCATION' => null,
			'IS_ADDRESS' => null,
		];

		$propsIterator = Sale\Internals\OrderPropsTable::getList([
			'filter'=> [ '=PERSON_TYPE_ID' => $personTypeId ],
		]);

		while ($prop = $propsIterator->fetch())
		{
			foreach ($orderPropsMap as $orderPropCode => $orderPropId)
			{
				if ($orderPropId !== null) { continue; }

				if ($prop[$orderPropCode] === 'Y')
				{
					$orderPropsMap[$orderPropCode] = $prop['ID'];
				}
			}
		}

		return array_filter($orderPropsMap);
	}

	protected function insertProfile(int $userId, int $personTypeId) : Main\ORM\Data\AddResult
	{
		return Sale\Internals\UserPropsTable::add([
			'NAME' => $this->defaultValue('PROFILE_NAME'),
			'EMAIL' => $this->defaultValue('EMAIL'),
			'USER_ID' => $userId,
			'PERSON_TYPE_ID' => $personTypeId,
			'DATE_UPDATE' => new Main\Type\DateTime(),
		]);
	}

	protected function makeDefaultValues(array $propertiesMap) : array
	{
		$values = [];

		foreach ($propertiesMap as $type => $propertyId)
		{
			$typeWithoutPrefix = str_replace('IS_', '', $type);

			$values[$propertyId] = $this->defaultValue($typeWithoutPrefix);
		}

		return array_filter($values);
	}

	protected function defaultValue(string $type) : ?string
	{
		if ($type === 'LOCATION')
		{
			$location = \CSaleHelper::getShopLocation();

			if (!empty($location['ID']))
			{
				return \CSaleLocation::getLocationCODEbyID($location['ID']);
			}
		}

		return static::getLocale('DEFAULT_' . $type);
	}
}