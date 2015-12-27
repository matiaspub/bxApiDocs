<?
/***************************************
		Статус результата веб-формы
***************************************/


/**
 * <b>CFormStatus</b> - класс для работы со <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статусами</a>. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/form/classes/cformstatus/index.php
 * @author Bitrix
 */
class CFormStatus extends CAllFormStatus
{
	fpublic static unction err_mess()
	{
		$module_id = "form";
		@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/install/version.php");
		return "<br>Module: ".$module_id." (".$arModuleVersion["VERSION"].")<br>Class: CFormStatus<br>File: ".__FILE__;
	}

	// список статусов

	/**
	* <p>Возвращает список <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статусов</a> в виде объекта класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p>
	*
	*
	* @param int $form_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">веб-формы</a>.</bod
	*
	* @param string &$by = "s_sort" Ссылка на переменную с полем для сортировки результирующего
	* списка, может принимать значения: <ul> <li> <b>s_id</b> - ID <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статуса</a>; </li> <li> <b>s_sort</b> -
	* индекс сортировки; </li> <li> <b>s_timestamp</b> - время последнего изменения <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статуса</a>; </li> <li> <b>s_active</b> - флаг
	* активности; </li> <li> <b>s_default</b> - флаг установки по умолчанию; </li> <li>
	* <b>s_title</b> - заголовок; </li> <li> <b>s_description</b> - описание; </li> <li> <b>s_results</b> -
	* количество результатов находящихся в данном <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статусе</a>. </li> </ul>
	*
	* @param string &$order = "asc" Ссылка на переменную с порядком сортировки, может принимать
	* значения: <ul> <li> <b>asc</b> - по возрастанию; </li> <li> <b>desc</b> - по убыванию.
	* </li> </ul>
	*
	* @param array $filter = array() Массив для фильтрации. Необязательный параметр. В массиве
	* допустимы следующие ключи: <ul> <li> <b>ID</b>* - ID <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статуса</a> (по умолчанию будет
	* искаться точное совпадение); </li> <li> <b>ID_EXACT_MATCH</b> - если значение
	* равно "N", при фильтрации по <b>ID</b> будет искаться вхождение; </li> <li>
	* <b>ACTIVE</b> - флаг активности, допустимые следующие значения: <ul> <li>
	* <b>Y</b> - <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статус</a> активен; </li> <li>
	* <b>N</b> - <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статус</a> не активен. </li>
	* </ul> </li> <li> <b>TITLE</b>* - заголовок <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статуса</a> (по умолчанию будет
	* искаться вхождение); </li> <li> <b>TITLE_EXACT_MATCH</b> - если значение равно "Y",
	* то при фильтрации по <b>TITLE</b> будет искаться точное совпадение; </li>
	* <li> <b>DESCRIPTION</b>* - описание <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статуса</a> (по умолчанию будет
	* искаться вхождение); </li> <li> <b>DESCRIPTION_EXACT_MATCH</b> - если значение равно
	* "Y", то при фильтрации по <b>DESCRIPTION</b> будет искаться точное
	* совпадение. </li> </ul> * - допускается сложная логика
	*
	* @param bool &$is_filtered  Ссылка на переменную хранящую флаг отфильтрованности
	* результирующего списка. Если значение равно "true", то список был
	* отфильтрован.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $FORM_ID = 4; // ID веб-формы
	* 
	* // сформируем массив фильтра
	* $arFilter = Array(
	*     "ID"                       =&gt; "1 | 4",       // ID статуса равен 1 или 4
	*     "ID_EXACT_MATCH"           =&gt; "Y",           // точное совпадение для ID
	*     "ACTIVE"                   =&gt; "Y",           // флаг активности
	*     "TITLE"                    =&gt; "опубликован", // заголовок
	*     "TITLE_EXACT_MATCH"        =&gt; "N",           // точное совпадение для TITLE
	*     "DESCRIPTION"              =&gt; "конечный",    // описание
	*     "DESCRIPTION_EXACT_MATCH"  =&gt; "N",           // точное совпадение для DESCRIPTION
	* );
	* 
	* // получим список всех статусов формы, соответствующих фильтру
	* $rsStatuses = <b>CFormStatus::GetList</b>(
	*     $FORM_ID, 
	*     $by="s_id", 
	*     $order="desc", 
	*     $arFilter, 
	*     $is_filtered
	*     );
	* while ($arStatus = $rsStatuses-&gt;Fetch())
	* {
	*     echo "&lt;pre&gt;"; print_r($arStatus); echo "&lt;/pre&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformstatus/index.php">Поля CFormStatus</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformstatus/getbyid.php">CFormStatus::GetByID</a> </li>
	* </ul></b<a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformstatus/getlist.php
	* @author Bitrix
	*/
	public static 	function GetList($FORM_ID, &$by, &$order, $arFilter=array(), &$is_filtered)
	{
		$err_mess = (CFormStatus::err_mess())."<br>Function: GetList<br>Line: ";
		global $DB, $strError;
		$FORM_ID = intval($FORM_ID);
		$arSqlSearch = Array();
		$strSqlSearch = "";
		if (is_array($arFilter))
		{
			$filter_keys = array_keys($arFilter);
			for ($i=0; $i<count($filter_keys); $i++)
			{
				$key = $filter_keys[$i];
				$val = $arFilter[$filter_keys[$i]];
				if (strlen($val)<=0 || "$val"=="NOT_REF") continue;
				if (is_array($val) && count($val)<=0) continue;
				$match_value_set = (in_array($key."_EXACT_MATCH", $filter_keys)) ? true : false;
				$key = strtoupper($key);
				switch($key)
				{
					case "ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("S.ID",$val,$match);
						break;
					case "ACTIVE":
						$arSqlSearch[] = ($val=="Y") ? "S.ACTIVE='Y'" : "S.ACTIVE='N'";
						break;
					case "TITLE":
					case "DESCRIPTION":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("S.".$key, $val, $match);
						break;
					case "RESULTS_1":
						$arSqlSearch_h[] = "count(R.ID)>='".intval($val)."'";
						break;
					case "RESULTS_2":
						$arSqlSearch_h[] = "count(R.ID)<='".intval($val)."'";
						break;
				}
			}
			for($i=0; $i<count($arSqlSearch_h); $i++) $strSqlSearch_h .= " and (".$arSqlSearch_h[$i].") ";
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		if ($by == "s_id")					$strSqlOrder = "ORDER BY S.ID";
		elseif ($by == "s_timestamp")		$strSqlOrder = "ORDER BY S.TIMESTAMP_X";
		elseif ($by == "s_active")			$strSqlOrder = "ORDER BY S.ACTIVE";
		elseif ($by == "s_c_sort" ||
				$by == "s_sort")			$strSqlOrder = "ORDER BY S.C_SORT";
		elseif ($by == "s_default")			$strSqlOrder = "ORDER BY S.DEFAULT_VALUE";
		elseif ($by == "s_title")			$strSqlOrder = "ORDER BY S.TITLE ";
		elseif ($by == "s_description")		$strSqlOrder = "ORDER BY S.DESCRIPTION";
		elseif ($by == "s_results")			$strSqlOrder = "ORDER BY RESULTS";
		else
		{
			$by = "s_sort";
			$strSqlOrder = "ORDER BY S.C_SORT";
		}
		if ($order!="desc")
		{
			$strSqlOrder .= " asc ";
			$order="asc";
		}
		else $strSqlOrder .= " desc ";

		$strSql = "
			SELECT
				S.ID, S.CSS, S.FORM_ID, S.C_SORT, S.ACTIVE, S.TITLE, S.DESCRIPTION, S.DEFAULT_VALUE, S.HANDLER_OUT, S.HANDLER_IN,
				".$DB->DateToCharFunction("S.TIMESTAMP_X")."	TIMESTAMP_X,
				count(distinct R.ID) RESULTS
			FROM
				b_form_status S
			LEFT JOIN b_form_result R ON (R.STATUS_ID = S.ID and R.FORM_ID=S.FORM_ID)
			WHERE
			$strSqlSearch
			and S.FORM_ID = $FORM_ID
			GROUP BY S.ID
			HAVING
				1=1
				$strSqlSearch_h
			$strSqlOrder
			";
		//echo "<pre>".$strSql."</pre>";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch));
		return $res;
	}


	/**
	* <p>Возвращает <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformstatus/index.php">параметры</a> <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статуса</a> в виде объекта класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p>
	*
	*
	* @param int $status_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статуса</a>.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $status_id = 1; // ID статуса
	* $rsStatus = <b>CFormStatus::GetByID</b>($status_id);
	* $arStatus = $rsStatus-&gt;Fetch();
	* echo "&lt;pre&gt;"; print_r($arStatus); echo "&lt;/pre";
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformstatus/index.php">Поля CFormStatus</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformstatus/getlist.php">CFormStatus::GetList</a> </li>
	* </ul></b<a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformstatus/getbyid.php
	* @author Bitrix
	*/
	public static 	function GetByID($ID)
	{
		$err_mess = (CFormStatus::err_mess())."<br>Function: GetByID<br>Line: ";
		global $DB, $strError;
		$ID = intval($ID);
		$strSql = "
			SELECT
				S.ID, S.CSS, S.FORM_ID, S.C_SORT, S.ACTIVE, S.TITLE, S.DESCRIPTION, S.DEFAULT_VALUE, S.HANDLER_OUT, S.HANDLER_IN, S.MAIL_EVENT_TYPE, 
				".$DB->DateToCharFunction("S.TIMESTAMP_X")." TIMESTAMP_X,
				count(distinct R.ID) RESULTS
			FROM
				b_form_status S
			LEFT JOIN b_form_result R ON (R.STATUS_ID = S.ID and R.FORM_ID=S.FORM_ID)
			WHERE
				S.ID = $ID
			GROUP BY S.ID
			";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}

public static 	function GetDropdown($FORM_ID, $PERMISSION = array("MOVE"), $OWNER_ID=0)
	{
		$err_mess = (CFormStatus::err_mess())."<br>Function: GetDropdown<br>Line: ";
		global $DB, $USER, $strError;
		$FORM_ID = intval($FORM_ID);
		if (CForm::IsAdmin())
		{
			$strSql = "
				SELECT
					S.ID								REFERENCE_ID,
					concat('[',S.ID,'] ',S.TITLE)		REFERENCE
				FROM
					b_form_status S
				WHERE
					S.FORM_ID = $FORM_ID
				and S.ACTIVE = 'Y'
				ORDER BY S.C_SORT
				";
		}
		else
		{
			if (is_array($PERMISSION)) $arrPERMISSION = $PERMISSION;
			else
			{
				if (intval($PERMISSION)==2) $PERMISSION = "MOVE";
				if (intval($PERMISSION)==1) $PERMISSION = "VIEW, MOVE";
				$arrPERMISSION = explode(",",$PERMISSION);
			}
			$str = "''";
			$arrPERM = array();
			if (is_array($arrPERMISSION) && count($arrPERMISSION)>0)
			{
				foreach ($arrPERMISSION as $perm)
				{
					$arrPERM[] = trim($perm);
					$str .= ",'".$DB->ForSql(trim($perm))."'";
				}
			}
			$arGroups = $USER->GetUserGroupArray();
			if (!is_array($arGroups)) $arGroups[] = 2;
			if ($OWNER_ID==$USER->GetID() || (in_array("VIEW",$arrPERM) && in_array("MOVE",$arrPERM))) $arGroups[] = 0;
			if (is_array($arGroups) && count($arGroups)>0) $groups = implode(",",$arGroups);
			$strSql = "
				SELECT
					S.ID								REFERENCE_ID,
					concat('[',S.ID,'] ',S.TITLE)		REFERENCE
				FROM
					b_form_status S,
					b_form_status_2_group G
				WHERE
					S.FORM_ID = $FORM_ID
				and S.ACTIVE = 'Y'
				and G.STATUS_ID = S.ID
				and G.GROUP_ID in ($groups)
				and G.PERMISSION in ($str)
				GROUP BY
					S.ID, S.TITLE
				ORDER BY S.C_SORT
				";
		}
		//echo "<pre>".$strSql."</pre>";
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $z;
	}
}
?>