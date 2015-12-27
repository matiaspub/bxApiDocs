<?
/***************************************
			Веб-форма
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
class CAllForm extends CForm_old
{
public static 	function err_mess()
	{
		$module_id = "form";
		@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/install/version.php");
		return "<br>Module: ".$module_id." (".$arModuleVersion["VERSION"].")<br>Class: CAllForm<br>File: ".__FILE__;
	}

	// true - если текущий пользователь имеет полный доступ к модулю
	// false - в противном случае

	/**
	* <p>Возвращает "true", если текущий пользователь имеет административные <a href="http://dev.1c-bitrix.ru/api_help/form/permissions.php#module">права</a> на модуль <b>Веб-формы</b>, в противном случае - "false".</p>
	*
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (<b>CForm::IsAdmin</b>())
	* {
	*     echo "У вас административные права на модуль Веб-форм.";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/form/permissions.php#module">Права на модуль</a>
	* </li></ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/isadmin.php
	* @author Bitrix
	*/
	public static 	function IsAdmin()
	{
		global $USER, $APPLICATION;
		if (!is_object($USER)) $USER = new CUser;
		if ($USER->IsAdmin()) return true;
		$FORM_RIGHT = $APPLICATION->GetGroupRight("form");
		if ($FORM_RIGHT>="W") return true;
	}

	// Функция возвращает массивы, содержащие данные по вопросам и полям формы, а также ответы и их значения.

	/**
	* <p>Возвращает массивы, описывающие <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопросы</a> и <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a> <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">веб-формы</a>, а также <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответы на вопросы</a>.</p>
	*
	*
	* @param int $form_id  ID формы.</b
	*
	* @param array &$columns  Параметр примет значение ссылки на массив, описывающий те
	* вопросы и поля формы, которые: <ol> <li> активны;</li> <li>включены в
	* таблицу результатов.</li> </ol> Ключами данного массива являются ID
	* вопросов/полей, а значениями - массив, описывающий сам
	* вопрос/поле, в свою очередь, имеющий следующие ключи: <ul> <li> <b>ID</b> -
	* ID вопроса/поля; </li> <li> <b>FORM_ID</b> - ID формы; </li> <li> <b>TIMESTAMP_X</b> - дата
	* изменения вопроса/поля; </li> <li> <b>ACTIVE</b> - флаг активности [Y|N]; </li> <li>
	* <b>TITLE</b> - текст вопроса, либо заголовок поля; </li> <li> <b>TITLE_TYPE</b> - тип
	* текста; </li> <li> <b>SID</b> - символьный идентификатор вопроса/поля; </li>
	* <li> <b>C_SORT</b> - порядок сортировки; </li> <li> <b>ADDITIONAL</b> - если Y - то данная
	* запись является вопросом; если N - то полем формы; </li> <li> <b>REQUIRED</b> -
	* флаг обязательности ответа на вопрос [Y|N]; </li> <li> <b>IN_FILTER</b> - флаг
	* показывающий отражен ли вопрос/поле в фильтре формы результатов
	* [Y|N]; </li> <li> <b>IN_RESULTS_TABLE</b> - флаг показывающий отображается ли
	* вопрос/поле в таблице результатов [Y|N]; </li> <li> <b>IN_EXCEL_TABLE</b> - флаг
	* показывающий отображается ли вопрос/поле в Excel-таблице
	* результатов [Y|N]; </li> <li> <b>FIELD_TYPE</b> тип поля, возможны следующие
	* значения: <ul> <li> <b>text</b> - текст; </li> <li> <b>integer</b> - число; </li> <li> <b>date</b> -
	* дата. </li> </ul> </li> <li> <b>IMAGE_ID</b> - ID изображения в описании вопроса; </li>
	* <li> <b>COMMENTS</b> - служебный комментарий; </li> <li> <b>FILTER_TITLE</b> - заголовок
	* поля фильтра по данному вопросу/полю; </li> <li> <b>RESULTS_TABLE_TITLE</b> -
	* заголовок столбца таблицы результатов. </li> </ul> <b>Пример:</b> <pre
	* style="height:450px">Array ( [140] =&gt; Array ( [ID] =&gt; 140 [FORM_ID] =&gt; 4 [TIMESTAMP_X] =&gt; 19.05.2005
	* 11:42:04 [ACTIVE] =&gt; Y [TITLE] =&gt; Фамилия, имя, отчество [TITLE_TYPE] =&gt; html [SID] =&gt;
	* VS_NAME [C_SORT] =&gt; 100 [ADDITIONAL] =&gt; N [REQUIRED] =&gt; Y [IN_FILTER] =&gt; Y [IN_RESULTS_TABLE] =&gt; N
	* [IN_EXCEL_TABLE] =&gt; N [FIELD_TYPE] =&gt; [IMAGE_ID] =&gt; [COMMENTS] =&gt; [FILTER_TITLE] =&gt; [RESULTS_TABLE_TITLE]
	* =&gt; ) [144] =&gt; Array ( [ID] =&gt; 144 [FORM_ID] =&gt; 4 [TIMESTAMP_X] =&gt; 11.11.2004 18:11:21 [ACTIVE] =&gt; Y
	* [TITLE] =&gt; Какие области знаний вас интересуют ? [TITLE_TYPE] =&gt; text [SID] =&gt;
	* VS_INTEREST [C_SORT] =&gt; 500 [ADDITIONAL] =&gt; N [REQUIRED] =&gt; N [IN_FILTER] =&gt; Y [IN_RESULTS_TABLE] =&gt; Y
	* [IN_EXCEL_TABLE] =&gt; Y [FIELD_TYPE] =&gt; [IMAGE_ID] =&gt; [COMMENTS] =&gt; [FILTER_TITLE] =&gt; [RESULTS_TABLE_TITLE]
	* =&gt; ) ... ) </pre>
	*
	* @param array &$answers  Параметр примет значение ссылки на массив, содержащий ответы на
	* вопросы формы, а также значения полей формы. Ключами данного
	* массива являются: <ul> <li> <b>RESULT_ID</b> - ID результата; </li> <li> <b>FIELD_ID</b> - ID
	* вопроса/поля; </li> <li> <b>SID</b> - символьный идентификатор
	* вопроса/поля; </li> <li> <b>TITLE</b> - текст вопроса или заголовок поля
	* веб-формы; </li> <li> <b>TITLE_TYPE</b> - тип текста вопроса, допустимы
	* следующие значения: <ul> <li> <b>text</b> - текст; </li> <li> <b>html</b> - HTML код. </li>
	* </ul> </li> <li> <b>FILTER_TITLE</b> - подпись поля фильтра по данному
	* вопросу/полю; </li> <li> <b>RESULTS_TABLE_TITLE</b> - подпись столбца таблицы
	* результатов; </li> <li> <b>ANSWER_ID</b> - ID ответа; </li> <li> <b>ANSWER_TEXT</b> - параметр
	* ответа <font color="green">ANSWER_TEXT</font>, записанный в таблицу результата; </li>
	* <li> <b>MESSAGE</b> - параметр ответа <font color="green">ANSWER_TEXT</font>, хранящийся в
	* таблице ответов (синоним ключа <b>ANSWER_TEXT</b>); </li> <li> <b>ANSWER_VALUE</b> -
	* параметр ответа <font color="red">ANSWER_VALUE</font>, записанный в таблицу
	* результата; </li> <li> <b>VALUE</b> - параметр ответа <font color="red">ANSWER_VALUE</font>,
	* хранящийся в таблице ответов (синоним ключа <b>ANSWER_VALUE</b>); </li> <li>
	* <b>USER_TEXT</b> - текстовое значение, введенное пользователем; </li> <li>
	* <b>USER_DATE</b> - дата, введенная пользователем (данный ключ может
	* содержать значение, только если <b>FIELD_TYPE</b>="date"); </li> <li> <b>USER_FILE_ID</b> -
	* ID файла загруженного пользователем (данный ключ может содержать
	* значение, только если <b>FIELD_TYPE</b>="image" или <b>FIELD_TYPE</b>="file"); </li> <li>
	* <b>USER_FILE_NAME</b> - оригинальное имя загруженного файла (данный ключ
	* может содержать значение, только если <b>FIELD_TYPE</b>="image" или
	* <b>FIELD_TYPE</b>="file"); </li> <li> <b>USER_FILE_IS_IMAGE</b> - "Y" - если <b>FIELD_TYPE</b>="image", "N" -
	* если <b>FIELD_TYPE</b>="file" </li> <li> <b>USER_FILE_HASH</b> - уникальный хеш,
	* используемый при показе файла (данный ключ может содержать
	* значение, только если <b>FIELD_TYPE</b>="file"); </li> <li> <b>USER_FILE_SUFFIX</b> - суффикс
	* к расширению загруженного файла (данный ключ может содержать
	* значение, только если <b>FIELD_TYPE</b>="file"); </li> <li> <b>USER_FILE_SIZE</b> - размер
	* файла в байтах (данный ключ может содержать значение, только если
	* <b>FIELD_TYPE</b>="image" или <b>FIELD_TYPE</b>="file"); </li> <li> <b>FIELD_TYPE</b> - тип поля ответа,
	* возможны следующие значения: <ul> <li> <b>text</b> - однострочное
	* текстовое поле; </li> <li> <b>textarea</b> - многострочное текстовое поле; </li>
	* <li> <b>radio</b> - переключатель одиночного выбора; </li> <li> <b>checkbox</b> - флаг
	* множественного выбора; </li> <li> <b>dropdown</b> - элемента выпадающего
	* списка одиночного выбора; </li> <li> <b>multiselect</b> - элемента списка
	* множественного выбора; </li> <li> <b>date</b> - поле для ввода дата в
	* календарем; </li> <li> <b>image</b> - поле для ввода изображения; </li> <li>
	* <b>file</b> - поле для ввода произвольного файла; </li> <li> <b>password</b> -
	* однострочное поле для ввода пароля. </li> </ul> </li> <li> <b>FIELD_WIDTH</b> -
	* ширина поля ответа; </li> <li> <b>FIELD_HEIGHT</b> - высота поля ответа; </li> <li>
	* <b>FIELD_PARAM</b> - параметры поля ответа. </li> </ul> <b>Пример:</b> <pre
	* style="height:450px"> Array ( [186] =&gt; Array ( [140] =&gt; Array ( [586] =&gt; Array ( [RESULT_ID] =&gt; 186
	* [FIELD_ID] =&gt; 140 [SID] =&gt; VS_NAME [TITLE] =&gt; Фамилия, имя, отчество [TITLE_TYPE] =&gt; html
	* [FILTER_TITLE] =&gt; [RESULTS_TABLE_TITLE] =&gt; [ANSWER_ID] =&gt; 586 [ANSWER_TEXT] =&gt; [MESSAGE] =&gt;
	* [ANSWER_VALUE] =&gt; [VALUE] =&gt; [USER_TEXT] =&gt; Иванов Дмитрий Витальевич [USER_DATE] =&gt;
	* [USER_FILE_ID] =&gt; [USER_FILE_NAME] =&gt; [USER_FILE_IS_IMAGE] =&gt; [USER_FILE_HASH] =&gt; [USER_FILE_SUFFIX] =&gt;
	* [USER_FILE_SIZE] =&gt; [FIELD_TYPE] =&gt; text [FIELD_WIDTH] =&gt; 50 [FIELD_HEIGHT] =&gt; 0 [FIELD_PARAM] =&gt; ) )
	* [144] =&gt; Array ( [594] =&gt; Array ( [RESULT_ID] =&gt; 186 [FIELD_ID] =&gt; 144 [SID] =&gt; VS_INTEREST [TITLE] =&gt;
	* Какие области знаний вас интересуют ? [TITLE_TYPE] =&gt; text [FILTER_TITLE] =&gt;
	* [RESULTS_TABLE_TITLE] =&gt; [ANSWER_ID] =&gt; 594 [ANSWER_TEXT] =&gt; иностранные языки [MESSAGE] =&gt;
	* иностранные языки [ANSWER_VALUE] =&gt; 4 [VALUE] =&gt; 4 [USER_TEXT] =&gt; [USER_DATE] =&gt;
	* [USER_FILE_ID] =&gt; [USER_FILE_NAME] =&gt; [USER_FILE_IS_IMAGE] =&gt; [USER_FILE_HASH] =&gt; [USER_FILE_SUFFIX] =&gt;
	* [USER_FILE_SIZE] =&gt; [FIELD_TYPE] =&gt; checkbox [FIELD_WIDTH] =&gt; 0 [FIELD_HEIGHT] =&gt; 0 [FIELD_PARAM] =&gt; )
	* [595] =&gt; Array ( [RESULT_ID] =&gt; 186 [FIELD_ID] =&gt; 144 [SID] =&gt; VS_INTEREST [TITLE] =&gt; Какие
	* области знаний вас интересуют ? [TITLE_TYPE] =&gt; text [FILTER_TITLE] =&gt;
	* [RESULTS_TABLE_TITLE] =&gt; [ANSWER_ID] =&gt; 595 [ANSWER_TEXT] =&gt; програмирование [MESSAGE] =&gt;
	* програмирование [ANSWER_VALUE] =&gt; 5 [VALUE] =&gt; 5 [USER_TEXT] =&gt; [USER_DATE] =&gt; [USER_FILE_ID]
	* =&gt; [USER_FILE_NAME] =&gt; [USER_FILE_IS_IMAGE] =&gt; [USER_FILE_HASH] =&gt; [USER_FILE_SUFFIX] =&gt; [USER_FILE_SIZE]
	* =&gt; [FIELD_TYPE] =&gt; checkbox [FIELD_WIDTH] =&gt; 0 [FIELD_HEIGHT] =&gt; 0 [FIELD_PARAM] =&gt; SELECTED
	* class=inputcheckbox ) ) ... ) ... ) </pre>
	*
	* @param array &$answers2 = array() Параметр примет значение ссылки на массив, содержащий, по сути, те
	* же данные, что и массив answers, но имеющий несколько другую
	* структуру. <br><br><b>Пример:</b> <pre style="height:450px"> Array ( [186] =&gt; Array ( [VS_NAME] =&gt;
	* Array ( [0] =&gt; Array ( [RESULT_ID] =&gt; 186 [FIELD_ID] =&gt; 140 [SID] =&gt; VS_NAME [TITLE] =&gt; Фамилия,
	* имя, отчество [TITLE_TYPE] =&gt; html [FILTER_TITLE] =&gt; [RESULTS_TABLE_TITLE] =&gt; [ANSWER_ID] =&gt; 586
	* [ANSWER_TEXT] =&gt; [MESSAGE] =&gt; [ANSWER_VALUE] =&gt; [VALUE] =&gt; [USER_TEXT] =&gt; Иванов Дмитрий
	* Витальевич [USER_DATE] =&gt; [USER_FILE_ID] =&gt; [USER_FILE_NAME] =&gt; [USER_FILE_IS_IMAGE] =&gt;
	* [USER_FILE_HASH] =&gt; [USER_FILE_SUFFIX] =&gt; [USER_FILE_SIZE] =&gt; [FIELD_TYPE] =&gt; text [FIELD_WIDTH] =&gt; 50
	* [FIELD_HEIGHT] =&gt; 0 [FIELD_PARAM] =&gt; ) ) [VS_INTEREST] =&gt; Array ( [0] =&gt; Array ( [RESULT_ID] =&gt; 186
	* [FIELD_ID] =&gt; 144 [SID] =&gt; VS_INTEREST [TITLE] =&gt; Какие области знаний вас
	* интересуют ? [TITLE_TYPE] =&gt; text [FILTER_TITLE] =&gt; [RESULTS_TABLE_TITLE] =&gt; [ANSWER_ID] =&gt; 594
	* [ANSWER_TEXT] =&gt; иностранные языки [MESSAGE] =&gt; иностранные языки [ANSWER_VALUE]
	* =&gt; 4 [VALUE] =&gt; 4 [USER_TEXT] =&gt; [USER_DATE] =&gt; [USER_FILE_ID] =&gt; [USER_FILE_NAME] =&gt;
	* [USER_FILE_IS_IMAGE] =&gt; [USER_FILE_HASH] =&gt; [USER_FILE_SUFFIX] =&gt; [USER_FILE_SIZE] =&gt; [FIELD_TYPE] =&gt;
	* checkbox [FIELD_WIDTH] =&gt; 0 [FIELD_HEIGHT] =&gt; 0 [FIELD_PARAM] =&gt; ) [1] =&gt; Array ( [RESULT_ID] =&gt; 186
	* [FIELD_ID] =&gt; 144 [SID] =&gt; VS_INTEREST [TITLE] =&gt; Какие области знаний вас
	* интересуют ? [TITLE_TYPE] =&gt; text [FILTER_TITLE] =&gt; [RESULTS_TABLE_TITLE] =&gt; [ANSWER_ID] =&gt; 595
	* [ANSWER_TEXT] =&gt; програмирование [MESSAGE] =&gt; програмирование [ANSWER_VALUE] =&gt; 5
	* [VALUE] =&gt; 5 [USER_TEXT] =&gt; [USER_DATE] =&gt; [USER_FILE_ID] =&gt; [USER_FILE_NAME] =&gt; [USER_FILE_IS_IMAGE]
	* =&gt; [USER_FILE_HASH] =&gt; [USER_FILE_SUFFIX] =&gt; [USER_FILE_SIZE] =&gt; [FIELD_TYPE] =&gt; checkbox [FIELD_WIDTH]
	* =&gt; 0 [FIELD_HEIGHT] =&gt; 0 [FIELD_PARAM] =&gt; SELECTED class=inputcheckbox ) ) ... ) ... ) </pre>
	*
	* @param array $filter = array() Массив для фильтрации выбираемых значений. Необязательный
	* параметр. В массиве допустимы следующие ключи: <ul> <li> <b>RESULT_ID</b>* - ID
	* результата (по умолчанию будет искаться точное совпадение); </li>
	* <li>RESULT_ID_EXACT_MATCH - если значение равно "N", то при фильтрации по
	* <b>RESULT_ID</b> будет искаться вхождение; </li> <li> <b>FIELD_ID</b>* - ID
	* вопроса/поля (по умолчанию будет искаться точное совпадение); </li>
	* <li> <b>FIELD_ID_EXACT_MATCH</b> - если значение равно "N", то при фильтрации по
	* <b>FIELD_ID</b> будет искаться вхождение; </li> <li> <b>FIELD_SID</b>* - символьный
	* код вопроса/поля (по умолчанию будет искаться вхождение); </li> <li>
	* <b>FIELD_SID_EXACT_MATCH</b> - если значение равно "Y", то при фильтрации по
	* <b>FIELD_SID</b> будет искаться точное совпадение; </li> <li> <b>IN_RESULTS_TABLE</b> -
	* если значение равно "Y", то ответы на вопрос (либо значения поля
	* веб-формы) будет отображены в таблице результатов; </li> <li>
	* <b>IN_EXCEL_TABLE</b> - если значение равно "Y", то ответы на вопрос (либо
	* значения поля веб-формы) будет отображены в Excel таблице
	* результатов. </li> </ul> * - допускается сложная логика.
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre>
	* // получим данные по результату ID=145
	* <b>CForm::GetResultAnswerArray</b>($FORM_ID, 
	* 	$arrColumns, 
	* 	$arrAnswers, 
	* 	$arrAnswersVarname, 
	* 	array("RESULT_ID" =&gt; "145"));
	* 
	* echo "&lt;pre&gt;";
	* echo "arrColumns:";
	* print_r($arrColumns);
	* echo "arrAnswers:";
	* print_r($arrAnswers);
	* echo "arrAnswersVarname:";
	* print_r($arrAnswersVarname);
	* echo "&lt;/pre&gt;";
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/getdatabyid.php">CFormResult::GetDataByID</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/getlist.php">CFormField::GetList</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformanswer/getlist.php">CFormAnswer::GetList</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/getresultanswerarray.php
	* @author Bitrix
	*/
	public static 	function GetResultAnswerArray($WEB_FORM_ID, &$arrColumns, &$arrAnswers, &$arrAnswersSID, $arFilter=Array())
	{
		$err_mess = (CAllForm::err_mess())."<br>Function: GetResultAnswerArray<br>Line: ";
		global $DB, $strError;
		$WEB_FORM_ID = intval($WEB_FORM_ID);
		$arSqlSearch = Array();
		$strSqlSearch = "";
		if (is_array($arFilter))
		{
			if (strlen($arFilter["FIELD_SID"])>0) $arFilter["FIELD_VARNAME"] = $arFilter["FIELD_SID"];
			elseif (strlen($arFilter["FIELD_VARNAME"])>0) $arFilter["FIELD_SID"] = $arFilter["FIELD_VARNAME"];

			$filter_keys = array_keys($arFilter);
			$cntFilterKeys = count($filter_keys);
			for ($i=0; $i<$cntFilterKeys; $i++)
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
					case "FIELD_ID":
					case "RESULT_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("RA.".$key, $val, $match);
						break;
					case "FIELD_SID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("F.SID", $val, $match);
						break;
					case "IN_RESULTS_TABLE":
					case "IN_EXCEL_TABLE":
						$arSqlSearch[] = ($val=="Y") ? "F.".$key."='Y'" : "F.".$key."='N'";
						break;
				}
			}
		}
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT
				RA.RESULT_ID, RA.FIELD_ID, F.SID, F.SID as VARNAME, F.TITLE, F.TITLE_TYPE, F.FILTER_TITLE, F.RESULTS_TABLE_TITLE,
				RA.ANSWER_ID, RA.ANSWER_TEXT, A.MESSAGE, RA.ANSWER_VALUE, A.VALUE, RA.USER_TEXT,
				".$DB->DateToCharFunction("RA.USER_DATE")."	USER_DATE,
				RA.USER_FILE_ID, RA.USER_FILE_NAME, RA.USER_FILE_IS_IMAGE, RA.USER_FILE_HASH, RA.USER_FILE_SUFFIX, RA.USER_FILE_SIZE,
				A.FIELD_TYPE, A.FIELD_WIDTH, A.FIELD_HEIGHT, A.FIELD_PARAM
			FROM
				b_form_result_answer RA
			INNER JOIN b_form_field F ON (F.ID = RA.FIELD_ID and F.ACTIVE='Y')
			LEFT JOIN b_form_answer A ON (A.ID = RA.ANSWER_ID)
			WHERE
			$strSqlSearch
			and RA.FORM_ID = $WEB_FORM_ID
			ORDER BY RA.RESULT_ID, F.C_SORT, A.C_SORT
			";
		//echo "<pre>".$strSql."</pre>";
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		while ($zr = $z->Fetch())
		{
			$arrAnswers[$zr["RESULT_ID"]][$zr["FIELD_ID"]][intval($zr["ANSWER_ID"])]=$zr;
			$arrAnswersSID[$zr["RESULT_ID"]][$zr["SID"]][]=$zr;
		}
		$q = CFormField::GetList($WEB_FORM_ID, "", $v1, $v2,
			array(
				"ID"				=> $arFilter["FIELD_ID"],
				"VARNAME"			=> $arFilter["FIELD_SID"],
				"SID"				=> $arFilter["FIELD_SID"],
				"IN_RESULTS_TABLE"	=> $arFilter["IN_RESULTS_TABLE"],
				"IN_EXCEL_TABLE"	=> $arFilter["IN_EXCEL_TABLE"],
				"ACTIVE"			=> "Y"),
			$is_filtered
			);
		while ($qr = $q->Fetch())
		{
			$arrColumns[$qr["ID"]] = $qr;
		}
	}

	// получаем массив почтовых шаблонов связанных с формой
public static 	function GetMailTemplateArray($FORM_ID)
	{
		$err_mess = (CAllForm::err_mess())."<br>Function: GetMailTemplateArray<br>Line: ";
		global $DB, $USER, $strError;
		$FORM_ID = intval($FORM_ID);
		if ($FORM_ID<=0) return false;
		$arrRes = array();
		$strSql = "
			SELECT
				FM.MAIL_TEMPLATE_ID
			FROM
				b_form_2_mail_template FM
			WHERE
				FM.FORM_ID = $FORM_ID
			";
		//echo "<pre>".$strSql."</pre>";
		$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
		while ($ar = $rs->Fetch()) $arrRes[] = $ar["MAIL_TEMPLATE_ID"];
		return $arrRes;
	}

	// получаем массив сайтов связанных с формой
public static 	function GetSiteArray($FORM_ID)
	{
		$err_mess = (CAllForm::err_mess())."<br>Function: GetSiteArray<br>Line: ";
		global $DB, $USER, $strError;
		$FORM_ID = intval($FORM_ID);
		if ($FORM_ID<=0) return false;
		$arrRes = array();
		$strSql = "
			SELECT
				FS.SITE_ID
			FROM
				b_form_2_site FS
			WHERE
				FS.FORM_ID = $FORM_ID
			";
		//echo "<pre>".$strSql."</pre>";
		$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
		while ($ar = $rs->Fetch()) $arrRes[] = $ar["SITE_ID"];
		return $arrRes;
	}

	// функция вызывает заданный обработчик до смены статуса
public static 	function ExecHandlerBeforeChangeStatus($RESULT_ID, $ACTION, $NEW_STATUS_ID=0)
	{
		global $arrPREV_RESULT_STATUS, $DB, $MESS, $APPLICATION, $USER, $HTTP_POST_VARS, $HTTP_GET_VARS, $strError;
		$err_mess = (CAllForm::err_mess())."<br>Function: ExecHandlerBeforeChangeStatus<br>Line: ";
		$RESULT_ID = intval($RESULT_ID);
		if ($RESULT_ID<=0) return;
		else
		{
			$strSql = "
				SELECT
					R.*,
					".$DB->DateToCharFunction("R.DATE_CREATE")."	DATE_CREATE,
					".$DB->DateToCharFunction("R.TIMESTAMP_X")."	TIMESTAMP_X,
					S.TITLE			STATUS_TITLE,
					S.DESCRIPTION	STATUS_DESCRIPTION,
					S.DEFAULT_VALUE	STATUS_DEFAULT_VALUE,
					S.CSS			STATUS_CSS,
					S.HANDLER_IN	STATUS_HANDLER_IN,
					S.HANDLER_OUT	STATUS_HANDLER_OUT
				FROM
					b_form_result R
				INNER JOIN b_form_status S ON (R.STATUS_ID=S.ID)
				WHERE
					R.ID = $RESULT_ID
				";
			//echo "<pre>".$strSql."</pre>";
			$rsResult = $DB->Query($strSql, false, $err_mess.__LINE__);
			if ($arResult = $rsResult->Fetch())
			{
				$arrPREV_RESULT_STATUS[$RESULT_ID] = $arResult["STATUS_ID"];
				$handler = trim($arResult["STATUS_HANDLER_OUT"]);
				if (strlen($handler)>0)
				{
					$fname = $handler;
					$fname = str_replace("\\", "/", $fname);
					$fname = str_replace("//", "/", $fname);
					$fname = TrimEx($fname,"/");
					$CURRENT_STATUS_ID = $arResult["STATUS_ID"];
					$fname = $_SERVER["DOCUMENT_ROOT"]."/".$fname;
					include($fname);
				}
			}
		}
	}

	// функция вызывает заданный обработчик после смены статуса
public static 	function ExecHandlerAfterChangeStatus($RESULT_ID, $ACTION)
	{
		global $arrCURRENT_RESULT_STATUS, $arrPREV_RESULT_STATUS, $DB, $MESS, $APPLICATION, $USER, $HTTP_POST_VARS, $HTTP_GET_VARS, $strError;
		$err_mess = (CAllForm::err_mess())."<br>Function: ExecHandlerAfterChangeStatus<br>Line: ";
		$RESULT_ID = intval($RESULT_ID);
		if ($RESULT_ID<=0) return;
		else
		{
			$strSql = "
				SELECT
					R.*,
					".$DB->DateToCharFunction("R.DATE_CREATE")."	DATE_CREATE,
					".$DB->DateToCharFunction("R.TIMESTAMP_X")."	TIMESTAMP_X,
					S.TITLE			STATUS_TITLE,
					S.DESCRIPTION	STATUS_DESCRIPTION,
					S.DEFAULT_VALUE	STATUS_DEFAULT_VALUE,
					S.CSS			STATUS_CSS,
					S.HANDLER_IN	STATUS_HANDLER_IN,
					S.HANDLER_OUT	STATUS_HANDLER_OUT
				FROM
					b_form_result R
				INNER JOIN b_form_status S ON (R.STATUS_ID=S.ID)
				WHERE
					R.ID = $RESULT_ID
				";
			//echo "<pre>".$strSql."</pre>";
			$rsResult = $DB->Query($strSql, false, $err_mess.__LINE__);
			if ($arResult = $rsResult->Fetch())
			{
				$arrCURRENT_RESULT_STATUS[$RESULT_ID] = $arResult["STATUS_ID"];
				$handler = trim($arResult["STATUS_HANDLER_IN"]);
				if (strlen($handler)>0)
				{
					$fname = $handler;
					$fname = str_replace("\\", "/", $fname);
					$fname = str_replace("//", "/", $fname);
					$fname = TrimEx($fname,"/");
					$fname = $_SERVER["DOCUMENT_ROOT"]."/".$fname;
					$CURRENT_STATUS_ID = $arResult["STATUS_ID"];
					$PREV_STATUS_ID = $arrPREV_RESULT_STATUS[$RESULT_ID];
					include($fname);
				}
			}
		}
	}

	// права на веб-форму
