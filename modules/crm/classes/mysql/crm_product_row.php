<?php
class CCrmProductRow extends CAllCrmProductRow
{
	const TABLE_NAME = 'b_crm_product_row';
	const CONFIG_TABLE_NAME = 'b_crm_product_row_cfg';
	const DB_TYPE = 'MYSQL';

	// Contract -->
	public static function DeleteByOwner($ownerType, $ownerID)
	{
		$ownerType = strval($ownerType);
		$ownerID = intval($ownerID);

		global $DB;
		$ownerType = $DB->ForSql($ownerType);

		$tableName = self::TABLE_NAME;
		$DB->Query(
			"DELETE FROM {$tableName} WHERE OWNER_TYPE = '{$ownerType}' AND OWNER_ID = {$ownerID}", false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
	}

	public static function DoSaveRows($ownerType, $ownerID, $arRows)
	{
		global $DB;

		self::DeleteByOwner($ownerType, $ownerID);

		if(count($arRows) == 0)
		{
			return true;
		}

		$bulkColumns = '';
		$bulkValues = array();
		foreach($arRows as &$arRow)
		{
			$data = $DB->PrepareInsert(self::TABLE_NAME, $arRow);

			$cols = $data[0];
			$vals = $data[1];

			if(!isset($vals[0])) //empty values
			{
				continue;
			}

			if(!isset($bulkColumns[0]))
			{
				$bulkColumns = $cols;
			}

			$bulkValues[] = $vals;
		}

		if(count($bulkValues) == 0)
		{
			self::RegisterError('There are no values for insert.');
			return false;
		}

		$query = '';
		foreach($bulkValues as &$value)
		{
			$query .= (isset($query[0]) ? ',' : '').'('.$value.')';
		}

		if(!isset($query[0]))
		{
			self::RegisterError('Could not build query.');
			return false;
		}

		$query = 'INSERT INTO '.self::TABLE_NAME.'('.$bulkColumns.') VALUES'.$query;
		$DB->Query(
			$query,
			false,
			'File: '.__FILE__.'<br/>Line: '.__LINE__
		);

		return true;
	}

	public static function LoadSettings($ownerType, $ownerID)
	{
		$ownerType = strval($ownerType);
		$ownerID = intval($ownerID);

		global $DB;
		$tableName = self::CONFIG_TABLE_NAME;
		$ownerType = $DB->ForSql($ownerType);
		$dbResult = $DB->Query("SELECT SETTINGS FROM {$tableName} WHERE OWNER_TYPE = '{$ownerType}' AND OWNER_ID = {$ownerID}", false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
		$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
		$s = is_array($fields) && isset($fields['SETTINGS']) ? $fields['SETTINGS'] : '';
		if($s === '')
		{
			return array();
		}

		return unserialize($s);
	}

	public static function SaveSettings($ownerType, $ownerID, $settings)
	{
		$ownerType = $ownerType;
		$ownerID = intval($ownerID);

		global $DB;
		$tableName = self::CONFIG_TABLE_NAME;
		$ownerType = $DB->ForSql($ownerType);
		$s = $DB->ForSql(serialize($settings));
		$sql = "INSERT INTO {$tableName}(OWNER_ID, OWNER_TYPE, SETTINGS)
			VALUES({$ownerID}, '{$ownerType}', '{$s}')
			ON DUPLICATE KEY UPDATE SETTINGS = '{$s}'";

		$DB->Query($sql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
	}
	// <-- Contract
}
