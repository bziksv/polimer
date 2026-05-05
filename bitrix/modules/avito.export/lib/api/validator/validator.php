<?php
namespace Avito\Export\Api\Validator;

use Bitrix\Main\Web\HttpClient;

abstract class Validator
{
	protected $data;
	protected $transport;

	public function __construct($data, HttpClient $transport)
	{
		$this->data = $data;
		$this->transport = $transport;
	}

	abstract public function test() : void;
}