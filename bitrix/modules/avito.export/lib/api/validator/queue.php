<?php
namespace Avito\Export\Api\Validator;

class Queue
{
	/** @var Validator[] */
	protected $stages = [];

	public function add(Validator $validator) : Queue
	{
		$this->stages[] = $validator;

		return $this;
	}

	public function run() : void
	{
		foreach ($this->stages as $validator)
		{
			$validator->test();
		}
	}
}