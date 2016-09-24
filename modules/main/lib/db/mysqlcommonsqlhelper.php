<?php
namespace Bitrix\Main\DB;

use Bitrix\Main;
use Bitrix\Main\Type;
use Bitrix\Main\Entity;

abstract class MysqlCommonSqlHelper extends SqlHelper
{
	/**
	 * Returns an identificator escaping left character.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод. Экранирование идентификаторов - левый символ.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqlcommonsqlhelper/getleftquote.php
	* @author Bitrix
	*/
	static public function getLeftQuote()
	{
		return '`';
	}

	/**
	 * Returns an identificator escaping right character.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод. Экранирование идентификатора - правый символ</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqlcommonsqlhelper/getrightquote.php
	* @author Bitrix
	*/
	static public function getRightQuote()
	{
		return '`';
	}

	/**
	 * Returns maximum length of an alias in a select statement
	 *
	 * @return integer
	 */
	
	/**
	* <p>Нестатический метод возвращает значение максимальной длины алиаса в выбранном операторе.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqlcommonsqlhelper/getaliaslength.php
	* @author Bitrix
	*/
	static public function getAliasLength()
	{
		return 256;
	}

	/**
	 * Returns database specific query delimiter for batch processing.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает специальный разделитель запроса для пакетной обработки.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqlcommonsqlhelper/getquerydelimiter.php
	* @author Bitrix
	*/
	static public function getQueryDelimiter()
	{
		return ';';
	}

	/**
	 * Returns function for getting current time.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает sql-функцию для получения текущей даты и времени.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqlcommonsqlhelper/getcurrentdatetimefunction.php
	* @author Bitrix
	*/
	static public function getCurrentDateTimeFunction()
	{
		return "NOW()";
	}

	/**
	 * Returns function for getting current date without time part.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает sql-функцию для получения даты без времени.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqlcommonsqlhelper/getcurrentdatefunction.php
	* @author Bitrix
	*/
	static public function getCurrentDateFunction()
	{
		return "CURDATE()";
	}

	/**
	 * Returns function for adding seconds time interval to $from.
	 * <p>
	 * If $from is null or omitted, then current time is used.
	 * <p>
	 * $seconds and $from parameters are SQL unsafe.
	 *
	 * @param integer $seconds How many seconds to add.
	 * @param integer $from Datetime database field of expression.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает sql-функцию для добавления секунд к интервалу, указанному в параметре <code>$from</code>. Если значение параметра <code>$from</code> равно нулю или прошло, то используется текущее время.</p> <p>Параметры метода не будут приведены к SQL безопасному виду.</p>
	*
	*
	* @param integer $seconds  Сколько секунд нужно добавить.
	*
	* @param integer $from = null Дата и время базы данных.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqlcommonsqlhelper/addsecondstodatetime.php
	* @author Bitrix
	*/
	static public function addSecondsToDateTime($seconds, $from = null)
	{
		if ($from === null)
		{
			$from = static::getCurrentDateTimeFunction();
		}

		return 'DATE_ADD('.$from.', INTERVAL '.$seconds.' SECOND)';
	}

	/**
	 * Returns function cast $value to datetime database type.
	 * <p>
	 * $value parameter is SQL unsafe.
	 *
	 * @param string $value Database field or expression to cast.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает sql-функцию передающую значение <code>$value</code> в тип Дата/Время базы данных.</p> <p>Параметр <code>$value</code> не приводится к SQL безопасному виду.</p>
	*
	*
	* @param string $value  Поле и оператор базы данных для передачи.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqlcommonsqlhelper/getdatetimetodatefunction.php
	* @author Bitrix
	*/
	static public function getDatetimeToDateFunction($value)
	{
		return 'DATE('.$value.')';
	}

