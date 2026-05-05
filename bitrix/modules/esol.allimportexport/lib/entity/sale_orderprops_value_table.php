<?php
namespace Bitrix\Sale\Internals;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class EsolIEOrderPropsValueTable extends \Bitrix\Sale\Internals\OrderPropsValueTable
{
	public static function getIEFieldsRel()
	{
		return array(
			'VALUE' => array(
				'data_type' => 'string'
			),
		);
	}
	
	/*
    public static function getUfId(): string
    {
        return '';
    }
	*/
}