public static 	function GetPermissionList($get_default="Y")
	{
		global $MESS, $strError;
		$ref_id = array(1,10,15,20,25,30);
		$ref = array(
			"[1] ".GetMessage("FORM_DENIED"),
			"[10] ".GetMessage("FORM_FILL"),
			"[15] ".GetMessage("FORM_FILL_EDIT"),
			"[20] ".GetMessage("FORM_VIEW"),
			"[25] ".GetMessage("FORM_VIEW_PARAMS"),
			"[30] ".GetMessage("FORM_WRITE")
			);
		$ref_id_def = array();
		$ref_def = array();
		if ($get_default=="Y")
		{
			$default_perm = COption::GetOptionString("form", "FORM_DEFAULT_PERMISSION");
			$idx = array_search($default_perm, $ref_id);
			$ref_id_def[] = 0;
			$ref_def[] = GetMessage("FORM_DEFAULT")." - ".$ref[$idx];
		}
		$arr = array(
			"reference_id" => array_merge($ref_id_def,$ref_id),
			"reference" => array_merge($ref_def, $ref));
		return $arr;
	}


	/**
	* <p>Возвращает <a href="http://dev.1c-bitrix.ru/api_help/form/permissions.php#form">право доступа к веб-форме</a>:</p> <ul> <li> <b>1</b> - доступ закрыт (форма и ее результаты полностью недоступны); </li> <li> <b>10</b> - заполнение формы (посетитель может только заполнить и сохранить форму); </li> <li> <b>15</b> - редактирование своего результата (посетитель получает возможность видеть список своих результатов, который он может фильтровать и сортировать; также посетитель может просмотреть, изменить и удалить свой результат); </li> <li> <b>20</b> - просмотр всех результатов (посетитель получает возможность просмотра всех активных результатов); </li> <li> <b>25</b> - редактирование всех результатов и просмотр настроек формы (посетитель получает возможность просмотра и редактирования всех результатов в зависимости от их статусов; также, если у него открыт доступ к административной части модуля, доступ на просмотр настроек формы); </li> <li> <b>30</b> - полный доступ (включает в себя все вышеописанные права, а также право на изменение настроек формы). </li> </ul>
	*
	*
	* @param int $form_id  ID веб-формы.</bod
	*
	* @param array $groups = false Массив ID групп пользователей, для которых нужно определить право
	* доступа.<br><br>Параметр необязательный. По умолчанию - "false" (группы
	* текущего пользователя).
	*
	* @param string $from_db = "" Если значение равно "Y", право доступа определяется без учета
	* значения по умолчанию, устанавливаемого в настройках модуля
	* <b>Веб-формы</b>. Параметр необязательный.
	*
	* @return int 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $FORM_ID = 4;
	* // получим права текущего пользователя
	* $permission = <b>CForm::GetPermission</b>($FORM_ID);
	* if ($permission==10) echo "У вас есть право на заполнение веб-формы";
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/permissions.php#form">Права на веб-форму</a>
	* </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformstatus/getpermissions.php">CFormStatus::GetPermissions</a>
	* </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformstatus/getpermissionlist.php">CFormStatus::GetPermissionList</a>
	* </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/getpermissions.php">CFormResult::GetPermissions</a>
	* </li> </ul> </htm<a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/getpermission.php
	* @author Bitrix
	*/
	public static 	function GetPermission($form_id, $arGroups=false, $get_from_database="")
	{
		global $DB, $USER, $strError;
		$err_mess = (CAllForm::err_mess())."<br>Function: GetPermission<br>Line: ";
		$default_right = COption::GetOptionString("form","FORM_DEFAULT_PERMISSION");
		if ($arGroups===false)
		{
			$arGroups = $USER->GetUserGroupArray();
			if (!is_array($arGroups))
				$arGroups = array(2);
		}

		if (CForm::IsAdmin() && $get_from_database!="Y") $right = 30;
		else
		{
			if (is_array($arGroups) && count($arGroups)>0)
			{
				foreach ($arGroups as $k => $g)
					$arGroups[$k] = intval($g);

				$arr = array();
				$groups = implode(',', $arGroups);
				$form_id = intval($form_id);
				$strSql = "
					SELECT
						FG.PERMISSION,
						FG.GROUP_ID
					FROM
						b_form_2_group FG
					WHERE
						FG.FORM_ID = '".$form_id."'
					and FG.GROUP_ID in (".$groups.")
					";
				//echo "<pre>".$strSql."</pre>";
				$t = $DB->Query($strSql, false, $err_mess.__LINE__);
				while ($tr = $t->Fetch())
					$arr[$tr["GROUP_ID"]] = $tr["PERMISSION"];

				if ($get_from_database!="Y")
				{
					foreach ($arGroups as $gid)
					{
						if (!array_key_exists($gid, $arr))
							$arr[$gid] = $default_right;
					}
				}

				$arr_values = is_array($arr) ? array_values($arr) : array(0);
				$right = count($arr_values)>0 ? max($arr_values) : 0;
			}
		}
		$right = intval($right);
		if ($right<=0 && $get_from_database!="Y") $right = $default_right;
		//echo "right = ".$right;
		return $right;
	}

public static 	function GetTemplateList($type="SHOW", $path="xxx", $WEB_FORM_ID=0)
	{
		$err_mess = (CAllForm::err_mess())."<br>Function: GetTemplateList<br>Line: ";
		global $DB, $strError;
		$WEB_FORM_ID = intval($WEB_FORM_ID);
		if ($type!="MAIL")
		{
			if ($path=="xxx")
			{
				if ($type=="SHOW") $path = COption::GetOptionString("form", "SHOW_TEMPLATE_PATH");
				elseif ($type=="SHOW_RESULT") $path = COption::GetOptionString("form", "SHOW_RESULT_TEMPLATE_PATH");
				elseif ($type=="PRINT_RESULT") $path = COption::GetOptionString("form", "PRINT_RESULT_TEMPLATE_PATH");
				elseif ($type=="EDIT_RESULT") $path = COption::GetOptionString("form", "EDIT_RESULT_TEMPLATE_PATH");
			}
			$arr = array();
			$handle=@opendir($_SERVER["DOCUMENT_ROOT"].$path);
			if($handle)
			{
				while (false!==($fname = readdir($handle)))
				{
					if (is_file($_SERVER["DOCUMENT_ROOT"].$path.$fname) && $fname!="." && $fname!="..")
					{
						$arReferenceId[] = $fname;
						$arReference[] = $fname;
					}
				}
				closedir($handle);
			}
		}
		elseif ($WEB_FORM_ID>0)
		{
			$arrSITE = array();
			$strSql = "
				SELECT
					F.MAIL_EVENT_TYPE,
					FS.SITE_ID
				FROM
					b_form F
				INNER JOIN b_form_2_site FS ON (FS.FORM_ID = F.ID)
				WHERE
					F.ID = $WEB_FORM_ID
				";
			$z = $DB->Query($strSql,false,$err_mess.__LINE__);

			$MAIL_EVENT_TYPE = '';
			$arrSITE = array();
			while ($zr = $z->Fetch())
			{
				$MAIL_EVENT_TYPE = $zr["MAIL_EVENT_TYPE"];
				$arrSITE[] = $zr["SITE_ID"];
			}

			$arReferenceId = array();
			$arReference = array();
			if (strlen($MAIL_EVENT_TYPE) > 0)
			{
				$arFilter = Array(
					"ACTIVE"		=> "Y",
					"SITE_ID"		=> $arrSITE,
					"EVENT_NAME"	=> $MAIL_EVENT_TYPE
					);
				$e = CEventMessage::GetList($by="id", $order="asc", $arFilter);
				while ($er=$e->Fetch())
				{
					if (!in_array($er["ID"], $arReferenceId))
					{
						$arReferenceId[] = $er["ID"];
						$arReference[] = "(".$er["LID"].") ".TruncateText($er["SUBJECT"],50);
					}
				}
			}
		}
		$arr = array("reference"=>$arReference,"reference_id"=>$arReferenceId);
		return $arr;
	}

public static 	function GetMenuList($arFilter=Array(), $check_rights="Y")
	{
		$err_mess = (CAllForm::err_mess())."<br>Function: GetMenuList<br>Line: ";
		global $DB, $USER, $strError;
		$arSqlSearch = Array();
		$strSqlSearch = "";
		if (is_array($arFilter))
		{
			$filter_keys = array_keys($arFilter);
			$cntFilterKeys = count($filter_keys);
			for ($i=0; $i<$cntFilterKeys; $i++)
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
					case "FORM_ID":
					case "LID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("L.".$key,$val,$match);
						break;
					case "MENU":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("L.MENU", $val, $match);
						break;
				}
			}
		}
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		if ($check_rights=="N" || CForm::IsAdmin())
		{
			$strSql = "
				SELECT
					F.ID,
					F.NAME,
					L.LID,
					L.MENU
				FROM
					b_form_menu L,
					b_form F
				WHERE
				$strSqlSearch
				and L.FORM_ID = F.ID
				ORDER BY F.C_SORT
				";
		}
		else
		{
			$arGroups = $USER->GetUserGroupArray();
			if (!is_array($arGroups)) $arGroups[] = 2;
			$groups = implode(",",$arGroups);
			$strSql = "
				SELECT
					F.ID,
					F.NAME,
					L.LID,
					L.MENU
				FROM
					b_form_menu L,
					b_form F,
					b_form_2_group G
				WHERE
				$strSqlSearch
				and L.FORM_ID = F.ID
				and G.FORM_ID = F.ID
				and G.GROUP_ID in ($groups)
				GROUP BY
					L.ID, L.LID, L.MENU, F.NAME, F.ID, F.C_SORT
				HAVING
					max(G.PERMISSION)>=15
				ORDER BY F.C_SORT
				";
		}
		//echo "<pre>".$strSql."</pre>";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}

public static 	function GetNextSort()
	{
		global $DB, $strError;
		$err_mess = (CAllForm::err_mess())."<br>Function: GetNextSort<br>Line: ";
		$strSql = "SELECT max(C_SORT) as MAX_SORT FROM b_form";
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		$zr = $z->Fetch();
		return (intval($zr["MAX_SORT"])+100);
	}

public static 	function ShowRequired($flag)
	{
		if ($flag=="Y") return "<font color='red'><span class='form-required starrequired'>*</span></font>";
	}


	/**
	* <p>Возвращает HTML код поля фильтра, предназначенного для фильтрации <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результатов</a> по текстовым значениям <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответов</a> на <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопросы веб-формы</a> или текстовым значениям <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">полей</a> веб-формы. Возвращаемый HTML код включает в себя однострочное текстовое поле и флаг для установки точности фильтрации.</p> <p class="note"><b>Примечание</b><br>Имена результирующих HTML полей будут сформированы по следующим маскам:<br><b>find_</b><i>filter_sid</i> - однострочное текстовое поля<br><b>find_</b><i>filter_sid</i><b>_exact_match</b> - флаг для установки точности фильтрации </p>
	*
	*
	* @param int $filter_sid  Идентификатор поля фильтра. Формируется по следующему
	* шаблону:<br><nobr><i>FSID</i><b>_</b><i>QSID</i><b>_</b><i>PTYPE</i><b>_text</b>,</nobr><br> где: <ul> <li>
	* <i>FSID</i> - символьный идентификатор <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">веб-формы</a>; </li> <li> <i>QSID</i> -
	* символьный идентификатор <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>/<a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a> веб-формы; </li> <li> <i>PTYPE</i> -
	* тип параметра по которому будет фильтрация, возможны следующие
	* значения: <ul> <li> <i>ANSWER_TEXT</i> - параметр <font color="green">ANSWER_TEXT</font> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a> веб-формы; </li> <li>
	* <i>ANSWER_VALUE</i> - параметр <font color="red">ANSWER_VALUE</font> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a> веб-формы; </li> <li>
	* <i>USER</i> - для <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>
	* веб-формы - вводимое с клавиатуры значение, для <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">полей</a> веб-формы - значение
	* этого поля веб-формы. </li> </ul> </li> </ul> Примеры: <ul> <li>ANKETA_USER_NAME_USER_text;
	* </li> <li>ANKETA_TEST_FIELD_USER_text. </li> </ul>
	*
	* @param int $size = 45 Ширина однострочного текстового поля:<br><code> &lt;input type="text"
	* size="<i>size</i>" ...&gt;</code><br><br>Параметр необязательный. По умолчанию - "45".
	*
	* @param string $add_to_text = "class=\"inputtext\"" Произвольный HTML, который будет добавлен в тег однострочного
	* текстового поля:<br><code> &lt;input type="text" <i>add_to_text</i> ...&gt;</code><br><br>Параметр
	* необязательный. По умолчанию - "class='typeinput'". С версии 4.0.4 -
	* "class=\"inputtext\""
	*
	* @param string $add_to_checkbox = "class=\"inputcheckbox\"" Произвольный HTML, который будет добавлен в тег флага для установки
	* точности фильтрации:<br><code> &lt;input type="checkbox" <i>add_to_checkbox</i>
	* ...&gt;</code><br><br>Параметр необязательный. По умолчанию -
	* "class=\"inputcheckbox\"".
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;form action="" method="POST"&gt;
	* &lt;table&gt;
	*     &lt;tr&gt;
	*         &lt;td&gt;Фамилия:&lt;/td&gt;
	*         &lt;td&gt;&lt;?
	*             echo <b>CForm::GetTextFilter</b>(
	*                 "ANKETA_USER_NAME_USER_text", 
	*                 45, 
	*                 "class=\"inputtext\"", 
	*                 "class=\"inputcheckbox\""
	*                 );
	*             ?&gt;&lt;/td&gt;
	*     &lt;/tr&gt;
	* &lt;/table&gt;
	* &lt;input type="submit" value="Фильтр"&gt;
	* &lt;/form&gt;
	* 
	* </h
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getdatefilter.php">CForm::GetDateFilter</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getdropdownfilter.php">CForm::GetDropDownFilter</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getnumberfilter.php">CForm::GetNumberFilter</a>
	* </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getexistflagfilter.php">CForm::GetExistFlagFilter</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/gettextfilter.php
	* @author Bitrix
	*/
	public static 	function GetTextFilter($FID, $size="45", $field_text="class=\"inputtext\"", $field_checkbox="class=\"inputcheckbox\"")
	{
		$var = "find_".$FID;
		$var_exec_match = "find_".$FID."_exact_match";
		global ${$var}, ${$var_exec_match};
		$checked = (${$var_exec_match}=="Y") ? "checked" : "";
		return '<input '.$field_text.' type="text" name="'.$var.'" size="'.$size.'" value="'.htmlspecialcharsbx(${$var}).'"><input '.$field_checkbox.' type="checkbox" value="Y" name="'.$var.'_exact_match" title="'.GetMessage("FORM_EXACT_MATCH").'" '.$checked.'>'.ShowFilterLogicHelp();
	}


	/**
	* <p>Возвращает HTML код поля фильтра, предназначенного для фильтрации <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результатов</a> по датам, введенным в качестве ответа на <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопрос</a> веб-формы, либо значений <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">полей</a> веб-формы типа "дата". Возвращаемый HTML код включает в себя два поля, предназначенных для ввода интервала дат, а также некоторые вспомогательные элементы (календарь, выпадающий список дней).</p> <p class="note"><b>Примечание</b><br>Имена результирующих HTML полей будут сформированы по следующим маскам:<br><b>find_</b><i>filter_sid</i><b>_1</b> - первое поля интервала дат (с) <br><b>find_</b><i>filter_sid</i><b>_2</b> - второе поле интервала дат (по) </p>
	*
	*
	* @param int $filter_sid  Идентификатор поля фильтра. Формируется по следующему
	* шаблону:<br><nobr><i>FSID</i><b>_</b><i>QSID</i><b>_USER_date</b>,</nobr><br> где: <ul> <li> <i>FSID</i> -
	* символьный идентификатор <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">веб-формы</a>; </li> <li> <i>QSID</i> -
	* символьный идентификатор <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>/<a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a> веб-формы. </li> </ul> Примеры:
	* <ul> <li>ANKETA_USER_BIRTHDAY_USER_date </li> <li>ANKETA_DATE_FIELD_USER_date </li> </ul>
	*
	* @param string $html_form_name = "form1" Имя HTML формы, в которой выводится фильтр.<br><code> &lt;form
	* name="<i>html_form_name</i>" ...&gt;</code><br><br>Параметр необязательный. По
	* умолчанию - "form1".
	*
	* @param string $show_dropdown = "Y" Если значение "Y", то возвращаемый HTML код будет включать
	* выпадающий список дней, предназначенный для облегчения выбора
	* даты.<br><br>Параметр необязательный. По умолчанию - "Y" (вывести
	* выпадающий список дней).
	*
	* @param string $add_to_dropdown = "class=\"inputselect\"" Если <i>show_dropdown</i>="Y", то в данном параметре можно указать
	* произвольный HTML, который будет добавлен в тег выпадающего списка
	* дней:<br><code> &lt;select <i>add_to_dropdown</i> ...&gt;</code><br><br>Параметр
	* необязательный. По умолчанию - "class=\"inputselect\"".
	*
	* @param string $add_to_text = "class=\"inputtext\"" Произвольный HTML, который будет добавлен в теги однострочных
	* текстовых полей, предназначенных для ввода даты:<br><code> &lt;input
	* type="text" <i>add_to_text</i> ...&gt;</code><br><br>Параметр необязательный. По
	* умолчанию - "class=\"inputtext\"".
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;form name="form1" action="" method="POST"&gt;
	* &lt;table&gt;
	*     &lt;tr&gt;
	*         &lt;td&gt;Дата рождения:&lt;/td&gt;
	*         &lt;td&gt;&lt;?
	*             echo <b>CForm::GetDateFilter</b>(
	*                 "ANKETA_USER_BIRTHDAY_USER_date", 
	*                 "form1", 
	*                 "Y", 
	*                 "class=\"inputselect\"", 
	*                 "class=\"inputtext\""
	*                 );
	*             ?&gt;&lt;/td&gt;
	*     &lt;/tr&gt;
	* &lt;/table&gt;
	* &lt;input type="submit" value="Фильтр"&gt;
	* &lt;/form&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/date/calendarperiod.php">CalendarPeriod</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/gettextfilter.php">CForm::GetTextFilter</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getdropdownfilter.php">CForm::GetDropDownFilter</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getnumberfilter.php">CForm::GetNumberFilter</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getexistflagfilter.php">CForm::GetExistFlagFilter</a>
	* </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/getdatefilter.php
	* @author Bitrix
	*/
	public static 	function GetDateFilter($FID, $form_name="form1", $show_select="Y", $field_select="class=\"inputselect\"", $field_input="class=\"inputtext\"")
	{
		$var1 = "find_".$FID."_1";
		$var2 = "find_".$FID."_2";

		global $APPLICATION, ${$var1}, ${$var2};

		if (!defined('ADMIN_SECTION'))
		{
			ob_start();
			$APPLICATION->IncludeComponent(
				'bitrix:main.calendar',
				'',
				array(
					'SHOW_INPUT' => 'Y',
					'FORM_NAME' => $form_name,
					'INPUT_NAME' => $var1,
					'INPUT_NAME_FINISH' => $var2,
					'INPUT_VALUE' => ${$var1},
					'INPUT_VALUE_FINISH' => ${$var2},
					'SHOW_TIME' => 'N',
				),
				null,
				array('HIDE_ICONS' => 'Y')
			);
			$res .= ob_get_contents();
			ob_end_clean();

			return $res;
		}
		else
			return CalendarPeriod($var1, htmlspecialcharsbx(${$var1}), $var2, htmlspecialcharsbx(${$var2}), $form_name, $show_select, $field_select, $field_input);
	}


	/**
	* <p>Возвращает HTML код поля фильтра, предназначенного для фильтрации <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результатов</a> по цифровым значениям, введенным в качестве ответа на <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопрос</a> веб-формы, либо цифровым значениям <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">полей</a> веб-формы. Возвращаемый HTML код включает в себя два поля, предназначенных для ввода числового интервала.</p> <p class="note"><b>Примечание</b><br>Имена результирующих HTML полей будут сформированы по следующим маскам:<br><b>find_</b><i>filter_sid</i><b>_1</b> - первое поля числового интервала (с) <br><b>find_</b><i>filter_sid</i><b>_2</b> - второе поле числового интервала (по) </p>
	*
	*
	* @param int $filter_sid  Идентификатор поля фильтра. Формируется по следующему
	* шаблону:<br><nobr><i>FSID</i><b>_</b><i>QSID</i><b>_</b><i>PTYPE</i><b>_integer</b>,</nobr><br> где: <ul> <li>
	* <i>FSID</i> - символьный идентификатор <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">веб-формы</a>, </li> <li> <i>QSID</i> -
	* символьный идентификатор <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>/<a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a> веб-формы; </li> <li> <i>PTYPE</i> -
	* тип параметра, по которому будет фильтрация, возможны следующие
	* значения: <ul> <li> <i>ANSWER_TEXT</i> - параметр <font color="green">ANSWER_TEXT</font> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a> веб-формы; </li> <li>
	* <i>ANSWER_VALUE</i> - параметр <font color="red">ANSWER_VALUE</font> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a> веб-формы; </li> <li>
	* <i>USER</i> - для <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>
	* веб-формы - вводимое с клавиатуры значение, для <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">полей</a> веб-формы - значение
	* этого поля веб-формы. </li> </ul> </li> </ul> Примеры: <ul> <li>ANKETA_AGE_USER_integer; </li>
	* <li>ANKETA_CAR_POWER_ANSWER_VALUE_integer. </li> </ul>
	*
	* @param int $size = "10" Ширина однострочного текстового поля:<br><code>&lt;input type="text" size="<i>size</i>"
	* ...&gt;</code><br><br>Параметр необязательный. По умолчанию - "10".
	*
	* @param string $add_to_text = "class=\"inputtext\"" Произвольный HTML, который будет добавлен в теги однострочных
	* текстовых полей, в которых вводится дата:<br><code> &lt;input type="text"
	* <i>add_to_text</i> ...&gt;</code><br><br>Параметр необязательный. По умолчанию -
	* "class=\"inputtext\"".
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;form name="form1" action="" method="POST"&gt;
	* &lt;table&gt;
	*     &lt;tr&gt;
	*         &lt;td&gt;Возраст:&lt;/td&gt;
	*         &lt;td&gt;&lt;?
	*             echo <b>CForm::GetNumberFilter</b>(
	*                 "ANKETA_AGE_USER_integer", 
	*                 "10", 
	*                 "class=\"inputtext\""
	*                 );
	*             ?&gt;&lt;/td&gt;
	*     &lt;/tr&gt;
	* &lt;/table&gt;
	* &lt;input type="submit" value="Фильтр"&gt;
	* &lt;/form&gt;
	* 
	* </ht
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/date/calendarperiod.php">CalendarPeriod</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/gettextfilter.php">CForm::GetTextFilter</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getdropdownfilter.php">CForm::GetDropDownFilter</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getdatefilter.php">CForm::GetDateFilter</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getexistflagfilter.php">CForm::GetExistFlagFilter</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/getnumberfilter.php
	* @author Bitrix
	*/
	public static 	function GetNumberFilter($FID, $size="10", $field="class=\"inputtext\"")
	{
		global $MESS;
		$var1 = "find_".$FID."_1";
		$var2 = "find_".$FID."_2";
		global ${$var1}, ${$var2};
		return '<input '.$field.' type="text" name="'.$var1.'" size="'.$size.'" value="'.htmlspecialcharsbx(${$var1}).'">&nbsp;'.GetMessage("FORM_TILL").'&nbsp;<input '.$field.' type="text" name="'.$var2.'" size="'.$size.'" value="'.htmlspecialcharsbx(${$var2}).'">';
	}


	/**
	* <p>Возвращает HTML код поля фильтра, предназначенного для фильтрации <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результатов</a> по факту существования значения <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> на <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопрос веб-формы</a> или факту существования значения <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a> веб-формы. Возвращаемый HTML код включает в себя флаг множественного выбора (<b>checkbox</b>).</p> <p class="note"><b>Примечание</b><br>Имя результирующего HTML поля будет сформировано по следующей маске:<br><b>find_</b><i>filter_sid</i></p>
	*
	*
	* @param int $filter_sid  Идентификатор поля фильтра. Формируется по следующему
	* шаблону:<br><nobr><i>FSID</i><b>_</b><i>QSID</i><b>_</b><i>PTYPE</i><b>_exist</b>,</nobr><br> где: <ul> <li>
	* <i>FSID</i> - символьный идентификатор <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">веб-формы</a>; </li> <li> <i>QSID</i> -
	* символьный идентификатор <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>/<a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a> веб-формы; </li> <li> <i>PTYPE</i> -
	* тип параметра по которому будет фильтрация, возможны следующие
	* значения: <ul> <li> <i>ANSWER_TEXT</i> - параметр <font color="green">ANSWER_TEXT</font> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a> веб-формы; </li> <li>
	* <i>ANSWER_VALUE</i> - параметр <font color="red">ANSWER_VALUE</font> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a> веб-формы; </li> <li>
	* <i>USER</i> - для <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>
	* веб-формы - вводимое с клавиатуры значение, для <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">полей</a> веб-формы - значение
	* этого поля веб-формы. </li> </ul> </li> </ul> Примеры: <ul> <li>ANKETA_USER_NAME_USER_text;
	* </li> <li>ANKETA_TEST_FIELD_USER_text. </li> </ul>
	*
	* @param string $add_to_checkbox = "class=\"inputcheckbox\"" Произвольный HTML который будет добавлен в тег флага выпадающего
	* списка:<br><code> &lt;input type="checkbox" <i>add_to_checkbox</i> ...&gt;</code><br><br>Параметр
	* необязательный. По умолчанию - "class=\"inputcheckbox\"".
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;form action="" method="POST"&gt;
	* &lt;table&gt;
	*     &lt;tr&gt;
	*         &lt;td&gt;Есть фотография?&lt;/td&gt;
	*         &lt;td&gt;&lt;?
	*             echo <b>CForm::GetExistFlagFilter</b>(
	*                 "ANKETA_PHOTO_USER_exist", 
	*                 "class=\"inputcheckbox\""
	*                 );
	*             ?&gt;&lt;/td&gt;
	*     &lt;/tr&gt;
	* &lt;/table&gt;
	* &lt;input type="submit" value="Фильтр"&gt;
	* &lt;/form&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getdatefilter.php">CForm::GetDateFilter</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getdropdownfilter.php">CForm::GetDropDownFilter</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getnumberfilter.php">CForm::GetNumberFilter</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/gettextfilter.php">CForm::GetTextFilter</a>
	* </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/getexistflagfilter.php
	* @author Bitrix
	*/
	public static 	function GetExistFlagFilter($FID, $field="class=\"inputcheckbox\"")
	{
		global $MESS;
		$var = "find_".$FID;
		global ${$var};
		return InputType("checkbox", $var, "Y", ${$var}, false, "", $field);
	}

