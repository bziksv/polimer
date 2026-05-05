<?php
namespace Avito\Export\Trading\Action\OrderStatus\Stage;

use Avito\Export\Api;
use Avito\Export\Trading;

class FillContact
{
	protected $apiOrder;
	protected $tradingOrder;
	protected $state;
	protected $environment;
	protected $settings;

	public function __construct(
		Api\OrderManagement\Model\Order $apiOrder,
		Trading\Entity\SaleCrm\Order $tradingOrder,
		Trading\State\Order $state,
		Trading\Entity\SaleCrm\Container $environment,
		Trading\Setup\Settings $settings
	)
	{
		$this->apiOrder = $apiOrder;
		$this->tradingOrder = $tradingOrder;
		$this->state = $state;
		$this->environment = $environment;
		$this->settings = $settings;
	}

	public function need() : bool
	{
		$buyerData = $this->buyerData();

		return (
			$buyerData !== null
			&& $this->state->hashChanged('CONTACT_BUYER', $buyerData)
		);
	}

	public function execute() : void
	{
		$personType = $this->settings->personType();
		$properties = $this->tradingOrder->properties();
		$properties = array_diff_assoc($properties, $this->profileValues());
		[$realContacts, $anonymousContacts] = $this->splitOrderContacts();
		$contact = $this->environment->contactRegistry()->contact($personType, $properties);

		if (!empty($anonymousContacts[$contact::TYPE_CONTACT]))
		{
			$newContacts = $realContacts;
			$newContacts += $contact->install([$contact::TYPE_CONTACT]);

			$this->tradingOrder->fillContacts($newContacts);
		}

		if (!empty($realContacts))
		{
			$contact->fillId($realContacts);
			$contact->update();
		}

		$this->state->setHash('CONTACT_BUYER', $this->buyerData());
	}

	protected function buyerData() : ?array
	{
		$buyerInfo = $this->apiOrder->delivery()->buyerInfo();

		if ($buyerInfo === null) { return null; }

		return [
			'PHONE' => $buyerInfo->phoneNumber(),
			'NAME' => $buyerInfo->fullName(),
		];
	}

	protected function splitOrderContacts() : array
	{
		$used = $this->tradingOrder->contacts();
		$anonymous = $this->environment->contactRegistry()->anonymous($this->settings->personType())->id();

		return [
			array_diff_assoc($used, $anonymous),
			array_intersect_assoc($anonymous, $used),
		];
	}

	protected function profileValues() : array
	{
		$profileId = $this->settings->buyerProfile();

		if ($profileId === null) { return []; }

		return $this->environment->buyerProfile()->properties($profileId);
	}
}

