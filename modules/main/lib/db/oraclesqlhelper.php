<?php
namespace Bitrix\Main\DB;

use Bitrix\Main;
use Bitrix\Main\Type;
use Bitrix\Main\Entity;

class OracleSqlHelper extends SqlHelper
{
	/**
	 * Returns an identificator escaping left character.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод. Экранирование идентификатора - левый символ</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/getleftquote.php
	* @author Bitrix
	*/
	static public function getLeftQuote()
	{
		return '"';
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/getrightquote.php
	* @author Bitrix
	*/
	static public function getRightQuote()
	{
		return '"';
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/getaliaslength.php
	* @author Bitrix
	*/
	static public function getAliasLength()
	{
		return 30;
	}

	/**
	 * Returns quoted identifier.
	 *
	 * @param string $identifier Table or Column name.
	 *
	 * @return string
	 * @see \Bitrix\Main\DB\SqlHelper::quote
	 */
	
	/**
	* <p>Нестатический метод возвращает запрошенный идентификатор.</p>
	*
	*
	* @param string $identifier  Имя таблицы или колонки.
	*
	* @return string 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/quote.php">\Bitrix\Main\DB\SqlHelper::quote</a></li>
	* </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/quote.php
	* @author Bitrix
	*/
	static public function quote($identifier)
	{
		return parent::quote(strtoupper($identifier));
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/getquerydelimiter.php
	* @author Bitrix
	*/
	static public function getQueryDelimiter()
	{
		return "(?<!\\*)/(?!\\*)";
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/forsql.php
	* @author Bitrix
	*/
	public static function forSql($value, $maxLength = 0)
	{
		if ($maxLength <= 0 || $maxLength > 2000)
			$maxLength = 2000;

		$value = substr($value, 0, $maxLength);

		if (\Bitrix\Main\Application::isUtfMode())
		{
			// From http://w3.org/International/questions/qa-forms-utf-8.html
			// This one can crash php with segmentation fault on large input data (over 20K)
			// https://bugs.php.net/bug.php?id=60423
			if (preg_match_all('%(
				[\x00-\x7E]                        # ASCII
				|[\xC2-\xDF][\x80-\xBF]            # non-overlong 2-byte
				|\xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
				|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
				|\xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
				|\xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
				|[\xF1-\xF3][\x80-\xBF]{3}         # planes 4-15
				|\xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
			)+%x', $value, $match))
				$value = implode(' ', $match[0]);
			else
				return ''; //There is no valid utf at all
		}

		return str_replace("'", "''", $value);
	}

	/**
	 * Returns function for getting current time.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает sql-функцию для получения текущего времени.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/getcurrentdatetimefunction.php
	* @author Bitrix
	*/
	static public function getCurrentDateTimeFunction()
	{
		return "SYSDATE";
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/getcurrentdatefunction.php
	* @author Bitrix
	*/
	static public function getCurrentDateFunction()
	{
		return "TRUNC(SYSDATE)";
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/addsecondstodatetime.php
	* @author Bitrix
	*/
	static public function addSecondsToDateTime($seconds, $from = null)
	{
		if ($from === null)
		{
			$from = static::getCurrentDateTimeFunction();
		}

		return '('.$from.'+'.$seconds.'/86400)';
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/getdatetimetodatefunction.php
	* @author Bitrix
	*/
	static public function getDatetimeToDateFunction($value)
	{
		return 'TRUNC('.$value.')';
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/formatdate.php
	* @author Bitrix
	*/
	static public function formatDate($format, $field = null)
	{
		$format = str_replace("HH", "HH24", $format);
		$format = str_replace("GG", "HH24", $format);

		if (strpos($format, 'HH24') === false)
		{
			$format = str_replace("H", "HH", $format);
		}

		$format = str_replace("G", "HH", $format);

		$format = str_replace("MI", "II", $format);

		if (strpos($format, 'MMMM') !== false)
		{
			$format = str_replace("MMMM", "MONTH", $format);
		}
		elseif (strpos($format, 'MM') === false)
		{
			$format = str_replace("M", "MON", $format);
		}

		$format = str_replace("II", "MI", $format);

		$format = str_replace("TT", "AM", $format);
		$format = str_replace("T", "AM", $format);

		if ($field === null)
		{
			return $format;
		}
		else
		{
			return "TO_CHAR(".$field.", '".$format."')";
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/getconcatfunction.php
	* @author Bitrix
	*/
	static public function getConcatFunction()
	{
		$str = "";
		$ar = func_get_args();
		if (is_array($ar))
			$str .= implode(" || ", $ar);
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
	* <p>Нестатический метод возвращает sql-функцию для проверки полей и операторов базы данных на значение <i>NULL</i>. Если равно <i>NULL</i>, то будет возвращён <code>$result</code>.</p> <p>Все параметры не приводятся к SQL безопасному виду.</p>
	*
	*
	* @param string $expression  Поле или оператор базы данных для проверки на &gt;NULL.
	*
	* @param string $result  Поле или оператор базы данных, которые будут возвращены если
	* $expression будет равно <i>NULL</i>.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/getisnullfunction.php
	* @author Bitrix
	*/
	static public function getIsNullFunction($expression, $result)
	{
		return "NVL(".$expression.", ".$result.")";
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
	* <p>Нестатический метод возвращает sql-функцию для получения длины поля или оператора базы данных.</p> <p>Параметр $field не приводится к SQL безопасному виду. </p>
	*
	*
	* @param string $field  Поле или оператор БД.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/getlengthfunction.php
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
	* <p>Нестатический метод возвращает sql-функцию для конвертации строкового значения в тип <code>Дата/время</code>.</p> <p>Значение параметра <code>$value</code> не приводится к SQL безопасному виду.</p>
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/getchartodatefunction.php
	* @author Bitrix
	*/
	static public function getCharToDateFunction($value)
	{
		return "TO_DATE('".$value."', 'YYYY-MM-DD HH24:MI:SS')";
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/getdatetocharfunction.php
	* @author Bitrix
	*/
	static public function getDateToCharFunction($fieldName)
	{
		return "TO_CHAR(".$fieldName.", 'YYYY-MM-DD HH24:MI:SS')";
	}

	/**
	 * Performs additional processing of CLOB fields.
	 *
	 * @param Entity\ScalarField[] $tableFields Table fields.
	 * @param array $fields Data fields.
	 *
	 * @return array
	 */
	protected function prepareBinds(array $tableFields, array $fields)
	{
		$binds = array();

		foreach ($tableFields as $columnName => $tableField)
		{
			if (isset($fields[$columnName]) && !($fields[$columnName] instanceof SqlExpression))
			{
				if ($tableField instanceof Entity\TextField && $fields[$columnName] <> '')
				{
					$binds[$columnName] = $fields[$columnName];
				}
			}
		}

		return $binds;
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/getconverter.php
	* @author Bitrix
	*/
	static public function getConverter(Entity\ScalarField $field)
	{
		if ($field instanceof Entity\DatetimeField)
		{
			return array($this, "convertDatetimeField");
		}
		elseif ($field instanceof Entity\TextField)
		{
			return array($this, "convertTextField");
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
	 * @see \Bitrix\Main\Db\OracleSqlHelper::getConverter
	 */
	
	/**
	* <p>Нестатический метод конвертирует строку в объект <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/datetime/index.php">\Bitrix\Main\Type\DateTime</a>. Вспомогательная функция</p>
	*
	*
	* @param string $value  Передаваемое значение.
	*
	* @return null|\Bitrix\Main\Type\DateTime 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/getconverter.php">\Bitrix\Main\Db\OracleSqlHelper::getConverter</a></li>
	* </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/convertdatetimefield.php
	* @author Bitrix
	*/
	static public function convertDatetimeField($value)
	{
		if ($value !== null)
		{
			if (strlen($value) == 19)
			{
				//preferable format: NLS_DATE_FORMAT='YYYY-MM-DD HH24:MI:SS'
				$value = new Type\DateTime($value, "Y-m-d H:i:s");
			}
			else
			{
				//default Oracle date format: 03-MAR-14
				$value = new Type\DateTime($value." 00:00:00", "d-M-y H:i:s");
			}
		}

		return $value;
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/casttochar.php
	* @author Bitrix
	*/
	static public function castToChar($fieldName)
	{
		return 'TO_CHAR('.$fieldName.')';
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
		return 'dbms_lob.substr('.$fieldName.', 4000, 1)';
	}

	/**
	 * Converts lob object into string.
	 * <p>
	 * Helper function.
	 *
	 * @param string $value Value fetched.
	 *
	 * @return null|string
	 * @see \Bitrix\Main\Db\OracleSqlHelper::getConverter
	 */
	
	/**
	* <p>Нестатический метод конвертирует объект типа lob в строку. Вспомогательная функция.</p>
	*
	*
	* @param string $value  Передаваемое значение.
	*
	* @return mixed 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/getconverter.php">\Bitrix\Main\Db\OracleSqlHelper::getConverter</a></li>
	* </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/converttextfield.php
	* @author Bitrix
	*/
	static public function convertTextField($value)
	{
		if ($value !== null)
		{
			if (is_object($value))
			{
				/** @var \OCI_Lob $value */
				$value = $value->load();
			}
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
	 * @see \Bitrix\Main\Db\OracleSqlHelper::getConverter
	 */
	
	/**
	* <p>Нестатический метод конвертирует строку в объект <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/date/index.php">\Bitrix\Main\Type\Date</a> если строка имеет специфический формат даты. Вспомогательная функция.</p>
	*
	*
	* @param string $value  Передаваемое значение.
	*
	* @return null|\Bitrix\Main\Type\DateTime 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/getconverter.php">\Bitrix\Main\Db\OracleSqlHelper::getConverter</a></li>
	* </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/convertstringfield.php
	* @author Bitrix
	*/
	static public function convertStringField($value)
	{
		if ($value !== null)
		{
			if ((strlen($value) == 19) && preg_match("#^\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}$#", $value))
			{
				$value = new Type\DateTime($value, "Y-m-d H:i:s");
			}
		}

		return $value;
	}

	/**
	 * Converts values to the string according to the column type to use it in a SQL query.
	 *
	 * @param mixed $value Value to be converted.
	 * @param Entity\ScalarField $field Type "source".
	 *
	 * @return string Value to write to column.
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	
	/**
	* <p>Нестатический метод конвертирует значение в строку, соответствующую типу колонки используемой в SQL запросе.</p>
	*
	*
	* @param mixed $value  Значение для конвертации
	*
	* @param mixed $Bitrix  Тип "источника".
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/converttodb.php
	* @author Bitrix
	*/
	public function convertToDb($value, Entity\ScalarField $field)
	{
		if ($value === null)
		{
			return "NULL";
		}

		if ($value instanceof SqlExpression)
		{
			return $value->compile();
		}

		if ($field instanceof Entity\DatetimeField)
		{
			if (empty($value))
			{
				$result = "NULL";
			}
			elseif ($value instanceof Type\Date)
			{
				if ($value instanceof Type\DateTime)
				{
					$value = clone($value);
					$value->setDefaultTimeZone();
				}
				$result = $this->getCharToDateFunction($value->format("Y-m-d H:i:s"));
			}
			else
			{
				throw new Main\ArgumentTypeException('value', '\Bitrix\Main\Type\Date');
			}
		}
		elseif ($field instanceof Entity\TextField)
		{
			if (empty($value))
			{
				$result = "NULL";
			}
			else
			{
				$result = "EMPTY_CLOB()";
			}
		}
		elseif ($field instanceof Entity\IntegerField)
		{
			$result = "'".intval($value)."'";
		}
		elseif ($field instanceof Entity\FloatField)
		{
			if (($scale = $field->getScale()) !== null)
			{
				$result = "'".round(doubleval($value), $scale)."'";
			}
			else
			{
				$result = "'".doubleval($value)."'";
			}
		}
		elseif ($field instanceof Entity\StringField)
		{
			$result = "'".$this->forSql($value, $field->getSize())."'";
		}
		else
		{
			$result = "'".$this->forSql($value)."'";
		}

		return $result;
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/getcolumntypebyfield.php
	* @author Bitrix
	*/
	static public function getColumnTypeByField(Entity\ScalarField $field)
	{
		if ($field instanceof Entity\IntegerField)
		{
			return 'number(18)';
		}
		elseif ($field instanceof Entity\FloatField)
		{
			$scale = $field->getScale();
			return 'number'.($scale !== null? "(*,".$scale.")": "");
		}
		elseif ($field instanceof Entity\DatetimeField)
		{
			return 'date';
		}
		elseif ($field instanceof Entity\DateField)
		{
			return 'date';
		}
		elseif ($field instanceof Entity\TextField)
		{
			return 'clob';
		}
		elseif ($field instanceof Entity\BooleanField)
		{
			$values = $field->getValues();

			if (preg_match('/^[0-9]+$/', $values[0]) && preg_match('/^[0-9]+$/', $values[1]))
			{
				return 'number(1)';
			}
			else
			{
				return 'varchar2('.max(strlen($values[0]), strlen($values[1])).' char)';
			}
		}
		elseif ($field instanceof Entity\EnumField)
		{
			return 'varchar2('.max(array_map('strlen', $field->getValues())).' char)';
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
			return 'varchar2('.($defaultLength > 0? $defaultLength: 255).' char)';
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/getfieldbycolumntype.php
	* @author Bitrix
	*/
	static public function getFieldByColumnType($name, $type, array $parameters = null)
	{
		switch ($type)
		{
		case "DATE":
			return new Entity\DatetimeField($name);

		case "NCLOB":
		case "CLOB":
		case "BLOB":
			return new Entity\TextField($name);

		case "FLOAT":
		case "BINARY_FLOAT":
		case "BINARY_DOUBLE":
			return new Entity\FloatField($name);

		case "NUMBER":
			if ($parameters["precision"] == 0 && $parameters["scale"] == -127)
			{
				//NUMBER
				return new Entity\FloatField($name);
			}
			if (intval($parameters["scale"]) <= 0)
			{
				//NUMBER(18)
				//NUMBER(18,-2)
				return new Entity\IntegerField($name);
			}
			//NUMBER(*,2)
			return new Entity\FloatField($name, array("scale" => $parameters["scale"]));
		}
		//LONG
		//VARCHAR2(size [BYTE | CHAR])
		//NVARCHAR2(size)
		//TIMESTAMP [(fractional_seconds_precision)]
		//TIMESTAMP [(fractional_seconds)] WITH TIME ZONE
		//TIMESTAMP [(fractional_seconds)] WITH LOCAL TIME ZONE
		//INTERVAL YEAR [(year_precision)] TO MONTH
		//INTERVAL DAY [(day_precision)] TO SECOND [(fractional_seconds)]
		//RAW(size)
		//LONG RAW
		//ROWID
		//UROWID [(size)]
		//CHAR [(size [BYTE | CHAR])]
		//NCHAR[(size)]
		//BFILE
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
	* <p>Нестатический метод трансформирует запрос согласно установленному лимиту (<code>$limit</code>) ограничениям смещения (<code>$offset</code>). Необходимо обязательно указать <code>$limit</code> когда <code>$offset</code> задан.</p>
	*
	*
	* @param string $sql  Текст запроса
	*
	* @param integer $limit  Максимальное число строк для возврата.
	*
	* @param integer $offset  Смещение первых строк, начинается с 0.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/gettopsql.php
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
			//The first row selected has a ROWNUM of 1, the second has 2, and so on
			if ($offset <= 0)
			{
				$sql =
					"SELECT * ".
					"FROM (".$sql.") ".
					"WHERE ROWNUM <= ".$limit;
			}
			else
			{
				$sql =
					"SELECT * ".
					"FROM (".
					"   SELECT rownum_query_alias.*, ROWNUM rownum_alias ".
					"   FROM (".$sql.") rownum_query_alias ".
					"   WHERE ROWNUM <= ".($offset + $limit)." ".
					") ".
					"WHERE rownum_alias >= ".($offset + 1);
			}
		}
		return $sql;
	}

	/**
	 * Returns ascending order specifier for ORDER BY clause.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает возрастающий порядок в соответствии с ORDER BY.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/getascendingorder.php
	* @author Bitrix
	*/
	static public function getAscendingOrder()
	{
		return 'ASC NULLS FIRST';
	}

	/**
	 * Returns descending order specifier for ORDER BY clause.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает убывающий порядок установленный для ORDER BY.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/getdescendingorder.php
	* @author Bitrix
	*/
	static public function getDescendingOrder()
	{
		return 'DESC NULLS LAST';
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
	* <p>Нестатический метод выстраивает строки для команды SQL MERGE для данной таблицы.</p>
	*
	*
	* @param string $tableName  Имя таблицы.
	*
	* @param array $primaryFields  Массив первичных ключей списка колонок. <code>Array("column")[]</code>
	*
	* @param array $insertFields  Моссив добавляемых данных <code>Array("column" =&gt; $value)[]</code>
	*
	* @param array $updateFields  Массив обновляеых данных. <code>Array("column" =&gt; $value)[]</code>
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oraclesqlhelper/preparemerge.php
	* @author Bitrix
	*/
	public function prepareMerge($tableName, array $primaryFields, array $insertFields, array $updateFields)
	{
		$insert = $this->prepareInsert($tableName, $insertFields);

		$updateColumns = array();
		$sourceSelectColumns = array();
		$targetConnectColumns = array();
		$tableFields = $this->connection->getTableFields($tableName);
		foreach($tableFields as $columnName => $tableField)
		{
			$quotedName = $this->quote($columnName);
			if (in_array($columnName, $primaryFields))
			{
				$sourceSelectColumns[] = $this->convertToDb($insertFields[$columnName], $tableField)." AS ".$quotedName;
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
			&& $sourceSelectColumns && $targetConnectColumns
		)
		{
			$sql = "
				MERGE INTO ".$this->quote($tableName)." target USING (
					SELECT ".implode(", ", $sourceSelectColumns)." FROM dual
				)
				source ON
				(
					".implode(" AND ", $targetConnectColumns)."
				)
				WHEN MATCHED THEN
					UPDATE SET ".implode(", ", $updateColumns)."
				WHEN NOT MATCHED THEN
					INSERT (".$insert[0].")
					VALUES (".$insert[1].")
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
