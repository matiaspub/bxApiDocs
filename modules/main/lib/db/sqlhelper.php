<?php
namespace Bitrix\Main\DB;

use Bitrix\Main;
use Bitrix\Main\Type;
use Bitrix\Main\Entity;

abstract class SqlHelper
{
	/** @var Connection $connection */
	protected $connection;

	/**
	 * @param Connection $connection Database connection.
	 */
	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	/**
	 * Returns an identificator escaping left character.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод. Экранирование идентификаторов - левый символ</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/getleftquote.php
	* @author Bitrix
	*/
	static public function getLeftQuote()
	{
		return '';
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/getrightquote.php
	* @author Bitrix
	*/
	static public function getRightQuote()
	{
		return '';
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/getaliaslength.php
	* @author Bitrix
	*/
	abstract public function getAliasLength();

	/**
	 * Returns quoted identifier.
	 * <p>
	 * For example Title become :
	 * - `Title` for MySQL
	 * - "TITLE" for Oracle
	 * - [Title] for Ms SQL
	 * <p>
	 * @param string $identifier Table or Column name.
	 *
	 * @return string
	 * @see \Bitrix\Main\DB\SqlHelper::getLeftQuote
	 * @see \Bitrix\Main\DB\SqlHelper::getRightQuote
	 */
	
	/**
	* <p>Нестатический метод возвращает идентификатор в кавычках.</p> <p>Например, Title станет:<br> `Title` для MySQL<br> "TITLE" для Oracle<br> [Title] для Ms SQL </p>
	*
	*
	* @param string $identifier  Название таблицы или колонки.
	*
	* @return string 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/getleftquote.php">\Bitrix\Main\DB\SqlHelper::getLeftQuote</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/getrightquote.php">\Bitrix\Main\DB\SqlHelper::getRightQuote</a></li>
	* </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/quote.php
	* @author Bitrix
	*/
	public function quote($identifier)
	{
		// security unshielding
		$identifier = str_replace(array($this->getLeftQuote(), $this->getRightQuote()), '', $identifier);

		// shield [[database.]tablename.]columnname
		if (strpos($identifier, '.') !== false)
		{
			$identifier = str_replace('.', $this->getRightQuote() . '.' . $this->getLeftQuote(), $identifier);
		}

		// shield general borders
		return $this->getLeftQuote() . $identifier . $this->getRightQuote();
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/getquerydelimiter.php
	* @author Bitrix
	*/
	abstract public function getQueryDelimiter();

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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/forsql.php
	* @author Bitrix
	*/
	abstract public function forSql($value, $maxLength = 0);

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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/getcurrentdatetimefunction.php
	* @author Bitrix
	*/
	abstract public function getCurrentDateTimeFunction();

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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/getcurrentdatefunction.php
	* @author Bitrix
	*/
	abstract public function getCurrentDateFunction();

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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/addsecondstodatetime.php
	* @author Bitrix
	*/
	abstract public function addSecondsToDateTime($seconds, $from = null);

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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/getdatetimetodatefunction.php
	* @author Bitrix
	*/
	abstract public function getDatetimeToDateFunction($value);

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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/formatdate.php
	* @author Bitrix
	*/
	abstract public function formatDate($format, $field = null);

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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/getsubstrfunction.php
	* @author Bitrix
	*/
	static public function getSubstrFunction($str, $from, $length = null)
	{
		$sql = 'SUBSTR('.$str.', '.$from;

		if (!is_null($length))
			$sql .= ', '.$length;

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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/getconcatfunction.php
	* @author Bitrix
	*/
	abstract public function getConcatFunction();

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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/getisnullfunction.php
	* @author Bitrix
	*/
	abstract public function getIsNullFunction($expression, $result);

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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/getlengthfunction.php
	* @author Bitrix
	*/
	abstract public function getLengthFunction($field);

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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/getchartodatefunction.php
	* @author Bitrix
	*/
	abstract public function getCharToDateFunction($value);

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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/getdatetocharfunction.php
	* @author Bitrix
	*/
	abstract public function getDateToCharFunction($fieldName);

	/**
	 * Returns CAST expression for converting field or expression into string
	 *
	 * @param string $fieldName
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает CAST выражение для конвертируемого поля или строчного выражения.</p>
	*
	*
	* @param string $fieldName  Имя поля
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/casttochar.php
	* @author Bitrix
	*/
	abstract public function castToChar($fieldName);

	/**
	 * Returns expression for text field being used in group or order
	 * @see \Bitrix\Main\Entity\Query::buildGroup
	 * @see \Bitrix\Main\Entity\Query::buildOrder
	 *
	 * @param string $fieldName
	 *
	 * @return string
	 */
	abstract public function softCastTextToChar($fieldName);

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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/gettopsql.php
	* @author Bitrix
	*/
	abstract public function getTopSql($sql, $limit, $offset = 0);

	/**
	 * Builds the strings for the SQL INSERT command for the given table.
	 *
	 * @param string $tableName A table name.
	 * @param array $fields Array("column" => $value)[].
	 *
	 * @return array (columnList, valueList, binds)
	 */
	
	/**
	* <p>Нестатический метод простраивает строку для sql-команды INSERT для указанной таблицы.</p>
	*
	*
	* @param string $tableName  Имя таблийы.
	*
	* @param array $fields  Массив вида: <pre class="syntax">Array("column" =&gt; $value)[]</pre>
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/prepareinsert.php
	* @author Bitrix
	*/
	public function prepareInsert($tableName, array $fields)
	{
		$columns = array();
		$values = array();

		$tableFields = $this->connection->getTableFields($tableName);

		foreach ($fields as $columnName => $value)
		{
			if (isset($tableFields[$columnName]))
			{
				$columns[] = $this->quote($columnName);
				$values[] = $this->convertToDb($value, $tableFields[$columnName]);
			}
			else
			{
				trigger_error("Column `{$columnName}` is not found in the `{$tableName}` table", E_USER_WARNING);
			}
		}

		$binds = $this->prepareBinds($tableFields, $fields);

		return array(
			implode(", ", $columns),
			implode(", ", $values),
			$binds
		);
	}

	/**
	 * Builds the strings for the SQL UPDATE command for the given table.
	 *
	 * @param string $tableName A table name.
	 * @param array $fields Array("column" => $value)[].
	 *
	 * @return array (update, binds)
	 */
	
	/**
	* <p>Нестатический метод выстраивает строку для SQL-команды UPDATE для указанной таблицы.</p>
	*
	*
	* @param string $tableName  AНазвание таблицы
	*
	* @param array $fields  Массив вида: <pre class="syntax">Array("column" =&gt; $value)[]</pre>
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/prepareupdate.php
	* @author Bitrix
	*/
	public function prepareUpdate($tableName, array $fields)
	{
		$update = array();

		$tableFields = $this->connection->getTableFields($tableName);

		foreach ($fields as $columnName => $value)
		{
			if (isset($tableFields[$columnName]))
			{
				$update[] = $this->quote($columnName).' = '.$this->convertToDb($value, $tableFields[$columnName]);
			}
			else
			{
				trigger_error("Column `{$columnName}` is not found in the `{$tableName}` table", E_USER_WARNING);
			}
		}

		$binds = $this->prepareBinds($tableFields, $fields);

		return array(
			implode(", ", $update),
			$binds
		);
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/preparemerge.php
	* @author Bitrix
	*/
	static public function prepareMerge($tableName, array $primaryFields, array $insertFields, array $updateFields)
	{
		return array();
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
		return array();
	}

	/**
	 * Builds the string for the SQL assignment operation of the given column.
	 *
	 * @param string $tableName A table name.
	 * @param string $columnName A column name.
	 * @param string $value A value to assign.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод выстраивает строку для присвоения SQL оператора указанной колонке.</p>
	*
	*
	* @param string $tableName  Название таблицы
	*
	* @param string $columnName  Название колонки.
	*
	* @param string $value  Присвоенное значение
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/prepareassignment.php
	* @author Bitrix
	*/
	public function prepareAssignment($tableName, $columnName, $value)
	{
		$tableField = $this->connection->getTableField($tableName, $columnName);

		return $this->quote($columnName).' = '.$this->convertToDb($value, $tableField);
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
	* <p>Нетатический метод конвертирует значение в строку, соответствующую типу колонки используемой в SQL запросе.</p>
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/converttodb.php
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

		if($field instanceof Entity\DatetimeField)
		{
			if (empty($value))
			{
				$result = "NULL";
			}
			elseif($value instanceof Type\Date)
			{
				if($value instanceof Type\DateTime)
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
		elseif($field instanceof Entity\DateField)
		{
			if (empty($value))
			{
				$result = "NULL";
			}
			elseif($value instanceof Type\Date)
			{
				$result = $this->getCharToDateFunction($value->format("Y-m-d"));
			}
			else
			{
				throw new Main\ArgumentTypeException('value', '\Bitrix\Main\Type\Date');
			}
		}
		elseif($field instanceof Entity\IntegerField)
		{
			$result = "'".intval($value)."'";
		}
		elseif($field instanceof Entity\FloatField)
		{
			if(($scale = $field->getScale()) !== null)
			{
				$result = "'".round(doubleval($value), $scale)."'";
			}
			else
			{
				$result = "'".doubleval($value)."'";
			}
		}
		elseif($field instanceof Entity\StringField)
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
	 * Returns $value converted to an type according to $field type.
	 * <p>
	 * For example if $field is Entity\DatetimeField then returned value will be instance of Type\DateTime.
	 *
	 * @param mixed $value Value to be converted.
	 * @param Entity\ScalarField $field Type "source".
	 *
	 * @return mixed
	 */
	
	/**
	* <p>Нестатический метод возвращает значение параметра <code>$value</code> сконвертированное в тип, соответствующий типу параметра <code>$field</code>.</p> <p>Например, если поле <code>$field</code> имеет тип <code>Entity\DatetimeField</code>, то возвращено будет значение в типе, установленном в <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/datetime/index.php">\Main\Type\DateTime</a>.</p>
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
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/convertfromdb.php
	* @author Bitrix
	*/
	public function convertFromDb($value, Entity\ScalarField $field)
	{
		if($value !== null)
		{
			$converter = $this->getConverter($field);
			if (is_callable($converter))
			{
				return call_user_func_array($converter, array($value));
			}
		}

		return $value;
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/getconverter.php
	* @author Bitrix
	*/
	static public function getConverter(Entity\ScalarField $field)
	{
		return false;
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/getcolumntypebyfield.php
	* @author Bitrix
	*/
	abstract public function getColumnTypeByField(Entity\ScalarField $field);

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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/getfieldbycolumntype.php
	* @author Bitrix
	*/
	abstract public function getFieldByColumnType($name, $type, array $parameters = null);

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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/getascendingorder.php
	* @author Bitrix
	*/
	static public function getAscendingOrder()
	{
		return 'ASC';
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlhelper/getdescendingorder.php
	* @author Bitrix
	*/
	static public function getDescendingOrder()
	{
		return 'DESC';
	}
}
