<?php
namespace Avito\Export\Api\OAuth\ExchangeCode;

use Avito\Export\Api;

class Response extends Api\OAuth\ClientCredentials\Response
{
	public function refreshToken() : string
	{
		return (string)$this->requireValue('refresh_token');
	}

	public function scope() : string
	{
		return (string)$this->requireValue('scope');
	}
}