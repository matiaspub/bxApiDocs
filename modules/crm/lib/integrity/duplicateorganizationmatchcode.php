<?php
namespace Bitrix\Crm\Integrity;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class DuplicateOrganizationMatchCodeTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_dp_org_mcd';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true
			),
			'ENTITY_ID' => array(
				'data_type' => 'integer',
				'required' => true
			),
			'ENTITY_TYPE_ID' => array(
				'data_type' => 'integer',
				'required' => true
			),
			'TITLE' => array(
				'data_type' => 'string',
				'required' => true
			)
		);
	}
}