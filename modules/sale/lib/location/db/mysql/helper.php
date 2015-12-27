<?php
/**
 * Bitrix Framework
 * @package Bitrix\Sale\Location
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 * 
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Sale\Location\DB;

use Bitrix\Main;

final class Helper extends CommonHelper
{
	public static function getSqlForAutoIncrement()
	{
		return 'auto_increment';
	}

	public static function mergeTables($toTable, $fromTable, $fldMap, $fldCondition)
	{
		$dbConnection = Main\HttpApplication::getConnection();
		$dbHelper = $dbConnection->getSqlHelper();

		$toTable = $dbHelper->forSql(trim($toTable));
		$fromTable = $dbHelper->forSql(trim($fromTable));

		if(!strlen($toTable) || !strlen($toTable) || !is_array($fldMap) || empty($fldMap) || empty($fldCondition))
			return false;

		// update tab1, tab2 set tab1.aa = tab2.bb, tab1.cc = tab2.dd where tab1.ee = tab2.ff 

		$sql = 'update '.$toTable.', '.$fromTable.' set ';

		$fields = array();
		foreach($fldMap as $toFld => $fromFld)
			$fields[] = $toTable.'.'.$dbHelper->forSql(trim($toFld)).' = '.$fromTable.'.'.$dbHelper->forSql(trim($fromFld));

		$sql .= implode(', ', $fields);

		$where = array();
		foreach($fldCondition as $left => $right)
			$where[] = $toTable.'.'.$dbHelper->forSql(trim($left)).' = '.$fromTable.'.'.$dbHelper->forSql(trim($right));

		$sql .= ' where '.implode(' and ', $where);

		$dbConnection->query($sql);

		return true;
	}

	public static function checkIndexNameExists($indexName, $tableName)
	{
		$dbConnection = Main\HttpApplication::getConnection();
		$dbHelper = $dbConnection->getSqlHelper();

		$indexName = trim($indexName);
		$tableName = $dbHelper->forSql(trim($tableName));

		if(!strlen($indexName) || !strlen($tableName))
			return false;

		$res = $dbConnection->query("show index from ".$tableName);

		while($item = $res->fetch())
		{
			if($item['Key_name'] == $indexName || $item['KEY_NAME'] == $indexName)
				return true;
		}

		return false;
	}

	public static function dropIndexByName($indexName, $tableName)
	{
		$dbConnection = Main\HttpApplication::getConnection();
		$dbHelper = $dbConnection->getSqlHelper();

		$indexName = $dbHelper->forSql(trim($indexName));
		$tableName = $dbHelper->forSql(trim($tableName));

		if(!strlen($indexName) || !strlen($tableName))
			return false;

		if(!static::checkIndexNameExists($indexName, $tableName))
			return false;

		$dbConnection->query("alter table {$tableName} drop index {$indexName}");

		return true;
	}

	public static function getMaxTransferUnit()
	{
		$dbConnection = Main\HttpApplication::getConnection();

		$res = $dbConnection->query("SHOW VARIABLES LIKE 'max_allowed_packet'")->fetch();
		if(!($res['Variable_name'] == 'max_allowed_packet' && $mtu = intval($res['Value'])))
			return 0;

		return $mtu;
	}

	// this function is used to adjust auto_increment value of a table to a certain position
	public static function resetAutoIncrement($tableName, $startIndex = 1)
	{
		$startIndex = intval($startIndex);
		if($startIndex <= 0 || !strlen($tableName))
			return false;

		$dbConnection = Main\HttpApplication::getConnection();
		$dbHelper = $dbConnection->getSqlHelper();

		$tableName = $dbHelper->forSql(trim($tableName));

		$dbConnection->query('alter table '.$tableName.' AUTO_INCREMENT = '.$startIndex);

		return true;
	}

	public static function getQuerySeparatorSql()
	{
		return ";";
	}
}