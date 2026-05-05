<?php
/** @noinspection ReturnTypeCanBeDeclaredInspection */
namespace Avito\Export\Trading\Action\Reference\Internals;

class DummyUser extends \CUser
{
	public function GetParam($name)
	{
		if ($name === 'USER_ID')
		{
			return 0;
		}

		if ($name === 'APPLICATION_ID')
		{
			return parent::GetParam($name);
		}

		return null;
	}

	public function SetParam($name, $value)
	{
		// nothing
	}
}