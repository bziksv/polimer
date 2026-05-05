<?php
namespace Avito\Export\Api\Messenger\V2\Model\Chat\User;

use Avito\Export\Api;

class PublicUserProfile extends Api\Response
{
	public function itemId() : int
	{
		return $this->requireValue('item_id');
	}

	public function url() : string
	{
		return $this->requireValue('url');
	}

	public function userId() : int
	{
		return $this->requireValue('user_id');
	}

	public function avatar() : PublicUserProfile\Avatar
	{
		return $this->requireModel('avatar', PublicUserProfile\Avatar::class);
	}
}