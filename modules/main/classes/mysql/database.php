<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/database.php");

/********************************************************************
*	MySQL database classes
********************************************************************/
class CDatabase extends CAllDatabase
{
	var $DBName;
	var $DBHost;
	var $DBLogin;
	var $DBPassword;
	var $bConnected;
	var $version;
	var $cntQuery;
	var $timeQuery;
	var $obSlave;

	public
		$escL = '`',
		$escR = '`';

	public
		$alias_length = 256;

	public function GetVersion()
	{
		if($this->version)
			return $this->version;

		$rs = $this->Query("SELECT VERSION() as R", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		if($ar = $rs->Fetch())
		{
			$version = trim($ar["R"]);
			preg_match("#[0-9]+\\.[0-9]+\\.[0-9]+#", $version, $arr);
			$version = $arr[0];
			$this->version = $version;
			return $version;
		}
		else
		{
			return false;
		}
	}

	
	/**
	 * <p>Открывает транзакцию. Для закрытия используйте <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/commit.php">Commit</a> или <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/rollback.php">Rollback</a>.</p> <p class="note">Работает для Oracle, MSSQL, MySQL (для типа таблиц InnoDB).</p>
	 *
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * if (strlen($save)&gt;0)
	 * {
	 *     if (CheckFields())
	 *     {
	 *         $DB-&gt;PrepareFields("b_form");
	 *         $arFields = array(
	 *             "TIMESTAMP_X"             =&gt; $DB-&gt;GetNowFunction(),
	 *             "NAME"                    =&gt; "'".trim($str_NAME)."'",
	 *             "VARNAME"                 =&gt; "'".trim($str_VARNAME)."'",
	 *             "C_SORT"                  =&gt; "'".intval($str_C_SORT)."'",
	 *             "FIRST_SITE_ID"           =&gt; "'".$DB-&gt;ForSql($FIRST_SITE_ID,2)."'",
	 *             "BUTTON"                  =&gt; "'".$str_BUTTON."'",
	 *             "DESCRIPTION"             =&gt; "'".$str_DESCRIPTION."'",
	 *             "DESCRIPTION_TYPE"        =&gt; "'".$str_DESCRIPTION_TYPE."'",
	 *             "SHOW_TEMPLATE"           =&gt; "'".trim($str_SHOW_TEMPLATE)."'",
	 *             "MAIL_EVENT_TYPE"         =&gt; "'".$DB-&gt;ForSql("FORM_FILLING_".$str_VARNAME,50)."'",
	 *             "SHOW_RESULT_TEMPLATE"    =&gt; "'".trim($str_SHOW_RESULT_TEMPLATE)."'",
	 *             "PRINT_RESULT_TEMPLATE"   =&gt; "'".trim($str_PRINT_RESULT_TEMPLATE)."'",
	 *             "EDIT_RESULT_TEMPLATE"    =&gt; "'".trim($str_EDIT_RESULT_TEMPLATE)."'",
	 *             "FILTER_RESULT_TEMPLATE"  =&gt; "'".trim($str_FILTER_RESULT_TEMPLATE)."'",
	 *             "TABLE_RESULT_TEMPLATE"   =&gt; "'".trim($str_TABLE_RESULT_TEMPLATE)."'",
	 *             "STAT_EVENT1"             =&gt; "'".trim($str_STAT_EVENT1)."'",
	 *             "STAT_EVENT2"             =&gt; "'".trim($str_STAT_EVENT2)."'",
	 *             "STAT_EVENT3"             =&gt; "'".trim($str_STAT_EVENT3)."'"
	 *             );
	 *         <b>$DB-&gt;StartTransaction();</b>
	 *         if ($ID&gt;0) 
	 *         {
	 *             $DB-&gt;Update("b_form", $arFields, "WHERE ID='".$ID."'", $err_mess.__LINE__);
	 *         }
	 *         else 
	 *         {
	 *             $ID = $DB-&gt;Insert("b_form", $arFields, $err_mess.__LINE__);
	 *             $new="Y";
	 *         }
	 *         $ID = intval($ID);
	 *         if (strlen($strError)&lt;=0) 
	 *         {
	 *             $DB-&gt;Commit();
	 *             if (strlen($save)&gt;0) LocalRedirect("form_list.php?lang=".LANGUAGE_ID);
	 *             elseif ($new=="Y") LocalRedirect("form_edit.php?lang=".LANGUAGE_ID."&amp;ID=".$ID);
	 *         }
	 *         else $DB-&gt;Rollback();
	 *     }
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/commit.php">CDatabase::Commit</a> </li>
	 * <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/rollback.php">CDatabase::Rollback</a> </li>
	 * </ul><a name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/starttransaction.php
	 * @author Bitrix
	 */
	public function StartTransaction()
	{
		$this->Query("START TRANSACTION");
	}

	
	/**
	 * <p>Завершает открытую транзакцию.</p> <p class="note">Работает для Oracle, MSSQL, MySQL (для типа таблиц InnoDB).</p>
	 *
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * if (strlen($save)&gt;0)
	 * {
	 *     if (CheckFields())
	 *     {
	 *         $DB-&gt;PrepareFields("b_form");
	 *         $arFields = array(
	 *             "TIMESTAMP_X"             =&gt; $DB-&gt;GetNowFunction(),
	 *             "NAME"                    =&gt; "'".trim($str_NAME)."'",
	 *             "VARNAME"                 =&gt; "'".trim($str_VARNAME)."'",
	 *             "C_SORT"                  =&gt; "'".intval($str_C_SORT)."'",
	 *             "FIRST_SITE_ID"           =&gt; "'".$DB-&gt;ForSql($FIRST_SITE_ID,2)."'",
	 *             "BUTTON"                  =&gt; "'".$str_BUTTON."'",
	 *             "DESCRIPTION"             =&gt; "'".$str_DESCRIPTION."'",
	 *             "DESCRIPTION_TYPE"        =&gt; "'".$str_DESCRIPTION_TYPE."'",
	 *             "SHOW_TEMPLATE"           =&gt; "'".trim($str_SHOW_TEMPLATE)."'",
	 *             "MAIL_EVENT_TYPE"         =&gt; "'".$DB-&gt;ForSql("FORM_FILLING_".$str_VARNAME,50)."'",
	 *             "SHOW_RESULT_TEMPLATE"    =&gt; "'".trim($str_SHOW_RESULT_TEMPLATE)."'",
	 *             "PRINT_RESULT_TEMPLATE"   =&gt; "'".trim($str_PRINT_RESULT_TEMPLATE)."'",
	 *             "EDIT_RESULT_TEMPLATE"    =&gt; "'".trim($str_EDIT_RESULT_TEMPLATE)."'",
	 *             "FILTER_RESULT_TEMPLATE"  =&gt; "'".trim($str_FILTER_RESULT_TEMPLATE)."'",
	 *             "TABLE_RESULT_TEMPLATE"   =&gt; "'".trim($str_TABLE_RESULT_TEMPLATE)."'",
	 *             "STAT_EVENT1"             =&gt; "'".trim($str_STAT_EVENT1)."'",
	 *             "STAT_EVENT2"             =&gt; "'".trim($str_STAT_EVENT2)."'",
	 *             "STAT_EVENT3"             =&gt; "'".trim($str_STAT_EVENT3)."'"
	 *             );
	 * 		$DB-&gt;StartTransaction();
	 *         if ($ID&gt;0) 
	 *         {
	 *             $DB-&gt;Update("b_form", $arFields, "WHERE ID='".$ID."'", $err_mess.__LINE__);
	 *         }
	 *         else 
	 *         {
	 *             $ID = $DB-&gt;Insert("b_form", $arFields, $err_mess.__LINE__);
	 *             $new="Y";
	 *         }
	 *         $ID = intval($ID);
	 *         if (strlen($strError)&lt;=0) 
	 *         {
	 *             &lt;b&gt;$DB-&gt;Commit&lt;/b&gt;();
	 *             if (strlen($save)&gt;0) LocalRedirect("form_list.php?lang=".LANGUAGE_ID);
	 *             elseif ($new=="Y") LocalRedirect("form_edit.php?lang=".LANGUAGE_ID."&amp;ID=".$ID);
	 *         }
	 *         else $DB-&gt;Rollback();
	 *     }
	 * }?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/rollback.php">CDatabase::Rollback</a> </li>
	 * <li> <a
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/starttransaction.php">CDatabase::StartTransaction</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/commit.php
	 * @author Bitrix
	 */
	public function Commit()
	{
		$this->Query("COMMIT", true);
	}

	
	/**
	 * <p>Откатывает назад изменения произведенные открытой и незавершенной транзакцией.</p> <p class="note">Работает для Oracle, MSSQL, MySQL (для типа таблиц InnoDB).</p>
	 *
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * if (strlen($save)&gt;0)
	 * {
	 *     if (CheckFields())
	 *     {
	 *         $DB-&gt;PrepareFields("b_form");
	 *         $arFields = array(
	 *             "TIMESTAMP_X"             =&gt; $DB-&gt;GetNowFunction(),
	 *             "NAME"                    =&gt; "'".trim($str_NAME)."'",
	 *             "VARNAME"                 =&gt; "'".trim($str_VARNAME)."'",
	 *             "C_SORT"                  =&gt; "'".intval($str_C_SORT)."'",
	 *             "FIRST_SITE_ID"           =&gt; "'".$DB-&gt;ForSql($FIRST_SITE_ID,2)."'",
	 *             "BUTTON"                  =&gt; "'".$str_BUTTON."'",
	 *             "DESCRIPTION"             =&gt; "'".$str_DESCRIPTION."'",
	 *             "DESCRIPTION_TYPE"        =&gt; "'".$str_DESCRIPTION_TYPE."'",
	 *             "SHOW_TEMPLATE"           =&gt; "'".trim($str_SHOW_TEMPLATE)."'",
	 *             "MAIL_EVENT_TYPE"         =&gt; "'".$DB-&gt;ForSql("FORM_FILLING_".$str_VARNAME,50)."'",
	 *             "SHOW_RESULT_TEMPLATE"    =&gt; "'".trim($str_SHOW_RESULT_TEMPLATE)."'",
	 *             "PRINT_RESULT_TEMPLATE"   =&gt; "'".trim($str_PRINT_RESULT_TEMPLATE)."'",
	 *             "EDIT_RESULT_TEMPLATE"    =&gt; "'".trim($str_EDIT_RESULT_TEMPLATE)."'",
	 *             "FILTER_RESULT_TEMPLATE"  =&gt; "'".trim($str_FILTER_RESULT_TEMPLATE)."'",
	 *             "TABLE_RESULT_TEMPLATE"   =&gt; "'".trim($str_TABLE_RESULT_TEMPLATE)."'",
	 *             "STAT_EVENT1"             =&gt; "'".trim($str_STAT_EVENT1)."'",
	 *             "STAT_EVENT2"             =&gt; "'".trim($str_STAT_EVENT2)."'",
	 *             "STAT_EVENT3"             =&gt; "'".trim($str_STAT_EVENT3)."'"
	 *             );
	 * 		$DB-&gt;StartTransaction();
	 *         if ($ID&gt;0) 
	 *         {
	 *             $DB-&gt;Update("b_form", $arFields, "WHERE ID='".$ID."'", $err_mess.__LINE__);
	 *         }
	 *         else 
	 *         {
	 *             $ID = $DB-&gt;Insert("b_form", $arFields, $err_mess.__LINE__);
	 *             $new="Y";
	 *         }
	 *         $ID = intval($ID);
	 *         if (strlen($strError)&lt;=0) 
	 *         {
	 *             $DB-&gt;Commit();
	 *             if (strlen($save)&gt;0) LocalRedirect("form_list.php?lang=".LANGUAGE_ID);
	 *             elseif ($new=="Y") LocalRedirect("form_edit.php?lang=".LANGUAGE_ID."&amp;ID=".$ID);
	 *         }
	 *         else <b>$DB-&gt;Rollback();</b>
	 *     }
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/commit.php">CDatabase::Commit</a> </li>
	 * <li> <a
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/starttransaction.php">CDatabase::StartTransaction</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/rollback.php
	 * @author Bitrix
	 */
	public function Rollback()
	{
		$this->Query("ROLLBACK", true);
	}

	//Connect to database
	
	/**
	 * <p>Открывает соединение с базой данных. Функция возвращает "true" при успешном открытии соединения или "false" при ошибке.</p> <p> </p>
	 *
	 *
	 *
	 *
	 * @param string $host  Сервер (хост) базы данных.
	 *
	 *
	 *
	 * @param string $db  Имя базы данных.
	 *
	 *
	 *
	 * @param string $login  Логин.
	 *
	 *
	 *
	 * @param string $password  Пароль.
	 *
	 *
	 *
	 * @return bool 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
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
	 *
	 * <h4>See Also</h4> 
	 * <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/disconnect.php">CDatabase::Disconnect</a>
	 * </li></ul><a name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/connect.php
	 * @author Bitrix
	 */
	public function Connect($DBHost, $DBName, $DBLogin, $DBPassword)
	{
		$this->type="MYSQL";
		$this->DBHost = $DBHost;
		$this->DBName = $DBName;
		$this->DBLogin = $DBLogin;
		$this->DBPassword = $DBPassword;
		$this->bConnected = false;

		if (!defined("DBPersistent"))
			// define("DBPersistent",true);

		if(defined("DELAY_DB_CONNECT") && DELAY_DB_CONNECT===true)
			return true;
		else
			return $this->DoConnect();
	}

	public function DoConnect()
	{
		if($this->bConnected)
			return true;
		$this->bConnected = true;

		if (DBPersistent && !$this->bNodeConnection)
			$this->db_Conn = @mysql_pconnect($this->DBHost, $this->DBLogin, $this->DBPassword);
		else
			$this->db_Conn = @mysql_connect($this->DBHost, $this->DBLogin, $this->DBPassword, true);

		if(!$this->db_Conn)
		{
			$s = (DBPersistent && !$this->bNodeConnection? "mysql_pconnect" : "mysql_connect");
			if($this->debug || (@session_start() && $_SESSION["SESS_AUTH"]["ADMIN"]))
				echo "<br><font color=#ff0000>Error! ".$s."('-', '-', '-')</font><br>".mysql_error()."<br>";

			SendError("Error! ".$s."('-', '-', '-')\n".mysql_error()."\n");
			return false;
		}

		if(!mysql_select_db($this->DBName, $this->db_Conn))
		{
			if($this->debug || (@session_start() && $_SESSION["SESS_AUTH"]["ADMIN"]))
				echo "<br><font color=#ff0000>Error! mysql_select_db(".$this->DBName.")</font><br>".mysql_error($this->db_Conn)."<br>";

			SendError("Error! mysql_select_db(".$this->DBName.")\n".mysql_error($this->db_Conn)."\n");
			return false;
		}

		$this->cntQuery = 0;
		$this->timeQuery = 0;
		$this->arQueryDebug = array();

		/** @noinspection PhpUnusedLocalVariableInspection */
		global $DB, $USER, $APPLICATION;
		if(file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/after_connect.php"))
			include($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/after_connect.php");

		return true;
	}

	//This function executes query against database
	public function Query($strSql, $bIgnoreErrors=false, $error_position="", $arOptions=array())
	{
		global $DB;

		$this->DoConnect();
		$this->db_Error="";

		if($this->DebugToFile || $DB->ShowSqlStat)
			$start_time = microtime(true);

		//We track queries for DML statements
		//and when there is no one we can choose
		//to run query against master connection
		//or replicated one
		static $bSelectOnly = true;

		if($this->bModuleConnection)
		{
			//In case of dedicated module database
			//were is nothing to do
		}
		elseif($DB->bMasterOnly > 0)
		{
			//We requested to process all queries
			//by master connection
		}
		elseif(isset($arOptions["fixed_connection"]))
		{
			//We requested to process this query
			//by current connection
		}
		elseif($this->bNodeConnection)
		{
			//It is node so nothing to do
		}
		else
		{
			$bSelect = preg_match('/^\s*(select|show)/i', $strSql) && !preg_match('/get_lock/i', $strSql);
			if(!$bSelect && !isset($arOptions["ignore_dml"]))
				$bSelectOnly = false;

			if($bSelect && $bSelectOnly)
			{
				if(!isset($this->obSlave))
				{
					$this->StartUsingMasterOnly(); //This is bootstrap code
					$this->obSlave = CDatabase::SlaveConnection();
					$this->StopUsingMasterOnly();
				}

				if(is_object($this->obSlave))
					return $this->obSlave->Query($strSql, $bIgnoreErrors, $error_position, $arOptions);
			}
		}

		$result = @mysql_query($strSql, $this->db_Conn);

		if($this->DebugToFile || $DB->ShowSqlStat)
		{
			/** @noinspection PhpUndefinedVariableInspection */
			$exec_time = round(microtime(true) - $start_time, 10);

			if($DB->ShowSqlStat)
				$DB->addDebugQuery($strSql, $exec_time);

			if($this->DebugToFile)
			{
				$fp = fopen($_SERVER["DOCUMENT_ROOT"]."/mysql_debug.sql","ab+");
				$str = "TIME: ".$exec_time." SESSION: ".session_id()."  CONN: ".$this->db_Conn."\n";
				$str .= $strSql."\n\n";
				$str .= "----------------------------------------------------\n\n";
				fputs($fp, $str);
				@fclose($fp);
			}
		}

		if(!$result)
		{
			$this->db_Error = mysql_error($this->db_Conn);
			$this->db_ErrorSQL = $strSql;
			if(!$bIgnoreErrors)
			{
				AddMessage2Log($error_position." MySql Query Error: ".$strSql." [".$this->db_Error."]", "main");
				if ($this->DebugToFile)
				{
					$fp = fopen($_SERVER["DOCUMENT_ROOT"]."/mysql_debug.sql","ab+");
					fputs($fp,"SESSION: ".session_id()." ERROR: ".$this->db_Error."\n\n----------------------------------------------------\n\n");
					@fclose($fp);
				}

				if($this->debug || (@session_start() && $_SESSION["SESS_AUTH"]["ADMIN"]))
					echo $error_position."<br><font color=#ff0000>MySQL Query Error: ".htmlspecialcharsbx($strSql)."</font>[".htmlspecialcharsbx($this->db_Error)."]<br>";

				$error_position = preg_replace("#<br[^>]*>#i","\n",$error_position);
				SendError($error_position."\nMySQL Query Error:\n".$strSql." \n [".$this->db_Error."]\n---------------\n\n");

				if(file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/dbquery_error.php"))
					include($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/dbquery_error.php");
				elseif(file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/dbquery_error.php"))
					include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/dbquery_error.php");
				else
					die("MySQL Query Error!");

				die();
			}
			return false;
		}

		$res = new CDBResult($result);
		$res->DB = $this;
		if($DB->ShowSqlStat)
			$res->SqlTraceIndex = count($DB->arQueryDebug) - 1;
		return $res;
	}

	public function QueryLong($strSql, $bIgnoreErrors = false)
	{
		return $this->Query($strSql, $bIgnoreErrors);
	}

	
	/**
	 * <p>Функция возвращает строку "SYSDATE" для Oracle версии и "now()" для MySQL.</p> <p> </p>
	 *
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $DB-&gt;Update("my_table", Array("TIME_CHANGE" =&gt; <b>$DB-&gt;CurrentTimeFunction</b>()), 
	 * "WHERE ID=45", $err_mess.__LINE__);
	 * ?&gt;
	 * &lt;?
	 * $strSql = "
	 *     UPDATE my_table SET 
	 *         TIME_CHANGE=".<b>$DB-&gt;CurrentTimeFunction</b>()." 
	 *     WHERE ID=45
	 *     ";
	 * $Query($strSql, false, "FILE: ".__FILE__."&lt;br&gt;LINE: ".__LINE__);
	 * ?&gt;
	 * &lt;?
	 * $strSql = "
	 *     SELECT 
	 *         ID
	 *     FROM 
	 *         my_table
	 *     WHERE 
	 *         TIME_CHANGE &lt;= ".<b>$DB-&gt;CurrentTimeFunction</b>()."
	 *     ";
	 * $rs = $DB-&gt;Query($strSql, false, $err_mess.__LINE__);
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/currentdatefunction.php">CDatabase::CurrentDateFunction</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/date/index.php">Функции для работы
	 * с датой и временем</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/currenttimefunction.php
	 * @author Bitrix
	 */
	public static function CurrentTimeFunction()
	{
		return "now()";
	}

	
	/**
	 * <p>Функция возвращает SQL функцию, которая в свою очередь возвращающую текущую дату. А именно: "CURRENT_DATE" для MySQL и "TRUNC(SYSDATE)" для Oracle.</p> <p> </p>
	 *
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $strSql = "UPDATE my_table SET DATE_CHANGE=".<b>$DB-&gt;CurrentDateFunction</b>()." WHERE ID=45";
	 * $Query($strSql, false, "FILE: ".__FILE__."&lt;br&gt;LINE: ".__LINE__);
	 * ?&gt;
	 * &lt;?
	 * $strSql = "
	 *     SELECT 
	 *         ID
	 *     FROM 
	 *         my_table
	 *     WHERE 
	 *         DATE_CREATE &lt;= ".<b>$DB-&gt;CurrentDateFunction</b>()."
	 *     ";
	 * $rs = $DB-&gt;Query($strSql, false, $err_mess.__LINE__);
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/currenttimefunction.php">CDatabase::CurrentTimeFunction</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/date/index.php">Функции для работы
	 * с датой и временем</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/currentdatefunction.php
	 * @author Bitrix
	 */
	public static function CurrentDateFunction()
	{
		return "CURRENT_DATE";
	}

	public static function DateFormatToDB($format, $field = false)
	{
//		static $search  = array("YYYY", "MM", "DD", "HH", "MI", "SS");
//		static $replace = array("%Y", "%m", "%d", "%H", "%i", "%s");

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

		foreach ($search as $k=>$v)
		{
			$format = str_replace($v, $replace[$k], $format);
		}
		if (strpos($format, '%H') === false)
		{
			$format = str_replace("H", "%h", $format);
		}
		if (strpos($format, '%M') === false)
		{
			$format = str_replace("M", "%b", $format);
		}

		if($field === false)
		{
			return $format;
		}
		else
		{
			return "DATE_FORMAT(".$field.", '".$format."')";
		}
	}

	
	/**
	 * <p>Возвращает для MySQL строку DATE_FORMAT, для Oracle - TO_CHAR с нужными параметрами.<br> Форматы даты устанавливается в настройках языка или сайта.</p> <p> </p>
	 *
	 *
	 *
	 *
	 * @param string $value  Значение даты для формата текущего сайта.
	 *
	 *
	 *
	 * @param string $type = "FULL" Тип формата даты: "FULL" - для даты со временем, "SHORT" - для даты (без
	 * времени) <br>Необязательный. По умолчанию "FULL".
	 *
	 *
	 *
	 * @param string $site = false Код сайта для публичной части, либо код языка для
	 * административной части.<br>Необязательный. По умолчанию текущий.
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
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
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/chartodatefunction.php">CDatabase::CharToDateFunction</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/date/index.php">Функции для работы
	 * с датой и временем</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/datetocharfunction.php
	 * @author Bitrix
	 */
	public function DateToCharFunction($strFieldName, $strType="FULL", $lang=false, $bSearchInSitesOnly=false)
	{
		static $CACHE=array();
		$id = $strType.",".$lang.",".$bSearchInSitesOnly;
		if(!array_key_exists($id,$CACHE))
			$CACHE[$id] = $this->DateFormatToDB(CLang::GetDateFormat($strType, $lang, $bSearchInSitesOnly));

		$sFieldExpr = $strFieldName;

		//time zone
		if($strType == "FULL" && CTimeZone::Enabled())
		{
			static $diff = false;
			if($diff === false)
				$diff = CTimeZone::GetOffset();

			if($diff <> 0)
				$sFieldExpr = "DATE_ADD(".$strFieldName.", INTERVAL ".$diff." SECOND)";
		}

		return "DATE_FORMAT(".$sFieldExpr.", '".$CACHE[$id]."')";
	}

	
	/**
	 * <p>Возвращает для MySQL значение сконвертированное в формат YYYY-MM-DD [HH:MI:SS], для Oracle - функция вернет строку TO_DATE с нужными параметрами.<br>Форматы даты устанавливается в настройках языка, либо настройках сайта.</p>
	 *
	 *
	 *
	 *
	 * @param string $value  Если функция вызывается в публичной части сайта, то это - значение
	 * даты для формата текущего сайта. Если функция вызывается в
	 * административной части, то это - значение даты для формата
	 * текущего языка.
	 *
	 *
	 *
	 * @param string $type = "FULL" Тип формата даты: "FULL" - для даты со временем, "SHORT" - для даты (без
	 * времени) <br>Необязательный. По умолчанию "FULL".
	 *
	 *
	 *
	 * @param string $site = false Код сайта для публичной части, либо код языка для
	 * административной части.<br>Необязательный. По умолчанию текущий.
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
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
	public static function CharToDateFunction($strValue, $strType="FULL", $lang=false)
	{
		$sFieldExpr = "'".CDatabase::FormatDate($strValue, CLang::GetDateFormat($strType, $lang), ($strType=="SHORT"? "Y-M-D":"Y-M-D H:I:S"))."'";

		//time zone
		if($strType == "FULL" && CTimeZone::Enabled())
		{
			static $diff = false;
			if($diff === false)
				$diff = CTimeZone::GetOffset();

			if($diff <> 0)
				$sFieldExpr = "DATE_ADD(".$sFieldExpr.", INTERVAL -(".$diff.") SECOND)";
		}

		return $sFieldExpr;
	}

	
	/**
	 * <p>Позволяет выбирать дату в формате UNIX_TIMESTAMP без обращения к <a href="http://dev.1c-bitrix.ru/api_help/main/functions/date/maketimestamp.php">MakeTimeStamp</a> (с версии main 12.5.12).</p>
	 *
	 *
	 *
	 *
	 * @param TABLE_FIEL $D  Поле в БД которое требуется перевести из формата DATE TIME в формат
	 * TIMESTAMP.
	 *
	 *
	 *
	 * @return mixed <p>Возвращает валидный <b>timestamp</b>.</p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $strSql = "
	 *     SELECT 
	 * ID, 
	 * ".$DB-&gt; DatetimeToTimestampFunction("DATE_CREATE")." DATE_CREATE
	 * FROM 
	 * my_table
	 * ";
	 * $rs = $DB-&gt;Query($strSql, false, $err_mess.__LINE__);
	 * ?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/datetimetotimestampfunction.php
	 * @author Bitrix
	 */
	public static function DatetimeToTimestampFunction($fieldName)
	{
		$timeZone = "";
		if (CTimeZone::Enabled())
		{
			static $diff = false;
			if($diff === false)
				$diff = CTimeZone::GetOffset();

			if($diff <> 0)
				$timeZone = $diff > 0? "+".$diff: $diff;
		}
		return "UNIX_TIMESTAMP(".$fieldName.")".$timeZone;
	}

	public static function DatetimeToDateFunction($strValue)
	{
		return 'DATE('.$strValue.')';
	}

	//  1 if date1 > date2
	//  0 if date1 = date2
	// -1 if date1 < date2
	
	/**
	 * <p>Сравнивает между собой две даты. Возвращаемые значения:</p> <table class="tnormal"> <tr> <th width="50%">Условие</th> <th width="50%">Возвращаемое значение</th> </tr> <tr> <td align="center" nowrap> <i>date1</i> &gt; <i>date2</i> </td> <td align="center">1</td> </tr> <tr> <td align="center" nowrap> <i>date1</i> &lt; <i>date2</i> </td> <td align="center">-1</td> </tr> <tr> <td align="center" nowrap> <i>date1</i> = <i>date2</i> </td> <td align="center">0</td> </tr> </table>
	 *
	 *
	 *
	 *
	 * @param string $date1  1
	 *
	 *
	 *
	 * @param string $date2  -1
	 *
	 *
	 *
	 * @return int <h4>Параметры функции</h4><table class="tnormal" width="100%"> <tr> <th
	 * width="30%">Параметр</th> <th>Описание</th> </tr> <tr> <td><i>date1</i></td> <td>Первая дата
	 * для сравнения.</td> </tr> <tr> <td><i>date2</i></td> <td>Вторая дата для
	 * сравнения.</td> </tr> </table>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // зададим дату 1
	 * $date1 = "01.01.2005";
	 * 
	 * // зададим дату 2
	 * $date2 = "01.01.2006";
	 * 
	 * $result = <b>$DB-&gt;CompareDates</b>($date2, $date1);
	 * 
	 * if ($result==1) echo $date1." &gt; ".$date2;
	 * elseif ($result==-1) echo $date1." &lt; ".$date2;
	 * elseif ($result==0) echo $date1." = ".$date2;
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/date/index.php">Функции для работы с
	 * датой и временем</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ru/api_help/main/functions/filter/checkfilterdates.php">CheckFilterDates</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/comparedates.php
	 * @author Bitrix
	 */
	public function CompareDates($date1, $date2)
	{
		$s_date1 = $this->CharToDateFunction($date1);
		$s_date2 = $this->CharToDateFunction($date2);
		$strSql = "
			SELECT
				if($s_date1 > $s_date2, 1,
					if ($s_date1 < $s_date2, -1,
						if ($s_date1 = $s_date2, 0, 'x')
				)) as RES
			";
		$z = $this->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		$zr = $z->Fetch();
		return $zr["RES"];
	}

	
	/**
	 * <p>Функция возвращает ID последней вставленной записи.</p> <p> </p>
	 *
	 *
	 *
	 *
	 * @return int 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * function AddResultAnswer($arFields)
	 * {
	 * 	$err_mess = (CForm::err_mess())."&lt;br&gt;Function: AddResultAnswer&lt;br&gt;Line: ";
	 * 	global $DB;
	 * 	$arInsert = $DB-&gt;PrepareInsert("b_form_result_answer", $arFields, "form");
	 * 	$strSql = "INSERT INTO b_form_result_answer (".$arInsert[0].") VALUES (".$arInsert[1].")";
	 * 	$DB-&gt;Query($strSql, false, $err_mess.__LINE__);
	 * 	return intval(<b>$DB-&gt;LastID()</b>);
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul><li><a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/query.php">CDatabase::Query</a></li></ul><a
	 * name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/lastid.php
	 * @author Bitrix
	 */
	public function LastID()
	{
		$this->DoConnect();
		return mysql_insert_id($this->db_Conn);
	}

	//Closes database connection
	
	/**
	 * <p>Закрывает соединение с базой данных.</p> <p> </p>
	 *
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * <b>$DB-&gt;Disconnect();</b>
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/connect.php">CDatabase::Connect</a>
	 * </li></ul><a name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/disconnect.php
	 * @author Bitrix
	 */
	public function Disconnect()
	{
		if(!DBPersistent && $this->bConnected)
		{
			$this->bConnected = false;
			mysql_close($this->db_Conn);
		}

		foreach(self::$arNodes as $arNode)
		{
			if(is_array($arNode) && array_key_exists("DB", $arNode))
			{
				mysql_close($arNode["DB"]->db_Conn);
				unset($arNode["DB"]);
			}
		}
	}

	
	/**
	 * <p> Функция подготавливает глобальные переменные, соответствующие именам полей таблицы <i>table</i> для записи в БД.</p> <p>Создает глобальные переменные ${<i>prefix</i>.<i>имя_поля</i>.<i>postfix</i>} и устанавливает их значениями глобальных переменных, соответствующих именам полей из таблицы <i>table</i>, предварительно преобразовав их в зависимости от типа поля. <br><br>Например, для поля типа <b>int</b> будет выполнено: </p> <pre>${<i>prefix</i>.<i>имя_поля</i>.<i>postfix</i>} = intval(${<i>имя_поля</i>});</pre> Для поля типа <b>varchar</b>:<br><br><pre>${<i>prefix</i>.<i>имя_поля</i>.<i>postfix</i>} = CDatabase::ForSql(${<i>имя_поля</i>}, <i>размер_поля</i>);</pre> <p class="note">Функция работает с переменными из глобальной области видимости, это необходимо учитывать при создании основных файлов компонентов.</p>
	 *
	 *
	 *
	 *
	 * @param string $table  Имя таблицы.
	 *
	 *
	 *
	 * @param string $prefix = "str_" Префикс переменных. <br>Необязательный. По умолчанию "str_".
	 *
	 *
	 *
	 * @param string $postfix = "" Постфикс переменных. <br>Необязательный. По умолчанию пустая
	 * строка.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * if (strlen($save)&gt;0)
	 * {
	 *     if (CheckFields())
	 *     {
	 *         <b>$DB-&gt;PrepareFields</b>("b_form");
	 *         $arFields = array(
	 *             "TIMESTAMP_X"             =&gt; $DB-&gt;GetNowFunction(),
	 *             "NAME"                    =&gt; "'".trim($str_NAME)."'",
	 *             "VARNAME"                 =&gt; "'".trim($str_VARNAME)."'",
	 *             "C_SORT"                  =&gt; "'".intval($str_C_SORT)."'",
	 *             "FIRST_SITE_ID"           =&gt; "'".$DB-&gt;ForSql($FIRST_SITE_ID,2)."'",
	 *             "BUTTON"                  =&gt; "'".$str_BUTTON."'",
	 *             "DESCRIPTION"             =&gt; "'".$str_DESCRIPTION."'",
	 *             "DESCRIPTION_TYPE"        =&gt; "'".$str_DESCRIPTION_TYPE."'",
	 *             "SHOW_TEMPLATE"           =&gt; "'".trim($str_SHOW_TEMPLATE)."'",
	 *             "MAIL_EVENT_TYPE"         =&gt; "'".$DB-&gt;ForSql("FORM_FILLING_".$str_VARNAME,50)."'",
	 *             "SHOW_RESULT_TEMPLATE"    =&gt; "'".trim($str_SHOW_RESULT_TEMPLATE)."'",
	 *             "PRINT_RESULT_TEMPLATE"   =&gt; "'".trim($str_PRINT_RESULT_TEMPLATE)."'",
	 *             "EDIT_RESULT_TEMPLATE"    =&gt; "'".trim($str_EDIT_RESULT_TEMPLATE)."'",
	 *             "FILTER_RESULT_TEMPLATE"  =&gt; "'".trim($str_FILTER_RESULT_TEMPLATE)."'",
	 *             "TABLE_RESULT_TEMPLATE"   =&gt; "'".trim($str_TABLE_RESULT_TEMPLATE)."'",
	 *             "STAT_EVENT1"             =&gt; "'".trim($str_STAT_EVENT1)."'",
	 *             "STAT_EVENT2"             =&gt; "'".trim($str_STAT_EVENT2)."'",
	 *             "STAT_EVENT3"             =&gt; "'".trim($str_STAT_EVENT3)."'"
	 *             );
	 *         if ($ID&gt;0) 
	 *         {
	 *             $DB-&gt;Update("b_form", $arFields, "WHERE ID='".$ID."'", $err_mess.__LINE__);
	 *         }
	 *         else 
	 *         {
	 *             $ID = $DB-&gt;Insert("b_form", $arFields, $err_mess.__LINE__);
	 *             $new="Y";
	 *         }
	 *         $ID = intval($ID);
	 *         if (strlen($strError)&lt;=0) 
	 *         {
	 *             if (strlen($save)&gt;0) LocalRedirect("form_list.php?lang=".LANGUAGE_ID);
	 *             elseif ($new=="Y") LocalRedirect("form_edit.php?lang=".LANGUAGE_ID."&amp;ID=".$ID);
	 *         }
	 *     }
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul><li> <a
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/inittablevarsforedit.php">CDatabase::InitTableVarsForEdit</a>
	 * </li></ul><a name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/preparefields.php
	 * @author Bitrix
	 */
	public function PrepareFields($strTableName, $strPrefix = "str_", $strSuffix = "")
	{
		$arColumns = $this->GetTableFields($strTableName);
		foreach($arColumns as $arColumn)
		{
			$column = $arColumn["NAME"];
			$type = $arColumn["TYPE"];
			global $$column;
			$var = $strPrefix.$column.$strSuffix;
			global $$var;
			switch ($type)
			{
				case "int":
					$$var = IntVal($$column);
					break;
				case "real":
					$$var = DoubleVal($$column);
					break;
				default:
					$$var = $this->ForSql($$column);
			}
		}
	}

	
	/**
	 * <p>Функция подготавливает массив из двух строк для SQL запроса вставки записи в базу данных. Возвращает массив из двух элементов, где элемент с ключом 0 строка список полей вида "имя поля1, имя поля2[, ...]", а элемент с ключом 1 строка значений вида "значение1, значение2[, ...]". При этом функция сама преобразует все значение в SQL вид в зависимости от типа поля. </p> <p></p>
	 *
	 *
	 *
	 *
	 * @param string $table  Имя таблицы для вставки записи.
	 *
	 *
	 *
	 * @param array $fields  Массив значений полей в формате "имя поля1"=&gt;"значение1", "имя
	 * поля2"=&gt;"значение2" [, ...]. <br> Если необходимо вставить значение NULL,
	 * то значение должно быть равно false.
	 *
	 *
	 *
	 * @param string $dir = "" Не используется.
	 *
	 *
	 *
	 * @param string $site = false Код сайта для публичной части, либо код языка для
	 * административной части. Используется для определения формата
	 * даты, для вставки полей типа date или datetime. <br> Необязательный. По
	 * умолчанию текущий.
	 *
	 *
	 *
	 * @return array 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
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
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/prepareupdate.php">CDatabase::PrepareUpdate</a> </li>
	 * </ul><a name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/prepareinsert.php
	 * @author Bitrix
	 */
	public function PrepareInsert($strTableName, $arFields, $strFileDir="", $lang=false)
	{
		$strInsert1 = "";
		$strInsert2 = "";

		$arColumns = $this->GetTableFields($strTableName);
		foreach($arColumns as $strColumnName => $arColumnInfo)
		{
			$type = $arColumnInfo["TYPE"];
			if(isset($arFields[$strColumnName]))
			{
				$value = $arFields[$strColumnName];

				if($value === false)
				{
					$strInsert1 .= ", `".$strColumnName."`";
					$strInsert2 .= ",  NULL ";
				}
				else
				{
					$strInsert1 .= ", `".$strColumnName."`";
					switch ($type)
					{
						case "datetime":
							if(strlen($value)<=0)
								$strInsert2 .= ", NULL ";
							else
								$strInsert2 .= ", ".CDatabase::CharToDateFunction($value, "FULL", $lang);
							break;
						case "date":
							if(strlen($value)<=0)
								$strInsert2 .= ", NULL ";
							else
								$strInsert2 .= ", ".CDatabase::CharToDateFunction($value, "SHORT", $lang);
							break;
						case "int":
							$strInsert2 .= ", '".IntVal($value)."'";
							break;
						case "real":
							$strInsert2 .= ", '".DoubleVal($value)."'";
							break;
						default:
							$strInsert2 .= ", '".$this->ForSql($value)."'";
					}
				}
			}
			elseif(array_key_exists("~".$strColumnName, $arFields))
			{
				$strInsert1 .= ", `".$strColumnName."`";
				$strInsert2 .= ", ".$arFields["~".$strColumnName];
			}
		}

		if($strInsert1!="")
		{
			$strInsert1 = substr($strInsert1, 2);
			$strInsert2 = substr($strInsert2, 2);
		}
		return array($strInsert1, $strInsert2);
	}

	
	/**
	 * <p>Функция подготавливает строку для SQL запроса изменения записи в базе данных. Возвращает строку вида "имя поля1 = значение1", имя поля2 = значение2[, ...]". При этом функция сама преобразует все значение в SQL вид в зависимости от типа поля.</p>
	 *
	 *
	 *
	 *
	 * @param string $table  Имя таблицы.
	 *
	 *
	 *
	 * @param array $fields  Массив значений полей в формате "имя поля1"=&gt;"значение1", "имя
	 * поля2"=&gt;"значение2" [, ...]. <br> Если необходимо изменить значение на
	 * NULL, то значение в массиве должно быть равно false.
	 *
	 *
	 *
	 * @param string $dir = "" Не используется.
	 *
	 *
	 *
	 * @param string $site = false Код сайта для публичной части, либо код языка для
	 * административной части. Используется для определения формата
	 * даты, для вставки полей типа date или datetime. <br> Необязательный. По
	 * умолчанию текущий.
	 *
	 *
	 *
	 * @return array 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
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
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/prepareinsert.php">CDatabase::PrepareInsert</a> </li>
	 * </ul><a name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/prepareupdate.php
	 * @author Bitrix
	 */
	public function PrepareUpdate($strTableName, $arFields, $strFileDir="", $lang = false, $strTableAlias = "")
	{
		$arBinds = array();
		return $this->PrepareUpdateBind($strTableName, $arFields, $strFileDir, $lang, $arBinds, $strTableAlias);
	}

	public function PrepareUpdateBind($strTableName, $arFields, $strFileDir, $lang, &$arBinds, $strTableAlias = "")
	{
		$arBinds = array();
		if ($strTableAlias != "")
			$strTableAlias .= ".";
		$strUpdate = "";
		$arColumns = $this->GetTableFields($strTableName);
		foreach($arColumns as $strColumnName => $arColumnInfo)
		{
			$type = $arColumnInfo["TYPE"];
			if(isset($arFields[$strColumnName]))
			{
				$value = $arFields[$strColumnName];
				if($value === false)
				{
					$strUpdate .= ", $strTableAlias`".$strColumnName."` = NULL";
				}
				else
				{
					switch ($type)
					{
						case "int":
							$value = IntVal($value);
							break;
						case "real":
							$value = DoubleVal($value);
							break;
						case "datetime":
							if(strlen($value)<=0)
								$value = "NULL";
							else
								$value = CDatabase::CharToDateFunction($value, "FULL", $lang);
							break;
						case "date":
							if(strlen($value)<=0)
								$value = "NULL";
							else
								$value = CDatabase::CharToDateFunction($value, "SHORT", $lang);
							break;
						default:
							$value = "'".$this->ForSql($value)."'";
					}
					$strUpdate .= ", $strTableAlias`".$strColumnName."` = ".$value;
				}
			}
			elseif(is_set($arFields, "~".$strColumnName))
			{
				$strUpdate .= ", $strTableAlias`".$strColumnName."` = ".$arFields["~".$strColumnName];
			}
		}

		if($strUpdate!="")
			$strUpdate = substr($strUpdate, 2);

		return $strUpdate;
	}

	
	/**
	 * <p>Функция вставляет запись в таблицу <i>table</i> с значениями полей <i>fields</i>. Необходимые условия использования данной функции: </p> <ul> <li>Необходимо наличие поля "ID" в таблице, представляющее из себя Primary Key для данной таблицы. </li> <li>Для MySQL поле "ID" должно быть auto increment (если при вызове функции явно не задается параметр exist_id). </li> <li>Для Oracle обязательно наличие sequence (последовательности) с именем вида "SQ_.<i>table</i>". </li> </ul> Возвращает ID вставленной записи или false в случае ошибки. <p><b>Примечание</b>. Если необходимо вставить запись с определенным ID и при этом указать его в параметре fields, то функция возвращает 0, при этом запись вставляется. Если необходимо, чтобы функция вернула ID, который вы указывали, то его необходимо указывать в параметре exist_id.</p> <p class="note">Примечание: все значения полей должны быть подготовлены для SQL запроса, например, при помощи функции <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/forsql.php">CDatabase::ForSql</a>.</p> <p> </p>
	 *
	 *
	 *
	 *
	 * @param string $table  Название таблицы.
	 *
	 *
	 *
	 * @param array $fields  Массив вида значений полей "поле"=&gt;"значение",...
	 *
	 *
	 *
	 * @param string $error_position = "" Строка идентифицирующая позицию в коде, откуда была вызвана
	 * данная функция CDatabase::Insert. Если в SQL запросе будет ошибка и если в
	 * файле <b>/bitrix/php_interface/dbconn.php</b> установлена переменная <b>$DBDebug=true;</b>,
	 * то на экране будет выведена данная информация и сам SQL запрос.
	 *
	 *
	 *
	 * @param bool $debug = false Если значение - "true", то на экран будет выведен текст SQL запроса.
	 *
	 *
	 *
	 * @param int $exist_id = "" Если данный параметр задан в виде положительного числа, то при
	 * вставке записи в таблицу, будет добавлено поле с именем "ID" и
	 * значением <i>exist_id</i>. Если данный параметр явно не задан, то для Oracle
	 * таблицы будет добавлено поле "ID", со значением SQ_<i>table</i>.nextval().
	 *
	 *
	 *
	 * @param bool $ignore_errors = false если значение "true", то в случае ошибки возникшей в результате
	 * выполнения SQL запроса, она будет проигнорирована и работа скрипта
	 * продолжена.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * if (strlen($save)&gt;0)
	 * {
	 *     if (CheckFields())
	 *     {
	 *         $DB-&gt;PrepareFields("b_form");
	 *         $arFields = array(
	 *             "TIMESTAMP_X"             =&gt; $DB-&gt;GetNowFunction(),
	 *             "NAME"                    =&gt; "'".trim($str_NAME)."'",
	 *             "VARNAME"                 =&gt; "'".trim($str_VARNAME)."'",
	 *             "C_SORT"                  =&gt; "'".intval($str_C_SORT)."'",
	 *             "FIRST_SITE_ID"           =&gt; "'".$DB-&gt;ForSql($FIRST_SITE_ID,2)."'",
	 *             "BUTTON"                  =&gt; "'".$str_BUTTON."'",
	 *             "DESCRIPTION"             =&gt; "'".$str_DESCRIPTION."'",
	 *             "DESCRIPTION_TYPE"        =&gt; "'".$str_DESCRIPTION_TYPE."'",
	 *             "SHOW_TEMPLATE"           =&gt; "'".trim($str_SHOW_TEMPLATE)."'",
	 *             "MAIL_EVENT_TYPE"         =&gt; "'".$DB-&gt;ForSql("FORM_FILLING_".$str_VARNAME,50)."'",
	 *             "SHOW_RESULT_TEMPLATE"    =&gt; "'".trim($str_SHOW_RESULT_TEMPLATE)."'",
	 *             "PRINT_RESULT_TEMPLATE"   =&gt; "'".trim($str_PRINT_RESULT_TEMPLATE)."'",
	 *             "EDIT_RESULT_TEMPLATE"    =&gt; "'".trim($str_EDIT_RESULT_TEMPLATE)."'",
	 *             "FILTER_RESULT_TEMPLATE"  =&gt; "'".trim($str_FILTER_RESULT_TEMPLATE)."'",
	 *             "TABLE_RESULT_TEMPLATE"   =&gt; "'".trim($str_TABLE_RESULT_TEMPLATE)."'",
	 *             "STAT_EVENT1"             =&gt; "'".trim($str_STAT_EVENT1)."'",
	 *             "STAT_EVENT2"             =&gt; "'".trim($str_STAT_EVENT2)."'",
	 *             "STAT_EVENT3"             =&gt; "'".trim($str_STAT_EVENT3)."'"
	 *             );
	 * 		$DB-&gt;StartTransaction();
	 *         if ($ID&gt;0) 
	 *         {
	 *             $DB-&gt;Update("b_form", $arFields, "WHERE ID='".$ID."'", $err_mess.__LINE__);
	 *         }
	 *         else 
	 *         {
	 *             $ID = <b>$DB-&gt;Insert</b>("b_form", $arFields, $err_mess.__LINE__);
	 *             $new="Y";
	 *         }
	 *         $ID = intval($ID);
	 *         if (strlen($strError)&lt;=0) 
	 *         {
	 *             $DB-&gt;Commit();
	 *             if (strlen($save)&gt;0) LocalRedirect("form_list.php?lang=".LANGUAGE_ID);
	 *             elseif ($new=="Y") LocalRedirect("form_edit.php?lang=".LANGUAGE_ID."&amp;ID=".$ID);
	 *         }
	 *         else $DB-&gt;Rollback();
	 *     }
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/query.php">CDatabase::Query</a> </li> <li>
	 * <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/forsql.php">CDatabase::ForSql</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/update.php">CDatabase::Update</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/prepareinsert.php">CDatabase::PrepareInsert</a> </li>
	 * <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/prepareupdate.php">CDatabase::PrepareUpdate</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/insert.php
	 * @author Bitrix
	 */
	public function Insert($table, $arFields, $error_position="", $DEBUG=false, $EXIST_ID="", $ignore_errors=false)
	{
		if (!is_array($arFields))
			return false;

		$str1 = "";
		$str2 = "";
		foreach ($arFields as $field => $value)
		{
			$str1 .= ($str1 <> ""? ", ":"")."`".$field."`";
			if (strlen($value) <= 0)
				$str2 .= ($str2 <> ""? ", ":"")."''";
			else
				$str2 .= ($str2 <> ""? ", ":"").$value;
		}

		if (strlen($EXIST_ID)>0)
		{
			$strSql = "INSERT INTO ".$table."(ID,".$str1.") VALUES ('".$this->ForSql($EXIST_ID)."',".$str2.")";
		}
		else
		{
			$strSql = "INSERT INTO ".$table."(".$str1.") VALUES (".$str2.")";
		}

		if ($DEBUG)
			echo "<br>".htmlspecialcharsEx($strSql)."<br>";

		$res = $this->Query($strSql, $ignore_errors, $error_position);

		if ($res === false)
			return false;

		if (strlen($EXIST_ID) > 0)
			return $EXIST_ID;
		else
			return $this->LastID();
	}

	
	/**
	 * <p>Функция изменяет записи в таблицы <i>table</i> значениями полей <i>fields</i>. Возвращает количество измененных записей.</p> <p class="note">Примечание: все значения полей должны быть подготовлены для SQL запроса, например, при помощи функции <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/forsql.php">CDatabase::ForSql</a>.</p> <p> </p>
	 *
	 *
	 *
	 *
	 * @param string $table  Название таблицы.
	 *
	 *
	 *
	 * @param array $fields  Массив вида значений полей "поле"=&gt;"значение",...
	 *
	 *
	 *
	 * @param string $where = "" Ограничение для WHERE в формате SQL<br> Необязательный. По умолчанию
	 * все записи в таблице будут изменены.
	 *
	 *
	 *
	 * @param string $error_position = "" Строка идентифицирующая позицию в коде, откуда была вызвана
	 * данная функция CDatabase::Update. Если в SQL запросе будет ошибка и если в
	 * файле <b>/bitrix/php_interface/dbconn.php</b> установлена переменная <b>$DBDebug=true;</b>,
	 * то на экране будет выведена данная информация и сам SQL запрос.
	 *
	 *
	 *
	 * @param bool $debug = false Если значение - "true", то на экран будет выведен текст SQL запроса.
	 *
	 *
	 *
	 * @param bool $ignore_errors = false если значение "true", то в случае ошибки возникшей в результате
	 * выполнения SQL запроса, она будет проигнорирована и работа скрипта
	 * продолжена.
	 *
	 *
	 *
	 * @return int 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * if (strlen($save)&gt;0)
	 * {
	 *     if (CheckFields())
	 *     {
	 *         $DB-&gt;PrepareFields("b_form");
	 *         $arFields = array(
	 *             "TIMESTAMP_X"             =&gt; $DB-&gt;GetNowFunction(),
	 *             "NAME"                    =&gt; "'".trim($str_NAME)."'",
	 *             "VARNAME"                 =&gt; "'".trim($str_VARNAME)."'",
	 *             "C_SORT"                  =&gt; "'".intval($str_C_SORT)."'",
	 *             "FIRST_SITE_ID"           =&gt; "'".$DB-&gt;ForSql($FIRST_SITE_ID,2)."'",
	 *             "BUTTON"                  =&gt; "'".$str_BUTTON."'",
	 *             "DESCRIPTION"             =&gt; "'".$str_DESCRIPTION."'",
	 *             "DESCRIPTION_TYPE"        =&gt; "'".$str_DESCRIPTION_TYPE."'",
	 *             "SHOW_TEMPLATE"           =&gt; "'".trim($str_SHOW_TEMPLATE)."'",
	 *             "MAIL_EVENT_TYPE"         =&gt; "'".$DB-&gt;ForSql("FORM_FILLING_".$str_VARNAME,50)."'",
	 *             "SHOW_RESULT_TEMPLATE"    =&gt; "'".trim($str_SHOW_RESULT_TEMPLATE)."'",
	 *             "PRINT_RESULT_TEMPLATE"   =&gt; "'".trim($str_PRINT_RESULT_TEMPLATE)."'",
	 *             "EDIT_RESULT_TEMPLATE"    =&gt; "'".trim($str_EDIT_RESULT_TEMPLATE)."'",
	 *             "FILTER_RESULT_TEMPLATE"  =&gt; "'".trim($str_FILTER_RESULT_TEMPLATE)."'",
	 *             "TABLE_RESULT_TEMPLATE"   =&gt; "'".trim($str_TABLE_RESULT_TEMPLATE)."'",
	 *             "STAT_EVENT1"             =&gt; "'".trim($str_STAT_EVENT1)."'",
	 *             "STAT_EVENT2"             =&gt; "'".trim($str_STAT_EVENT2)."'",
	 *             "STAT_EVENT3"             =&gt; "'".trim($str_STAT_EVENT3)."'"
	 *             );
	 * 		$DB-&gt;StartTransaction();
	 *         if ($ID&gt;0) 
	 *         {
	 *             <b>$DB-&gt;Update</b>("b_form", $arFields, "WHERE ID='".$ID."'", $err_mess.__LINE__);
	 *         }
	 *         else 
	 *         {
	 *             $ID = $DB-&gt;Insert("b_form", $arFields, $err_mess.__LINE__);
	 *             $new="Y";
	 *         }
	 *         $ID = intval($ID);
	 *         if (strlen($strError)&lt;=0) 
	 *         {
	 *             $DB-&gt;Commit();
	 *             if (strlen($save)&gt;0) LocalRedirect("form_list.php?lang=".LANGUAGE_ID);
	 *             elseif ($new=="Y") LocalRedirect("form_edit.php?lang=".LANGUAGE_ID."&amp;ID=".$ID);
	 *         }
	 *         else $DB-&gt;Rollback();
	 *     }
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/query.php">CDatabase::Query</a> </li> <li>
	 * <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/forsql.php">CDatabase::ForSql</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/insert.php">CDatabase::Insert</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/prepareinsert.php">CDatabase::PrepareInsert</a> </li>
	 * <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/prepareupdate.php">CDatabase::PrepareUpdate</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/update.php
	 * @author Bitrix
	 */
	public function Update($table, $arFields, $WHERE="", $error_position="", $DEBUG=false, $ignore_errors=false, $additional_check=true)
	{
		$rows = 0;
		if(is_array($arFields))
		{
			$ar = array();
			foreach($arFields as $field => $value)
			{
				if (strlen($value)<=0)
					$ar[] = "`".$field."` = ''";
				else
					$ar[] = "`".$field."` = ".$value."";
			}

			if (!empty($ar))
			{
				$strSql = "UPDATE ".$table." SET ".implode(", ", $ar)." ".$WHERE;
				if ($DEBUG)
					echo "<br>".htmlspecialcharsEx($strSql)."<br>";
				$w = $this->Query($strSql, $ignore_errors, $error_position);
				if (is_object($w))
				{
					$rows = $w->AffectedRowsCount();
					if ($DEBUG)
						echo "affected_rows = ".$rows."<br>";

					if ($rows <= 0 && $additional_check)
					{
						$w = $this->Query("SELECT 'x' FROM ".$table." ".$WHERE, $ignore_errors, $error_position);
						if (is_object($w))
						{
							if ($w->Fetch())
								$rows = $w->SelectedRowsCount();
							if ($DEBUG)
								echo "num_rows = ".$rows."<br>";
						}
					}
				}
			}
		}
		return $rows;
	}

	public static function Add($tablename, $arFields, $arCLOBFields = Array(), $strFileDir="", $ignore_errors=false, $error_position="", $arOptions=array())
	{
		global $DB;

		if(!is_object($this) || !isset($this->type))
		{
			return $DB->Add($tablename, $arFields, $arCLOBFields, $strFileDir, $ignore_errors, $error_position, $arOptions);
		}
		else
		{
			$arInsert = $this->PrepareInsert($tablename, $arFields, $strFileDir);
			$strSql =
				"INSERT INTO ".$tablename."(".$arInsert[0].") ".
				"VALUES(".$arInsert[1].")";
			$this->Query($strSql, $ignore_errors, $error_position, $arOptions);
			return $this->LastID();
		}
	}

	public static function TopSql($strSql, $nTopCount)
	{
		$nTopCount = intval($nTopCount);
		if($nTopCount>0)
			return $strSql."\nLIMIT ".$nTopCount;
		else
			return $strSql;
	}

	
	/**
	 * <p>Подготавливает строку (заменяет кавычки и прочее) для вставки в SQL запрос. Если задан параметр <i>max_length</i>, то также обрезает строку до длины <i>max_length</i>.</p> <p> </p>
	 *
	 *
	 *
	 *
	 * @param string $value  Исходная строка.
	 *
	 *
	 *
	 * @param int $max_length = 0 Максимальная длина. <br>Необязательный. По умолчанию - "0" (строка не
	 * обрезается).
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
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
	public static function ForSql($strValue, $iMaxLength = 0)
	{
		if ($iMaxLength > 0)
			$strValue = substr($strValue, 0, $iMaxLength);

		if (!is_object($this) || !$this->db_Conn)
		{
			global $DB;
			$DB->DoConnect();
			return mysql_real_escape_string($strValue, $DB->db_Conn);
		}
		else
		{
			$this->DoConnect();
			return mysql_real_escape_string($strValue, $this->db_Conn);
		}
	}

	public static function ForSqlLike($strValue, $iMaxLength = 0)
	{
		if ($iMaxLength > 0)
			$strValue = substr($strValue, 0, $iMaxLength);

		if(!is_object($this) || !$this->db_Conn)
		{
			global $DB;
			$DB->DoConnect();
			return mysql_real_escape_string(str_replace("\\", "\\\\", $strValue), $DB->db_Conn);
		}
		else
		{
			$this->DoConnect();
			return mysql_real_escape_string(str_replace("\\", "\\\\", $strValue), $this->db_Conn);
		}
	}

	
	/**
	 * <p>Создает глобальные переменные с именами ${<i>prefix_to</i>.имя_поля} и присваивает им значения переменных с именами ${<i>prefix_from</i>.имя_поля.<i>postfix_from</i>} переводя при этом в HTML-безопасный вид. Под "имя_поля" подразумеваются имена полей таблицы <i>table</i>.</p> <p> </p> <p class="note">Функция работает с переменными из глобальной области видимости, это необходимо учитывать при создании основных файлов компонентов.</p>
	 *
	 *
	 *
	 *
	 * @param string $table  Название таблицы.
	 *
	 *
	 *
	 * @param string $prefix_from = "str_" Префикс для переменных ИЗ которых будет производиться
	 * преобразование. <br> Необязательный. По умолчанию "str_".
	 *
	 *
	 *
	 * @param string $prefix_to = "str_" Префикс для переменных В которые будет производиться
	 * преобразование. <br> Необязательный. По умолчанию "str_".
	 *
	 *
	 *
	 * @param string $postfix_from = "" Суффикс (постфикс) для переменных ИЗ которых будет производиться
	 * преобразование. <br> Необязательный. По умолчанию "".
	 *
	 *
	 *
	 * @param bool $init_anyway = false Значение "true" - инициализировать переменные всегда, т.е. не
	 * зависимо были ли они изначально. <br> Необязательный. По умолчанию -
	 * "false".
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $stoplist = CStoplist::GetByID($ID);
	 * if (!($stoplist &amp;&amp; $stoplist-&gt;ExtractFields()))
	 * {
	 * 	$ID=0; 
	 * 	$str_ACTIVE="Y";
	 * 	$str_MASK_1="255";
	 * 	$str_MASK_2="255";
	 * 	$str_MASK_3="255";
	 * 	$str_MASK_4="255";
	 * 	$str_IP_1 = $net1;
	 * 	$str_IP_2 = $net2;
	 * 	$str_IP_3 = $net3;
	 * 	$str_IP_4 = $net4;
	 * 	$str_USER_AGENT = $user_agent;
	 * 	$str_DATE_START=GetTime(time(),"FULL");
	 * 	$str_MESSAGE = GetMessage("STAT_DEFAULT_MESSAGE");
	 * 	$str_MESSAGE_LID = LANGUAGE_ID;
	 * 	$str_SAVE_STATISTIC = "Y";
	 * }
	 * if (strlen($strError)&gt;0) <b>$DB-&gt;InitTableVarsForEdit</b>("b_stop_list", "", "str_");
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/preparefields.php">CDatabase::PrepareFields</a> </li>
	 * </ul><a name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/inittablevarsforedit.php
	 * @author Bitrix
	 */
	public function InitTableVarsForEdit($tablename, $strIdentFrom="str_", $strIdentTo="str_", $strSuffixFrom="", $bAlways=false)
	{
		$this->DoConnect();
		$db_result = mysql_list_fields($this->DBName, $tablename, $this->db_Conn);
		if($db_result > 0)
		{
			$intNumFields = mysql_num_fields($db_result);
			while(--$intNumFields >= 0)
			{
				$strColumnName = mysql_field_name($db_result, $intNumFields);

				$varnameFrom = $strIdentFrom.$strColumnName.$strSuffixFrom;
				$varnameTo = $strIdentTo.$strColumnName;
				global ${$varnameFrom}, ${$varnameTo};
				if((isset(${$varnameFrom}) || $bAlways))
				{
					if(is_array(${$varnameFrom}))
					{
						${$varnameTo} = array();
						foreach(${$varnameFrom} as $k => $v)
							${$varnameTo}[$k] = htmlspecialcharsbx($v);
					}
					else
						${$varnameTo} = htmlspecialcharsbx(${$varnameFrom});
				}
			}
		}
	}

	public function GetTableFieldsList($table)
	{
		return array_keys($this->GetTableFields($table));
	}

	public function GetTableFields($table)
	{
		if(!array_key_exists($table, $this->column_cache))
		{
			$this->column_cache[$table] = array();
			$this->DoConnect();
			$rs = @mysql_list_fields($this->DBName, $table, $this->db_Conn);
			if($rs > 0)
			{
				$intNumFields = mysql_num_fields($rs);
				while(--$intNumFields >= 0)
				{
					$ar = array(
						"NAME" => mysql_field_name($rs, $intNumFields),
						"TYPE" => mysql_field_type($rs, $intNumFields),
					);
					$this->column_cache[$table][$ar["NAME"]] = $ar;
				}
			}
		}
		return $this->column_cache[$table];
	}

	public function LockTables($str)
	{
		register_shutdown_function(array(&$this, "UnLockTables"));
		$this->Query("LOCK TABLE ".$str, false, '', array("fixed_connection"=>true));
	}

	public function UnLockTables()
	{
		$this->Query("UNLOCK TABLES", true, '', array("fixed_connection"=>true));
	}

	public static function Concat()
	{
		$str = "";
		$ar = func_get_args();
		if (is_array($ar)) $str .= implode(" , ", $ar);
		if (strlen($str)>0) $str = "concat(".$str.")";
		return $str;
	}

	public static function IsNull($expression, $result)
	{
		return "ifnull(".$expression.", ".$result.")";
	}

	public static function Length($field)
	{
		return "length($field)";
	}

	public static function ToChar($expr, $len=0)
	{
		return $expr;
	}

	public function TableExists($tableName)
	{
		$tableName = preg_replace("/[^A-Za-z0-9%_]+/i", "", $tableName);
		$tableName = Trim($tableName);

		if (strlen($tableName) <= 0)
			return False;

		$dbResult = $this->Query("SHOW TABLES LIKE '".$this->ForSql($tableName)."'", false, '', array("fixed_connection"=>true));
		if ($arResult = $dbResult->Fetch())
			return True;
		else
			return False;
	}

	public function IndexExists($tableName, $arColumns)
	{
		return $this->GetIndexName($tableName, $arColumns) !== "";
	}

	public function GetIndexName($tableName, $arColumns, $bStrict = false)
	{
		if(!is_array($arColumns) || count($arColumns) <= 0)
			return "";

		$rs = $this->Query("SHOW INDEX FROM `".$this->ForSql($tableName)."`", true, '', array("fixed_connection"=>true));
		if(!$rs)
			return "";

		$arIndexes = array();
		while($ar = $rs->Fetch())
			$arIndexes[$ar["Key_name"]][$ar["Seq_in_index"]-1] = $ar["Column_name"];

		$strColumns = implode(",", $arColumns);
		foreach($arIndexes as $Key_name => $arKeyColumns)
		{
			ksort($arKeyColumns);
			$strKeyColumns = implode(",", $arKeyColumns);
			if($bStrict)
			{
				if($strKeyColumns === $strColumns)
					return $Key_name;
			}
			else
			{
				if(substr($strKeyColumns, 0, strlen($strColumns)) === $strColumns)
					return $Key_name;
			}
		}

		return "";
	}

	public static function SlaveConnection()
	{
		if(!class_exists('cmodule') || !class_exists('csqlwhere'))
			return null;

		if(!CModule::IncludeModule('cluster'))
			return false;

		$arSlaves = CClusterSlave::GetList();
		if(empty($arSlaves))
			return false;

		$max_slave_delay = COption::GetOptionInt("cluster", "max_slave_delay", 10);
		if(isset($_SESSION["BX_REDIRECT_TIME"]))
		{
			$redirect_delay = time() - $_SESSION["BX_REDIRECT_TIME"] + 1;
			if(
				$redirect_delay > 0
				&& $redirect_delay < $max_slave_delay
			)
				$max_slave_delay = $redirect_delay;
		}

		$total_weight = 0;
		foreach($arSlaves as $i=>$slave)
		{
			if(defined("BX_CLUSTER_GROUP") && BX_CLUSTER_GROUP != $slave["GROUP_ID"])
			{
				unset($arSlaves[$i]);
			}
			elseif($slave["ROLE_ID"] == "SLAVE")
			{
				$arSlaveStatus = CClusterSlave::GetStatus($slave["ID"], true, false, false);
				if(
					$arSlaveStatus['Seconds_Behind_Master'] > $max_slave_delay
					|| $arSlaveStatus['Last_SQL_Error'] != ''
					|| $arSlaveStatus['Last_IO_Error'] != ''
					|| $arSlaveStatus['Slave_SQL_Running'] === 'No'
				)
				{
					unset($arSlaves[$i]);
				}
				else
				{
					$total_weight += $slave["WEIGHT"];
				}
			}
			else
			{
				$total_weight += $slave["WEIGHT"];
			}
		}

		$found = false;
		foreach($arSlaves as $slave)
		{
			if(mt_rand(0, $total_weight) < $slave["WEIGHT"])
			{
				$found = $slave;
				break;
			}
		}

		if(!$found || $found["ROLE_ID"] != "SLAVE")
		{
			return false; //use main connection
		}
		else
		{
			ob_start();
			$conn = CDatabase::GetDBNodeConnection($found["ID"], true);
			ob_end_clean();

			if(is_object($conn))
			{
				return $conn;
			}
			else
			{
				self::$arNodes[$found["ID"]]["ONHIT_ERROR"] = true;
				CClusterDBNode::SetOffline($found["ID"]);
				return false; //use main connection
			}
		}
	}

	public static function Instr($str, $toFind)
	{
		return "INSTR($str, $toFind)";
	}
}

class CDBResult extends CAllDBResult
{
	public static function CDBResult($res=NULL)
	{
		parent::CAllDBResult($res);
	}

	/**
	 * Returns next row of the select result in form of associated array
	 *
	 * @return array
	 */
	
	/**
	 * <p>Делает выборку значений полей в массив. Возвращает массив вида Array("поле"=&gt;"значение" [, ...]) и передвигает курсор на следующую запись. Если достигнута последняя запись (или в результате нет ни одной записи) - функция вернет "false".</p>
	 *
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $rsUser = CUser::GetByID($USER_ID);
	 * $arUser = <b>$rsUser-&gt;Fetch</b>();
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/getnext.php">CDBResult::GetNext</a> </li>
	 * <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/extractfields.php">CDBResult::ExtractFields</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/navnext.php">CDBResult::NavNext</a> </li>
	 * </ul><a name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/fetch.php
	 * @author Bitrix
	 */
	public function Fetch()
	{
		global $DB;

		if($this->bNavStart || $this->bFromArray)
		{
			if(!is_array($this->arResult))
				$res = false;
			elseif($res = current($this->arResult))
				next($this->arResult);
		}
		elseif($this->SqlTraceIndex)
		{
			$start_time = microtime(true);

			if(!$this->arUserMultyFields)
			{
				$res = mysql_fetch_array($this->result, MYSQL_ASSOC);
			}
			else
			{
				$res = mysql_fetch_array($this->result, MYSQL_ASSOC);
				if($res)
					foreach($this->arUserMultyFields as $FIELD_NAME=>$flag)
						if($res[$FIELD_NAME])
							$res[$FIELD_NAME] = unserialize($res[$FIELD_NAME]);
			}

			if ($res && $this->arReplacedAliases)
			{
				foreach($this->arReplacedAliases as $tech => $human)
				{
					$res[$human] = $res[$tech];
					unset($res[$tech]);
				}
			}

			$exec_time = round(microtime(true) - $start_time, 10);
			$DB->addDebugTime($this->SqlTraceIndex, $exec_time);
			$DB->timeQuery += $exec_time;
		}
		else
		{
			if(!$this->arUserMultyFields)
			{
				$res = mysql_fetch_array($this->result, MYSQL_ASSOC);
			}
			else
			{
				$res = mysql_fetch_array($this->result, MYSQL_ASSOC);
				if($res)
					foreach($this->arUserMultyFields as $FIELD_NAME=>$flag)
						if($res[$FIELD_NAME])
							$res[$FIELD_NAME] = unserialize($res[$FIELD_NAME]);
			}

			if ($res && $this->arReplacedAliases)
			{
				foreach($this->arReplacedAliases as $tech => $human)
				{
					$res[$human] = $res[$tech];
					unset($res[$tech]);
				}
			}
		}

		return $res;
	}

	
	/**
	 * <p>Функция возвращает количество выбранных записей (выборка записей осуществляется с помощью SQL-команды "SELECT ...").</p> <p class="note">Для Oracle версии данная функция будет корректно работать только после вызова <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/navstart.php">CDBResult::NavStart</a>, либо если достигнут конец (последняя запись) выборки.</p>
	 *
	 *
	 *
	 *
	 * @return int 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
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
	 *
	 * <h4>See Also</h4> 
	 * <ul><li> <a
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/affectedrowscount.php">CDBResult::AffectedRowsCount</a>
	 * </li></ul><a name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/selectedrowscount.php
	 * @author Bitrix
	 */
	public function SelectedRowsCount()
	{
		if($this->nSelectedCount !== false)
			return $this->nSelectedCount;

		if(is_resource($this->result))
			return mysql_num_rows($this->result);
		else
			return 0;
	}

	
	/**
	 * <p>Функция возвращает количество записей, измененных SQL-командами <b>INSERT</b>, <b>UPDATE</b> или <b>DELETE</b>.</p> <br>
	 *
	 *
	 *
	 *
	 * @return int 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
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
	public static function AffectedRowsCount()
	{
		if(is_object($this) && is_object($this->DB))
		{
			/** @noinspection PhpUndefinedMethodInspection */
			$this->DB->DoConnect();
			return mysql_affected_rows($this->DB->db_Conn);
		}
		else
		{
			global $DB;
			$DB->DoConnect();
			return mysql_affected_rows($DB->db_Conn);
		}
	}

	public function AffectedRowsCountEx()
	{
		if(is_resource($this->result) && mysql_num_rows($this->result) > 0)
			return 0;
		else
			return mysql_affected_rows();
	}

	
	/**
	 * <p>Функция возвращает количество полей результата выборки.</p>
	 *
	 *
	 *
	 *
	 * @return int 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
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
	 *
	 * <h4>See Also</h4> 
	 * <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/fieldname.php">CDBResult::FieldName</a>
	 * </li></ul><a name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/fieldscount.php
	 * @author Bitrix
	 */
	public function FieldsCount()
	{
		if(is_resource($this->result))
			return mysql_num_fields($this->result);
		else
			return 0;
	}

	
	/**
	 * <p>Функция возвращает название поля по его номеру.</p>
	 *
	 *
	 *
	 *
	 * @param int $column  
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
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
	 *
	 * <h4>See Also</h4> 
	 * <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/fieldscount.php">CDBResult::FieldsCount</a>
	 * </li></ul><a name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/fieldname.php
	 * @author Bitrix
	 */
	public function FieldName($iCol)
	{
		return mysql_field_name($this->result, $iCol);
	}

	public function DBNavStart()
	{
		global $DB;

		//total rows count
		if(is_resource($this->result))
			$this->NavRecordCount = mysql_num_rows($this->result);
		else
			return;

		if($this->NavRecordCount < 1)
			return;

		if($this->NavShowAll)
			$this->NavPageSize = $this->NavRecordCount;

		//calculate total pages depend on rows count. start with 1
		$this->NavPageCount = floor($this->NavRecordCount/$this->NavPageSize);
		if($this->NavRecordCount % $this->NavPageSize > 0)
			$this->NavPageCount++;

		//page number to display. start with 1
		$this->NavPageNomer = ($this->PAGEN < 1 || $this->PAGEN > $this->NavPageCount? ($_SESSION[$this->SESS_PAGEN] < 1 || $_SESSION[$this->SESS_PAGEN] > $this->NavPageCount? 1:$_SESSION[$this->SESS_PAGEN]):$this->PAGEN);

		//rows to skip
		$NavFirstRecordShow = $this->NavPageSize * ($this->NavPageNomer-1);
		$NavLastRecordShow = $this->NavPageSize * $this->NavPageNomer;

		if($this->SqlTraceIndex)
			$start_time = microtime(true);

		mysql_data_seek($this->result, $NavFirstRecordShow);
		$temp_arrray = array();
		for($i=$NavFirstRecordShow; $i<$NavLastRecordShow; $i++)
		{
			if(($res = mysql_fetch_array($this->result, MYSQL_ASSOC)))
			{
				if($this->arUserMultyFields)
					foreach($this->arUserMultyFields as $FIELD_NAME=>$flag)
						if($res[$FIELD_NAME])
							$res[$FIELD_NAME] = unserialize($res[$FIELD_NAME]);

				if ($this->arReplacedAliases)
				{
					foreach($this->arReplacedAliases as $tech => $human)
					{
						$res[$human] = $res[$tech];
						unset($res[$tech]);
					}
				}

				$temp_arrray[] = $res;
			}
			else
			{
				break;
			}
		}

		if($this->SqlTraceIndex)
		{
			/** @noinspection PhpUndefinedVariableInspection */
			$exec_time = round(microtime(true) - $start_time, 10);
			$DB->addDebugTime($this->SqlTraceIndex, $exec_time);
			$DB->timeQuery += $exec_time;
		}

		$this->arResult = $temp_arrray;
	}

	public function NavQuery($strSql, $cnt, $arNavStartParams, $bIgnoreErrors = false)
	{
		global $DB;

		if(isset($arNavStartParams["SubstitutionFunction"]))
		{
			$arNavStartParams["SubstitutionFunction"]($this, $strSql, $cnt, $arNavStartParams);
			return null;
		}

		if(isset($arNavStartParams["bDescPageNumbering"]))
			$bDescPageNumbering = $arNavStartParams["bDescPageNumbering"];
		else
			$bDescPageNumbering = false;

		$this->InitNavStartVars($arNavStartParams);
		$this->NavRecordCount = $cnt;

		if($this->NavShowAll)
			$this->NavPageSize = $this->NavRecordCount;

		//calculate total pages depend on rows count. start with 1
		$this->NavPageCount = ($this->NavPageSize>0 ? floor($this->NavRecordCount/$this->NavPageSize) : 0);
		if($bDescPageNumbering)
		{
			$makeweight = ($this->NavRecordCount % $this->NavPageSize);
			if($this->NavPageCount == 0 && $makeweight > 0)
				$this->NavPageCount = 1;

			//page number to display
			$this->NavPageNomer =
			(
				$this->PAGEN < 1 || $this->PAGEN > $this->NavPageCount
				?
					($_SESSION[$this->SESS_PAGEN] < 1 || $_SESSION[$this->SESS_PAGEN] > $this->NavPageCount
					?
						$this->NavPageCount
					:
						$_SESSION[$this->SESS_PAGEN]
					)
				:
					$this->PAGEN
			);

			//rows to skip
			$NavFirstRecordShow = 0;
			if($this->NavPageNomer != $this->NavPageCount)
				$NavFirstRecordShow += $makeweight;

			$NavFirstRecordShow += ($this->NavPageCount - $this->NavPageNomer) * $this->NavPageSize;
			$NavLastRecordShow = $makeweight + ($this->NavPageCount - $this->NavPageNomer + 1) * $this->NavPageSize;
		}
		else
		{
			if($this->NavPageSize && ($this->NavRecordCount % $this->NavPageSize > 0))
				$this->NavPageCount++;

			//calculate total pages depend on rows count. start with 1
			if($this->PAGEN >= 1 && $this->PAGEN <= $this->NavPageCount)
				$this->NavPageNomer = $this->PAGEN;
			elseif($_SESSION[$this->SESS_PAGEN] >= 1 && $_SESSION[$this->SESS_PAGEN] <= $this->NavPageCount)
				$this->NavPageNomer = $_SESSION[$this->SESS_PAGEN];
			elseif($arNavStartParams["checkOutOfRange"] !== true)
				$this->NavPageNomer = 1;
			else
				return null;

			//rows to skip
			$NavFirstRecordShow = $this->NavPageSize*($this->NavPageNomer-1);
			$NavLastRecordShow = $this->NavPageSize*$this->NavPageNomer;
		}

		$NavAdditionalRecords = 0;
		if(is_set($arNavStartParams, "iNavAddRecords"))
			$NavAdditionalRecords = $arNavStartParams["iNavAddRecords"];

		if(!$this->NavShowAll)
			$strSql .= " LIMIT ".$NavFirstRecordShow.", ".($NavLastRecordShow - $NavFirstRecordShow + $NavAdditionalRecords);

		if(is_object($this->DB))
			$res_tmp = $this->DB->Query($strSql, $bIgnoreErrors);
		else
			$res_tmp = $DB->Query($strSql, $bIgnoreErrors);

		// Return false on sql errors (if $bIgnoreErrors == true)
		if ($bIgnoreErrors && ($res_tmp === false))
			return false;

		if($this->SqlTraceIndex)
			$start_time = microtime(true);

		$temp_arrray = array();
		$temp_arrray_add = array();
		$tmp_cnt = 0;

		while($ar = mysql_fetch_array($res_tmp->result, MYSQL_ASSOC))
		{
			$tmp_cnt++;
			if($this->arUserMultyFields)
				foreach($this->arUserMultyFields as $FIELD_NAME=>$flag)
					if($ar[$FIELD_NAME])
						$ar[$FIELD_NAME] = unserialize($ar[$FIELD_NAME]);

			if ($this->arReplacedAliases)
			{
				foreach($this->arReplacedAliases as $tech => $human)
				{
					$ar[$human] = $ar[$tech];
					unset($ar[$tech]);
				}
			}

			if (intval($NavLastRecordShow - $NavFirstRecordShow) > 0 && $tmp_cnt > ($NavLastRecordShow - $NavFirstRecordShow))
				$temp_arrray_add[] = $ar;
			else
				$temp_arrray[] = $ar;
		}

		if($this->SqlTraceIndex)
		{
			/** @noinspection PhpUndefinedVariableInspection */
			$exec_time = round(microtime(true) - $start_time, 10);
			$DB->addDebugTime($this->SqlTraceIndex, $exec_time);
			$DB->timeQuery += $exec_time;
		}

		$this->result = $res_tmp->result; // added for FieldsCount and other compatibility
		$this->arResult = (count($temp_arrray)? $temp_arrray : false);
		$this->arResultAdd = (count($temp_arrray_add)? $temp_arrray_add : false);
		$this->nSelectedCount = $cnt;
		$this->bDescPageNumbering = $bDescPageNumbering;
		$this->bFromLimited = true;
		$this->DB = $res_tmp->DB;

		return null;
	}
}
