<?php
namespace Avito\Export\Api\OAuth;

use Bitrix\Main;

class Token extends EO_Token
{
	public function isExpired(Main\Type\DateTime $now = null) : bool
	{
		$now = $now ?? new Main\Type\DateTime();
		$expires = $this->getExpires();

		return $expires->getTimestamp() <= $now->getTimestamp();
	}

	public function isOwner() : bool
	{
		return $this->getRefreshToken() === TokenTable::CLIENT_OWNER;
	}

	public function installAgent() : void
	{
		RefreshToken\Agent::install($this);
	}
}