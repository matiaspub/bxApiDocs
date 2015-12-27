<?
/***************************************
			Web-form
***************************************/


/**
 * <b>CForm</b> - класс для работы с <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">веб-формами</a>. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/index.php
 * @author Bitrix
 */
class CForm extends CAllForm
{
	public static function err_mess()
	{
		$module_id = "form";
		@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/install/version.php");
		return "<br>Module: ".$module_id." (".$arModuleVersion["VERSION"].")<br>Class: CForm<br>File: ".__FILE__;
	}

	// список веб-форм

	/**
	* <p>Возвращает список <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">веб-форм</a> в виде объекта класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p>
	*
	*
	* @param string &$by = "s_sort" Ссылка на переменную с полем для сортировки, может принимать
	* значения: <ul> <li> <b>s_id</b> - ID веб-формы; </li> <li> <b>s_sort</b> - индекс
	* сортировки; </li> <li> <b>s_name</b> - заголовок веб-формы; </li> <li> <b>s_sid</b> -
	* символьный идентификатор веб-формы. </li> </ul>
	*
	* @param string &$order = "asc" Ссылка на переменную с порядком сортировки, может принимать
	* значения: <ul> <li> <b>asc</b> - по возрастанию; </li> <li> <b>desc</b> - по убыванию.
	* </li> </ul>
	*
	* @param array $filter = array() Массив для фильтрации. В массиве допустимы следующие ключи: <ul> <li>
	* <b>ID</b>* - ID веб-формы (по умолчанию будет искаться точное
	* совпадение); </li> <li> <b>ID_EXACT_MATCH</b> - если значение равно "N", то при
	* фильтрации по <b>ID</b> будет искаться вхождение; </li> <li> <b>SID</b>* -
	* символьный идентификатор веб-формы (по умолчанию будет искаться
	* точное совпадение); </li> <li> <b>SID_EXACT_MATCH</b> - если значение равно "N", то
	* при фильтрации по <b>SID</b> будет искаться вхождение; </li> <li> <b>NAME</b>* -
	* заголовок веб-формы (по умолчанию будет искаться вхождение); </li>
	* <li> <b>NAME_EXACT_MATCH</b> - если значение равно "Y", то при фильтрации по
	* <b>NAME</b> будет искаться точное совпадение; </li> <li> <b>DESCRIPTION</b>* -
	* описание веб-формы (по умолчанию будет искаться вхождение); </li> <li>
	* <b>DESCRIPTION_EXACT_MATCH</b> - если значение равно "Y", то при фильтрации по
	* <b>DESCRIPTION</b> будет искаться точное совпадение; </li> <li> <b>SITE</b>* -
	* идентификатор сайта или массив идентификаторов сайтов, к которым
	* приписана веб-форма (по умолчанию будет искаться точное
	* совпадение); </li> <li> <b>SITE_EXACT_MATCH</b> - если значение равно "N", то при
	* фильтрации по <b>SITE</b> будет искаться вхождение. </li> </ul> * -
	* допускается сложная логика
	*
	* @param bool &$is_filtered  Ссылка на переменную, хранящую флаг отфильтрованности списка
	* веб-форм. Если значение равно "true", то список был отфильтрован.
	*
	* @param int $min_permission = 10 Минимальный уровень <a href="http://dev.1c-bitrix.ru/api_help/form/permissions.php#form">прав на
	* веб-форму</a>.<br><br>Параметр необязательный. По умолчанию - 2. С
	* версии 4.0.4 - 10 (право на заполнение веб-формы и создание нового
	* результата).
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // сформируем массив фильтра
	* $arFilter = Array(
	*  "ID"                      =&gt; "1 | 2",          // ID=1 либо ID=2
	*  "ID_EXACT_MATCH"          =&gt; "Y",              // точное совпадение по ID
	*  "NAME"                    =&gt; "Анкета",         // в заголовке веб-формы есть слово "Анкета"
	*  "NAME_EXACT_MATCH"        =&gt; "N",              // не точное совпадение по NAME
	*  "SID"                     =&gt; "ANKETA",         // символьный идентификатор равен ANEKTA
	*  "SID_EXACT_MATCH"         =&gt; "Y",              // точное совпадение по SID
	*  "DESCRIPTION"             =&gt; "заполните",      // в описании есть слово "заполните"
	*  "DESCRIPTION_EXACT_MATCH" =&gt; "N",              // не точное совпадение по DESCRIPTION
	*  "SITE"                    =&gt; array("r1", "e2") // веб-форма приписана и к сайтам r1, e2
	* );
	* 
	* // получим список всех форм, для которых у текущего пользователя есть право на заполнение
	* $rsForms = <b>CForm::GetList</b>($by="s_id", $order="desc", $arFilter, $is_filtered);
	* while ($arForm = $rsForms-&gt;Fetch())
	* {
	*     echo "&lt;pre&gt;"; print_r($arForm); echo "&lt;/pre&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/index.php">Поля CForm</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/permissions.php#form">Права на веб-форму</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getbyid.php">CForm::GetByID</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getbysid.php">CForm::GetBySID</a> <br> </li> </ul> <a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/getlist.php
	* @author Bitrix
	*/
	public static 	function GetList(&$by, &$order, $arFilter=Array(), &$is_filtered, $min_permission=10)
	{
		$err_mess = (CForm::err_mess())."<br>Function: GetList<br>Line: ";
		global $DB, $USER, $strError;
		$min_permission = intval($min_permission);

		$arSqlSearch = Array();
		$strSqlSearch = "";
		if (is_array($arFilter))
		{
			if (strlen($arFilter["SID"])>0) $arFilter["VARNAME"] = $arFilter["SID"];
			elseif (strlen($arFilter["VARNAME"])>0) $arFilter["SID"] = $arFilter["VARNAME"];

			$filter_keys = array_keys($arFilter);
			for ($i=0; $i<count($filter_keys); $i++)
			{
				$key = $filter_keys[$i];
				$val = $arFilter[$filter_keys[$i]];
				if(is_array($val))
				{
					if(count($val) <= 0)
						continue;
				}
				else
				{
				if( (strlen($val) <= 0) || ($val === "NOT_REF") )
					continue;
				}
				$match_value_set = (in_array($key."_EXACT_MATCH", $filter_keys)) ? true : false;
				$key = strtoupper($key);
				switch($key)
				{
					case "ID":
					case "SID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("F.".$key, $val, $match);
						break;
					case "NAME":
					case "DESCRIPTION":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("F.".$key, $val, $match);
						break;
					case "SITE":
						if (is_array($val)) $val = implode(" | ", $val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("FS.SITE_ID", $val, $match);
						$left_join = "LEFT JOIN b_form_2_site FS ON (F.ID = FS.FORM_ID)";
						break;
				}
			}
		}

		if ($by == "s_id")								$strSqlOrder = "ORDER BY F.ID";
		elseif ($by == "s_c_sort" || $by == "s_sort")	$strSqlOrder = "ORDER BY F.C_SORT";
		elseif ($by == "s_name")						$strSqlOrder = "ORDER BY F.NAME";
		elseif ($by == "s_varname" || $by == "s_sid")	$strSqlOrder = "ORDER BY F.SID";
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
		if (CForm::IsAdmin())
		{
			$strSql = "
				SELECT
					F.*,
					F.SID											VARNAME,
					F.FIRST_SITE_ID,
					F.FIRST_SITE_ID									LID,
					".$DB->DateToCharFunction("F.TIMESTAMP_X")."	TIMESTAMP_X,
					count(distinct D1.ID)							C_FIELDS,
					count(distinct D2.ID)							QUESTIONS,
					count(distinct S.ID)							STATUSES
				FROM
					b_form F
				LEFT JOIN b_form_status S ON (S.FORM_ID = F.ID)
				LEFT JOIN b_form_field D1 ON (D1.FORM_ID = F.ID and D1.ADDITIONAL='Y')
				LEFT JOIN b_form_field D2 ON (D2.FORM_ID = F.ID and D2.ADDITIONAL<>'Y')
				$left_join
				WHERE
				$strSqlSearch
				GROUP BY F.ID
				$strSqlOrder
				";
		}
		else
		{
			$arGroups = $USER->GetUserGroupArray();
			if (!is_array($arGroups)) $arGroups[] = 2;
			$groups = implode(",",$arGroups);
			$def_permission = COption::GetOptionInt("form", "FORM_DEFAULT_PERMISSION", 10);
			$strSql = "
				SELECT
					F.*,
					F.SID VARNAME,
					F.FIRST_SITE_ID,
					F.FIRST_SITE_ID LID,
					".$DB->DateToCharFunction("F.TIMESTAMP_X")."	TIMESTAMP_X,
					count(distinct D1.ID) C_FIELDS,
					count(distinct D2.ID) QUESTIONS,
					count(distinct S.ID) STATUSES
				FROM
					b_form F
					".
					($def_permission >=$min_permission?
					"	LEFT JOIN b_form_2_group G ON (G.FORM_ID=F.ID and G.GROUP_ID in ($groups)) "
					:
					"	INNER JOIN b_form_2_group G ON (G.FORM_ID=F.ID and G.PERMISSION>=$min_permission and G.GROUP_ID in ($groups))	"
					)."
				LEFT JOIN b_form_status S ON (S.FORM_ID = F.ID)
				LEFT JOIN b_form_field D1 ON (D1.FORM_ID = F.ID and D1.ADDITIONAL='Y')
				LEFT JOIN b_form_field D2 ON (D2.FORM_ID = F.ID and D2.ADDITIONAL<>'Y')
				$left_join
				WHERE $strSqlSearch ".
				($def_permission >=$min_permission?
				"	AND (G.FORM_ID IS NULL OR G.PERMISSION>=$min_permission) "
				:
				""
				).
				"

				GROUP BY F.ID
				$strSqlOrder
				";
		}
		//echo "<pre>".$strSql."</pre>";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch));
		return $res;
	}


	/**
	* <p>Возвращает <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/index.php">параметры</a> <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">веб-формы</a> в виде объекта класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p>
	*
	*
	* @param int $form_id  Необязательный параметр.
	*
	* @param mixed $GET_BY_SID = "N" 
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $FORM_ID = 2;
	* $rsForm = <b>CForm::GetByID</b>($FORM_ID);
	* $arForm = $rsForm-&gt;Fetch();
	* echo "&lt;pre&gt;"; print_r($arForm); echo "&lt;/pre&gt;";
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/index.php">Поля CForm</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getbysid.php">CForm::GetBySID</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getlist.php">CForm::GetList</a> </li> </ul></b<a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/getbyid.php
	* @author Bitrix
	*/
	public static 	function GetByID($ID, $GET_BY_SID="N")
	{
		$err_mess = (CForm::err_mess())."<br>Function: GetByID<br>Line: ";
		global $DB, $strError;
		$where = ($GET_BY_SID=="N") ? " F.ID = '".intval($ID)."' " : " F.SID='".$DB->ForSql($ID,50)."' ";
		$strSql = "
			SELECT
				F.*,
				F.FIRST_SITE_ID,
				F.FIRST_SITE_ID									LID,
				F.SID,
				F.SID											VARNAME,
				".$DB->DateToCharFunction("F.TIMESTAMP_X")."	TIMESTAMP_X,
				count(distinct D1.ID)							C_FIELDS,
				count(distinct D2.ID)							QUESTIONS,
				count(distinct S.ID)							STATUSES
			FROM b_form F
			LEFT JOIN b_form_status S ON (S.FORM_ID = F.ID)
			LEFT JOIN b_form_field D1 ON (D1.FORM_ID = F.ID and D1.ADDITIONAL='Y')
			LEFT JOIN b_form_field D2 ON (D2.FORM_ID = F.ID and D2.ADDITIONAL<>'Y')
			WHERE
				$where
			GROUP BY
				F.ID
			";
		//echo "<pre>".$strSql."</pre>";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}

public static 	function GetFormTemplateByID($ID, $GET_BY_SID="N")
	{
		$err_mess = (CForm::err_mess())."<br>Function: GetFormTemplateByID<br>Line: ";
		global $DB, $strError;
		$where = ($GET_BY_SID=="N") ? " F.ID = '".intval($ID)."' " : " F.SID='".$DB->ForSql($ID,50)."' ";
		$strSql = "
			SELECT
				F.FORM_TEMPLATE FT
			FROM b_form F
			WHERE
				$where
			";
		//echo "<pre>".$strSql."</pre>";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		if ($arRes = $res->Fetch()) return $arRes["FT"];
		else return "";
	}
}

?>