public static 	function GetCrmFlagFilter($FID, $field="class=\"inputselect\"")
	{
		$var = "find_".$FID;
		global ${$var};
		$arr = array("reference_id"=>array('Y', 'N'), "reference"=>array(GetMessage('MAIN_YES'), GetMessage('MAIN_NO')));
		return SelectBoxFromArray($var, $arr, ${$var}, GetMessage("FORM_ALL"), $field);
	}


	/**
	* <p>Возвращает HTML код поля фильтра, представляющего из себя выпадающий список одиночного выбора. Данный выпадающий список может быть использован для фильтрации <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результатов</a> по значению <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> на <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопрос</a> <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">веб-формы</a>. Значения этого выпадающего списка формируются из значений параметров <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответов</a> - <font color="green">ANSWER_TEXT</font> или <font color="red">ANSWER_VALUE</font>.</p> <p class="note"><b>Примечание</b><br>Имя результирующего HTML поля будет сформировано по следующей маске:<br><b>find_</b><i>filter_sid</i></p>
	*
	*
	* @param int $field_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>/<a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a>.
	*
	* @param string $parameter_type  Тип параметра <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>,
	* допустимы следующие значения: <ul> <li> <b>ANSWER_TEXT</b>; </li> <li> <b>ANSWER_VALUE</b>.
	* </li> </ul>
	*
	* @param string $filter_sid  Идентификатор поля фильтра. Формируется по следующему
	* шаблону:<br><nobr><i>FSID</i><b>_</b><i>QSID</i><b>_</b><i>PTYPE</i><b>_dropdown</b>,</nobr><br> где: <ul>
	* <li> <i>FSID</i> - символьный идентификатор <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">веб-формы</a>; </li> <li> <i>QSID</i> -
	* символьный идентификатор <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>/<a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a> веб-формы; </li> <li> <i>PTYPE</i> -
	* тип параметра ответа, задаваемый в <i>parameter_type.</i> </li> </ul> Примеры: <ul>
	* <li>ANKETA_MARRIED_ANSWER_TEXT_dropdown; </li> <li>ANKETA_CAR_ANSWER_VALUE_dropdown. </li> </ul>
	*
	* @param string $add_to_dropdown = "class=\"inputselect\"" Произвольный HTML который будет добавлен в тег выпадающего
	* списка:<br><code> &lt;select <i>add_to_dropdown</i> ...&gt;</code><br><br>Параметр
	* необязательный. По умолчанию - "class=\"inputselect\"".
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;form name="form1" action="" method="POST"&gt;
	* &lt;table&gt;
	*     &lt;tr&gt;
	*         &lt;td&gt;Образование:&lt;/td&gt;
	*         &lt;td&gt;&lt;?
	*             $FIELD_ID = 15; // ID вопроса "Ваше образование?"
	*             echo <b>CForm::GetDropDownFilter</b>(
	*                 $FIELD_ID, 
	*                 "ANSWER_TEXT", 
	*                 "ANKETA_EDUCATION_ANSWER_TEXT_dropdown", 
	*                 "class=\"inputselect\""
	*                 );
	*             ?&gt;&lt;/td&gt;
	*     &lt;/tr&gt;
	* &lt;/table&gt;
	* &lt;input type="submit" value="Фильтр"&gt;
	* &lt;/form&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/gettextfilter.php">CForm::GetTextFilter</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getdatefilter.php">CForm::GetDateFilter</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getnumberfilter.php">CForm::GetNumberFilter</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getexistflagfilter.php">CForm::GetExistFlagFilter</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/getdropdownfilter.php
	* @author Bitrix
	*/
	public static 	function GetDropDownFilter($ID, $PARAMETER_NAME, $FID, $field="class=\"inputselect\"")
	{
		$err_mess = (CAllForm::err_mess())."<br>Function: GetDropDownFilter<br>Line: ";
		global $DB, $MESS, $strError;
		if ($PARAMETER_NAME=="ANSWER_VALUE") $str=", VALUE as REFERENCE"; else $str=", MESSAGE as REFERENCE";
		$ID = intval($ID);
		$strSql = "
			SELECT
				ID as REFERENCE_ID
				$str
			FROM
				b_form_answer
			WHERE
				FIELD_ID = $ID
			ORDER BY
				C_SORT
			";
		$z = $DB->Query($strSql,false,$err_mess.__LINE__);
		$ref = array();
		$ref_id = array();
		while ($zr = $z->Fetch())
		{
			if (strlen(trim($zr["REFERENCE"]))>0)
			{
				$ref[] = TruncateText($zr["REFERENCE"],70);
				$ref_id[] = $zr["REFERENCE_ID"];
			}
		}
		$arr = array("reference_id"=>$ref_id, "reference"=>$ref);
		$var = "find_".$FID;
		global ${$var};
		return SelectBoxFromArray($var, $arr, ${$var}, GetMessage("FORM_ALL"), $field);
	}


	/**
	* <p>Если массив, переданный в параметре <i>form_values,</i> инициализирован (например, в момент редактирования <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>), то метод возвращает текущее значение <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> типа "text", ID которого передается в параметре <i>answer_id</i>.</p> <p>Если массив, переданный в параметре <i>form_values,</i> не инициализирован (например, в момент создания нового <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>), то метод вернет значение по умолчанию для данного <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> (т.е. то что задается в <nobr><i>answer</i>["VALUE"]</nobr>).</p>
	*
	*
	* @param int $answer_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>.</bo
	*
	* @param array $answer  Массив, описывающий параметры <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>, обязательным
	* элементом которого является элемент с ключом <b>VALUE</b> и со
	* значением в котором содержится значение по умолчанию для <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>. Как правило таким
	* значением по умолчанию становится параметр <font color="red">ANSWER_VALUE</font>
	* <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>.
	*
	* @param mixed $form_values = false Ассоциированный массив значений, пришедших с веб-формы при
	* создании нового или редактировании существующего <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a> (стандартный
	* массив <b>$_REQUEST</b>). Данный массив может быть также получен с
	* помощью метода <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/getdatabyidforhtml.php">CFormResult::GetDataByIDForHTML</a>.<br><br>Параметр
	* необязательный. По умолчанию - "false".
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* //<******************************************
	*        Редактирование результата
	* ******************************************>//
	* 
	* $RESULT_ID = 12; // ID результата
	* 
	* // если была нажата кнопка "Сохранить" то
	* if (strlen($_REQUEST["save"])&gt;0)
	* {
	*     // используем данные пришедшие с формы
	*     $arrVALUES = $_REQUEST; 
	* }
	* else
	* {
	*     // сформируем этот массив из данных по результату
	*     $arrVALUES = CFormResult::GetDataByIDForHTML($RESULT_ID); 
	* }
	* ?&gt;
	* &lt;form action="" method="POST"&gt;
	* &lt;table&gt;
	*     &lt;tr&gt;
	*         &lt;td&gt;Фамилия:&lt;/td&gt;
	*         &lt;td&gt;&lt;?
	* 
	*             // массив описывающий однострочное текстовое поле
	*             // содержит минимально-необходимые поля
	*             $arAnswer = array(
	*                 "ID"            =&gt; 586,   // ID поля для ответа на вопрос "Ваша фамилия?"
	*                 "VALUE"         =&gt; "",    // параметр ANSWER_VALUE (значение по умолчанию)
	*                 "FIELD_WIDTH"   =&gt; 10,    // ширина поля
	*                 "FIELD_PARAM"   =&gt; ""     // параметры поля
	*                 );
	* 
	*             // получим текущее значение
	*             $value = <b>CForm::GetTextValue</b>($arAnswer["ID"], $arAnswer, $arrVALUES);
	* 
	*             // выведем поле
	*             echo CForm::GetTextField(
	*                 $arAnswer["ID"],
	*                 $value,
	*                 $arAnswer["FIELD_WIDTH"],
	*                 $arAnswer["FIELD_PARAM"]
	*                 );
	*             ?&gt;&lt;/td&gt;
	*     &lt;/tr&gt;
	* &lt;/table&gt;
	* &lt;input type="submit" name="save" value="Сохранить"&gt;
	* &lt;/form&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/gettextfield.php">CForm::GetTextField</a>
	* </li></ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/gettextvalue.php
	* @author Bitrix
	*/
	public static 	function GetTextValue($FIELD_NAME, $arAnswer, $arrVALUES=false)
	{
		$fname = "form_text_".$FIELD_NAME;
		if (is_array($arrVALUES) && isset($arrVALUES[$fname])) $value = $arrVALUES[$fname];
		else $value = $arAnswer["VALUE"];
		return $value;
	}

	public static function GetHiddenValue($FIELD_NAME, $arAnswer, $arrVALUES=false)
	{
		$fname = "form_hidden_".$FIELD_NAME;
		if (is_array($arrVALUES) && isset($arrVALUES[$fname])) $value = $arrVALUES[$fname];
		else $value = $arAnswer["VALUE"];
		return $value;
	}

	
	/**
	* <p>Если массив, переданный в параметре <i>form_values,</i> инициализирован (например, в момент редактирования <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>), то метод возвращает текущее значение <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> типа "password", ID которого передается в параметре <i>answer_id</i>.</p> <p>Если массив, переданный в параметре <i>form_values,</i> не инициализирован (например, в момент создания нового <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>), то метод вернет значение по умолчанию для данного <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> (т.е. то что задается в <nobr><i>answer</i>["VALUE"]</nobr>).</p>
	*
	*
	* @param int $answer_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>.</bo
	*
	* @param array $answer  Массив, описывающий параметры <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>, обязательным
	* элементом которого является элемент с ключом <b>VALUE</b> и значением,
	* в котором содержится значение по умолчанию для <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>. Как правило, таким
	* значением по умолчанию становится параметр <font color="red">ANSWER_VALUE</font>
	* <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>.
	*
	* @param mixed $form_values = false Ассоциированный массив значений, пришедших с веб-формы при
	* создании нового или редактировании существующего <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a> (стандартный
	* массив <b>$_REQUEST</b>). Данный массив может быть также получен с
	* помощью метода <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/getdatabyidforhtml.php">CFormResult::GetDataByIDForHTML</a>.<br><br>Параметр
	* необязательный. По умолчанию - "false".
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* //<******************************************
	*        Редактирование результата
	* ******************************************>//
	* 
	* $RESULT_ID = 12; // ID результата
	* 
	* // если была нажата кнопка "Сохранить" то
	* if (strlen($_REQUEST["save"])&gt;0)
	* {
	*     // используем данные пришедшие с формы
	*     $arrVALUES = $_REQUEST; 
	* }
	* else
	* {
	*     // сформируем этот массив из данных по результату
	*     $arrVALUES = CFormResult::GetDataByIDForHTML($RESULT_ID); 
	* }
	* ?&gt;
	* &lt;form action="" method="POST"&gt;
	* &lt;table&gt;
	*     &lt;tr&gt;
	*         &lt;td&gt;Пароль:&lt;/td&gt;
	*         &lt;td&gt;&lt;?
	* 
	*             // массив описывающий поле для ввода пароля
	*             // содержит минимально-необходимые поля
	*             $arAnswer = array(
	*                 "ID"            =&gt; 609,   // ID поля для ответа на вопрос "Пароль"
	*                 "FIELD_WIDTH"   =&gt; 10,    // ширина поля
	*                 "FIELD_PARAM"   =&gt; ""     // параметры поля
	*                 );
	* 
	*             // получим текущее значение
	*             $value = <b>CForm::GetPasswordValue</b>($arAnswer["ID"], $arAnswer, $arrVALUES);
	* 
	*             // выведем поле
	*             echo CForm::GetPasswordField(
	*                 $arAnswer["ID"],
	*                 $value,
	*                 $arAnswer["FIELD_WIDTH"],
	*                 $arAnswer["FIELD_PARAM"]
	*                 );
	*             ?&gt;&lt;/td&gt;
	*     &lt;/tr&gt;
	* &lt;/table&gt;
	* &lt;input type="submit" name="save" value="Сохранить"&gt;
	* &lt;/form&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getpasswordfield.php">CForm::GetPasswordField</a>
	* </li></ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/getpasswordvalue.php
	* @author Bitrix
	*/
	public static function GetPasswordValue($FIELD_NAME, $arAnswer, $arrVALUES=false)
	{
		$fname = "form_password_".$FIELD_NAME;
		if (is_array($arrVALUES) && isset($arrVALUES[$fname])) $value = $arrVALUES[$fname];
		else $value = $arAnswer["VALUE"];
		return $value;
	}

public static 	function GetEmailValue($FIELD_NAME, $arAnswer, $arrVALUES=false)
	{
		$fname = "form_email_".$FIELD_NAME;
		if (is_array($arrVALUES) && isset($arrVALUES[$fname])) $value = $arrVALUES[$fname];
		else $value = $arAnswer["VALUE"];
		return $value;
	}

public static 	function GetUrlValue($FIELD_NAME, $arAnswer, $arrVALUES=false)
	{
		$fname = "form_url_".$FIELD_NAME;
		if (is_array($arrVALUES) && isset($arrVALUES[$fname])) $value = $arrVALUES[$fname];
		else $value = $arAnswer["VALUE"];
		return $value;
	}


	/**
	* <p>Возвращает HTML код однострочного текстового поля. Данное поле предназначено для ввода <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> типа "text".</p> <p>Метод может использоваться как в форме создания нового <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>, так и в форме редактирования существующего.</p> <p class="note"><b>Примечание</b><br>Имя результирующего HTML поля будет сформировано по следующей маске:<br><b>form_text_</b><i>answer_id</i></p>
	*
	*
	* @param int $answer_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>.</bo
	*
	* @param string $value = "" Значение результирующего текстового поля:<br><code> &lt;input type="text"
	* value="<i>value</i>" ...&gt;</code><br><br>Параметр необязательный. По умолчанию - "".
	*
	* @param mixed $size = "" Ширина результирующего текстового поля:<br><code> &lt;input type="text"
	* size="<i>size</i>" ...&gt;</code><br><br>Параметр необязательный. По умолчанию - "".
	*
	* @param string $add_to_text = "class=\"inputtext\"" Произвольный HTML, который будет добавлен в результирующий HTML тег
	* текстового поля:<br><code> &lt;input type="text" <i>add_to_text</i> ...&gt;</code><br><br>Параметр
	* необязательный. По умолчанию - "class=\"inputtext\"".
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* //<******************************************
	*        Редактирование результата
	* ******************************************>//
	* 
	* $RESULT_ID = 12; // ID результата
	* 
	* // если была нажата кнопка "Сохранить" то
	* if (strlen($_REQUEST["save"])&gt;0)
	* {
	*     // используем данные пришедшие с формы
	*     $arrVALUES = $_REQUEST; 
	* }
	* else
	* {
	*     // сформируем этот массив из данных по результату
	*     $arrVALUES = CFormResult::GetDataByIDForHTML($RESULT_ID); 
	* }
	* ?&gt;
	* &lt;form action="" method="POST"&gt;
	* &lt;table&gt;
	*     &lt;tr&gt;
	*         &lt;td&gt;Фамилия:&lt;/td&gt;
	*         &lt;td&gt;&lt;?
	* 
	*             // массив описывающий однострочное текстовое поле
	*             // содержит минимально-необходимые поля
	*             $arAnswer = array(
	*                 "ID"            =&gt; 586,   // ID поля для ответа на вопрос "Ваша фамилия?"
	*                 "VALUE"         =&gt; "",    // параметр ANSWER_VALUE (значение по умолчанию)
	*                 "FIELD_WIDTH"   =&gt; 10,    // ширина поля
	*                 "FIELD_PARAM"   =&gt; ""     // параметры поля
	*                 );
	* 
	*             // получим текущее значение
	*             $value = CForm::GetTextValue($arAnswer["ID"], $arAnswer, $arrVALUES);
	* 
	*             // выведем поле
	*             echo <b>CForm::GetTextField</b>(
	*                 $arAnswer["ID"],
	*                 $value,
	*                 $arAnswer["FIELD_WIDTH"],
	*                 $arAnswer["FIELD_PARAM"]
	*                 );
	*             ?&gt;&lt;/td&gt;
	*     &lt;/tr&gt;
	* &lt;/table&gt;
	* &lt;input type="submit" name="save" value="Сохранить"&gt;
	* &lt;/form&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/gettextvalue.php">CForm::GetTextValue</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/form/htmlnames.php">Имена HTML полей</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/gettextfield.php
	* @author Bitrix
	*/
	public static 	function GetTextField($FIELD_NAME, $VALUE="", $SIZE="", $PARAM="")
	{
		if (strlen($PARAM)<=0) $PARAM = " class=\"inputtext\" ";
		return "<input type=\"text\" ".$PARAM." name=\"form_text_".$FIELD_NAME."\" value=\"".htmlspecialcharsbx($VALUE)."\" size=\"".$SIZE."\" />";
	}

public static 	function GetHiddenField($FIELD_NAME, $VALUE="", $PARAM="")
	{
		return "<input type=\"hidden\" ".$PARAM." name=\"form_hidden_".$FIELD_NAME."\" value=\"".htmlspecialcharsbx($VALUE)."\" />";
	}


public static 	function GetEmailField($FIELD_NAME, $VALUE="", $SIZE="", $PARAM="")
	{
		if (strlen($PARAM)<=0) $PARAM = " class=\"inputtext\" ";
		return "<input type=\"text\" ".$PARAM." name=\"form_email_".$FIELD_NAME."\" value=\"".htmlspecialcharsbx($VALUE)."\" size=\"".$SIZE."\" />";
	}

