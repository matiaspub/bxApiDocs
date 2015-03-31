<?php
namespace Bitrix\Crm\Integrity\Entity;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class DuplicateEntityMatchHashTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_dp_entity_hash';
	}
	public static function getMap()
	{
		return array(
			'ENTITY_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'required' => true
			),
			'ENTITY_TYPE_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'required' => true
			),
			'TYPE_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'required' => true
			),
			'MATCH_HASH' => array(
				'data_type' => 'string',
				'primary' => true,
				'required' => true
			),
			'IS_PRIMARY' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			)
		);
	}
	public static function upsert(array $data)
	{
		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$entityID = isset($data['ENTITY_ID']) ? (int)$data['ENTITY_ID'] : 0;
		$entityTypeID = isset($data['ENTITY_TYPE_ID']) ? (int)$data['ENTITY_TYPE_ID'] : 0;
		$typeID = isset($data['TYPE_ID']) ? (int)$data['TYPE_ID'] : 0;
		$matchHash = isset($data['MATCH_HASH']) ? $sqlHelper->forSql($data['MATCH_HASH'], 32) : '';

		$isPrimary = 'N';
		if(isset($data['IS_PRIMARY']))
		{
			if(is_bool($data['IS_PRIMARY']))
			{
				$isPrimary = $data['IS_PRIMARY'] ? 'Y' : 'N';
			}
			elseif(is_string($data['IS_PRIMARY']) && strtoupper(trim($data['IS_PRIMARY'])) === 'Y')
			{
				$isPrimary = 'Y';
			}
		}

		if($connection instanceof Main\DB\MysqlCommonConnection)
		{
			$connection->queryExecute(
				"INSERT INTO b_crm_dp_entity_hash(ENTITY_ID, ENTITY_TYPE_ID, TYPE_ID, MATCH_HASH, IS_PRIMARY)
					VALUES({$entityID}, {$entityTypeID}, {$typeID}, '{$matchHash}', '{$isPrimary}')
					ON DUPLICATE KEY UPDATE IS_PRIMARY = '{$isPrimary}'"
			);
		}
		elseif($connection instanceof Main\DB\MssqlConnection)
		{
			$dbResult = $connection->query(
				"SELECT 'X' FROM b_crm_dp_entity_hash WHERE ENTITY_ID = {$entityID} AND ENTITY_TYPE_ID = {$entityTypeID} AND TYPE_ID = {$typeID} AND MATCH_HASH = '{$matchHash}'"
			);

			if(!is_array($dbResult->fetch()))
			{
				$connection->queryExecute(
					"INSERT INTO b_crm_dp_entity_hash(ENTITY_ID, ENTITY_TYPE_ID, TYPE_ID, MATCH_HASH, IS_PRIMARY)
						VALUES({$entityID}, {$entityTypeID}, {$typeID}, '{$matchHash}', '{$isPrimary}')"
				);
			}
		}
		elseif($connection instanceof Main\DB\OracleConnection)
		{
			$connection->queryExecute("MERGE INTO b_crm_dp_entity_hash USING (SELECT {$entityID} ENTITY_ID, {$entityTypeID} ENTITY_TYPE_ID, {$typeID} TYPE_ID, '{$matchHash}' MATCH_HASH FROM dual)
				source ON
				(
					source.ENTITY_ID = b_crm_dp_entity_hash.ENTITY_ID
					AND source.ENTITY_TYPE_ID = b_crm_dp_entity_hash.ENTITY_TYPE_ID
					AND source.TYPE_ID = b_crm_dp_entity_hash.TYPE_ID
					AND source.MATCH_HASH = b_crm_dp_entity_hash.MATCH_HASH
				)
				WHEN MATCHED THEN
					UPDATE SET b_crm_dp_entity_hash.IS_PRIMARY = '{$isPrimary}'
				WHEN NOT MATCHED THEN
					INSERT (ENTITY_ID, ENTITY_TYPE_ID, TYPE_ID, MATCH_HASH, IS_PRIMARY)
					VALUES({$entityID}, {$entityTypeID}, {$typeID}, '{$matchHash}', '{$isPrimary}')"
			);
		}
		else
		{
			$dbType = $connection->getType();
			throw new Main\NotSupportedException("The '{$dbType}' is not supported in current context");
		}
	}
	public static function deleteByFilter(array $filter)
	{
		$conditions = array();

		$entityID = isset($filter['ENTITY_ID']) ? (int)$filter['ENTITY_ID'] : 0;
		if($entityID > 0)
		{
			$conditions[] = "ENTITY_ID = {$entityID}";
		}

		$entityTypeID = isset($filter['ENTITY_TYPE_ID']) ? (int)$filter['ENTITY_TYPE_ID'] : \CCrmOwnerType::Undefined;
		if($entityTypeID > 0)
		{
			$conditions[] = "ENTITY_TYPE_ID = {$entityTypeID}";
		}

		$typeID = isset($filter['TYPE_ID']) ? (int)$filter['TYPE_ID'] : 0;
		if($typeID > 0)
		{
			$conditions[] = "TYPE_ID = {$typeID}";
		}

		if(!empty($conditions))
		{
			Main\Application::getConnection()->queryExecute('DELETE FROM  b_crm_dp_entity_hash WHERE '.implode(' AND ', $conditions));
		}
	}
}