	/**
	 * Returns database expression for converting $field value according the $format.
	 * <p>
	 * Following format parts converted:
	 * - YYYY   A full numeric representation of a year, 4 digits
	 * - MMMM   A full textual representation of a month, such as January or March
	 * - MM     Numeric representation of a month, with leading zeros
	 * - MI     Minutes with leading zeros
	 * - M      A short textual representation of a month, three letters
	 * - DD     Day of the month, 2 digits with leading zeros
	 * - HH     24-hour format of an hour with leading zeros
	 * - H      24-hour format of an hour without leading zeros
	 * - GG     12-hour format of an hour with leading zeros
	 * - G      12-hour format of an hour without leading zeros
	 * - SS     Seconds with leading zeros
	 * - TT     AM or PM
	 * - T      AM or PM
	 * <p>
	 * $field parameter is SQL unsafe.
	 *
	 * @param string $format Format string.
	 * @param string $field Database field or expression.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает оператор Базы данных для конвертации значения <code>$field</code> в соответствии с значением <code>$format</code>.</p> <p>Будут конвертированы:</p> <ul> <li>YYYY - Полное числовое представление года, 4 цифры.</li> <li>MMMM - Полное текстовое представление месяца, например: Январь или Март.</li>  <li>MM Числовое представление месяца с ведущим нулём.</li>  <li>MI - Минуты с начального нуля.</li> <li>M - короткое текстовое представление месяца, три буквы.</li>  <li>DD - День месяца, две цифры с ведущим нулём.</li>  <li>HH - 24-ичасовой формат времени часов с ведущим нулём.</li> <li>H - 24-ичасовой формат часов без ведущего нуля.</li>  <li>GG - 12-ичасовой формат с ведущим нулём.</li>  <li>G - 12-ичасовой формат без ведущего нуля.</li> <li>SS - Минуты с ведущим нулём.</li>  <li>TT - AM или PM</li>  <li>T - am/pm.</li>  </ul> <p>Параметр <code>$field</code> не приводится к SQL безопасному виду.</p>
	*
	*
	* @param string $format  Формат.
	*
	* @param string $field = null Область базы данных или выражение.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqlcommonsqlhelper/formatdate.php
	* @author Bitrix
	*/
	static public function formatDate($format, $field = null)
	{
		static $search  = array(
			"YYYY",
			"MMMM",
			"MM",
			"MI",
			"DD",
			"HH",
			"GG",
			"G",
			"SS",
			"TT",
			"T"
		);
		static $replace = array(
			"%Y",
			"%M",
			"%m",
			"%i",
			"%d",
			"%H",
			"%h",
			"%l",
			"%s",
			"%p",
			"%p"
		);

		$format = str_replace($search, $replace, $format);

		if (strpos($format, '%H') === false)
		{
			$format = str_replace("H", "%h", $format);
		}

		if (strpos($format, '%M') === false)
		{
			$format = str_replace("M", "%b", $format);
		}

		if($field === null)
		{
			return $format;
		}
		else
		{
			return "DATE_FORMAT(".$field.", '".$format."')";
		}
	}

	/**
	 * Returns function for concatenating database fields or expressions.
	 * <p>
	 * All parameters are SQL unsafe.
	 *
	 * @param string $field,... Database fields or expressions.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает sql-функцию для конкатенации полей и операторов базы данных.</p> <p>Параметры не сводятся к SQL безопасному виду.</p>
	*
	*
	* @param string $field  Поля и операторы базы данных.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqlcommonsqlhelper/getconcatfunction.php
	* @author Bitrix
	*/
	static public function getConcatFunction()
	{
		$str = "";
		$ar = func_get_args();
		if (is_array($ar))
			$str .= implode(", ", $ar);
		if (strlen($str) > 0)
			$str = "CONCAT(".$str.")";
		return $str;
	}

