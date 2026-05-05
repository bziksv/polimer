<?php
namespace Avito\Export\Admin\UserField;

use Bitrix\Main;

class Registry
{
	public static function description(string $name) : array
	{
		global $USER_FIELD_MANAGER;

		$userClass = __NAMESPACE__ . '\\' . ucfirst($name) . 'Type';
		$userClassExists = class_exists($userClass);

		if ($userClassExists && method_exists($userClass, 'GetUserTypeDescription'))
		{
			$result = $userClass::GetUserTypeDescription();
		}
		else
		{
			$result = $USER_FIELD_MANAGER->GetUserType($name);

			if (!is_array($result))
			{
				throw new Main\ArgumentException(sprintf('unknown %s user type', $name));
			}
		}

		if ($userClassExists)
		{
			$result['CLASS_NAME'] = $userClass;
		}

		return $result;
	}
}