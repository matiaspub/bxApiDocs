<?php
namespace Bitrix\Main\DB;

use Bitrix\Main\Entity;

class MysqlSqlHelper extends MysqlCommonSqlHelper
{
	/**
	 * Escapes special characters in a string for use in an SQL statement.
	 *
	 * @param string $value Value to be escaped.
	 * @param integer $maxLength Limits string length if set.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод выводит специальные символы в строке для использования в SQL операторе</p>
	*
	*
	* @param string $value  Значение для вывода
	*
	* @param integer $maxLength  Лимит длины строки, если существует.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqlsqlhelper/forsql.php
	* @author Bitrix
	*/
	public function forSql($value, $maxLength = 0)
	{
		if ($maxLength > 0)
			$value = substr($value, 0, $maxLength);

		return mysql_real_escape_string($value, $this->connection->getResource());
	}

	/**
	 * Returns instance of a descendant from Entity\ScalarField
	 * that matches database type.
	 *
	 * @param string $name Database column name.
	 * @param mixed $type Database specific type.
	 * @param array $parameters Additional information.
	 *
	 * @return Entity\ScalarField
	 */
	
	/**
	* <p>Нестатический метод возвращает исключение потомка из <code>Entity\ScalarField</code>, который соответствует типу базы данных.</p>
	*
	*
	* @param string $name  Название колонки Базы данных.
	*
	* @param mixed $type  Тип характерный для Базы данных.
	*
	* @param array $parameters = null Дополнительная информация.
	*
	* @return \Bitrix\Main\Entity\ScalarField 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqlsqlhelper/getfieldbycolumntype.php
	* @author Bitrix
	*/
	static public function getFieldByColumnType($name, $type, array $parameters = null)
	{
		switch ($type)
		{
			case "int":
				return new Entity\IntegerField($name);

			case "real":
				return new Entity\FloatField($name);

			case "datetime":
			case "timestamp":
				return new Entity\DatetimeField($name);

			case "date":
				return new Entity\DateField($name);
		}
		return new Entity\StringField($name);
	}
}