	/**
	 * Returns function for testing database field or expressions
	 * against NULL value. When it is NULL then $result will be returned.
	 * <p>
	 * All parameters are SQL unsafe.
	 *
	 * @param string $expression Database field or expression for NULL test.
	 * @param string $result Database field or expression to return when $expression is NULL.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает sql-функцию для проверки полей и операторов базы данных на значение <i>NULL</i>. Если равно <i>NULL</i>, то будет возвращён <code>$result</code>.</p> <p></p> <p>Все параметры не приводятся к SQL безопасному виду.</p>
	*
	*
	* @param string $expression  Поле или оператор базы данных для проверки на <i>NULL</i>.
	*
	* @param string $result  Поле или оператор базы данных, которые будут возвращены если
	* <code>$expression</code> будет равно <i>NULL</i>.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqlcommonsqlhelper/getisnullfunction.php
	* @author Bitrix
	*/
	static public function getIsNullFunction($expression, $result)
	{
		return "IFNULL(".$expression.", ".$result.")";
	}

	/**
	 * Returns function for getting length of database field or expression.
	 * <p>
	 * $field parameter is SQL unsafe.
	 *
	 * @param string $field Database field or expression.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает sql-функцию для получения длины поля или оператора базы данных.</p> <p>Параметр <code>$field</code> не приводится к SQL безопасному виду.</p>
	*
	*
	* @param string $field  Поле или оператор базы данных.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqlcommonsqlhelper/getlengthfunction.php
	* @author Bitrix
	*/
	static public function getLengthFunction($field)
	{
		return "LENGTH(".$field.")";
	}

	/**
	 * Returns function for converting string value into datetime.
	 * $value must be in YYYY-MM-DD HH:MI:SS format.
	 * <p>
	 * $value parameter is SQL unsafe.
	 *
	 * @param string $value String in YYYY-MM-DD HH:MI:SS format.
	 *
	 * @return string
	 * @see \Bitrix\Main\DB\MssqlSqlHelper::formatDate
	 */
	
	/**
	* <p>Нестатический метод возвращает sql-функцию для конвертации строкового значения в тип Дата/время.</p> <p>Значение параметра <code>$value</code> не приводится к SQL безопасному виду.</p>
	*
	*
	* @param string $value  Строка в формате YYYY-MM-DD HH:MI:SS.
	*
	* @return string 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlsqlhelper/formatdate.php">\Bitrix\Main\DB\MssqlSqlHelper::formatDate</a></li>
	* </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqlcommonsqlhelper/getchartodatefunction.php
	* @author Bitrix
	*/
	static public function getCharToDateFunction($value)
	{
		return "'".$value."'";
	}

	/**
	 * Returns function for converting database field or expression into string.
	 * <p>
	 * Result string will be in YYYY-MM-DD HH:MI:SS format.
	 * <p>
	 * $fieldName parameter is SQL unsafe.
	 *
	 * @param string $fieldName Database field or expression.
	 *
	 * @return string
	 * @see \Bitrix\Main\DB\MssqlSqlHelper::formatDate
	 */
	
	/**
	* <p>Нестатический метод возвращает sql-функцию для конвертации полей и операторов базы данных в строку.</p> <p>Результирующая строка будет в формате: YYYY-MM-DD HH:MI:SS.</p> <p>Параметр <code>$fieldName</code> не приводится к SQL безопасному виду.</p>
	*
	*
	* @param string $fieldName  Поле или оператор базы данных.
	*
	* @return string 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlsqlhelper/formatdate.php">\Bitrix\Main\DB\MssqlSqlHelper::formatDate</a></li>
	* </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqlcommonsqlhelper/getdatetocharfunction.php
	* @author Bitrix
	*/
	static public function getDateToCharFunction($fieldName)
	{
		return $fieldName;
	}

	/**
	 * Returns callback to be called for a field value on fetch.
	 *
	 * @param Entity\ScalarField $field Type "source".
	 *
	 * @return false|callback
	 */
	
	/**
	* <p>Нестатический метод возвращает ответ запрошенный для значения поля в выборке.</p>
	*
	*
	* @param mixed $Bitrix  Тип "источника".
	*
	* @param Bitri $Main  
	*
	* @param Mai $Entity  
	*
	* @param ScalarField $field  
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqlcommonsqlhelper/getconverter.php
	* @author Bitrix
	*/
	static public function getConverter(Entity\ScalarField $field)
	{
		if($field instanceof Entity\DatetimeField)
		{
			return array($this, "convertDatetimeField");
		}
		elseif($field instanceof Entity\DateField)
		{
			return array($this, "convertDateField");
		}
		else
		{
			return parent::getConverter($field);
		}
	}

