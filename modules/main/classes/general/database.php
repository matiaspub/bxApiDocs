<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */

use Bitrix\Main;
use Bitrix\Main\Data\ConnectionPool;


/**
 * <b>CDatabase</b> - класс для работы с базой данной.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/index.php
 * @author Bitrix
 */
abstract class CAllDatabase
{
	var $DBName;
	var $DBHost;
	var $DBLogin;
	var $DBPassword;
	var $bConnected;

	var $db_Conn;
	var $debug;
	var $DebugToFile;
	var $ShowSqlStat;
	var $db_Error;
	var $db_ErrorSQL;
	var $result;
	var $type;

	static $arNodes = array();
	var $column_cache = array();
	var $bModuleConnection;
	var $bNodeConnection;
	var $node_id;
	/** @var CDatabase */
	var $obSlave = null;

	/**
	 * @var integer
	 * @deprecated Use \Bitrix\Main\Application::getConnection()->getTracker()->getCounter();
	 **/
	var $cntQuery = 0;
	/**
	 * @var float
	 * @deprecated Use \Bitrix\Main\Application::getConnection()->getTracker()->getTime();
	 **/
	var $timeQuery = 0.0;
	/**
	 * @var \Bitrix\Main\Diag\SqlTrackerQuery[]
	 * @deprecated Use \Bitrix\Main\Application::getConnection()->getTracker()->getQueries();
	 **/
	var $arQueryDebug = array();
	/**
	 * @var \Bitrix\Main\Diag\SqlTracker
	 */
	public $sqlTracker = null;

	public static function StartUsingMasterOnly()
	{
		Main\Application::getInstance()->getConnectionPool()->useMasterOnly(true);
	}

	public static function StopUsingMasterOnly()
	{
		Main\Application::getInstance()->getConnectionPool()->useMasterOnly(false);
	}

