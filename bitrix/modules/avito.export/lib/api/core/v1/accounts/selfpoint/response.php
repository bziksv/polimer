<?php
namespace Avito\Export\Api\Core\V1\Accounts\SelfPoint;

use Avito\Export\Api;

class Response extends Api\Response
{
	public function id() : int
	{
		return (int)$this->requireValue('id');
	}

	public function name() : ?string
	{
		return $this->getValue('name');
	}

	public function email() : ?string
	{
		return $this->getValue('email');
	}

	public function phone() : ?string
	{
		return $this->getValue('phone');
	}
}