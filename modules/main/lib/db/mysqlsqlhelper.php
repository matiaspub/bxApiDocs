<?php
namespace Bitrix\Main\DB;

class MysqlSqlHelper extends MysqliSqlHelper
{
	public function forSql($value, $maxLength = 0)
	{
		if ($maxLength > 0)
			$value = substr($value, 0, $maxLength);

		return mysql_real_escape_string($value, $this->dbConnection->getResource());
	}
}
