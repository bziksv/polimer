<?php
namespace Avito\Export\Trading\Entity\SaleCrm;

use Avito\Export\Assert;
use Bitrix\Crm;
use Avito\Export\Concerns;

class Contact
{
	use Concerns\HasLocale;
	use Concerns\HasOnce;

	protected $environment;
	protected $personTypeId;
	protected $properties;

	public const TYPE_CONTACT = 3;
	public const TYPE_COMPANY = 4;

	/** @var int[] */
	protected $id;

	public function __construct(Container $environment, int $personTypeId, array $properties = [])
	{
		$this->environment = $environment;
		$this->personTypeId = $personTypeId;
		$this->properties = $properties;
	}

	public function same(array $contacts) : bool
	{
		return empty(array_diff($contacts, $this->id()));
	}

	public function installed() : bool
	{
		$matched = $this->id();

		return !empty($matched);
	}

	public function fillId(array $id) : void
	{
		$this->id = $id;
	}

	public function id() : array
	{
		if ($this->id === null)
		{
			$this->id = $this->search();
		}

		return $this->id;
	}

	protected function search() : array
	{
		$order = $this->makeOrder();

		return $this->makeMatchManager()->search($order);
	}

	public function install(array $only = null) : array
	{
		$result = [];

		$entityProperties = $this->entityProperties();
		$assignedById = $this->getAssignedById();
		$duplicateMode = $this->getDuplicateMode();

		$matcherMap = [
			self::TYPE_COMPANY => new Crm\Order\Matcher\CompanyMatcher(),
			self::TYPE_CONTACT => new Crm\Order\Matcher\ContactMatcher(),
		];

		/** @var Crm\Order\Matcher\BaseEntityMatcher $matcher */
		foreach ($matcherMap as $type => $matcher)
		{
			if ($only !== null && !in_array($type, $only, true)) { continue; }

			if (empty($entityProperties[$type]) || !empty($this->id[$type])) { continue; }

			$matcher->setProperties($entityProperties[$type]);
			$matcher->setAssignedById($assignedById);
			$matcher->setDuplicateControlMode($duplicateMode);

			if (
				$type === self::TYPE_CONTACT
				&& !empty($entityProperties[self::TYPE_COMPANY])
				&& !empty($result[self::TYPE_COMPANY])
			)
			{
				$matcher->setRelation(self::TYPE_COMPANY, $result[self::TYPE_COMPANY]);
			}

			$result[$type] = $matcher->match();
		}

		if ($this->id === null) { $this->id = $result; }
		else { $this->id += $result; }

		return $result;
	}

	protected function makeOrder(array $data = []) : Crm\Order\Order
	{
		$siteId = $data['SITE_ID'] ?? SITE_ID;

		/** @var Crm\Order\Order $order */
		$order = Crm\Order\Order::create($siteId);
		$order->setPersonTypeId($this->personTypeId);
		$order->getPropertyCollection()->setValuesFromPost([ 'PROPERTIES' => $this->properties ], []);

		return $order;
	}

	protected function makeMatchManager() : Crm\Order\Matcher\EntityMatchManager
	{
		return Crm\Order\Matcher\EntityMatchManager::getInstance();
	}

	public function update() : void
	{
		Assert::notNull($this->id, 'contact.id');

		$entityProperties = $this->entityProperties();
		$matcherMap = [
			self::TYPE_COMPANY => new Crm\Order\Matcher\CompanyMatcher(),
			self::TYPE_CONTACT => new Crm\Order\Matcher\ContactMatcher(),
		];

		/** @var Crm\Order\Matcher\BaseEntityMatcher $matcher */
		foreach ($matcherMap as $type => $matcher)
		{
			if (empty($entityProperties[$type]) || empty($this->id[$type])) { continue; }

			$matcher->setProperties($entityProperties[$type]);
			$matcher->setDuplicateControlMode($matcher::DUPLICATE_CONTROL_MODES['REPLACE']);

			if (
				$type === self::TYPE_CONTACT
				&& !empty($entityProperties[self::TYPE_COMPANY])
				&& !empty($this->id[self::TYPE_COMPANY])
			)
			{
				$matcher->setRelation(self::TYPE_COMPANY, $this->id[self::TYPE_COMPANY]);
			}

			$matcher->update($this->id[$type]);
		}
	}

	protected function entityProperties() : array
	{
		return $this->once('entityProperties', function() {
			$propertyMap = $this->entityPropertyMap(array_keys($this->properties));
			$entityProperties = [];

			foreach ($propertyMap as $propertyId => $properties)
			{
				foreach ($properties as $property)
				{
					$entityType = $this->sanitizeEntityType($property);

					$property['VALUE'] = $this->properties[$propertyId];

					$entityProperties[$entityType][$property['CRM_FIELD_TYPE']][$property['ID']] = $property;
				}
			}

			return $entityProperties;
		});
	}

	protected function entityPropertyMap(array $propertyIds) : array
	{
		if (empty($propertyIds)) { return []; }

		$propertyMap = [];

		$bindings = Crm\Order\Matcher\Internals\OrderPropsMatchTable::getList([
			'filter' => [ '=SALE_PROP_ID' => $propertyIds ],
		]);

		while ($binding = $bindings->fetch())
		{
			$propertyMap[$binding['SALE_PROP_ID']][] = $binding;
		}

		return $propertyMap;
	}

	protected function sanitizeEntityType(array $property) : int
	{
		if (isset($property['CRM_ENTITY_TYPE']) && \CCrmOwnerType::IsEntity($property['CRM_ENTITY_TYPE']))
		{
			return (int)$property['CRM_ENTITY_TYPE'];
		}

		return \CCrmOwnerType::Undefined;
	}

	public function merge(array $from, int $mergerUserId = null) : array
	{
		$mergerUserId = $mergerUserId ?? Crm\Service\Container::getInstance()->getContext()->getUserId();
		$to = $this->id();
		$merged = $to;

		foreach ($from as $type => $fromId)
		{
			if (empty($to[$type]))
			{
				$merged[$type] = $fromId;
				continue;
			}

			$toId = $to[$type];

			$merger = Crm\Merger\EntityMerger::create($type, $mergerUserId);
			$merger->setConflictResolutionMode(Crm\Merger\ConflictResolutionMode::NEVER_OVERWRITE);

			$merger->mergeBatch([$fromId], $toId);
		}

		return $merged;
	}

	protected function getAssignedById() : int
	{
		$responsibleQueue = new Crm\Order\Matcher\ResponsibleQueue($this->personTypeId);
		$responsibleId = $responsibleQueue->getNextId();

		return (int)$responsibleId ?: 1;
	}

	protected function getDuplicateMode() : string
	{
		return Crm\Order\Matcher\Internals\FormTable::getDuplicateModeByPersonType($this->personTypeId);
	}
}