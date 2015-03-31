<?php
namespace Bitrix\Crm\Integrity;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class DuplicateCommunicationMatchCodeTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_dp_comm_mcd';
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
			'TYPE' => array(
				'data_type' => 'string',
				'required' => true
			),
			'VALUE' => array(
				'data_type' => 'string',
				'required' => true
			)
		);
	}

	public static function replaceValues($entityTypeID, $entityID, $type, array $values)
	{
		$connection = \Bitrix\Main\Application::getConnection();

		$sqlHelper = $connection->getSqlHelper();
		$typeSql = $sqlHelper->forSql($type);

		if(empty($values))
		{
			$connection->queryExecute(
				"DELETE FROM b_crm_dp_comm_mcd WHERE ENTITY_TYPE_ID = {$entityTypeID} AND ENTITY_ID = {$entityID} AND TYPE = '{$typeSql}'"
			);
			return;
		}

		$items = array();
		$result = $connection->query("SELECT ID, VALUE FROM b_crm_dp_comm_mcd WHERE ENTITY_TYPE_ID = {$entityTypeID} AND ENTITY_ID = {$entityID} AND TYPE = '{$typeSql}'");
		while($fields = $result->fetch())
		{
			$items[intval($fields['ID'])] = $fields['VALUE'];
		}

		$deleteIDs = array();
		foreach($items as $itemID => $itemValue)
		{
			if(!in_array($itemValue, $values, true))
			{
				$deleteIDs[] = $itemID;
			}
		}

		$insertValues = array();
		foreach($values as $value)
		{
			if(!is_string($value) || $value === '')
			{
				continue;
			}

			if(!in_array($value, $items, true))
			{
				$insertValues[] = $value;
			}
		}

		if(!empty($deleteIDs))
		{
			$idsSql = implode(',', $deleteIDs);
			$connection->queryExecute(
				"DELETE FROM b_crm_dp_comm_mcd WHERE ID IN ({$idsSql})"
			);
		}

		if(!empty($insertValues))
		{
			if($connection instanceof \Bitrix\Main\DB\MysqlCommonConnection)
			{
				$valueData = array();
				foreach($insertValues as $value)
				{
					$valueSql = $sqlHelper->forSql($value);
					$valueData[] = "({$entityTypeID}, {$entityID}, '{$typeSql}', '{$valueSql}')";
				}

				$valuesSql = implode(', ', $valueData);
				$connection->queryExecute(
					"INSERT INTO b_crm_dp_comm_mcd(ENTITY_TYPE_ID, ENTITY_ID, TYPE, VALUE) VALUES {$valuesSql}"
				);
			}
			elseif($connection instanceof \Bitrix\Main\DB\MssqlConnection)
			{
				if(count($insertValues) > 1)
				{
					$valueData = array();
					foreach($insertValues as $value)
					{
						$valueSql = $sqlHelper->forSql($value);
						$valueData[] = "SELECT {$entityTypeID}, {$entityID}, '{$typeSql}', '{$valueSql}'";
					}
					$valuesSql = implode(' UNION ALL ', $valueData);

					if($valuesSql !== '')
					{
						$connection->queryExecute(
							"INSERT INTO b_crm_dp_comm_mcd(ENTITY_TYPE_ID, ENTITY_ID, TYPE, VALUE) {$valuesSql}"
						);
					}
				}
				else
				{
					$valueSql = $sqlHelper->forSql($insertValues[0]);
					$valuesSql = "({$entityTypeID}, {$entityID}, '{$typeSql}', '{$valueSql}')";
					$connection->queryExecute(
						"INSERT INTO b_crm_dp_comm_mcd(ENTITY_TYPE_ID, ENTITY_ID, TYPE, VALUE) VALUES {$valuesSql}"
					);
				}
			}
			elseif($connection instanceof \Bitrix\Main\DB\OracleConnection)
			{
				if(count($insertValues) > 1)
				{
					$valueData = array();
					foreach($insertValues as $value)
					{
						$valueSql = $sqlHelper->forSql($value);
						$valueData[] = "SELECT {$entityTypeID}, {$entityID}, '{$typeSql}', '{$valueSql}' FROM dual";
					}

					$valuesSql = implode(' UNION ALL ', $valueData);
					$connection->queryExecute(
						"INSERT INTO b_crm_dp_comm_mcd(ENTITY_TYPE_ID, ENTITY_ID, TYPE, VALUE) {$valuesSql}"
					);
				}
				else
				{
					$valueSql = $sqlHelper->forSql($insertValues[0]);
					$valuesSql = "({$entityTypeID}, {$entityID}, '{$typeSql}', '{$valueSql}')";
					$connection->queryExecute(
						"INSERT INTO b_crm_dp_comm_mcd(ENTITY_TYPE_ID, ENTITY_ID, TYPE, VALUE) VALUES {$valuesSql}"
					);
				}
			}
			else
			{
				$dbType = $connection->getType();
				throw new Main\NotSupportedException("The '{$dbType}' is not supported in current context");
			}
		}
	}
}