<?php
namespace Avito\Export\Api\Messenger\V2\Model\Chat;

use Avito\Export\Api;

class Users extends Api\ResponseCollection
{
	protected function itemClass() : string
	{
		return User::class;
	}
}