public static 	function GetUrlField($FIELD_NAME, $VALUE="", $SIZE="", $PARAM="")
	{
		if (strlen($PARAM)<=0) $PARAM = " class=\"inputtext\" ";
		return "<input type=\"text\" ".$PARAM." name=\"form_url_".$FIELD_NAME."\" value=\"".htmlspecialcharsbx($VALUE)."\" size=\"".$SIZE."\" />";
	}


	/**
	* <p>Возвращает HTML код однострочного текстового поля. Данное поле предназначено для ввода <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> типа "password".</p> <p>Метод может использоваться как в форме создания нового <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>, так и в форме редактирования существующего.</p> <p class="note"><b>Примечание</b><br>Имя результирующего HTML поля будет сформировано по следующей маске:<br><b>form_password_</b><i>answer_id</i></p>
	*
	*
	* @param int $answer_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>.</bo
	*
	* @param string $value = "" Значение результирующего текстового поля:<br><code> &lt;input type="password"
	* value="<i>value</i>" ...&gt;</code><br><br>Параметр необязательный. По умолчанию - "".
	*
	* @param mixed $size = "" Ширина результирующего текстового поля:<br><code> &lt;input type="password"
	* size="<i>size</i>" ...&gt;</code><br><br>Параметр необязательный. По умолчанию - "".
	*
	* @param string $add_to_text = "class=\"inputtext\"" Произвольный HTML, который будет добавлен в результирующий HTML тег
	* текстового поля:<br><code> &lt;input type="password" <i>add_to_text</i>
	* ...&gt;</code><br><br>Параметр необязательный. По умолчанию - "class=\"inputtext\"".
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* //<******************************************
	*        Редактирование результата
	* ******************************************>//
	* 
	* $RESULT_ID = 12; // ID результата
	* 
	* // если была нажата кнопка "Сохранить" то
	* if (strlen($_REQUEST["save"])&gt;0)
	* {
	*     // используем данные пришедшие с формы
	*     $arrVALUES = $_REQUEST; 
	* }
	* else
	* {
	*     // сформируем этот массив из данных по результату
	*     $arrVALUES = CFormResult::GetDataByIDForHTML($RESULT_ID); 
	* }
	* ?&gt;
	* &lt;form action="" method="POST"&gt;
	* &lt;table&gt;
	*     &lt;tr&gt;
	*         &lt;td&gt;Пароль:&lt;/td&gt;
	*         &lt;td&gt;&lt;?
	* 
	*             // массив описывающий поле для ввода пароля
	*             // содержит минимально-необходимые поля
	*             $arAnswer = array(
	*                 "ID"            =&gt; 609,   // ID поля для ответа на вопрос "Пароль"
	*                 "FIELD_WIDTH"   =&gt; 10,    // ширина поля
	*                 "FIELD_PARAM"   =&gt; ""     // параметры поля
	*                 );
	* 
	*             // получим текущее значение
	*             $value = CForm::GetPasswordValue($arAnswer["ID"], $arAnswer, $arrVALUES);
	* 
	*             // выведем поле
	*             echo <b>CForm::GetPasswordField</b>(
	*                 $arAnswer["ID"],
	*                 $value,
	*                 $arAnswer["FIELD_WIDTH"],
	*                 $arAnswer["FIELD_PARAM"]
	*                 );
	*             ?&gt;&lt;/td&gt;
	*     &lt;/tr&gt;
	* &lt;/table&gt;
	* &lt;input type="submit" name="save" value="Сохранить"&gt;
	* &lt;/form&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getpasswordvalue.php">CForm::GetPasswordValue</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/htmlnames.php">Имена HTML полей</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/getpasswordfield.php
	* @author Bitrix
	*/
	public static 	function GetPasswordField($FIELD_NAME, $VALUE="", $SIZE="", $PARAM="")
	{
		if (strlen($PARAM)<=0) $PARAM = " class=\"inputtext\" ";
		return "<input type=\"password\" ".$PARAM." name=\"form_password_".$FIELD_NAME."\" value=\"".htmlspecialcharsbx($VALUE)."\" size=\"".$SIZE."\" />";
	}

	f
	/**
	* <p>Если массив, переданный в параметре <i>form_values,</i> инициализирован (например, в момент редактирования <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>), то метод возвращает ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>, выбранного среди группы ответов типа "dropdown" на вопрос, символьный идентификатор которого указан в параметре <i>question_sid</i>.</p> <p>Если массив, переданный в параметре <i>form_values,</i> не инициализирован (например, в момент создания нового <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>), то метод вернет ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> выбранного по умолчанию. Поиск ответа по умолчанию осуществляется среди группы ответов, задаваемых в параметре <i>answer_list,</i> посредством поиска строки "checked" в <nobr><i>answer_list</i>["param"][i]</nobr>; если такая строка будет найдена, то метод вернет ID данного <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> (хранимый в <nobr><i>answer_list</i>["reference_id"][i]</nobr>).</p>
	*
	*
	* @param string $question_sid  Символьный идентификатор <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>.
	*
	* @param array $answer_list  Массив ответов типа "dropdown" на вопрос <i>question_sid</i>. Минимально
	* требуемая структура данного массива: <pre> Array ( [reference_id] =&gt; Array ( [0]
	* =&gt; <i>ID ответа 1</i> [1] =&gt; <i>ID ответа 2</i> [2] =&gt; <i>ID ответа 3</i> ... ) [param] =&gt;
	* Array ( [0] =&gt; <i>параметр ответа 1</i> [1] =&gt; <i>параметр ответа 2</i> [2] =&gt;
	* <i>параметр ответа 3</i> ... ) ) </pre>
	*
	* @param mixed $form_values = false Ассоциированный массив значений, пришедших с веб-формы при
	* создании нового или редактировании существующего <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a> (стандартный
	* массив <b>$_REQUEST</b>). Данный массив может быть также получен с
	* помощью функции <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/getdatabyidforhtml.php">CFormResult::GetDataByIDForHTML</a>.<br><br>Параметр
	* необязательный. По умолчанию - "false".
	*
	* @return int 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* //<******************************************
	*        Редактирование результата
	* ******************************************>//
	* 
	* $RESULT_ID = 12; // ID результата
	* 
	* // если была нажата кнопка "Сохранить" то
	* if (strlen($_REQUEST["save"])&gt;0)
	* {
	*     // используем данные пришедшие с формы
	*     $arrVALUES = $_REQUEST; 
	* }
	* else
	* {
	*     // сформируем этот массив из данных по результату
	*     $arrVALUES = CFormResult::GetDataByIDForHTML($RESULT_ID); 
	* }
	* ?&gt;
	* &lt;form action="" method="POST"&gt;
	* &lt;table&gt;
	*     &lt;tr&gt;
	*         &lt;td&gt;*Возраст:&lt;/td&gt;
	*         &lt;td&gt;&lt;?
	* 
	*             // символьный идентификатор вопроса
	*             $QUESTION_SID = "AGE"; 
	* 
	*             // массив описывающий элементы выпадающего списка
	*             $arDropDown = array (
	* 
	*                 "reference" =&gt; array (
	*                         "-",
	*                         "10-19",
	*                         "20-29",
	*                         "30-39",
	*                         "40-49",
	*                         "50-59",
	*                         "60 и старше"
	*                     ),
	* 
	*                 "reference_id" =&gt; array (
	*                         608,
	*                         596,
	*                         597,
	*                         598,
	*                         599,
	*                         600,
	*                         601
	*                     ),
	* 
	*                 "param" =&gt; array (
	*                         "not_answer class=\"inputselect\"", // не является ответом
	*                         "",
	*                         "checked", // значение по умолчанию
	*                         "",
	*                         "",
	*                         "",
	*                         ""
	*                     )
	*             );
	* 
	*             // получим текущее значение выпадающего списка
	*             $value = <b>CForm::GetDropDownValue</b>($QUESTION_SID, $arDropDown, $arrVALUES);
	* 
	*             // выведем выпадающий список
	*             echo CForm::GetDropDownField(
	*                 $QUESTION_SID,           // символьный идентификатор вопроса
	*                 $arDropDown,             // массив описывающий элементы списка
	*                 $value,                  // значение выбранного элемента списка
	*                 "class=\"inputselect\""  // стиль выпадающего списка
	*                 );            
	*             ?&gt;&lt;/td&gt;
	*     &lt;/tr&gt;
	* &lt;/table&gt;
	* &lt;input type="submit" name="save" value="Сохранить"&gt;
	* &lt;/form&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getdropdownfield.php">CForm::GetDropDownField</a>
	* </li></ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/getdropdownvalue.php
	* @author Bitrix
	*/
	public static unction GetDropDownValue($FIELD_NAME, $arDropDown, $arrVALUES=false)
	{
		$fname = "form_dropdown_".$FIELD_NAME;
		if (is_array($arrVALUES) && isset($arrVALUES[$fname]))
		{
			$value = intval($arrVALUES[$fname]);
		}
		elseif (is_array($arDropDown[$FIELD_NAME]["param"]))
		{
			$c = count($arDropDown[$FIELD_NAME]["param"]);
			if ($c>0)
			{
				for ($i=0; $i<=$c-1; $i++)
				{
					if (strpos(strtolower($arDropDown[$FIELD_NAME]["param"][$i]), "selected")!==false || strpos(strtolower($arDropDown[$FIELD_NAME]["param"][$i]), "checked")!==false)
					{
						$value = $arDropDown[$FIELD_NAME]["reference_id"][$i];
						break;
					}
				}
			}
		}
		return $value;
	}


	/**
	* <p>Возвращает HTML код выпадающего списка одиночного выбора, предназначенного для выбора <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> из группы ответов типа "dropdown" на вопрос, символьный идентификатор которого передается в параметре <i>question_sid</i>.</p> <p>Метод может использоваться как в форме создания нового <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>, так и в форме редактирования существующего.</p> <p class="note"><b>Примечание</b><br>Имя результирующего HTML поля будет сформировано по следующей маске:<br><b>form_dropdown_</b><i>question_sid</i></p>
	*
	*
	* @param string $question_sid  Символьный идентификатор <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>.
	*
	* @param array $list  Массив ответов типа "dropdown" на вопрос <i>question_sid</i>. Минимально
	* требуемая структура данного массива: <pre> Array ( [reference] =&gt; Array ( [0] =&gt;
	* <i>заголовок ответа 1</i> [1] =&gt; <i>заголовок ответа 2</i> [2] =&gt;
	* <i>заголовок ответа 3</i> ... ) [reference_id] =&gt; Array ( [0] =&gt; <i>ID ответа 1</i> [1] =&gt;
	* <i>ID ответа 2</i> [2] =&gt; <i>ID ответа 3</i> ... ) ) </pre> В данном массиве под
	* <i>заголовком элемента</i> понимается параметр <font
	* color="green">ANSWER_TEXT</font> <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>.
	*
	* @param mixed $value = "" Если в данном параметре будет передано значение совпадающее с <i>ID
	* ответа</i>, то данный ответ будет выбран в результирующем
	* выпадающем списке:<br><code> &lt;option value="<i>значение элемента</i>"
	* selected&gt;<i>заголовок ответа</i>&lt;/option&gt;</code><br><br>Параметр
	* необязательный. По умолчанию - "".
	*
	* @param string $add_to_dropdown = "class=\"inputselect\"" Произвольный HTML который будет добавлен в результирующий HTML
	* тег:<br><code> &lt;select <i>add_to_dropdown</i> ...&gt;</code><br><br>Параметр необязательный.
	* По умолчанию - "class=\"inputselect\"".
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* //<******************************************
	*        Редактирование результата
	* ******************************************>//
	* 
	* $RESULT_ID = 12; // ID результата
	* 
	* // если была нажата кнопка "Сохранить" то
	* if (strlen($_REQUEST["save"])&gt;0)
	* {
	*     // используем данные пришедшие с формы
	*     $arrVALUES = $_REQUEST; 
	* }
	* else
	* {
	*     // сформируем этот массив из данных по результату
	*     $arrVALUES = CFormResult::GetDataByIDForHTML($RESULT_ID); 
	* }
	* ?&gt;
	* &lt;form action="" method="POST"&gt;
	* &lt;table&gt;
	*     &lt;tr&gt;
	*         &lt;td&gt;*Возраст:&lt;/td&gt;
	*         &lt;td&gt;&lt;?
	* 
	*             // символьный идентификатор вопроса
	*             $QUESTION_SID = "AGE"; 
	* 
	*             // массив описывающий элементы выпадающего списка
	*             $arDropDown = array (
	* 
	*                 "reference" =&gt; array (
	*                         "-",
	*                         "10-19",
	*                         "20-29",
	*                         "30-39",
	*                         "40-49",
	*                         "50-59",
	*                         "60 и старше"
	*                     ),
	* 
	*                 "reference_id" =&gt; array (
	*                         608,
	*                         596,
	*                         597,
	*                         598,
	*                         599,
	*                         600,
	*                         601
	*                     ),
	* 
	*                 "param" =&gt; array (
	*                         "not_answer class=\"inputselect\"", // не является ответом
	*                         "",
	*                         "checked", // значение по умолчанию
	*                         "",
	*                         "",
	*                         "",
	*                         ""
	*                     )
	*             );
	* 
	*             // получим текущее значение выпадающего списка
	*             $value = CForm::GetDropDownValue($QUESTION_SID, $arDropDown, $arrVALUES);
	* 
	*             // выведем выпадающий список
	*             echo <b>CForm::GetDropDownField</b>(
	*                 $QUESTION_SID,           // символьный идентификатор вопроса
	*                 $arDropDown,             // массив описывающий элементы списка
	*                 $value,                  // значение выбранного элемента списка
	*                 "class=\"inputselect\""  // стиль выпадающего списка
	*                 );            
	*             ?&gt;&lt;/td&gt;
	*     &lt;/tr&gt;
	* &lt;/table&gt;
	* &lt;input type="submit" name="save" value="Сохранить"&gt;
	* &lt;/form&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getdropdownvalue.php">CForm::GetDropDownValue</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/htmlnames.php">Имена HTML полей</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/getdropdownfield.php
	* @author Bitrix
	*/
	public static 	function GetDropDownField($FIELD_NAME, $arDropDown, $VALUE, $PARAM="")
	{
		if (strlen($PARAM)<=0) $PARAM = " class=\"inputselect\" ";
		return SelectBoxFromArray("form_dropdown_".$FIELD_NAME, $arDropDown, $VALUE, "", $PARAM);
	}

	
	/**
	* <p>Если массив, переданный в параметре <i>form_values,</i> инициализирован (например, в момент редактирования <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>), то метод возвращает массив ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответов</a>, выбранных среди группы ответов типа "<b>multiselect</b>" на вопрос, символьный идентификатор которого указан в параметре <i>question_sid</i>.</p> <p>Если массив, переданный в параметре <i>form_values,</i> не инициализирован (например, в момент создания нового <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>), то метод вернет массив ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответов</a>, выбранных по умолчанию. Поиск ответа по умолчанию осуществляется среди группы ответов, задаваемых в параметре <i>answer_list,</i> посредством поиска строки "checked" в <nobr><i>answer_list</i>["param"][i]</nobr>; если такая строка будет найдена, то метод добавит данный ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> (хранимый в <nobr><i>answer_list</i>["reference_id"][i]</nobr>) в результирующий массив.</p>
	*
	*
	* @param string $question_sid  Символьный идентификатор <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>.
	*
	* @param array $answer_list  Массив ответов типа "multiselect" на вопрос <i>question_sid</i>. Минимально
	* требуемая структура данного массива: <pre> Array ( [reference_id] =&gt; Array ( [0]
	* =&gt; <i>ID ответа 1</i> [1] =&gt; <i>ID ответа 2</i> [2] =&gt; <i>ID ответа 3</i> ... ) [param] =&gt;
	* Array ( [0] =&gt; <i>параметр ответа 1</i> [1] =&gt; <i>параметр ответа 2</i> [2] =&gt;
	* <i>параметр ответа 3</i> ... ) ) </pre>
	*
	* @param mixed $form_values = false Ассоциированный массив значений, пришедших с веб-формы при
	* создании нового или редактировании существующего <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a> (стандартный
	* массив <b>$_REQUEST</b>). Данный массив может быть также получен с
	* помощью функции <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/getdatabyidforhtml.php">CFormResult::GetDataByIDForHTML</a>.<br><br>Параметр
	* необязательный. По умолчанию - "false".
	*
	* @return array 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* //<******************************************
	*        Редактирование результата
	* ******************************************>//
	* 
	* $RESULT_ID = 12; // ID результата
	* 
	* // если была нажата кнопка "Сохранить" то
	* if (strlen($_REQUEST["save"])&gt;0)
	* {
	*   // используем данные пришедшие с формы
	*   $arrVALUES = $_REQUEST; 
	* }
	* else
	* {
	*   // сформируем этот массив из данных по результату
	*   $arrVALUES = CFormResult::GetDataByIDForHTML($RESULT_ID); 
	* }
	* ?&gt;
	* &lt;form action="" method="POST"&gt;
	* &lt;table&gt;
	*   &lt;tr&gt;
	*     &lt;td&gt;Ваше образование:&lt;/td&gt;
	*     &lt;td&gt;&lt;?
	* 
	*       // символьный идентификатор вопроса
	*       $QUESTION_SID = "EDUCATION"; 
	* 
	*       // массив описывающий элементы списка множественного выбора
	*       $arMultiSelect = array (
	* 
	*         "reference" =&gt; array (
	*             "начальное",
	*             "средне-специальное",
	*             "высшее",
	*           ),
	* 
	*         "reference_id" =&gt; array (
	*             602,
	*             603,
	*             604,
	*           ),
	* 
	*         "param" =&gt; array (
	*             "",
	*             "",
	*             "checked", // значение по умолчанию
	*           )
	*       );
	* 
	*       // получим текущее значение выпадающего списка
	*       $arValues = <b>CForm::GetMultiSelectValue</b>($QUESTION_SID, $arMultiSelect, $arrVALUES);
	* 
	*       // выведем список множественного выбора
	*       echo CForm::GetMultiSelectField(
	*         $QUESTION_SID,           // символьный идентификатор вопроса
	*         $arMultiSelect,          // массив описывающий элементы списка
	*         $arValues,               // значения выбранных элементов списка
	*         10,                      // высота списка
	*         "class=\"inputselect\""  // стиль списка
	*         );      
	*       ?&gt;&lt;/td&gt;
	*   &lt;/tr&gt;
	* &lt;/table&gt;
	* &lt;input type="submit" name="save" value="Сохранить"&gt;
	* &lt;/form&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getmultiselectfield.php">CForm::GetMultiSelectField</a>
	* </li></ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/getmultiselectvalue.php
	* @author Bitrix
	*/
	public static function GetMultiSelectValue($FIELD_NAME, $arMultiSelect, $arrVALUES=false)
	{
		$fname = "form_multiselect_".$FIELD_NAME;
		if (is_array($arrVALUES) && isset($arrVALUES[$fname]))
		{
			$value=$arrVALUES[$fname];
		}
		elseif (is_array($arMultiSelect[$FIELD_NAME]["param"]))
		{
			$c = count($arMultiSelect[$FIELD_NAME]["param"]);
			if ($c>0)
			{
				for ($i=0;$i<=$c-1;$i++)
				{
					if (strpos(strtolower($arMultiSelect[$FIELD_NAME]["param"][$i]), "selected")!==false || strpos(strtolower($arMultiSelect[$FIELD_NAME]["param"][$i]), "checked")!==false)
						$value[] = $arMultiSelect[$FIELD_NAME]["reference_id"][$i];
				}
			}
		}
		return $value;
	}


	/**
	* <p>Возвращает HTML код списка множественного выбора, предназначенного для выбора <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответов</a> из группы ответов типа "multiselect" на вопрос, символьный идентификатор которого передается в параметре <i>question_sid</i>.</p> <p>Метод может использоваться как в форме создания нового <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>, так и в форме редактирования существующего.</p> <p class="note"><b>Примечание</b><br>Имя результирующего HTML поля будет сформировано по следующей маске:<br><b>form_multiselect_</b><i>question_sid</i><b>[]</b></p>
	*
	*
	* @param string $question_sid  Символьный идентификатор <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>.
	*
	* @param array $list  Массив ответов типа "multiselect" на вопрос <i>question_sid</i>. Минимально
	* требуемая структура данного массива: <pre> Array ( [reference] =&gt; array ( [0] =&gt;
	* <i>заголовок ответа 1</i> [1] =&gt; <i>заголовок ответа 2</i> [2] =&gt;
	* <i>заголовок ответа 3</i> ... ) [reference_id] =&gt; array ( [0] =&gt; <i>ID ответа 1</i> [1] =&gt;
	* <i>ID ответа 2</i> [2] =&gt; <i>ID ответа 3</i> ... ) ) </pre> В данном массиве под
	* <i>заголовком ответа</i> понимается параметр <font color="green">ANSWER_TEXT</font> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>.
	*
	* @param array $values = array() Если в данном параметре будет передан массив со значениями,
	* совпадающими с <i>ID ответов</i>, данные ответы будут выбраны
	* (выделены) в результирующем списке:<br><code> &lt;option value="<i>значение
	* элемента</i>" selected&gt;<i>заголовок
	* элемента</i>&lt;/option&gt;</code><br><br>Параметр необязательный. По умолчанию
	* - array() (пустой массив).
	*
	* @param mixed $height = "" Высота результирующего списка множественного выбора:<br><code> &lt;select
	* multiple size="<i>height</i>" ...&gt;</code><br><br>Параметр необязательный. По
	* умолчанию - "class=\"inputselect\"".
	*
	* @param string $add_to_multiselect = "class=\"inputselect\"" Произвольный HTML, который будет добавлен в результирующий HTML
	* тег:<br><code> &lt;select <i>add_to_multiselect</i> ...&gt;</code><br><br>Параметр
	* необязательный. По умолчанию - "class=\"inputselect\"".
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* //<******************************************
	*        Редактирование результата
	* ******************************************>//
	* 
	* $RESULT_ID = 12; // ID результата
	* 
	* // если была нажата кнопка "Сохранить" то
	* if (strlen($_REQUEST["save"])&gt;0)
	* {
	*   // используем данные пришедшие с формы
	*   $arrVALUES = $_REQUEST; 
	* }
	* else
	* {
	*   // сформируем этот массив из данных по результату
	*   $arrVALUES = CFormResult::GetDataByIDForHTML($RESULT_ID); 
	* }
	* ?&gt;
	* &lt;form action="" method="POST"&gt;
	* &lt;table&gt;
	*   &lt;tr&gt;
	*     &lt;td&gt;Ваше образование:&lt;/td&gt;
	*     &lt;td&gt;&lt;?
	* 
	*       // символьный идентификатор вопроса
	*       $QUESTION_SID = "EDUCATION"; 
	* 
	*       // массив описывающий элементы списка множественного выбора
	*       $arMultiSelect = array (
	* 
	*         "reference" =&gt; array (
	*             "начальное",
	*             "средне-специальное",
	*             "высшее",
	*           ),
	* 
	*         "reference_id" =&gt; array (
	*             602,
	*             603,
	*             604,
	*           ),
	* 
	*         "param" =&gt; array (
	*             "",
	*             "",
	*             "checked", // значение по умолчанию
	*           )
	*       );
	* 
	*       // получим текущее значение выпадающего списка
	*       $arValues = CForm::GetMultiSelectValue($QUESTION_SID, $arMultiSelect, $arrVALUES);
	* 
	*       // выведем список множественного выбора
	*       echo <b>CForm::GetMultiSelectField</b>(
	*         $QUESTION_SID,           // символьный идентификатор вопроса
	*         $arMultiSelect,          // массив описывающий элементы списка
	*         $arValues,               // значения выбранных элементов списка
	*         10,                      // высота списка
	*         "class=\"inputselect\""  // стиль списка
	*         );      
	*       ?&gt;&lt;/td&gt;
	*   &lt;/tr&gt;
	* &lt;/table&gt;
	* &lt;input type="submit" name="save" value="Сохранить"&gt;
	* &lt;/form&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getmultiselectvalue.php">CForm::GetMultiSelectValue</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/form/htmlnames.php">Имена HTML полей</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/getmultiselectfield.php
	* @author Bitrix
	*/
	public static 	function GetMultiSelectField($FIELD_NAME, $arMultiSelect, $arSELECTED=array(), $HEIGHT="", $PARAM="")
	{
		if (strlen($PARAM)<=0) $PARAM = " class=\"inputselect\" ";
		return SelectBoxMFromArray("form_multiselect_".$FIELD_NAME."[]", $arMultiSelect, $arSELECTED, "", false, $HEIGHT, $PARAM);
	}


	/**
	* <p>Если массив, переданный в параметре <i>form_values,</i> инициализирован (например, в момент редактирования <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>), то метод возвращает текущее значение <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> типа "date", ID которого передается в параметре <i>answer_id</i>.</p> <p>Если массив, переданный в параметре <i>form_values,</i> не инициализирован (например, в момент создания нового <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>), то метод вернет значение по умолчанию для данного <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> (т.е. то что задается в <nobr><i>answer</i>["VALUE"]</nobr>).</p>
	*
	*
	* @param int $answer_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>.</bo
	*
	* @param array $answer  Массив, описывающий параметры <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>, обязательным
	* элементом которого является элемент с ключом <b>VALUE</b> и значением,
	* в котором содержится значение по умолчанию для <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>. Как правило, таким
	* значением по умолчанию становится параметр <font color="red">ANSWER_VALUE</font>
	* <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>.
	*
	* @param mixed $form_values = false Ассоциированный массив значений, пришедших с веб-формы при
	* создании нового или редактировании существующего <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a> (стандартный
	* массив <b>$_REQUEST</b>). Данный массив может быть также получен с
	* помощью метода <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/getdatabyidforhtml.php">CFormResult::GetDataByIDForHTML</a>.<br><br>Параметр
	* необязательный. По умолчанию - "false".
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* //<******************************************
	*        Редактирование результата
	* ******************************************>//
	* 
	* $RESULT_ID = 12; // ID результата
	* 
	* // если была нажата кнопка "Сохранить" то
	* if (strlen($_REQUEST["save"])&gt;0)
	* {
	*     // используем данные пришедшие с формы
	*     $arrVALUES = $_REQUEST; 
	* }
	* else
	* {
	*     // сформируем этот массив из данных по результату
	*     $arrVALUES = CFormResult::GetDataByIDForHTML($RESULT_ID); 
	* }
	* ?&gt;
	* &lt;form name="ANKETA" action="" method="POST"&gt;
	* &lt;table&gt;
	*     &lt;tr&gt;
	*         &lt;td&gt;Дата рождения:&lt;/td&gt;
	*         &lt;td&gt;&lt;?
	* 
	*             // массив описывающий поле для ввода даты
	*             // содержит минимально-необходимые поля
	*             $arAnswer = array(
	*                 "ID"            =&gt; 587,   // ID поля для ответа на вопрос "Дата рождения?"
	*                 "VALUE"         =&gt; "",    // параметр ANSWER_VALUE (значение по умолчанию)
	*                 "FIELD_WIDTH"   =&gt; 10,    // ширина поля
	*                 "FIELD_PARAM"   =&gt; ""     // параметры поля
	*                 );
	*             
	*             // получим текущее значение
	*             $value = <b>CForm::GetDateValue</b>($arAnswer["ID"], $arAnswer, $arrVALUES);
	* 
	*             // выведем поле
	*             echo CForm::GetDateField(
	*                 $arAnswer["ID"],
	*                 "ANKETA",
	*                 $value,
	*                 $arAnswer["FIELD_WIDTH"],
	*                 $arAnswer["FIELD_PARAM"]
	*                 );
	*             ?&gt;&lt;/td&gt;
	*     &lt;/tr&gt;
	* &lt;/table&gt;
	* &lt;input type="submit" name="save" value="Сохранить"&gt;
	* &lt;/form&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getdatefield.php">CForm::GetDateField</a>
	* </li></ul><a name="examples"></a><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/getdatevalue.php
	* @author Bitrix
	*/
	public static 	function GetDateValue($FIELD_NAME, $arAnswer, $arrVALUES=false)
	{
		$fname = "form_date_".$FIELD_NAME;
		if (is_array($arrVALUES) && isset($arrVALUES[$fname])) $value = $arrVALUES[$fname];
		else
		{
			if (preg_match("/NOW_DATE/i",$arAnswer["FIELD_PARAM"])) $value = GetTime(time(),"SHORT");
			elseif (preg_match("/NOW_TIME/i",$arAnswer["FIELD_PARAM"])) $value = GetTime(time()+CTimeZone::GetOffset(),"FULL");
			else $value = $arAnswer["VALUE"];
		}
		return $value;
	}


	/**
	* <p>Возвращает HTML код однострочного текстового поля. Данное поле предназначено для ввода <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> типа "date". В результирующий HTML код будет добавлена иконка, ведущая на страницу с календарем.</p> <p>Метод может использоваться как в форме создания нового <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>, так и в форме редактирования существующего.</p> <p class="note"><b>Примечание</b><br>Имя результирующего HTML поля для ввода даты будет сформировано по следующей маске:<br><b>form_date_</b><i>answer_id</i></p>
	*
	*
	* @param int $answer_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>.</bo
	*
	* @param string $html_form_name  Имя HTML формы для создания нового <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a> или
	* редактирования существующего.<br><code> &lt;form name="<i>html_form_name</i>"
	* ...&gt;<br></code> <br>Параметр необязательный. По умолчанию - "form1".
	*
	* @param string $value = "" Значение результирующего текстового поля:<br><code> &lt;input type="text"
	* value="<i>value</i>" ...&gt;<br><br></code>Параметр необязательный. По умолчанию - "".
	*
	* @param mixed $size = "" Ширина результирующего текстового поля для ввода даты:<br><code>
	* &lt;input type="text" size="<i>size</i>" ...&gt;<br><br></code>Параметр необязательный. По
	* умолчанию - "".
	*
	* @param string $add_to_text = "class=\"inputtext\"" Произвольный HTML который будет добавлен в результирующий HTML тег
	* текстового поля для ввода даты:<br><code> &lt;input type="text" <i>add_to_text</i>
	* ...&gt;<br><br></code>Параметр необязательный. По умолчанию - "class=\"inputtext\"".
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* //<******************************************
	*        Редактирование результата
	* ******************************************>//
	* 
	* $RESULT_ID = 12; // ID результата
	* 
	* // если была нажата кнопка "Сохранить" то
	* if (strlen($_REQUEST["save"])&gt;0)
	* {
	*     // используем данные пришедшие с формы
	*     $arrVALUES = $_REQUEST; 
	* }
	* else
	* {
	*     // сформируем этот массив из данных по результату
	*     $arrVALUES = CFormResult::GetDataByIDForHTML($RESULT_ID); 
	* }
	* ?&gt;
	* &lt;form name="ANKETA" action="" method="POST"&gt;
	* &lt;table&gt;
	*     &lt;tr&gt;
	*         &lt;td&gt;Дата рождения:&lt;/td&gt;
	*         &lt;td&gt;&lt;?
	* 
	*             // массив описывающий поле для ввода даты
	*             // содержит минимально-необходимые поля
	*             $arAnswer = array(
	*                 "ID"            =&gt; 587,   // ID поля для ответа на вопрос "Дата рождения?"
	*                 "VALUE"         =&gt; "",    // параметр ANSWER_VALUE (значение по умолчанию)
	*                 "FIELD_WIDTH"   =&gt; 10,    // ширина поля
	*                 "FIELD_PARAM"   =&gt; ""     // параметры поля
	*                 );
	*             
	*             // получим текущее значение
	*             $value = CForm::GetDateValue($arAnswer["ID"], $arAnswer, $arrVALUES);
	* 
	*             // выведем поле
	*             echo <b>CForm::GetDateField</b>(
	*                 $arAnswer["ID"],
	*                 "ANKETA",
	*                 $value,
	*                 $arAnswer["FIELD_WIDTH"],
	*                 $arAnswer["FIELD_PARAM"]
	*                 );
	*             ?&gt;&lt;/td&gt;
	*     &lt;/tr&gt;
	* &lt;/table&gt;
	* &lt;input type="submit" name="save" value="Сохранить"&gt;
	* &lt;/form&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getdatevalue.php">CForm::GetDateValue</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/form/htmlnames.php">Имена HTML полей</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/getdatefield.php
	* @author Bitrix
	*/
	public static 	function GetDateField($FIELD_NAME, $FORM_NAME, $VALUE="", $FIELD_WIDTH="", $PARAM="")
	{
		global $APPLICATION;
		//if (strlen($PARAM)<=0) $PARAM = " class=\"inputtext\" ";

		$rid = RandString(8);
		$res = "<input type=\"text\" ".$PARAM." name=\"form_date_".$FIELD_NAME."\" id=\"form_date_".$rid."\" value=\"".htmlspecialcharsbx($VALUE)."\" size=\"".$FIELD_WIDTH."\" />";

		ob_start();
		$APPLICATION->IncludeComponent(
			'bitrix:main.calendar',
			'',
			array(
				'SHOW_INPUT' => 'N',
				'FORM_NAME' => $FORM_NAME,
				'INPUT_NAME' => "form_date_".$rid,
				'SHOW_TIME' => 'N',
			),
			null,
			array('HIDE_ICONS' => 'Y')
		);
		$res .= ob_get_contents();
		ob_end_clean();

		return $res;

		//return CalendarDate("form_date_".$FIELD_NAME, $VALUE, $FORM_NAME, $FIELD_WIDTH, $PARAM);
	}


	/**
	* <p>Если массив, переданный в параметре <i>form_values</i>, инициализирован (например, в момент редактирования <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>), то метод возвращает ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> (<nobr><i>answer</i>["ID"]</nobr>), в случае если он был выбран среди группы ответов типа "checkbox" на вопрос, символьный идентификатор которого указан в параметре <i>question_sid</i>.</p> <p>Если массив, переданный в параметре <i>form_values</i>, не инициализирован (например, в момент создания нового <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>), то метод вернет ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> (<nobr><i>answer</i>["ID"]</nobr>), если он был установлен как ответ по умолчанию (ответом по умолчанию считаются те, у которых присутствует строка "checked" в <nobr><i>answer</i>["FIELD_PARAM"]</nobr>).</p>
	*
	*
	* @param string $question_sid  Символьный идентификатор <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>.
	*
	* @param array $answer  Массив, описывающий параметры <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>, с обязательными
	* ключами: <ul> <li> <b>ID</b> - ID <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>; </li> <li> <b>FIELD_PARAM</b> - если
	* значение этого ключа содержит слово "checked", то ID этого ответа будет
	* возвращен данным методом по умолчанию (т.е. при создании нового <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>). </li> </ul>
	*
	* @param mixed $form_values = false Ассоциированный массив значений, пришедших с веб-формы при
	* создании нового или редактировании существующего <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a> (стандартный
	* массив <b>$_REQUEST</b>). Данный массив может быть также получен с
	* помощью метода <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/getdatabyidforhtml.php">CFormResult::GetDataByIDForHTML</a>.<br><br>Параметр
	* необязательный. По умолчанию - "false".
	*
	* @return int 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* //<******************************************
	*        Редактирование результата
	* ******************************************>//
	* 
	* $RESULT_ID = 12; // ID результата
	* 
	* // если была нажата кнопка "Сохранить", то
	* if (strlen($_REQUEST["save"])&gt;0)
	* {
	*     // используем данные пришедшие с формы
	*     $arrVALUES = $_REQUEST; 
	* }
	* else
	* {
	*     // сформируем этот массив из данных по результату
	*     $arrVALUES = CFormResult::GetDataByIDForHTML($RESULT_ID); 
	* }
	* ?&gt;
	* &lt;form action="" method="POST"&gt;
	* &lt;table&gt;
	*     &lt;tr&gt;
	*         &lt;td&gt;Какие области знаний вас интересуют ?&lt;/td&gt;
	*         &lt;td&gt;&lt;?
	*             
	*             //<*********************************************************
	*                 выводим два checkbox'а (математика/физика) 
	*                 как варианты ответа на вопрос 
	*                 "Какие области знаний вас интересуют ?"
	*             *********************************************************>//
	* 
	*             $QUESTION_SID = "INTEREST"; // символьный идентификатор вопроса
	* 
	*             //<**********************
	*               checkbox "математика"
	*             **********************>//
	* 
	*             // массив описывающий один checkbox
	*             // содержит минимально-необходимые поля
	*             $arAnswer = array(
	*                 "ID"            =&gt; 591,            // ID checkbox'а
	*                 "FIELD_PARAM"   =&gt; "checked class=\"inputcheckbox\""   // параметр ответа
	*                 );
	* 
	*             // получим текущее значение
	*             $value = <b>CForm::GetCheckBoxValue</b>($QUESTION_SID, $arAnswer, $arrVALUES);
	* 
	*             // выведем checkbox
	*             echo CForm::GetCheckBoxField(
	*                 $QUESTION_SID,
	*                 $arAnswer["ID"],
	*                 $value,
	*                 $arAnswer["FIELD_PARAM"]
	*                 );            
	*             echo "математика&lt;br&gt;";
	* 
	*             //<**********************
	*                 checkbox "физика"
	*             **********************>//
	* 
	*             // массив описывающий один checkbox
	*             // содержит минимально-необходимые поля
	*             $arAnswer = array(
	*                 "ID"            =&gt; 593,       // ID checkbox'а
	*                 "FIELD_PARAM"   =&gt; ""         // параметр ответа
	*                 );
	* 
	*             // получим текущее значение
	*             $value = <b>CForm::GetCheckBoxValue</b>($QUESTION_SID, $arAnswer, $arrVALUES);
	* 
	*             // выведем checkbox
	*             echo CForm::GetCheckBoxField(
	*                 $QUESTION_SID,
	*                 $arAnswer["ID"],
	*                 $value,
	*                 $arAnswer["FIELD_PARAM"]
	*                 );            
	*             echo "физика";
	*             ?&gt;&lt;/td&gt;
	*     &lt;/tr&gt;
	* &lt;/table&gt;
	* &lt;input type="submit" name="save" value="Сохранить"&gt;
	* &lt;/form&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getcheckboxfield.php">CForm::GetCheckBoxField</a>
	* </li></ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/getcheckboxvalue.php
	* @author Bitrix
	*/
	public static 	function GetCheckBoxValue($FIELD_NAME, $arAnswer, $arrVALUES=false)
	{
		$fname = "form_checkbox_".$FIELD_NAME;

		if (is_array($arrVALUES))
		{
			if(isset($arrVALUES[$fname]))
			{
				$arr = $arrVALUES[$fname];
				if (is_array($arr) && in_array($arAnswer["ID"],$arr))
				{
					$value = $arAnswer["ID"];
				}
			}
		}
		else
		{
			if ($value<=0)
			{
				if (strpos(strtolower($arAnswer["FIELD_PARAM"]), "selected")!==false || strpos(strtolower($arAnswer["FIELD_PARAM"]), "checked")!==false)
				{
					$value = $arAnswer["ID"];
				}
			}
		}

		return $value;
	}


	/**
	* <p>Возвращает HTML код флага множественного выбора (checkbox), предназначенного для выбора <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> типа "checkbox" на вопрос, символьный идентификатор которого передается в параметре <i>question_sid</i>.</p> <p>Метод может использоваться как в форме создания нового <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>, так и в форме редактирования существующего.</p> <p class="note"><b>Примечание</b><br>Имя результирующего HTML поля будет сформировано по следующей маске:<br><b>form_checkbox_</b><i>question_sid</i><b>[]</b></p>
	*
	*
	* @param string $question_sid  Символьный идентификатор <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>.
	*
	* @param int $answer_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>.</bo
	*
	* @param mixed $value = "" Если в данном параметре будет передано значение, совпадающее с
	* <i>answer_id</i>, то флаг множественного выбора будет отмечен
	* (<i>checked</i>):<br><code> &lt;input type="checkbox" checked ...&gt;</code> <br><br>Параметр
	* необязательный. По умолчанию - "".
	*
	* @param string $add_to_checkbox = "class=\"inputcheckbox\"" Произвольный HTML, который будет добавлен в результирующий HTML тег
	* флага множественного выбора:<br> &lt;input type="checkbox" <i>add_to_checkbox</i>
	* ...&gt;<br><br> Необходимо учитывать, что если в данном параметре задать
	* ключевое слово "checked", то данный переключатель будет выбран по
	* умолчанию. <br><br>Параметр необязательный. По умолчанию -
	* "class=\"inputcheckbox\"".
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* //<******************************************
	*        Редактирование результата
	* ******************************************>//
	* 
	* $RESULT_ID = 12; // ID результата
	* 
	* // если была нажата кнопка "Сохранить" то
	* if (strlen($_REQUEST["save"])&gt;0)
	* {
	*     // используем данные, пришедшие с формы
	*     $arrVALUES = $_REQUEST; 
	* }
	* else
	* {
	*     // сформируем этот массив из данных по результату
	*     $arrVALUES = CFormResult::GetDataByIDForHTML($RESULT_ID); 
	* }
	* ?&gt;
	* &lt;form action="" method="POST"&gt;
	* &lt;table&gt;
	*     &lt;tr&gt;
	*         &lt;td&gt;Какие области знаний вас интересуют ?&lt;/td&gt;
	*         &lt;td&gt;&lt;?
	*             
	*             //<*********************************************************
	*                 выводим два checkbox'а (математика/физика) 
	*                 как варианты ответа на вопрос 
	*                 "Какие области знаний вас интересуют ?"
	*             *********************************************************>//
	* 
	*             $QUESTION_SID = "INTEREST"; // символьный идентификатор вопроса
	* 
	*             //<**********************
	*               checkbox "математика"
	*             **********************>//
	* 
	*             // массив описывающий один checkbox
	*             // содержит минимально-необходимые поля
	*             $arAnswer = array(
	*                 "ID"            =&gt; 591,            // ID checkbox'а
	*                 "FIELD_PARAM"   =&gt; "checked class=\"inputcheckbox\""   // параметр ответа
	*                 );
	* 
	*             // получим текущее значение
	*             $value = CForm::GetCheckBoxValue($QUESTION_SID, $arAnswer, $arrVALUES);
	* 
	*             // выведем checkbox
	*             echo <b>CForm::GetCheckBoxField</b>(
	*                 $QUESTION_SID,
	*                 $arAnswer["ID"],
	*                 $value,
	*                 $arAnswer["FIELD_PARAM"]
	*                 );            
	*             echo "математика&lt;br&gt;";
	* 
	*             //<**********************
	*                 checkbox "физика"
	*             **********************>//
	* 
	*             // массив описывающий один checkbox
	*             // содержит минимально-необходимые поля
	*             $arAnswer = array(
	*                 "ID"            =&gt; 593,       // ID checkbox'а
	*                 "FIELD_PARAM"   =&gt; ""         // параметр ответа
	*                 );
	* 
	*             // получим текущее значение
	*             $value = CForm::GetCheckBoxValue($QUESTION_SID, $arAnswer, $arrVALUES);
	* 
	*             // выведем checkbox
	*             echo <b>CForm::GetCheckBoxField</b>(
	*                 $QUESTION_SID,
	*                 $arAnswer["ID"],
	*                 $value,
	*                 $arAnswer["FIELD_PARAM"]
	*                 );            
	*             echo "физика";
	*             ?&gt;&lt;/td&gt;
	*     &lt;/tr&gt;
	* &lt;/table&gt;
	* &lt;input type="submit" name="save" value="Сохранить"&gt;
	* &lt;/form&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getcheckboxvalue.php">CForm::GetCheckBoxValue</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/htmlnames.php">Имена HTML полей</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/getcheckboxfield.php
	* @author Bitrix
	*/
	public static 	function GetCheckBoxField($FIELD_NAME, $FIELD_ID, $VALUE="", $PARAM="")
	{
		if (strlen($PARAM)<=0) $PARAM = " class=\"inputcheckbox\" ";
		return InputType("checkbox", "form_checkbox_".$FIELD_NAME."[]", $FIELD_ID, $VALUE, false, "", $PARAM);
	}


	/**
	* <p>Если массив, переданный в параметре <i>form_values,</i> инициализирован (например, в момент редактирования <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>), то метод возвращает ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>, выбранного среди группы ответов типа "radio" на вопрос, символьный идентификатор которого указан в параметре <i>question_sid</i>.</p> <p>Если массив, переданный в параметре <i>form_values,</i> не инициализирован (например, в момент создания нового <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>), то метод вернет ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> (<nobr><i>answer</i>["ID"]</nobr>), если он был установлен как ответ по умолчанию (ответами по умолчанию считаются те, у которых присутствует строка "checked" в <nobr><i>answer</i>["FIELD_PARAM"]</nobr>).</p>
	*
	*
	* @param string $question_sid  Символьный идентификатор <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>.
	*
	* @param array $answer  Массив описывающий параметры <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>, с обязательными
	* ключами: <ul> <li> <b>ID</b> - ID <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>; </li> <li> <b>FIELD_PARAM</b> - если
	* значение этого ключа содержит слово "checked", то ID этого ответа будет
	* возвращен данным методом по умолчанию (т.е. при создании нового <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>). </li> </ul>
	*
	* @param mixed $form_values = false Ассоциированный массив значений, пришедших с веб-формы при
	* создании нового или редактировании существующего <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a> (стандартный
	* массив <b>$_REQUEST</b>). Данный массив может быть также получен с
	* помощью метода <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/getdatabyidforhtml.php">CFormResult::GetDataByIDForHTML</a>.<br><br>Параметр
	* необязательный. По умолчанию - "false".
	*
	* @return int 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* //<******************************************
	*        Редактирование результата
	* ******************************************>//
	* 
	* $RESULT_ID = 12; // ID результата
	* 
	* // если была нажата кнопка "Сохранить" то
	* if (strlen($_REQUEST["save"])&gt;0)
	* {
	*     // используем данные пришедшие с формы
	*     $arrVALUES = $_REQUEST; 
	* }
	* else
	* {
	*     // сформируем этот массив из данных по результату
	*     $arrVALUES = CFormResult::GetDataByIDForHTML($RESULT_ID); 
	* }
	* ?&gt;
	* &lt;form action="" method="POST"&gt;
	* &lt;table&gt;
	*     &lt;tr&gt;
	*         &lt;td&gt;Вы курите?&lt;/td&gt;
	*         &lt;td&gt;&lt;?
	*             
	*             //<*********************************************************
	*                 выводим две radio-кнопки (да/нет) 
	*                 как варианты ответа на вопрос "Вы курите?"
	*             *********************************************************>//
	* 
	*             $QUESTION_SID = "SMOKE"; // символьный идентификатор вопроса
	* 
	*             //<**********************
	*                 radio-кнопка "да"
	*             **********************>//
	* 
	*             // массив описывающий одну radio-кнопку
	*             $arAnswer = array(
	*                 "ID"            =&gt; 589,    // ID radio-кнопки
	*                 "FIELD_PARAM"   =&gt; "checked class=\"inputradio\""   // параметр ответа
	*                 );
	*             
	*             // получим текущее значение
	*             $value = <b>CForm::GetRadioValue</b>($QUESTION_SID, $arAnswer, $arrVALUES);
	* 
	*             // выведем radio-кнопку
	*             echo CForm::GetRadioField(
	*                 $QUESTION_SID,
	*                 $arAnswer["ID"],
	*                 $value,
	*                 $arAnswer["FIELD_PARAM"]
	*                 );            
	*             echo "да &lt;br&gt;";
	* 
	*             //<**********************
	*                 radio-кнопка "нет"
	*             **********************>//
	* 
	*             // массив описывающий одну radio-кнопку
	*             $arAnswer = array(
	*                 "ID"            =&gt; 590,    // ID radio-кнопки
	*                 "FIELD_PARAM"   =&gt; ""      // параметр ответа
	*                 );
	*             
	*             // получим текущее значение
	*             $value = <b>CForm::GetRadioValue</b>($QUESTION_SID, $arAnswer, $arrVALUES);
	* 
	*             // выведем radio-кнопку
	*             echo CForm::GetRadioField(
	*                 $QUESTION_SID,
	*                 $arAnswer["ID"],
	*                 $value,
	*                 $arAnswer["FIELD_PARAM"]
	*                 );            
	*             echo "нет";
	*             ?&gt;&lt;/td&gt;
	*     &lt;/tr&gt;
	* &lt;/table&gt;
	* &lt;input type="submit" name="save" value="Сохранить"&gt;
	* &lt;/form&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getradiofield.php">CForm::GetRadioField</a>
	* </li></ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/getradiovalue.php
	* @author Bitrix
	*/
	public static 	function GetRadioValue($FIELD_NAME, $arAnswer, $arrVALUES=false)
	{
		$fname = "form_radio_".$FIELD_NAME;
		if (is_array($arrVALUES) && isset($arrVALUES[$fname]))
		{
			$value = intval($arrVALUES[$fname]);
		}
		else
		{
			if (strpos(strtolower($arAnswer["FIELD_PARAM"]), "selected")!==false || strpos(strtolower($arAnswer["FIELD_PARAM"]), "checked")!==false)
				$value = $arAnswer["ID"];
		}
		return $value;
	}


	/**
	* <p>Возвращает HTML код переключателя одиночного выбора (radio-кнопка), предназначенного для выбора <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> типа "radio" на вопрос, символьный идентификатор которого передается в параметре <i>question_sid</i>.</p> <p>Метод может использоваться как в форме содания нового <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>, так и в форме редактирования существующего.</p> <p class="note">Имя результирующего HTML поля будет сформировано по следующей маске: <b>form_radio_</b><i>question_sid</i></p>
	*
	*
	* @param string $question_sid  Символьный идентификатор <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a>.
	*
	* @param int $answer_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>.</bo
	*
	* @param mixed $value = "" Если в данном параметре будет передано значение совпадающее с
	* <i>answer_id</i>, то переключатель одиночного выбора будет выбран
	* (checked):<br> &lt;input type="radio" checked ...&gt; <br>Параметр необязательный. По
	* умолчанию - "".
	*
	* @param string $add_to_radio = "class=\"inputradio\"" Произвольный HTML который будет добавлен в результирующий HTML тег
	* переключателя одиночного выбора:<br> &lt;input type="radio" <i>add_to_radio</i> ...&gt;<br>
	* Необходимо учитывать что если в данном параметре задать ключевое
	* слово "checked", то данный переключатель будет выбран по умолчанию.
	* <br>Параметр необязательный. По умолчанию - "class=\"inputradio\"".
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* //<******************************************
	*        Редактирование результата
	* ******************************************>//
	* 
	* $RESULT_ID = 12; // ID результата
	* 
	* // если была нажата кнопка "Сохранить" то
	* if (strlen($_REQUEST["save"])&gt;0)
	* {
	*     // используем данные пришедшие с формы
	*     $arrVALUES = $_REQUEST; 
	* }
	* else
	* {
	*     // сформируем этот массив из данных по результату
	*     $arrVALUES = CFormResult::GetDataByIDForHTML($RESULT_ID); 
	* }
	* ?&gt;
	* &lt;form action="" method="POST"&gt;
	* &lt;table&gt;
	*     &lt;tr&gt;
	*         &lt;td&gt;Вы курите?&lt;/td&gt;
	*         &lt;td&gt;&lt;?
	*             
	*             //<*********************************************************
	*                 выводим две radio-кнопки (да/нет) 
	*                 как варианты ответа на вопрос "Вы курите?"
	*             *********************************************************>//
	* 
	*             $QUESTION_SID = "SMOKE"; // символьный идентификатор вопроса
	* 
	*             //<**********************
	*                 radio-кнопка "да"
	*             **********************>//
	* 
	*             // массив описывающий одну radio-кнопку
	*             // содержит минимально-необходимые поля
	*             $arAnswer = array(
	*                 "ID"            =&gt; 589,    // ID radio-кнопки
	*                 "FIELD_PARAM"   =&gt; "checked class=\"inputradio\""   // параметр ответа
	*                 );
	*             
	*             // получим текущее значение
	*             $value = CForm::GetRadioValue($QUESTION_SID, $arAnswer, $arrVALUES);
	* 
	*             // выведем radio-кнопку
	*             echo <b>CForm::GetRadioField</b>(
	*                 $QUESTION_SID,
	*                 $arAnswer["ID"],
	*                 $value,
	*                 $arAnswer["FIELD_PARAM"]
	*                 );            
	*             echo "да &lt;br&gt;";
	* 
	*             //<**********************
	*                 radio-кнопка "нет"
	*             **********************>//
	* 
	*             // массив описывающий одну radio-кнопку
	*             // содержит минимально-необходимые поля
	*             $arAnswer = array(
	*                 "ID"            =&gt; 590,    // ID radio-кнопки
	*                 "FIELD_PARAM"   =&gt; ""      // параметр ответа
	*                 );
	*             
	*             // получим текущее значение
	*             $value = CForm::GetRadioValue($QUESTION_SID, $arAnswer, $arrVALUES);
	* 
	*             // выведем radio-кнопку
	*             echo <b>CForm::GetRadioField</b>(
	*                 $QUESTION_SID,
	*                 $arAnswer["ID"],
	*                 $value,
	*                 $arAnswer["FIELD_PARAM"]
	*                 );            
	*             echo "нет";
	*             ?&gt;&lt;/td&gt;
	*     &lt;/tr&gt;
	* &lt;/table&gt;
	* &lt;input type="submit" name="save" value="Сохранить"&gt;
	* &lt;/form&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getradiovalue.php">CForm::GetRadioValue</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/form/htmlnames.php">Имена HTML полей</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/getradiofield.php
	* @author Bitrix
	*/
	public static 	function GetRadioField($FIELD_NAME, $FIELD_ID, $VALUE="", $PARAM="")
	{
		if (strlen($PARAM)<=0) $PARAM = " class=\"inputradio\" ";

		return InputType("radio", "form_radio_".$FIELD_NAME, $FIELD_ID, $VALUE, false, "", $PARAM);
	}

	
	/**
	* <p>Если массив, переданный в параметре <i>form_values,</i> инициализирован (например, в момент редактирования <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>), то метод возвращает текущее значение <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> типа "textarea", ID которого передается в параметре <i>answer_id</i>.</p> <p>Если массив, переданный в параметре <i>form_values,</i> не инициализирован (например, в момент создания нового <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>), то метод вернет значение по умолчанию для данного <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> (т.е. то что задается в <nobr><i>answer</i>["VALUE"]</nobr>).</p>
	*
	*
	* @param int $answer_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>.</bo
	*
	* @param array $answer  Массив, описывающий параметры <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>, обязательным
	* элементом которого является элемент с ключом <b>VALUE</b> и значением,
	* в котором содержится значение по умолчанию для <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>. Как правило, таким
	* значением по умолчанию становится параметр <font color="red">ANSWER_VALUE</font>
	* <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>.
	*
	* @param mixed $form_values = false Ассоциированный массив значений, пришедших с веб-формы при
	* создании нового или редактировании существующего <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a> (стандартный
	* массив <b>$_REQUEST</b>). Данный массив может быть также получен с
	* помощью метода <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/getdatabyidforhtml.php">CFormResult::GetDataByIDForHTML</a>.<br><br>Параметр
	* необязательный. По умолчанию - "false".
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* //<******************************************
	*        Редактирование результата
	* ******************************************>//
	* 
	* $RESULT_ID = 12; // ID результата
	* 
	* // если была нажата кнопка "Сохранить" то
	* if (strlen($_REQUEST["save"])&gt;0)
	* {
	*     // используем данные пришедшие с формы
	*     $arrVALUES = $_REQUEST; 
	* }
	* else
	* {
	*     // сформируем этот массив из данных по результату
	*     $arrVALUES = CFormResult::GetDataByIDForHTML($RESULT_ID); 
	* }
	* ?&gt;
	* &lt;form action="" method="POST"&gt;
	* &lt;table&gt;
	*     &lt;tr&gt;
	*         &lt;td&gt;Адрес:&lt;/td&gt;
	*         &lt;td&gt;&lt;?
	* 
	*             // массив описыващий многострочное текстовое поле
	*             // содержит минимально-необходимые поля
	*             $arAnswer = array(
	*                 "ID"            =&gt; 588,   // ID поля для ответа на вопрос "Ваш адрес?"
	*                 "VALUE"         =&gt; "",    // параметр ANSWER_VALUE (значение по умолчанию)
	*                 "FIELD_WIDTH"   =&gt; 10,    // ширина поля
	*                 "FIELD_HEIGHT"  =&gt; 5,     // высота поля
	*                 "FIELD_PARAM"   =&gt; ""     // параметры поля
	*                 );
	* 
	*             // получим текущее значение
	*             $value = <b>CForm::GetTextAreaValue</b>($arAnswer["ID"], $arAnswer, $arrVALUES);
	* 
	*             // выведем поле
	*             echo CForm::GetTextAreaField(
	*                 $arAnswer["ID"], 
	*                 $arAnswer["FIELD_WIDTH"], 
	*                 $arAnswer["FIELD_HEIGHT"], 
	*                 $arAnswer["FIELD_PARAM"],
	*                 $value
	*                 );
	*             ?&gt;&lt;/td&gt;
	*     &lt;/tr&gt;
	* &lt;/table&gt;
	* &lt;input type="submit" name="save" value="Сохранить"&gt;
	* &lt;/form&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/gettextareafield.php">CForm::GetTextAreaField</a>
	* </li></ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/gettextareavalue.php
	* @author Bitrix
	*/
	public static function GetTextAreaValue($FIELD_NAME, $arAnswer, $arrVALUES=false)
	{
		$fname = "form_textarea_".$FIELD_NAME;
		if (is_array($arrVALUES) && isset($arrVALUES[$fname])) $value = $arrVALUES[$fname];
		else $value = $arAnswer["VALUE"];
		return $value;
	}


	/**
	* <p>Возвращает HTML код многострочного текстового поля. Данное поле предназначено для ввода <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> типа "textarea".</p> <p>Метод может использоваться как в форме создания нового <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>, так и в форме редактирования существующего.</p> <p class="note"><b>Примечание</b><br>Имя результирующего HTML поля будет сформировано по следующей маске:<br><b>form_textarea_</b><i>answer_id</i></p>
	*
	*
	* @param int $answer_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>.</bo
	*
	* @param int $cols = "" Ширина результирующего многострочного текстового поля:<br><code>
	* &lt;textarea cols="<i>cols</i>" ...&gt;</code><br><br>Параметр необязательный. По
	* умолчанию - "".
	*
	* @param int $rows = "" Высота результирующего многострочного текстового поля:<br><code>
	* &lt;textarea rows="<i>rows</i>" ...&gt;</code><br><br>Параметр необязательный. По
	* умолчанию - "".
	*
	* @param string $add_to_textarea = "class=\"inputtextarea\"" Произвольный HTML, который будет добавлен в результирующий тег
	* многострочного текстового поля:<br><code> &lt;textarea <i>add_to_textarea</i>
	* ...&gt;</code><br><br>Параметр необязательный. По умолчанию -
	* "class=\"inputtextarea\"".
	*
	* @param string $value = "" Значение результирующего многострочного текстового поля:<br><code>
	* &lt;textarea ...&gt;<i>value</i>&lt;/textarea&gt;</code><br><br>Параметр необязательный. По
	* умолчанию - "".
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* //<******************************************
	*        Редактирование результата
	* ******************************************>//
	* 
	* $RESULT_ID = 12; // ID результата
	* 
	* // если была нажата кнопка "Сохранить" то
	* if (strlen($_REQUEST["save"])&gt;0)
	* {
	*     // используем данные пришедшие с формы
	*     $arrVALUES = $_REQUEST; 
	* }
	* else
	* {
	*     // сформируем этот массив из данных по результату
	*     $arrVALUES = CFormResult::GetDataByIDForHTML($RESULT_ID); 
	* }
	* ?&gt;
	* &lt;form action="" method="POST"&gt;
	* &lt;table&gt;
	*     &lt;tr&gt;
	*         &lt;td&gt;Адрес:&lt;/td&gt;
	*         &lt;td&gt;&lt;?
	* 
	*             // массив описыващий многострочное текстовое поле
	*             // содержит минимально-необходимые поля
	*             $arAnswer = array(
	*                 "ID"            =&gt; 588,   // ID поля для ответа на вопрос "Ваш адрес?"
	*                 "VALUE"         =&gt; "",    // параметр ANSWER_VALUE (значение по умолчанию)
	*                 "FIELD_WIDTH"   =&gt; 10,    // ширина поля
	*                 "FIELD_HEIGHT"  =&gt; 5,     // высота поля
	*                 "FIELD_PARAM"   =&gt; ""     // параметры поля
	*                 );
	* 
	*             // получим текущее значение
	*             $value = CForm::GetTextAreaValue($arAnswer["ID"], $arAnswer, $arrVALUES);
	* 
	*             // выведем поле
	*             echo <b>CForm::GetTextAreaField</b>(
	*                 $arAnswer["ID"], 
	*                 $arAnswer["FIELD_WIDTH"], 
	*                 $arAnswer["FIELD_HEIGHT"], 
	*                 $arAnswer["FIELD_PARAM"],
	*                 $value
	*                 );
	*             ?&gt;&lt;/td&gt;
	*     &lt;/tr&gt;
	* &lt;/table&gt;
	* &lt;input type="submit" name="save" value="Сохранить"&gt;
	* &lt;/form&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/gettextareavalue.php">CForm::GetTextAreaValue</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/htmlnames.php">Имена HTML полей</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/gettextareafield.php
	* @author Bitrix
	*/
	public static 	function GetTextAreaField($FIELD_NAME, $WIDTH="", $HEIGHT="", $PARAM="", $VALUE="")
	{
		if (strlen($PARAM)<=0) $PARAM = " class=\"inputtextarea\" ";
		return "<textarea name=\"form_textarea_".$FIELD_NAME."\" cols=\"".$WIDTH."\" rows=\"".$HEIGHT."\" ".$PARAM.">".htmlspecialcharsbx($VALUE)."</textarea>";
	}


	/**
	* <p>Возвращает HTML код поля для загрузки файла. Данное поле предназначено для ввода <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> типа "<b>image</b>" или "<b>file</b>".</p> <p>Метод может использоваться как в форме создания нового <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>, так и в форме редактирования существующего.</p> <p class="note"><b>Примечание</b><br>Имя результирующего HTML поля будет сформировано по следующей маске:<br><b>form_</b><i>file_type</i><b>_</b><i>answer_id</i></p>
	*
	*
	* @param int $answer_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>.</bo
	*
	* @param mixed $width = "" Ширина результирующего поля для ввода файла:<br><code> &lt;input type="file"
	* size="<i>width</i>" ...&gt;</code><br><br>Параметр необязательный. По умолчанию - "".
	*
	* @param string $file_type = "IMAGE" Тип файла, допустимы следующие значения: <ul> <li> <b>IMAGE</b> -
	* изображение; </li> <li> <b>FILE</b> - произвольный файл. </li> </ul> Параметр
	* необязательный. По умолчанию - "IMAGE" (изображение).
	*
	* @param int $max_file_size = 0 Максимальный размер загружаемого файла (в байтах).<br><br>Параметр
	* необязательный. По умолчанию - 0 (без ограничений).
	*
	* @param mixed $file_id = "" ID загруженного (редактируемого) файла.<br><br>Параметр
	* необязательный. По умолчанию - "".
	*
	* @param string $add_to_file = "class=\"inputfile\"" Произвольный HTML, который будет добавлен в HTML тег поля для
	* загрузки файла:<br><code> &lt;input type="file" <i>add_to_file</i> ...&gt;</code><br><br>Параметр
	* необязательный. По умолчанию - "class=\"inputfile\"".
	*
	* @param string $add_to_checkbox = "class=\"inputcheckbox\"" Произвольный HTML, который будет добавлен в HTML тег флага удаления
	* редактируемого файла:<br><code> &lt;input type="checkbox" <i>add_to_checkbox</i>
	* ...&gt;</code><br><br>Параметр необязательный. По умолчанию -
	* "class=\"inputcheckbox\"".
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* //<******************************************
	*        Редактирование результата
	* ******************************************>//
	* 
	* $RESULT_ID = 12; // ID результата
	* ?&gt;
	* &lt;form action="" method="POST"&gt;
	* &lt;table&gt;
	*     &lt;tr&gt;
	*         &lt;td&gt;Фотография:&lt;/td&gt;
	*         &lt;td&gt;&lt;?
	* 
	*             //<***********************************************************
	*                                     Изображение
	*             ***********************************************************>//
	* 
	*             // массив описывающий поле ответа
	*             // содержит минимально-необходимые поля
	*             $arAnswer = array(
	*                 "ID"            =&gt; 607,   // ID поля для ответа на вопрос "Фотография"
	*                 "FIELD_WIDTH"   =&gt; 10,    // ширина поля
	*                 "FIELD_PARAM"   =&gt; ""     // параметры поля
	*                 );
	* 
	*             // попробуем получить параметры загруженного файла
	*             if ($arFile = CFormResult::GetFileByAnswerID($RESULT_ID, $arAnswer["ID"])):
	*                 // если файл был получен то
	*                 if (intval($arFile["USER_FILE_ID"])&gt;0):
	*                     // если это изображение то
	*                     if ($arFile["USER_FILE_IS_IMAGE"]=="Y") :
	*                         // выведем изображение
	*                         echo CFile::ShowImage(
	*                             $arFile["USER_FILE_ID"], 
	*                             0, 
	*                             0, 
	*                             "border=0", 
	*                             "", 
	*                             true);
	*                     endif;
	*                     echo "&lt;br&gt;&lt;br&gt;"; 
	*                 endif;
	*             endif;
	* 
	*             // выведем поле для ввода файла
	*             echo <b>CForm::GetFileField</b>(
	*                 $arAnswer["ID"],
	*                 $arAnswer["FIELD_WIDTH"],
	*                 "IMAGE",
	*                 0,  // максимальный размер файла не ограничен
	*                 $arFile["USER_FILE_ID"],
	*                 $arAnswer["FIELD_PARAM"]);
	* 
	*             ?&gt;&lt;/td&gt;
	*     &lt;/tr&gt;
	*     &lt;tr&gt;
	*         &lt;td&gt;Резюме:&lt;/td&gt;
	*         &lt;td&gt;&lt;?
	* 
	*             //<***********************************************************
	*                                 Произвольный файл
	*             ***********************************************************>//
	* 
	*             // массив описывающий поле ответа
	*             // содержит минимально-необходимые поля
	*             $arAnswer = array(
	*                 "ID"            =&gt; 610,   // ID поля для ответа на вопрос "Резюме"
	*                 "FIELD_WIDTH"   =&gt; 10,    // ширина поля
	*                 "FIELD_PARAM"   =&gt; ""     // параметры поля
	*                 );
	* 
	*             // попробуем получить параметры загруженного файла
	*             if ($arFile = CFormResult::GetFileByAnswerID($RESULT_ID, $arAnswer["ID"])):
	*                 // если файл был получен то
	*                 if (intval($arFile["USER_FILE_ID"])&gt;0):
	* 
	*                     // выведем информацию о файле
	*                     ?&gt;
	*                     
	*                     &lt;a title="Просмотр файла" target="_blank" class="tablebodylink" href="/bitrix/tools/form_show_file.php?rid=&lt;?=$result_id?&gt;&amp;hash=&lt;?echo $arFile["USER_FILE_HASH"]?&gt;&#9001;=&lt;?=LANGUAGE_ID?&gt;"&gt;&lt;?=htmlspecialchars($arFile["USER_FILE_NAME"])?&gt;&lt;/a&gt;
	*                     &amp;nbsp;
	*                     (&lt;?
	*                     $a = array("b", "Kb", "Mb", "Gb");
	*                     $pos = 0;
	*                     $size = $arFile["USER_FILE_SIZE"];
	*                     while($size&gt;=1024) {$size /= 1024; $pos++;}
	*                     echo round($size,2)." ".$a[$pos];
	*                     ?&gt;)
	*                     &amp;nbsp;&amp;nbsp;
	*                     [&amp;nbsp;&lt;a title="&lt;?echo str_replace("#FILE_NAME#", $arFile["USER_FILE_NAME"], "Скачать")?&gt;" class="tablebodylink" href="/bitrix/tools/form_show_file.php?rid=&lt;?=$result_id?&gt;&amp;hash=&lt;?echo $arFile["USER_FILE_HASH"]?&gt;&#9001;=&lt;?=LANGUAGE_ID?&gt;&amp;action=download"&gt;Скачать&lt;/a&gt;&amp;nbsp;]
	*                     &lt;br&gt;&lt;br&gt;
	*                     
	*                     &lt;?
	*                 endif;
	*             endif;
	* 
	*             // выведем поле для ввода файла
	*             echo <b>CForm::GetFileField</b>(
	*                 $arAnswer["ID"],
	*                 $arAnswer["FIELD_WIDTH"],
	*                 "FILE",
	*                 0,  // максимальный размер файла не ограничен
	*                 $arFile["USER_FILE_ID"],
	*                 $arAnswer["FIELD_PARAM"]);
	* 
	*             ?&gt;&lt;/td&gt;
	*     &lt;/tr&gt;
	* &lt;/table&gt;
	* &lt;input type="submit" name="save" value="Сохранить"&gt;
	* &lt;/form&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/getfilebyanswerid.php">CFormResult::GetFileByAnswerID</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/htmlnames.php">Имена HTML полей</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/getfilefield.php
	* @author Bitrix
	*/
	public static 	function GetFileField($FIELD_NAME, $WIDTH="", $FILE_TYPE="IMAGE", $MAX_FILE_SIZE=0, $VALUE="", $PARAM_FILE="", $PARAM_CHECKBOX="")
	{
		global $USER;
		if (!is_object($USER)) $USER = new CUser;
		if (strlen($PARAM_FILE)<=0) $PARAM_FILE = " class=\"inputfile\" ";
		if (strlen($PARAM_CHECKBOX)<=0) $PARAM_CHECKBOX = " class=\"inputcheckbox\" ";
		$show_notes = (strtoupper($FILE_TYPE)=="IMAGE" || $USER->isAdmin()) ? true : false;
		return CFile::InputFile("form_".strtolower($FILE_TYPE)."_".$FIELD_NAME, $WIDTH, $VALUE, false, $MAX_FILE_SIZE, $FILE_TYPE, $PARAM_FILE, 0, "", $PARAM_CHECKBOX, $show_notes);
	}

	// возвращает массивы описывающие поля и вопросы формы

	/**
	* <p>Возвращает массивы, описывающие <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">веб-форму</a>, <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопросы</a> и поля для <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответов</a>. Сам метод возвращает ID веб-формы в случае положительного результата, в противном случае - "false".</p>
	*
	*
	* @param int $form_id  ID веб-формы. С версии 3.3.10 переименован в <i>web_form_id</i>
	*
	* @param array &$form  Массив, содержащий параметры формы. Ключи данного массива: <ul> <li>
	* <b>ID</b> - ID веб-формы; </li> <li> <b>TIMESTAMP_X</b> - дата изменения; </li> <li> <b>NAME</b> -
	* наименование; </li> <li> <b>SID</b> - символьный идентификатор; </li> <li>
	* <b>BUTTON</b> - подпись к кнопке при редактировании результата или
	* создании нового результата; </li> <li> <b>C_SORT</b> - порядок сортировки;
	* </li> <li> <b>IMAGE_ID</b> - ID изображения; </li> <li> <b>DESCRIPTION</b> - описание; </li> <li>
	* <b>DESCRIPTION_TYPE</b> - тип описания, допустимы следующие значения: <ul> <li>
	* <b>text</b> - текст; </li> <li> <b>html</b> - HTML код. </li> </ul> </li> <li> <b>MAIL_EVENT_TYPE</b> -
	* идентификатор типа почтового события; </li> <li> <b>FILTER_RESULT_TEMPLATE</b> -
	* путь относительно корня к скрипту, отображающему фильтр по
	* результатам веб-форм в административной части модуля; </li> <li>
	* <b>TABLE_RESULT_TEMPLATE</b> - путь относительно корня к скрипту,
	* отображающему таблицу результатов веб-формы в административной
	* части модуля; </li> <li> <b>STAT_EVENT1</b> - идентификатор event1 типа события
	* для модуля "Статистика"; </li> <li> <b>STAT_EVENT2</b> - идентификатор event2 типа
	* события для модуля "Статистика"; </li> <li> <b>STAT_EVENT3</b> - дополнительный
	* параметр event3 события для модуля "Статистика"; </li> <li> <b>QUESTIONS</b> -
	* количество вопросов формы; </li> <li> <b>C_FIELDS</b> - количество полей
	* формы; </li> <li> <b>STATUSES</b> - количество статусов. </li> </ul> <b>Пример:</b> <pre>
	* Array ( [ID] =&gt; 4 [TIMESTAMP_X] =&gt; 18.05.2005 12:17:05 [NAME] =&gt; Анкета посетителя сайта
	* [SID] =&gt; ANKETA [BUTTON] =&gt; Сохранить [C_SORT] =&gt; 300 [IMAGE_ID] =&gt; 1053 [DESCRIPTION] =&gt;
	* Тестовая форма. [DESCRIPTION_TYPE] =&gt; text [MAIL_EVENT_TYPE] =&gt; FORM_FILLING_ANKETA
	* [FILTER_RESULT_TEMPLATE] =&gt; [TABLE_RESULT_TEMPLATE] =&gt; [STAT_EVENT1] =&gt; form [STAT_EVENT2] =&gt; anketa
	* [STAT_EVENT3] =&gt; [C_FIELDS] =&gt; 1 [QUESTIONS] =&gt; 6 [STATUSES] =&gt; 4 ) </pre>
	*
	* @param array &$questions  Массив, содержащий вопросы и поля формы. Ключами данного массива
	* являются идентификаторы вопросов/полей, а значениями - массивы,
	* каждый из которых описывает один вопрос/поле.<br><br>Ключи массива,
	* описывающего один вопрос/поле: <ul> <li> <b>ID</b> - ID вопроса/поля; </li> <li>
	* <b>FORM_ID</b> - ID формы; </li> <li> <b>TIMESTAMP_X</b> - дата изменения вопроса/поля;
	* </li> <li> <b>ACTIVE</b> - флаг активности [Y|N]; </li> <li> <b>TITLE</b> - текст вопроса
	* либо заголовок поля; </li> <li> <b>TITLE_TYPE</b> - тип текста; </li> <li> <b>SID</b> -
	* символьный идентификатор вопроса/поля; </li> <li> <b>C_SORT</b> - порядок
	* сортировки; </li> <li> <b>ADDITIONAL</b> - если <b> Y</b> - то данная запись
	* является вопросом; если <b> N</b> - то полем формы; </li> <li> <b>REQUIRED</b> -
	* флаг обязательности ответа на вопрос [Y|N]; </li> <li> <b>IN_FILTER</b> - флаг,
	* показывающий отражен ли вопрос/поле в фильтре формы результатов
	* [Y|N]; </li> <li> <b>IN_RESULTS_TABLE</b> - флаг, показывающий отображается ли
	* вопрос/поле в таблице результатов [Y|N]; </li> <li> <b>IN_EXCEL_TABLE</b> - флаг,
	* показывающий отображается ли вопрос/поле в Excel-таблице
	* результатов [Y|N]; </li> <li> <b>FIELD_TYPE</b> тип поля, возможны следующие
	* значения: <ul> <li> <b>text</b> - текст; </li> <li> <b>integer</b> - число; </li> <li> <b>date</b> -
	* дата. </li> </ul> </li> <li> <b>IMAGE_ID</b> - ID изображения в описании вопроса; </li>
	* <li> <b>COMMENTS</b> - служебный комментарий; </li> <li> <b>FILTER_TITLE</b> - заголовок
	* поля фильтра по данному вопросу/полю; </li> <li> <b>RESULTS_TABLE_TITLE</b> -
	* заголовок столбца таблицы результатов. </li> </ul> <b>Пример:</b> <pre> Array (
	* [VS_NAME] =&gt; Array ( [ID] =&gt; 140 [FORM_ID] =&gt; 4 [TIMESTAMP_X] =&gt; 28.08.2003 11:45:57 [ACTIVE] =&gt; Y
	* [TITLE] =&gt; Фамилия, имя, отчество [TITLE_TYPE] =&gt; html [SID] =&gt; VS_NAME [C_SORT] =&gt; 100
	* [ADDITIONAL] =&gt; N [REQUIRED] =&gt; Y [IN_FILTER] =&gt; Y [IN_RESULTS_TABLE] =&gt; Y [IN_EXCEL_TABLE] =&gt; Y
	* [FIELD_TYPE] =&gt; [IMAGE_ID] =&gt; [COMMENTS] =&gt; [FILTER_TITLE] =&gt; ФИО [RESULTS_TABLE_TITLE] =&gt; ФИО )
	* [VS_MARRIED] =&gt; Array ( [ID] =&gt; 143 [FORM_ID] =&gt; 4 [TIMESTAMP_X] =&gt; 11.11.2004 18:13:21 [ACTIVE] =&gt; Y
	* [TITLE] =&gt; Вы женаты / замужем ? [TITLE_TYPE] =&gt; text [SID] =&gt; VS_MARRIED [C_SORT] =&gt; 400
	* [ADDITIONAL] =&gt; N [REQUIRED] =&gt; Y [IN_FILTER] =&gt; Y [IN_RESULTS_TABLE] =&gt; Y [IN_EXCEL_TABLE] =&gt; Y
	* [FIELD_TYPE] =&gt; [IMAGE_ID] =&gt; [COMMENTS] =&gt; [FILTER_TITLE] =&gt; Семейный статус
	* [RESULTS_TABLE_TITLE] =&gt; Семейный статус ) ... ) </pre>
	*
	* @param array &$answers  Массив, содержащие данные по полям для ответа на вопросы
	* веб-формы. Ключами данного массива являются идентификаторы
	* вопросов, а значениями - массивы, каждый из которых описывает
	* набор полей для ответа на вопрос.<br><br>Структура массива,
	* описывающего одно поле ответа: <ul> <li> <b>ID</b> - ID поля для ответа; </li>
	* <li> <b>FIELD_ID</b> - ID вопроса формы; </li> <li> <b>TIMESTAMP_X</b> - дата изменения
	* поля; </li> <li> <b>MESSAGE</b> - текст [ANSWER_TEXT]; </li> <li> <b>C_SORT</b> - порядок
	* сортировки; </li> <li> <b>ACTIVE</b> - флаг активности [Y|N]; </li> <li> <b>VALUE</b> -
	* значение [ANSWER_VALUE]; </li> <li> <b>FIELD_TYPE</b> - тип поля ответа, допустимы
	* следующие значения: <ul> <li> <b>text</b> - однострочное текстовое поле; </li>
	* <li> <b>textarea</b> - многострочное текстовое поле; </li> <li> <b>radio</b> -
	* переключатель одиночного выбора; </li> <li> <b>checkbox</b> - флаг
	* множественного выбора; </li> <li> <b>dropdown</b> - элемента выпадающего
	* списка одиночного выбора; </li> <li> <b>multiselect</b> - элемента списка
	* множественного выбора; </li> <li> <b>date</b> - поле для ввода дата в
	* календарем; </li> <li> <b>image</b> - поле для ввода изображения; </li> <li>
	* <b>file</b> - поле для ввода произвольного файла; </li> <li> <b>password</b> -
	* однострочное поле для ввода пароля. </li> </ul> </li> <li> <b>FIELD_WIDTH</b> -
	* ширина поля ответа; </li> <li> <b>FIELD_HEIGHT</b> - высота поля ответа; </li> <li>
	* <b>FIELD_PARAM</b> - параметры поля ответа. </li> </ul> <b>Пример</b> <pre> Array ( [VS_NAME]
	* =&gt; Array ( [0] =&gt; Array ( [ID] =&gt; 586 [FIELD_ID] =&gt; 140 [TIMESTAMP_X] =&gt; 2003-08-28 11:45:57 [MESSAGE]
	* =&gt; [C_SORT] =&gt; 100 [ACTIVE] =&gt; Y [VALUE] =&gt; [FIELD_TYPE] =&gt; text [FIELD_WIDTH] =&gt; 50 [FIELD_HEIGHT]
	* =&gt; 0 [FIELD_PARAM] =&gt; ) ) [VS_MARRIED] =&gt; Array ( [0] =&gt; Array ( [ID] =&gt; 589 [FIELD_ID] =&gt; 143
	* [TIMESTAMP_X] =&gt; 2004-11-11 18:13:21 [MESSAGE] =&gt; да [C_SORT] =&gt; 100 [ACTIVE] =&gt; Y [VALUE] =&gt;
	* [FIELD_TYPE] =&gt; radio [FIELD_WIDTH] =&gt; 0 [FIELD_HEIGHT] =&gt; 0 [FIELD_PARAM] =&gt; SELECTED class="inputradio" )
	* [1] =&gt; Array ( [ID] =&gt; 590 [FIELD_ID] =&gt; 143 [TIMESTAMP_X] =&gt; 2004-11-11 18:13:21 [MESSAGE] =&gt; нет
	* [C_SORT] =&gt; 200 [ACTIVE] =&gt; Y [VALUE] =&gt; [FIELD_TYPE] =&gt; radio [FIELD_WIDTH] =&gt; 0 [FIELD_HEIGHT] =&gt; 0
	* [FIELD_PARAM] =&gt; ) ) ... ) </pre>
	*
	* @param array &$dropdown  Массив, предназначенный для построения выпадающих списков
	* одиночного выбора; содержит данные по всем полям ответа типа <b>
	* dropdown</b>.<br><br><b>Пример:</b> <pre> Array ( [VS_AGE] =&gt; Array ( [reference] =&gt; Array ( [0] =&gt; - [1]
	* =&gt; 10-19 [2] =&gt; 20-29 [3] =&gt; 30-39 [4] =&gt; 40-49 [5] =&gt; 50-59 [6] =&gt; 60 и старше )
	* [reference_id] =&gt; Array ( [0] =&gt; 608 [1] =&gt; 596 [2] =&gt; 597 [3] =&gt; 598 [4] =&gt; 599 [5] =&gt; 600 [6]
	* =&gt; 601 ) [param] =&gt; Array ( [0] =&gt; NOT_ANSWER [1] =&gt; [2] =&gt; SELECTED [3] =&gt; [4] =&gt; [5] =&gt; [6]
	* =&gt; ) ) ... ) </pre>
	*
	* @param array &$multiselect  Массив, предназначенный для построения списков множественного
	* выбора; содержит данные по всем полям для ответа типа <b>
	* multiselect</b>.<br><br><b>Пример:</b> <pre> Array ( [VS_EDUCATION] =&gt; Array ( [reference] =&gt; Array ( [0]
	* =&gt; начальное [1] =&gt; средне-специальное [2] =&gt; высшее [3] =&gt; ясли с
	* отличием ) [reference_id] =&gt; Array ( [0] =&gt; 602 [1] =&gt; 603 [2] =&gt; 604 [3] =&gt; 605 ) [param] =&gt;
	* Array ( [0] =&gt; [1] =&gt; [2] =&gt; SELECTED [3] =&gt; ) ) ... ) </pre>
	*
	* @param string $get_fields = "Y" Если значение данного параметра равно "Y", то в массиве <i>questions</i>
	* будут представлены только поля формы.<br> Если значение равно "" -
	* вопросы и поля формы.<br> В остальных случаях - в массиве <i>questions</i>
	* будут описаны только вопросы формы.<br><br> Параметр необязательный.
	* По умолчанию - "N" (не добавлять в массив <i>questions</i> данные о полях
	* веб-формы).
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (<b>CForm::GetDataByID</b>($FORM_ID, 
	*     $form, 
	*     $questions, 
	*     $answers, 
	*     $dropdown, 
	*     $multiselect))
	* {
	*     echo "&lt;pre&gt;";
	*         print_r($form);
	*         print_r($questions);
	*         print_r($answers);
	*         print_r($dropdown);
	*         print_r($multiselect);
	*     echo "&lt;/pre&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getresultanswerarray.php">CForm::GetResultAnswerArray</a>
	* </li></ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/getdatabyid.php
	* @author Bitrix
	*/
	public static 	function GetDataByID($WEB_FORM_ID, &$arForm, &$arQuestions, &$arAnswers, &$arDropDown, &$arMultiSelect, $additional="N", $active="N")
	{
		global $strError;
		$WEB_FORM_ID = intval($WEB_FORM_ID);
		$arForm = array();
		$arQuestions = array();
		$arAnswers = array();
		$arDropDown = array();
		$arMultiSelect = array();
		$z = CForm::GetByID($WEB_FORM_ID);
		if ($arForm = $z->Fetch())
		{
			if (!is_set($arForm, "FORM_TEMPLATE")) $arForm["FORM_TEMPLATE"] = CForm::GetFormTemplateByID($WEB_FORM_ID);

			$u = CFormField::GetList($WEB_FORM_ID, $additional, ($by="s_c_sort"), ($order="asc"), $active == "N" ? array("ACTIVE"=>"Y") : array(), $is_filtered);
			while ($ur=$u->Fetch())
			{
				$arQuestions[$ur["SID"]] = $ur;
				$w = CFormAnswer::GetList($ur["ID"], ($by="s_c_sort"), ($order="asc"), $active == "N" ? array("ACTIVE"=>"Y") : array(), $is_filtered);
				while ($wr=$w->Fetch()) $arAnswers[$ur["SID"]][] = $wr;
			}

			// собираем по каждому вопросу все dropdown и multiselect в отдельные массивы
			if (is_array($arQuestions) && is_array($arAnswers))
			{
				foreach ($arQuestions as $arQ)
				{
					$QUESTION_ID = $arQ["SID"];
					$arDropReference = array();
					$arDropReferenceID = array();
					$arDropParam = array();
					$arMultiReference = array();
					$arMultiReferenceID = array();
					$arMultiParam = array();
					if (is_array($arAnswers[$QUESTION_ID]))
					{
						foreach ($arAnswers[$QUESTION_ID] as $arA)
						{
							switch ($arA["FIELD_TYPE"])
							{
								case "dropdown":
									$arDropReference[] = $arA["MESSAGE"];
									$arDropReferenceID[] = $arA["ID"];
									$arDropParam[] = $arA["FIELD_PARAM"];
									break;
								case "multiselect":
									$arMultiReference[] = $arA["MESSAGE"];
									$arMultiReferenceID[] = $arA["ID"];
									$arMultiParam[] = $arA["FIELD_PARAM"];
									break;
							}
						}
					}
					if (count($arDropReference)>0)
						$arDropDown[$QUESTION_ID] = array("reference"=>$arDropReference, "reference_id"=>$arDropReferenceID, "param" => $arDropParam);
					if (count($arMultiReference)>0)
						$arMultiSelect[$QUESTION_ID] = array("reference"=>$arMultiReference, "reference_id"=>$arMultiReferenceID, "param" => $arMultiParam);
				}
			}

			reset($arForm);
			reset($arQuestions);
			reset($arAnswers);
			reset($arDropDown);
			reset($arMultiSelect);

			return $arForm["ID"];
		}
		else return false;

	}

