<?php
namespace Avito\Export\Trading\Entity\Sale;

use Avito\Export\Concerns;
use Bitrix\Sale;

class PersonType
{
	use Concerns\HasOnce;

	protected $environment;

	public function __construct(Container $environment)
	{
		$this->environment = $environment;
	}

	public function sites(int $personTypeId) : array
	{
		$query = Sale\Internals\PersonTypeSiteTable::getList([
			'filter' => [ '=PERSON_TYPE_ID' => $personTypeId ],
			'select' => [ 'SITE_ID' ],
		]);

		return array_column($query->fetchAll(), 'SITE_ID');
	}

	public function variants(array $sites = null) : array
	{
		$sitesSign = is_array($sites) ? implode(',', $sites) : '';

		return $this->once('variants-' . $sitesSign, function() use ($sites) {
			$result = [];
			$filter = [];

			if (!empty($sites))
			{
				$filter['=PERSON_TYPE_SITE.SITE_ID'] = $sites;
			}

			$query = Sale\Internals\PersonTypeTable::getList([
				'filter' => $filter,
				'select' => [ 'ID', 'NAME' ],
			]);

			while ($row = $query->fetch())
			{
				$result[$row['ID']] = [
					'ID' => $row['ID'],
					'VALUE' => sprintf('[%s] %s', $row['ID'], $row['NAME']),
				];
			}

			return array_values($result);
		});
	}

	public function legalDefault(array $sites = null) : ?int
	{
		return $this->domainDefault(Sale\BusinessValue::ENTITY_DOMAIN, $sites);
	}

	protected function domainDefault(string $type, array $sites = null) : ?int
	{
		$sitesSign = is_array($sites) ? implode(',', $sites) : '';

		return $this->once('domainDefault-' . $type . '-' . $sitesSign, function() use ($type, $sites) {
			$result = null;
			$filter = [
				'=DOMAIN' => $type,
				'=PERSON_TYPE_REFERENCE.ACTIVE' => 'Y',
				'=PERSON_TYPE_REFERENCE.ENTITY_REGISTRY_TYPE' => Sale\Registry::REGISTRY_TYPE_ORDER,
			];

			if (!empty($sites))
			{
				$filter['=PERSON_TYPE_REFERENCE.PERSON_TYPE_SITE.SITE_ID'] = $sites;
			}

			$query = Sale\Internals\BusinessValuePersonDomainTable::getList([
				'select' => [ 'PERSON_TYPE_ID' ],
				'filter' => $filter,
				'limit' => 1,
			]);

			if ($row = $query->fetch())
			{
				$result = (int)$row['PERSON_TYPE_ID'];
			}

			return $result;
		});
	}
}