	/**
	 * Converts string into \Bitrix\Main\Type\DateTime object.
	 * <p>
	 * Helper function.
	 *
	 * @param string $value Value fetched.
	 *
	 * @return null|\Bitrix\Main\Type\DateTime
	 * @see \Bitrix\Main\Db\MysqlCommonSqlHelper::getConverter
	 */
	
	/**
	* <p>Нестатический метод конвертирует строку в объект <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/datetime/index.php">\Bitrix\Main\Type\DateTime</a>.</p> <p>Вспомогательная функция.</p>
	*
	*
	* @param string $value  Полученное значение.
	*
	* @return null|\Bitrix\Main\Type\DateTime 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqlcommonsqlhelper/getconverter.php">\Bitrix\Main\Db\MysqlCommonSqlHelper::getConverter</a></li>
	* </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqlcommonsqlhelper/convertdatetimefield.php
	* @author Bitrix
	*/
	static public function convertDatetimeField($value)
	{
		if($value !== null && $value != '0000-00-00 00:00:00')
		{
			return new Type\DateTime($value, "Y-m-d H:i:s");
		}

		return null;
	}

	/**
	 * Converts string into \Bitrix\Main\Type\Date object.
	 * <p>
	 * Helper function.
	 *
	 * @param string $value Value fetched.
	 *
	 * @return null|\Bitrix\Main\Type\DateTime
	 * @see \Bitrix\Main\Db\MysqlCommonSqlHelper::getConverter
	 */
	
