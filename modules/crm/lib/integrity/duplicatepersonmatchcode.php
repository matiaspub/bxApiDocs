<?php
namespace Bitrix\Crm\Integrity;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class DuplicatePersonMatchCodeTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_dp_prsn_mcd';
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
			'LAST_NAME' => array(
				'data_type' => 'string',
				'required' => true
			),
			'NAME' => array(
				'data_type' => 'string'
			),
			'SECOND_NAME' => array(
				'data_type' => 'string'
			)
		);
	}

	public static function calculateDuplicateCount(array $parameters)
	{
		$connection = \Bitrix\Main\Application::getConnection();

		$filterSql = '';
		if(isset($parameters['ENTITY_TYPE_ID']))
		{
			$entityTypeID = intval($parameters['ENTITY_TYPE_ID']);
			$filterSql = "ENTITY_TYPE_ID = $entityTypeID";
		}

		if(isset($parameters['PERMISSION_SQL']))
		{
			$permissionSql = $parameters['PERMISSION_SQL'];
			if(is_string($permissionSql) && $permissionSql !== '')
			{
				if($filterSql !== '')
				{
					$filterSql .= ' AND ';
				}
				$filterSql .= "ENTITY_ID IN({$permissionSql})";
			}
		}

		$subquerySql= $filterSql !== ''
			? "SELECT LAST_NAME FROM b_crm_dp_prsn_mcd WHERE {$filterSql} GROUP BY LAST_NAME HAVING COUNT(LAST_NAME) > 1"
			: "SELECT LAST_NAME FROM b_crm_dp_prsn_mcd GROUP BY LAST_NAME HAVING COUNT(LAST_NAME) > 1";

		$result = $connection->query("SELECT COUNT(*) AS CNT FROM ({$subquerySql}) t");
		$fields = $result->fetch();
		return is_array($fields) ? intval($fields['CNT']) : 0;
	}
}