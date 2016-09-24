<?php
namespace Bitrix\Main\DB;

use Bitrix\Main;
use Bitrix\Main\Type;
use Bitrix\Main\Entity;

class MssqlSqlHelper extends SqlHelper
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlsqlhelper/getleftquote.php
	* @author Bitrix
	*/
	static public function getLeftQuote()
	{
		return '[';
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlsqlhelper/getrightquote.php
	* @author Bitrix
	*/
	static public function getRightQuote()
	{
		return ']';
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlsqlhelper/getaliaslength.php
	* @author Bitrix
	*/
	static public function getAliasLength()
	{
		return 28;
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlsqlhelper/getquerydelimiter.php
	* @author Bitrix
	*/
	static public function getQueryDelimiter()
	{
		return "\nGO";
	}

	/**
	 * Escapes special characters in a string for use in an SQL statement.
	 *
	 * @param string $value Value to be escaped.
	 * @param integer $maxLength Limits string length if set.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод выводит специальные символы в строке для использования в SQL операторе.</p>
	*
	*
	* @param string $value  Значение для вывода
	*
	* @param integer $maxLength  Лимит длины строки, если существует.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlsqlhelper/forsql.php
	* @author Bitrix
	*/
	public static function forSql($value, $maxLength = 0)
	{
		if ($maxLength > 0)
		{
			$value = substr($value, 0, $maxLength);
		}
		$value = str_replace("'", "''", $value);
		$value = str_replace("\x00", "", $value);
		return $value;
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlsqlhelper/getcurrentdatetimefunction.php
	* @author Bitrix
	*/
	static public function getCurrentDateTimeFunction()
	{
		return "GETDATE()";
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlsqlhelper/getcurrentdatefunction.php
	* @author Bitrix
	*/
	static public function getCurrentDateFunction()
	{
		return "convert(datetime, cast(year(getdate()) as varchar(4)) + '-' + cast(month(getdate()) as varchar(2)) + '-' + cast(day(getdate()) as varchar(2)), 120)";
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlsqlhelper/addsecondstodatetime.php
	* @author Bitrix
	*/
	static public function addSecondsToDateTime($seconds, $from = null)
	{
		if ($from === null)
		{
			$from = static::getCurrentDateTimeFunction();
		}

		return 'DATEADD(second, '.$seconds.', '.$from.')';
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
	* <p>Нестатический метод возвращает sql-функцию передающую значение <code>$value</code> в тип <code>Дата/Время</code> базы данных.</p> <p>Параметр <code>$value</code> не приводится к SQL безопасному виду.</p>
	*
	*
	* @param string $value  Поле и оператор базы данных для передачи.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlsqlhelper/getdatetimetodatefunction.php
	* @author Bitrix
	*/
	static public function getDatetimeToDateFunction($value)
	{
		return 'DATEADD(dd, DATEDIFF(dd, 0, '.$value.'), 0)';
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlsqlhelper/formatdate.php
	* @author Bitrix
	*/
	static public function formatDate($format, $field = null)
	{
		if ($field === null)
		{
			return '';
		}

		$result = array();

		foreach (preg_split("#(YYYY|MMMM|MM|MI|M|DD|HH|H|GG|G|SS|TT|T)#", $format, -1, PREG_SPLIT_DELIM_CAPTURE) as $part)
		{
			switch ($part)
			{
				case "YYYY":
					$result[] = "\n\tCONVERT(varchar(4),DATEPART(yyyy, $field))";
					break;
				case "MMMM":
					$result[] = "\n\tdatename(mm, $field)";
					break;
				case "MM":
					$result[] = "\n\tREPLICATE('0',2-LEN(DATEPART(mm, $field)))+CONVERT(varchar(2),DATEPART(mm, $field))";
					break;
				case "MI":
					$result[] = "\n\tREPLICATE('0',2-LEN(DATEPART(mi, $field)))+CONVERT(varchar(2),DATEPART(mi, $field))";
					break;
				case "M":
					$result[] = "\n\tCONVERT(varchar(3), $field,7)";
					break;
				case "DD":
					$result[] = "\n\tREPLICATE('0',2-LEN(DATEPART(dd, $field)))+CONVERT(varchar(2),DATEPART(dd, $field))";
					break;
				case "HH":
					$result[] = "\n\tREPLICATE('0',2-LEN(DATEPART(hh, $field)))+CONVERT(varchar(2),DATEPART(hh, $field))";
					break;
				case "H":
					$result[] = "\n\tCASE WHEN DATEPART(HH, $field) < 13 THEN RIGHT(REPLICATE('0',2) + CAST(datepart(HH, $field) AS VARCHAR(2)),2) ELSE RIGHT(REPLICATE('0',2) + CAST(datepart(HH, dateadd(HH, -12, $field)) AS VARCHAR(2)), 2) END";
					break;
				case "GG":
					$result[] = "\n\tREPLICATE('0',2-LEN(DATEPART(hh, $field)))+CONVERT(varchar(2),DATEPART(hh, $field))";
					break;
				case "G":
					$result[] = "\n\tCASE WHEN DATEPART(HH, $field) < 13 THEN RIGHT(REPLICATE('0',2) + CAST(datepart(HH, $field) AS VARCHAR(2)),2) ELSE RIGHT(REPLICATE('0',2) + CAST(datepart(HH, dateadd(HH, -12, $field)) AS VARCHAR(2)), 2) END";
					break;
				case "SS":
					$result[] = "\n\tREPLICATE('0',2-LEN(DATEPART(ss, $field)))+CONVERT(varchar(2),DATEPART(ss, $field))";
					break;
				case "TT":
					$result[] = "\n\tCASE WHEN DATEPART(HH, $field) < 12 THEN 'AM' ELSE 'PM' END";
					break;
				case "T":
					$result[] = "\n\tCASE WHEN DATEPART(HH, $field) < 12 THEN 'AM' ELSE 'PM' END";
					break;
				default:
					$result[] = "'".$part."'";
					break;
			}
		}

		return implode("+", $result);
	}

	/**
	 * Returns function for getting part of string.
	 * <p>
	 * If length is null or omitted, the substring starting
	 * from start until the end of the string will be returned.
	 * <p>
	 * $str and $from parameters are SQL unsafe.
	 *
	 * @param string $str Database field or expression.
	 * @param integer $from Start position.
	 * @param integer $length Maximum length.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает sql-функцию для получения части строки.</p> <p>Если длина равна нулю или пустая, то будет возвращена строка от начала и до конца.</p> <p>Параметры <code>$str</code> и <code>$from</code> не приводятся к SQL безопасному виду.</p>
	*
	*
	* @param string $str  Поле или оператор базы данных.
	*
	* @param integer $from  Стартовая позиция.
	*
	* @param integer $length = null Максимальная длина.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlsqlhelper/getsubstrfunction.php
	* @author Bitrix
	*/
	static public function getSubstrFunction($str, $from, $length = null)
	{
		$sql = 'SUBSTRING('.$str.', '.$from;

		if (!is_null($length))
			$sql .= ', '.$length;
		else
			$sql .= ', LEN('.$str.') + 1 - '.$from;

		return $sql.')';
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlsqlhelper/getconcatfunction.php
	* @author Bitrix
	*/
	static public function getConcatFunction()
	{
		return implode(" + ", func_get_args());
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlsqlhelper/getisnullfunction.php
	* @author Bitrix
	*/
	static public function getIsNullFunction($expression, $result)
	{
		return "ISNULL(".$expression.", ".$result.")";
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlsqlhelper/getlengthfunction.php
	* @author Bitrix
	*/
	static public function getLengthFunction($field)
	{
		return "LEN(".$field.")";
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlsqlhelper/getchartodatefunction.php
	* @author Bitrix
	*/
	static public function getCharToDateFunction($value)
	{
		return "CONVERT(datetime, '".$value."', 120)";
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlsqlhelper/getdatetocharfunction.php
	* @author Bitrix
	*/
	static public function getDateToCharFunction($fieldName)
	{
		return "CONVERT(varchar(19), ".$fieldName.", 120)";
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
	* @param string $fieldName  Возвращаемая строка
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlsqlhelper/casttochar.php
	* @author Bitrix
	*/
	static public function castToChar($fieldName)
	{
		return 'CAST('.$fieldName.' AS varchar)';
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
		return 'CONVERT(VARCHAR(8000), '.$fieldName.')';
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlsqlhelper/getconverter.php
	* @author Bitrix
	*/
	static public function getConverter(Entity\ScalarField $field)
	{
		if ($field instanceof Entity\DatetimeField)
		{
			return array($this, "convertDatetimeField");
		}
		elseif ($field instanceof Entity\DateField)
		{
			return array($this, "convertDateField");
		}
		elseif ($field instanceof Entity\StringField)
		{
			return array($this, "convertStringField");
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
	 * @see \Bitrix\Main\Db\MssqlSqlHelper::getConverter
	 */
	
	/**
	* <p>Нестатический метод конвертирует строку в объект <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/datetime/index.php">\Bitrix\Main\Type\DateTime</a>.</p> <p>Вспомогательная функция</p>
	*
	*
	* @param string $value  Переданное значение
	*
	* @return null|\Bitrix\Main\Type\DateTime 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlsqlhelper/getconverter.php">\Bitrix\Main\Db\MssqlSqlHelper::getConverter</a></li>
	* </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlsqlhelper/convertdatetimefield.php
	* @author Bitrix
	*/
	static public function convertDatetimeField($value)
	{
		if ($value !== null)
		{
			$value = new Type\DateTime(substr($value, 0, 19), "Y-m-d H:i:s");
		}

		return $value;
	}

	/**
	 * Converts string into \Bitrix\Main\Type\Date object.
	 * <p>
	 * Helper function.
	 *
	 * @param string $value Value fetched.
	 *
	 * @return null|\Bitrix\Main\Type\DateTime
	 * @see \Bitrix\Main\Db\MssqlSqlHelper::getConverter
	 */
	
	/**
	* <p>Нестатический метод конвертирует строку в объект <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/date/index.php">\Bitrix\Main\Type\Date</a>.</p> <p>Вспомогательная функция</p>
	*
	*
	* @param string $value  Передаваемое значение.
	*
	* @return null|\Bitrix\Main\Type\DateTime 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlsqlhelper/getconverter.php">\Bitrix\Main\Db\MssqlSqlHelper::getConverter</a></li>
	* </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlsqlhelper/convertdatefield.php
	* @author Bitrix
	*/
	static public function convertDateField($value)
	{
		if($value !== null)
		{
			$value = new Type\Date($value, "Y-m-d");
		}

		return $value;
	}

	/**
	 * Converts string into \Bitrix\Main\Type\Date object if string has datetime specific format..
	 * <p>
	 * Helper function.
	 *
	 * @param string $value Value fetched.
	 *
	 * @return null|\Bitrix\Main\Type\DateTime
	 * @see \Bitrix\Main\Db\MssqlSqlHelper::getConverter
	 */
	
	/**
	* <p>Нестатический метод конвертирует строку в объект <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/date/index.php">\Bitrix\Main\Type\Date</a>, если строка имеет специфичный формат.</p> <p>Вспомогательная функция.</p>
	*
	*
	* @param string $value  Переданное значение
	*
	* @return null|\Bitrix\Main\Type\DateTime 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlsqlhelper/getconverter.php">\Bitrix\Main\Db\MssqlSqlHelper::getConverter</a></li>
	* </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlsqlhelper/convertstringfield.php
	* @author Bitrix
	*/
	static public function convertStringField($value)
	{
		if ($value !== null)
		{
			if(preg_match("#^\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}\$#", $value))
			{
				$value = new Type\DateTime($value, "Y-m-d H:i:s");
			}
		}

		return $value;
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlsqlhelper/getcolumntypebyfield.php
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
			return 'float';
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlsqlhelper/getfieldbycolumntype.php
	* @author Bitrix
	*/
	static public function getFieldByColumnType($name, $type, array $parameters = null)
	{
		switch($type)
		{
			case 4:
			case 5:
			case -6:
				//int SQL_INTEGER (4)
				//smallint SQL_SMALLINT (5)
				//tinyint SQL_TINYINT (-6)
				return new Entity\IntegerField($name);

			case 2:
			case 3:
			case 6:
			case 7:
				//numeric SQL_NUMERIC (2)
				//decimal SQL_DECIMAL (3)
				//smallmoney SQL_DECIMAL (3)
				//money SQL_DECIMAL (3)
				//float SQL_FLOAT (6)
				//real SQL_REAL (7)
				return new Entity\FloatField($name, array("scale" => $parameters["scale"]));

			case 93:
				//datetime - SQL_TYPE_TIMESTAMP (93)
				//datetime2 - SQL_TYPE_TIMESTAMP (93)
				//smalldatetime - SQL_TYPE_TIMESTAMP (93)
				return new Entity\DatetimeField($name);

			case 91:
				//date - SQL_TYPE_DATE (91)
				return new Entity\DateField($name);
		}
		//bigint SQL_BIGINT (-5)
		//binary SQL_BINARY (-2)
		//bit SQL_BIT (-7)
		//char SQL_CHAR (1)
		//datetimeoffset SQL_SS_TIMESTAMPOFFSET (-155)
		//image SQL_LONGVARBINARY (-4)
		//nchar SQL_WCHAR (-8)
		//ntext SQL_WLONGVARCHAR (-10)
		//nvarchar SQL_WVARCHAR (-9)
		//text SQL_LONGVARCHAR (-1)
		//time SQL_SS_TIME2 (-154)
		//timestamp SQL_BINARY (-2)
		//udt SQL_SS_UDT (-151)
		//uniqueidentifier SQL_GUID (-11)
		//varbinary SQL_VARBINARY (-3)
		//varchar SQL_VARCHAR (12)
		//xml SQL_SS_XML (-152)
		return new Entity\StringField($name, array("size" => $parameters["size"]));
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
	* @param string $sql  Sql text.
	*
	* @param integer $limit  Максимальное число строк для возврата.
	*
	* @param integer $offset  Смещение первой строки для возврата, начинается с 0.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlsqlhelper/gettopsql.php
	* @author Bitrix
	*/
	static public function getTopSql($sql, $limit, $offset = 0)
	{
		$offset = intval($offset);
		$limit = intval($limit);

		if ($offset > 0 && $limit <= 0)
			throw new Main\ArgumentException("Limit must be set if offset is set");

		if ($limit > 0)
		{
			if ($offset <= 0)
			{
				$sql = preg_replace("/^\\s*SELECT/i", "SELECT TOP ".$limit, $sql);
			}
			else
			{
				$orderBy = '';
				$sqlTmp = $sql;

				preg_match_all("#\\sorder\\s+by\\s#i", $sql, $matches, PREG_OFFSET_CAPTURE);
				if (isset($matches[0]) && is_array($matches[0]) && count($matches[0]) > 0)
				{
					$idx = $matches[0][count($matches[0]) - 1][1];
					$s = substr($sql, $idx);
					if (substr_count($s, '(') === substr_count($s, ')'))
					{
						$orderBy = $s;
						$sqlTmp = substr($sql, 0, $idx);
					}
				}

				if ($orderBy === '')
				{
					$orderBy = "ORDER BY (SELECT 1)";
					$sqlTmp = $sql;
				}

				// ROW_NUMBER() Returns the sequential number of a row within a partition of a result set, starting at 1 for the first row in each partition.
				$sqlTmp = preg_replace(
					"/^\\s*SELECT/i",
					"SELECT ROW_NUMBER() OVER (".$orderBy.") AS ROW_NUMBER_ALIAS,",
					$sqlTmp
				);

				$sql =
					"WITH ROW_NUMBER_QUERY_ALIAS AS (".$sqlTmp.") ".
					"SELECT * ".
					"FROM ROW_NUMBER_QUERY_ALIAS ".
					"WHERE ROW_NUMBER_ALIAS BETWEEN ".($offset + 1)." AND ".($offset + $limit);
			}
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlsqlhelper/preparemerge.php
	* @author Bitrix
	*/
	public function prepareMerge($tableName, array $primaryFields, array $insertFields, array $updateFields)
	{
		$insert = $this->prepareInsert($tableName, $insertFields);

		$updateColumns = array();
		$sourceSelectValues = array();
		$sourceSelectColumns = array();
		$targetConnectColumns = array();
		$tableFields = $this->connection->getTableFields($tableName);
		foreach($tableFields as $columnName => $tableField)
		{
			$quotedName = $this->quote($columnName);
			if (in_array($columnName, $primaryFields))
			{
				$sourceSelectValues[] = $this->convertToDb($insertFields[$columnName], $tableField);
				$sourceSelectColumns[] = $quotedName;
				$targetConnectColumns[] = "source.".$quotedName." = target.".$quotedName;
			}

			if (isset($updateFields[$columnName]) || array_key_exists($columnName, $updateFields))
			{
				$updateColumns[] = "target.".$quotedName.' = '.$this->convertToDb($updateFields[$columnName], $tableField);
			}
		}

		if (
			$insert && $insert[0] != "" && $insert[1] != ""
			&& $updateColumns
			&& $sourceSelectValues && $sourceSelectColumns && $targetConnectColumns
		)
		{
			$sql = "
				MERGE INTO ".$this->quote($tableName)." AS target USING (
					SELECT ".implode(", ", $sourceSelectValues)."
				) AS source (
					".implode(", ", $sourceSelectColumns)."
				)
				ON
				(
					".implode(" AND ", $targetConnectColumns)."
				)
				WHEN MATCHED THEN
					UPDATE SET ".implode(", ", $updateColumns)."
				WHEN NOT MATCHED THEN
					INSERT (".$insert[0].")
					VALUES (".$insert[1].")
				;
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
