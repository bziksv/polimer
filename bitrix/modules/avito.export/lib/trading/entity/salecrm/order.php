<?php
namespace Avito\Export\Trading\Entity\SaleCrm;

use Bitrix\Main;
use Bitrix\Crm;
use Avito\Export\Trading\Entity\Sale as TradingSale;

/**
 * @property Crm\Order\Order $saleOrder
*/
class Order extends TradingSale\Order
{
	public function __construct(TradingSale\Container $environment, Crm\Order\Order $saleOrder, int $listenerState = null)
	{
		parent::__construct($environment, $saleOrder, $listenerState);
	}

	public function contacts() : array
	{
		$communication = $this->saleOrder->getContactCompanyCollection();

		if ($communication === null) { return []; }

		$result = [];

		/** @var Crm\Order\ContactCompanyEntity $contact */
		foreach ($communication as $contact)
		{
			if (!$contact->isPrimary()) { continue; }

			$result[$contact::getEntityType()] = $contact->getField('ENTITY_ID');
		}

		return $result;
	}

	public function fillContacts(array $contacts) : void
	{
		$this->setOrderContacts($contacts);
		$this->setDealContacts($contacts);
	}

	protected function setOrderContacts(array $contacts) : void
	{
		try
		{
			$communication = $this->saleOrder->getContactCompanyCollection();

			if ($communication === null) { return; }

			if (isset($contacts[\CCrmOwnerType::Contact]))
			{
				foreach ($communication->getContacts() as $contact)
				{
					$contact->delete();
				}
				$contact = Crm\Order\Contact::create($communication);
				$contact->setField('ENTITY_ID', $contacts[\CCrmOwnerType::Contact]);
				$contact->setField('IS_PRIMARY', 'Y');

				$communication->addItem($contact);
			}

			if (isset($contacts[\CCrmOwnerType::Company]))
			{
				foreach ($communication->getCompanies() as $company)
				{
					$company->delete();
				}
				$company = Crm\Order\Company::create($communication);
				$company->setField('ENTITY_ID', $contacts[\CCrmOwnerType::Company]);
				$company->setField('IS_PRIMARY', 'Y');

				$communication->addItem($company);
			}
		}
		catch (Main\SystemException $exception)
		{
			trigger_error($exception->getMessage(), E_USER_WARNING);
		}
	}

	protected function setDealContacts(array $contacts) : void
	{
		$dealId = $this->dealId();

		if ($dealId <= 0) { return; }

		$fields = [];

		if (isset($contacts[\CCrmOwnerType::Company]))
		{
			$fields['COMPANY_ID'] = $contacts[\CCrmOwnerType::Company];
		}

		if (isset($contacts[\CCrmOwnerType::Contact]))
		{
			$fields['CONTACT_ID'] = $contacts[\CCrmOwnerType::Contact];
		}

		if (empty($fields)) { return; }

		$updater = new \CCrmDeal(false);
		$updated = $updater->Update($dealId, $fields, [
			'DISABLE_USER_FIELD_CHECK' => true,
		]);

		if (!$updated)
		{
			trigger_error($updater->LAST_ERROR, E_USER_WARNING);
		}
	}

	protected function dealId() : ?int
	{
		if (method_exists($this->saleOrder, 'getDealBinding'))
		{
			$binding = $this->saleOrder->getDealBinding();

			if ($binding === null) { return null; }

			$result = $binding->getDealId();
		}
		else if (method_exists($this->saleOrder, 'getEntityBinding'))
		{
			$binding = $this->saleOrder->getEntityBinding();

			if ($binding === null || $binding->getOwnerTypeId() !== \CCrmOwnerType::Deal) { return null; }

			$result = $binding->getOwnerId();
		}
		else
		{
			$result = null;
		}

		return $result;
	}

	public function linkActivity(?int $activityId) : void
	{
		if ($activityId === null) { return; }

		$dealId = $this->dealId();
		if ($dealId === null)
		{
			$this->afterSave([$this, 'linkActivity'], $activityId);
			return;
		}

		$bindings = [
			[
				'OWNER_TYPE_ID' => \CCrmOwnerType::Deal,
				'OWNER_ID' => $dealId
			]
		];
		foreach ($this->contacts() as $entityType => $id)
		{
			$bindings[] = [
				'OWNER_TYPE_ID' => $entityType,
				'OWNER_ID' => $id
			];
		}

		\CCrmActivity::SaveBindings($activityId, $bindings);
	}

	public function linkChat(Chat $chat) : void
	{
		$dealId = $this->dealId();

		if ($dealId === null)
		{
			$this->afterSave([$this, 'linkChat'], $chat);
			return;
		}

		$chat->linkDeal($dealId);
	}

	public function waitChat(string $chatId) : void
	{
		$id = $this->id();
		if ($id <= 0)
		{
			$this->afterSave([$this, 'waitChat'], $chatId);
			return;
		}

		$exist = Internals\WaitChatTable::getRow([
			'filter' => [
				'=ORDER_ID' => $id,
				'=CHAT_ID' => $chatId,
			],
		]);

		if ($exist) { return; }

		Internals\WaitChatTable::add([
			'ORDER_ID' => $id,
			'CHAT_ID' => $chatId,
		]);
	}
}