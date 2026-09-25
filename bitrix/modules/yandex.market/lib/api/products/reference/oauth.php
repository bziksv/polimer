<?php
namespace Yandex\Market\Api\Products\Reference;

use Yandex\Market\Reference\Assert;

class OAuth
{
	const HEADER_NAME = 'Authorization';

	private $accessToken;

	public function __construct($accessToken)
	{
		$this->accessToken = $accessToken;
	}

	public function getHeader()
	{
		Assert::nonEmptyString($this->accessToken, 'accessToken');

		return [ self::HEADER_NAME, 'OAuth ' . $this->accessToken ];
	}
}
