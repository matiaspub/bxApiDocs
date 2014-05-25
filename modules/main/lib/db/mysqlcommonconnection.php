<?php
namespace Bitrix\Main\DB;

abstract class MysqlCommonConnection
	extends Connection
{
	/*********************************************************
	 * DDL
	 *********************************************************/

	public function isTableExists($tableName)
	{
		$tableName = preg_replace("/[^a-z0-9%_]+/i", "", $tableName);
		$tableName = trim($tableName);

		if (strlen($tableName) <= 0)
		{
			return false;
		}

		$dbResult = $this->query("SHOW TABLES LIKE '".$this->getSqlHelper()->forSql($tableName)."'");

		return (bool) $dbResult->fetch();
	}

	public function isIndexExists($tableName, array $arColumns)
	{
		return $this->getIndexName($tableName, $arColumns) !== null;
	}

	public function getIndexName($tableName, array $arColumns, $strict = false)
	{
		if (!is_array($arColumns) || count($arColumns) <= 0)
			return null;

		$tableName = preg_replace("/[^a-z0-9_]+/i", "", $tableName);
		$tableName = trim($tableName);

		$rs = $this->query("SHOW INDEX FROM `".$this->getSqlHelper()->forSql($tableName)."`");
		if (!$rs)
			return null;

		$arIndexes = array();
		while ($ar = $rs->fetch())
			$arIndexes[$ar["Key_name"]][$ar["Seq_in_index"] - 1] = $ar["Column_name"];

		$strColumns = implode(",", $arColumns);
		foreach ($arIndexes as $Key_name => $arKeyColumns)
		{
			ksort($arKeyColumns);
			$strKeyColumns = implode(",", $arKeyColumns);
			if ($strict)
			{
				if ($strKeyColumns === $strColumns)
					return $Key_name;
			}
			else
			{
				if (substr($strKeyColumns, 0, strlen($strColumns)) === $strColumns)
					return $Key_name;
			}
		}

		return null;
	}

	public function renameTable($currentName, $newName)
	{
		$this->query('RENAME TABLE '.$this->getSqlHelper()->quote($currentName).' TO '.$this->getSqlHelper()->quote($newName));
	}

	/*********************************************************
	 * Transaction
	 *********************************************************/

	public function startTransaction()
	{
		$this->query("START TRANSACTION");
	}

	public function commitTransaction()
	{
		$this->query("COMMIT");
	}

	public function rollbackTransaction()
	{
		$this->query("ROLLBACK");
	}
}