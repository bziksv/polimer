<?php
namespace Avito\Export\Trading\Entity\SaleCrm;

use Bitrix\Crm;
use Avito\Export;
use Avito\Export\Concerns;

class AnonymousContact extends Contact
{
	use Concerns\HasLocale;

	protected $id;

	public function __construct(Container $environment, $personTypeId)
	{
		parent::__construct($environment, $personTypeId);
	}

	protected function search() : array
	{
		return $this->getOptionValue();
	}

	public function install(array $only = null) : array
	{
		$supported = [
			\CCrmOwnerType::Company,
			\CCrmOwnerType::Contact,
		];
		$entities = Crm\Order\Matcher\FieldMatcher::getMatchedEntities($this->personTypeId);
		$entities = array_intersect($supported, $entities);
		$primaries = [];

		foreach ($entities as $ownerType)
		{
			$ownerType = (int)$ownerType;

			if ($ownerType === \CCrmOwnerType::Company)
			{
				$entity = new \CCrmCompany(false);
				$fields = [
					'TITLE' => self::getLocale('COMPANY_TITLE'),
				];
			}
			else if ($ownerType === \CCrmOwnerType::Contact)
			{
				$entity = new \CCrmContact(false);
				$fields = [
					'NAME' => self::getLocale('CONTACT_NAME'),
				];

				if (isset($primaries[\CCrmOwnerType::Company]))
				{
					$fields['COMPANY_ID'] = $primaries[\CCrmOwnerType::Company];
				}
			}
			else
			{
				continue;
			}

			$primary = $entity->Add($fields, [
				'DISABLE_USER_FIELD_CHECK' => true,
			]);

			if ($primary)
			{
				$primaries[$ownerType] = $primary;
			}
			else
			{
				trigger_error($entity->LAST_ERROR, E_USER_WARNING);
			}
		}

		$this->id = $primaries;

		if (!empty($primaries))
		{
			$this->saveOptionValue($primaries);
		}

		return $primaries;
	}

	protected function getOptionValue() : array
	{
		$name = $this->getOptionName();
		$option = (string)Export\Config::getOption($name);

		if ($option === '') { return []; }

		$stored = unserialize($option, ['allowed_classes' => false]);

		if (!is_array($stored)) { return []; }

		return $stored;
	}

	protected function saveOptionValue(array $contacts) : void
	{
		$name = $this->getOptionName();

		Export\Config::setOption($name, serialize($contacts));
	}

	protected function getOptionName() : string
	{
		return 'trading_contact_' . $this->personTypeId;
	}
}