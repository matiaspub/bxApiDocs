<?

/***************************************
				Ответы
***************************************/


/**
 * <b>CFormAnswer</b> - класс для работы с <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответами</a>. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/form/classes/cformanswer/index.php
 * @author Bitrix
 */
class CAllFormAnswer
{
public static 	function err_mess()
	{
		$module_id = "form";
		@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/install/version.php");
		return "<br>Module: ".$module_id." (".$arModuleVersion["VERSION"].")<br>Class: CAllFormAnswer<br>File: ".__FILE__;
	}

	// копирует ответ
	
	/**
	* <p>Копирует <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответ</a>. Возвращает ID нового <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> в случае положительного результата, в противном случае - "false".</p>
	*
	*
	* @param int $answer_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> который необходимо
	* скопировать.
	*
	* @param mixed $question_id = false ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>, в который
	* необходимо скопировать <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответ</a>.<br> Необязательный
	* параметр. По умолчанию - "false" (текущий <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопрос</a>).
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $answer_id = 589; // ID ответа "да" на вопрос "Вы женаты/замужем?"
	* // скопируем ответ
	* if ($NEW_ANSWER_ID = <b>CFormAnswer::Copy</b>($answer_id))
	* {
	*     echo "Ответ #589 успешно скопирован в новый ответ #".$NEW_ANSWER_ID;
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
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/copy.php">CFormField::Copy</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformstatus/copy.php">CFormStatus::Copy</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformanswer/copy.php
	* @author Bitrix
	*/
	public static function Copy($ID, $NEW_QUESTION_ID=false)
	{
		global $DB, $APPLICATION, $strError;
		$err_mess = (CAllFormAnswer::err_mess())."<br>Function: Copy<br>Line: ";
		$ID = intval($ID);
		$NEW_QUESTION_ID = intval($NEW_QUESTION_ID);
		$rsAnswer = CFormAnswer::GetByID($ID);
		if ($arAnswer = $rsAnswer->Fetch())
		{
			$arFields = array(
				"QUESTION_ID"	=> ($NEW_QUESTION_ID>0) ? $NEW_QUESTION_ID : $arAnswer["QUESTION_ID"],
				"MESSAGE"		=> $arAnswer["MESSAGE"],
				"VALUE"			=> $arAnswer["VALUE"],
				"C_SORT"		=> $arAnswer["C_SORT"],
				"ACTIVE"		=> $arAnswer["ACTIVE"],
				"FIELD_TYPE"	=> $arAnswer["FIELD_TYPE"],
				"FIELD_WIDTH"	=> $arAnswer["FIELD_WIDTH"],
				"FIELD_HEIGHT"	=> $arAnswer["FIELD_HEIGHT"],
				"FIELD_PARAM"	=> $arAnswer["FIELD_PARAM"],
				);
			$NEW_ID = CFormAnswer::Set($arFields);
			return $NEW_ID;
		}
		else $strError .= GetMessage("FORM_ERROR_ANSWER_NOT_FOUND")."<br>";
		return false;
	}

	// удаляем ответ

	/**
	* <p>Удаляет <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответ</a> и все значения в результатах, связанные с ним. Возвращает "true" в случае положительного результата, и "false" - в противном случае.</p>
	*
	*
	* @param int $answer_id  ID удаляемого <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>. </h
	*
	* @param int $question_id = false ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>, к которому
	* приписан удаляемый <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответ</a>.
	* Указание данного параметра позволяет ускорить выполнение
	* функции.<br> Параметр необязательный. По умолчанию - "false".
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $answer_id = 589; // ID ответа
	* // удалим ответ
	* if (<b>CFormAnswer::Delete</b>($answer_id))
	* {
	*     echo "Ответ #589 удален.";
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
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/delete.php">CFormField::Delete</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformstatus/delete.php">CFormStatus::Delete</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/delete.php">CFormResult::Delete</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformanswer/delete.php
	* @author Bitrix
	*/
	public static 	function Delete($ID, $QUESTION_ID=false)
	{
		global $DB, $strError;
		$err_mess = (CAllFormAnswer::err_mess())."<br>Function: Delete<br>Line: ";
		$ID = intval($ID);
		$DB->Query("DELETE FROM b_form_answer WHERE ID='".$ID."'", false, $err_mess.__LINE__);
		if (intval($QUESTION_ID)>0) $str = " FIELD_ID = ".intval($QUESTION_ID)." and ";
		$DB->Query("DELETE FROM b_form_result_answer WHERE ".$str." ANSWER_ID='".$ID."'", false, $err_mess.__LINE__);
		return true;
	}

public static 	function GetTypeList()
	{
		global $bSimple;
		$arrT = array(
				"text",
				"textarea",
				"radio",
				"checkbox",
				"dropdown",
				"multiselect",
				"date",
				"image",
				"file",
				"email",
				"url",
				"password",
				"hidden"
				);
		//if ($bSimple) $arrT[] = "hidden";
		$arr = array("reference_id" => $arrT, "reference" => $arrT);
		return $arr;
	}

