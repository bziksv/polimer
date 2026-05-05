<?php
namespace Avito\Export\Api;

use Avito\Export\Assert;

abstract class RequestWithClient extends Request
{
	/** @var string */
	protected $clientId;
	/** @var string */
	protected $clientSecret;

	public function clientId(string $clientId) : void
	{
		$this->clientId = $clientId;
	}

	public function clientSecret(string $clientSecret) : void
	{
		$this->clientSecret = $clientSecret;
	}

	protected function queryClient() : array
	{
		Assert::notEmptyString($this->clientId, 'clientId');
		Assert::notEmptyString($this->clientSecret, 'clientSecret');

		return [
			'client_id' => $this->clientId,
			'client_secret' => $this->clientSecret,
		];
	}
}