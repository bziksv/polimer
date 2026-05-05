<?php
namespace Avito\Export\Trading\Entity\SaleCrm;

use Bitrix\ImOpenLines;

class Chat
{
	protected $environment;
	protected $imChat;

	public function __construct(Container $environment, ImOpenLines\Chat $imChat)
	{
		$this->environment = $environment;
		$this->imChat = $imChat;
	}

	/** @noinspection PhpExpressionAlwaysNullInspection */
	public function user() : ?int
	{
		$entityId = $this->imChat->getData('ENTITY_ID');

		return ImOpenLines\Chat::parseLinesChatEntityId($entityId)['connectorUserId'];
	}

	public function contacts() : array
	{
		$fields = $this->imChat->getFieldData(ImOpenLines\Chat::FIELD_CRM);

		return array_filter([
			\CCrmOwnerType::Contact => $fields['CONTACT'],
			\CCrmOwnerType::Company => $fields['COMPANY'],
		]);
	}

	public function activity() : ?int
	{
		$session = ImOpenLines\Model\SessionTable::getRow([
			'filter' => [
				'=USER_CODE' => $this->imChat->getData('ENTITY_ID')
			],
			'select' => ['CRM_ACTIVITY_ID']
		]);
		if (empty($session['CRM_ACTIVITY_ID'])) { return null; }

		return (int)$session['CRM_ACTIVITY_ID'];
	}

	public function linkDeal($dealId) : void
	{
		$fields = $this->imChat->getFieldData(ImOpenLines\Chat::FIELD_CRM);

		$this->imChat->updateFieldData([
			'LINES_SESSION' => [
				'CRM' => 'Y',
				'CRM_ENTITY_TYPE' => 'DEAL',
				'CRM_ENTITY_ID' => $dealId,
			],
			'LINES_CRM' => [
				'CONTACT' => $fields['CONTACT'],
				'COMPANY' => $fields['COMPANY'],
				'DEAL' => $dealId,
			]
		]);
	}
}