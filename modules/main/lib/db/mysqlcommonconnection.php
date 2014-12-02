<?php
namespace Bitrix\Main\DB;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity;

abstract class MysqlCommonConnection extends Connection
{
	protected $engine = "";

	/**
	 * $configuration may contain following keys:
	 * <ul>
	 * <li>host
	 * <li>database
	 * <li>login
	 * <li>password
	 * <li>initCommand
	 * <li>options
	 * <li>engine
	 * </ul>
	 *
	 * @param array $configuration Array of Name => Value pairs.
	 */
	public function __construct(array $configuration)
	{
		parent::__construct($configuration);
		$this->engine = isset($configuration['engine']) ? $configuration['engine'] : "";
	}

	/**
	 * Checks if a table exists.
	 *
	 * @param string $tableName The table name.
	 *
	 * @return boolean
	 */
	public function isTableExists($tableName)
	{
		$tableName = preg_replace("/[^a-z0-9%_]+/i", "", $tableName);
		$tableName = trim($tableName);

		if (strlen($tableName) <= 0)
		{
			return false;
		}

		$result = $this->query("SHOW TABLES LIKE '".$this->getSqlHelper()->forSql($tableName)."'");

		return (bool) $result->fetch();
	}

	/**
	 * Checks if an index exists.
	 * Actual columns in the index may differ from requested.
	 * $columns may present an "prefix" of actual index columns.
	 *
	 * @param string $tableName A table name.
	 * @param array  $columns An array of columns in the index.
	 *
	 * @return boolean
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function isIndexExists($tableName, array $columns)
	{
		return $this->getIndexName($tableName, $columns) !== null;
	}

	/**
	 * Returns the name of an index.
	 *
	 * @param string $tableName A table name.
	 * @param array $columns An array of columns in the index.
	 * @param bool $strict The flag indicating that the columns in the index must exactly match the columns in the $arColumns parameter.
	 *
	 * @return string|null Name of the index or null if the index doesn't exist.
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function getIndexName($tableName, array $columns, $strict = false)
	{
		if (!is_array($columns) || count($columns) <= 0)
			return null;

		$tableName = preg_replace("/[^a-z0-9_]+/i", "", $tableName);
		$tableName = trim($tableName);

		$rs = $this->query("SHOW INDEX FROM `".$this->getSqlHelper()->forSql($tableName)."`");
		if (!$rs)
			return null;

		$indexes = array();
		while ($ar = $rs->fetch())
		{
			$indexes[$ar["Key_name"]][$ar["Seq_in_index"] - 1] = $ar["Column_name"];
		}

		$columnsList = implode(",", $columns);
		foreach ($indexes as $indexName => $indexColumns)
		{
			ksort($indexColumns);
			$indexColumnList = implode(",", $indexColumns);
			if ($strict)
			{
				if ($indexColumnList === $columnsList)
					return $indexName;
			}
			else
			{
				if (substr($indexColumnList, 0, strlen($columnsList)) === $columnsList)
					return $indexName;
			}
		}

		return null;
	}

	/**
	 * Returns fields objects according to the columns of a table.
	 * Table must exists.
	 *
	 * @param string $tableName The table name.
	 *
	 * @return Entity\ScalarField[] An array of objects with columns information.
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function getTableFields($tableName)
	{
		if (!isset($this->tableColumnsCache[$tableName]))
		{
			$this->connectInternal();

			$query = $this->queryInternal("SELECT * FROM ".$this->getSqlHelper()->quote($tableName)." LIMIT 0");

			$result = $this->createResult($query);

			$this->tableColumnsCache[$tableName] = $result->getFields();
		}
		return $this->tableColumnsCache[$tableName];
	}

	/**
	 * @param string $tableName Name of the new table.
	 * @param \Bitrix\Main\Entity\ScalarField[] $fields Array with columns descriptions.
	 * @param string[] $primary Array with primary key column names.
	 * @param string[] $autoincrement Which columns will be auto incremented ones.
	 *
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function createTable($tableName, $fields, $primary = array(), $autoincrement = array())
	{
		$sql = 'CREATE TABLE '.$this->getSqlHelper()->quote($tableName).' (';
		$sqlFields = array();

		foreach ($fields as $columnName => $field)
		{
			if (!($field instanceof Entity\ScalarField))
			{
				throw new ArgumentException(sprintf(
					'Field `%s` should be an Entity\ScalarField instance', $columnName
				));
			}

			$sqlFields[] = $this->getSqlHelper()->quote($columnName)
				. ' ' . $this->getSqlHelper()->getColumnTypeByField($field)
				. ' NOT NULL' // null for oracle if is not primary
				. (in_array($columnName, $autoincrement, true) ? ' AUTO_INCREMENT' : '')
			;
		}

		$sql .= join(', ', $sqlFields);

		if (!empty($primary))
		{
			foreach ($primary as &$primaryColumn)
			{
				$primaryColumn = $this->getSqlHelper()->quote($primaryColumn);
			}

			$sql .= ', PRIMARY KEY('.join(', ', $primary).')';
		}

		$sql .= ')';

		if ($this->engine)
		{
			$sql .= ' Engine='.$this->engine;
		}

		$this->query($sql);
	}

	/**
	 * Creates index on column(s)
	 * @api
	 *
	 * @param string          $tableName     Name of the table.
	 * @param string          $indexName     Name of the new index.
	 * @param string|string[] $columnNames   Name of the column or array of column names to be included into the index.
	 * @param string[]        $columnLengths Array of column names and maximum length for them.
	 *
	 * @return Result
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function createIndex($tableName, $indexName, $columnNames, $columnLengths = null)
	{
		if (!is_array($columnNames))
		{
			$columnNames = array($columnNames);
		}

		$sqlHelper = $this->getSqlHelper();

		foreach ($columnNames as &$columnName)
		{
			if (is_array($columnLengths) && isset($columnLengths[$columnName]) && $columnLengths[$columnName] > 0)
			{
				$maxLength = intval($columnLengths[$columnName]);
			}
			else
			{
				$maxLength = 0;
			}

			$columnName = $sqlHelper->quote($columnName);
			if ($maxLength > 0)
			{
				$columnName .= '('.$maxLength.')';
			}
		}
		unset($columnName);

		$sql = 'CREATE INDEX '.$sqlHelper->quote($indexName).' ON '.$sqlHelper->quote($tableName).' ('.join(', ', $columnNames).')';

		return $this->query($sql);
	}

	/**
	 * Renames the table. Renamed table must exists and new name must not be occupied by any database object.
	 *
	 * @param string $currentName Old name of the table.
	 * @param string $newName New name of the table.
	 *
	 * @return void
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function renameTable($currentName, $newName)
	{
		$this->query('RENAME TABLE '.$this->getSqlHelper()->quote($currentName).' TO '.$this->getSqlHelper()->quote($newName));
	}

	/**
	 * Drops the table.
	 *
	 * @param string $tableName Name of the table to be dropped.
	 *
	 * @return void
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function dropTable($tableName)
	{
		$this->query('DROP TABLE '.$this->getSqlHelper()->quote($tableName));
	}

	/*********************************************************
	 * Transaction
	 *********************************************************/

	/**
	 * Starts new database transaction.
	 *
	 * @return void
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function startTransaction()
	{
		$this->query("START TRANSACTION");
	}

	/**
	 * Commits started database transaction.
	 *
	 * @return void
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function commitTransaction()
	{
		$this->query("COMMIT");
	}

	/**
	 * Rollbacks started database transaction.
	 *
	 * @return void
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function rollbackTransaction()
	{
		$this->query("ROLLBACK");
	}

	/*********************************************************
	 * Type, version, cache, etc.
	 *********************************************************/

	/**
	 * Sets default storage engine for all consequent CREATE TABLE statements and all other relevant DDL.
	 * Storage engine read from .settings.php file. It is 'engine' key of the 'default' from the 'connections'.
	 *
	 * @return void
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function setStorageEngine()
	{
		if ($this->engine)
		{
			$this->query("SET storage_engine = '".$this->engine."'");
		}
	}
}