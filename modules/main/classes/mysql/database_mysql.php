<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */

/********************************************************************
*	MySQL database classes
********************************************************************/

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
class CDatabase extends CDatabaseMysql
{
	public function ConnectInternal()
	{
		if (DBPersistent && !$this->bNodeConnection)
			$this->db_Conn = @mysql_pconnect($this->DBHost, $this->DBLogin, $this->DBPassword);
		else
			$this->db_Conn = @mysql_connect($this->DBHost, $this->DBLogin, $this->DBPassword, true);

		if(!$this->db_Conn)
		{
			$s = (DBPersistent && !$this->bNodeConnection? "mysql_pconnect" : "mysql_connect");
			if($this->debug || (isset($_SESSION["SESS_AUTH"]["ADMIN"]) && $_SESSION["SESS_AUTH"]["ADMIN"]))
				echo "<br><font color=#ff0000>Error! ".$s."()</font><br>".mysql_error()."<br>";

			SendError("Error! ".$s."()\n".mysql_error()."\n");

			return false;
		}

		if(!mysql_select_db($this->DBName, $this->db_Conn))
		{
			if($this->debug || (isset($_SESSION["SESS_AUTH"]["ADMIN"]) && $_SESSION["SESS_AUTH"]["ADMIN"]))
				echo "<br><font color=#ff0000>Error! mysql_select_db(".$this->DBName.")</font><br>".mysql_error($this->db_Conn)."<br>";

			SendError("Error! mysql_select_db(".$this->DBName.")\n".mysql_error($this->db_Conn)."\n");

			return false;
		}

		return true;
	}

	protected function QueryInternal($strSql)
	{
		return mysql_query($strSql, $this->db_Conn);
	}

	protected function GetError()
	{
		return mysql_error($this->db_Conn);
	}

	protected function DisconnectInternal($resource)
	{
		mysql_close($resource);
	}

	
	/**
	* <p>Метод возвращает ID последней вставленной записи. Нестатический метод.</p> <p> </p>
	*
	*
	* @return int 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
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
	* <h4>See Also</h4> 
	* <ul><li><a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/query.php">CDatabase::Query</a></li></ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/lastid.php
	* @author Bitrix
	*/
	public function LastID()
	{
		$this->DoConnect();
		return mysql_insert_id($this->db_Conn);
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

	protected function getThreadId()
	{
		return mysql_thread_id($this->db_Conn);
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
class CDBResult extends CDBResultMysql
{
	static public function __construct($res = null)
	{
		parent::__construct($res);
	}

	/** @deprecated */
	static public function CDBResult($res = null)
	{
		self::__construct($res);
	}

	protected function FetchRow()
	{
		return mysql_fetch_array($this->result, MYSQL_ASSOC);
	}

	
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
	public function FieldsCount()
	{
		if(is_resource($this->result))
			return mysql_num_fields($this->result);
		else
			return 0;
	}

	
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
			if(($res = $this->FetchInternal()))
			{
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
}
