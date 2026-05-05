<?php
namespace Avito\Export\Api\OAuth\ClientCredentials;

use Avito\Export\Api;

class Response extends Api\Response
{
	public function accessToken() : string
	{
		return (string)$this->requireValue('access_token');
	}

	public function expiresIn() : int
	{
		return (int)$this->requireValue('expires_in');
	}

	public function tokenType() : string
	{
		return (string)$this->requireValue('token_type');
	}
}