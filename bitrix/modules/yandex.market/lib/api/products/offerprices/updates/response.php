<?php
namespace Yandex\Market\Api\Products\OfferPrices\Updates;

use Yandex\Market\Api\Reference;

class Response extends Reference\Response
{
	public function getStatus()
	{
		return (string)$this->getField('status');
	}

	public function isSuccess()
	{
		return $this->getStatus() === 'OK';
	}

	public function getErrors()
	{
		$errors = $this->getField('errors');

		return is_array($errors) ? $errors : [];
	}
}