	// возвращает список ответов

	/**
	* <p>Возвращает список <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответов</a> в виде объекта класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p>
	*
	*
	* @param int $question_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>.</bod
	*
	* @param string &$by = "s_sort" Ссылка на переменную с полем для сортировки результирующего
	* списка. Может принимать значения: <ul> <li> <b>s_id</b> - ID <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>; </li> <li> <b>s_sort</b> - индекс
	* сортировки. </li> </ul>
	*
	* @param string &$order = "asc" Ссылка на переменную с порядком сортировки. Может принимать
	* значения: <ul> <li> <b>asc</b> - по возрастанию; </li> <li> <b>desc</b> - по убыванию.
	* </li> </ul>
	*
	* @param array $filter = array() Массив для фильтрации. Необязательный параметр. В массиве
	* допустимы следующие ключи: <ul> <li> <b>ID</b>* - ID <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> (по умолчанию будет
	* искаться точное совпадение); </li> <li> <b>ID_EXACT_MATCH</b> - если значение
	* равно "N", при фильтрации по <b>ID</b> будет искаться вхождение; </li> <li>
	* <b>ACTIVE</b> - флаг активности, допустимые следующие значения: <ul> <li>
	* <b>Y</b> - <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответ</a> активен; </li> <li>
	* <b>N</b> - <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответ</a> не активен. </li>
	* </ul> </li> <li> <b>MESSAGE</b>* - параметр <font color="green">ANSWER_TEXT</font> (по умолчанию
	* будет искаться вхождение); </li> <li> <b>MESSAGE_EXACT_MATCH</b> - если значение
	* равно "Y", при фильтрации по <b>MESSAGE</b> будет искаться точное
	* совпадение; </li> <li> <b>VALUE</b>* - параметр <font color="red">ANSWER_VALUE</font> (по
	* умолчанию будет искаться вхождение); </li> <li> <b>VALUE_EXACT_MATCH</b> - если
	* значение равно "Y", то при фильтрации по <b>VALUE</b> будет искаться
	* точное совпадение; </li> <li> <b>FIELD_TYPE</b>* - <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformanswer/index.php#field_type">тип поля ответа</a>
	* (по умолчанию будет искаться вхождение); </li> <li> <b>FIELD_TYPE_EXACT_MATCH</b> -
	* если значение равно "Y", при фильтрации по <b>FIELD_TYPE</b> будет искаться
	* точное совпадение; </li> <li> <b>FIELD_PARAM</b>* - параметр поля ответа (по
	* умолчанию будет искаться вхождение); </li> <li> <b>FIELD_PARAM_EXACT_MATCH</b> - если
	* значение равно "Y", то при фильтрации по <b>FIELD_PARAM</b> будет искаться
	* точное совпадение. </li> </ul> * - допускается <a
	* href="http://dev.1c-bitrix.ru/user_help/general/filter.php">сложная логика</a>
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
	* $QUESTION_ID = 143; // ID вопроса
	* 
	* // сформируем массив фильтра
	* $arFilter = Array(
	*     "ID"                      =&gt; "589 | 590", // ID ответа равен 589 или 590
	*     "ID_EXACT_MATCH"          =&gt; "Y",         // точное совпадение для ID
	*     "ACTIVE"                  =&gt; "Y",         // флаг активности
	*     "MESSAGE"                 =&gt; "да | нет",  // параметр <font color="green">ANSWER_TEXT</font> равен "да" или "нет"
	*     "MESSAGE_EXACT_MATCH"     =&gt; "Y",         // точное совпадение для MESSAGE
	*     "FIELD_TYPE"              =&gt; "radio",     // тип поля ответа - radio-кнопка
	*     "FIELD_TYPE_EXACT_MATCH"  =&gt; "Y",         // точное совпадение для FIELD_TYPE
	*     "FIELD_PARAM"             =&gt; "checked",   // параметр включает в себя строку "checked"
	*     "FIELD_PARAM_EXACT_MATCH" =&gt; "N"          // вхождение для FIELD_PARAM
	* );
	* 
	* // получим список всех ответов вопроса #143
	* $rsAnswers = <b>CFormAnswer::GetList</b>(
	*     $QUESTION_ID, 
	*     $by="s_id", 
	*     $order="desc", 
	*     $arFilter, 
	*     $is_filtered
	*     );
	* while ($arAnswer = $rsAnswers-&gt;Fetch())
	* {
	*     echo "&lt;pre&gt;"; print_r($arAnswer); echo "&lt;/pre&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformanswer/index.php">Поля CFormAnswer</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformanswer/getbyid.php">CFormAnswer::GetByID</a> <br> </li>
	* </ul></b<a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformanswer/getlist.php
	* @author Bitrix
	*/
	public static 	function GetList($QUESTION_ID, &$by, &$order, $arFilter=Array(), &$is_filtered)
	{
		$err_mess = (CAllFormAnswer::err_mess())."<br>Function: GetList<br>Line: ";
		global $DB, $strError;
		$QUESTION_ID = intval($QUESTION_ID);
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
						$arSqlSearch[] = GetFilterQuery("A.ID",$val,$match);
						break;
					case "MESSAGE":
					case "VALUE":
					case "FIELD_TYPE":
					case "FIELD_WIDTH":
					case "FIELD_HEIGHT":
					case "FIELD_PARAM":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("A.".$key, $val, $match);
						break;
					case "ACTIVE":
						$arSqlSearch[] = ($val=="Y") ? "A.ACTIVE='Y'" : "A.ACTIVE='N'";
						break;
				}
			}
		}
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		if ($by == "s_id") $strSqlOrder = "ORDER BY A.ID";
		elseif ($by == "s_c_sort" || $by == "s_sort") $strSqlOrder = "ORDER BY A.C_SORT";
		else
		{
			$by = "s_sort";
			$strSqlOrder = "ORDER BY A.C_SORT";
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
		$strSql = "
			SELECT
				A.ID,
				A.FIELD_ID,
				A.FIELD_ID as QUESTION_ID,
				".$DB->DateToCharFunction("A.TIMESTAMP_X")."	TIMESTAMP_X,
				A.MESSAGE,
				A.VALUE,
				A.FIELD_TYPE,
				A.FIELD_WIDTH,
				A.FIELD_HEIGHT,
				A.FIELD_PARAM,
				A.C_SORT,
				A.ACTIVE
			FROM
				b_form_answer A
			WHERE
			$strSqlSearch
			and A.FIELD_ID = $QUESTION_ID
			$strSqlOrder
			";
		//echo "<pre>$strSql</pre>";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch));
		return $res;
	}


	/**
	* <p>Возвращает <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformanswer/index.php">параметры</a> <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> в виде объекта класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p>
	*
	*
	* @param int $answer_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>.</bo
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $answer_id = 589; // ID ответа
	* $rsAnswer = <b>CFormAnswer::GetByID</b>($answer_id);
	* $arAnswer = $rsAnswer-&gt;Fetch();
	* echo "&lt;pre&gt;"; print_r($arAnswer); echo "&lt;/pre";
	* ?&gt;</bo
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformanswer/index.php">Поля CFormAnswer</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformanswer/getlist.php">CFormAnswer::GetList</a> </li>
	* </ul></b<a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformanswer/getbyid.php
	* @author Bitrix
	*/
	public static 	function GetByID($ID)
	{
		$err_mess = (CAllFormAnswer::err_mess())."<br>Function: GetByID<br>Line: ";
		global $DB, $strError;
		$ID = intval($ID);
		$strSql = "
			SELECT
				A.ID,
				A.FIELD_ID,
				A.FIELD_ID as QUESTION_ID,
				".$DB->DateToCharFunction("A.TIMESTAMP_X")."	TIMESTAMP_X,
				A.MESSAGE,
				A.VALUE,
				A.FIELD_TYPE,
				A.FIELD_WIDTH,
				A.FIELD_HEIGHT,
				A.FIELD_PARAM,
				A.C_SORT,
				A.ACTIVE
			FROM
				b_form_answer A
			WHERE
				ID='$ID'
			";
		//echo $strSql;
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}

	// проверка ответа