	/**
	* <p>Нестатический метод конвертирует строку в объект <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/date/index.php">\Bitrix\Main\Type\Date</a>.</p> <p> Вспомогательная функция.</p>
	*
	*
	* @param string $value  Переданное значение.
	*
	* @return null|\Bitrix\Main\Type\DateTime 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqlcommonsqlhelper/getconverter.php">\Bitrix\Main\Db\MysqlCommonSqlHelper::getConverter</a></li>
	* </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqlcommonsqlhelper/convertdatefield.php
	* @author Bitrix
	*/
	static public function convertDateField($value)
	{
		if($value !== null && $value != '0000-00-00')
		{
			return new Type\Date($value, "Y-m-d");
		}

		return null;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $fieldName
	 *
	 * return string
	 */
	
	/**
	* <p>Нестатический метод возвращает CAST выражение для конвертируемого поля или строчного выражения.</p>
	*
	*
	* @param string $fieldName  Возвращенная строка
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqlcommonsqlhelper/casttochar.php
	* @author Bitrix
	*/
	static public function castToChar($fieldName)
	{
		return 'CAST('.$fieldName.' AS char)';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $fieldName
	 *
	 * return string
	 */
	static public function softCastTextToChar($fieldName)
	{
		return $fieldName;
	}

	/**
	 * Returns a column type according to ScalarField object.
	 *
	 * @param Entity\ScalarField $field Type "source".
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает тип колонки в соответствии с объектом скалярного поля.</p>
	*
	*
	* @param mixed $Bitrix  Tип "источника".
	*
	* @param Bitri $Main  
	*
	* @param Mai $Entity  
	*
	* @param ScalarField $field  
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqlcommonsqlhelper/getcolumntypebyfield.php
	* @author Bitrix
	*/
	static public function getColumnTypeByField(Entity\ScalarField $field)
	{
		if ($field instanceof Entity\IntegerField)
		{
			return 'int';
		}
		elseif ($field instanceof Entity\FloatField)
		{
			return 'double';
		}
		elseif ($field instanceof Entity\DatetimeField)
		{
			return 'datetime';
		}
		elseif ($field instanceof Entity\DateField)
		{
			return 'date';
		}
		elseif ($field instanceof Entity\TextField)
		{
			return 'text';
		}
		elseif ($field instanceof Entity\BooleanField)
		{
			$values = $field->getValues();

			if (preg_match('/^[0-9]+$/', $values[0]) && preg_match('/^[0-9]+$/', $values[1]))
			{
				return 'int';
			}
			else
			{
				return 'varchar('.max(strlen($values[0]), strlen($values[1])).')';
			}
		}
		elseif ($field instanceof Entity\EnumField)
		{
			return 'varchar('.max(array_map('strlen', $field->getValues())).')';
		}
		else
		{
			// string by default
			$defaultLength = false;
			foreach ($field->getValidators() as $validator)
			{
				if ($validator instanceof Entity\Validator\Length)
				{
					if ($defaultLength === false || $defaultLength > $validator->getMax())
					{
						$defaultLength = $validator->getMax();
					}
				}
			}
			return 'varchar('.($defaultLength > 0? $defaultLength: 255).')';
		}
	}

	/**
	 * Transforms Sql according to $limit and $offset limitations.
	 * <p>
	 * You must specify $limit when $offset is set.
	 *
	 * @param string $sql Sql text.
	 * @param integer $limit Maximum number of rows to return.
	 * @param integer $offset Offset of the first row to return, starting from 0.
	 *
	 * @return string
	 * @throws Main\ArgumentException
	 */
	
	/**
	* <p>Нестатический метод трансформирует Sql запрос в соответствии со значениями параметров <code>$limit</code> и <code>$offset</code>.</p> <p>Параметр <code>$limit</code> должен быть определён к моменту задания <code>$offset</code>.</p>
	*
	*
	* @param string $sql  Текст Sql запроса.
	*
	* @param integer $limit  Максимальное число строк для возврата.
	*
	* @param integer $offset  Смещение первой строки для возврата, начинается с 0.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqlcommonsqlhelper/gettopsql.php
	* @author Bitrix
	*/
	static public function getTopSql($sql, $limit, $offset = 0)
	{
		$offset = intval($offset);
		$limit = intval($limit);

		if ($offset > 0 && $limit <= 0)
			throw new \Bitrix\Main\ArgumentException("Limit must be set if offset is set");

		if ($limit > 0)
		{
			$sql .= "\nLIMIT ".$offset.", ".$limit."\n";
		}

		return $sql;
	}

	/**
	 * Builds the strings for the SQL MERGE command for the given table.
	 *
	 * @param string $tableName A table name.
	 * @param array $primaryFields Array("column")[] Primary key columns list.
	 * @param array $insertFields Array("column" => $value)[] What to insert.
	 * @param array $updateFields Array("column" => $value)[] How to update.
	 *
	 * @return array (merge)
	 */
	
	/**
	* <p>Нестатический метод выстраивает строку для SQL-команды MERGE для указанной таблицы.</p>
	*
	*
	* @param string $tableName  Название таблицы
	*
	* @param array $primaryFields  Массив первичных ключей списка колонок вида: <pre
	* class="syntax">Array("column")[]</pre>
	*
	* @param array $insertFields  Массив вставки вида: <pre class="syntax">Array("column" =&gt; $value)[]</pre>
	*
	* @param array $updateFields  Массив обновлений вида: <pre class="syntax">Array("column" =&gt; $value)[]</pre>
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqlcommonsqlhelper/preparemerge.php
	* @author Bitrix
	*/
	public function prepareMerge($tableName, array $primaryFields, array $insertFields, array $updateFields)
	{
		$insert = $this->prepareInsert($tableName, $insertFields);
		$update = $this->prepareUpdate($tableName, $updateFields);

		if (
			$insert && $insert[0] != "" && $insert[1] != ""
			&& $update && $update[1] != ""
		)
		{
			$sql = "
				INSERT INTO ".$this->quote($tableName)." (".$insert[0].")
				VALUES (".$insert[1].")
				ON DUPLICATE KEY UPDATE ".$update[0]."
			";
		}
		else
		{
			$sql = "";
		}

		return array(
			$sql
		);
	}
}