public static 	function __check_PushError(&$container, $MESSAGE, $key = false)
	{
		if (is_array($container))
		{
			if ($key !== false) $container[$key] = $MESSAGE;
			else $container[] = $MESSAGE;
		}
		else $container .= (strlen($container) > 0 ? "<br />" : "").$MESSAGE;
	}

	// check form field values for required fields, date format validation, file type validation, additional validators

	/**
	* <p>Метод проверяет введенные значения на обязательность, правильность формата даты и правильность типа файла. При необходимости проверяются права текущего пользователя. В случае неудачи - возвращает текст ошибки.</p>
	*
	*
	* @param int $form_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">веб-формы</a>.</bod
	*
	* @param array $values = false Массив значений введенных в веб-форме.<br>Параметр необязательный.
	* По умолчанию - "false" (использовать стандартный массив
	* $_REQUEST).<br><br><b>Пример:</b> <pre> Array ( [form_text_586] =&gt; Иванов Иван Иванович
	* [form_date_587] =&gt; 10.03.1992 [form_textarea_588] =&gt; г. Мурманск [form_radio_VS_MARRIED] =&gt; 589
	* [form_checkbox_VS_INTEREST] =&gt; Array ( [0] =&gt; 592 [1] =&gt; 593 [2] =&gt; 594 ) [form_dropdown_VS_AGE] =&gt; 597
	* [form_multiselect_VS_EDUCATION] =&gt; Array ( [0] =&gt; 603 [1] =&gt; 604 ) [form_text_606] =&gt; 2345 [form_image_607]
	* =&gt; 1045 [form_file_607] =&gt; 1049 ) </pre>
	*
	* @param int $result_id = false Если данный метод вызывается для проверки полей при
	* редактировании результата, то в данном параметре необходимо
	* указать его ID.<br>Параметр необязательный. По умолчанию - "false"
	* (новый результат).
	*
	* @param string $check_rights = "Y" Флаг необходимости проверки прав текущего пользователя.
	* Возможны следующие значения: <ul> <li> <b>Y</b> - права необходимо
	* проверить; </li> <li> <b>N</b> - право не нужно проверять. </li> </ul> Для
	* успешной проверки прав, производимой данным методом,
	* пользователь должен обладать как минимум правом <b>[10] Заполнение
	* формы</b> на форму, указанную в параметре <i>form_id</i>. <br> Параметр
	* необязательный. По умолчанию - "Y" (права необходимо проверить).
	*
	* @param string $return_array = "N" Если данный параметр не установлен или равен "N", то метод
	* возвращает отформатированный список ошибок.<br> Если же
	* установлен в "Y", то метод возвращает массив, в котором сообщения
	* об ошибках, связанные с конкретными полями, идут с ключом, равным
	* строковому идентификатору поля, а остальные - с числовым ключом.
	* Например,<br><pre>array( 0 =&gt; "Неверно введено слово с картинки", "test_fld"
	* =&gt; "Не указано значение обязательных полей: Первое поле" )</pre>
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // проверим корректность введенных параметров и 
	* // права пользователя
	* $error = <b>CForm::Check</b>($FORM_ID, $_REQUEST, $RESULT_ID);
	* 
	* // если метод не вернул текст ошибки, то
	* if (strlen($error)&lt;=0) 
	* {
	*     // обновляем результат
	*     CFormResult::Update($RESULT_ID, $_REQUEST);
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/add.php">CFormResult::Add</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/update.php">CFormResult::Update</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/check.php
	* @author Bitrix
	*/
	public static 	function Check($WEB_FORM_ID, $arrVALUES=false, $RESULT_ID=false, $CHECK_RIGHTS="Y", $RETURN_ARRAY="N")
	{
		$err_mess = (CAllForm::err_mess())."<br>Function: Check<br>Line: ";
		global $DB, $APPLICATION, $USER, $_REQUEST, $HTTP_POST_VARS, $HTTP_GET_VARS, $HTTP_POST_FILES;
		if ($arrVALUES===false) $arrVALUES = $_REQUEST;

		$RESULT_ID = intval($RESULT_ID);

		$errors = $RETURN_ARRAY == "Y" ? array() : "";

		$WEB_FORM_ID = intval($WEB_FORM_ID);
		if ($WEB_FORM_ID>0)
		{
			// получаем данные по форме
			$WEB_FORM_ID = CForm::GetDataByID($WEB_FORM_ID, $arForm, $arQuestions, $arAnswers, $arDropDown, $arMultiSelect, "ALL");
			$WEB_FORM_ID = intval($WEB_FORM_ID);
			if ($WEB_FORM_ID>0)
			{
				// проверяем права
				$F_RIGHT = ($CHECK_RIGHTS=="Y") ? CForm::GetPermission($WEB_FORM_ID) : 30;

				if ($F_RIGHT<10) CForm::__check_PushError($errors, GetMessage("FORM_ACCESS_DENIED_FOR_FORM_WRITE"));
				else
				{
					$NOT_ANSWER = "NOT_ANSWER";
					// проходим по вопросам
					foreach ($arQuestions as $key => $arQuestion)
					{
						$arAnswerValues = array();

						$FIELD_ID = $arQuestion["ID"];
						if ($arQuestion["TITLE_TYPE"]=="html")
						{
							$FIELD_TITLE = strip_tags($arQuestion["TITLE"]);
						}
						else
						{
							$FIELD_TITLE = $arQuestion["TITLE"];
						}

						if ($arQuestion["ADDITIONAL"]!="Y")
						{
							// проверяем вопросы формы
							$FIELD_SID = $arQuestion["SID"];
							$FIELD_REQUIRED = $arQuestion["REQUIRED"];

							// массив полей: N - поле не отвечено; Y - поле отвечено;
							if ($FIELD_REQUIRED=="Y") $REQUIRED_FIELDS[$FIELD_SID] = "N";

							$startType = "";
							$bCheckValidators = true;
							// проходим по ответам
							if (is_array($arAnswers[$FIELD_SID]))
							{
								foreach ($arAnswers[$FIELD_SID] as $key => $arAnswer)
								{
									$ANSWER_ID = 0;
									$FIELD_TYPE = $arAnswer["FIELD_TYPE"];
									$FIELD_PARAM = $arAnswer["FIELD_PARAM"];

									if ($startType == "")
										$startType = $FIELD_TYPE;
									else
										$bCheckValidators &= $startType == $FIELD_TYPE;

									switch ($FIELD_TYPE) :

										case "radio":
										case "dropdown":

											$fname = "form_".$FIELD_TYPE."_".$FIELD_SID;
											$arAnswerValues[] = $arrVALUES[$fname];
											$ANSWER_ID = intval($arrVALUES[$fname]);
											if ($ANSWER_ID>0 && $ANSWER_ID==$arAnswer["ID"])
											{
												if ($FIELD_REQUIRED=="Y" && !preg_match("/".$NOT_ANSWER."/i", $FIELD_PARAM))
												{
													$REQUIRED_FIELDS[$FIELD_SID] = "Y";
												}
											}

										break;

										case "checkbox":
										case "multiselect":

											$fname = "form_".$FIELD_TYPE."_".$FIELD_SID;
											if (is_array($arrVALUES[$fname]) && count($arrVALUES[$fname])>0)
											{
												$arAnswerValues = $arrVALUES[$fname];
												reset($arrVALUES[$fname]);
												foreach($arrVALUES[$fname] as $ANSWER_ID)
												{
													$ANSWER_ID = intval($ANSWER_ID);
													if ($ANSWER_ID>0 && $ANSWER_ID==$arAnswer["ID"])
													{
														if ($FIELD_REQUIRED=="Y" && !preg_match("/".$NOT_ANSWER."/i", $FIELD_PARAM))
														{
															$REQUIRED_FIELDS[$FIELD_SID] = "Y";
															break;
														}
													}
												}
											}

										break;

										case "text":
										case "textarea":
										case "password":
										case "hidden":

											$fname = "form_".$FIELD_TYPE."_".$arAnswer["ID"];
											$ANSWER_ID = intval($arAnswer["ID"]);
											$USER_TEXT = $arrVALUES[$fname];
											$arAnswerValues[] = $arrVALUES[$fname];
											if (strlen(trim($USER_TEXT))>0)
											{
												if ($FIELD_REQUIRED=="Y")
												{
													$REQUIRED_FIELDS[$FIELD_SID] = "Y";
													break;
												}
											}
										break;

										case "url":

											$fname = "form_".$FIELD_TYPE."_".$arAnswer["ID"];
											$arAnswerValues[] = $arrVALUES[$fname];
											$ANSWER_ID = intval($arAnswer["ID"]);
											$USER_TEXT = $arrVALUES[$fname];
											if (strlen($USER_TEXT)>0)
											{
												if (!preg_match("/^(http|https|ftp):\/\//i",$USER_TEXT))
												{
													CForm::__check_PushError($errors, GetMessage('FORM_ERROR_BAD_URL'), $FIELD_SID);
												}
												if ($FIELD_REQUIRED=="Y")
												{
													$REQUIRED_FIELDS[$FIELD_SID] = "Y";
													break;
												}
											}

										break;

										case "email":

											$fname = "form_".$FIELD_TYPE."_".$arAnswer["ID"];
											$arAnswerValues[] = $arrVALUES[$fname];
											$ANSWER_ID = intval($arAnswer["ID"]);
											$USER_TEXT = $arrVALUES[$fname];
											if (strlen($USER_TEXT)>0)
											{
												if (!check_email($USER_TEXT))
												{
													CForm::__check_PushError($errors, GetMessage('FORM_ERROR_BAD_EMAIL'), $FIELD_SID);
												}
												if ($FIELD_REQUIRED=="Y")
												{
													$REQUIRED_FIELDS[$FIELD_SID] = "Y";
													break;
												}
											}

										break;

										case "date":

											$fname = "form_".$FIELD_TYPE."_".$arAnswer["ID"];
											$arAnswerValues[] = $arrVALUES[$fname];
											$USER_DATE = $arrVALUES[$fname];
											if (strlen($USER_DATE)>0)
											{
												if (!CheckDateTime($USER_DATE))
												{
													CForm::__check_PushError(
														$errors,
														str_replace("#FIELD_NAME#", $FIELD_TITLE, GetMessage("FORM_INCORRECT_DATE_FORMAT")),
														$FIELD_SID
													);
												}
												if ($FIELD_REQUIRED=="Y")
												{
													$REQUIRED_FIELDS[$FIELD_SID] = "Y";
													break;
												}
											}
											break;

										case "image":

											$fname = "form_".$FIELD_TYPE."_".$arAnswer["ID"];
											$fname_del = $arrVALUES["form_".$FIELD_TYPE."_".$arAnswer["ID"]."_del"];
											$ANSWER_ID = intval($arAnswer["ID"]);
											$arIMAGE = isset($arrVALUES[$fname]) ? $arrVALUES[$fname] : $HTTP_POST_FILES[$fname];
											if (is_array($arIMAGE) && strlen($arIMAGE["tmp_name"])>0)
											{
												$arIMAGE["MODULE_ID"] = "form";
												if (strlen(CFile::CheckImageFile($arIMAGE))>0)
												{
													CForm::__check_PushError(
														$errors,
														str_replace("#FIELD_NAME#", $FIELD_TITLE, GetMessage("FORM_INCORRECT_FILE_TYPE")),
														$FIELD_SID

													);
												}
												else
												{
													$arAnswerValues[] = $arIMAGE;
												}

												if ($FIELD_REQUIRED=="Y")
												{
													$REQUIRED_FIELDS[$FIELD_SID] = "Y";
													break;
												}
											}
											elseif ($RESULT_ID>0 && $fname_del!="Y")
											{
												$REQUIRED_FIELDS[$FIELD_SID] = "Y";
												break;
											}

										break;

										case "file":

											$fname = "form_".$FIELD_TYPE."_".$arAnswer["ID"];
											$fname_del = $arrVALUES["form_".$FIELD_TYPE."_".$arAnswer["ID"]."_del"];
											$arFILE = isset($arrVALUES[$fname]) ? $arrVALUES[$fname] : $HTTP_POST_FILES[$fname];
											if (is_array($arFILE) && strlen($arFILE["tmp_name"])>0)
											{
												$arAnswerValues[] = $arFILE;
												if ($FIELD_REQUIRED=="Y")
												{
													$REQUIRED_FIELDS[$FIELD_SID] = "Y";
													break;
												}
											}
											elseif ($RESULT_ID>0 && $fname_del!="Y")
											{
												$REQUIRED_FIELDS[$FIELD_SID] = "Y";
												break;
											}

										break;

									endswitch;
								}
							}
						}
						else // проверяем дополнительные поля
						{
							$FIELD_TYPE = $arQuestion["FIELD_TYPE"];

							$fname = "form_date_ADDITIONAL_".$arQuestion["ID"];
							$arAnswerValues = array($arrVALUES[$fname]);

							$bCheckValidators = true;
							switch ($FIELD_TYPE) :

								case "date":

									$USER_DATE = $arrVALUES[$fname];
									if (strlen($USER_DATE)>0)
									{
										if (!CheckDateTime($USER_DATE))
										{
											CForm::__check_PushError(
												$errors,
												str_replace("#FIELD_NAME#", $FIELD_TITLE, GetMessage("FORM_INCORRECT_DATE_FORMAT")),
												$FIELD_SID
											);
										}
									}
								break;

							endswitch;
						}

						// check custom validators
						if ($bCheckValidators)
						{
							if ($arQuestion["ADDITIONAL"] == "Y" || is_array($arAnswers[$FIELD_SID]))
							{
								$rsValidatorList = CFormValidator::GetList($FIELD_ID, array("TYPE" => $FIELD_TYPE), $by="C_SORT", $order="ASC");
								while ($arValidator = $rsValidatorList->Fetch())
								{
									if (!CFormValidator::Execute($arValidator, $arQuestion, $arAnswers[$FIELD_SID], $arAnswerValues))
									{
										if ($e = $APPLICATION->GetException())
										{
											CForm::__check_PushError($errors, str_replace("#FIELD_NAME#", $FIELD_TITLE, $e->GetString()), $FIELD_SID);
										}
									}
								}
							}
						}
					}

					if (($arForm["USE_CAPTCHA"] == "Y" && !$RESULT_ID && !defined('ADMIN_SECTION')))
					{
						if (!($GLOBALS["APPLICATION"]->CaptchaCheckCode($arrVALUES["captcha_word"], $arrVALUES["captcha_sid"])))
						{
							CForm::__check_PushError($errors, GetMessage("FORM_WRONG_CAPTCHA"));
						}
					}

					if (is_array($REQUIRED_FIELDS) && count($REQUIRED_FIELDS)>0)
					{
						foreach ($REQUIRED_FIELDS as $key => $value)
						{
							if ($value == "N")
							{
								if (strlen($arQuestions[$key]["RESULTS_TABLE_TITLE"])>0)
								{
									$title = $arQuestions[$key]["RESULTS_TABLE_TITLE"];
								}
								/*elseif (strlen($arQuestions[$key]["FILTER_TITLE"])>0)
								{
									$title = TrimEx($arQuestions[$key]["FILTER_TITLE"],":");
								}*/
								else
								{
									$title = ($arQuestions[$key]["TITLE_TYPE"]=="html") ? strip_tags($arQuestions[$key]["TITLE"]) : $arQuestions[$key]["TITLE"];
								}
								if ($RETURN_ARRAY == 'N')
									$EMPTY_REQUIRED_NAMES[] = $title;
								else
									CForm::__check_PushError($errors, GetMessage("FORM_EMPTY_REQUIRED_FIELDS").' '.$title, $key);
							}
						}
					}

					if ($RETURN_ARRAY == 'N')
					{
						if (is_array($EMPTY_REQUIRED_NAMES) && count($EMPTY_REQUIRED_NAMES)>0)
						{
							$errMsg = "";
							$errMsg .= GetMessage("FORM_EMPTY_REQUIRED_FIELDS")."<br />";
							foreach ($EMPTY_REQUIRED_NAMES as $key => $name) $errMsg .= ($key != 0 ? "<br />" : "")."&nbsp;&nbsp;&raquo;&nbsp;\"".$name."\"";
							CForm::__check_PushError($errors, $errMsg);
						}
					}
				}
			}
			else CForm::__check_PushError($errors, GetMessage("FORM_INCORRECT_FORM_ID"));
		}
		return $errors;
	}

	// проверка формы