public static 	function CheckFields($arFields, $ANSWER_ID=false)
	{
		$err_mess = (CAllFormAnswer::err_mess())."<br>Function: CheckFields<br>Line: ";
		global $DB, $strError, $APPLICATION, $USER;
		$str = "";
		$ANSWER_ID = intval($ANSWER_ID);

		if (intval($arFields["QUESTION_ID"])>0) $arFields["FIELD_ID"] = $arFields["QUESTION_ID"];
		else $arFields["QUESTION_ID"] = $arFields["FIELD_ID"];

		if ($ANSWER_ID<=0 && intval($arFields["QUESTION_ID"])<=0)
		{
			$str .= GetMessage("FORM_ERROR_FORGOT_QUESTION_ID")."<br>";
		}

		if ($ANSWER_ID<=0 || ($ANSWER_ID>0 && is_set($arFields, "MESSAGE")))
		{
			if (strlen($arFields["MESSAGE"])<=0) $str .= GetMessage("FORM_ERROR_FORGOT_ANSWER_TEXT")."<br>";
		}

		$strError .= $str;
		if (strlen($str)>0) return false; else return true;
	}

	// добавление/обновление ответа

	/**
	* <p>Добавляет новый <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответ</a> или обновляет существующий. Возвращает ID обновленного или добавленного <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> в случае положительного результата, в противном случае - "false".</p>
	*
	*
	* @param array $fields  Массив значений, в качестве ключей массива допустимы: <ul> <li>
	* <b>QUESTION_ID</b><font color="red">*</font> - ID <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a> </li> <li> <b>MESSAGE</b><font
	* color="red">*</font> - значение параметра <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> <font color="green">ANSWER_TEXT;</font>
	* </li> <li> <b>VALUE</b> - значение параметра <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> <font color="red">ANSWER_VALUE;</font>
	* </li> <li> <b>C_SORT</b> - порядок сортировки; </li> <li> <b>ACTIVE</b> - флаг
	* активности, допустимы следующие значения: <ul> <li> <b>Y</b> - ответ
	* активен; </li> <li> <b>N</b> - ответ не активен (по умолчанию). </li> </ul> </li> <li>
	* <b>FIELD_TYPE</b> - тип поля <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>,
	* допустимы следующие значения: <ul> <li> <b>text</b> - однострочное
	* текстовое поле; </li> <li> <b>textarea</b> - многострочное текстовое поле; </li>
	* <li> <b>radio</b> - переключатель одиночного выбора (radio-кнопка); </li> <li>
	* <b>checkbox</b> - флаг множественного выбора (checkbox); </li> <li> <b>dropdown</b> -
	* элемент выпадающего списка одиночного выбора; </li> <li> <b>multiselect</b> -
	* элемент списка множественного выбора; </li> <li> <b>date</b> - поле для
	* ввода даты; </li> <li> <b>image</b> - поле для загрузки изображения; </li> <li>
	* <b>file</b> - поле для загрузки произвольного файла; </li> <li> <b>password</b> -
	* поле для ввода пароля. </li> </ul> </li> <li> <b>FIELD_WIDTH</b> - ширина поля <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>; </li> <li> <b>FIELD_HEIGHT</b> -
	* высота поля <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>; </li> <li>
	* <b>FIELD_PARAM</b> - параметр поля <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>. </li> </ul> <font color="red">*</font> -
	* обязательные поля.
	*
	* @param mixed $answer_id = false ID обновляемого <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>.<br>
	* Параметр необязательный. По умолчанию - "false" (добавление нового <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>).
	*
	* @param mixed $current_question_id = false ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>, к которому
	* приписан обновляемый <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответ</a>.
	* Указание данного параметра позволяет ускорить выполнение
	* метода. <br>Параметр необязательный. По умолчанию - "false".
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $QUESTION_ID = 140; // ID вопроса "Фамилия, имя, отчество"
	* 
	* $arFields = array(
	*     "QUESTION_ID"   =&gt; $QUESTION_ID,
	*     "MESSAGE"       =&gt; " ",
	*     "C_SORT"        =&gt; 100,
	*     "ACTIVE"        =&gt; "Y",
	*     "FIELD_TYPE"    =&gt; "text",
	*     "FIELD_WIDTH"   =&gt; "40"
	*     );
	* 
	* $NEW_ID = <b>CFormAnswer::Set</b>($arFields);
	* if ($NEW_ID&gt;0) echo "Успешно добавлен ID=".$NEW_ID;
	* else // ошибка
	* {
	*     // выводим текст ошибки
	*     global $strError;
	*     echo $strError;
	* }
	* ?&gt;
	* 
	* 
	* 
	* &lt;?
	* $QUESTION_ID = 143; // ID вопроса "Вы женаты/замужем?"
	* 
	* $arFields = array(
	*     "QUESTION_ID"      =&gt; $QUESTION_ID,
	*     "MESSAGE"       =&gt; "да",
	*     "C_SORT"        =&gt; 100,
	*     "ACTIVE"        =&gt; "Y",
	*     "FIELD_TYPE"    =&gt; "radio",
	*     "FIELD_PARAM"   =&gt; "checked"
	*     );
	* <b>CFormAnswer::Set</b>($arFields);
	* 
	* $arFields = array(
	*     "QUESTION_ID"      =&gt; $QUESTION_ID,
	*     "MESSAGE"       =&gt; "нет",
	*     "C_SORT"        =&gt; 200,
	*     "ACTIVE"        =&gt; "Y",
	*     "FIELD_TYPE"    =&gt; "radio"
	*     );
	* <b>CFormAnswer::Set</b>($arFields);
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformanswer/index.php">Поля CFormAnswer</a>
	* </li></ul></b<a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformanswer/set.php
	* @author Bitrix
	*/
	public static 	function Set($arFields, $ANSWER_ID=false)
	{
		$err_mess = (CAllFormAnswer::err_mess())."<br>Function: Set<br>Line: ";
		global $DB, $USER, $strError, $APPLICATION;

		$ANSWER_ID = intval($ANSWER_ID);

		if (CFormAnswer::CheckFields($arFields, $ANSWER_ID))
		{
			$arFields_i = array();

			$arFields_i["TIMESTAMP_X"] = $DB->GetNowFunction();

			if (is_set($arFields, "MESSAGE"))
				$arFields_i["MESSAGE"] = "'".$DB->ForSql($arFields["MESSAGE"],2000)."'";

			if (is_set($arFields, "VALUE"))
				$arFields_i["VALUE"] = "'".$DB->ForSql($arFields["VALUE"],2000)."'";

			if (is_set($arFields, "ACTIVE"))
				$arFields_i["ACTIVE"] = ($arFields["ACTIVE"]=="Y") ? "'Y'" : "'N'";

			if (is_set($arFields, "C_SORT"))
				$arFields_i["C_SORT"] = "'".intval($arFields["C_SORT"])."'";

			if (is_set($arFields, "FIELD_TYPE"))
				$arFields_i["FIELD_TYPE"] = "'".$DB->ForSql($arFields["FIELD_TYPE"],255)."'";

			if (is_set($arFields, "FIELD_WIDTH"))
				$arFields_i["FIELD_WIDTH"] = "'".intval($arFields["FIELD_WIDTH"])."'";

			if (is_set($arFields, "FIELD_HEIGHT"))
				$arFields_i["FIELD_HEIGHT"] = "'".intval($arFields["FIELD_HEIGHT"])."'";

			if (is_set($arFields, "FIELD_PARAM"))
				$arFields_i["FIELD_PARAM"] = "'".$DB->ForSql($arFields["FIELD_PARAM"],2000)."'";

			if ($ANSWER_ID>0)
			{
				$DB->Update("b_form_answer", $arFields_i, "WHERE ID='".$ANSWER_ID."'", $err_mess.__LINE__);

				// обновим все результаты для данного ответа
				$arFields_u = array();
				$arFields_u["ANSWER_TEXT"] = $arFields_i["MESSAGE"];
				$arFields_u["ANSWER_VALUE"] = $arFields_i["VALUE"];
				if (intval($CURRENT_FIELD_ID)>0) $str = " FIELD_ID = ".intval($CURRENT_FIELD_ID)." and ";
				$DB->Update("b_form_result_answer", $arFields_u, "WHERE ".$str." ANSWER_ID='".$ANSWER_ID."'", $err_mess.__LINE__);
			}
			else
			{
				if (intval($arFields["QUESTION_ID"])>0) $arFields["FIELD_ID"] = $arFields["QUESTION_ID"];
				else $arFields["QUESTION_ID"] = $arFields["FIELD_ID"];

				$arFields_i["FIELD_ID"] = "'".intval($arFields["QUESTION_ID"])."'";
				
				$ANSWER_ID = $DB->Insert("b_form_answer", $arFields_i, $err_mess.__LINE__);
				$ANSWER_ID = intval($ANSWER_ID);
			}
			return $ANSWER_ID;
		}
		return false;
	}
}


?>