<?php
namespace Bitrix\EsolAie\Entity;

use Bitrix\Main\Entity,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader;
Loc::loadMessages(__FILE__);

class SaleUserAccountTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_user_account';
	}
	
	public static function getMap(): array
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('ESOL_AIE_SALE_USER_ACCOUNT_FIELD_ID'),
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('ESOL_AIE_SALE_USER_ACCOUNT_FIELD_USER_ID'),
			),
			'TIMESTAMP_X' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('ESOL_AIE_SALE_USER_ACCOUNT_FIELD_TIMESTAMP_X'),
			),
			'CURRENT_BUDGET' => array(
				'data_type' => 'float',
				'title' => Loc::getMessage('ESOL_AIE_SALE_USER_ACCOUNT_FIELD_CURRENT_BUDGET'),
			),
			'CURRENCY' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('ESOL_AIE_SALE_USER_ACCOUNT_FIELD_CURRENCY'),
			),
			'LOCKED' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('ESOL_AIE_SALE_USER_ACCOUNT_FIELD_LOCKED'),
			),
			'DATE_LOCKED' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('ESOL_AIE_SALE_USER_ACCOUNT_FIELD_DATE_LOCKED'),
			),
			'NOTES' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('ESOL_AIE_SALE_USER_ACCOUNT_FIELD_NOTES'),
			),
			'USER' => array(
				'data_type' => '\Bitrix\Main\UserTable',
				'reference' => array('=this.USER_ID' => 'ref.ID'),
			),
		);
	}
}