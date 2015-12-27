<?

/***************************************
			Вопрос/поле
***************************************/


/**
 * <b>CFormField</b> - класс для работы с <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопросами</a> и <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">полями</a>. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/index.php
 * @author Bitrix
 */
class CAllFormField
{
public static 	function err_mess()
	{
		$module_id = "form";
		@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/install/version.php");
		return "<br>Module: ".$module_id." (".$arModuleVersion["VERSION"].")<br>Class: CAllFormField<br>File: ".__FILE__;
	}

	// список вопросов/полей

	/**
	* <p>Возвращает список <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопросов</a>/<a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">полей</a> веб-формы в виде объекта класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p>
	*
	*
	* @param int $form_id  ID веб-формы.</bod
	*
	* @param string $get_only_fields  Может принимать следующие значения: <ul> <li> <b>Y</b> - возвращаемый
	* список должен содержать только <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a> веб-формы; </li> <li> <b>N</b> -
	* возвращаемый список должен содержать только <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопросы</a> веб-формы; </li> <li>
	* <b>ALL</b> - возвращаемый список должен содержать и <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопросы</a> и <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a> веб-формы. </li> </ul>
	*
	* @param string &$by = "s_sort" Ссылка на переменную с полем для сортировки результирующего
	* списка, может принимать значения: <ul> <li> <b>s_id</b> - ID; </li> <li> <b>s_active</b> -
	* флаг активности; </li> <li> <b>s_sid</b> - символьный идентификатор; </li> <li>
	* <b>s_sort</b> - индекс сортировки; </li> <li> <b>s_title</b> - текст <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a> или заголовок <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a> веб-формы; </li> <li>
	* <b>s_comments</b> - служебный комментарий; </li> <li> <b>s_required</b> - флаг
	* обязательности ответа на <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопрос</a> веб-формы; </li> <li>
	* <b>s_in_results_table</b> - флаг включения в HTML таблицу результатов; </li> <li>
	* <b>s_in_excel_table</b> - флаг включения в Excel таблицу результатов; </li> <li>
	* <b>s_field_type</b> - тип <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a>
	* веб-формы. </li> </ul>
	*
	* @param string &$order = "asc" Ссылка на переменную с порядком сортировки, может принимать
	* значения: <ul> <li> <b>asc</b> - по возрастанию; </li> <li> <b>desc</b> - по убыванию.
	* </li> </ul>
	*
	* @param array $filter = array() Массив для фильтрации. Необязательный параметр. В массиве
	* допустимы следующие ключи: <ul> <li> <b>ID</b>* - ID <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>/<a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a> (по умолчанию будет
	* искаться точное совпадение); </li> <li> <b>ID_EXACT_MATCH</b> - если значение
	* равно "N", то при фильтрации по <b>ID</b> будет искаться вхождение; </li>
	* <li> <b>SID</b>* - символьный идентификатор <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>/<a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a> (по умолчанию будет
	* искаться точное совпадение); </li> <li> <b>SID_EXACT_MATCH</b> - если значение
	* равно "N", то при фильтрации по <b>SID</b> будет искаться вхождение; </li>
	* <li> <b>TITLE</b>* - текст <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопрос</a>
	* или заголовок <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a> веб-формы
	* (по умолчанию будет искаться вхождение); </li> <li> <b>TITLE_EXACT_MATCH</b> - если
	* значение равно "Y", то при фильтрации по <b>TITLE</b> будет искаться
	* точное совпадение; </li> <li> <b>COMMENTS</b>* - служебный комментарий (по
	* умолчанию будет искаться вхождение); </li> <li> <b>COMMENTS_EXACT_MATCH</b> - если
	* значение равно "Y", то при фильтрации по <b>COMMENTS</b> будет искаться
	* точное совпадение; </li> <li> <b>ACTIVE</b> - флаг активности [Y|N] </li> <li>
	* <b>IN_RESULTS_TABLE</b> - флаг включения в HTML таблицу результатов [Y|N]; </li> <li>
	* <b>IN_EXCEL_TABLE</b> - флаг включения в Excel таблицу результатов [Y|N]; </li> <li>
	* <b>IN_FILTER</b> - флаг включения в HTML таблицу результатов [Y|N]; </li> <li>
	* <b>REQUIRED</b> - флаг обязательности ответа на <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопрос</a> веб-формы [Y|N]. </li> </ul> *
	* - допускается сложная логика
	*
	* @param bool &$is_filtered  Ссылка на переменную, хранящую флаг отфильтрованности
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
	*   "ID"                    =&gt; "140 | 141",     // вопрос с ID=140 или с ID=141
	*   "ID_EXACT_MATCH"        =&gt; "Y",             // точное совпадение при фильтрации по ID
	*   "SID"                   =&gt; "VS_BIRTHDAY",   // символьный идентификатор
	*   "SID_EXACT_MATCH"       =&gt; "Y",             // точное совпадение с симв. идентификатором
	*   "TITLE"                 =&gt; "День рождения", // текст вопроса
	*   "TITLE_EXACT_MATCH"     =&gt; "N",             // вхождение при фильтрации по тексту вопроса
	*   "ACTIVE"                =&gt; "Y",             // флаг активности
	*   "IN_RESULTS_TABLE"      =&gt; "Y",             // флаг вхождение в HTML таблицу результатов
	*   "IN_EXCEL_TABLE"        =&gt; "N",             // флаг вхождения в Excel таблицу результатов
	*   "IN_FILTER"             =&gt; "Y",             // флаг вхождения в фильтр
	*   "REQUIRED"              =&gt; "Y",             // флаг обязательности ответа на <a href="/api_help/form/terms.php#question">вопрос</a>
	* );
	* 
	* // получим список всех вопросов веб-формы #4
	* $rsQuestions = <b>CFormField::GetList</b>(
	*     $FORM_ID, 
	*     "N", 
	*     $by="s_id", 
	*     $order="desc", 
	*     $arFilter, 
	*     $is_filtered
	*     );
	* while ($arQuestion = $rsQuestions-&gt;Fetch())
	* {
	*     echo "&lt;pre&gt;"; print_r($arQuestion); echo "&lt;/pre&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/index.php">Поля CFormField</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/getbyid.php">CFormField::GetByID</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/getbysid.php">CFormField::GetBySID</a> <br> </li>
	* </ul></b<a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/getlist.php
	* @author Bitrix
	*/
	public static 	function GetList($WEB_FORM_ID, $get_fields, &$by, &$order, $arFilter=Array(), &$is_filtered)
	{
		$err_mess = (CAllFormField::err_mess())."<br>Function: GetList<br>Line: ";
		global $DB, $strError;
		$WEB_FORM_ID = intval($WEB_FORM_ID);
		$str = "";
		if (strlen($get_fields)>0 && $get_fields!="ALL")
		{
			InitBVar($get_fields);
			$str = "and ADDITIONAL='$get_fields'";
		}
		$arSqlSearch = Array();
		$strSqlSearch = "";
		if (is_array($arFilter))
		{
			if(isset($arFilter["SID"]) && strlen($arFilter["SID"])>0)
			{
				$arFilter["VARNAME"] = $arFilter["SID"];
			}
			elseif(isset($arFilter["VARNAME"]) && strlen($arFilter["VARNAME"])>0)
			{
				$arFilter["SID"] = $arFilter["VARNAME"];
			}

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
					case "SID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("F.".$key, $val, $match);
						break;
					case "TITLE":
					case "COMMENTS":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("F.".$key, $val, $match);
						break;
					case "ACTIVE":
					case "IN_RESULTS_TABLE":
					case "IN_EXCEL_TABLE":
					case "IN_FILTER":
					case "REQUIRED":
						$arSqlSearch[] = ($val=="Y") ? "F.".$key."='Y'" : "F.".$key."='N'";
						break;
				}
			}
		}
		if ($by == "s_id")						$strSqlOrder = "ORDER BY F.ID";
		elseif ($by == "s_active")				$strSqlOrder = "ORDER BY F.ACTIVE";
		elseif ($by == "s_varname" ||
				$by == "s_sid")					$strSqlOrder = "ORDER BY F.SID";
		elseif ($by == "s_c_sort" ||
				$by == "s_sort")				$strSqlOrder = "ORDER BY F.C_SORT";
		elseif ($by == "s_title")				$strSqlOrder = "ORDER BY F.TITLE";
		elseif ($by == "s_comments")			$strSqlOrder = "ORDER BY F.COMMENTS";
		elseif ($by == "s_required")			$strSqlOrder = "ORDER BY F.REQUIRED";
		elseif ($by == "s_in_results_table")	$strSqlOrder = "ORDER BY F.IN_RESULTS_TABLE";
		elseif ($by == "s_in_excel_table")		$strSqlOrder = "ORDER BY F.IN_EXCEL_TABLE";
		elseif ($by == "s_field_type")			$strSqlOrder = "ORDER BY F.FIELD_TYPE";
		else
		{
				$by = "s_sort";
				$strSqlOrder = "ORDER BY F.C_SORT";
		}
		if ($order!="desc")
		{
			$strSqlOrder .= " asc ";
			$order="asc";
		}
		else
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT
				F.*,
				F.SID as VARNAME,
				".$DB->DateToCharFunction("F.TIMESTAMP_X")."	TIMESTAMP_X
			FROM
				b_form_field F
			WHERE
			$strSqlSearch
			$str
			and FORM_ID='$WEB_FORM_ID'
			$strSqlOrder
			";
		//echo "<pre>".$strSql."</pre>";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch));
		return $res;
	}


	/**
	* <p>Возвращает <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/index.php">параметры</a> <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">вопроса</a>/<a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">поля</a> в виде объекта класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p>
	*
	*
	* @param int $field_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">вопроса</a>/<a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">поля</a>.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $FIELD_ID = 140; // ID вопроса или поля веб-формы
	* $rsField = <b>CFormField::GetByID</b>($FIELD_ID);
	* $arField = $rsField-&gt;Fetch();
	* echo "&lt;pre&gt;"; print_r($arField); echo "&lt;/pre";
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/index.php">Поля CFormField</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/getbysid.php">CFormField::GetBySID</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/getlist.php">CFormField::GetList</a> </li> </ul></b<a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/getbyid.php
	* @author Bitrix
	*/
	public static 	function GetByID($ID)
	{
		$err_mess = (CAllFormField::err_mess())."<br>Function: GetByID<br>Line: ";
		global $DB;
		$ID = intval($ID);
		$strSql = "
			SELECT
				F.*,
				F.SID as VARNAME,
				".$DB->DateToCharFunction("F.TIMESTAMP_X")."	TIMESTAMP_X
			FROM b_form_field F
			WHERE F.ID = $ID
			";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}


	/**
	* <p>Возвращает <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/index.php">параметры</a> <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">вопроса</a>/<a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">поля</a> в виде объекта класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p>
	*
	*
	* @param int $field_sid  Символьный идентификатор <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">вопроса</a>/<a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">поля</a>.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $FIELD_SID = "VS_INTEREST"; // символьный идентификатор вопроса или поля веб-формы
	* $rsField = <b>CFormField::GetBySID</b>($FIELD_SID);
	* $arField = $rsField-&gt;Fetch();
	* echo "&lt;pre&gt;"; print_r($arField); echo "&lt;/pre";
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/index.php">Поля CFormField</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/getbyid.php">CFormField::GetByID</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/getlist.php">CFormField::GetList</a> </li> </ul></b<a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/getbysid.php
	* @author Bitrix
	*/
	public static 	function GetBySID($SID, $FORM_ID = false)
	{
		$FORM_ID = intval($FORM_ID);

		$err_mess = (CAllFormField::err_mess())."<br>Function: GetBySID<br>Line: ";
		global $DB;
		$strSql = "
			SELECT
				F.*,
				F.SID as VARNAME,
				".$DB->DateToCharFunction("F.TIMESTAMP_X")."	TIMESTAMP_X
			FROM b_form_field F
			WHERE F.SID = '".$DB->ForSql($SID,50)."'
			";
		if ($FORM_ID > 0)
			$strSql .= " AND F.FORM_ID='".$DB->ForSql($FORM_ID)."'";

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		return $res;
	}

public static 	function GetNextSort($WEB_FORM_ID)
	{
		global $DB;
		$err_mess = (CAllFormField::err_mess())."<br>Function: GetNextSort<br>Line: ";
		$WEB_FORM_ID = intval($WEB_FORM_ID);
		//InitBVar($additional);
		$strSql = "SELECT max(C_SORT) as MAX_SORT FROM b_form_field WHERE FORM_ID='$WEB_FORM_ID'";
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		$zr = $z->Fetch();
		return (intval($zr["MAX_SORT"])+100);
	}

	// копирует вопрос/поле

	/**
	* <p>Копирует <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопрос</a> или <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поле</a> веб-формы. Возвращает ID нового <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>/<a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a> в случае положительного результата, в противном случае - "false".</p>
	*
	*
	* @param int $field_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>/<a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a>, который необходимо
	* скопировать.
	*
	* @param string $check_rights = "Y" Флаг необходимости проверки <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#permissions">прав</a> текущего
	* пользователя. Возможны следующие значения: <ul> <li> <b>Y</b> - права
	* необходимо проверить; </li> <li> <b>N</b> - право не нужно проверять. </li>
	* </ul> Для копирования <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>/<a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a> необходимо обладать
	* нижеследующими <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#permissions#module">правами</a>: <ol> <li> <b>[25]
	* просмотр параметров веб-формы</b> на веб-форму, из которой идет
	* копирование; </li> <li> <b>[30] полный доступ</b> на веб-форму, в которую
	* копируется </li> </ol> Параметр необязательный. По умолчанию - "Y"
	* (права необходимо проверить).
	*
	* @param mixed $form_id = false ID веб-формы, в которую необходимо скопировать <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопрос</a>/<a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поле</a>.<br><br> Необязательный
	* параметр. По умолчанию - "false" (текущая веб-форма).
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $FIELD_ID = 140; // ID вопроса
	* // скопируем вопрос
	* if ($NEW_FIELD_ID=<b>CFormField::Copy</b>($FIELD_ID))
	* {
	*     echo "Вопрос #140 успешно скопирован в новый вопрос #".$NEW_FIELD_ID;
	* }
	* else
	* {
	*     // выведем текст ошибки
	*     global $strError;
	*     echo $strError;
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/copy.php">CForm::Copy</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformanswer/copy.php">CFormAnswer::Copy</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformstatus/copy.php">CFormStatus::Copy</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/copy.php
	* @author Bitrix
	*/
	public static 	function Copy($ID, $CHECK_RIGHTS="Y", $NEW_FORM_ID=false)
	{
		global $DB, $strError;
		$err_mess = (CAllFormField::err_mess())."<br>Function: Copy<br>Line: ";
		$ID = intval($ID);
		$NEW_FORM_ID = intval($NEW_FORM_ID);
		$rsField = CFormField::GetByID($ID);
		if ($arField = $rsField->Fetch())
		{
			$RIGHT_OK = "N";
			if ($CHECK_RIGHTS!="Y" || CForm::IsAdmin()) $RIGHT_OK="Y";
			else
			{
				$F_RIGHT = CForm::GetPermission($arField["FORM_ID"]);
				// если имеем право на просмотр параметров формы
				if ($F_RIGHT>=25)
				{
					// если задана новая форма
					if ($NEW_FORM_ID>0)
					{
						$NEW_F_RIGHT = CForm::GetPermission($NEW_FORM_ID);
						// если имеем полный доступ на новую форму
						if ($NEW_F_RIGHT>=30) $RIGHT_OK = "Y";
					}
					elseif ($F_RIGHT>=30) // иначе если имеем полный доступ на исходную форму
					{
						$RIGHT_OK = "Y";
					}
				}
			}

			// если права проверили то
			if ($RIGHT_OK=="Y")
			{
				// символьный код поля
				if (!$NEW_FORM_ID)
				{
					while(true)
					{
						// change: SID изменяем только если для старой формы. Требование уникальности снято.
						$SID = $arField["SID"];
						if (strlen($SID) > 44) $SID = substr($SID, 0, 44);
						$SID .= "_".RandString(5);


						$strSql = "SELECT 'x' FROM b_form WHERE SID='".$DB->ForSql($SID,50)."'";
						$z = $DB->Query($strSql, false, $err_mess.__LINE__);
						if (!($zr = $z->Fetch()))
						{
							$strSql = "SELECT 'x' FROM b_form_field WHERE SID='".$DB->ForSql($SID,50)."' AND FORM_ID='".$arField["FORM_ID"]."'";
							$t = $DB->Query($strSql, false, $err_mess.__LINE__);
							if (!($tr = $t->Fetch())) break;
						}
					}
				}
				else
				{
					$SID = $arField["SID"];
				}


				// копируем
				$arFields = array(
					"FORM_ID"				=> ($NEW_FORM_ID>0) ? $NEW_FORM_ID : $arField["FORM_ID"],
					"ACTIVE"				=> $arField["ACTIVE"],
					"TITLE"					=> $arField["TITLE"],
					"TITLE_TYPE"			=> $arField["TITLE_TYPE"],
					"SID"					=> $SID,
					"C_SORT"				=> $arField["C_SORT"],
					"ADDITIONAL"			=> $arField["ADDITIONAL"],
					"REQUIRED"				=> $arField["REQUIRED"],
					"IN_FILTER"				=> $arField["IN_FILTER"],
					"IN_RESULTS_TABLE"		=> $arField["IN_RESULTS_TABLE"],
					"IN_EXCEL_TABLE"		=> $arField["IN_EXCEL_TABLE"],
					"FIELD_TYPE"			=> $arField["FIELD_TYPE"],
					"COMMENTS"				=> $arField["COMMENTS"],
					"FILTER_TITLE"			=> $arField["FILTER_TITLE"],
					"RESULTS_TABLE_TITLE"	=> $arField["RESULTS_TABLE_TITLE"],
					);

				// картинка
				if (intval($arField["IMAGE_ID"])>0)
				{
					$arIMAGE = CFile::MakeFileArray(CFile::CopyFile($arField["IMAGE_ID"]));
					$arIMAGE["MODULE_ID"] = "form";
					$arFields["arIMAGE"] = $arIMAGE;
				}

				// фильтр
				$z = CFormField::GetFilterList($arField["FORM_ID"], Array("FIELD_ID" => $ID, "FIELD_ID_EXACT_MATCH" => "Y"));
				while ($zr = $z->Fetch())
				{
					if ($arField["ADDITIONAL"]!="Y") $arFields["arFILTER_".$zr["PARAMETER_NAME"]][] = $zr["FILTER_TYPE"];
					elseif ($zr["PARAMETER_NAME"]=="USER") $arFields["arFILTER_FIELD"][] = $zr["FILTER_TYPE"];
				}
				//echo "<pre>"; print_r($arFields); echo "</pre>";
				$NEW_ID = CFormField::Set($arFields);
				if (intval($NEW_ID)>0)
				{
					if ($arField["ADDITIONAL"]!="Y")
					{
						// ответы
						$rsAnswer = CFormAnswer::GetList($ID, $by='ID', $order='ASC', array(), $is_filtered);
						while ($arAnswer = $rsAnswer->Fetch())
							CFormAnswer::Copy($arAnswer["ID"], $NEW_ID);

						// валидаторы
						$dbValidators = CFormValidator::GetList($ID);
						while ($arVal = $dbValidators->Fetch())
						{
							CFormValidator::Set($arField['FORM_ID'], $NEW_ID, $arVal['NAME'], $arVal['PARAMS'], $arVal['C_SORT']);
						}
					}
				}
				return $NEW_ID;
			}
			else $strError .= GetMessage("FORM_ERROR_ACCESS_DENIED")."<br>";
		}
		else $strError .= GetMessage("FORM_ERROR_FIELD_NOT_FOUND")."<br>";
		return false;
	}

	// удаляет вопрос/поле

	/**
	* <p>Удаляет <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопрос</a>/<a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поле</a> и все ответы на него из <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результатов</a>. Возвращает "true" в случае положительного результата, и "false" - в противном случае.</p>
	*
	*
	* @param int $field_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>/<a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a>.
	*
	* @param string $check_rights = "Y" Флаг необходимости проверки <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#permissions">прав</a> текущего
	* пользователя. Возможны следующие значения: <ul> <li> <b>Y</b> - права
	* необходимо проверить; </li> <li> <b>N</b> - право не нужно проверять. </li>
	* </ul> Для успешного выполнения данной операции необходимо иметь <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#permissions#form">право</a> <b>[30] Полный
	* доступ</b> на веб-форму, к которой принадлежит
	* <i>field_id</i>.<br><br>Параметр необязательный. По умолчанию - "Y" (права
	* необходимо проверить).
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $FIELD_ID = 140;
	* // удалим вопрос #140
	* if (<b>CFormField::Delete</b>($FIELD_ID))
	* {
	*     echo "Вопрос #140 удален.";
	* }
	* else
	* {
	*     // выведем текст ошибки
	*     global $strError;
	*     echo $strError;
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/delete.php">CForm::Delete</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformanswer/delete.php">CFormAnswer::Delete</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformstatus/delete.php">CFormStatus::Delete</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/delete.php">CFormResult::Delete</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/delete.php
	* @author Bitrix
	*/
	public static 	function Delete($ID, $CHECK_RIGHTS="Y")
	{
		global $DB, $strError;
		$err_mess = (CAllFormField::err_mess())."<br>Function: Delete<br>Line: ";
		$ID = intval($ID);

		$rsField = CFormField::GetByID($ID);
		if ($arField = $rsField->Fetch())
		{
			$WEB_FORM_ID = intval($arField["FORM_ID"]);

			$F_RIGHT = ($CHECK_RIGHTS!="Y") ? 30 : CForm::GetPermission($WEB_FORM_ID);
			if ($F_RIGHT>=30)
			{
				// очищаем результаты по данному полю
				CFormField::Reset($ID, $CHECK_RIGHTS);
				// clear field validators
				CFormValidator::Clear($ID);

				// удаляем изображения поля
				$strSql = "SELECT IMAGE_ID FROM b_form_field WHERE ID='$ID' and IMAGE_ID>0";
				$z = $DB->Query($strSql, false, $err_mess.__LINE__);
				while ($zr = $z->Fetch())
					CFile::Delete($zr["IMAGE_ID"]);

				// удаляем варианты ответов на поле формы
				$DB->Query("DELETE FROM b_form_answer WHERE FIELD_ID='$ID'", false, $err_mess.__LINE__);

				// удаляем привязку к типам фильтра
				$DB->Query("DELETE FROM b_form_field_filter WHERE FIELD_ID='$ID'", false, $err_mess.__LINE__);

				// удаляем само поле
				$DB->Query("DELETE FROM b_form_field WHERE ID='$ID'", false, $err_mess.__LINE__);

				return true;
			}
			else $strError .= GetMessage("FORM_ERROR_ACCESS_DENIED")."<br>";
		}
		else $strError .= GetMessage("FORM_ERROR_FIELD_NOT_FOUND")."<br>";
		return false;
	}

	// обнуляем результаты по вопросу/полю

	/**
	* <p>Удаляет все значения ответов из <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результатов</a> по заданному <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопросу</a>/<a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">полю</a>. Возвращает "true" в случае положительного результата, и "false" - в противном случае.</p>
	*
	*
	* @param int $field_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>/<a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a>.
	*
	* @param string $check_rights = "Y" Флаг необходимости проверки <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#permissions">прав</a> текущего
	* пользователя. Возможны следующие значения: <ul> <li> <b>Y</b> - права
	* необходимо проверить; </li> <li> <b>N</b> - право не нужно проверять. </li>
	* </ul> Для успешного выполнения данной операции необходимо иметь <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#permissions#form">право</a> <b>[30] Полный
	* доступ</b> на веб-форму, к которой принадлежит
	* <i>field_id</i>.<br><br>Параметр необязательный. По умолчанию - "Y" (права
	* необходимо проверить).
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $FIELD_ID = 4;
	* // удалим все ответы из результатов на вопрос с ID=140
	* if (<b>CFormField::Reset</b>($FIELD_ID))
	* {
	*     echo "Операция успешна.";
	* }
	* else
	* {
	*     // выведем текст ошибки
	*     global $strError;
	*     echo $strError;
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/reset.php">CForm::Reset</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/reset.php">CFormResult::Reset</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/reset.php
	* @author Bitrix
	*/
	public static 	function Reset($ID, $CHECK_RIGHTS="Y")
	{
		global $DB, $strError;
		$err_mess = (CAllFormField::err_mess())."<br>Function: Reset<br>Line: ";
		$ID = intval($ID);

		$rsField = CFormField::GetByID($ID);
		if ($arField = $rsField->Fetch())
		{
			$WEB_FORM_ID = intval($arField["FORM_ID"]);

			$F_RIGHT = ($CHECK_RIGHTS!="Y") ? 30 : CForm::GetPermission($WEB_FORM_ID);
			if ($F_RIGHT>=30)
			{
				// удаляем ответы по данному полю
				$DB->Query("DELETE FROM b_form_result_answer WHERE FIELD_ID='".$ID."'", false, $err_mess.__LINE__);

				return true;
			}
			else $strError .= GetMessage("FORM_ERROR_ACCESS_DENIED")."<br>";
		}
		else $strError .= GetMessage("FORM_ERROR_FIELD_NOT_FOUND")."<br>";
		return false;
	}

	public static function GetFilterTypeList(&$arrUSER, &$arrANSWER_TEXT, &$arrANSWER_VALUE, &$arrFIELD)
	{
		$arrUSER = array(
			"reference_id" => array(
				"text",
				"integer",
				"date",
				"exist",
				),
			"reference" => array(
				GetMessage("FORM_TEXT_FIELD"),
				GetMessage("FORM_NUMERIC_INTERVAL"),
				GetMessage("FORM_DATE_INTERVAL"),
				GetMessage("FORM_EXIST_FLAG"),
				)
			);
		$arrANSWER_TEXT = array(
			"reference_id" => array(
				"text",
				"integer",
				"dropdown",
				"exist",
				),
			"reference" => array(
				GetMessage("FORM_TEXT_FIELD"),
				GetMessage("FORM_NUMERIC_INTERVAL"),
				GetMessage("FORM_DROPDOWN_LIST"),
				GetMessage("FORM_EXIST_FLAG"),
				)
			);
		$arrANSWER_VALUE = array(
			"reference_id" => array(
				"text",
				"integer",
				"dropdown",
				"exist",
				),
			"reference" => array(
				GetMessage("FORM_TEXT_FIELD"),
				GetMessage("FORM_NUMERIC_INTERVAL"),
				GetMessage("FORM_DROPDOWN_LIST"),
				GetMessage("FORM_EXIST_FLAG"),
				)
			);
		$arrFIELD = array(
			"reference_id" => array(
				"text",
				"integer",
				"date",
				"exist",
				),
			"reference" => array(
				GetMessage("FORM_TEXT_FIELD"),
				GetMessage("FORM_NUMERIC_INTERVAL"),
				GetMessage("FORM_DATE_INTERVAL"),
				GetMessage("FORM_EXIST_FLAG"),
				)
			);
	}

public static 	function GetTypeList()
	{
		$arr = array(
			"reference_id" => array(
				"text",
				"integer",
				"date"),
			"reference" => array(
				GetMessage("FORM_FIELD_TEXT"),
				GetMessage("FORM_FIELD_INTEGER"),
				GetMessage("FORM_FIELD_DATE")
				)
			);
		return $arr;
	}

public static 	function GetFilterList($WEB_FORM_ID, $arFilter=Array())
	{
		$err_mess = (CAllFormField::err_mess())."<br>Function: GetFilterList<br>Line: ";
		global $DB;
		$WEB_FORM_ID = intval($WEB_FORM_ID);
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
					case "FIELD_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("F.ID",$val,$match);
						break;
					case "FIELD_SID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("F.SID",$val,$match);
					break;
					case "ACTIVE":
						$arSqlSearch[] = ($val=="Y") ? "F.ACTIVE='Y'" : "F.ACTIVE='N'";
						break;
					case "FILTER_TYPE":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("L.FILTER_TYPE", $val, $match);
						break;
					case "PARAMETER_NAME":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("L.PARAMETER_NAME", $val, $match);
						break;
				}
			}
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT
				F.*,
				F.SID as VARNAME,
				L.PARAMETER_NAME,
				L.FILTER_TYPE
			FROM
				b_form_field F,
				b_form_field_filter	L
			WHERE
			$strSqlSearch
			and F.FORM_ID = $WEB_FORM_ID
			and F.IN_FILTER = 'Y'
			and L.FIELD_ID = F.ID
			ORDER BY F.C_SORT, L.PARAMETER_NAME, L.FILTER_TYPE desc
			";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}

	// проверка вопроса/поля