	/**
	 * @param string $node_id
	 * @param boolean $bIgnoreErrors
	 * @param boolean $bCheckStatus
	 *
	 * @return boolean|CDatabase
	 */
	public static function GetDBNodeConnection($node_id, $bIgnoreErrors = false, $bCheckStatus = true)
	{
		global $DB;

		if(!array_key_exists($node_id, self::$arNodes))
		{
			if(CModule::IncludeModule('cluster'))
				self::$arNodes[$node_id] = CClusterDBNode::GetByID($node_id);
			else
				self::$arNodes[$node_id] = false;
		}
		$node = &self::$arNodes[$node_id];

		if(
			is_array($node)
			&& (
				!$bCheckStatus
				|| (
					$node["ACTIVE"] == "Y"
					&& ($node["STATUS"] == "ONLINE" || $node["STATUS"] == "READY")
				)
			)
			&& !isset($node["ONHIT_ERROR"])
		)
		{
			if(!array_key_exists("DB", $node))
			{
				$node_DB = new CDatabase;
				$node_DB->type = $DB->type;
				$node_DB->debug = $DB->debug;
				$node_DB->DebugToFile = $DB->DebugToFile;
				$node_DB->bNodeConnection = true;
				$node_DB->node_id = $node_id;

				if($node_DB->Connect($node["DB_HOST"], $node["DB_NAME"], $node["DB_LOGIN"], $node["DB_PASSWORD"], "node".$node_id))
				{
					if(defined("DELAY_DB_CONNECT") && DELAY_DB_CONNECT===true)
					{
						if($node_DB->DoConnect("node".$node_id))
							$node["DB"] = $node_DB;
					}
					else
					{
						$node["DB"] = $node_DB;
					}
				}
			}

			if(array_key_exists("DB", $node))
				return $node["DB"];
		}

		if($bIgnoreErrors)
		{
			return false;
		}
		else
		{
			if(file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/dbconn_error.php"))
				include($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/dbconn_error.php");
			else
				include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/dbconn_error.php");
			die();
		}
	}

	/**
	 * Returns module database connection.
	 * Can be used only if module supports sharding.
	 *
	 * @param string $module_id
	 * @param bool $bModuleInclude
	 * @return bool|CDatabase
	 */
	public static function GetModuleConnection($module_id, $bModuleInclude = false)
	{
		$node_id = COption::GetOptionString($module_id, "dbnode_id", "N");
		if(is_numeric($node_id))
		{
			if($bModuleInclude)
			{
				$status = COption::GetOptionString($module_id, "dbnode_status", "ok");
				if($status === "move")
					return false;
			}

			$moduleDB = CDatabase::GetDBNodeConnection($node_id, $bModuleInclude);

			if(is_object($moduleDB))
			{
				$moduleDB->bModuleConnection = true;
				return $moduleDB;
			}

			//There was an connection error
			if($bModuleInclude && CModule::IncludeModule('cluster'))
				CClusterDBNode::SetOffline($node_id);

			//TODO: unclear what to return when node went offline
			//in the middle of the hit.
			return false;
		}
		else
		{
			return $GLOBALS["DB"];
		}
	}

	
	/**
	* <p>Открывает соединение с базой данных. Метод возвращает "true" при успешном открытии соединения или "false" при ошибке. Нестатический метод.</p> <p> </p>
	*
	*
	* @param string $host  Сервер (хост) базы данных.
	*
	* @param string $stringdb  Имя базы данных.
	*
	* @param string $login  Логин.
	*
	* @param string $password  Пароль.
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* if(!(<b>$DB-&gt;Connect</b>($DBHost, $DBName, $DBLogin, $DBPassword)))
	* {
	* 	if(file_exists($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/php_interface/dbconn_error.php"))
	* 	{
	* 		include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/php_interface/dbconn_error.php");
	* 	}
	* 	else
	* 	{
	* 		include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/dbconn_error.php");
	* 	}
	* 	die();
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/disconnect.php">CDatabase::Disconnect</a>
	* </li></ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/connect.php
	* @author Bitrix
	*/
	abstract function Connect($DBHost, $DBName, $DBLogin, $DBPassword);

	abstract function ConnectInternal();

	public function DoConnect($connectionName = "")
	{
		if($this->bConnected)
			return true;

		$app = Main\Application::getInstance();
		if ($app != null)
		{
			$con = $app->getConnection($connectionName);
			if (
				$con
				&& $con->isConnected()
				&& ($con instanceof Bitrix\Main\DB\Connection)
				&& ($this->DBHost == $con->getHost())
				&& ($this->DBLogin == $con->getLogin())
				&& ($this->DBName == $con->getDatabase())
			)
			{
				$this->db_Conn = $con->getResource();
				$this->bConnected = true;
				$this->sqlTracker = null;
				$this->cntQuery = 0;
				$this->timeQuery = 0;
				$this->arQueryDebug = array();

				return true;
			}
		}

		if(!$this->ConnectInternal())
		{
			return false;
		}

		$this->bConnected = true;
		$this->sqlTracker = null;
		$this->cntQuery = 0;
		$this->timeQuery = 0;
		$this->arQueryDebug = array();

		/** @noinspection PhpUnusedLocalVariableInspection */
		global $DB, $USER, $APPLICATION;
		if(file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/after_connect.php"))
			include($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/after_connect.php");

		if ($app != null)
		{
			$con = $app->getConnection($connectionName);
			if(!$con && $this->bNodeConnection)
			{
				//create a node connection in the new kernel
				$pool = $app->getConnectionPool();
				$parameters = array(
					'host' => $this->DBHost,
					'database' => $this->DBName,
					'login' => $this->DBLogin,
					'password' => $this->DBPassword,
				);
				$con = $pool->cloneConnection(ConnectionPool::DEFAULT_CONNECTION_NAME, $connectionName, $parameters);
				$con->setNodeId($this->node_id);
			}
			if (
				$con
				&& !$con->isConnected()
				&& ($con instanceof Bitrix\Main\DB\Connection)
				&& ($this->DBHost == $con->getHost())
				&& ($this->DBLogin == $con->getLogin())
				&& ($this->DBName == $con->getDatabase())
			)
			{
				$con->setConnectionResourceNoDemand($this->db_Conn);
			}
		}

		return true;
	}

	public function startSqlTracker()
	{
		if (!$this->sqlTracker)
		{
			$app = Main\Application::getInstance();
			$this->sqlTracker = $app->getConnection()->startTracker();
		}
		return $this->sqlTracker;
	}

	public static function GetNowFunction()
	{
		return CDatabase::CurrentTimeFunction();
	}

	public static function GetNowDate()
	{
		return CDatabase::CurrentDateFunction();
	}

	
	/**
	* <p>Возвращает для MySQL строку DATE_FORMAT, для Oracle - TO_CHAR с нужными параметрами.<br> Форматы даты устанавливается в <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=35&amp;LESSON_ID=2071#local_settings" >Региональных настройках</a>.<br> Нестатический метод.</p> <p> </p>
	*
	*
	* @param string $value  Значение даты для формата текущего сайта.
	*
	* @param string $type = "FULL" Тип формата даты: "FULL" - для даты со временем, "SHORT" - для даты (без
	* времени) 		<br>Необязательный. По умолчанию "FULL".
	*
	* @param string $lang = false Код языка для административной части.<br>Необязательный. По
	* умолчанию текущий. Отсутствовал в версях с 3.0.11 до 3.3.21.
	*
	* @param string $SearchInSitesOnly = false Необязательный.
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $strSql = "
	*     SELECT 
	*         ID,    
	*         ".<b>$DB-&gt;DateToCharFunction</b>("DATE_CREATE")."    DATE_CREATE
	*     FROM 
	*         my_table
	*     ";
	* $rs = $DB-&gt;Query($strSql, false, $err_mess.__LINE__);
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/chartodatefunction.php">CDatabase::CharToDateFunction</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/date/index.php">Функции для работы
	* с датой и временем</a> </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/datetocharfunction.php
	* @author Bitrix
	*/
	abstract function DateToCharFunction($strFieldName, $strType="FULL");

	
	/**
	* <p>Возвращает для MySQL значение сконвертированное в формат YYYY-MM-DD [HH:MI:SS], для Oracle - метод вернет строку TO_DATE с нужными параметрами.<br>Форматы даты устанавливается в <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=35&amp;LESSON_ID=2071" >Региональных настройках</a> сайта. Нестатический метод.</p>
	*
	*
	* @param string $value  Если функция вызывается в публичной части сайта, то это - значение
	* даты для формата текущего сайта. Если функция вызывается в
	* административной части, то это - значение даты для формата
	* текущего языка.
	*
	* @param string $type = "FULL" Тип формата даты: "FULL" - для даты со временем, "SHORT" - для даты (без
	* времени) 		<br>Необязательный. По умолчанию "FULL".
	*
	* @param string $lang = false Код языка для административной части.<br>Необязательный. По
	* умолчанию текущий. Отсутствовал в версиях с 3.0.11 по 3.3.21.
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $arr = getdate();
	* $ndate = mktime(9,0,0,$arr["mon"],$arr["mday"],$arr["year"]);
	* $next_exec = <b>$DB-&gt;CharToDateFunction</b>(GetTime($ndate,"FULL"));
	* CAgent::AddAgent("SendDailyStatistics();","statistic","Y",86400,"","Y",$next_exec, 25);
	* ?&gt;
	* &lt;?
	* $strSql = "
	*     SELECT 
	*         ID
	*     FROM 
	*         my_table
	*     WHERE 
	*         DATE_CREATE &lt;= ".<b>$DB-&gt;CharToDateFunction</b>("10.01.2003 23:59:59")."
	*     ";
	* $rs = $DB-&gt;Query($strSql, false, $err_mess.__LINE__);
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/datetocharfunction.php">CDatabase::DateToCharFunction</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/date/index.php">Функции для работы
	* с датой и временем</a> </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/chartodatefunction.php
	* @author Bitrix
	*/
	abstract function CharToDateFunction($strValue, $strType="FULL");

	abstract function Concat();

	public static function Substr($str, $from, $length = null)
	{
		// works for mysql and oracle, redefined for mssql
		$sql = 'SUBSTR('.$str.', '.$from;

		if (!is_null($length))
		{
			$sql .= ', '.$length;
		}

		return $sql.')';
	}

	abstract function IsNull($expression, $result);

	abstract function Length($field);

	public static function ToChar($expr, $len=0)
	{
		return "CAST(".$expr." AS CHAR".($len > 0? "(".$len.")":"").")";
	}

	
	/**
	* <p>Метод конвертирует любой формат времени допустимый в настройках сайта в формат принятый в PHP. Нестатический метод.</p> <p>Правила конвертации:</p> <p> </p> <table class="tnormal" width="100%"> <tr> <th width="20%">Исходные символы</th> 		<th width="20%">После конвертации</th> 		<th width="60%">Описание</th> 	</tr> <tr> <td>YYYY</td> 		<td>Y</td> 		<td>Год (0001 - 9999)</td> 	</tr> <tr> <td>MM</td> 		<td>m</td> 		<td>Месяц (01 - 12)</td> 	</tr> <tr> <td>DD</td> 		<td>d</td> 		<td>День (01 - 31)</td> 	</tr> <tr> <td>HH</td> 		<td>H</td> 		<td>Часы (00 - 24)</td> 	</tr> <tr> <td>MI</td> 		<td>i</td> 		<td>Минуты (00 - 59)</td> 	</tr> <tr> <td>SS</td> 		<td>s</td> 		<td>Секунды (00 - 59)</td> 	</tr> </table>
	*
	*
	* @param string $format  Y
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* // исходный формат
	* $format = "DD.MM.YYYY HH:MI:SS";
	* 
	* // переведем в PHP формат
	* $php_format = <b>$DB-&gt;DateFormatToPHP</b>($format); // d.m.Y H:i:s
	* ?&gt;
	* &lt;?
	* // вывод текущей даты в формате текущего сайта
	* 
	* // получим формат сайта
	* $site_format = CSite::GetDateFormat("SHORT");
	* 
	* // переведем формат сайта в формат PHP
	* $php_format = <b>$DB-&gt;DateFormatToPHP</b>($site_format);
	* 
	* // выведем текущую дату в формате текущего сайта
	* echo date($php_format, time());
	* ?&gt;
	* &lt;?
	* // вывод вчерашне даты в формате текущего сайта
	* 
	* // получим формат сайта
	* $site_format = CSite::GetDateFormat("SHORT");
	* 
	* // переведем формат сайта в формат PHP
	* $php_format = <b>$DB-&gt;DateFormatToPHP</b>($site_format);
	* 
	* // выведем вчерашнюю дату в формате текущего сайта
	* echo date($php_format, time()-86400);
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/formatdate.php">CDatabase::FormatDate</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/csite/getdateformat.php">CSite::GetDateFormat</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/date/index.php">Функции для работы
	* с датой и временем</a> </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/dateformattophp.php
	* @author Bitrix
	*/
	public static function DateFormatToPHP($format)
	{
		static $cache = array();
		if (!isset($cache[$format]))
		{
			$cache[$format] = Main\Type\Date::convertFormatToPhp($format);
		}
		return $cache[$format];
	}

	
	/**
	* <p>Преобразует дату из одного заданного формата в другой заданный формат. В формате допустимы следующие обозначения:</p> <p> </p> <table width="100%" class="tnormal"> <tr> <th width="40%">Обозначение</th> 		<th width="60%">Описание</th> 	</tr> <tr> <td>YYYY</td> 		<td>Год (0001 - 9999)</td> 	</tr> <tr> <td>MM</td> 		<td>Месяц (01 - 12)</td> 	</tr> <tr> <td>DD</td> 		<td>День (01 - 31)</td> 	</tr> <tr> <td>HH</td> 		<td>Часы (00 - 24)</td> 	</tr> <tr> <td>MI</td> 		<td>Минуты (00 - 59)</td> 	</tr> <tr> <td>SS</td> 		<td>Секунды (00 - 59)</td> 	</tr> </table> <p>Нестатический метод.</p>
	*
	*
	* @param string $date  Год (0001 - 9999)
	*
	* @param string $format = "DD.MM.YYYY Месяц (01 - 12)
	*
	* @param mixed $mixedHH  День (01 - 31)
	*
	* @param H $HMI  Часы (00 - 24)
	*
	* @param M $MSS  Минуты (00 - 59)
	*
	* @param string $new_format = "DD.MM.YYYY Секунды (00 - 59)
	*
	* @param mixed $mixedHH  
	*
	* @param H $HMI  Дата для конвертации.
	*
	* @param M $MSS  Текущий формат даты. 		<br>Необязательный. По умолчанию - "DD.MM.YYYY
	* HH:MI:SS".
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* // зададим дату
	* $date = "31.12.2007";
	* 
	* // укажем формат этой даты
	* $format = "DD.MM.YYYY";
	* 
	* // получим формат текущего сайта
	* $new_format = CSite::GetDateFormat("SHORT"); // YYYY-MM-DD
	* 
	* // переведем дату из одного формата в другой
	* $new_date = $DB-&gt;<b>FormatDate</b>($date, $format, $new_format);
	* 
	* // в результате получим дату в новом формате
	* echo $new_date; // 2007-12-31
	* ?&gt;
	* &lt;?
	* // конвертация даты из формата одного сайта в формат другого
	* 
	* // получим формат сайта ru
	* $format_ru = CSite::GetDateFormat("SHORT", "ru"); // DD.MM.YYYY
	* 
	* // получим формат сайта en
	* $format_en = CSite::GetDateFormat("SHORT", "en"); // YYYY-MM-DD
	* 
	* // переведем дату из формата сайта ru в формат сайта en
	* $new_date = $DB-&gt;<b>FormatDate</b>($date, $format_ru, $format_en);
	* 
	* // в результате получим дату в новом формате
	* echo $date; // 2007-12-31
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/dateformattophp.php">CDatabase::DateFormatToPHP</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/csite/getdateformat.php">CSite::GetDateFormat</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/date/convertdatetime.php">ConvertDateTime</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/date/index.php">Функции для работы с
	* датой и временем</a> </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/formatdate.php
	* @author Bitrix
	*/
	public static function FormatDate($strDate, $format="DD.MM.YYYY HH:MI:SS", $new_format="DD.MM.YYYY HH:MI:SS")
	{
		if (empty($strDate))
			return false;

		if ($format===false && defined("FORMAT_DATETIME"))
			$format = FORMAT_DATETIME;

		$fromPhpFormat = Main\Type\Date::convertFormatToPhp($format);

		$time = false;
		try
		{
			$time = new Main\Type\DateTime($strDate, $fromPhpFormat);
		}
		catch(Main\ObjectException $e)
		{
		}

		if ($time !== false)
		{
			//Compatibility issue
			$fixed_format = preg_replace(
				array(
					"/(?<!Y)Y(?!Y)/i",
					"/(?<!M)M(?!M|I)/i",
					"/(?<!D)D(?!D)/i",
					"/(?<!H)H:I:S/i",
				),
				array(
					"YYYY",
					"MM",
					"DD",
					"HH:MI:SS",
				),
				strtoupper($new_format)
			);
			$toPhpFormat = Main\Type\Date::convertFormatToPhp($fixed_format);

			return $time->format($toPhpFormat);
		}

		return false;
	}

	/**
	 * @param string $strSql
	 * @param bool $bIgnoreErrors
	 * @param string $error_position
	 * @param array $arOptions
	 * @return CDBResult
	 */
	
	/**
	* <p>Метод выполняет запрос к базе данных и если не произошло ошибки возвращает результат. В случае успешного выполнения метод возвращает объект класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>. <br> Если произошла ошибка и параметр <i>ignore_errors</i> равен "true", то метод вернет "false".<br> Если произошла ошибка и параметр <i>ignore_errors</i> равен "false", то метод прерывает выполнение страницы, выполняя перед этим  следующие действия: </p> <ol> <li>Вызов функции <a href="http://dev.1c-bitrix.ru/api_help/main/functions/debug/addmessage2log.php">AddMessage2Log</a>. 	</li> <li>Если текущий пользователь является администратором сайта, либо в файле <b>/bitrix/php_interface/dbconn.php</b> была инициализирована переменная <b>$DBDebug=true;</b>, то на экран будет выведен полный текст ошибки, в противном случае будет вызвана функция <a href="http://dev.1c-bitrix.ru/api_help/main/functions/debug/senderror.php">SendError</a>. 	</li> <li>Будет подключен файл <b>/bitrix/php_interface/dbquery_error.php</b>, если он не существует, то будет подключен файл <b>/bitrix/modules/main/include/dbquery_error.php</b> </li> </ol> <br><p class="note"><b>Примечания для Oracle версии</b>: <br>1. При возникновении ошибки, если была открыта транзакция, то выполняется <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/rollback.php">CDataBase::Rollback</a>.<br>2. Для вставки текстовых полей типа BLOB, CLOB, LONG и т.п. (длинною больше 4000 символов), воспользуйтесь методом <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/querybind.php">CDatabase::QueryBind</a>.<br>3. Если при выполнении SQL-запроса типа "SELECT" требуется связывание переменных, то воспользуйтесь методом <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/querybindselect.php">CDatabase::QueryBindSelect</a>.</p> <p> </p> <p>Нестатический метод.</p> <p>Аналог метода в новом ядре D7 - <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/connection/query.php" >Bitrix\Main\DB\Connection::query </a>.</p>
	*
	*
	* @param string $sql  SQL запрос.
	*
	* @param bool $ignore_errors = false Игнорировать ошибки. Если true, то в случае ошибки функция
	* возвращает "false".	 	Если параметр <i>ignore_errors</i> равен "false", то в случае
	* ошибки функция прекращает выполнение всей
	* страницы.<br>Необязательный. По умолчанию - "false".
	*
	* @param string $error_position = "" Строка идентифицирующая позицию в коде, откуда была вызвана
	* данная функция CDatabase::Query. Если в SQL запросе будет ошибка и если в
	* файле <b>/bitrix/php_interface/dbconn.php</b> установлена переменная <b>$DBDebug=true;</b>,
	* то на экране будет выведена данная информация и сам SQL запрос.
	* Необязательный.
	*
	* @param array $Options = array() Необязательный.
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* function GetByID($ID, $GET_BY_SID="N")
	* {
	* 	$err_mess = (CForm::err_mess())."&lt;br&gt;Function: GetByID&lt;br&gt;Line: ";
	* 	global $DB;
	* 	$where = ($GET_BY_SID=="N") ? " F.ID = '".intval($ID)."' " : " F.VARNAME='".$DB-&gt;ForSql($ID,50)."' ";
	* 	$strSql = "
	* 		SELECT
	* 			F.*,
	* 			F.FIRST_SITE_ID,
	* 			F.FIRST_SITE_ID									LID,
	* 			F.VARNAME,
	* 			F.VARNAME										SID,
	* 			".$DB-&gt;DateToCharFunction("F.TIMESTAMP_X")."	TIMESTAMP_X,
	* 			count(distinct D1.ID)							C_FIELDS,
	* 			count(distinct D2.ID)							QUESTIONS,
	* 			count(distinct S.ID)							STATUSES
	* 		FROM b_form F
	* 		LEFT JOIN b_form_status S ON (S.FORM_ID = F.ID)
	* 		LEFT JOIN b_form_field D1 ON (D1.FORM_ID = F.ID and D1.ADDITIONAL='Y')
	* 		LEFT JOIN b_form_field D2 ON (D2.FORM_ID = F.ID and D2.ADDITIONAL&lt;&gt;'Y')
	* 		WHERE 
	* 			$where
	* 		GROUP BY 
	* 			F.ID
	* 		";
	* 	$res = <b>$DB-&gt;Query</b>($strSql, false, $err_mess.__LINE__);
	* 	return $res;
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/querybind.php">CDatabase::QueryBind</a>
	* </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/querybindselect.php">CDatabase::QueryBindSelect</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/forsql.php">CDatabase::ForSql</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/debug/addmessage2log.php">AddMessage2Log</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/query.php
	* @author Bitrix
	*/
	abstract function Query($strSql, $bIgnoreErrors=false, $error_position="", $arOptions=array());

	//query with CLOB
	
	/**
	* <p>Выполняет SQL-запросы типа "UPDATE", "INSERT", в которых есть необходимость связывания переменных (как правило для полей типа BLOB, CLOB, LONG и т.п.). Нестатический метод.</p> <p>В случае успешного выполнения метод возвращает объект класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.<br> Если произошла ошибка и параметр <i>ignore_errors</i> равен "true", то метод вернет "false".<br> Если произошла ошибка и параметр <i>ignore_errors</i> равен "false", то метод прерывает выполнение страницы, выполняя перед этим  следующие действия: </p> <ol> <li>Вызов функции <a href="http://dev.1c-bitrix.ru/api_help/main/functions/debug/addmessage2log.php">AddMessage2Log</a>. 	</li> <li>Если текущий пользователь является администратором сайта, либо в файле <b>/bitrix/php_interface/dbconn.php</b> была инициализирована переменная <b>$DBDebug=true;</b>, то на экран будет выведен полный текст ошибки, в противном случае будет вызвана функция <a href="http://dev.1c-bitrix.ru/api_help/main/functions/debug/senderror.php">SendError</a>. 	</li> <li>Будет подключен файл <b>/bitrix/php_interface/dbquery_error.php</b>, если он не существует, то будет подключен файл <b>/bitrix/modules/main/include/dbquery_error.php</b> </li> </ol>
	*
	*
	* @param string $sql  SQL запрос.
	*
	* @param array $binds  Массив полей типа BLOB, CLOB, LONG и т.п. в формате array("имя поля" =&gt;
	* "значение" [, ...]).
	*
	* @param bool $ignore_errors = false Игнорировать ошибки. Если true, то в случае ошибки метод возвращает
	* "false".	 	Если параметр <i>ignore_errors</i> равен "false", то в случае ошибки
	* метод прекращает выполнение всей страницы.<br>Необязательный. По
	* умолчанию - "false".
	*
	* @param string $error_position = "" Строка идентифицирующая позицию в коде, откуда был вызван данный
	* метод CDatabase::QueryBind. Если в SQL запросе будет ошибка и если в файле
	* <b>/bitrix/php_interface/dbconn.php</b> установлена переменная <b>$DBDebug=true;</b>, то на
	* экране будет выведена данная информация и сам SQL запрос.
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
	* $APPLICATION-&gt;SetTitle("TEST CLOB");
	* require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
	* 
	* // обновление поля
	* 
	* $MY_FIELD_VALUE = "123";
	* 
	* // обновим поле MY_FIELD типа CLOB
	* $strError = "";
	* $arFields["NEWS_TEXT_FCK"] = $MY_FIELD_VALUE;
	* $strUpdate = $DB-&gt;PrepareUpdate("aa_abc", $arFields);
	* if($strUpdate != "")
	* {
	* $strSql = "UPDATE aa_abc SET ".$strUpdate." WHERE ID=15";
	* // в переменной $MY_FIELD_VALUE содержится текст длиной более 4000 символов
	* $arBinds = array("NEWS_TEXT_FCK" =&gt; $MY_FIELD_VALUE);
	* 
	* // выполним запрос со связыванием :MY_FIELD с реальным значением
	* if(!$DB-&gt;QueryBind($strSql, $arBinds))
	* $strError = "Query Error!";
	* }
	* if($strError=="")
	* echo "all Ok!";
	* 
	* require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/query.php">CDatabase::Query</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/querybindselect.php">CDatabase::QueryBindSelect</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/forsql.php">CDatabase::ForSql</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/debug/addmessage2log.php">AddMessage2Log</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/querybind.php
	* @author Bitrix
	*/
	public function QueryBind($strSql, $arBinds, $bIgnoreErrors=false)
	{
		return $this->Query($strSql, $bIgnoreErrors);
	}

	public function QueryLong($strSql, $bIgnoreErrors = false)
	{
		return $this->Query($strSql, $bIgnoreErrors);
	}

	
	/**
	* <p>Подготавливает строку (заменяет кавычки и прочее) для вставки в SQL запрос. Если задан параметр <i>max_length</i>, то также обрезает строку до длины <i>max_length</i>. Нестатический метод.</p> <p> </p>
	*
	*
	* @param string $value  Исходная строка.
	*
	* @param int $max_length = 0 Максимальная длина. 		<br>Необязательный. По умолчанию - "0" (строка
	* не обрезается).
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $strSql = "
	*     SELECT 
	*         ID 
	*     FROM 
	*         b_stat_phrase_list 
	*     WHERE 
	*         PHRASE='".<b>$DB-&gt;ForSql</b>($search_phrase)."' 
	*     and SESSION_ID='".$_SESSION["SESS_SESSION_ID"]."'
	*     ";
	* $w = $DB-&gt;Query($strSql, false, $err_mess.__LINE__);
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/query.php">CDatabase::Query</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/update.php">CDatabase::Update</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/insert.php">CDatabase::Insert</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/forsql.php
	* @author Bitrix
	*/
	abstract function ForSql($strValue, $iMaxLength=0);

	
	/**
	* <p>Метод подготавливает массив из двух строк для SQL запроса вставки записи в базу данных. Возвращает массив из двух элементов, где элемент с ключом 0 строка список полей вида "имя поля1, имя поля2[, ...]", а элемент с ключом 1 строка значений вида "значение1, значение2[, ...]". При этом метод сам преобразует все значение в SQL вид в зависимости от типа поля. Нестатический метод.</p> <p></p>
	*
	*
	* @param string $TableName  Имя таблицы для вставки записи.
	*
	* @param array $fields  Массив значений полей в формате "имя поля1"=&gt;"значение1", "имя
	* поля2"=&gt;"значение2" [, ...].          <br>       Если необходимо вставить
	* значение NULL, то значение должно быть равно false.
	*
	* @param string $FileDir = "" Не используется.
	*
	* @param string $lang = false Код сайта для публичной части, либо код языка для
	* административной части. Используется для определения формата
	* даты, для вставки полей типа date или datetime.          <br>      
	* Необязательный. По умолчанию false.
	*
	* @return array 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* function AddResultAnswer($arFields)
	* {
	* 	$err_mess = (CForm::err_mess())."&lt;br&gt;Function: AddResultAnswer&lt;br&gt;Line: ";
	* 	global $DB;
	* 	$arInsert = <b>$DB-&gt;PrepareInsert</b>("b_form_result_answer", $arFields, "form");
	* 	$strSql = "INSERT INTO b_form_result_answer (".$arInsert[0].") VALUES (".$arInsert[1].")";
	* 	$DB-&gt;Query($strSql, false, $err_mess.__LINE__);
	* 	return intval($DB-&gt;LastID());
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/prepareupdate.php">CDatabase::PrepareUpdate</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/prepareinsert.php
	* @author Bitrix
	*/
	abstract function PrepareInsert($strTableName, $arFields);

	
	/**
	* <p>Метод подготавливает строку для SQL запроса изменения записи в базе данных. Возвращает строку вида "имя поля1 = значение1", имя поля2 = значение2[, ...]". При этом метод сам преобразует все значение в SQL вид в зависимости от типа поля. Нестатический метод.</p>
	*
	*
	* @param string $TableName  Имя таблицы.
	*
	* @param array $fields  Массив значений полей в формате "имя поля1"=&gt;"значение1", "имя
	* поля2"=&gt;"значение2" [, ...].          <br>       Если необходимо изменить
	* значение на NULL, то значение в массиве должно быть равно false.
	*
	* @param string $FileDir = "" Не используется.
	*
	* @param string $lang = false Код сайта для публичной части, либо код языка для
	* административной части. Используется для определения формата
	* даты, для вставки полей типа date или datetime.          <br>      
	* Необязательный. По умолчанию false.
	*
	* @param string $TableAlias = "" Необязательный.
	*
	* @return array 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* function UpdateResultField($arFields, $RESULT_ID, $FIELD_ID)
	* {
	* 	$err_mess = (CForm::err_mess())."&lt;br&gt;Function: UpdateResultField&lt;br&gt;Line: ";
	* 	global $DB;
	* 	$RESULT_ID = intval($RESULT_ID);
	* 	$FIELD_ID = intval($FIELD_ID);
	* 	$strUpdate = <b>$DB-&gt;PrepareUpdate</b>("b_form_result_answer", $arFields, "form");
	* 	$strSql = "UPDATE b_form_result_answer SET ".$strUpdate." WHERE RESULT_ID=".$RESULT_ID." and FIELD_ID=".$FIELD_ID;
	* 	$DB-&gt;Query($strSql, false, $err_mess.__LINE__);
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/prepareinsert.php">CDatabase::PrepareInsert</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/prepareupdate.php
	* @author Bitrix
	*/
	abstract function PrepareUpdate($strTableName, $arFields);

	
	/**
	* <p>Метод разбирает строку из пакета запросов на массив запросов и возвращает этот массив. Нестатический метод.</p> <p> </p>
	*
	*
	* @param string $sql  Строка с пакетом запросов, разделенных символом ";" для MySQL версии
	* и символом "/" для Oracle версии.
	*
	* @param bool $Incremental = False Необязательный.
	*
	* @return array 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* function RunSqlBatch($filepath)
	* {
	*     $arErr = Array();
	* 	// откроем файл с запросами
	*     $f = @fopen($filepath, "rb");
	*     if($f)
	*     {
	*         $contents = fread($f, filesize($filepath));
	*         fclose($f);
	*         
	* 		// разобьем на отдельные запросы
	*         $arSql = <b>$this-&gt;ParseSqlBatch</b>($contents);
	*         for($i=0; $i&lt;count($arSql); $i++)
	*         {
	*             $strSql = str_replace("\r\n", "\n", $arSql[$i]);
	*             if(!$this-&gt;Query($strSql, true))
	*                 $arErr[] = $this-&gt;db_Error;
	*         }
	*     }
	*     if(count($arErr)&gt;0)
	*         return $arErr;
	* 
	*     return false;
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/runsqlbatch.php">CDatabase::RunSqlBatch</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/query.php">CDatabase::Query</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/parsesqlbatch.php
	* @author Bitrix
	*/
	public function ParseSqlBatch($strSql, $bIncremental = False)
	{
		if(strtolower($this->type)=="mysql")
			$delimiter = ";";
		elseif(strtolower($this->type)=="mssql")
			$delimiter = "\nGO";
		else
			$delimiter = "(?<!\\*)/(?!\\*)";

		$strSql = trim($strSql);

		$ret = array();
		$str = "";

		do
		{
			if(preg_match("%^(.*?)(['\"`#]|--|".$delimiter.")%is", $strSql, $match))
			{
				//Found string start
				if($match[2] == "\"" || $match[2] == "'" || $match[2] == "`")
				{
					$strSql = substr($strSql, strlen($match[0]));
					$str .= $match[0];
					//find a qoute not preceeded by \
					if(preg_match("%^(.*?)(?<!\\\\)".$match[2]."%s", $strSql, $string_match))
					{
						$strSql = substr($strSql, strlen($string_match[0]));
						$str .= $string_match[0];
					}
					else
					{
						//String falled beyong end of file
						$str .= $strSql;
						$strSql = "";
					}
				}
				//Comment found
				elseif($match[2] == "#" || $match[2] == "--")
				{
					//Take that was before comment as part of sql
					$strSql = substr($strSql, strlen($match[1]));
					$str .= $match[1];
					//And cut the rest
					$p = strpos($strSql, "\n");
					if($p === false)
					{
						$p1 = strpos($strSql, "\r");
						if($p1 === false)
							$strSql = "";
						elseif($p < $p1)
							$strSql = substr($strSql, $p);
						else
							$strSql = substr($strSql, $p1);
					}
					else
						$strSql = substr($strSql, $p);
				}
				//Delimiter!
				else
				{
					//Take that was before delimiter as part of sql
					$strSql = substr($strSql, strlen($match[0]));
					$str .= $match[1];
					//Delimiter must be followed by whitespace
					if(preg_match("%^[\n\r\t ]%", $strSql))
					{
						$str = trim($str);
						if(strlen($str))
						{
							if ($bIncremental)
							{
								$strSql1 = str_replace("\r\n", "\n", $str);
								if (!$this->QueryLong($strSql1, true))
									$ret[] = $this->GetErrorMessage();
							}
							else
							{
								$ret[] = $str;
								$str = "";
							}
						}
					}
					//It was not delimiter!
					elseif(strlen($strSql))
					{
						$str .= $match[2];
					}
				}
			}
			else //End of file is our delimiter
			{
				$str .= $strSql;
				$strSql = "";
			}
		} while (strlen($strSql));

		$str = trim($str);
		if(strlen($str))
		{
			if ($bIncremental)
			{
				$strSql1 = str_replace("\r\n", "\n", $str);
				if (!$this->QueryLong($strSql1, true))
					$ret[] = $this->GetErrorMessage();
			}
			else
			{
				$ret[] = $str;
			}
		}
		return $ret;
	}

	public function RunSQLBatch($filepath, $bIncremental = False)
	{
		if(!file_exists($filepath) || !is_file($filepath))
			return array("File $filepath is not found.");

		$arErr = array();
		$contents = file_get_contents($filepath);

		$arSql = $this->ParseSqlBatch($contents, $bIncremental);
		foreach($arSql as $strSql)
		{
			if ($bIncremental)
			{
				$arErr[] = $strSql;
			}
			else
			{
				$strSql = str_replace("\r\n", "\n", $strSql);
				if(!$this->Query($strSql, true))
					$arErr[] = "<hr><pre>Query:\n".$strSql."\n\nError:\n<font color=red>".$this->GetErrorMessage()."</font></pre>";
			}
		}

		if(!empty($arErr))
			return $arErr;

		return false;
	}

	
	/**
	* <p>Проверяет дату на корректность и возвращает "true" если дата корректна, в противном случае - "false". Нестатический метод.</p> <p> </p>
	*
	*
	* @param string $date  Строка с проверяемой датой.
	*
	* @param string $format = false Формат даты.<br> 	Необязательный. По умолчанию - "false" - определять
	* формат по текущему сайту, либо языку (если административная
	* часть).
	*
	* @param string $lang = false Код сайта для публичной части, либо код языка для
	* административной части (для определения формата, если <i>format</i>
	* равен false).<br>Необязательный. По умолчанию - текущий сайт, либо
	* текущий язык (если административная часть).
	*
	* @param string $format_type = "SHORT" Тип формата даты: "FULL" - для даты со временем, "SHORT" - для даты (без
	* времени) 		<br>Необязательный. По умолчанию "SHORT". С версии 3.3.7 до
	* версии 4.1.0 назывался Type.
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* if (!<b>$DB-&gt;IsDate</b>("12.10.2005 22:34:15", "DD.MM.YYYY HH:MI:SS"))
	*   echo "Ошибка. Неверный формат даты.";
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/date/index.php">Функции для работы с
	* датой и временем</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/functions/filter/checkfilterdates.php">CheckFilterDates</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/isdate.php
	* @author Bitrix
	*/
	public static function IsDate($value, $format=false, $lang=false, $format_type="SHORT")
	{
		if ($format===false) $format = CLang::GetDateFormat($format_type, $lang);
		return CheckDateTime($value, $format);
	}

	public function GetErrorMessage()
	{
		if(is_object($this->obSlave) && strlen($this->obSlave->db_Error))
			return $this->obSlave->db_Error;
		elseif(strlen($this->db_Error))
			return $this->db_Error."!";
		else
			return '';
	}

	public function GetErrorSQL()
	{
		if(is_object($this->obSlave) && strlen($this->obSlave->db_ErrorSQL))
			return $this->obSlave->db_ErrorSQL;
		elseif(strlen($this->db_ErrorSQL))
			return $this->db_ErrorSQL;
		else
			return '';
	}

	public function DDL($strSql, $bIgnoreErrors=false, $error_position="", $arOptions=array())
	{
		$res = $this->Query($strSql, $bIgnoreErrors, $error_position, $arOptions);

		//Reset metadata cache
		$this->column_cache = array();

		return $res;
	}

	public function addDebugQuery($strSql, $exec_time, $node_id = 0)
	{
		$this->cntQuery++;
		$this->timeQuery += $exec_time;
		$this->arQueryDebug[] = $this->startSqlTracker()->getNewTrackerQuery()
			->setSql($strSql)
			->setTime($exec_time)
			->setTrace(defined("BX_NO_SQL_BACKTRACE")? null: Main\Diag\Helper::getBackTrace(8, null, 2))
			->setState($GLOBALS["BX_STATE"])
			->setNode($node_id)
		;
	}

	public function addDebugTime($index, $exec_time)
	{
		if ($this->arQueryDebug[$index])
		{
			$this->arQueryDebug[$index]->addTime($exec_time);
		}
	}

	abstract public function GetIndexName($tableName, $arColumns, $bStrict = false);

	public function IndexExists($tableName, $arColumns, $bStrict = false)
	{
		return $this->GetIndexName($tableName, $arColumns, $bStrict) !== "";
	}
}


/**
 * <b>CDBResult</b> - класс результата выполнения запроса.<br><br>Содержит в  себе методы для постраничной навигации и работы с результатом запроса.  Автоматически создаётся как результат работы метода <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/query.php">CDatabase::Query</a>.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php
 * @author Bitrix
 */
abstract class CAllDBResult
{
	var $result;
	var $arResult;
	var $arReplacedAliases; // replace tech. aliases in Fetch to human aliases
	var $arResultAdd;
	var $bNavStart = false;
	var $bShowAll = false;
	var $NavNum, $NavPageCount, $NavPageNomer, $NavPageSize, $NavShowAll, $NavRecordCount;
	var $bFirstPrintNav = true;
	var $PAGEN, $SIZEN;
	var $SESS_SIZEN, $SESS_ALL, $SESS_PAGEN;
	var $add_anchor = "";
	var $bPostNavigation = false;
	var $bFromArray = false;
	var $bFromLimited = false;
	var $sSessInitAdd = "";
	var $nPageWindow = 5;
	var $nSelectedCount = false;
	var $arGetNextCache = false;
	var $bDescPageNumbering = false;
	/** @var array */
	var $arUserFields = false;
	var $usedUserFields = false;
	/** @var array */
	var $SqlTraceIndex = false;
	/** @var CDatabase */
	var $DB;
	var $NavRecordCountChangeDisable = false;
	var $is_filtered = false;
	var $nStartPage = 0;
	var $nEndPage = 0;
	/** @var Main\DB\Result */
	var $resultObject = null;

	/** @param CDBResult $res */
	public function __construct($res = null)
	{
		$obj = is_object($res);
		if($obj && is_subclass_of($res, "CAllDBResult"))
		{
			$this->result = $res->result;
			$this->nSelectedCount = $res->nSelectedCount;
			$this->arResult = $res->arResult;
			$this->arResultAdd = $res->arResultAdd;
			$this->bNavStart = $res->bNavStart;
			$this->NavPageNomer = $res->NavPageNomer;
			$this->bShowAll = $res->bShowAll;
			$this->NavNum = $res->NavNum;
			$this->NavPageCount = $res->NavPageCount;
			$this->NavPageSize = $res->NavPageSize;
			$this->NavShowAll = $res->NavShowAll;
			$this->NavRecordCount = $res->NavRecordCount;
			$this->bFirstPrintNav = $res->bFirstPrintNav;
			$this->PAGEN = $res->PAGEN;
			$this->SIZEN = $res->SIZEN;
			$this->bFromArray = $res->bFromArray;
			$this->bFromLimited = $res->bFromLimited;
			$this->nPageWindow = $res->nPageWindow;
			$this->bDescPageNumbering = $res->bDescPageNumbering;
			$this->SqlTraceIndex = $res->SqlTraceIndex;
			$this->DB = $res->DB;
			$this->arUserFields = $res->arUserFields;
		}
		elseif($obj && $res instanceof Main\DB\ArrayResult)
		{
			$this->InitFromArray($res->getResource());
		}
		elseif($obj && $res instanceof Main\DB\Result)
		{
			$this->result = $res->getResource();
			$this->resultObject = $res;
		}
		elseif(is_array($res))
		{
			$this->arResult = $res;
		}
		else
		{
			$this->result = $res;
		}
	}

	/** @deprecated */
	static public function CAllDBResult($res = null)
	{
		self::__construct($res);
	}

	static public function __sleep()
	{
		return array(
			'result',
			'arResult',
			'arReplacedAliases',
			'arResultAdd',
			'bNavStart',
			'bShowAll',
			'NavNum',
			'NavPageCount',
			'NavPageNomer',
			'NavPageSize',
			'NavShowAll',
			'NavRecordCount',
			'bFirstPrintNav',
			'PAGEN',
			'SIZEN',
			'add_anchor',
			'bPostNavigation',
			'bFromArray',
			'bFromLimited',
			'sSessInitAdd',
			'nPageWindow',
			'nSelectedCount',
			'arGetNextCache',
			'bDescPageNumbering',
			'arUserMultyFields',
		);
	}

	/**
	 * @return array
	 */
	
	/**
	* <p>Делает выборку значений полей в массив. Возвращает массив вида Array("поле"=&gt;"значение" [, ...]) и передвигает курсор на следующую запись. Если достигнута последняя запись (или в результате нет ни одной записи) - метод вернет "false". Нестатический метод.</p>
	*
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $rsUser = CUser::GetByID($USER_ID);
	* $arUser = <b>$rsUser-&gt;Fetch</b>();
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/getnext.php">CDBResult::GetNext</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/extractfields.php">CDBResult::ExtractFields</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/navnext.php">CDBResult::NavNext</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/fetch.php
	* @author Bitrix
	*/
	abstract public function Fetch();

	/**
	 * @return array
	 */
	abstract protected function FetchInternal();

	
	/**
	* <p>Метод возвращает количество выбранных записей (выборка записей осуществляется с помощью SQL-команды "SELECT ..."). Нестатический метод.</p> <p class="note"><b>Примечание</b>. Для Oracle версии данный метод будет корректно работать только после вызова <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/navstart.php">CDBResult::NavStart</a>, либо если достигнут конец (последняя запись) выборки.</p>
	*
	*
	* @return int 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $rsBanners = CAdvBanner::GetList($by, $order, $arFilter, $is_filtered);
	* $rsBanners-&gt;NavStart(20);
	* if (intval(<b>$rsBanners-&gt;SelectedRowsCount()</b>)&gt;0):
	*     echo $rsBanners-&gt;NavPrint("Баннеры");
	*     while($rsBanners-&gt;NavNext(true, "f_")):
	*          echo "[".$f_ID."] ".$f_NAME."&lt;br&gt;";
	*     endwhile;
	*     echo $rsBanners-&gt;NavPrint("Баннеры");
	* endif;
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/affectedrowscount.php">CDBResult::AffectedRowsCount</a>
	* </li></ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/selectedrowscount.php
	* @author Bitrix
	*/
	abstract public function SelectedRowsCount();

	
	/**
	* <p>Метод возвращает количество записей, измененных SQL-командами <b>INSERT</b>, <b>UPDATE</b> или <b>DELETE</b>. Нестатический метод.</p> <br>
	*
	*
	* @return int 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $strSql = "
	* 	INSERT INTO b_stat_day(
	* 		ID,
	* 		DATE_STAT,
	* 		TOTAL_HOSTS)
	* 	SELECT
	* 		SQ_B_STAT_DAY.NEXTVAL,
	* 		trunc(SYSDATE),
	* 		nvl(PREV.MAX_TOTAL_HOSTS,0)
	* 	FROM
	* 		(SELECT	max(TOTAL_HOSTS) AS MAX_TOTAL_HOSTS	FROM b_stat_day) PREV						
	* 	WHERE			
	* 		not exists(SELECT 'x' FROM b_stat_day D WHERE TRUNC(D.DATE_STAT) = TRUNC(SYSDATE))
	* 	";
	* $q = $DB-&gt;Query($strSql, true, $err_mess.__LINE__);
	* if ($q &amp;&amp; intval(<b>$q-&gt;AffectedRowsCount</b>())&gt;0)
	* {
	* 	$arFields = Array("LAST"=&gt;"'N'");
	* 	$DB-&gt;Update("b_stat_adv_day",$arFields,"WHERE LAST='Y'", $err_mess.__LINE__);
	* 	$DB-&gt;Update("b_stat_adv_event_day",$arFields,"WHERE LAST='Y'", $err_mess.__LINE__);
	* 	$DB-&gt;Update("b_stat_searcher_day",$arFields,"WHERE LAST='Y'", $err_mess.__LINE__);
	* 	$DB-&gt;Update("b_stat_event_day",$arFields,"WHERE LAST='Y'", $err_mess.__LINE__);
	* 	$DB-&gt;Update("b_stat_country_day",$arFields,"WHERE LAST='Y'", $err_mess.__LINE__);
	* 	$DB-&gt;Update("b_stat_guest",$arFields,"WHERE LAST='Y'",$err_mess.__LINE__);
	* 	$DB-&gt;Update("b_stat_session",$arFields,"WHERE LAST='Y'",$err_mess.__LINE__);
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/selectedrowscount.php">CDBResult::SelectedRowsCount</a>
	* </li></ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/affectedrowscount.php
	* @author Bitrix
	*/
	abstract public function AffectedRowsCount();

	
	/**
	* <p>Метод возвращает количество полей результата выборки. Нестатический метод.</p>
	*
	*
	* @return int 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $rs = $DB-&gt;Query($query,true);
	* $intNumFields = <b>$rs-&gt;FieldsCount</b>();
	* $i = 0;
	* while ($i &lt; $intNumFields) 
	* {
	* 	$arFieldName[] = $rs-&gt;FieldName($i);
	* 	$i++;
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/fieldname.php">CDBResult::FieldName</a>
	* </li></ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/fieldscount.php
	* @author Bitrix
	*/
	abstract public function FieldsCount();

	
	/**
	* <p>Метод возвращает название поля по его номеру. Нестатический метод.</p>
	*
	*
	* @param int $column  
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $rs = $DB-&gt;Query($query,true);
	* $intNumFields = $rs-&gt;FieldsCount();
	* $i = 0;
	* while ($i &lt; $intNumFields) 
	* {
	* 	$arFieldName[] = <b>$rs-&gt;FieldName</b>($i);
	* 	$i++;
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/fieldscount.php">CDBResult::FieldsCount</a>
	* </li></ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/fieldname.php
	* @author Bitrix
	*/
	abstract public function FieldName($iCol);

	public function NavContinue()
	{
		if (count($this->arResultAdd) > 0)
		{
			$this->arResult = $this->arResultAdd;
			return true;
		}
		else
			return false;
	}

	
	/**
	* <p>Метод возвращает <i>false</i>, если все записи умещаются в одну страницу. В противном случае <i>true</i>. Нестатический метод.</p> <p class="note"><b>Внимание!</b> Перед использованием данного метода необходимо вызвать <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/navstart.php">CDBResult::NavStart</a>.</p>
	*
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $arDirContent = array_merge($arDirs, $arFiles);
	* $rsDirContent = new CDBResult;
	* $rsDirContent-&gt;InitFromArray($arDirContent);
	* $rsDirContent-&gt;NavStart(50);
	* if(<b>$rsDirContent-&gt;IsNavPrint</b>())
	* {
	* 	echo "&lt;p&gt;";
	* 	$rsDirContent-&gt;NavPrint("Файлы");
	* 	echo "&lt;/p&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/navprint.php">CDBResult::NavPrint</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/navstart.php">CDBResult::NavStart</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/navnext.php">CDBResult::NavNext</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/isnavprint.php
	* @author Bitrix
	*/
	public function IsNavPrint()
	{
		if ($this->NavRecordCount == 0 || ($this->NavPageCount == 1 && $this->NavShowAll == false))
			return false;

		return true;
	}

	
	/**
	* <p>Метод выводит ссылки для постраничной навигации. Перед использованием данного метода необходимо вызвать метод <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/navstart.php">NavStart</a>.<br><br>По умолчанию в сессии запоминается последняя открытая страница постраничной навигации. Если вы хотите изменить такое поведение для данной текущей страницы, то до вызова етода необходимо воспользоваться следующим кодом: </p> <pre class="syntax" id="xmp27BC3DDD"> CPageOption::SetOptionString("main", "nav_page_in_session", "N");</pre> <p>Нестатический метод.</p>
	*
	*
	* @param string $title  Названия выводимых элементов.
	*
	* @param bool $show_always = false Если "false", то метод не будет выводить навигационные ссылки если
	* все записи умещаются на одну страницу. Если "true", то ссылки для
	* постраничной навигации будут выводиться
	* всегда.<br>Необязательный. По умолчанию - "false".
	*
	* @param string $StyleText = "text" CSS класс шрифта для вывода навигационных
	* ссылок.<br>Необязательный. По умолчанию "text".
	*
	* @param string $template_path = false Путь к шаблону показа навигационных ссылок. Если "false", то
	* используется шаблон по умолчанию.
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $rsEvents = CAdv::GetEventList($f_ID,($by="s_def"),($order="desc"), $arF, $is_filtered);
	* <b>$rsEvents-&gt;NavPrint</b>("События", false, "tablebodytext", 
	* "/bitrix/modules/statistic/admin/adv_navprint.php");
	* ?&gt;
	* &lt;?
	* echo('&lt;font class="'.$StyleText.'"&gt;('.$title.' ');
	* echo(($this-&gt;NavPageNomer-1)*$this-&gt;NavPageSize+1);
	* echo(' - ');
	* if($this-&gt;NavPageNomer != $this-&gt;NavPageCount)
	*   echo($this-&gt;NavPageNomer * $this-&gt;NavPageSize);
	* else
	*   echo($this-&gt;NavRecordCount); 
	* echo(' '.GetMessage("nav_of").' ');
	* echo($this-&gt;NavRecordCount);
	* echo(")\n \n&lt;/font&gt;");
	* 
	* echo('&lt;font class="'.$StyleText.'"&gt;');
	* 
	* if($this-&gt;NavPageNomer &gt; 1)
	*   echo('&lt;a class="tablebodylink" href="'.$sUrlPath.'?PAGEN_'.$this-&gt;NavNum.'=1'.
	*   $strNavQueryString.'#nav_start'.$add_anchor.'"&gt;'.
	*   $sBegin.'&lt;/a&gt; | &lt;a class="tablebodylink" href="'.$sUrlPath.'?PAGEN_'.
	*   $this-&gt;NavNum.'='.($this-&gt;NavPageNomer-1).$strNavQueryString.'#nav_start'.
	*   $add_anchor.'"&gt;'.$sPrev.'&lt;/a&gt;');
	* else
	*   echo($sBegin.' | '.$sPrev);
	* 
	* echo(' | '); 
	* 
	* $NavRecordGroup = $nStartPage;
	* while($NavRecordGroup &lt;= $nEndPage)
	* {
	*   if($NavRecordGroup == $this-&gt;NavPageNomer) 
	*     echo('&lt;b&gt;'.$NavRecordGroup.'&lt;/b&gt;&amp;nbsp'); 
	*   else
	*     echo('&lt;a class="tablebodylink" href="'.$sUrlPath.'?PAGEN_'.$this-&gt;NavNum.'='.
	* 	$NavRecordGroup.$strNavQueryString.'#nav_start'.$add_anchor.'"&gt;'.
	* 	$NavRecordGroup.'&lt;/a&gt; ');
	* 
	*   $NavRecordGroup++;
	* }
	* 
	* echo('| ');
	* if($this-&gt;NavPageNomer &lt; $this-&gt;NavPageCount)
	*   echo ('&lt;a class="tablebodylink" href="'.$sUrlPath.'?PAGEN_'.$this-&gt;NavNum.'='.
	*   ($this-&gt;NavPageNomer+1).$strNavQueryString.'#nav_start'.$add_anchor.'"&gt;'.
	*   $sNext.'&lt;/a&gt; | &lt;a class="tablebodylink" href="'.$sUrlPath.'?PAGEN_'.
	*   $this-&gt;NavNum.'='.$this-&gt;NavPageCount.$strNavQueryString.
	*   '#nav_start'.$add_anchor.'"&gt;'.$sEnd.'&lt;/a&gt; ');
	* else
	*   echo ($sNext.' | '.$sEnd.' ');
	* 
	* if($this-&gt;bShowAll)
	*   echo ($this-&gt;NavShowAll? '| &lt;a class="tablebodylink" 
	*   href="'.$sUrlPath.'?SHOWALL_'.$this-&gt;NavNum.'=0'.$strNavQueryString.
	*   '#nav_start'.$add_anchor.'"&gt;'.$sPaged.
	*   '&lt;/a&gt; ' : '| &lt;a class="tablebodylink" href="'.$sUrlPath.'?SHOWALL_'.
	*   $this-&gt;NavNum.'=1'.$strNavQueryString.
	*   '#nav_start'.$add_anchor.'"&gt;'.$sAll.'&lt;/a&gt; ');
	* 
	* echo('&lt;/font&gt;');
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/navstart.php">CDBResult::NavStart</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/navnext.php">CDBResult::NavNext</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/isnavprint.php">CDBResult::IsNavPrint</a> </li>
	* <li><a href="http://dev.1c-bitrix.ru/api_help/main/reference/cpageoption/index.php">Класс CPageOption</a></li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/navprint.php
	* @author Bitrix
	*/
	public function NavPrint($title, $show_allways=false, $StyleText="text", $template_path=false)
	{
		echo $this->GetNavPrint($title, $show_allways, $StyleText, $template_path);
	}

	public function GetNavPrint($title, $show_allways=false, $StyleText="text", $template_path=false, $arDeleteParam=false)
	{
		$res = '';
		$add_anchor = $this->add_anchor;

		$sBegin = GetMessage("nav_begin");
		$sEnd = GetMessage("nav_end");
		$sNext = GetMessage("nav_next");
		$sPrev = GetMessage("nav_prev");
		$sAll = GetMessage("nav_all");
		$sPaged = GetMessage("nav_paged");

		$nPageWindow = $this->nPageWindow;

		if(!$show_allways)
		{
			if ($this->NavRecordCount == 0 || ($this->NavPageCount == 1 && $this->NavShowAll == false))
				return '';
		}

		$sUrlPath = GetPagePath();

		$arDel = array("PAGEN_".$this->NavNum, "SIZEN_".$this->NavNum, "SHOWALL_".$this->NavNum, "PHPSESSID");
		if(is_array($arDeleteParam))
			$arDel = array_merge($arDel, $arDeleteParam);
		$strNavQueryString = DeleteParam($arDel);
		if($strNavQueryString <> "")
			$strNavQueryString = htmlspecialcharsbx("&".$strNavQueryString);

		if($template_path!==false && !file_exists($template_path) && file_exists($_SERVER["DOCUMENT_ROOT"].$template_path))
			$template_path = $_SERVER["DOCUMENT_ROOT"].$template_path;

		if($this->bDescPageNumbering === true)
		{
			if($this->NavPageNomer + floor($nPageWindow/2) >= $this->NavPageCount)
				$nStartPage = $this->NavPageCount;
			else
			{
				if($this->NavPageNomer + floor($nPageWindow/2) >= $nPageWindow)
					$nStartPage = $this->NavPageNomer + floor($nPageWindow/2);
				else
				{
					if($this->NavPageCount >= $nPageWindow)
						$nStartPage = $nPageWindow;
					else
						$nStartPage = $this->NavPageCount;
				}
			}

			if($nStartPage - $nPageWindow >= 0)
				$nEndPage = $nStartPage - $nPageWindow + 1;
			else
				$nEndPage = 1;
			//echo "nEndPage = $nEndPage; nStartPage = $nStartPage;";
		}
		else
		{
			if($this->NavPageNomer > floor($nPageWindow/2) + 1 && $this->NavPageCount > $nPageWindow)
				$nStartPage = $this->NavPageNomer - floor($nPageWindow/2);
			else
				$nStartPage = 1;

			if($this->NavPageNomer <= $this->NavPageCount - floor($nPageWindow/2) && $nStartPage + $nPageWindow-1 <= $this->NavPageCount)
				$nEndPage = $nStartPage + $nPageWindow - 1;
			else
			{
				$nEndPage = $this->NavPageCount;
				if($nEndPage - $nPageWindow + 1 >= 1)
					$nStartPage = $nEndPage - $nPageWindow + 1;
			}
		}

		$this->nStartPage = $nStartPage;
		$this->nEndPage = $nEndPage;

		if($template_path!==false && file_exists($template_path))
		{
/*
			$this->bFirstPrintNav - is first tiem call
			$this->NavPageNomer - number of current page
			$this->NavPageCount - total page count
			$this->NavPageSize - page size
			$this->NavRecordCount - records count
			$this->bShowAll - show "all" link
			$this->NavShowAll - is all shown
			$this->NavNum - number of navigation
			$this->bDescPageNumbering - reverse paging

			$this->nStartPage - first page in chain
			$this->nEndPage - last page in chain

			$strNavQueryString - query string
			$sUrlPath - current url

			Url for link to the page #PAGE_NUMBER#:
			$sUrlPath.'?PAGEN_'.$this->NavNum.'='.#PAGE_NUMBER#.$strNavQueryString.'#nav_start"'.$add_anchor
*/

			ob_start();
			include($template_path);
			$res = ob_get_contents();
			ob_end_clean();
			$this->bFirstPrintNav = false;
			return $res;
		}

		if($this->bFirstPrintNav)
		{
			$res .= '<a name="nav_start'.$add_anchor.'"></a>';
			$this->bFirstPrintNav = false;
		}

		$res .= '<font class="'.$StyleText.'">'.$title.' ';
		if($this->bDescPageNumbering === true)
		{
			$makeweight = ($this->NavRecordCount % $this->NavPageSize);
			$NavFirstRecordShow = 0;
			if($this->NavPageNomer != $this->NavPageCount)
				$NavFirstRecordShow += $makeweight;

			$NavFirstRecordShow += ($this->NavPageCount - $this->NavPageNomer) * $this->NavPageSize + 1;

			if ($this->NavPageCount == 1)
				$NavLastRecordShow = $this->NavRecordCount;
			else
				$NavLastRecordShow = $makeweight + ($this->NavPageCount - $this->NavPageNomer + 1) * $this->NavPageSize;

			$res .= $NavFirstRecordShow;
			$res .= ' - '.$NavLastRecordShow;
			$res .= ' '.GetMessage("nav_of").' ';
			$res .= $this->NavRecordCount;
			$res .= "\n<br>\n</font>";

			$res .= '<font class="'.$StyleText.'">';

			if($this->NavPageNomer < $this->NavPageCount)
				$res .= '<a href="'.$sUrlPath.'?PAGEN_'.$this->NavNum.'='.$this->NavPageCount.$strNavQueryString.'#nav_start'.$add_anchor.'">'.$sBegin.'</a>&nbsp;|&nbsp;<a href="'.$sUrlPath.'?PAGEN_'.$this->NavNum.'='.($this->NavPageNomer+1).$strNavQueryString.'#nav_start'.$add_anchor.'">'.$sPrev.'</a>';
			else
				$res .= $sBegin.'&nbsp;|&nbsp;'.$sPrev;

			$res .= '&nbsp;|&nbsp;';

			$NavRecordGroup = $nStartPage;
			while($NavRecordGroup >= $nEndPage)
			{
				$NavRecordGroupPrint = $this->NavPageCount - $NavRecordGroup + 1;
				if($NavRecordGroup == $this->NavPageNomer)
					$res .= '<b>'.$NavRecordGroupPrint.'</b>&nbsp';
				else
					$res .= '<a href="'.$sUrlPath.'?PAGEN_'.$this->NavNum.'='.$NavRecordGroup.$strNavQueryString.'#nav_start'.$add_anchor.'">'.$NavRecordGroupPrint.'</a>&nbsp;';
				$NavRecordGroup--;
			}
			$res .= '|&nbsp;';
			if($this->NavPageNomer > 1)
				$res .= '<a href="'.$sUrlPath.'?PAGEN_'.$this->NavNum.'='.($this->NavPageNomer-1).$strNavQueryString.'#nav_start'.$add_anchor.'">'.$sNext.'</a>&nbsp;|&nbsp;<a href="'.$sUrlPath.'?PAGEN_'.$this->NavNum.'=1'.$strNavQueryString.'#nav_start'.$add_anchor.'">'.$sEnd.'</a>&nbsp;';
			else
				$res .= $sNext.'&nbsp;|&nbsp;'.$sEnd.'&nbsp;';
		}
		else
		{
			$res .= ($this->NavPageNomer-1)*$this->NavPageSize+1;
			$res .= ' - ';
			if($this->NavPageNomer != $this->NavPageCount)
				$res .= $this->NavPageNomer * $this->NavPageSize;
			else
				$res .= $this->NavRecordCount;
			$res .= ' '.GetMessage("nav_of").' ';
			$res .= $this->NavRecordCount;
			$res .= "\n<br>\n</font>";

			$res .= '<font class="'.$StyleText.'">';

			if($this->NavPageNomer > 1)
				$res .= '<a href="'.$sUrlPath.'?PAGEN_'.$this->NavNum.'=1'.$strNavQueryString.'#nav_start'.$add_anchor.'">'.$sBegin.'</a>&nbsp;|&nbsp;<a href="'.$sUrlPath.'?PAGEN_'.$this->NavNum.'='.($this->NavPageNomer-1).$strNavQueryString.'#nav_start'.$add_anchor.'">'.$sPrev.'</a>';
			else
				$res .= $sBegin.'&nbsp;|&nbsp;'.$sPrev;

			$res .= '&nbsp;|&nbsp;';

			$NavRecordGroup = $nStartPage;
			while($NavRecordGroup <= $nEndPage)
			{
				if($NavRecordGroup == $this->NavPageNomer)
					$res .= '<b>'.$NavRecordGroup.'</b>&nbsp';
				else
					$res .= '<a href="'.$sUrlPath.'?PAGEN_'.$this->NavNum.'='.$NavRecordGroup.$strNavQueryString.'#nav_start'.$add_anchor.'">'.$NavRecordGroup.'</a>&nbsp;';
				$NavRecordGroup++;
			}
			$res .= '|&nbsp;';
			if($this->NavPageNomer < $this->NavPageCount)
				$res .= '<a href="'.$sUrlPath.'?PAGEN_'.$this->NavNum.'='.($this->NavPageNomer+1).$strNavQueryString.'#nav_start'.$add_anchor.'">'.$sNext.'</a>&nbsp;|&nbsp;<a href="'.$sUrlPath.'?PAGEN_'.$this->NavNum.'='.$this->NavPageCount.$strNavQueryString.'#nav_start'.$add_anchor.'">'.$sEnd.'</a>&nbsp;';
			else
				$res .= $sNext.'&nbsp;|&nbsp;'.$sEnd.'&nbsp;';
		}

		if($this->bShowAll)
			$res .= $this->NavShowAll? '|&nbsp;<a href="'.$sUrlPath.'?SHOWALL_'.$this->NavNum.'=0'.$strNavQueryString.'#nav_start'.$add_anchor.'">'.$sPaged.'</a>&nbsp;' : '|&nbsp;<a href="'.$sUrlPath.'?SHOWALL_'.$this->NavNum.'=1'.$strNavQueryString.'#nav_start'.$add_anchor.'">'.$sAll.'</a>&nbsp;';

		$res .= '</font>';
		return $res;
	}

	
	/**
	* <p>Объявляет глобальные переменные с именами вида ${<i>prefix</i>."имя поля"} и значениями соответствующими именам полей, приведенных в HTML-безопасный вид.<br>Возвращает массив вида Array("поле"=&gt;"значение" [, ...]) и передвигает курсор на следующую запись. Если достигнута последняя запись (или в результате нет ни одной записи), то метод вернет "false". Нестатический метод.</p>
	*
	*
	* @param string $prefix = "str_" Префикс глобальных переменных. 		<br>Необязательный. По умолчанию
	* "str_".
	*
	* @param bool $encode = true Приводить глобальные переменные в HTML-безопасный вид.
	* 		<br>Необязательный. По умолчанию - "true".
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;select&gt;
	* &lt;?
	* $rs = CGroup::GetList($order="ID", $by="ASC");
	* while (<b>$rs-&gt;ExtractFields</b>("g_")) :
	*    ?&gt;&lt;option value="&lt;?=$g_ID?&gt;"
	*    &lt;?if (IntVal($g_ID)==IntVal($show_perms_for)) echo " selected";?&gt;
	*    &gt;&lt;?=$g_NAME?&gt;&lt;/option&gt;&lt;?
	* endwhile;
	* ?&gt;
	* &lt;/select&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/fetch.php">CDBResult::Fetch</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/getnext.php">CDBResult::GetNext</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/navnext.php">CDBResult::NavNext</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/extractfields.php
	* @author Bitrix
	*/
	public function ExtractFields($strPrefix="str_", $bDoEncode=true)
	{
		return $this->NavNext(true, $strPrefix, $bDoEncode);
	}

	public function ExtractEditFields($strPrefix="str_")
	{
		return $this->NavNext(true, $strPrefix, true, false);
	}

	
	/**
	* <p>Возвращает массив значений полей приведенный в HTML-безопасный вид. Если достигнут конец результата выборки метод вернет <i>false</i>. Нестатический метод.</p>
	*
	*
	* @param bool $TextHtmlAuto = true Если значение данного параметра - "true", то метод будет
	* автоматически обрабатывать поля с выбором формата
	* text/html.<br>Необязательный. По умолчанию - "true".
	*
	* @param bool $use_tilda = true Если значение данного параметра - "true", то помимо преобразованных
	* в HTML-безопасный вид полей, в результирующий массив будут включены
	* также оригинальные (исходные) значения этих полей (ключи массива
	* с оригинальными значениями этих полей будут иметь суффикс
	* "~").<br>Необязательный. По умолчанию - "true".
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;select&gt;
	* &lt;?
	* $rs = CGroup::GetList($order="ID", $by="ASC");
	* while ($arGroup=<b>$rs-&gt;GetNext</b>()) :
	*    ?&gt;&lt;option value="&lt;?=$arGroup["ID"]?&gt;"
	*    &lt;?if (IntVal($arGroup["ID"])==IntVal($show_perms_for)) echo " selected";?&gt;
	*    &gt;&lt;?=$arGroup["NAME"]?&gt;&lt;/option&gt;&lt;?
	* endwhile;
	* ?&gt;
	* &lt;/select&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/fetch.php">CDBResult::Fetch</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/extractfields.php">CDBResult::ExtractFields</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/navnext.php">CDBResult::NavNext</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/getnext.php
	* @author Bitrix
	*/
	public function GetNext($bTextHtmlAuto=true, $use_tilda=true)
	{
		if($arRes = $this->Fetch())
		{
			if($this->arGetNextCache==false)
			{
				$this->arGetNextCache = array();
				foreach($arRes as $FName=>$arFValue)
					$this->arGetNextCache[$FName] = array_key_exists($FName."_TYPE", $arRes);
			}
			if($use_tilda)
			{
				$arTilda = array();
				foreach($arRes as $FName=>$arFValue)
				{
					if($this->arGetNextCache[$FName] && $bTextHtmlAuto)
						$arTilda[$FName] = FormatText($arFValue, $arRes[$FName."_TYPE"]);
					elseif(is_array($arFValue))
						$arTilda[$FName] = htmlspecialcharsEx($arFValue);
					elseif(preg_match("/[;&<>\"]/", $arFValue))
						$arTilda[$FName] = htmlspecialcharsEx($arFValue);
					else
						$arTilda[$FName] = $arFValue;
					$arTilda["~".$FName] = $arFValue;
				}
				return $arTilda;
			}
			else
			{
				foreach($arRes as $FName=>$arFValue)
				{
					if($this->arGetNextCache[$FName] && $bTextHtmlAuto)
						$arRes[$FName] = FormatText($arFValue, $arRes[$FName."_TYPE"]);
					elseif(is_array($arFValue))
						$arRes[$FName] = htmlspecialcharsEx($arFValue);
					elseif(preg_match("/[;&<>\"]/", $arFValue))
						$arRes[$FName] = htmlspecialcharsEx($arFValue);
				}
			}
		}
		return $arRes;
	}

	
	/**
	* <p>Возвращает уникальную строку идентифицирующую текущее состояние постраничной навигации (номер текущей страницы, нажата ли ссылка "Все"). Результат данного метода применяется как правило для составления идентификатора кэша, который в свою очередь используется в методах классов <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cpagecache/index.php">CPageCache</a> и <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cphpcache/index.php">CPHPCache</a>. Нестатический метод.</p>
	*
	*
	* @param int $page_size = 10 Размер страницы постраничной навигации (от 1 и более).
	* Необязательный. По умолчанию 10.
	*
	* @param bool $show_all = true Разрешить ли показывать все записи (и выводить ссылку "Все" в
	* навигации).<br>Необязательный. По умолчанию - "true".
	*
	* @param int $NumPage = false Принудительно ли открывать страницу с этим номером (в
	* независимости от параметров в URL).<br>Необязательный. По умолчанию -
	* "false" (открывать страницу в зависимости от параметров в URL).
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* // создаем объект
	* $obCache = new CPageCache; 
	* 
	* // время кэширования - 30 минут
	* $life_time = 30*60; 
	* 
	* // получим строку идентифицирующую состояние постраничной навигации
	* $nav = <b>CDBResult::NavStringForCache</b>($PAGE_ELEMENT_COUNT);
	* 
	* // формируем идентификатор кэша в зависимости от всех параметров 
	* // которые могут повлиять на результирующий HTML
	* $cache_id = $nav.$ELEMENT_ID.$IBLOCK_TYPE.$USER-&gt;GetUserGroupString(); 
	* 
	* // инициализируем буферизирование вывода
	* if($obCache-&gt;StartDataCache($life_time, $cache_id, "/")):
	* 
	* 	// получаем список элементов
	* 	if ($rsElements = GetIBlockElementList($IBLOCK_ID, $SECTION_ID)):
	* 
	* 		// инициализируем постраничную навигацию
	* 		$rsElements-&gt;NavStart($PAGE_ELEMENT_COUNT);
	* 
	* 		// выведем постраничную навигацию
	* 		echo $rsElements-&gt;NavPrint($ELEMENT_NAME);
	* 
	* 		// пройдемся по элементам
	* 		while ($obElement = $rsElements-&gt;GetNextElement()):
	* 
	* 			$arElement = $obElement-&gt;GetFields();
	* 			$arProperty = $obElement-&gt;GetProperties();		
	* 
	* 			echo "&lt;pre&gt;"; print_r($arElement); echo "&lt;/pre&gt;";
	* 			echo "&lt;pre&gt;"; print_r($arProperty); echo "&lt;/pre&gt;";
	* 
	* 		endwhile;
	* 	endif;
	* 
	* 	// записываем буферизированный результат на диск в файл кэша
	* 	$obCache-&gt;EndDataCache(); 
	* endif;
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cpagecache/index.php">Класс CPageCache</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cphpcache/index.php">Класс CPHPCache</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/navstringforcache.php
	* @author Bitrix
	*/
	public static function NavStringForCache($nPageSize=0, $bShowAll=true, $iNumPage=false)
	{
		$NavParams = CDBResult::GetNavParams($nPageSize, $bShowAll, $iNumPage);
		return "|".($NavParams["SHOW_ALL"]?"":$NavParams["PAGEN"])."|".$NavParams["SHOW_ALL"]."|";
	}

	public static function GetNavParams($nPageSize=0, $bShowAll=true, $iNumPage=false)
	{
		/** @global CMain $APPLICATION */
		global $NavNum, $APPLICATION;

		$bDescPageNumbering = false; //it can be extracted from $nPageSize

		if(is_array($nPageSize))
		{
			$params = $nPageSize;
			if(isset($params["iNumPage"]))
				$iNumPage = $params["iNumPage"];
			if(isset($params["nPageSize"]))
				$nPageSize = $params["nPageSize"];
			if(isset($params["bDescPageNumbering"]))
				$bDescPageNumbering = $params["bDescPageNumbering"];
			if(isset($params["bShowAll"]))
				$bShowAll = $params["bShowAll"];
			if(isset($params["NavShowAll"]))
				$NavShowAll = $params["NavShowAll"];
			if(isset($params["sNavID"]))
				$sNavID = $params["sNavID"];
		}

		$nPageSize = intval($nPageSize);
		$NavNum = intval($NavNum);

		$PAGEN_NAME = "PAGEN_".($NavNum+1);
		$SHOWALL_NAME = "SHOWALL_".($NavNum+1);

		global ${$PAGEN_NAME}, ${$SHOWALL_NAME};
		$md5Path = md5((isset($sNavID)? $sNavID: $APPLICATION->GetCurPage()));

		if($iNumPage === false)
			$PAGEN = ${$PAGEN_NAME};
		else
			$PAGEN = $iNumPage;

		$SHOWALL = ${$SHOWALL_NAME};

		$SESS_PAGEN = $md5Path."SESS_PAGEN_".($NavNum+1);
		$SESS_ALL = $md5Path."SESS_ALL_".($NavNum+1);
		if(intval($PAGEN) <= 0)
		{
			if(CPageOption::GetOptionString("main", "nav_page_in_session", "Y")=="Y" && intval($_SESSION[$SESS_PAGEN])>0)
				$PAGEN = $_SESSION[$SESS_PAGEN];
			elseif($bDescPageNumbering === true)
				$PAGEN = 0;
			else
				$PAGEN = 1;
		}

		//Number of records on a page
		$SIZEN = $nPageSize;
		if(intval($SIZEN) < 1)
			$SIZEN = 10;

		//Show all records
		$SHOW_ALL = ($bShowAll? (isset($SHOWALL) ? ($SHOWALL == 1) : (CPageOption::GetOptionString("main", "nav_page_in_session", "Y")=="Y" && $_SESSION[$SESS_ALL] == 1)) : false);

		//$NavShowAll comes from $nPageSize array
		$res = array(
			"PAGEN"=>$PAGEN,
			"SIZEN"=>$SIZEN,
			"SHOW_ALL"=>(isset($NavShowAll)? $NavShowAll : $SHOW_ALL),
		);

		if(CPageOption::GetOptionString("main", "nav_page_in_session", "Y")=="Y")
		{
			$_SESSION[$SESS_PAGEN] = $PAGEN;
			$_SESSION[$SESS_ALL] = $SHOW_ALL;
			$res["SESS_PAGEN"] = $SESS_PAGEN;
			$res["SESS_ALL"] = $SESS_ALL;
		}

		return $res;
	}

	public function InitNavStartVars($nPageSize=0, $bShowAll=true, $iNumPage=false)
	{
		if(is_array($nPageSize) && isset($nPageSize["bShowAll"]))
			$this->bShowAll = $nPageSize["bShowAll"];
		else
			$this->bShowAll = $bShowAll;

		$this->bNavStart = true;

		$arParams = self::GetNavParams($nPageSize, $bShowAll, $iNumPage);

		$this->PAGEN = $arParams["PAGEN"];
		$this->SIZEN = $arParams["SIZEN"];
		$this->NavShowAll = $arParams["SHOW_ALL"];
		$this->NavPageSize = $arParams["SIZEN"];
		$this->SESS_SIZEN = $arParams["SESS_SIZEN"];
		$this->SESS_PAGEN = $arParams["SESS_PAGEN"];
		$this->SESS_ALL = $arParams["SESS_ALL"];

		global $NavNum;

		$NavNum++;
		$this->NavNum = $NavNum;

		if($this->NavNum>1)
			$add_anchor = "_".$this->NavNum;
		else
			$add_anchor = "";

		$this->add_anchor = $add_anchor;
	}

	
	/**
	* <p>Метод разбивает результат выборки на страницы.</p> <p> Для встраивания системы автоматической постраничной навигации необходимо сначала вызвать данный метод <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/navstart.php">CDBResult::NavStart</a>. После ее вызова, методы  </p> <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/fetch.php">CDBResult::Fetch</a> 	</li> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/getnext.php">CDBResult::GetNext</a> 	</li> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/extractfields.php">CDBResult::ExtractFields</a> 	</li> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/navnext.php">CDBResult::NavNext</a>  </li> </ul> будут ограничены только текущей страницей (а не всей выборкой). Для вывода ссылок постраничной навигации необходимо воспользоваться методом <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/navprint.php">CDBResult::NavPrint</a>. <p>Нестатический метод.</p>
	*
	*
	* @param int $page_size = 10 Размер страницы (от 1 и более). Необязательный. По умолчанию 10.
	*
	* @param bool $show_all = true Разрешить показывать все записи (и выводить ссылку "Все" в
	* навигации).<br>Необязательный. По умолчанию - "true".
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $rsBanners = CAdvBanner::GetList($by, $order, $arFilter, $is_filtered);
	* <b>$rsBanners-&gt;NavStart(20)</b>;
	* echo $rsBanners-&gt;NavPrint("Баннеры");
	* while($rsBanners-&gt;NavNext(true, "f_")):
	*     echo "[".$f_ID."] ".$f_NAME."&lt;br&gt;";
	* endwhile;
	* echo $rsBanners-&gt;NavPrint("Баннеры");
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/navnext.php">CDBResult::NavNext</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/navprint.php">CDBResult::NavPrint</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/isnavprint.php">CDBResult::IsNavPrint</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/fetch.php">CDBResult::Fetch</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/getnext.php">CDBResult::GetNext</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/extractfields.php">CDBResult::ExtractFields</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/navstart.php
	* @author Bitrix
	*/
	public function NavStart($nPageSize=0, $bShowAll=true, $iNumPage=false)
	{
		if($this->bFromLimited)
			return;

		if(is_array($nPageSize))
			$this->InitNavStartVars($nPageSize);
		else
			$this->InitNavStartVars(intval($nPageSize), $bShowAll, $iNumPage);

		if($this->bFromArray)
		{
			$this->NavRecordCount = count($this->arResult);
			if($this->NavRecordCount < 1)
				return;

			if($this->NavShowAll)
				$this->NavPageSize = $this->NavRecordCount;

			$this->NavPageCount = floor($this->NavRecordCount/$this->NavPageSize);
			if($this->NavRecordCount % $this->NavPageSize > 0)
				$this->NavPageCount++;

			$this->NavPageNomer =
				($this->PAGEN < 1 || $this->PAGEN > $this->NavPageCount
				?
					(CPageOption::GetOptionString("main", "nav_page_in_session", "Y")!="Y"
						|| $_SESSION[$this->SESS_PAGEN] < 1
						|| $_SESSION[$this->SESS_PAGEN] > $this->NavPageCount
					?
						1
					:
						$_SESSION[$this->SESS_PAGEN]
					)
				:
					$this->PAGEN
				);

			$NavFirstRecordShow = $this->NavPageSize*($this->NavPageNomer-1);
			$NavLastRecordShow = $this->NavPageSize*$this->NavPageNomer;

			$this->arResult = array_slice($this->arResult, $NavFirstRecordShow, $NavLastRecordShow - $NavFirstRecordShow);
		}
		else
		{
			$this->DBNavStart();
		}
	}

	abstract public function DBNavStart();

	
	/**
	* <p>Метод инициализирует объект класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> значениями из массива. Нестатический метод.</p>
	*
	*
	* @param array $values  
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $arr = array();
	* $arr[] = array("ID" =&gt; 1, "NAME" =&gt; "Заголовок 1");
	* $arr[] = array("ID" =&gt; 2, "NAME" =&gt; "Заголовок 2");
	* $arr[] = array("ID" =&gt; 3, "NAME" =&gt; "Заголовок 3");
	* $arr[] = array("ID" =&gt; 4, "NAME" =&gt; "Заголовок 4");
	* 
	* $rs = new CDBResult;
	* <b>$rs-&gt;InitFromArray</b>($arr);
	* 
	* $rs-&gt;NavStart(2);
	* if($rs-&gt;IsNavPrint())
	* {
	*      echo "&lt;p&gt;"; $rs-&gt;NavPrint("Элементы"); echo "&lt;/p&gt;";
	* }
	* ?&gt;
	* &lt;?
	* // получим список файлов и каталогов
	* CFileMan::GetDirList(Array($site_id, $path), $arDirs, $arFiles, $arFilter, Array($by=&gt;$order), "DF");
	* 
	* // объединим файлы и каталоги в один массив
	* $arDirContent = array_merge($arDirs, $arFiles);
	* 
	* // создадим объект класса CDBResult
	* $rsDirContent = new CDBResult;
	* 
	* // инициализируем этот объект исходным массивом
	* <b>$rsDirContent-&gt;InitFromArray</b>($arDirContent);
	* 
	* // теперь на данном объекте 
	* // мы можем использовать все методы класса CDBResult
	* // например, "Постраничная навигация":
	* $rsDirContent-&gt;NavStart(50);
	* if($rsDirContent-&gt;IsNavPrint()) echo "&lt;p&gt;"; $rs-&gt;NavPrint("Файлы"); echo "&lt;/p&gt;";
	* while ($arElement = $rsDirContent-&gt;Fetch()):
	*     // если это каталог то
	*     if ($arElement["TYPE"]=="D"):
	*         // выводим название каталога
	*         echo $arElement["NAME"];
	*     else: // иначе если это файл то
	*         // если это служебный файл то переходим к следующему элементу
	*         if ($arElement["NAME"]==".section.php") continue;
	*         // иначе выводим его название
	*         echo $arElement["NAME"];
	*     endif;
	* endwhile;
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/initfromarray.php
	* @author Bitrix
	*/
	public function InitFromArray($arr)
	{
		if(is_array($arr))
			reset($arr);
		$this->arResult = $arr;
		$this->nSelectedCount = count($arr);
		$this->bFromArray = true;
	}

	
	/**
	* <p>Возвращает массив значений полей. Если установлен флаг <i>init_globals</i>, то объявляет глобальные переменные с именами <i>prefix</i>.имя_поля. Если достигнут конец результата выборки, то метод вернет "false". Нестатический метод.</p> <p></p> <div class="note"> <b>Примечания</b>: <br><ul> <li>Метод работает с переменными из глобальной области видимости, это необходимо учитывать при создании основных файлов компонентов.</li> <li>Когда явно не требуются возможности NavNext лучше использовать <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/getnext.php">CDBResult::GetNext</a>.</li>   </ul> </div>
	*
	*
	* @param bool $SetGlobalVars = true Если "true", то метод будет объявлять глобальные переменные
	* соответствующие именам полей выборки.<br>Необязательный. По
	* умолчанию - "true".
	*
	* @param string $prefix = "str_" Префикс глобальных переменных (только если <i>init_globals</i>
	* установлен).<br>Необязательный. По умолчанию - "str_".
	*
	* @param bool $DoEncode = true Приводить глобальные переменные в HTML-безопасный вид (только если
	* <i>init_globals</i> установлен).<br>Необязательный. По умолчанию - "true".
	*
	* @param bool $SkipEntities = true Необязательный. По умолчанию - "true".
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $rsBanners = CAdvBanner::GetList($by, $order, $arFilter, $is_filtered);
	* $rsBanners-&gt;NavStart(20);
	* echo $rsBanners-&gt;NavPrint("Баннеры");
	* while(<b>$rsBanners-&gt;NavNext</b>(true, "f_")):
	*     echo "[".$f_ID."] ".$f_NAME."&lt;br&gt;";
	* endwhile;
	* echo $rsBanners-&gt;NavPrint("Баннеры");
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/navstart.php">CDBResult::NavStart</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/fetch.php">CDBResult::Fetch</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/getnext.php">CDBResult::GetNext</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/extractfields.php">CDBResult::ExtractFields</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/navnext.php
	* @author Bitrix
	*/
	public function NavNext($bSetGlobalVars=true, $strPrefix="str_", $bDoEncode=true, $bSkipEntities=true)
	{
		$arr = $this->Fetch();
		if($arr && $bSetGlobalVars)
		{
			foreach($arr as $key=>$val)
			{
				$varname = $strPrefix.$key;
				global $$varname;

				if($bDoEncode && !is_array($val) && !is_object($val))
				{
					if($bSkipEntities)
						$$varname = htmlspecialcharsEx($val);
					else
						$$varname = htmlspecialcharsbx($val);
				}
				else
				{
					$$varname = $val;
				}
			}
		}
		return $arr;
	}

	
	/**
	* <p>Нестатический метод.</p>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/getpagenavstring.php
	* @author Bitrix
	*/
	public function GetPageNavString($navigationTitle, $templateName = "", $showAlways=false, $parentComponent=null)
	{
		return $this->GetPageNavStringEx($dummy, $navigationTitle, $templateName, $showAlways, $parentComponent);
	}

	
	/**
	* <p>Возвращает панель постраничной навигации в HTML виде. Формирует ее на основе параметров. Нестатический метод.</p>
	*
	*
	* @param mixed $navComponentObject  Использовать обратную навигацию
	*
	* @param navComponentObjec $navigationTitle  Название категорий
	*
	* @param navigationTitl $templateName = "" Название шаблона
	*
	* @param mixed $showAlways = false Выводить всегда
	*
	* @param array $parentComponent = nul Время кеширования страниц для обратной навигации
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* CModule::IncludeModule('iblock');
	* $arSort = array();
	* $arFilter = array('IBLOCK_ID'=&gt; '1');
	* $arNavParams = array(
	*         "nPageSize" =&gt; '2',
	*         "bDescPageNumbering" =&gt; 'Описание',
	*         "bShowAll" =&gt; 'Y',
	*     );  
	* 
	* $arSelect = array("ID", "NAME");
	* $rsElement = CIBlockElement::GetList($arSort, $arFilter, false, $arNavParams, $arSelect);
	* $NAV_STRING = $rsElement-&gt;GetPageNavStringEx($navComponentObject, 'Заголовок', '', 'Y');
	* echo $NAV_STRING."<br>";
	* while($arElem = $rsElement-&gt;Fetch())
	* {
	*    echo $arElem['ID']."__".$arElem["NAME"]."<br>";
	* }
	* echo $NAV_STRING;$rsElements = CIBlockElement::GetList($arSort, $arFilter, false, array("nPageSize" =&gt; $arParams["PAGE_COUNT"], "bShowAll" =&gt; false), $arSelect);
	* ....
	* $arResult["NAV_STRING"] = $rsElements-&gt;GetPageNavStringEx($navComponentObject, "", $arParams["PAGER_TEMPLATE"]);Теперь в $arResult["NAV_STRING"] у нас полная постраничная навигация, обернутая в шаблон $arParams["PAGER_TEMPLATE"]. Если $arParams["PAGER_TEMPLATE"] пуст, то берется .default.Иногда надо чтобы не выводилось много страниц (1....11, 12, 13, 14, 15, 16, 17...100) а, например 3 (1....14, 15, 16...100). В примере выше перед вызовом <b>GetPageNavStringEx</b> надо поставить:$rsElements-&gt;nPageWindow = 3;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/getpagenavstringex.php
	* @author Bitrix
	*/
	static public function GetPageNavStringEx(&$navComponentObject, $navigationTitle, $templateName = "", $showAlways=false, $parentComponent=null, $componentParams = array())
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		ob_start();

		$params = array_merge(
			array(
				"NAV_TITLE"=> $navigationTitle,
				"NAV_RESULT" => $this,
				"SHOW_ALWAYS" => $showAlways
			),
			$componentParams
		);

		$navComponentObject = $APPLICATION->IncludeComponent(
			"bitrix:system.pagenavigation",
			$templateName,
			$params,
			$parentComponent,
			array(
				"HIDE_ICONS" => "Y"
			)
		);

		$result = ob_get_contents();
		ob_end_clean();

		return $result;
	}

	public function SetUserFields($arUserFields)
	{
		if (is_array($arUserFields))
		{
			$this->arUserFields = $arUserFields;
			$this->usedUserFields = false;
		}
		else
		{
			$this->arUserFields = false;
			$this->usedUserFields = false;
		}
	}

	protected function AfterFetch(&$res)
	{
		global $USER_FIELD_MANAGER;

		if($this->arUserFields)
		{
			//Cache actual user fields on first fetch
			if ($this->usedUserFields === false)
			{
				$this->usedUserFields = array();
				foreach($this->arUserFields as $userField)
				{
					if (array_key_exists($userField['FIELD_NAME'], $res))
						$this->usedUserFields[] = $userField;
				}
			}
			// We need to call OnAfterFetch for each user field
			foreach($this->usedUserFields as $userField)
			{
				$name = $userField['FIELD_NAME'];
				if ($userField['MULTIPLE'] === 'Y')
				{
					if (substr($res[$name], 0, 1) !== 'a' && $res[$name] > 0)
					{
						$res[$name] = $USER_FIELD_MANAGER->LoadMultipleValues($userField, $res[$name]);
					}
					else
					{
						$res[$name] = unserialize($res[$name]);
					}
					$res[$name] = $USER_FIELD_MANAGER->OnAfterFetch($userField, $res[$name]);
				}
				else
				{
					$res[$name] = $USER_FIELD_MANAGER->OnAfterFetch($userField, $res[$name]);
				}
			}
		}

		if ($this->arReplacedAliases)
		{
			foreach($this->arReplacedAliases as $tech => $human)
			{
				$res[$human] = $res[$tech];
				unset($res[$tech]);
			}
		}
	}
}
