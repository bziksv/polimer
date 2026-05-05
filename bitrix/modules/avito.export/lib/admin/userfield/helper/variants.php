<?php
namespace Avito\Export\Admin\UserField\Helper;

class Variants
{
	public static function toArray($list) : array
	{
		if ($list instanceof \CDBResult)
		{
			$result = [];

			while ($row = $list->Fetch())
			{
				$result[] = $row;
			}
		}
		else if (is_array($list))
		{
			$result = $list;
		}
		else
		{
			$result = [];
		}

		return $result;
	}
}