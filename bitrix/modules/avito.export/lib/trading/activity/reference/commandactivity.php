<?php
namespace Avito\Export\Trading\Activity\Reference;

abstract class CommandActivity extends Activity
{
	abstract public function path() : string;

	abstract public function payload() : array;

	public function confirm() : ?string
	{
		return null;
	}
}