<?php
namespace Avito\Export\Api\OAuth\Invite;

use Avito\Export\Api;

class Response extends Api\Response
{
	public function code() : string
	{
		return (string)$this->requireValue('code');
	}
}