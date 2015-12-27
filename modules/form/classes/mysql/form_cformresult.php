<?

/***************************************
		Результат веб-формы
***************************************/


/**
 * <b>CFormResult</b> - класс для работы с <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результатами</a>. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/index.php
 * @author Bitrix
 */
class CFormResult extends CAllFormResult
{
public static 	function err_mess()
	{
		$module_id = "form";
		@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/install/version.php");
		return "<br>Module: ".$module_id." (".$arModuleVersion["VERSION"].")<br>Class: CFormResult<br>File: ".__FILE__;
	}

	// список результатов

	/**
	* <p>Возвращает список <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результатов</a> веб-формы в виде объекта класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p> <p class="note"><b>Примечание</b> <br> Возвращаемый список содержит только <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/index.php">поля результата</a>. Значения ответов и полей можно получить с помощью метода <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getresultanswerarray.php">CForm::GetResultAnswerArray</a> или <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/getdatabyid.php">CFormResult::GetDataByID</a>.</p>
	*
	*
	* @param int $form_id  ID веб-формы.</bod
	*
	* @param string &$by = "s_timestamp" Ссылка на переменную с полем для сортировки; может принимать
	* значения: <ul> <li> <b>s_id</b> - ID результата; </li> <li> <b>s_date_create</b> - дата
	* создания; </li> <li> <b>s_timestamp</b> - дата изменения (значение по
	* умолчанию); </li> <li> <b>s_user_id</b> - ID пользователя, создавшего результат;
	* </li> <li> <b>s_guest_id</b> - ID посетителя, создавшего результат; </li> <li>
	* <b>s_session_id</b> - ID сессии, в которой был создан результат; </li> <li>
	* <b>s_status</b> - ID статуса. </li> </ul>
	*
	* @param string &$order = "desc" Ссылка на переменную с порядком сортировки. Допустимы следующие
	* значения: <ul> <li> <b>desc</b> - по убыванию (значение по умолчанию); </li> <li>
	* <b>asc</b> - по возрастанию. </li> </ul>
	*
	* @param array $filter = array() Массив содержащий параметры фильтра. Необязательный параметр. В
	* массиве допустимы следующие ключи: <ul> <li> <b>ID</b>* - ID результата (по
	* умолчанию будет искаться точное совпадение); </li> <li> <b>ID_EXACT_MATCH</b> -
	* если значение равно "N", то при фильтрации по <b>ID</b> будет искаться
	* вхождение; </li> <li> <b>STATUS_ID</b>* - ID статуса (по умолчанию будет
	* искаться точное совпадение); </li> <li> <b>STATUS_ID_EXACT_MATCH</b> - если значение
	* равно "N", то при фильтрации по <b>STATUS_ID</b> будет искаться вхождение;
	* </li> <li> <b>TIMESTAMP_1</b> - левое значение интервала ("с") по дате изменения
	* (задается в формате даты текущего сайта); </li> <li> <b>TIMESTAMP_2</b> - правое
	* значение интервала ("по") по дате изменения (задается в формате
	* даты текущего сайта); </li> <li> <b>DATE_CREATE_1</b> - левое значение интервала
	* ("с") по дате создания (задается в формате даты текущего сайта); </li>
	* <li> <b>DATE_CREATE_2</b> - правое значение интервала ("по") по дате создания
	* (задается в формате даты текущего сайта); </li> <li> <b>TIME_CREATE_1</b> - левое
	* значение интервала ("с") по дате создания в полном формате (дата и
	* время); </li> <li> <b>TIME_CREATE_2</b> - правое значение интервала ("по") по дате
	* создания в полном формате (дата и время); </li> <li> <b>REGISTERED</b> - флаг
	* зарегистрированности автора результата; допустимы следующие
	* значения: <ul> <li> <b>Y</b> - автор был зарегистрирован как пользователь;
	* </li> <li> <b>N</b> - автор не был зарегистрирован как пользователь. </li> </ul>
	* </li> <li> <b>USER_AUTH</b> - флаг авторизованности автора результата;
	* допустимы следующие значения: <ul> <li> <b>Y</b> - автор был авторизован;
	* </li> <li> <b>N</b> - автор не был авторизован. </li> </ul> </li> <li> <b>USER_ID</b>* - ID
	* пользователя, создавшего результат (автор результата) (по
	* умолчанию будет искаться точное совпадение); </li> <li> <b>USER_ID_EXACT_MATCH</b>
	* - если значение равно "N", то при фильтрации по <b>USER_ID</b> будет
	* искаться вхождение; </li> <li> <b>GUEST_ID</b>* - ID посетителя создавшего
	* результат (автор результата) (по умолчанию будет искаться точное
	* совпадение); </li> <li> <b>GUEST_ID_EXACT_MATCH</b> - если значение равно "N", то при
	* фильтрации по <b>GUEST_ID</b> будет искаться вхождение; </li> <li> <b>SESSION_ID</b>*
	* - ID сессии в которой был создан результат (по умолчанию будет
	* искаться точное совпадение); </li> <li> <b>SESSION_ID_EXACT_MATCH</b> - если
	* значение равно "N", то при фильтрации по <b>SESSION_ID</b> будет искаться
	* вхождение; </li> <li> <b>FIELDS</b> - массив, содержащий параметры фильтра
	* для фильтрации <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результатов</a>
	* по значениям <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответов</a> и <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">полей</a> веб-формы. Каждый
	* элемент данного массива представляет из себя массив, описывающий
	* параметры фильтра по <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопросу</a> или <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">полю</a> веб-формы; ключами
	* подобного массива могут быть: <ul> <li> <b>SID</b> - символьный
	* идентификатор <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a> или
	* <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a> веб-формы; синоним -
	* <b>CODE</b>;</li> <li> <b>PARAMETER_NAME</b> - тип данных по которым фильтруем,
	* допустимы следующие значения: <ul> <li> <b>USER</b> - фильтруем по ответам
	* введенных авторами с клавиатуры (по умолчанию); </li> <li> <b>ANSWER_TEXT</b> -
	* фильтруем по параметру <font color="green">ANSWER_TEXT</font>; </li> <li> <b>ANSWER_VALUE</b> -
	* фильтруем по параметру <font color="red">ANSWER_VALUE.</font> </li> </ul> </li> <li> <b>VALUE</b> -
	* значение, по которому фильтруем (допускается <a
	* href="http://dev.1c-bitrix.ru/user_help/general/filter.php">сложная логика</a>); </li> <li>
	* <b>FILTER_TYPE</b> - тип фильтра, определяет, как интерпретировать данные
	* по которым фильтруем: <ul> <li> <b>integer</b> - означает, что данные, по
	* которым будет осуществляться фильтрация, считать числами
	* (используется только с <b>PARAMETER_NAME</b>=[USER|ANSWER_TEXT|ANSWER_VALUE]); </li> <li> <b>text</b>
	* - означает, что данные, по которым будет осуществляться
	* фильтрация, должны обрабатываться как текстовые поля
	* (используется только с <b>PARAMETER_NAME</b>=[USER|ANSWER_TEXT|ANSWER_VALUE]); </li> <li> <b>date</b>
	* - означает, что данные по которым будет осуществляться
	* фильтрация, должны обрабатываться как даты (используется только
	* с <b>PARAMETER_NAME</b>=USER); </li> <li> <b>answer_id</b> - означает, что фильтрация будет
	* производиться только по прямому совпадению с ID <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> (при этом PARAMETER_NAME не
	* имеет значения). </li> </ul> </li> <li> <b>PART</b> - если тип фильтра
	* <b>FILTER_TYPE</b>=[integer|date], то данное поле <b>должно</b>содержать одно из
	* трех значений: <ul> <li> <b>0</b> - прямое совпадение со значением; </li> <li>
	* <b>1</b> - левое значение интервала ("с"); </li> <li> <b>2</b> - правое значение
	* интервала ("по") </li> </ul> </li> <li> <b>EXACT_MATCH</b> - если <b>FILTER_TYPE</b>="text", то в
	* данном поле можно задать следующие значения: <ul> <li> <b>Y</b> - прямое
	* совпадение; </li> <li> <b>N</b> - будет искаться вхождение (по умолчанию).
	* </li> </ul> </li> </ul> </li> </ul>
	*
	* @param bool &$is_filtered  Ссылка на переменную хранящую флаг отфильтрованности
	* результирующего списка. Если значение равно "true", то список был
	* отфильтрован.
	*
	* @param string $check_rights = "Y" Флаг необходимости проверки прав текущего пользователя.
	* Возможны следующие значения: <ul> <li> <b>Y</b> - права необходимо
	* проверить; </li> <li> <b>N</b> - права не нужно проверять. </li> </ul> Для того
	* чтобы <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результат</a> попал в
	* результирующий список, необходимо обладать следующими <a
	* href="http://dev.1c-bitrix.ru/api_help/form/permissions.php">правами</a>: <ol> <li>На веб-форму
	* <i>form_id</i>: <br><br><b>[20] Работа со всеми результатами в соответствии с
	* их статусами</b> <br><br> или <br><br><b>[15] Работа со своим результатом в
	* соответствии с его статусом</b> - в этом случае результирующий
	* список будет состоять только из тех результатов создателем
	* которых является текущий пользователь. <br> </li> <li>На статус, в
	* котором находится результат, необходимо иметь право: <br><br><b>[VIEW]
	* просмотр</b> <br><br> или <br><br><b>[EDIT] редактирование</b> <br><br> или
	* <br><br><b>[DELETE] удаление</b> </li> </ol> Параметр необязательный. По
	* умолчанию - "Y" (права необходимо проверить).
	*
	* @param mixed $limit = false Максимальное количество результатов, которые войдут в
	* результирующий список. По умолчанию ограничивает выборку 5000
	* строками. <br><br> Параметр необязательный.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // ID веб-формы
	* $FORM_ID = 4;
	* 
	* // фильтр по полям результата
	* $arFilter = array(
	*     "ID"                   =&gt; "12",              // ID результата
	*     "ID_EXACT_MATCH"       =&gt; "N",               // вхождение
	*     "STATUS_ID"            =&gt; "9 | 10",          // статус
	*     "TIMESTAMP_1"          =&gt; "10.10.2003",      // изменен "с"
	*     "TIMESTAMP_2"          =&gt; "15.10.2003",      // изменен "до"
	*     "DATE_CREATE_1"        =&gt; "10.10.2003",      // создан "с"
	*     "DATE_CREATE_2"        =&gt; "12.10.2003",      // создан "до"
	*     "REGISTERED"           =&gt; "Y",               // был зарегистрирован
	*     "USER_AUTH"            =&gt; "N",               // не был авторизован
	*     "USER_ID"              =&gt; "45 | 35",         // пользователь-автор
	*     "USER_ID_EXACT_MATCH"  =&gt; "Y",               // точное совпадение
	*     "GUEST_ID"             =&gt; "4456 | 7768",     // посетитель-автор
	*     "SESSION_ID"           =&gt; "456456 | 778768", // сессия
	*     );
	* 
	* // фильтр по вопросам
	* $arFields = array();
	* 
	* $arFields[] = array(
	*     "CODE"              =&gt; "GAME_ID",       // код поля по которому фильтруем
	*     "FILTER_TYPE"       =&gt; "integer",       // фильтруем по числовому полю
	*     "PARAMETER_NAME"    =&gt; "USER",          // по значению введенному с клавиатуры
	*     "VALUE"             =&gt; $arGame["ID"],   // значение по которому фильтруем
	*     "PART"              =&gt; 0                // прямое совпадение со значением (не интервал)
	*     );
	* 
	* $arFields[] = array(
	*     "CODE"              =&gt; "GAME_NAME",     // код поля по которому фильтруем
	*     "FILTER_TYPE"       =&gt; "text",          // фильтруем по числовому полю
	*     "PARAMETER_NAME"    =&gt; "USER",          // фильтруем по введенному значению
	*     "VALUE"             =&gt; "Tetris",        // значение по которому фильтруем
	*     "EXACT_MATCH"       =&gt; "Y"              // ищем точное совпадение
	*     );
	* 
	* $arFields[] = array(
	*     "CODE"              =&gt; "GENRE_ID",      // код поля по которому фильтруем
	*     "FILTER_TYPE"       =&gt; "integer",       // фильтруем по числовому полю
	*     "PARAMETER_NAME"    =&gt; "ANSWER_VALUE",  // фильтруем по параметру ANSWER_VALUE
	*     "VALUE"             =&gt; "3",             // значение по которому фильтруем
	*     "PART"              =&gt; 1                // с
	*     );
	* 
	* $arFields[] = array(
	*     "CODE"              =&gt; "GENRE_ID",      // код поля по которому фильтруем
	*     "FILTER_TYPE"       =&gt; "integer",       // фильтруем по числовому полю
	*     "PARAMETER_NAME"    =&gt; "ANSWER_VALUE",  // фильтруем по параметру ANSWER_VALUE
	*     "VALUE"             =&gt; "6",             // значение по которому фильтруем
	*     "PART"              =&gt; 2                // по
	*     );
	* 
	* $arFilter["FIELDS"] = $arFields;
	* 
	* // выберем первые 10 результатов
	* $rsResults = <b>CFormResult::GetList</b>($FORM_ID, 
	*     ($by="s_timestamp"), 
	*     ($order="desc"), 
	*     $arFilter, 
	*     $is_filtered, 
	*     "Y", 
	*     10);
	* while ($arResult = $rsResults-&gt;Fetch())
	* {
	*     echo "&lt;pre&gt;"; print_r($arResult); echo "&lt;/pre&gt;";
	* }
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/index.php#field">Поля CFormResult</a>
	* </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/getdatabyid.php">CFormResult::GetDataByID</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getresultanswerarray.php">CForm::GetResultAnswerArray</a>
	* </li> </ul></b<a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/getlist.php
	* @author Bitrix
	*/
	public static 	function GetList($WEB_FORM_ID, &$by, &$order, $arFilter=Array(), &$is_filtered, $CHECK_RIGHTS="Y", $records_limit=false)
	{
		$err_mess = (CFormResult::err_mess())."<br>Function: GetList<br>Line: ";
		global $DB, $USER, $strError;

		$CHECK_RIGHTS = ($CHECK_RIGHTS=="Y") ? "Y" : "N";
		$WEB_FORM_ID = intval($WEB_FORM_ID);

		$F_RIGHT = CForm::GetPermission($WEB_FORM_ID);

		$USER_ID = intval($USER->GetID());
		$arSqlSearch = array();
		$arr["FIELDS"] = array();
		$strSqlSearch = "";
		if (is_array($arFilter))
		{
			$arFilter = CFormResult::PrepareFilter($WEB_FORM_ID, $arFilter);

			$z = CForm::GetByID($WEB_FORM_ID);
			$form = $z->Fetch();

			/***********************/

			$z = CFormField::GetList($WEB_FORM_ID, "", $v1, $v2, array(), $v3);

			while ($zr=$z->Fetch())
			{
				$arPARAMETER_NAME = array("ANSWER_TEXT", "ANSWER_VALUE", "USER");
				CFormField::GetFilterTypeList($arrUSER, $arrANSWER_TEXT, $arrANSWER_VALUE, $arrFIELD);
				foreach ($arPARAMETER_NAME as $PARAMETER_NAME)
				{
					switch ($PARAMETER_NAME)
					{
						case "ANSWER_TEXT":
							$arFILTER_TYPE = $arrANSWER_TEXT["reference_id"];
							break;
						case "ANSWER_VALUE":
							$arFILTER_TYPE = $arrANSWER_VALUE["reference_id"];
							break;
						case "USER":
							$arFILTER_TYPE = $arrUSER["reference_id"];
							break;
					}
					foreach ($arFILTER_TYPE as $FILTER_TYPE)
					{
						$arrUF = array();
						$arrUF["ID"] = $zr["ID"];
						$arrUF["PARAMETER_NAME"] = $PARAMETER_NAME;
						$arrUF["FILTER_TYPE"] = $FILTER_TYPE;
						$FID = $form["SID"]."_".$zr["SID"]."_".$PARAMETER_NAME."_".$FILTER_TYPE;
						if ($FILTER_TYPE=="date" || $FILTER_TYPE=="integer")
						{
							$arrUF["SIDE"] = "1";
							$arrFORM_FILTER[$FID."_1"] = $arrUF;
							$arrUF["SIDE"] = "2";
							$arrFORM_FILTER[$FID."_2"] = $arrUF;
							$arrUF["SIDE"] = "0";
							$arrFORM_FILTER[$FID."_0"] = $arrUF;
						}
						else $arrFORM_FILTER[$FID] = $arrUF;
					}
				}
			}
			if (is_array($arrFORM_FILTER)) $arrFORM_FILTER_KEYS = array_keys($arrFORM_FILTER);

			//echo "arFilter:<pre>"; print_r($arFilter); echo "</pre>";
			//echo "arrFORM_FILTER:<pre>"; print_r($arrFORM_FILTER); echo "</pre>";
			//echo "arrFORM_FILTER_KEYS:<pre>"; print_r($arrFORM_FILTER_KEYS); echo "</pre>";

			$t = 0;
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
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("R.ID", $val, $match);
						break;
					case "STATUS":
						$arSqlSearch[] = "R.STATUS_ID='".intval($val)."'";
						break;
					case "STATUS_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("R.STATUS_ID", $val, $match);
						break;
					case "TIMESTAMP_1":
						$arSqlSearch[] = "R.TIMESTAMP_X>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "TIMESTAMP_2":
						$arSqlSearch[] = "R.TIMESTAMP_X<".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
						break;
					case "DATE_CREATE_1":
						$arSqlSearch[] = "R.DATE_CREATE>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE_CREATE_2":
						$arSqlSearch[] = "R.DATE_CREATE<".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
						break;
					case "TIME_CREATE_1":
						$arSqlSearch[] = "R.DATE_CREATE>=".$DB->CharToDateFunction($val, "FULL");
						break;
					case "TIME_CREATE_2":
						$arSqlSearch[] = "R.DATE_CREATE<".$DB->CharToDateFunction($val, "FULL");
						break;
					case "REGISTERED":
						$arSqlSearch[] = ($val=="Y") ? "R.USER_ID>0" : "(R.USER_ID<=0 or R.USER_ID is null)";
						break;
					case "USER_AUTH":
						$arSqlSearch[] = ($val=="Y") ? "(R.USER_AUTH='Y' and R.USER_ID>0)" : "(R.USER_AUTH='N' and R.USER_ID>0)";
						break;
					case "USER_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("R.USER_ID", $val, $match);
						break;
					case "GUEST_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("R.STAT_GUEST_ID", $val, $match);
						break;
					case "SESSION_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("R.STAT_SESSION_ID", $val, $match);
						break;
					case "SENT_TO_CRM":
						$arSqlSearch[] = GetFilterQuery("R.SENT_TO_CRM", $val, "Y");
						break;
					default:
						if (is_array($arrFORM_FILTER))
						{
							$key = $filter_keys[$i];
							if (in_array($key, $arrFORM_FILTER_KEYS))
							{
								$arrF = $arrFORM_FILTER[$key];
								if (is_array($arr["FIELDS"]) && !in_array($arrF["ID"],$arr["FIELDS"]))
								{
									$t++;
									$A = "A".$t;
									$arr["TABLES"][] = "b_form_result_answer ".$A;
									$arr["WHERE"][] = "(".$A.".RESULT_ID=R.ID and ".$A.".FIELD_ID='".$arrF["ID"]."')";
									$arr["FIELDS"][] = $arrF["ID"];
								}
								switch(strtoupper($arrF["FILTER_TYPE"]))
								{
									case "EXIST":

										if ($arrF["PARAMETER_NAME"]=="ANSWER_TEXT")
											$arSqlSearch[] = "length(".$A.".ANSWER_TEXT)+0>0";

										elseif ($arrF["PARAMETER_NAME"]=="ANSWER_VALUE")
											$arSqlSearch[] = "length(".$A.".ANSWER_VALUE)+0>0";

										elseif ($arrF["PARAMETER_NAME"]=="USER")
											$arSqlSearch[] = "length(".$A.".USER_TEXT)+0>0";

										break;
									case "TEXT":
										$match = ($arFilter[$key."_exact_match"]=="Y") ? "N" : "Y";
										$sql = "";

										if ($arrF["PARAMETER_NAME"]=="ANSWER_TEXT")
											$sql = GetFilterQuery($A.".ANSWER_TEXT_SEARCH", ToUpper($val), $match);

										elseif ($arrF["PARAMETER_NAME"]=="ANSWER_VALUE")
											$sql = GetFilterQuery($A.".ANSWER_VALUE_SEARCH", ToUpper($val), $match);

										elseif ($arrF["PARAMETER_NAME"]=="USER")
											$sql = GetFilterQuery($A.".USER_TEXT_SEARCH", ToUpper($val), $match);

										if ($sql!=="0" && strlen(trim($sql))>0) $arSqlSearch[] = $sql;
										break;
									case "DROPDOWN":
									case "ANSWER_ID":
											$arSqlSearch[] = $A.".ANSWER_ID=".intval($val);
										break;
									case "DATE":
										if ($arrF["PARAMETER_NAME"]=="USER")
										{
											if (CheckDateTime($val))
											{
												if ($arrF["SIDE"]=="1")
													$arSqlSearch[] = $A.".USER_DATE>=".$DB->CharToDateFunction($val, "SHORT");
												elseif ($arrF["SIDE"]=="2")
													$arSqlSearch[] = $A.".USER_DATE<".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
												elseif ($arrF["SIDE"]=="0")
													$arSqlSearch[] = $A.".USER_DATE=".$DB->CharToDateFunction($val);
											}
										}
										break;
									case "INTEGER":
										if ($arrF["PARAMETER_NAME"]=="USER")
										{
											if ($arrF["SIDE"]=="1")
												$arSqlSearch[] = $A.".USER_TEXT+0>=".intval($val);
											elseif ($arrF["SIDE"]=="2")
												$arSqlSearch[] = $A.".USER_TEXT+0<=".intval($val);
											elseif ($arrF["SIDE"]=="0")
												$arSqlSearch[] = $A.".USER_TEXT='".intval($val)."'";
										}
										elseif ($arrF["PARAMETER_NAME"]=="ANSWER_TEXT")
										{
											if ($arrF["SIDE"]=="1")
												$arSqlSearch[] = $A.".ANSWER_TEXT+0>=".intval($val);
											elseif ($arrF["SIDE"]=="2")
												$arSqlSearch[] = $A.".ANSWER_TEXT+0<=".intval($val);
											elseif ($arrF["SIDE"]=="0")
												$arSqlSearch[] = $A.".ANSWER_TEXT='".intval($val)."'";
										}
										elseif ($arrF["PARAMETER_NAME"]=="ANSWER_VALUE")
										{
											if ($arrF["SIDE"]=="1")
												$arSqlSearch[] = $A.".ANSWER_VALUE+0>=".intval($val);
											elseif ($arrF["SIDE"]=="2")
												$arSqlSearch[] = $A.".ANSWER_VALUE+0<=".intval($val);
											elseif ($arrF["SIDE"]=="0")
												$arSqlSearch[] = $A.".ANSWER_VALUE='".intval($val)."'";
										}
										break;
								}
							}
						}
				}
			}
		}
		if ($by == "s_id")				$strSqlOrder = "ORDER BY R.ID";
		elseif ($by == "s_date_create")	$strSqlOrder = "ORDER BY R.DATE_CREATE";
		elseif ($by == "s_timestamp")	$strSqlOrder = "ORDER BY R.TIMESTAMP_X";
		elseif ($by == "s_user_id")		$strSqlOrder = "ORDER BY R.USER_ID";
		elseif ($by == "s_guest_id")	$strSqlOrder = "ORDER BY R.STAT_GUEST_ID";
		elseif ($by == "s_session_id")	$strSqlOrder = "ORDER BY R.STAT_SESSION_ID";
		elseif ($by == "s_status")		$strSqlOrder = "ORDER BY R.STATUS_ID";
		elseif ($by == "s_sent_to_crm")	$strSqlOrder = "ORDER BY R.SENT_TO_CRM";
		else
		{
			$by = "s_timestamp";
			$strSqlOrder = "ORDER BY R.TIMESTAMP_X";
		}
		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		if (is_array($arr["TABLES"]))
			$str1 = implode(",\n				",$arr["TABLES"]);
		if (is_array($arr["WHERE"]))
			$str2 = implode("\n			and ",$arr["WHERE"]);
		if (strlen($str1)>0) $str1 = ",\n				".$str1;
		if (strlen($str2)>0) $str2 = "\n			and ".$str2;

		if ($records_limit===false)
		{
			$records_limit = "LIMIT ".intval(COption::GetOptionString("form","RECORDS_LIMIT"));
		}
		else
		{
			$records_limit = intval($records_limit);
			if ($records_limit>0)
			{
				$records_limit = "LIMIT ".$records_limit;
			}
		}

		//this hack is for mysql <3.23. we no longer support that dino.
		//$DB->Query("SET SQL_BIG_TABLES=1", false, $err_mess.__LINE__);
		if ($CHECK_RIGHTS!="Y" || $F_RIGHT >= 30 || CForm::IsAdmin())
		{
			$strSql = "
				SELECT
					R.ID, R.USER_ID, R.USER_AUTH, R.STAT_GUEST_ID, R.STAT_SESSION_ID, R.STATUS_ID, R.SENT_TO_CRM,
					".$DB->DateToCharFunction("R.DATE_CREATE")."	DATE_CREATE,
					".$DB->DateToCharFunction("R.TIMESTAMP_X")."	TIMESTAMP_X,
					S.TITLE				STATUS_TITLE,
					S.CSS				STATUS_CSS
				FROM
					b_form_result R,
					b_form_status S
					$str1
				WHERE
				$strSqlSearch
				$str2
				and R.FORM_ID = '$WEB_FORM_ID'
				and S.ID = R.STATUS_ID
				GROUP BY
					R.ID, R.USER_ID, R.USER_AUTH, R.STAT_GUEST_ID, R.STAT_SESSION_ID, R.DATE_CREATE, R.STATUS_ID, R.SENT_TO_CRM
				$strSqlOrder
				$records_limit
				";
			$res = $DB->Query($strSql, false, $err_mess.__LINE__);
			//echo '<pre>'.$strSql.'</pre>';
		}
		elseif ($F_RIGHT>=15)
		{
			$arGroups = $USER->GetUserGroupArray();
			if (!is_array($arGroups)) $arGroups[] = 2;
			if (is_array($arGroups) && count($arGroups)>0) $groups = implode(",",$arGroups);
			if ($F_RIGHT<20) $str3 = "and ifnull(R.USER_ID,0) = $USER_ID";

			$strSql = "
				SELECT
					R.ID, R.USER_ID, R.USER_AUTH, R.STAT_GUEST_ID, R.STAT_SESSION_ID, R.STATUS_ID, R.SENT_TO_CRM,
					".$DB->DateToCharFunction("R.DATE_CREATE")."	DATE_CREATE,
					".$DB->DateToCharFunction("R.TIMESTAMP_X")."	TIMESTAMP_X,
					S.TITLE				STATUS_TITLE,
					S.CSS				STATUS_CSS
				FROM
					b_form_result R,
					b_form_status S,
					b_form_status_2_group G$str1
				WHERE
				$strSqlSearch
				$str2
				$str3
				and R.FORM_ID = '$WEB_FORM_ID'
				and S.ID = R.STATUS_ID
				and G.STATUS_ID = S.ID
				and (
					(G.GROUP_ID in ($groups)) or
					(G.GROUP_ID in ($groups,0) and ifnull(R.USER_ID,0) = $USER_ID and $USER_ID>0)
					)
				and G.PERMISSION in ('VIEW', 'EDIT', 'DELETE')
				GROUP BY
					R.ID, R.USER_ID, R.USER_AUTH, R.STAT_GUEST_ID,
					R.STAT_SESSION_ID, R.SENT_TO_CRM, R.DATE_CREATE, R.STATUS_ID, R.SENT_TO_CRM
				$strSqlOrder
				$records_limit
				";
			$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		}
		else
		{
			$res = new CDBResult();
			$res->InitFromArray(array());
		}
		//echo "<pre>".$strSql."</pre>";
		//echo "<pre>".$strSqlSearch."</pre>";
		$is_filtered = (IsFiltered($strSqlSearch));
		return $res;
	}

	
	/**
	* <p>Возвращает <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/index.php#field">поля результата</a>, а также некоторые <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/index.php">поля веб-формы</a> и <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformstatus/index.php">поля статуса</a> в виде объекта класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p> <p> Структура массива в объекте, возвращаемого данным методом: </p> <pre class="syntax">Array ( [ID] =&gt; ID результата [TIMESTAMP_X] =&gt; время изменения результата [DATE_CREATE] =&gt; дата создания результата [FORM_ID] =&gt; ID веб-формы [USER_ID] =&gt; ID пользователя создавшего результат (автор) [USER_AUTH] =&gt; флаг авторизованности автора при создании результата [Y|N] [STAT_GUEST_ID] =&gt; ID посетителя создавшего результат [STAT_SESSION_ID] =&gt; ID сессии в которой был создан результат [STATUS_ID] =&gt; ID статуса в котором находится результат [STATUS_TITLE] =&gt; заголовок статуса в котором находится результат [STATUS_DESCRIPTION] =&gt; описание статуса в котором находится результат [STATUS_CSS] =&gt; имя CSS класса в котором находится результат [SID] =&gt; символьный идентификатор веб-формы [NAME] =&gt; заголовок веб-формы [IMAGE_ID] =&gt; ID изображения веб-формы [DESCRIPTION] =&gt; описание веб-формы [DESCRIPTION_TYPE] =&gt; тип описания веб-формы [text|html] )</pre>
	*
	*
	* @param int $result_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $rsResult = <b>CFormResult::GetByID</b>(189);
	* $arResult = $rsResult-&gt;Fetch();
	* echo "&lt;pre&gt;"; print_r($arResult); echo "&lt;/pre&gt;";
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/index.php#field">Поля CFormResult</a>;
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/getlist.php">CFormResult::GetList</a>
	* </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/getdatabyid.php">CFormResult::GetDataByID</a>; </li>
	* <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getresultanswerarray.php">CForm::GetResultAnswerArray</a>
	* </li> </ul></b<a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		global $DB, $strError;
		$err_mess = (CFormResult::err_mess())."<br>Function: GetByID<br>Line: ";
		$ID = intval($ID);
		$strSql = "
			SELECT
				R.*,
				".$DB->DateToCharFunction("R.DATE_CREATE")."	DATE_CREATE,
				".$DB->DateToCharFunction("R.TIMESTAMP_X")."	TIMESTAMP_X,
				F.IMAGE_ID, F.DESCRIPTION, F.DESCRIPTION_TYPE, F.SHOW_RESULT_TEMPLATE, F.PRINT_RESULT_TEMPLATE, F.EDIT_RESULT_TEMPLATE, F.NAME,
				F.SID,
				F.SID											VARNAME,
				S.TITLE											STATUS_TITLE,
				S.DESCRIPTION									STATUS_DESCRIPTION,
				S.CSS											STATUS_CSS
			FROM
				b_form_result R,
				b_form_status S,
				b_form F
			WHERE
				R.ID = $ID
			and F.ID = R.FORM_ID
			and R.STATUS_ID = S.ID
			";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}

	// права на результат

	/**
	* <p>Возвращает массив символьных обозначений <a href="http://dev.1c-bitrix.ru/api_help/form/permissions.php">прав</a>, которыми обладает текущий пользователь для указанного <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>. Помимо этого, метод возвращает ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статуса</a> в котором находится указанный результат.</p> <p>В результирующем массиве могут быть следующие символьные обозначения прав: </p> <ul> <li> <b>VIEW</b> - право на просмотр результата; </li> <li> <b>EDIT</b> - право на редактирование результата; </li> <li> <b>DELETE</b> - право на удаление результата. </li> </ul> <p class="note"><b>Примечание</b><br>Права на результат, по сути, являются правами на статус, в котором находится данный результат.</p>
	*
	*
	* @param int $result_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>.
	*
	* @param int &$current_status_id  Ссылка на переменную, в которую будет сохранен ID <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статуса</a>, указанного
	* результата <i>result_id</i>.
	*
	* @return array 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $RESULT_ID = 189; // ID результата
	* 
	* // получим массив прав
	* $arPerm = <b>CFormResult::GetPermissions</b>($RESULT_ID, $current_status_id);
	* 
	* echo "Результат #".$RESULT_ID." находится в статусе ".$current_status_id;
	* 
	* if (in_array("VIEW", $arPerm)) 
	*     echo "У вас есть право на просмотр результата #".$RESULT_ID;
	* 
	* if (in_array("EDIT", $arPerm)) 
	*     echo "У вас есть право на редактирование результата #".$RESULT_ID;
	* 
	* if (in_array("DELETE", $arPerm)) 
	*     echo "У вас есть право на удаление результата #".$RESULT_ID;
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/permissions.php#result">Права на результат</a>
	* </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformstatus/getpermissions.php">CFormStatus::GetPermissions</a>
	* </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformstatus/getpermissionlist.php">CFormStatus::GetPermissionList</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getpermission.php">CForm::GetPermission</a>
	* </li> </ul> </ht<a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/getpermissions.php
	* @author Bitrix
	*/
	public static 	function GetPermissions($RESULT_ID, &$CURRENT_STATUS_ID)
	{
		$err_mess = (CFormResult::err_mess())."<br>Function: GetPermissions<br>Line: ";
		global $DB, $USER, $strError;
		$USER_ID = intval($USER->GetID());
		$RESULT_ID = intval($RESULT_ID);
		$arrReturn = array();
		$arGroups = $USER->GetUserGroupArray();
		if (!is_array($arGroups)) $arGroups[] = 2;
		if (CForm::IsAdmin()) return CFormStatus::GetMaxPermissions();
		else
		{
			$arr = array();
			if (is_array($arGroups) && count($arGroups)>0) $groups = implode(",",$arGroups);
			$strSql = "
				SELECT
					G.PERMISSION,
					R.STATUS_ID
				FROM
					b_form_result R,
					b_form_status_2_group G
				WHERE
					R.ID = $RESULT_ID
				and R.STATUS_ID = G.STATUS_ID
				and (
					(G.GROUP_ID in ($groups) and ifnull(R.USER_ID,0) <> $USER_ID) or
					(G.GROUP_ID in ($groups,0) and ifnull(R.USER_ID,0) = $USER_ID)
					)
				";
			$z = $DB->Query($strSql, false, $err_mess.__LINE__);
			while ($zr = $z->Fetch())
			{
				$arrReturn[] = $zr["PERMISSION"];
				$CURRENT_STATUS_ID = $zr["STATUS_ID"];
			}
		}
		return $arrReturn;
	}

public static 	function AddAnswer($arFields)
	{
		$err_mess = (CFormResult::err_mess())."<br>Function: AddAnswer<br>Line: ";
		global $DB, $strError;
		$arInsert = $DB->PrepareInsert("b_form_result_answer", $arFields, "form");
		$strSql = "INSERT INTO b_form_result_answer (".$arInsert[0].") VALUES (".$arInsert[1].")";
		$DB->Query($strSql, false, $err_mess.__LINE__);
		return intval($DB->LastID());
	}

public static 	function UpdateField($arFields, $RESULT_ID, $FIELD_ID)
	{
		$err_mess = (CFormResult::err_mess())."<br>Function: UpdateField<br>Line: ";
		global $DB, $strError;
		$RESULT_ID = intval($RESULT_ID);
		$FIELD_ID = intval($FIELD_ID);
		$strUpdate = $DB->PrepareUpdate("b_form_result_answer", $arFields, "form");
		$strSql = "UPDATE b_form_result_answer SET ".$strUpdate." WHERE RESULT_ID=".$RESULT_ID." and FIELD_ID=".$FIELD_ID;
		$DB->Query($strSql, false, $err_mess.__LINE__);
	}
}



?>