public static 	function CheckFields($arFields, $FORM_ID, $CHECK_RIGHTS="Y")
	{
		$err_mess = (CAllForm::err_mess())."<br>Function: CheckFields<br>Line: ";
		global $DB, $strError, $APPLICATION, $USER;
		$str = "";
		$FORM_ID = intval($FORM_ID);
		$RIGHT_OK = "N";
		if ($CHECK_RIGHTS!="Y" || CForm::IsAdmin()) $RIGHT_OK = "Y";
		else
		{
			if ($FORM_ID>0)
			{
				$F_RIGHT = CForm::GetPermission($FORM_ID);
				if ($F_RIGHT>=30) $RIGHT_OK = "Y";
			}
		}

		if ($RIGHT_OK=="Y")
		{

			if (strlen($arFields["SID"])>0) $arFields["VARNAME"] = $arFields["SID"];
			elseif (strlen($arFields["VARNAME"])>0) $arFields["SID"] = $arFields["VARNAME"];

			if ($FORM_ID<=0 || ($FORM_ID>0 && is_set($arFields, "NAME")))
			{
				if (strlen(trim($arFields["NAME"]))<=0) $str .= GetMessage("FORM_ERROR_FORGOT_NAME")."<br>";
			}

			if ($FORM_ID<=0 || ($FORM_ID>0 && is_set($arFields, "SID")))
			{
				if (strlen(trim($arFields["SID"]))<=0) $str .= GetMessage("FORM_ERROR_FORGOT_SID")."<br>";
				if (preg_match("/[^A-Za-z_01-9]/",$arFields["SID"])) $str .= GetMessage("FORM_ERROR_INCORRECT_SID")."<br>";
				else
				{
					$strSql = "SELECT ID FROM b_form WHERE SID='".$DB->ForSql(trim($arFields["SID"]),50)."' and ID<>'$FORM_ID'";
					$z = $DB->Query($strSql, false, $err_mess.__LINE__);
					if ($zr = $z->Fetch())
					{
						$s = str_replace("#TYPE#", GetMessage("FORM_TYPE_FORM"), GetMessage("FORM_ERROR_WRONG_SID"));
						$s = str_replace("#ID#",$zr["ID"],$s);
						$str .= $s."<br>";
					}
					else
					{
						$strSql = "SELECT ID, ADDITIONAL FROM b_form_field WHERE SID='".$DB->ForSql(trim($arFields["SID"]),50)."'";
						$z = $DB->Query($strSql, false, $err_mess.__LINE__);
						if ($zr = $z->Fetch())
						{
							$s = ($zr["ADDITIONAL"]=="Y") ?
								str_replace("#TYPE#", GetMessage("FORM_TYPE_FIELD"), GetMessage("FORM_ERROR_WRONG_SID")) :
								str_replace("#TYPE#", GetMessage("FORM_TYPE_QUESTION"), GetMessage("FORM_ERROR_WRONG_SID"));

							$s = str_replace("#ID#",$zr["ID"],$s);
							$str .= $s."<br>";
						}
					}
				}
			}
			$str .= CFile::CheckImageFile($arFields["arIMAGE"]);
		}
		else $str .= GetMessage("FORM_ERROR_ACCESS_DENIED");

		$strError .= $str;
		if (strlen($str)>0) return false; else return true;
	}

	// добавление/обновление формы

	/**
	* <p>Добавляет новую <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">веб-форму</a> или обновляет заданную. Возвращает ID обновленной или добавленной веб-формы в случае положительного результата, в противном случае - "false".</p> <p class="note"><b>Примечание</b><br>При обновлении существующей веб-формы (или при добавлении новой веб-формы), автоматически <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/setmailtemplate.php">обновляется</a> тип почтового события (либо <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/setmailtemplate.php">создаётся</a> новый тип).</p>
	*
	*
	* @param array $fields  Массив значений полей; в качестве ключей массива допустимы: <ul> <li>
	* <b>NAME</b><font color="red">*</font> - заголовок веб-формы; </li> <li> <b>SID</b><font
	* color="red">*</font> - символьный идентификатор веб-формы; </li> <li> <b>C_SORT</b> -
	* индекс сортировки; </li> <li> <b>BUTTON</b> - подпись к кнопке при создании
	* или редактировании результата; </li> <li> <b>USE_RESTRICTIONS</b> - использовать
	* ограничения; </li> <li> <b>RESTRICT_USER</b> - максимальное количество
	* результатов от пользователя; </li> <li> <b>RESTRICT_TIME</b> - минимальный
	* промежуток времени между результатами; </li> <li> <b>DESCRIPTION</b> -
	* описание; </li> <li> <b>DESCRIPTION_TYPE</b> - тип описания, допустимы следующие
	* значения: <ul> <li> <b>text</b> - текст; </li> <li> <b>html</b> - HTML код. </li> </ul> </li> <li>
	* <b>FILTER_RESULT_TEMPLATE</b> - путь относительно корня к файлу, который будет
	* использован для показа фильтра результатов в административной
	* части модуля; </li> <li> <b>TABLE_RESULT_TEMPLATE</b> - путь относительно корня к
	* файлу, который будет использован для показа таблицы результатов
	* в административной части модуля; </li> <li> <b>STAT_EVENT1</b> - идентификатор
	* EVENT1 типа события для модуля "Статистика"; </li> <li> <b>STAT_EVENT2</b> -
	* идентификатор EVENT2 типа события для модуля "Статистика"; </li> <li>
	* <b>STAT_EVENT3</b> - дополнительный параметр события для модуля
	* "Статистика"; </li> <li> <b>arIMAGE</b> - массив, описывающий изображение
	* веб-формы, допустимы следующие ключи этого массива: <ul> <li> <b>name</b> -
	* имя файла; </li> <li> <b>size</b> - размер файла; </li> <li> <b>tmp_name</b> - временный
	* путь на сервере; </li> <li> <b>type</b> - тип загружаемого файла; </li> <li> <b>del</b>
	* - если значение равно "Y", то изображение будет удалено; </li> <li>
	* <b>MODULE_ID</b> - идентификатор модуля "Веб-формы" ("form"). </li> </ul> </li> <li>
	* <b>arSITE</b> - массив идентификаторов сайтов, к которым будет
	* привязана данная форма: <pre>array("ID_САЙТА_1", "ID_САЙТА_2", ...)</pre> </li> <li>
	* <b>arMAIL_TEMPLATE</b> - массив ID почтовых шаблонов, приписанных к данной
	* форме: <pre>array("ID_ШАБЛОНА_1", "ID_ШАБЛОНА_2", ...)</pre> </li> <li> <b>arMENU</b> - массив
	* заголовков меню, отображаемого в административной части и
	* ведущего на результаты данной формы: <pre>array("ID_ЯЗЫКА_1" =&gt; "МЕНЮ_1",
	* "ID_ЯЗЫКА_2" =&gt; "МЕНЮ_2", ...)</pre> </li> <li> <b>arGROUP</b> - массив, описывающий
	* права групп пользователей на данную веб-форму: <pre>array("ID_ГРУППЫ_1"
	* =&gt; "ПРАВО_1", "ID_ГРУППЫ_2" =&gt; "ПРАВО_2", ...)</pre> </li> </ul> <font color="red">*</font> -
	* обязательно к заполнению.
	*
	* @param mixed $form_id = false ID обновляемой веб-формы.<br><br>Параметр необязательный. По
	* умолчанию - "false" (добавление новой веб-формы).
	*
	* @param string $check_rights = "Y" Флаг необходимости проверки прав текущего пользователя.
	* Возможны следующие значения: <ul> <li> <b>Y</b> - права необходимо
	* проверить; </li> <li> <b>N</b> - право не нужно проверять. </li> </ul> Для
	* обновления параметров веб-формы необходимо иметь право <b>[30]
	* Полный доступ</b> на форму, указанную в параметре <i>form_id</i>. Для
	* добавления новой веб-формы необходимо иметь право <b>[W] Полный
	* доступ</b> на модуль <b>Веб-формы</b>.<br><br>Параметр необязательный. По
	* умолчанию - "Y" (права необходимо проверить).
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* //<************************************************
	*              Добавление веб-формы
	* ************************************************>//
	* 
	* // создадим массив описывающий изображение 
	* // находящееся в файле на сервере
	* $arIMAGE = CFile::MakeFileArray($_SERVER["DOCUMENT_ROOT"]."/images/web_form.gif");
	* $arIMAGE["MODULE_ID"] = "form";
	* 
	* $arFields = array(
	*     "NAME"              =&gt; "Анкета посетителя",
	*     "SID"               =&gt; "VISITOR_FORM",
	*     "C_SORT"            =&gt; 300,
	*     "BUTTON"            =&gt; "Сохранить",
	*     "DESCRIPTION"       =&gt; "Заполните пож-та анкету",
	*     "DESCRIPTION_TYPE"  =&gt; "text",
	*     "STAT_EVENT1"       =&gt; "form",
	*     "STAT_EVENT2"       =&gt; "visitor_form",
	*     "arSITE"            =&gt; array("r1"),
	*     "arMENU"            =&gt; array("ru" =&gt; "Анкета посетителя", "en" =&gt; "Visitor Form"),
	*     "arGROUP"           =&gt; array("2" =&gt; "15", "3" =&gt; "20"),
	*     "arIMAGE"           =&gt; $arIMAGE
	*     );
	* 
	* // добавим новую веб-форму
	* $NEW_ID = <b>CForm::Set</b>($arFields);
	* if ($NEW_ID&gt;0) echo "Добавлена веб-форма с ID=".$NEW_ID;
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
	* // пример обновления веб-формы параметры которой были визуально 
	* // отредактированы в административной части
	* 
	* $w = CGroup::GetList($v1, $v2, Array("ADMIN"=&gt;"N"), $v3);
	* $arGroups = array();
	* while ($wr=$w-&gt;Fetch()) $arGroups[] = array("ID"=&gt;$wr["ID"], "NAME"=&gt;$wr["NAME"]);
	* 
	* $z = CLanguage::GetList($v1, $v2, array("ACTIVE" =&gt; "Y"));
	* $arFormMenuLang = array();
	* while ($zr=$z-&gt;Fetch()) $arFormMenuLang[] = array("LID"=&gt;$zr["LID"], "NAME"=&gt;$zr["NAME"]);
	* 
	* $rs = CSite::GetList(($by="sort"), ($order="asc"));
	* while ($ar = $rs-&gt;Fetch()) 
	* {
	*     if ($ar["DEF"]=="Y") $def_site_id = $ar["ID"];
	*     $arrSites[$ar["ID"]] = $ar;
	* }
	* 
	* if ((strlen($save)&gt;0 || strlen($apply)&gt;0) &amp;&amp; $REQUEST_METHOD=="POST")
	* {
	*     $arIMAGE_ID = $HTTP_POST_FILES["IMAGE_ID"];
	*     $arIMAGE_ID["MODULE_ID"] = "form";
	*     $arIMAGE_ID["del"] = ${"IMAGE_ID_del"};
	*     $arFields = array(
	*         "NAME"                      =&gt; $NAME,
	*         "SID"                       =&gt; $SID,
	*         "C_SORT"                    =&gt; $C_SORT,
	*         "BUTTON"                    =&gt; $BUTTON,
	*         "DESCRIPTION"               =&gt; $DESCRIPTION,
	*         "DESCRIPTION_TYPE"          =&gt; $DESCRIPTION_TYPE,
	*         "FILTER_RESULT_TEMPLATE"    =&gt; $FILTER_RESULT_TEMPLATE,
	*         "TABLE_RESULT_TEMPLATE"     =&gt; $TABLE_RESULT_TEMPLATE,
	*         "STAT_EVENT1"               =&gt; $STAT_EVENT1,
	*         "STAT_EVENT2"               =&gt; $STAT_EVENT2,
	*         "STAT_EVENT3"               =&gt; $STAT_EVENT3,
	*         "arIMAGE"                   =&gt; $arIMAGE_ID,
	*         "arSITE"                    =&gt; $arSITE,
	*         "arMAIL_TEMPLATE"           =&gt; $arMAIL_TEMPLATE
	*         );
	* 
	*     // меню
	*     $arMENU = array();
	*     reset($arFormMenuLang);
	*     while (list(,$arrL)=each($arFormMenuLang))
	*     {
	*         $var = "MENU_".$arrL["LID"];
	*         global $$var;
	*         $arMENU[$arrL["LID"]] = $$var;
	*     }
	*     $arFields["arMENU"] = $arMENU;
	* 
	*     // права доступа
	*     $arGROUP = array();
	*     reset($arGroups);
	*     while (list(,$arrG)=each($arGroups))
	*     {
	*         $var = "PERMISSION_".$arrG["ID"];
	*         global $$var;
	*         $arGROUP[$arrG["ID"]] = $$var;
	*     }
	*     $arFields["arGROUP"] = $arGROUP;
	*     
	*     if ($ID = <b>CForm::Set</b>($arFields, $ID))
	*     {
	*         if (strlen($strError)&lt;=0)
	*         {
	*             if (strlen($save)&gt;0) LocalRedirect("form_list.php?lang=".LANGUAGE_ID); 
	*             else LocalRedirect("form_edit.php?ID=".$ID."&#9001;=".LANGUAGE_ID);
	*         }
	*     }
	*     $DB-&gt;PrepareFields("b_form");
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/index.php">Поля CForm</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/permissions.php#form">Права на веб-форму</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cfile/makefilearray.php">CFile::MakeFileArray</a> <br> </li> </ul>
	* <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/set.php
	* @author Bitrix
	*/
	public static 	function Set($arFields, $FORM_ID=false, $CHECK_RIGHTS="Y")
	{
		$err_mess = (CAllForm::err_mess())."<br>Function: Set<br>Line: ";
		global $DB, $USER, $strError, $APPLICATION;
		$FORM_ID = intval($FORM_ID);
		if (CForm::CheckFields($arFields, $FORM_ID, $CHECK_RIGHTS))
		{
			$arFields_i = array();

			if (strlen(trim($arFields["SID"]))>0) $arFields["VARNAME"] = $arFields["SID"];
			elseif (strlen($arFields["VARNAME"])>0) $arFields["SID"] = $arFields["VARNAME"];

			//$arFields_i["TIMESTAMP_X"] = $DB->GetNowFunction();
			$arFields_i["TIMESTAMP_X"] = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")), time()+CTimeZone::GetOffset());

			if (is_set($arFields, "NAME"))
				$arFields_i["NAME"] = $arFields['NAME'];//"'".$DB->ForSql($arFields["NAME"],255)."'";

			if (is_set($arFields, "SID"))
				$arFields_i["SID"] = $arFields['SID'];//"'".$DB->ForSql($arFields["SID"],255)."'";

			if (is_set($arFields, "DESCRIPTION"))
				$arFields_i["DESCRIPTION"] = $arFields['DESCRIPTION'];//"'".$DB->ForSql($arFields["DESCRIPTION"],2000)."'";

			if (is_set($arFields, "C_SORT"))
				$arFields_i["C_SORT"] = intval($arFields["C_SORT"]);//"'".intval($arFields["C_SORT"])."'";

			if (is_array($arrSITE))
			{
				reset($arrSITE);
				list($k, $arFields["FIRST_SITE_ID"]) = each($arrSITE);
			}

			if (is_set($arFields, "BUTTON"))
				$arFields_i["BUTTON"] = $arFields['BUTTON']; //"'".$DB->ForSql($arFields["BUTTON"],255)."'";

			if (is_set($arFields, "USE_CAPTCHA"))
				$arFields_i["USE_CAPTCHA"] = $arFields["USE_CAPTCHA"] == "Y" ? "Y" : "N";// "'Y'" : "'N'";

			if (is_set($arFields, "DESCRIPTION_TYPE"))
				$arFields_i["DESCRIPTION_TYPE"] = ($arFields["DESCRIPTION_TYPE"]=="html") ? "html" : "text";//"'html'" : "'text'";

			if (is_set($arFields, "FORM_TEMPLATE"))
				$arFields_i["FORM_TEMPLATE"] = $arFields['FORM_TEMPLATE'];//"'".$DB->ForSql($arFields["FORM_TEMPLATE"])."'";

			if (is_set($arFields, "USE_DEFAULT_TEMPLATE"))
				$arFields_i["USE_DEFAULT_TEMPLATE"] = $arFields["USE_DEFAULT_TEMPLATE"] == "Y" ? "Y" : "N";//"'Y'" : "'N'";

			if (is_set($arFields, "SHOW_TEMPLATE"))
				$arFields_i["SHOW_TEMPLATE"] = $arFields['SHOW_TEMPLATE'];//"'".$DB->ForSql($arFields["SHOW_TEMPLATE"],255)."'";

			if (is_set($arFields, "SHOW_RESULT_TEMPLATE"))
				$arFields_i["SHOW_RESULT_TEMPLATE"] = $arFields['SHOW_RESULT_TEMPLATE']; //"'".$DB->ForSql($arFields["SHOW_RESULT_TEMPLATE"],255)."'";

			if (is_set($arFields, "PRINT_RESULT_TEMPLATE"))
				$arFields_i["PRINT_RESULT_TEMPLATE"] = $arFields['PRINT_RESULT_TEMPLATE'];//"'".$DB->ForSql($arFields["PRINT_RESULT_TEMPLATE"],255)."'";

			if (is_set($arFields, "EDIT_RESULT_TEMPLATE"))
				$arFields_i["EDIT_RESULT_TEMPLATE"] = $arFields['EDIT_RESULT_TEMPLATE'];//"'".$DB->ForSql($arFields["EDIT_RESULT_TEMPLATE"],255)."'";

			if (is_set($arFields, "FILTER_RESULT_TEMPLATE"))
				$arFields_i["FILTER_RESULT_TEMPLATE"] = $arFields['FILTER_RESULT_TEMPLATE']; //"'".$DB->ForSql($arFields["FILTER_RESULT_TEMPLATE"],255)."'";

			if (is_set($arFields, "TABLE_RESULT_TEMPLATE"))
				$arFields_i["TABLE_RESULT_TEMPLATE"] = $arFields['TABLE_RESULT_TEMPLATE']; //"'".$DB->ForSql($arFields["TABLE_RESULT_TEMPLATE"],255)."'";

			if (is_set($arFields, "USE_RESTRICTIONS"))
				$arFields_i["USE_RESTRICTIONS"] = $arFields["USE_RESTRICTIONS"] == "Y" ? "Y" : "N";//"'Y'" : "'N'";

			if (is_set($arFields, "RESTRICT_USER"))
				$arFields_i["RESTRICT_USER"] = intval($arFields["RESTRICT_USER"]);//"'".intval($arFields["RESTRICT_USER"])."'";

			if (is_set($arFields, "RESTRICT_TIME"))
				$arFields_i["RESTRICT_TIME"] = intval($arFields["RESTRICT_TIME"]);//"'".intval($arFields["RESTRICT_TIME"])."'";

			if (is_set($arFields, "arRESTRICT_STATUS"))
				$arFields_i["RESTRICT_STATUS"] = implode(",", $arFields["arRESTRICT_STATUS"]);//"'".$DB->ForSql(implode(",", $arFields["arRESTRICT_STATUS"]))."'";

			if (is_set($arFields, "STAT_EVENT1"))
				$arFields_i["STAT_EVENT1"] = $arFields['STAT_EVENT1']; //"'".$DB->ForSql($arFields["STAT_EVENT1"],255)."'";

			if (is_set($arFields, "STAT_EVENT2"))
				$arFields_i["STAT_EVENT2"] = $arFields['STAT_EVENT2']; //"'".$DB->ForSql($arFields["STAT_EVENT2"],255)."'";

			if (is_set($arFields, "STAT_EVENT3"))
				$arFields_i["STAT_EVENT3"] = $arFields['STAT_EVENT3']; //"'".$DB->ForSql($arFields["STAT_EVENT3"],255)."'";

			if (CForm::IsOldVersion()!="Y")
			{
				unset($arFields_i["SHOW_TEMPLATE"]);
				unset($arFields_i["SHOW_RESULT_TEMPLATE"]);
				unset($arFields_i["PRINT_RESULT_TEMPLATE"]);
				unset($arFields_i["EDIT_RESULT_TEMPLATE"]);
			}

			$z = $DB->Query("SELECT IMAGE_ID, SID, SID as VARNAME FROM b_form WHERE ID='".$FORM_ID."'", false, $err_mess.__LINE__);
			$zr = $z->Fetch();
			$oldSID = $zr["SID"];
			if (strlen($arFields["arIMAGE"]["name"])>0 || strlen($arFields["arIMAGE"]["del"])>0)
			{
				if(intval($zr["IMAGE_ID"]) > 0)
					$arFields["arIMAGE"]["old_file"] = $zr["IMAGE_ID"];

				if (!array_key_exists("MODULE_ID", $arFields["arIMAGE"]) || strlen($arFields["arIMAGE"]["MODULE_ID"]) <= 0)
					$arFields["arIMAGE"]["MODULE_ID"] = "form";

				$fid = CFile::SaveFile($arFields["arIMAGE"], "form");
				if (intval($fid)>0)	$arFields_i["IMAGE_ID"] = intval($fid);
				else $arFields_i["IMAGE_ID"] = "null";
			}

			if ($arFields['SID'])
				$arFields_i["MAIL_EVENT_TYPE"] = "FORM_FILLING_".$arFields["SID"];
			else
				$arFields_i["MAIL_EVENT_TYPE"] = "FORM_FILLING_".$oldSID;

			if ($FORM_ID>0)
			{
				$strUpdate = $DB->PrepareUpdate('b_form', $arFields_i);
				if ($strUpdate != '')
				{
					$query = 'UPDATE b_form SET '.$strUpdate." WHERE ID='".$FORM_ID."'";
					$arBinds = array('FORM_TEMPLATE' => $arFields_i['FORM_TEMPLATE']);
					$DB->QueryBind($query, $arBinds);
				}

				//$DB->Update("b_form", $arFields_i, "WHERE ID='".$FORM_ID."'", $err_mess.__LINE__);
				CForm::SetMailTemplate($FORM_ID, "N", $oldSID);
			}
			else
			{
				//$FORM_ID = $DB->Insert("b_form", $arFields_i, $err_mess.__LINE__);
				$FORM_ID = $DB->Add("b_form", $arFields_i, array('FORM_TEMPLATE'));
				CForm::SetMailTemplate($FORM_ID, "N");
			}
			$FORM_ID = intval($FORM_ID);

			if ($FORM_ID>0)
			{
				// сайты
				if (is_set($arFields, "arSITE"))
				{
					$DB->Query("DELETE FROM b_form_2_site WHERE FORM_ID='".$FORM_ID."'", false, $err_mess.__LINE__);
					if (is_array($arFields["arSITE"]))
					{
						reset($arFields["arSITE"]);
						foreach($arFields["arSITE"] as $sid)
						{
							$strSql = "
								INSERT INTO b_form_2_site (FORM_ID, SITE_ID) VALUES (
									$FORM_ID,
									'".$DB->ForSql($sid,2)."'
								)
								";
							$DB->Query($strSql, false, $err_mess.__LINE__);
						}
					}
				}

				// меню
				if (is_set($arFields, "arMENU"))
				{
					$DB->Query("DELETE FROM b_form_menu WHERE FORM_ID='".$FORM_ID."'", false, $err_mess.__LINE__);
					if (is_array($arFields["arMENU"]))
					{
						reset($arFields["arMENU"]);
						while(list($lid,$menu)=each($arFields["arMENU"]))
						{
							$arFields_i = array(
								"FORM_ID"	=> $FORM_ID,
								"LID"		=> "'".$DB->ForSql($lid,2)."'",
								"MENU"		=> "'".$DB->ForSql($menu,50)."'"
								);

							$DB->Insert("b_form_menu", $arFields_i, $err_mess.__LINE__);
						}
					}
				}

				// почтовые шаблоны
				if (is_set($arFields, "arMAIL_TEMPLATE"))
				{
					$DB->Query("DELETE FROM b_form_2_mail_template WHERE FORM_ID='".$FORM_ID."'", false, $err_mess.__LINE__);
					if (is_array($arFields["arMAIL_TEMPLATE"]))
					{
						reset($arFields["arMAIL_TEMPLATE"]);
						foreach($arFields["arMAIL_TEMPLATE"] as $mid)
						{
							$strSql = "
								INSERT INTO b_form_2_mail_template (FORM_ID, MAIL_TEMPLATE_ID) VALUES (
									$FORM_ID,
									'".intval($mid)."'
								)
								";
							$DB->Query($strSql, false, $err_mess.__LINE__);
						}
					}
				}

				// группы
				if (is_set($arFields, "arGROUP"))
				{
					$DB->Query("DELETE FROM b_form_2_group WHERE FORM_ID='".$FORM_ID."'", false, $err_mess.__LINE__);
					if (is_array($arFields["arGROUP"]))
					{
						reset($arFields["arGROUP"]);
						while(list($group_id,$perm)=each($arFields["arGROUP"]))
						{
							if (intval($perm)>0)
							{
								$arFields_i = array(
									"FORM_ID"		=> $FORM_ID,
									"GROUP_ID"		=> "'".intval($group_id)."'",
									"PERMISSION"	=> "'".intval($perm)."'"
									);
								$DB->Insert("b_form_2_group", $arFields_i, $err_mess.__LINE__);
							}
						}
					}
				}
			}
			return $FORM_ID;
		}
		return false;
	}

	// копирует веб-форму

	/**
	* <p>Копирует <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">веб-форму</a> с ее <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопросами</a>, <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">полями</a> и <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статусами</a>. Возвращает ID новой веб-формы в случае положительного результата, в противном случае - "false".</p>
	*
	*
	* @param int $form_id  ID формы.</b
	*
	* @param string $check_rights = "Y" Флаг необходимости проверки прав текущего пользователя.
	* Возможны следующие значения: <ul> <li> <b>Y</b> - права необходимо
	* проверить; </li> <li> <b>N</b> - право не нужно проверять. </li> </ul> Для
	* копирования веб-формы необходимо право <b>[W] Полный доступ" на
	* модуль "Веб-формы</b><b>"</b>. <br>Параметр необязательный. По умолчанию
	* - "Y" (права необходимо проверить).
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $FORM_ID = 4;
	* // скопируем веб-форму
	* if ($NEW_FORM_ID=<b>CForm::Copy</b>($FORM_ID))
	* {
	*     echo "Веб-форма #4 успешно скопирована в новую веб-форму #".$NEW_FORM_ID;
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
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/copy.php">CFormField::Copy</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformanswer/copy.php">CFormAnswer::Copy</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformstatus/copy.php">CFormStatus::Copy</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/copy.php
	* @author Bitrix
	*/
	public static 	function Copy($ID, $CHECK_RIGHTS="Y")
	{
		global $DB, $APPLICATION, $strError;
		$err_mess = (CAllForm::err_mess())."<br>Function: Copy<br>Line: ";
		$ID = intval($ID);
		if ($CHECK_RIGHTS!="Y" || CForm::IsAdmin())
		{
			$rsForm = CForm::GetByID($ID);
			$arForm = $rsForm->Fetch();
			if (!is_set($arForm, "FORM_TEMPLATE")) $arForm["FORM_TEMPLATE"] = CForm::GetFormTemplateByID($ID);

			// символьный код формы
			while(true)
			{
				$SID = $arForm["SID"];
				if (strlen($SID) > 25) $SID = substr($SID, 0, 25);
				$SID .= "_".RandString(5);

				$strSql = "SELECT 'x' FROM b_form WHERE SID='".$DB->ForSql($SID,50)."'";
				$z = $DB->Query($strSql, false, $err_mess.__LINE__);
				if (!($zr = $z->Fetch())) break;
			}

			$arFields = array(
				"NAME"						=> $arForm["NAME"],
				"SID"						=> $SID,
				"C_SORT"					=> $arForm["C_SORT"],
				"FIRST_SITE_ID"				=> $arForm["FIRST_SITE_ID"],
				"BUTTON"					=> $arForm["BUTTON"],
				"USE_CAPTCHA"				=> $arForm["USE_CAPTCHA"],
				"DESCRIPTION"				=> $arForm["DESCRIPTION"],
				"DESCRIPTION_TYPE"			=> $arForm["DESCRIPTION_TYPE"],
				"SHOW_TEMPLATE"				=> $arForm["SHOW_TEMPLATE"],
				"FORM_TEMPLATE"				=> $arForm["FORM_TEMPLATE"],
				"USE_DEFAULT_TEMPLATE"		=> $arForm["USE_DEFAULT_TEMPLATE"],
				"SHOW_RESULT_TEMPLATE"		=> $arForm["SHOW_RESULT_TEMPLATE"],
				"PRINT_RESULT_TEMPLATE"		=> $arForm["PRINT_RESULT_TEMPLATE"],
				"EDIT_RESULT_TEMPLATE"		=> $arForm["EDIT_RESULT_TEMPLATE"],
				"FILTER_RESULT_TEMPLATE"	=> $arForm["FILTER_RESULT_TEMPLATE"],
				"TABLE_RESULT_TEMPLATE"		=> $arForm["TABLE_RESULT_TEMPLATE"],
				"STAT_EVENT1"				=> $arForm["STAT_EVENT1"],
				"STAT_EVENT2"				=> $SID,
				"STAT_EVENT3"				=> $arForm["STAT_EVENT3"],
				"arSITE"					=> CForm::GetSiteArray($ID)
				);
			// пункты меню
			$z = CForm::GetMenuList(array("FORM_ID"=>$ID), "N");
			while ($zr = $z->Fetch()) $arFields["arMENU"][$zr["LID"]] = $zr["MENU"];

			// права групп
			$w = CGroup::GetList($v1="dropdown", $v2="asc", Array("ADMIN"=>"N"), $v3);
			$arGroups = array();
			while ($wr=$w->Fetch()) $arGroups[] = $wr["ID"];
			if (is_array($arGroups))
			{
				foreach($arGroups as $gid)
					$arFields["arGROUP"][$gid] = CForm::GetPermission($ID, array($gid), "Y");
			}

			// картинка
			if (intval($arForm["IMAGE_ID"])>0)
			{
				$arIMAGE = CFile::MakeFileArray(CFile::CopyFile($arForm["IMAGE_ID"]));
				$arIMAGE["MODULE_ID"] = "form";
				$arFields["arIMAGE"] = $arIMAGE;
			}

			$NEW_ID = CForm::Set($arFields, 0);

			if (intval($NEW_ID)>0)
			{
				// статусы
				$rsStatus = CFormStatus::GetList($ID, $by, $order, array(), $is_filtered);
				while ($arStatus = $rsStatus->Fetch()) CFormStatus::Copy($arStatus["ID"], "N", $NEW_ID);

				// вопросы/поля
				$rsField = CFormField::GetList($ID, "ALL", $by, $order, array(), $is_filtered);
				while ($arField = $rsField->Fetch())
				{
					CFormField::Copy($arField["ID"], "N", $NEW_ID);
				}
			}
			return $NEW_ID;
		}
		else $strError .= GetMessage("FORM_ERROR_ACCESS_DENIED")."<br>";
		return false;
	}

	// delete web-form

	/**
	* <p>Удаляет <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">веб-форму</a> со всеми ее <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результатами</a>. Возвращает "true" в случае положительного результата, и "false" - в противном случае.</p>
	*
	*
	* @param int $form_id  ID веб-формы.</bod
	*
	* @param string $check_rights = "Y" Флаг необходимости проверки прав текущего пользователя.
	* Возможны следующие значения: <ul> <li> <b>Y</b> - права необходимо
	* проверить; </li> <li> <b>N</b> - право не нужно проверять. </li> </ul> Для
	* удаления веб-формы необходимо иметь право <b>[W] Полный доступ на
	* модуль "Веб-формы"</b>. <br>Параметр необязательный. По умолчанию - "Y"
	* (права необходимо проверить).
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $FORM_ID = 4;
	* // удалим веб-форму
	* if (<b>CForm::Delete</b>($FORM_ID))
	* {
	*     echo "Веб-форма #4 удалена.";
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
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/delete.php">CFormField::Delete</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformanswer/delete.php">CFormAnswer::Delete</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformstatus/delete.php">CFormStatus::Delete</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/delete.php">CFormResult::Delete</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/delete.php
	* @author Bitrix
	*/
	public static 	function Delete($ID, $CHECK_RIGHTS="Y")
	{
		global $DB, $strError;
		$err_mess = (CAllForm::err_mess())."<br>Function: Delete<br>Line: ";
		$ID = intval($ID);

		if ($CHECK_RIGHTS!="Y" || CForm::IsAdmin())
		{
			// delete form results
			if (CForm::Reset($ID, "N"))
			{
				// delete temporary template
				$tmp_filename = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/tmp/form/form_".$ID.".php";
				if (file_exists($tmp_filename)) @unlink($tmp_filename);

				// delete form statuses
				$rsStatuses = CFormStatus::GetList($ID, $by, $order, $arFilter, $is_filtered);
				while ($arStatus = $rsStatuses->Fetch()) CFormStatus::Delete($arStatus["ID"], "N");

				// delete from fields & questions
				$rsFields = CFormField::GetList($ID, "ALL", $by, $order, array(), $is_filtered);
				while ($arField = $rsFields->Fetch()) CFormField::Delete($arField["ID"], "N");

				// delete form image
				$strSql = "SELECT IMAGE_ID FROM b_form WHERE ID='$ID' and IMAGE_ID>0";
				$z = $DB->Query($strSql, false, $err_mess.__LINE__);
				while ($zr = $z->Fetch()) CFile::Delete($zr["IMAGE_ID"]);

				// delete mail event type and mail templates, assigned to the current form
				$q = CForm::GetByID($ID);
				$qr = $q->Fetch();
				if (strlen(trim($qr["MAIL_EVENT_TYPE"]))>0)
				{
					// delete mail templates
					$em = new CEventMessage;
					$e = $em->GetList($by="id",$order="desc",array("EVENT_NAME"=>$qr["MAIL_EVENT_TYPE"], "EVENT_NAME_EXACT_MATCH" => "Y"));
					while ($er=$e->Fetch()) $em->Delete($er["ID"]);

					// delete mail event type
					$et = new CEventType;
					$et->Delete($qr["MAIL_EVENT_TYPE"]);
				}

				// delete site assignment
				$DB->Query("DELETE FROM b_form_2_site WHERE FORM_ID='$ID'", false, $err_mess.__LINE__);

				// delete mail templates assignment
				$DB->Query("DELETE FROM b_form_2_mail_template WHERE FORM_ID='$ID'", false, $err_mess.__LINE__);

				// delete form menu
				$DB->Query("DELETE FROM b_form_menu WHERE FORM_ID='$ID'", false, $err_mess.__LINE__);

				// delete from rights
				$DB->Query("DELETE FROM b_form_2_group WHERE FORM_ID='$ID'", false, $err_mess.__LINE__);

				// and finally delete form
				$DB->Query("DELETE FROM b_form WHERE ID='$ID'", false, $err_mess.__LINE__);

				return true;
			}
		}
		else $strError .= GetMessage("FORM_ERROR_ACCESS_DENIED")."<br>";
		return false;
	}

	// удаляем результаты формы

	/**
	* <p>Удаляет все <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результаты</a> <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">веб-формы</a>. Возвращает "true" в случае положительного результата, и "false" - в противном случае.</p>
	*
	*
	* @param int $form_id  ID веб-формы.</bod
	*
	* @param string $check_rights = "Y" Флаг необходимости проверки <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#permissions">прав</a> текущего
	* пользователя. Возможны следующие значения: <ul> <li> <b>Y</b> - права
	* необходимо проверить; </li> <li> <b>N</b> - право не нужно проверять. </li>
	* </ul> Для удаления всех результатов веб-формы необходимо иметь <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#permissions#form">право</a> <b>[30] Полный
	* доступ</b> на форму, указанную в параметре <i>form_id</i>.<br><br>Параметр
	* необязательный. По умолчанию - "Y" (права необходимо проверить).
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $FORM_ID = 4;
	* // удалим результаты веб-формы
	* if (<b>CForm::Reset</b>($FORM_ID))
	* {
	*     echo "Результаты веб-формы #4 удалены.";
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
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/reset.php">CFormField::Reset</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/reset.php">CFormResult::Reset</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/reset.php
	* @author Bitrix
	*/
	public static 	function Reset($ID, $CHECK_RIGHTS="Y")
	{
		global $DB, $strError;
		$err_mess = (CAllForm::err_mess())."<br>Function: Reset<br>Line: ";
		$ID = intval($ID);

		$F_RIGHT = ($CHECK_RIGHTS!="Y") ? 30 : CForm::GetPermission($ID);
		if ($F_RIGHT>=30)
		{
			// обнуляем поля формы
			$rsFields = CFormField::GetList($ID, "ALL", $by, $order, array(), $is_filtered);
			while ($arField = $rsFields->Fetch()) CFormField::Reset($arField["ID"], "N");

			// удаляем результаты данной формы
			$DB->Query("DELETE FROM b_form_result WHERE FORM_ID='$ID'", false, $err_mess.__LINE__);

			return true;
		}
		else $strError .= GetMessage("FORM_ERROR_ACCESS_DENIED")."<br>";

		return false;
	}

	// создает тип почтового события и шаблон на языке формы

	/**
	* <p>Создает или обновляет тип почтового события для <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">веб-формы</a>. При необходимости могут быть созданы почтовые шаблоны. Метод возвращает массив идентификаторов новых почтовых шаблонов, если они были созданы.</p> <p class="note"><b>Примечание</b><br>При создании нового типа почтового события, символьный идентификатор этого типа задается в виде <b>FORM_FILLING_</b><i>символьный ID веб-формы</i>.</p>
	*
	*
	* @param int $form_id  ID веб-формы, для которой необходимо создать или обновить тип
	* почтового события.
	*
	* @param string $add_template = "Y" Если значение равно "Y", то будут созданы почтовые шаблоны для
	* обновленного или вновь созданного типа почтового
	* события.<br><br>Параметр необязательный. По умолчанию - "Y" (создать
	* почтовые шаблоны).
	*
	* @param string $old_form_sid = "" Если в данном параметре будет задан символьный идентификатор
	* веб-формы, то все почтовые шаблоны, принадлежащие этой веб-форме,
	* будут приписаны к вновь созданному типу почтового события.
	* Данный параметр используется, как правило, при редактировании
	* веб-формы в момент смены символьного
	* идентификатора.<br><br>Параметр необязательный. По умолчанию - "" (не
	* приписывать почтовые шаблоны к новому типу почтового события).
	*
	* @return array 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // добавляем для веб-формы новый типа почтового события
	* // при этом создаем почтовые шаблоны
	* 
	* $arTemplates = <b>CForm::SetMailTemplate</b>($FORM_ID);
	* 
	* // приписываем вновь созданные почтовые шаблоны данной веб-форме
	* 
	* CForm::Set(array("arMAIL_TEMPLATE" = $arTemplates), $FORM_ID);
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/general/mailevents.php">Почтовая система</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/set.php">CForm::Set</a> <br> </li> </ul> <a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/setmailtemplate.php
	* @author Bitrix
	*/
	public static 	function SetMailTemplate($WEB_FORM_ID, $ADD_NEW_TEMPLATE="Y", $old_SID="", $bReturnFullInfo = false)
	{
		global $DB, $MESS, $strError;
		$err_mess = (CAllForm::err_mess())."<br>Function: SetMailTemplates<br>Line: ";
		$arrReturn = array();
		$WEB_FORM_ID = intval($WEB_FORM_ID);
		$q = CForm::GetByID($WEB_FORM_ID);
		if ($arrForm = $q->Fetch())
		{
			$MAIL_EVENT_TYPE = "FORM_FILLING_".$arrForm["SID"];
			if (strlen($old_SID)>0) $old_MAIL_EVENT_TYPE = "FORM_FILLING_".$old_SID;

			$et = new CEventType;
			$em = new CEventMessage;

			if (strlen($MAIL_EVENT_TYPE)>0)
				$et->Delete($MAIL_EVENT_TYPE);

			$z = CLanguage::GetList($v1, $v2);
			$OLD_MESS = $MESS;
			while ($arLang = $z->Fetch())
			{
				IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/form/admin/form_mail.php", $arLang["LID"]);

				$str = "";
				$str .= "#RS_FORM_ID# - ".GetMessage("FORM_L_FORM_ID")."\n";
				$str .= "#RS_FORM_NAME# - ".GetMessage("FORM_L_NAME")."\n";
				$str .= "#RS_FORM_SID# - ".GetMessage("FORM_L_SID")."\n";
				$str .= "#RS_RESULT_ID# - ".GetMessage("FORM_L_RESULT_ID")."\n";
				$str .= "#RS_DATE_CREATE# - ".GetMessage("FORM_L_DATE_CREATE")."\n";
				$str .= "#RS_USER_ID# - ".GetMessage("FORM_L_USER_ID")."\n";
				$str .= "#RS_USER_EMAIL# - ".GetMessage("FORM_L_USER_EMAIL")."\n";
				$str .= "#RS_USER_NAME# - ".GetMessage("FORM_L_USER_NAME")."\n";
				$str .= "#RS_USER_AUTH# - ".GetMessage("FORM_L_USER_AUTH")."\n";
				$str .= "#RS_STAT_GUEST_ID# - ".GetMessage("FORM_L_STAT_GUEST_ID")."\n";
				$str .= "#RS_STAT_SESSION_ID# - ".GetMessage("FORM_L_STAT_SESSION_ID")."\n";

				$strFIELDS = "";
				$w = CFormField::GetList($WEB_FORM_ID,"ALL", $by, $order, array("ACTIVE" => "Y"), $is_filtered);
				while ($wr=$w->Fetch())
				{
					if (strlen($wr["RESULTS_TABLE_TITLE"])>0)
					{
						$FIELD_TITLE = $wr["RESULTS_TABLE_TITLE"];
					}
					elseif (strlen($wr["TITLE"])>0)
					{
						$FIELD_TITLE = $wr["TITLE_TYPE"]=="html" ? htmlspecialcharsback(strip_tags($wr["TITLE"])) : $wr["TITLE"];
					}
					else
					{
						$FIELD_TITLE = TrimEx($wr["FILTER_TITLE"],":");
					}

					$str .= "#".$wr["SID"]."# - ".$FIELD_TITLE."\n";
					$str .= "#".$wr["SID"]."_RAW# - ".$FIELD_TITLE." (".GetMessage('FORM_L_RAW').")\n";
					$strFIELDS .= $FIELD_TITLE."\n*******************************\n#".$wr["SID"]."#\n\n";
				}

				$et->Add(
						Array(
						"LID"			=> $arLang["LID"],
						"EVENT_NAME"	=> $MAIL_EVENT_TYPE,
						"NAME"			=> GetMessage("FORM_FILLING")." \"".$arrForm["SID"]."\"",
						"DESCRIPTION"	=> $str
						)
					);
			}
			// задаем новый тип события для старых шаблонов
			if (strlen($old_MAIL_EVENT_TYPE)>0 && $old_MAIL_EVENT_TYPE!=$MAIL_EVENT_TYPE)
			{
				$e = $em->GetList($by="id",$order="desc",array("EVENT_NAME"=>$old_MAIL_EVENT_TYPE));
				while ($er=$e->Fetch())
				{
					$em->Update($er["ID"],array("EVENT_NAME"=>$MAIL_EVENT_TYPE));
				}
				if (strlen($old_MAIL_EVENT_TYPE)>0)
					$et->Delete($old_MAIL_EVENT_TYPE);
			}

			if ($ADD_NEW_TEMPLATE=="Y")
			{
				$z = CSite::GetList($v1, $v2);
				while ($arSite = $z->Fetch()) $arrSiteLang[$arSite["ID"]] = $arSite["LANGUAGE_ID"];

				$arrFormSite = CForm::GetSiteArray($WEB_FORM_ID);
				if (is_array($arrFormSite) && count($arrFormSite)>0)
				{
					foreach($arrFormSite as $sid)
					{
						IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/form/admin/form_mail.php", $arrSiteLang[$sid]);

						$SUBJECT = "#SERVER_NAME#: ".GetMessage("FORM_FILLING_S")." [#RS_FORM_ID#] #RS_FORM_NAME#";
						$MESSAGE = "#SERVER_NAME#

".GetMessage("FORM_FILLING").": [#RS_FORM_ID#] #RS_FORM_NAME#
-------------------------------------------------------

".GetMessage("FORM_DATE_CREATE")."#RS_DATE_CREATE#
".GetMessage("FORM_RESULT_ID")."#RS_RESULT_ID#
".GetMessage("FORM_USER")."[#RS_USER_ID#] #RS_USER_NAME# #RS_USER_AUTH#
".GetMessage("FORM_STAT_GUEST_ID")."#RS_STAT_GUEST_ID#
".GetMessage("FORM_STAT_SESSION_ID")."#RS_STAT_SESSION_ID#


$strFIELDS
".GetMessage("FORM_VIEW")."
http://#SERVER_NAME#/bitrix/admin/form_result_view.php?lang=".$arrSiteLang[$sid]."&WEB_FORM_ID=#RS_FORM_ID#&RESULT_ID=#RS_RESULT_ID#

-------------------------------------------------------
".GetMessage("FORM_GENERATED_AUTOMATICALLY")."
						";
						// добавляем новый шаблон
						$arFields = Array(
							"ACTIVE"		=> "Y",
							"EVENT_NAME"	=> $MAIL_EVENT_TYPE,
							"LID"			=> $sid,
							"EMAIL_FROM"	=> "#DEFAULT_EMAIL_FROM#",
							"EMAIL_TO"		=> "#DEFAULT_EMAIL_FROM#",
							"SUBJECT"		=> $SUBJECT,
							"MESSAGE"		=> $MESSAGE,
							"BODY_TYPE"		=> "text"
							);
						$TEMPLATE_ID = $em->Add($arFields);
						if ($bReturnFullInfo)
							$arrReturn[] = array(
								'ID' => $TEMPLATE_ID,
								'FIELDS' => $arFields,
							);
						else
							$arrReturn[] = $TEMPLATE_ID;
					}
				}
			}
			$MESS = $OLD_MESS;
		}
		return $arrReturn;
	}


	/**
	* <p>Возвращает <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/index.php">параметры</a> <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#form">веб-формы</a> в виде объекта класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p>
	*
	*
	* @param string $form_sid  
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $FORM_SID = "ANKETA";
	* $rsForm = <b>CForm::GetBySID</b>($FORM_SID);
	* $arForm = $rsForm-&gt;Fetch();
	* echo "&lt;pre&gt;"; print_r($arForm); echo "&lt;/pre";
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/index.php">Поля CForm</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getbyid.php">CForm::GetByID</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getlist.php">CForm::GetList</a> </li> </ul></b<a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cform/getbysid.php
	* @author Bitrix
	*/
	public static 	function GetBySID($SID)
	{ return CForm::GetByID($SID, "Y"); }

	/**
	 * Check whether current field is on template
	 *
	 * @param string $FIELD_SID
	 * @param string $tpl
	 * @return bool
	 */
	funpublic static ction isFieldInTemplate($FIELD_SID, $tpl)
	{
		$check_str1 = '$FORM->ShowInput(\''.$FIELD_SID.'\')';
		$check_str2 = '$FORM->ShowInput("'.$FIELD_SID.'")';

		return !((strpos($tpl, $check_str1) === false) && (strpos($tpl, $check_str2) === false));

	}

		/**
	 * Check whether CAPTCHA Fields is on template
	 *
	 * @param string $FIELD_SID
	 * @param string $tpl
	 * @return bool
	 */
public static 	function isCAPTCHAInTemplate($tpl)
	{
		$check_str = '$FORM->ShowCaptcha';

		return strpos($tpl, $check_str) !== false;

	}

public static 	function GetByID_admin($WEB_FORM_ID, $current_section = false)
	{
		$WEB_FORM_ID = intval($WEB_FORM_ID);
		if ($WEB_FORM_ID <= 0)
			return false;

		$dbForm = CForm::GetByID($WEB_FORM_ID);
		if ($arForm = $dbForm->Fetch())
		{
			if (!$current_section)
			{
				$current_script = basename($GLOBALS['APPLICATION']->GetCurPage());

				switch ($current_script)
				{
					case 'form_edit.php':
						$current_section = 'form';
					break;

					case 'form_field_edit.php':
					case 'form_field_edit_simple.php':
					case 'form_field_list.php':

						if (!$bSimple && $_GET['additional'] == 'Y')
							$current_section = 'field';
						else
							$current_section = 'question';

					break;

					case 'form_result_edit.php':
					case 'form_result_list.php':
					case 'form_result_view.php':
						$current_section = 'result';
					break;

					case 'form_status_edit.php':
					case 'form_status_list.php':
						$current_section = 'status';
					break;
				}
			}

			$bSimple = COption::GetOptionString("form", "SIMPLE", "Y") == "Y";

			$arForm['ADMIN_MENU'] = array();

			$arForm['ADMIN_MENU'][] = array(
				"ICON"	=> $current_section == 'form' ? 'btn_active' : '',
				"TEXT"	=> GetMessage("FORM_MENU_EDIT"),
				"LINK"	=> "/bitrix/admin/form_edit.php?lang=".LANGUAGE_ID."&ID=".$WEB_FORM_ID,
				"TITLE"	=> htmlspecialcharsbx(str_replace("#NAME#", $arForm["NAME"], GetMessage("FORM_MENU_EDIT_TITLE")))
			);

			$arForm['ADMIN_MENU'][] = array(
				"ICON"	=> $current_section == 'result' ? 'btn_active' : '',
				"TEXT"	=> GetMessage("FORM_MENU_RESULTS")
					." (".CFormResult::GetCount($WEB_FORM_ID).")",
				"LINK"	=> "/bitrix/admin/form_result_list.php?lang=".LANGUAGE_ID."&WEB_FORM_ID=".$WEB_FORM_ID,
				"TITLE"	=> htmlspecialcharsbx(str_replace("#NAME#", $arForm["NAME"], GetMessage("FORM_MENU_RESULTS_TITLE")))
			);

			$arForm['ADMIN_MENU'][] = array(
				"ICON"	=> $current_section == 'question' ? 'btn_active' : '',
				"TEXT"	=> GetMessage("FORM_MENU_QUESTIONS")
					." (".($bSimple ? $arForm["QUESTIONS"] + $arForm["C_FIELDS"] : $arForm["QUESTIONS"]).")",
				"LINK"	=> "/bitrix/admin/form_field_list.php?lang=".LANGUAGE_ID."&WEB_FORM_ID=".$WEB_FORM_ID,
				"TITLE"	=> htmlspecialcharsbx(str_replace("#NAME#", $arForm["NAME"], GetMessage("FORM_MENU_QUESTIONS_TITLE")))
			);

			if (!$bSimple)
			{
				$arForm['ADMIN_MENU'][] = array(
					"ICON"	=> $current_section == 'field' ? 'btn_active' : '',
					"TEXT"	=> GetMessage("FORM_MENU_FIELDS")
						." (".$arForm["C_FIELDS"].")",
					"LINK"	=> "/bitrix/admin/form_field_list.php?lang=".LANGUAGE_ID."&WEB_FORM_ID=".$WEB_FORM_ID."&additional=Y",
					"TITLE"	=> htmlspecialcharsbx(str_replace("#NAME#", $arForm["NAME"], GetMessage("FORM_MENU_FIELDS_TITLE")))
				);

				$arForm['ADMIN_MENU'][] = array(
					"ICON"	=> $current_section == 'status' ? 'btn_active' : '',
					"TEXT"	=> GetMessage("FORM_MENU_STATUSES")
						." (".$arForm["STATUSES"].")",
					"LINK"	=> "/bitrix/admin/form_status_list.php?lang=".LANGUAGE_ID."&WEB_FORM_ID=".$WEB_FORM_ID,
					"TITLE"	=> htmlspecialcharsbx(str_replace("#NAME#", $arForm["NAME"], GetMessage("FORM_MENU_STATUSES_TITLE")))
				);
			}

			return $arForm;
		}

		return false;
	}
}
?>