public static 	function CheckFields(&$arFields, $FIELD_ID, $CHECK_RIGHTS="Y")
	{
		$err_mess = (CAllFormField::err_mess())."<br>Function: CheckFields<br>Line: ";
		global $DB, $strError;
		$str = "";
		$FIELD_ID = intval($FIELD_ID);
		$FORM_ID = intval($arFields["FORM_ID"]);
		if ($FORM_ID<=0) $str .= GetMessage("FORM_ERROR_FORM_ID_NOT_DEFINED")."<br>";
		else
		{
			$RIGHT_OK = "N";
			if ($CHECK_RIGHTS!="Y" || CForm::IsAdmin()) $RIGHT_OK = "Y";
			else
			{
				$F_RIGHT = CForm::GetPermission($FORM_ID);
				if ($F_RIGHT>=30) $RIGHT_OK = "Y";
			}

			if ($RIGHT_OK=="Y")
			{
				if (strlen(trim($arFields["SID"]))>0) $arFields["VARNAME"] = $arFields["SID"];
				elseif (strlen($arFields["VARNAME"])>0) $arFields["SID"] = $arFields["VARNAME"];

				if ($FIELD_ID<=0 && !is_set($arFields, 'ADDITIONAL'))
					$arFields['ADDITIONAL'] = 'N';

				if ($FIELD_ID<=0 || ($FIELD_ID>0 && is_set($arFields, "SID")))
				{
					if (strlen(trim($arFields["SID"]))<=0) $str .= GetMessage("FORM_ERROR_FORGOT_SID")."<br>";
					if (preg_match("/[^A-Za-z_01-9]/",$arFields["SID"])) $str .= GetMessage("FORM_ERROR_INCORRECT_SID")."<br>";
					else
					{
						$strSql = "SELECT ID, ADDITIONAL FROM b_form_field WHERE SID='".$DB->ForSql(trim($arFields["SID"]),50)."' and ID<>'".$FIELD_ID."' AND FORM_ID='".$DB->ForSql($arFields["FORM_ID"])."'";
						$z = $DB->Query($strSql, false, $err_mess.__LINE__);
						if ($zr = $z->Fetch())
						{
							$s = ($zr["ADDITIONAL"]=="Y") ?
								str_replace("#TYPE#", GetMessage("FORM_TYPE_FIELD"), GetMessage("FORM_ERROR_WRONG_SID")) :
								str_replace("#TYPE#", GetMessage("FORM_TYPE_QUESTION"), GetMessage("FORM_ERROR_WRONG_SID"));
							$s = str_replace("#ID#",$zr["ID"],$s);
							$str .= $s."<br>";
						}
						else
						{
							$strSql = "SELECT ID FROM b_form WHERE SID='".$DB->ForSql(trim($arFields["SID"]),50)."'";
							$z = $DB->Query($strSql, false, $err_mess.__LINE__);
							if ($zr = $z->Fetch())
							{
								$s = str_replace("#TYPE#", GetMessage("FORM_TYPE_FORM"), GetMessage("FORM_ERROR_WRONG_SID"));
								$s = str_replace("#ID#",$zr["ID"],$s);
								$str .= $s."<br>";
							}
						}
					}
				}

				$str .= CFile::CheckImageFile($arFields["arIMAGE"]);
			}
			else $str .= GetMessage("FORM_ERROR_ACCESS_DENIED");
		}

		$strError .= $str;
		if (strlen($str)>0) return false; else return true;
	}

	// добавление/обновление вопроса/поля

	/**
	* <p>Добавляет новый <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопрос</a>/<a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поле</a> или обновляет существующий. Возвращает ID обновленного или добавленного <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>/<a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a> в случае положительного результата, в противном случае - "false".</p>
	*
	*
	* @param array $fields  Массив значений, в качестве ключей массива допустимы: <ul> <li>
	* <b>SID</b><font color="red">*</font> - символьный идентификатор <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>/<a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a>; </li> <li> <b>FORM_ID</b><font
	* color="red">*</font> - ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">веб-формы</a>; </li>
	* <li> <b>ACTIVE</b> - флаг активности; допустимы следующие значения: <ul> <li>
	* <b>Y</b> - <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопрос</a>/<a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поле</a> активен; </li> <li> <b>N</b> - <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопрос</a>/<a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поле</a> не активен (по умолчанию).
	* </li> </ul> </li> <li> <b>ADDITIONAL</b> - допустимы следующие значения: <ul> <li> <b>Y</b> -
	* данная запись является <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">полем</a> веб-формы; </li> <li> <b>N</b> -
	* данная запись является <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопросом</a> веб-формы (по
	* умолчанию). </li> </ul> </li> <li> <b>FIELD_TYPE</b><font color="green">*</font> - тип <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a>, допустимые следующие
	* значения: <ul> <li> <b>text</b> - текст; </li> <li> <b>integer</b> - число; </li> <li> <b>date</b> -
	* дата. </li> </ul> </li> <li> <b>TITLE</b> - текст <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a> либо заголовок <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a>. </li> <li> <b>TITLE_TYPE</b><font
	* color="green">*</font> - тип <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>;
	* допустимы следующие значения: <ul> <li> <b>text</b> - текст; </li> <li> <b>html</b> -
	* HTML код. </li> </ul> </li> <li> <b>C_SORT</b> - порядок сортировки; </li> <li> <b>REQUIRED</b><font
	* color="green">*</font> - флаг обязательности ответа на <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопрос</a>: <ul> <li> <b>Y</b> - ответ на
	* данный <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопрос</a> обязателен;
	* </li> <li> <b>N</b> - ответ на данный <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопрос</a> обязателен (по
	* умолчанию). </li> </ul> </li> <li> <b>FILTER_TITLE</b> - подпись к полю фильтра; </li> <li>
	* <b>IN_RESULTS_TABLE</b> - флаг вхождения в HTML таблицу результатов: <ul> <li> <b>Y</b>
	* - ответ на данный <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопрос</a>
	* либо значения <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a> веб-формы
	* отражены в HTML таблице результатов; </li> <li> <b>N</b> - ответ на данный <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопрос</a> либо значения <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a> веб-формы отражены в HTML
	* таблице результатов (по умолчанию). </li> </ul> </li> <li> <b>IN_EXCEL_TABLE</b> - флаг
	* вхождения в Excel таблицу <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результатов</a>: <ul> <li> <b>Y</b> -
	* ответ на данный <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопрос</a>
	* либо значения <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a> веб-формы
	* отражены в Excel таблице <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результатов</a> </li> <li> <b>N</b> -
	* ответ на данный <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопрос</a>
	* либо значения <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a> веб-формы
	* отражены в Excel таблице <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результатов</a> (по умолчанию).
	* </li> </ul> </li> <li> <b>RESULTS_TABLE_TITLE</b> - заголовок столбца в таблицах
	* результатов; </li> <li> <b>COMMENTS</b> - служебный комментарий; </li> <li>
	* <b>arIMAGE</b><font color="green">**</font> - массив, описывающий изображение <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>, допустимы
	* следующие ключи этого массива: <ul> <li> <b>name</b> - имя файла; </li> <li>
	* <b>size</b> - размер файла; </li> <li> <b>tmp_name</b> - временный путь на сервере;
	* </li> <li> <b>type</b> - тип загружаемого файла; </li> <li> <b>del</b> - если значение
	* равно "Y", то изображение будет удалено; </li> <li> <b>MODULE_ID</b> -
	* идентификатор модуля "Веб-формы" - <b>form</b> </li> </ul> </li> <li> <b>arANSWER</b><font
	* color="green">**</font> - массив, описывающий <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответы</a> на <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопрос</a>, со следующей
	* структурой: <pre>Array ( [0] =&gt; Array ( [ID] =&gt; ID [DELETE] =&gt; флаг необходимости
	* удаления [Y|N] [MESSAGE] =&gt; параметр <font color="green">ANSWER_TEXT</font> [VALUE] =&gt;
	* параметр <font color="red">ANSWER_VALUE</font> [C_SORT] =&gt; порядок сортировки [ACTIVE] =&gt;
	* флаг активности [Y|N] [FIELD_TYPE] =&gt; тип, допустимы следующие значения:
	* <b>text</b> - однострочное текстовое поле <b>textarea</b> - многострочное
	* текстовое поле <b>radio</b>* - переключатель одиночного выбора
	* (radio-кнопка) <b>checkbox</b>* - флаг множественного выбора (checkbox) <b>dropdown</b>* -
	* элемент выпадающего списка одиночного выбора <b>multiselect</b>* -
	* элемент списка множественного выбора <b>date</b> - поле для ввода даты
	* <b>image</b> - поле для загрузки изображения <b>file</b> - поле для загрузки
	* произвольного файла <b>password</b> - поле для ввода пароля [FIELD_WIDTH] =&gt;
	* ширина поля ввода [FIELD_HEIGHT] =&gt; высота поля ввода [FIELD_PARAM] =&gt;
	* дополнительные параметры; допустимо использование любого HTML
	* кода; для типов помеченных символом * допустимо использование
	* следующих зарезервированных строк: <b>checked</b> - <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответ</a> будет выбран (отмечен)
	* по умолчанию (синоним - <b>selected</b>) <b>not_answer</b> - выбор данного <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> не означает, что был
	* дан ответ на вопрос (как правило это первый элемент выпадающего
	* списка и важно при <b>REQUIRED</b>="Y") ) [1] =&gt; массив описывающий
	* следующий <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответ</a> ... )</pre> </li>
	* <li> <b>arFILTER_USER</b><font color="green">**</font> - массив полей фильтра для
	* фильтрации по значению <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>, введенному с
	* клавиатуры пользователем при заполнении веб-формы; в данном
	* массиве допустимы следующие значения: <ul> <li> <b>text</b> - <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/gettextfilter.php">текстовое поле</a>
	* фильтра; </li> <li> <b>integer</b> - поля фильтра для <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getnumberfilter.php">числового
	* интервала</a>; </li> <li> <b>date</b> - поля фильтра для <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getdatefilter.php">интервала дат</a>; </li> <li>
	* <b>exist</b> - поле для фильтрации по <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getexistflagfilter.php">факту
	* существования</a> введенного ответа. </li> </ul> </li> <li>
	* <b>arFILTER_ANSWER_TEXT</b><font color="green">**</font> - массив полей фильтра для
	* фильтрации по параметру <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> <font color="green">ANSWER_TEXT</font>;
	* в данном массиве допустимы следующие значения: <ul> <li> <b>text</b> - <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/gettextfilter.php">текстовое поле</a>
	* фильтра; </li> <li> <b>integer</b> - поля фильтра для <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getnumberfilter.php">числового
	* интервала</a>; </li> <li> <b>dropdown</b> - <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getdropdownfilter.php">выпадающий список
	* одиночного выбора</a>; </li> <li> <b>exist</b> - поле для фильтрации по <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getexistflagfilter.php">факту
	* существования</a>. </li> </ul> </li> <li> <b>arFILTER_ANSWER_VALUE</b><font color="green">**</font> -
	* массив полей фильтра для фильтрации по параметру <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> <font color="red">ANSWER_VALUE</font>; в
	* данном массиве допустимы следующие значения: <ul> <li> <b>text</b> - <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/gettextfilter.php">текстовое поле</a>
	* фильтра; </li> <li> <b>integer</b> - поля фильтра для <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getnumberfilter.php">числового
	* интервала</a>; </li> <li> <b>dropdown</b> - <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getdropdownfilter.php">выпадающий список
	* одиночного выбора</a>; </li> <li> <b>exist</b> - поле для фильтрации по <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getexistflagfilter.php">факту
	* существования</a>. </li> </ul> </li> <li> <b>arFILTER_FIELD</b><font color="green">*</font> - массив
	* полей фильтра для фильтрации по значению <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля веб-формы</a>: <ul> <li> <b>text</b> - <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/gettextfilter.php">текстовое поле</a>
	* фильтра; </li> <li> <b>integer</b> - поля фильтра для <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getnumberfilter.php">числового
	* интервала</a>; </li> <li> <b>date</b> - поля фильтра для <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getdatefilter.php">интервала дат</a>. </li> </ul>
	* </li> </ul> <br><font color="red">*</font> - обязательно к заполнению; <br><font
	* color="green">*</font> - заполняется <b>только</b> для <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">полей веб-формы</a>; <br><font
	* color="green">**</font> - заполняется <b>только</b> для <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопросов веб-формы</a>.
	*
	* @param mixed $field_id = false ID обновляемого <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>/<a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a>.<br>Параметр
	* необязательный. По умолчанию - "false" (добавление нового <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>/<a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a>).
	*
	* @param string $check_rights = "Y" Флаг необходимости проверки прав текущего пользователя.
	* Возможны следующие значения: <ul> <li> <b>Y</b> - права необходимо
	* проверить; </li> <li> <b>N</b> - право не нужно проверять. </li> </ul> Для
	* добавления нового <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>/<a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a> или обновления их
	* параметров необходимо иметь право <b>[30] Полный доступ</b> на
	* веб-форму указанную в <i>fields</i>["<b>FORM_ID</b>"].<br><br>Параметр
	* необязательный. По умолчанию - "Y" (права необходимо проверить).
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* //<************************************************
	*          Добавление <a href="/api_help/form/terms.php#question">вопроса</a> веб-формы
	* ************************************************>//
	* 
	* // создадим массив описывающий изображение 
	* // находящееся в файле на сервере
	* $arIMAGE = CFile::MakeFileArray($_SERVER["DOCUMENT_ROOT"]."/images/question.gif");
	* $arIMAGE["MODULE_ID"] = "form";
	* 
	* // формируем массив ответов
	* $arANSWER = array();
	* 
	* $arANSWER[] = array(
	*     "MESSAGE"     =&gt; "да",                           // параметр <font color="green">ANSWER_TEXT</font>
	*     "C_SORT"      =&gt; 100,                            // порядок фортировки 
	*     "ACTIVE"      =&gt; "Y",                            // флаг активности
	*     "FIELD_TYPE"  =&gt; "radio",                        // тип ответа
	*     "FIELD_PARAM" =&gt; "checked class=\"inputradio\""  // параметры ответа
	*     );
	* 
	* $arANSWER[] = array(
	*     "MESSAGE"     =&gt; "нет",
	*     "C_SORT"      =&gt; 200,
	*     "ACTIVE"      =&gt; "Y",
	*     "FIELD_TYPE"  =&gt; "radio"
	*     );
	* 
	* // формируем массив полей
	* $arFields = array( 
	*     "FORM_ID"              =&gt; 4,                     // ID веб-формы
	*     "ACTIVE"               =&gt; "Y",                     // флаг активности
	*     "TITLE"                =&gt; "Вы женаты/замужем ?", // текст вопроса
	*     "TITLE_TYPE"           =&gt; "text",                // тип текста вопроса
	*     "SID"                  =&gt; "VS_MARRIED",          // символьный идентификатор вопроса
	*     "C_SORT"               =&gt; 400,                   // порядок сортировки
	*     "ADDITIONAL"           =&gt; "N",                   // мы добавляем <b>вопрос</b> веб-формы
	*     "REQUIRED"             =&gt; "Y",                   // ответ на данный вопрос обязателен
	*     "IN_RESULTS_TABLE"     =&gt; "Y",                   // добавить в HTML таблицу результатов
	*     "IN_EXCEL_TABLE"       =&gt; "Y",                   // добавить в Excel таблицу результатов
	*     "FILTER_TITLE"         =&gt; "Женат/замужем",       // подпись к полю фильтра
	*     "RESULTS_TABLE_TITLE"  =&gt; "Женат/замужем",       // заголовок столбца фильтра
	*     "arIMAGE"              =&gt; $arIMAGE,              // изображение вопроса
	*     "arFILTER_ANSWER_TEXT" =&gt; array("dropdown"),     // тип фильтра по <font color="green">ANSWER_TEXT</font>
	*     "arANSWER"             =&gt; $arANSWER,             // набор <a href="/api_help/form/terms.php#answer">ответов</a>
	* );
	* 
	* // добавим новый вопрос
	* $NEW_ID = <b>CFormField::Set</b>($arFields);
	* if ($NEW_ID&gt;0) echo "Добавлен вопрос с ID=".$NEW_ID;
	* else // ошибка
	* {
	*     // выводим текст ошибки
	*     global $strError;
	*     echo $strError;
	* }
	* ?&gt;
	* 
	* 
	* &lt;?
	* //<************************************************
	*           Добавление <a href="/api_help/form/terms.php#field">поля</a> веб-формы
	* ************************************************>//
	* 
	* $arFields = array( 
	*     "FORM_ID"             =&gt; 4
	*     "ACTIVE"              =&gt; "Y",
	*     "TITLE"               =&gt; "Рассчитанная стоимость",
	*     "SID"                 =&gt; "VS_PRICE",
	*     "C_SORT"              =&gt; 1000,
	*     "ADDITIONAL"          =&gt; "Y",
	*     "IN_RESULTS_TABLE"    =&gt; "Y",
	*     "IN_EXCEL_TABLE"      =&gt; "Y",
	*     "FIELD_TYPE"          =&gt; "text",
	*     "FILTER_TITLE"        =&gt; "Стоимость",
	*     "RESULTS_TABLE_TITLE" =&gt; "Стоимость",
	*     "arFILTER_FIELD"      =&gt; array("text")
	*     );
	* 
	* // добавим новое поле
	* $NEW_ID = <b>CFormField::Set</b>($arFields);
	* if ($NEW_ID&gt;0) echo "Добавлено поле с ID=".$NEW_ID;
	* else // ошибка
	* {
	*     // выводим текст ошибки
	*     global $strError;
	*     echo $strError;
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/index.php">Поля CFormField</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/form/permissions.php#form">Права на веб-форму</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cfile/makefilearray.php">CFile::MakeFileArray</a> <br>
	* </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/set.php
	* @author Bitrix
	*/
	public static 	function Set($arFields, $FIELD_ID=false, $CHECK_RIGHTS="Y", $UPDATE_FILTER="Y")
	{
		$err_mess = (CAllFormField::err_mess())."<br>Function: Set<br>Line: ";
		global $DB;

		if (CFormField::CheckFields($arFields, $FIELD_ID, $CHECK_RIGHTS))
		{
			$arFields_i = array();

			if (strlen(trim($arFields["SID"]))>0) $arFields["VARNAME"] = $arFields["SID"];
			elseif (strlen($arFields["VARNAME"])>0) $arFields["SID"] = $arFields["VARNAME"];

			$arFields_i["TIMESTAMP_X"] = $DB->GetNowFunction();

			if (is_set($arFields, "ACTIVE"))
				$arFields_i["ACTIVE"] = ($arFields["ACTIVE"]=="Y") ? "'Y'" : "'N'";

			if (is_set($arFields, "TITLE"))
				$arFields_i["TITLE"] = "'".$DB->ForSql($arFields["TITLE"], 2000)."'";

			if (is_set($arFields, "TITLE_TYPE"))
				$arFields_i["TITLE_TYPE"] = ($arFields["TITLE_TYPE"]=="html") ? "'html'" : "'text'";

			if (is_set($arFields, "SID"))
				$arFields_i["SID"] = "'".$DB->ForSql($arFields["SID"],50)."'";

			if (is_set($arFields, "C_SORT"))
				$arFields_i["C_SORT"] = "'".intval($arFields["C_SORT"])."'";

			if (is_set($arFields, "ADDITIONAL"))
				$arFields_i["ADDITIONAL"] = ($arFields["ADDITIONAL"]=="Y") ? "'Y'" : "'N'";

			if (is_set($arFields, "REQUIRED"))
				$arFields_i["REQUIRED"] = ($arFields["REQUIRED"]=="Y") ? "'Y'" : "'N'";

			if (is_set($arFields, "IN_RESULTS_TABLE"))
				$arFields_i["IN_RESULTS_TABLE"] = ($arFields["IN_RESULTS_TABLE"]=="Y") ? "'Y'" : "'N'";

			if (is_set($arFields, "IN_EXCEL_TABLE"))
				$arFields_i["IN_EXCEL_TABLE"] = ($arFields["IN_EXCEL_TABLE"]=="Y") ? "'Y'" : "'N'";

			if (is_set($arFields, "FIELD_TYPE"))
				$arFields_i["FIELD_TYPE"] = "'".$DB->ForSql($arFields["FIELD_TYPE"],50)."'";

			if (is_set($arFields, "COMMENTS"))
				$arFields_i["COMMENTS"] = "'".$DB->ForSql($arFields["COMMENTS"],2000)."'";

			if (is_set($arFields, "FILTER_TITLE"))
				$arFields_i["FILTER_TITLE"] = "'".$DB->ForSql($arFields["FILTER_TITLE"],2000)."'";

			if (is_set($arFields, "RESULTS_TABLE_TITLE"))
				$arFields_i["RESULTS_TABLE_TITLE"] = "'".$DB->ForSql($arFields["RESULTS_TABLE_TITLE"],2000)."'";

			// fcuk knows why he wrote it. maybe for some checking. but it's absolutely useless.
			//$z = $DB->Query("SELECT IMAGE_ID FROM b_form_field WHERE ID='$FIELD_ID'", false, $err_mess.__LINE__);
			//$zr = $z->Fetch();

			if (strlen($arFields["arIMAGE"]["name"])>0 || strlen($arFields["arIMAGE"]["del"])>0)
			{
				if (!array_key_exists("MODULE_ID", $arFields["arIMAGE"]) || strlen($arFields["arIMAGE"]["MODULE_ID"]) <= 0)
					$arFields["arIMAGE"]["MODULE_ID"] = "form";

				$fid = CFile::SaveFile($arFields["arIMAGE"], "form");
				if (intval($fid)>0)	$arFields_i["IMAGE_ID"] = intval($fid);
				else $arFields_i["IMAGE_ID"] = "null";
			}

			$FIELD_ID = intval($FIELD_ID);

			if ($FIELD_ID>0)
			{
				$DB->Update("b_form_field", $arFields_i, "WHERE ID='".$FIELD_ID."'", $err_mess.__LINE__);
			}
			else
			{
				$arFields_i["FORM_ID"] = "'".intval($arFields["FORM_ID"])."'";
				$FIELD_ID = $DB->Insert("b_form_field", $arFields_i, $err_mess.__LINE__);
			}


			if ($FIELD_ID>0)
			{
				// ответы на вопрос
				if ($arFields["ADDITIONAL"]!="Y" && is_set($arFields, "arANSWER"))
				{
					$arANSWER = $arFields["arANSWER"];
					if (is_array($arANSWER) && count($arANSWER)>0)
					{
						$arrAnswers = array();
						$rs = CFormAnswer::GetList($FIELD_ID, $by='ID', $order='ASC', array(), $is_filtered);
						while($ar = $rs->Fetch())
							$arrAnswers[] = $ar["ID"];

						foreach($arANSWER as $arA)
						{
							$answer_id = in_array($arA["ID"], $arrAnswers) ? intval($arA["ID"]) : 0;
							if ($arA["DELETE"]=="Y" && $answer_id>0) CFormAnswer::Delete($answer_id, $FIELD_ID);
							else
							{
								if ($answer_id>0 || ($answer_id<=0 && strlen($arA["MESSAGE"])>0))
								{
									$arFields_a = array(
										"FIELD_ID"		=> $FIELD_ID,
										"MESSAGE"		=> $arA["MESSAGE"],
										"VALUE"			=> $arA["VALUE"],
										"C_SORT"		=> $arA["C_SORT"],
										"ACTIVE"		=> $arA["ACTIVE"],
										"FIELD_TYPE"	=> $arA["FIELD_TYPE"],
										"FIELD_WIDTH"	=> $arA["FIELD_WIDTH"],
										"FIELD_HEIGHT"	=> $arA["FIELD_HEIGHT"],
										"FIELD_PARAM"	=> $arA["FIELD_PARAM"],
										);
									//echo "<pre>"; print_r($arFields_a); echo "</pre>";
									CFormAnswer::Set($arFields_a, $answer_id, $FIELD_ID);
								}
							}
						}
					}
				}

				// тип почтового события
				CForm::SetMailTemplate(intval($arFields["FORM_ID"]),"N");

				if ($UPDATE_FILTER == 'Y')
				{
					// фильтр
					$in_filter="N";
					$DB->Query("UPDATE b_form_field SET IN_FILTER='N' WHERE ID='".$FIELD_ID."'", false, $err_mess.__LINE__);
					$arrFilterType = array(
						"arFILTER_USER"			=> "USER",
						"arFILTER_ANSWER_TEXT"	=> "ANSWER_TEXT",
						"arFILTER_ANSWER_VALUE"	=> "ANSWER_VALUE",
						"arFILTER_FIELD"		=> "USER",
					);

					foreach ($arrFilterType as $key => $value)
					{
						if (is_set($arFields, $key))
						{
							$strSql = "DELETE FROM b_form_field_filter WHERE FIELD_ID='".$FIELD_ID."' and PARAMETER_NAME='".$value."'";
							$DB->Query($strSql, false, $err_mess.__LINE__);
							if (is_array($arFields[$key]))
							{
								reset($arFields[$key]);
								foreach($arFields[$key] as $type)
								{
									$arFields_i = array(
										"FIELD_ID"			=> "'".intval($FIELD_ID)."'",
										"FILTER_TYPE"		=> "'".$DB->ForSql($type,50)."'",
										"PARAMETER_NAME"	=> "'".$value."'",
									);
									$DB->Insert("b_form_field_filter",$arFields_i, $err_mess.__LINE__);
									$in_filter="Y";
								}
							}
						}
					}

					if ($in_filter=="Y")
						$DB->Query("UPDATE b_form_field SET IN_FILTER='Y' WHERE ID='".$FIELD_ID."'", false, $err_mess.__LINE__);
				}
			}
			return $FIELD_ID;
		}
		return false;
	}
}


?>