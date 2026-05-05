<?php
namespace Avito\Export\Api\Messenger\V2\Model\Chat\User\PublicUserProfile;

use Avito\Export\Api;

class Avatar extends Api\Response
{
	public function default() : string
	{
		return $this->requireValue('default');
	}

	public function images() : array
	{
		return $this->requireValue('images');
	}
}