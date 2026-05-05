<?php
namespace Avito\Export\Api\Messenger\V2\Model\Chat;

use Avito\Export\Api;

class User extends Api\Response
{
	public function id() : int
	{
		return $this->requireValue('id');
	}

	public function name() : string
	{
		return $this->requireValue('name');
	}

	public function publicUserProfile() : User\PublicUserProfile
	{
		return $this->requireModel('public_user_profile', User\PublicUserProfile::class);
	}
}

