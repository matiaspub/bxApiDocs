<?php
namespace Bitrix\Crm\Integrity\Entity;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class DuplicateEntityStatisticsTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_dp_entity_stat';
	}

	public static function getMap()
	{
		return array(
			'ENTITY_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'primary' => true
			),
			'ENTITY_TYPE_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'primary' => true
			),
			'RANKING_DATA' => array(
				'data_type' => 'string'
			)
		);
	}

	public static function upsert(array $data)
	{
		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$entityID = isset($data['ENTITY_ID']) ? intval($data['ENTITY_ID']) : 0;
		$entityTypeID = isset($data['ENTITY_TYPE_ID']) ? intval($data['ENTITY_TYPE_ID']) : 0;
		$rankingData = isset($data['RANKING_DATA']) ? $data['RANKING_DATA'] : '';
		$rankingData = $sqlHelper->forSql($rankingData, 512);

		if($connection instanceof Main\DB\MysqlCommonConnection)
		{
			$connection->queryExecute(
				"INSERT INTO b_crm_dp_entity_stat(ENTITY_ID, ENTITY_TYPE_ID, RANKING_DATA)
					VALUES({$entityID}, {$entityTypeID}, '{$rankingData}')
					ON DUPLICATE KEY UPDATE RANKING_DATA = '{$rankingData}'"
			);
		}
		elseif($connection instanceof Main\DB\MssqlConnection)
		{
			$dbResult = $connection->query(
				"SELECT 'X' FROM b_crm_dp_entity_stat WHERE ENTITY_ID = {$entityID} AND ENTITY_TYPE_ID = {$entityTypeID}"
			);

			if(is_array($dbResult->fetch()))
			{
				$connection->queryExecute(
					"UPDATE b_crm_dp_entity_stat SET RANKING_DATA = '{$rankingData}'
						WHERE ENTITY_ID = {$entityID} AND ENTITY_TYPE_ID = {$entityTypeID}"
				);
			}
			else
			{
				$connection->queryExecute(
					"INSERT INTO b_crm_dp_entity_stat(ENTITY_ID, ENTITY_TYPE_ID, RANKING_DATA)
						VALUES({$entityID}, {$entityTypeID}, '{$rankingData}')"
				);
			}
		}
		elseif($connection instanceof Main\DB\OracleConnection)
		{
			$connection->queryExecute("MERGE INTO b_crm_dp_entity_stat USING (SELECT {$entityID} ENTITY_ID, {$entityTypeID} ENTITY_TYPE_ID FROM dual)
				source ON
				(
					source.ENTITY_ID = b_crm_dp_entity_stat.ENTITY_ID
					AND source.ENTITY_TYPE_ID = b_crm_dp_entity_stat.ENTITY_TYPE_ID
				)
				WHEN MATCHED THEN
					UPDATE SET b_crm_dp_entity_stat.RANKING_DATA = '{$rankingData}'
				WHEN NOT MATCHED THEN
					INSERT (ENTITY_ID, ENTITY_TYPE_ID, RANKING_DATA)
					VALUES({$entityID}, {$entityTypeID}, '{$rankingData}')"
			);
		}
		else
		{
			$dbType = $connection->getType();
			throw new Main\NotSupportedException("The '{$dbType}' is not supported in current context");
		}
	}
}