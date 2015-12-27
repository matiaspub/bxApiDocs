<?
/***************************************
		Web-form result
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
class CAllFormResult extends CFormResult_old
{
	public static function err_mess()
	{
		$module_id = "form";
		@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/install/version.php");
		return "<br>Module: ".$module_id." (".$arModuleVersion["VERSION"].")<br>Class: CAllFormResult<br>File: ".__FILE__;
	}

	
	/**
	* <p>Возвращает массив, содержащий ряд параметров файла, загруженного в поле <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> типа "image" или "file" для указанного <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>. В случае успеха метод возвратит массив, в противном случае - "false".</p> <p> Структура возвращаемого массива: </p> <pre class="syntax">Array ( [USER_FILE_ID] =&gt; ID файла [USER_FILE_NAME] =&gt; имя файла [USER_FILE_IS_IMAGE] =&gt; "Y" - если тип ответа "image"; "N" - если тип ответа "file" [USER_FILE_HASH] =&gt; хэш файла (если тип ответа "file") [USER_FILE_SUFFIX] =&gt; суффикс к расширению файла (если тип ответа "file") [USER_FILE_SIZE] =&gt; размер файла в байтах )</pre>
	*
	*
	* @param int $result_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>.
	*
	* @param int $answer_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a>.</bo
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $RESULT_ID = 189; // ID результата
	* $ANSWER_ID = 148; // ID ответа
	* 
	* // получим данные по изображению
	* $arImage = <b>CFormResult::GetFileByAnswerID</b>($RESULT_ID, $ANSWER_ID);
	* 
	* // выведем изображение
	* echo CFile::ShowImage($arImage["USER_FILE_ID"], 0, 0, "border=0", "", true);
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/getdatabyid.php">CFormResult::GetDataByID</a>
	* </li></ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/getfilebyanswerid.php
	* @author Bitrix
	*/
	public static function GetFileByAnswerID($RESULT_ID, $ANSWER_ID)
	{
		global $DB, $strError;
		$err_mess = (CAllFormResult::err_mess())."<br>Function: GetFileByAnswerID<br>Line: ";
		$RESULT_ID = intval($RESULT_ID);
		$ANSWER_ID = intval($ANSWER_ID);
		$strSql = "
			SELECT
				USER_FILE_ID,
				USER_FILE_NAME,
				USER_FILE_IS_IMAGE,
				USER_FILE_HASH,
				USER_FILE_SUFFIX,
				USER_FILE_SIZE
			FROM
				b_form_result_answer
			WHERE
				RESULT_ID='".$RESULT_ID."'
			and ANSWER_ID='".$ANSWER_ID."'
			";
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		if ($zr = $z->Fetch()) return $zr; else return false;
	}

	// return file data by file hash
	public static function GetFileByHash($RESULT_ID, $HASH)
	{
		global $DB, $APPLICATION, $strError, $USER;

		$err_mess = (CAllFormResult::err_mess())."<br>Function: GetAnswerFile<br>Line: ";

		$RESULT_ID = intval($RESULT_ID);
		if ($RESULT_ID<=0 || strlen(trim($HASH))<=0) return;

		$strSql = "
SELECT
	F.ID as FILE_ID,
	F.FILE_NAME,
	F.SUBDIR,
	F.CONTENT_TYPE,
	F.HANDLER_ID,
	F.FILE_SIZE,
	RA.USER_FILE_NAME ORIGINAL_NAME,
	RA.USER_FILE_IS_IMAGE,
	RA.FORM_ID, R.USER_ID
FROM b_form_result R
LEFT JOIN b_form_result_answer RA ON RA.RESULT_ID=R.ID
INNER JOIN b_file F ON (F.ID = RA.USER_FILE_ID)
WHERE R.ID = '".$RESULT_ID."'
AND RA.USER_FILE_HASH = '".$DB->ForSql($HASH, 255)."'
";

		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		if ($zr = $z->Fetch())
		{
			$F_RIGHT = CForm::GetPermission($zr['FORM_ID']);
			if ($F_RIGHT >= 20 || ($F_RIGHT >= 15 && $USER->GetID() == $zr['USER_ID']))
			{
				unset($zr['FORM_ID']); unset($zr['USER_ID']);
				return $zr;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}

	// create new event
	
	/**
	* <p>Создает событие в модуле "Статистика". Возвращает "true" в случае успеха, в противном случае - "false".</p>
	*
	*
	* @param int $result_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>.
	*
	* @param string $event1 = false Идентификатор типа события - event1.<br> Параметр необязательный. По
	* умолчанию - "false" (будет равен "form").
	*
	* @param string $event2 = false Идентификатор типа события - event2.<br> Параметр необязательный. По
	* умолчанию - "false" (будет равен символьному идентификатору
	* соответствующей веб-формы).
	*
	* @param string $event3 = false Дополнительный параметр события - event3.<br> Параметр
	* необязательный. По умолчанию - "false" (будет равен ссылке, ведущей на
	* административную страницу просмотра результата <i>result_id</i>).
	*
	* @param mixed $money = "" Денежная сумма события.<br> Параметр необязательный. По умолчанию -
	* "".
	*
	* @param mixed $currency = "" Трехсимвольный идентификатор валюты денежной суммы <i>money</i>.<br>
	* Параметр необязательный. По умолчанию - "".
	*
	* @param mixed $goto = "" Если в данный параметр передано значение "Y", при создании события
	* в модуле "Статистика" денежная сумма <i>money</i> будет зафиксирована с
	* отрицательным знаком.<br> Параметр необязательный. По умолчанию -
	* "N".
	*
	* @param mixed $chargeback = "N" Параметр необязательный. По умолчанию - "".
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $RESULT_ID = 189; // ID результата
	* 
	* // создадим событие в модуле "Статистика"
	* if (<b>CFormResult::SetEvent</b>($RESULT_ID))
	* {
	*     echo "Событие успешно создано.";
	* }
	* else // ошибка
	* {
	*     global $strError;
	*     echo $strError;
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/setevent.php
	* @author Bitrix
	*/
	public static function SetEvent($RESULT_ID, $IN_EVENT1=false, $IN_EVENT2=false, $IN_EVENT3=false, $money="", $currency="", $goto="", $chargeback="N")
	{
		$err_mess = (CAllFormResult::err_mess())."<br>Function: SetEvent<br>Line: ";
		global $DB, $strError;

		if (CModule::IncludeModule("statistic"))
		{
			$RESULT_ID = intval($RESULT_ID);
			$strSql = "SELECT FORM_ID FROM b_form_result WHERE ID='".$RESULT_ID."'";
			$z = $DB->Query($strSql, false, $err_mess.__LINE__);
			if ($zr = $z->Fetch())
			{
				$WEB_FORM_ID = $zr["FORM_ID"];
				$strSql = "SELECT SID, STAT_EVENT1, STAT_EVENT2, STAT_EVENT3 FROM b_form WHERE ID = '".$WEB_FORM_ID."'";
				$z = $DB->Query($strSql, false, $err_mess.__LINE__);
				$zr = $z->Fetch();

				if ($IN_EVENT1===false)
				{
					$event1 = (strlen($zr["STAT_EVENT1"])<=0) ? "form" : $zr["STAT_EVENT1"];
				}
				else $event1 = $IN_EVENT1;

				if ($IN_EVENT2===false)
				{
					$event2 = (strlen($zr["STAT_EVENT2"])<=0) ? $zr["SID"] : $zr["STAT_EVENT2"];
				}
				else $event2 = $IN_EVENT2;

				if ($IN_EVENT3===false)
				{
					$event3 = strlen($zr["STAT_EVENT3"])<=0
						? (
							$GLOBALS['APPLICATION']->IsHTTPS() ? "https://" : "http://"
						).$_SERVER["HTTP_HOST"]."/bitrix/admin/form_result_list.php?lang=".LANGUAGE_ID."&WEB_FORM_ID=".$WEB_FORM_ID."&find_id=".$RESULT_ID."&find_id_exact_match=Y&set_filter=Y"
						: $zr["STAT_EVENT3"];
				}
				else $event3 = $IN_EVENT3;

				CStatEvent::AddCurrent($event1, $event2, $event3, $money, $currency, $goto, $chargeback);
				return true;
			}
			else $strError .= GetMessage("FORM_ERROR_RESULT_NOT_FOUND")."<br>";
		}
		return false;
	}

	//returns data for questions and answers array
	
	/**
	* <p>Возвращает массив, описывающий значения <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответов</a> на <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопросы</a> или значения <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">полей</a> веб-формы для указанного <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>. Помимо этого, метод возвращает массив, содержащий <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/index.php">поля результата</a>.</p> <p> Формат массива, возвращаемого методом: </p> <pre class="syntax">Array ( [<i>символьный идентификатор вопроса 1</i>] =&gt; массив описывающий ответы на вопрос 1 Array ( [0] =&gt; массив описывающий ответ 1 Array ( [RESULT_ID] =&gt; ID результата [FIELD_ID] =&gt; ID вопроса [SID] =&gt; символьный идентификатор вопроса [TITLE] =&gt; текст вопроса [TITLE_TYPE] =&gt; тип текста вопроса [text|html] [FILTER_TITLE] =&gt; заголовок поля фильтра [RESULTS_TABLE_TITLE] =&gt; заголовок столбца таблицы результатов [ANSWER_ID] =&gt; ID ответа [ANSWER_TEXT] =&gt; параметр ответа <font color="green">ANSWER_TEXT</font> [ANSWER_VALUE] =&gt; параметр ответа <font color="red">ANSWER_VALUE</font> [USER_TEXT] =&gt; текст введенный с клавиатуры [USER_DATE] =&gt; введенная дата (если FIELD_TYPE=date) [USER_FILE_ID] =&gt; ID файла (если FIELD_TYPE=[file|image]) [USER_FILE_NAME] =&gt; имя файла [USER_FILE_IS_IMAGE] =&gt; "Y" - FIELD_TYPE=image; "N" - FIELD_TYPE=file [USER_FILE_HASH] =&gt; хэш файла (если FIELD_TYPE=file) [USER_FILE_SUFFIX] =&gt; суффикс к расширению файла (если FIELD_TYPE=file) [USER_FILE_SIZE] =&gt; размер файла (если FIELD_TYPE=[file|image]) [FIELD_TYPE] =&gt; тип ответа [FIELD_WIDTH] =&gt; ширина поля ответа [FIELD_HEIGHT] =&gt; высота поля ответа [FIELD_PARAM] =&gt; параметр поля ответа ) [1] =&gt; массив описывающий ответ 2 [2] =&gt; массив описывающий ответ 3 ... [N-1] =&gt; массив описывающий ответ N ) [<i>символьный идентификатор вопроса 2</i>] =&gt; массив описывающий ответы на вопрос 2 [<i>символьный идентификатор вопроса 3</i>] =&gt; массив описывающий ответы на вопрос 3 ... [<i>символьный идентификатор вопроса N</i>] =&gt; массив описывающий ответы на вопрос N )</pre>
	*
	*
	* @param int $result_id  ID результата.
	*
	* @param array $field  Массив символьных идентификаторов вопросов или полей веб-формы,
	* значения которых необходимо получить.
	*
	* @param array &$result  Ссылка на массив <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/index.php">полей
	* результата</a>, а также некоторых <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/index.php">полей веб-формы</a> и <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformstatus/index.php">полей статуса</a>.
	* Структура данного массива: <pre>Array ( [ID] =&gt; ID результата [TIMESTAMP_X] =&gt;
	* время изменения результата [DATE_CREATE] =&gt; дата создания результата
	* [FORM_ID] =&gt; ID веб-формы [USER_ID] =&gt; ID пользователя создавшего результат
	* (автор) [USER_AUTH] =&gt; флаг авторизованности автора при создании
	* результата [Y|N] [STAT_GUEST_ID] =&gt; ID посетителя создавшего результат
	* [STAT_SESSION_ID] =&gt; ID сессии в которой был создан результат [STATUS_ID] =&gt; ID
	* статуса в котором находится результат [STATUS_TITLE] =&gt; заголовок
	* статуса в котором находится результат [STATUS_DESCRIPTION] =&gt; описание
	* статуса в котором находится результат [STATUS_CSS] =&gt; имя CSS класса в
	* котором находится результат [SID] =&gt; символьный идентификатор
	* веб-формы [NAME] =&gt; заголовок веб-формы [IMAGE_ID] =&gt; ID изображения
	* веб-формы [DESCRIPTION] =&gt; описание веб-формы [DESCRIPTION_TYPE] =&gt; тип
	* описания веб-формы [text|html] )</pre>
	*
	* @param array &$answer  Ссылка на массив, описывающий значения ответов на вопросы или
	* значения полей веб-формы для указанного результата <i>result_id</i>.
	* Структура данного массива: <pre>Array ( [<i>символьный идентификатор
	* вопроса 1</i>] =&gt; массив описывающий ответы на вопрос 1 Array ( [<i>ID
	* ответа 1</i>] =&gt; массив описывающий ответ 1 Array ( [RESULT_ID] =&gt; ID
	* результата [FIELD_ID] =&gt; ID вопроса [SID] =&gt; символьный идентификатор
	* вопроса [TITLE] =&gt; текст вопроса [TITLE_TYPE] =&gt; тип текста вопроса [text|html]
	* [FILTER_TITLE] =&gt; заголовок поля фильтра [RESULTS_TABLE_TITLE] =&gt; заголовок
	* столбца таблицы результатов [ANSWER_ID] =&gt; ID ответа [ANSWER_TEXT] =&gt;
	* параметр ответа <font color="green">ANSWER_TEXT</font> [ANSWER_VALUE] =&gt; параметр ответа
	* <font color="red">ANSWER_VALUE</font> [USER_TEXT] =&gt; текст введенный с клавиатуры
	* [USER_DATE] =&gt; введенная дата (если FIELD_TYPE=date) [USER_FILE_ID] =&gt; ID файла
	* (FIELD_TYPE=[file|image]) [USER_FILE_NAME] =&gt; имя файла [USER_FILE_IS_IMAGE] =&gt; "Y" - FIELD_TYPE=image;
	* "N" - FIELD_TYPE=file [USER_FILE_HASH] =&gt; хэш файла (если FIELD_TYPE=file) [USER_FILE_SUFFIX] =&gt;
	* суффикс к расширению файла (FIELD_TYPE=file) [USER_FILE_SIZE] =&gt; размер файла
	* (если FIELD_TYPE=[file|image]) [FIELD_TYPE] =&gt; тип ответа [FIELD_WIDTH] =&gt; ширина поля
	* ответа [FIELD_HEIGHT] =&gt; высота поля ответа [FIELD_PARAM] =&gt; параметр поля
	* ответа ) [<i>ID ответа 2</i>] =&gt; массив описывающий ответ 2 [<i>ID ответа
	* 3</i>] =&gt; массив описывающий ответ 3 ... [<i>ID ответа N</i>] =&gt; массив
	* описывающий ответ N ) [<i>символьный идентификатор вопроса 2</i>] =&gt;
	* массив описывающий ответы на вопрос 2 [<i>символьный идентификатор
	* вопроса 3</i>] =&gt; массив описывающий ответы на вопрос 3 ...
	* [<i>символьный идентификатор вопроса N</i>] =&gt; массив описывающий
	* ответы на вопрос N )</pre>
	*
	* @return array 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $RESULT_ID = 189; // ID результата
	* 
	* $arAnswer = <b>CFormResult::GetDataByID</b>(
	* 	$RESULT_ID, 
	* 	array("VS_INTEREST"),  // вопрос "Какие области знаний вас интересуют?" 
	* 	$arResult, 
	* 	$arAnswer2);
	* 
	* // выведем поля результата
	* echo "&lt;pre&gt;"; print_r($arResult); echo "&lt;/pre&gt;";
	* 
	* // выведем значения ответов
	* echo "&lt;pre&gt;"; print_r($arAnswer); echo "&lt;/pre&gt;";
	* 
	* // выведем значения ответов в несколько ином формате
	* echo "&lt;pre&gt;"; print_r($arAnswer2); echo "&lt;/pre&gt;";
	* ?&gt;
	* 
	* 
	* 
	* &lt;?
	* $RESULT_ID = 189; // ID результата
	* 
	* // получим данные по всем вопросам
	* $arAnswer = <b>CFormResult::GetDataByID</b>(
	* 	$RESULT_ID, 
	* 	array(), 
	* 	$arResult, 
	* 	$arAnswer2);
	* 
	* // выведем поля результата
	* echo "&lt;pre&gt;"; print_r($arResult); echo "&lt;/pre&gt;";
	* 
	* // выведем значения ответов
	* echo "&lt;pre&gt;"; print_r($arAnswer); echo "&lt;/pre&gt;";
	* 
	* // выведем значения ответов в несколько ином формате
	* echo "&lt;pre&gt;"; print_r($arAnswer2); echo "&lt;/pre&gt;";
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/index.php">Поля CFormResult</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/index.php">Поля CForm</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/getresultanswerarray.php">CForm::GetResultAnswerArray</a>
	* </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/getdatabyid.php
	* @author Bitrix
	*/
	public static function GetDataByID($RESULT_ID, $arrFIELD_SID, &$arrRES, &$arrANSWER)
	{
		global $DB, $strError;
		$err_mess = (CAllFormResult::err_mess())."<br>Function: GetDataByID<br>Line: ";
		$arrReturn = array();
		$RESULT_ID = intval($RESULT_ID);
		$z = CFormResult::GetByID($RESULT_ID);
		if ($arrRES = $z->Fetch())
		{
			if (is_array($arrFIELD_SID) && count($arrFIELD_SID)>0)
			{
				foreach($arrFIELD_SID as $field) $str .= ",'".$DB->ForSql($field,50)."'";
				$str = TrimEx($str,",");
				if (strlen($str)>0) $s = "and SID in ($str)";
			}
			$strSql = "SELECT ID, SID, SID as VARNAME FROM b_form_field WHERE FORM_ID='".$arrRES["FORM_ID"]."' ".$s;
			$q = $DB->Query($strSql, false, $err_mess.__LINE__);
			while ($qr = $q->Fetch())
			{
				$arrFIELDS[$qr["ID"]] = $qr["SID"];
			}
			if (is_array($arrFIELDS)) $arrKeys = array_keys($arrFIELDS);
			CForm::GetResultAnswerArray($arrRES["FORM_ID"], $arrColumns, $arrAnswers, $arrAnswersSID, array("RESULT_ID"=>$RESULT_ID));

			foreach ($arrAnswers[$RESULT_ID] as $fid => $arrAns)
			{
				if (is_array($arrKeys))
				{
					if (in_array($fid,$arrKeys))
					{
						$sid = $arrFIELDS[$fid];
						$arrANSWER[$sid] = $arrAns;
						$arrA = array_values($arrAns);
						foreach($arrA as $arr) $arrReturn[$sid][] = $arr;
					}
				}
			}
		}
		else return false;

		if (is_array($arrANSWER)) reset($arrANSWER);
		if (is_array($arrReturn)) reset($arrReturn);
		if (is_array($arrRES)) reset($arrRES);

		return $arrReturn;
	}

	// return array of result values for component
	
	/**
	* <p>Возвращает массив значений <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответов</a> на <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопросы</a> веб-формы, а также значения <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">полей</a> веб-формы для указанного <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>. </p> <p>Ключи возвращаемого массива в точности соответствуют <a href="http://dev.1c-bitrix.ru/api_help/form/htmlnames.php">правилам</a> формирования имен HTML полей для веб-формы.</p> <p> Пример массива, возвращаемого методом: </p> <pre class="syntax">Array ( [form_text_586] =&gt; Иванов Иван Иванович [form_date_587] =&gt; 10.03.1992 [form_textarea_588] =&gt; г. Мурманск [form_radio_VS_MARRIED] =&gt; 589 [form_checkbox_VS_INTEREST] =&gt; Array ( [0] =&gt; 592 [1] =&gt; 593 [2] =&gt; 594 ) [form_dropdown_VS_AGE] =&gt; 597 [form_multiselect_VS_EDUCATION] =&gt; Array ( [0] =&gt; 603 [1] =&gt; 604 ) [form_text_606] =&gt; 2345 [form_image_607] =&gt; 1045 )</pre>
	*
	*
	* @param int $result_id  ID результата.
	*
	* @param string $get_fields = "N" Если значение данного параметра равно "Y", то в в массиве,
	* возвращаемом данным методом, будут также значения <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">полей</a> веб-формы; в противном
	* случае, в возвращаемом массиве будут только значения <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответов</a> на <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопросов</a> веб-формы.<br><br>
	* Параметр необязательный. По умолчанию - "N".
	*
	* @return array 
	*
	* <h4>Example</h4> 
	* <pre>
	* Array
	* (
	*     [form_text_586] =&gt; Иванов Иван Иванович
	*     [form_date_587] =&gt; 10.03.1992
	*     [form_textarea_588] =&gt; г. Мурманск
	*     [form_radio_VS_MARRIED] =&gt; 589
	*     [form_checkbox_VS_INTEREST] =&gt; Array
	*         (
	*             [0] =&gt; 592
	*             [1] =&gt; 593
	*             [2] =&gt; 594
	*         )
	* 
	*     [form_dropdown_VS_AGE] =&gt; 597
	*     [form_multiselect_VS_EDUCATION] =&gt; Array
	*         (
	*             [0] =&gt; 603
	*             [1] =&gt; 604
	*         )
	* 
	*     [form_text_606] =&gt; 2345
	*     [form_image_607] =&gt; 1045
	* )
	* 
	* Параметры метода
	* </h
	* <tr>
	* <th width="15%">Параметр</th>
	* 	<th>Описание</th>
	* </tr>
	* <tr>
	* <td><i>result_id</i></td>
	* 	<td>ID результата.</td>
	* </tr>
	* <tr>
	* <td><i>get_fields</i></td>
	* 	<td>Если значение данного параметра равно "Y", то в в массиве, возвращаемом данным методом, будут также значения <a href="/api_help/form/terms.php#field">полей</a> веб-формы; в противном случае, в возвращаемом массиве будут только значения <a href="/api_help/form/terms.php#answer">ответов</a> на <a href="/api_help/form/terms.php#question">вопросов</a> веб-формы.<br><br>
	* 	Параметр необязательный. По умолчанию - "N".
	* </td>
	* </tr>
	* 
	* 
	* 
	* &lt;?
	* $RESULT_ID = 189; // ID результата
	* 
	* // получим данные результата
	* $arValues = <b>CFormResult::GetDataByIDForHTML</b>($RESULT_ID, "Y");
	* 
	* // выведем ответ на вопрос "Фамилия, имя, отчество"
	* echo $arValues["form_text_586"]; // "Иванов Василий"
	* 
	* // выведем фотографию загруженную в качестве ответа на вопрос "Фотография"
	* CFile::ShowImage($arValues["form_image_607"], 200, 200, "border=0", "", true);
	* 
	* // выведем значение поля веб-формы "Рассчитанная стоимость"
	* echo $arValues["form_textarea_ADDITIONAL_149"]; // 134 руб.
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/htmlnames.php">Имена HTML полей</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/add.php">CFormResult::Add</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/update.php">CFormResult::Update</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/check.php">CForm::Check</a> </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/getdatabyidforhtml.php
	* @author Bitrix
	*/
	public static function GetDataByIDForHTML($RESULT_ID, $GET_ADDITIONAL="N")
	{
		$err_mess = (CAllFormResult::err_mess())."<br>Function: GetDataByIDForHTML<br>Line: ";
		global $DB, $strError;
		$z = CFormResult::GetByID($RESULT_ID);
		if ($zr=$z->Fetch())
		{
			$arrResult = $zr;
			$additional = ($GET_ADDITIONAL=="Y") ? "ALL" : "N";

			$WEB_FORM_ID = CForm::GetDataByID($arrResult["FORM_ID"], $arForm, $arQuestions, $arAnswers, $arDropDown, $arMultiSelect, $additional);

			CForm::GetResultAnswerArray($WEB_FORM_ID, $arrResultColumns, $arrResultAnswers, $arrResultAnswersSID, array("RESULT_ID" => $RESULT_ID));
			$arrResultAnswers = $arrResultAnswers[$RESULT_ID];

			$DB_VARS = array();
			foreach ($arQuestions as $key => $arQuestion)
			{
				if ($arQuestion["ADDITIONAL"]!="Y")
				{
					$FIELD_SID = $arQuestion["SID"];
					if (is_array($arAnswers[$FIELD_SID]))
					{
						foreach ($arAnswers[$FIELD_SID] as $key => $arAnswer)
						{
							$arrResultAnswer = $arrResultAnswers[$arQuestion["ID"]][$arAnswer["ID"]];
							$FIELD_TYPE = $arAnswer["FIELD_TYPE"];
							switch ($FIELD_TYPE) :

								case "radio":
								case "dropdown":
									if (intval($arrResultAnswer["ANSWER_ID"])>0)
									{
										$fname = "form_".strtolower($FIELD_TYPE)."_".$FIELD_SID;
										$DB_VARS[$fname] = $arrResultAnswer["ANSWER_ID"];
									}
								break;

								case "checkbox":
								case "multiselect":
									if (intval($arrResultAnswer["ANSWER_ID"])>0)
									{
										$fname = "form_".strtolower($FIELD_TYPE)."_".$FIELD_SID;
										$DB_VARS[$fname][] = $arrResultAnswer["ANSWER_ID"];
									}
								break;

								case "date":
									if (strlen($arrResultAnswer["USER_DATE"])>0)
									{
										$arrResultAnswer["USER_TEXT"] = $DB->FormatDate(
											$arrResultAnswer["USER_DATE"],
											FORMAT_DATETIME,
											(MakeTimeStamp($arrResultAnswer["USER_TEXT"])+date('Z'))%86400 == 0 ? FORMAT_DATE : FORMAT_DATETIME
										);

										$fname = "form_".strtolower($FIELD_TYPE)."_".$arAnswer["ID"];
										$DB_VARS[$fname] = $arrResultAnswer["USER_TEXT"];
									}

									break;

								case "text":
								case "password":
								case "textarea":
								case "email":
								case "url":
								case "hidden":
									if (strlen($arrResultAnswer["USER_TEXT"])>0)
									{
										$fname = "form_".strtolower($FIELD_TYPE)."_".$arAnswer["ID"];
										$DB_VARS[$fname] = $arrResultAnswer["USER_TEXT"];
									}
								break;

								case "image":
								case "file":
									if (intval($arrResultAnswer["USER_FILE_ID"])>0)
									{
										$fname = "form_".strtolower($FIELD_TYPE)."_".$arAnswer["ID"];
										$DB_VARS[$fname] = $arrResultAnswer["USER_FILE_ID"];
									}
								break;

							endswitch;
						} //endforeach;
					}
				}
				else
				{
					$FIELD_TYPE = $arQuestion["FIELD_TYPE"];
					$arrResultAnswer = $arrResultAnswers[$arQuestion["ID"]][0];
					switch ($FIELD_TYPE) :
						case "text":
							if (strlen($arrResultAnswer["USER_TEXT"])>0)
							{
								$fname = "form_textarea_ADDITIONAL_".$arQuestion["ID"];
								$DB_VARS[$fname] = $arrResultAnswer["USER_TEXT"];
							}
							break;
						case "integer":
							if (strlen($arrResultAnswer["USER_TEXT"])>0)
							{
								$fname = "form_text_ADDITIONAL_".$arQuestion["ID"];
								$DB_VARS[$fname] = $arrResultAnswer["USER_TEXT"];
							}
							break;
						case "date":
							$fname = "form_date_ADDITIONAL_".$arQuestion["ID"];
							$DB_VARS[$fname] = $arrResultAnswer["USER_TEXT"];
							break;
					endswitch;
				}
			}//endforeach
			return $DB_VARS;
		}
	}

	// add new form result
	
	/**
	* <p>Создает новый <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результат</a> веб-формы. В случае успеха - возвращает ID нового <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>, в противном случае - "false".</p> <p><b>Примечание: </b>в случае неактивных вопросов данные из формы в них не сохраняются и сообщения об ошибках не выводятся.</p>
	*
	*
	* @param int $form_id  ID веб-формы.</bod
	*
	* @param array $values = false Массив со значениями <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответов</a>. Массив имеет
	* следующую структуру: <pre> array( "<i>имя HTML поля ответа 1</i>" =&gt;
	* "<i>значение ответа 1</i>", "<i>имя HTML поля ответа 2</i>" =&gt; "<i>значение
	* ответа 2</i>", ... "<i>имя HTML поля ответа N</i>" =&gt; "<i>значение ответа N</i>" )
	* </pre> Правила формирования "<i>имен HTML полей ответов</i>" и "<i>значений
	* ответов</i>" описаны в разделе "<a
	* href="http://dev.1c-bitrix.ru/api_help/form/htmlnames.php">Имена HTML полей веб-форм</a>".
	* <h5>Пример:</h5> <pre> Array ( [form_text_586] =&gt; Иванов Иван Иванович [form_date_587] =&gt;
	* 10.03.1992 [form_textarea_588] =&gt; г. Мурманск [form_radio_VS_MARRIED] =&gt; 589 [form_checkbox_VS_INTEREST]
	* =&gt; Array ( [0] =&gt; 592 [1] =&gt; 593 [2] =&gt; 594 ) [form_dropdown_VS_AGE] =&gt; 597
	* [form_multiselect_VS_EDUCATION] =&gt; Array ( [0] =&gt; 603 [1] =&gt; 604 ) [form_text_606] =&gt; 2345 [form_image_607]
	* =&gt; 1045 ) </pre> Параметр необязательный. По умолчанию - "false" (будет
	* взят стандартный массив $_REQUEST).
	*
	* @param string $check_rights = "Y" Флаг необходимости проверки прав текущего пользователя.
	* Возможны следующие значения: <ul> <li> <b>Y</b> - права необходимо
	* проверить; </li> <li> <b>N</b> - право не нужно проверять. </li> </ul> Для
	* создания нового <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>
	* необходимо иметь право <b>[10] Заполнение веб-формы</b> на веб-форму
	* <i>form_id</i>.<br><br>Параметр необязательный. По умолчанию - "Y" (права
	* необходимо проверить).
	*
	* @param int $user_id = false ID пользователя, который будет записан как создатель данного <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>.<br><br> Параметр
	* необязательный. По умолчанию - "false" (будет взят ID текущего
	* пользователя).
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // ID веб-формы
	* $FORM_ID = 4;
	* 
	* // массив описывающий загруженную на сервер фотографию
	* $arImage = CFile::MakeFileArray($_SERVER["DOCUMENT_ROOT"]."/images/photo.gif");
	* 
	* // массив значений ответов
	* $arValues = array (
	*     "form_text_586"                 =&gt; "Иванов Иван",    // "Фамилия, имя, отчество"
	*     "form_date_587"                 =&gt; "01.06.1904",     // "Дата рождения"
	*     "form_textarea_588"             =&gt; "г. Москва",      // "Адрес"
	*     "form_radio_VS_MARRIED"         =&gt; 590,              // "Женаты/замужем?"
	*     "form_checkbox_VS_INTEREST"     =&gt; array(612, 613),  // "Увлечения"
	*     "form_dropdown_VS_AGE"          =&gt; 601,              // "Возраст"
	*     "form_multiselect_VS_EDUCATION" =&gt; array(602, 603),  // "Образование"
	*     "form_text_606"                 =&gt; 300,              // "Доход"
	*     "form_image_607"                =&gt; $arImage          // "Фотография"
	* );
	* 
	* // создадим новый результат
	* if ($RESULT_ID = <b>CFormResult::Add</b>($FORM_ID, $arValues))
	* {
	*     echo "Результат #".$RESULT_ID." успешно создан";
	* }
	* else
	* {
	*     global $strError;
	*     echo $strError;
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/update.php">CFormResult::Update</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/setfield.php">CFormResult::SetField</a>; </li>
	* <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/getdatabyidforhtml.php">CFormResult::GetDataByIDForHTML</a>
	* </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/add.php
	* @author Bitrix
	*/
	public static function Add($WEB_FORM_ID, $arrVALUES=false, $CHECK_RIGHTS="Y", $USER_ID=false)
	{
		$err_mess = (CAllFormResult::err_mess())."<br>Function: Add<br>Line: ";
		global $DB, $USER, $_REQUEST, $HTTP_POST_VARS, $HTTP_GET_VARS, $HTTP_POST_FILES, $strError, $APPLICATION;
		if ($arrVALUES===false) $arrVALUES = $_REQUEST;

		if ($CHECK_RIGHTS != "N") $CHECK_RIGHTS = "Y";

		$WEB_FORM_ID = intval($WEB_FORM_ID);

		if ($WEB_FORM_ID>0)
		{
			$WEB_FORM_ID = intval($WEB_FORM_ID);

			// get form data
			$arForm = array();
			$arQuestions = array();
			$arAnswers = array();
			$arDropDown = array();
			$arMultiSelect = array();

			$WEB_FORM_ID = CForm::GetDataByID($WEB_FORM_ID, $arForm, $arQuestions, $arAnswers, $arDropDown, $arMultiSelect);

			// if new form id is correct
			if ($WEB_FORM_ID>0)
			{
				// check result rights
				$F_RIGHT = CForm::GetPermission($WEB_FORM_ID);

				if (intval($F_RIGHT)>=10 || $CHECK_RIGHTS=="N")
				{
					if (intval($USER_ID)<=0)
					{
						$USER_AUTH = "N";
						$USER_ID = intval($_SESSION["SESS_LAST_USER_ID"]);
						if (intval($USER->GetID())>0)
						{
							$USER_AUTH = "Y";
							$USER_ID = intval($USER->GetID());
						}
					}
					else $USER_AUTH = "Y";

					// check result status
					$fname = "status_".$arForm["SID"];
					$STATUS_ID = (intval($arrVALUES[$fname])<=0) ? CFormStatus::GetDefault($WEB_FORM_ID) : intval($arrVALUES[$fname]);

					if ($STATUS_ID <= 0)
					{
						$strError .= GetMessage("FORM_STATUS_NOT_DEFINED")."<br>";
					}
					else
					{
						// status found
						if ($CHECK_RIGHTS != "N")
						{
							$arPerm = CFormStatus::GetPermissions($STATUS_ID);
						}

						if ($CHECK_RIGHTS == "N" || in_array("MOVE", $arPerm)) // has rights to a new status
						{
							// check restrictions

							if ($arForm["USE_RESTRICTIONS"] == "Y" && intval($USER_ID) > 0)
							{
								$arFilter = array("USER_ID" => $USER_ID);
								if (strlen($arForm["RESTRICT_STATUS"]) > 0)
								{
									$arStatus = explode(",", $arForm["RESTRICT_STATUS"]);
									$arFilter = array_merge($arFilter, array("STATUS_ID" => implode(" | ", $arStatus)));
								}

								if (intval($arForm["RESTRICT_USER"]) > 0)
								{
									$rsFormResult = CFormResult::GetList($WEB_FORM_ID, $by="s_timestamp", $order="desc", $arFilter, $is_filtered, "N", intval($arForm["RESTRICT_USER"]));
									$num = 0;
									while ($row = $rsFormResult->Fetch())
									{
										if (++$num >= $arForm["RESTRICT_USER"])
										{
											$strError .= GetMessage("FORM_RESTRICT_USER_ERROR")."<br />";
											break;
										}
									}
								}

								if (strlen($strError) <= 0 && intval($arForm["RESTRICT_TIME"]) > 0)
								{
									$DC2 = time();
									$DC1 = $DC2 - intval($arForm["RESTRICT_TIME"]);
									$arFilter = array_merge($arFilter, array(
										"TIME_CREATE_1" => ConvertTimeStamp($DC1, "FULL"),
										"TIME_CREATE_2" => ConvertTimeStamp($DC2, "FULL"),
									));

									CTimeZone::Disable();
									$rsFormResult = CFormResult::GetList($WEB_FORM_ID, $by="s_timestamp", $order="desc", $arFilter, $is_filtered, "N", 1);
									CTimeZone::Enable();

									if ($rsFormResult->Fetch())
									{
										$strError .= GetMessage("FORM_RESTRICT_TIME_ERROR")."<br>";
									}
								}
							}

							if (strlen($strError) <= 0)
							{
								// save result
								$arFields = array(
									"TIMESTAMP_X"		=> $DB->GetNowFunction(),
									"DATE_CREATE"		=> $DB->GetNowFunction(),
									"STATUS_ID"			=> $STATUS_ID,
									"FORM_ID"			=> $WEB_FORM_ID,
									"USER_ID"			=> intval($USER_ID),
									"USER_AUTH"			=> "'".$USER_AUTH."'",
									"STAT_GUEST_ID"		=> intval($_SESSION["SESS_GUEST_ID"]),
									"STAT_SESSION_ID"	=> intval($_SESSION["SESS_SESSION_ID"]),
									"SENT_TO_CRM"		=> "'N'", // result can be sent only after adding
									);

								$dbEvents = GetModuleEvents('form', 'onBeforeResultAdd');
								while ($arEvent = $dbEvents->Fetch())
								{
									ExecuteModuleEventEx($arEvent, array($WEB_FORM_ID, &$arFields, &$arrVALUES));

									if ($ex = $APPLICATION->GetException())
									{
										$strError .= $ex->GetString().'<br />';
										$APPLICATION->ResetException();
									}
								}

								if (strlen($strError) <= 0)
									$RESULT_ID = $DB->Insert("b_form_result", $arFields, $err_mess.__LINE__);
							}
						}
						else
							$strError .= GetMessage("FORM_ERROR_ACCESS_DENIED");
					}

					$RESULT_ID = intval($RESULT_ID);
					// save successful
					if ($RESULT_ID>0)
					{
						$arrANSWER_TEXT = array();
						$arrANSWER_VALUE = array();
						$arrUSER_TEXT = array();

						// process questions
						foreach ($arQuestions as $arQuestion)
						{
							$FIELD_ID = $arQuestion["ID"];
							$FIELD_SID = $arQuestion["SID"];
							$radio = "N";
							$checkbox = "N";
							$multiselect = "N";
							$dropdown = "N";
							if (is_array($arAnswers[$FIELD_SID]))
							{
								// process answers
								foreach ($arAnswers[$FIELD_SID] as $key => $arAnswer)
								{
									$ANSWER_ID = 0;
									$FIELD_TYPE = $arAnswer["FIELD_TYPE"];
									$FIELD_PARAM = $arAnswer["FIELD_PARAM"];
									switch ($FIELD_TYPE) :

										case "radio":
										case "dropdown":

											if (($radio=="N" && $FIELD_TYPE=="radio") ||
												($dropdown=="N" && $FIELD_TYPE=="dropdown"))
											{
												$fname = "form_".$FIELD_TYPE."_".$FIELD_SID;
												$ANSWER_ID = intval($arrVALUES[$fname]);
												if ($ANSWER_ID>0)
												{
													$z = CFormAnswer::GetByID($ANSWER_ID);
													if ($zr = $z->Fetch())
													{
														$arFields = array(
															"RESULT_ID"			=> $RESULT_ID,
															"FORM_ID"			=> $WEB_FORM_ID,
															"FIELD_ID"			=> $FIELD_ID,
															"ANSWER_ID"			=> $ANSWER_ID,
															"ANSWER_TEXT"		=> trim($zr["MESSAGE"]),
															"ANSWER_VALUE"		=> $zr["VALUE"]
															);
														$arrANSWER_TEXT[$FIELD_ID][] = ToUpper($arFields["ANSWER_TEXT"]);
														$arrANSWER_VALUE[$FIELD_ID][] = ToUpper($arFields["ANSWER_VALUE"]);
														CFormResult::AddAnswer($arFields);
													}
													if ($FIELD_TYPE=="radio") $radio = "Y";
													if ($FIELD_TYPE=="dropdown") $dropdown = "Y";
												}
											}

										break;

										case "checkbox":
										case "multiselect":

											if (($checkbox=="N" && $FIELD_TYPE=="checkbox") ||
												($multiselect=="N" && $FIELD_TYPE=="multiselect"))
											{
												$fname = "form_".$FIELD_TYPE."_".$FIELD_SID;
												if (is_array($arrVALUES[$fname]) && count($arrVALUES[$fname])>0)
												{
													foreach($arrVALUES[$fname] as $ANSWER_ID)
													{
														$ANSWER_ID = intval($ANSWER_ID);
														if ($ANSWER_ID>0)
														{
															$z = CFormAnswer::GetByID($ANSWER_ID);
															if ($zr = $z->Fetch())
															{
																$arFields = array(
																	"RESULT_ID"			=> $RESULT_ID,
																	"FORM_ID"			=> $WEB_FORM_ID,
																	"FIELD_ID"			=> $FIELD_ID,
																	"ANSWER_ID"			=> $ANSWER_ID,
																	"ANSWER_TEXT"		=> trim($zr["MESSAGE"]),
																	"ANSWER_VALUE"		=> $zr["VALUE"]
																	);
																$arrANSWER_TEXT[$FIELD_ID][] = ToUpper($arFields["ANSWER_TEXT"]);
																$arrANSWER_VALUE[$FIELD_ID][] = ToUpper($arFields["ANSWER_VALUE"]);
																CFormResult::AddAnswer($arFields);
															}
														}
													}
													if ($FIELD_TYPE=="checkbox") $checkbox = "Y";
													if ($FIELD_TYPE=="multiselect") $multiselect = "Y";
												}
											}

										break;

										case "text":
										case "hidden":
										case "textarea":
										case "password":
										case "email":
										case "url":

											$fname = "form_".$FIELD_TYPE."_".$arAnswer["ID"];
											$ANSWER_ID = intval($arAnswer["ID"]);
											$z = CFormAnswer::GetByID($ANSWER_ID);
											if ($zr = $z->Fetch())
											{
												$arFields = array(
													"RESULT_ID"			=> $RESULT_ID,
													"FORM_ID"			=> $WEB_FORM_ID,
													"FIELD_ID"			=> $FIELD_ID,
													"ANSWER_ID"			=> $ANSWER_ID,
													"ANSWER_TEXT"		=> trim($zr["MESSAGE"]),
													"ANSWER_VALUE"		=> $zr["VALUE"],
													"USER_TEXT"			=> $arrVALUES[$fname]
												);

												$arrANSWER_TEXT[$FIELD_ID][] = ToUpper($arFields["ANSWER_TEXT"]);
												$arrANSWER_VALUE[$FIELD_ID][] = ToUpper($arFields["ANSWER_VALUE"]);
												$arrUSER_TEXT[$FIELD_ID][] = ToUpper($arFields["USER_TEXT"]);
												CFormResult::AddAnswer($arFields);
											}

										break;

										case "date":

											$fname = "form_".$FIELD_TYPE."_".$arAnswer["ID"];
											$ANSWER_ID = intval($arAnswer["ID"]);
											$USER_DATE = $arrVALUES[$fname];
											if (CheckDateTime($USER_DATE))
											{
												$z = CFormAnswer::GetByID($ANSWER_ID);
												if ($zr = $z->Fetch())
												{
													$arFields = array(
														"RESULT_ID"			=> $RESULT_ID,
														"FORM_ID"			=> $WEB_FORM_ID,
														"FIELD_ID"			=> $FIELD_ID,
														"ANSWER_ID"			=> $ANSWER_ID,
														"ANSWER_TEXT"		=> trim($zr["MESSAGE"]),
														"ANSWER_VALUE"		=> $zr["VALUE"],
														"USER_DATE"			=> $USER_DATE,
														"USER_TEXT"			=> $USER_DATE
													);
													$arrANSWER_TEXT[$FIELD_ID][] = ToUpper($arFields["ANSWER_TEXT"]);
													$arrANSWER_VALUE[$FIELD_ID][] = ToUpper($arFields["ANSWER_VALUE"]);
													$arrUSER_TEXT[$FIELD_ID][] = ToUpper($arFields["USER_TEXT"]);
													CFormResult::AddAnswer($arFields);
												}
											}
											break;

										case "image":

											$fname = "form_".$FIELD_TYPE."_".$arAnswer["ID"];
											$ANSWER_ID = intval($arAnswer["ID"]);
											$arIMAGE = isset($arrVALUES[$fname]) ? $arrVALUES[$fname] : $HTTP_POST_FILES[$fname];
											$arIMAGE["MODULE_ID"] = "form";
											$fid = 0;
											if (strlen(CFile::CheckImageFile($arIMAGE))<=0)
											{
												if (strlen($arIMAGE["name"])>0)
												{
													$fid = CFile::SaveFile($arIMAGE, "form");
													$fid = intval($fid);
													if ($fid>0)
													{
														$md5 = md5(uniqid(mt_rand(), true).time());
														$z = CFormAnswer::GetByID($ANSWER_ID);
														if ($zr = $z->Fetch())
														{
															$arFields = array(
																"RESULT_ID"				=> $RESULT_ID,
																"FORM_ID"				=> $WEB_FORM_ID,
																"FIELD_ID"				=> $FIELD_ID,
																"ANSWER_ID"				=> $ANSWER_ID,
																"ANSWER_TEXT"			=> trim($zr["MESSAGE"]),
																"ANSWER_VALUE"			=> $zr["VALUE"],
																"USER_TEXT"				=> $arIMAGE["name"],
																"USER_FILE_ID"			=> $fid,
																"USER_FILE_IS_IMAGE"	=> "Y",
																"USER_FILE_HASH"		=> $md5,
																"USER_FILE_NAME"		=> $arIMAGE["name"],
																"USER_FILE_SIZE"		=> $arIMAGE["size"],
															);
															$arrANSWER_TEXT[$FIELD_ID][] = ToUpper($arFields["ANSWER_TEXT"]);
															$arrANSWER_VALUE[$FIELD_ID][] = ToUpper($arFields["ANSWER_VALUE"]);
															$arrUSER_TEXT[$FIELD_ID][] = ToUpper($arFields["USER_TEXT"]);
															CFormResult::AddAnswer($arFields);
														}
													}
												}
											}

										break;

										case "file":

											$fname = "form_".$FIELD_TYPE."_".$arAnswer["ID"];
											$ANSWER_ID = intval($arAnswer["ID"]);
											$arFILE = isset($arrVALUES[$fname]) ? $arrVALUES[$fname] : $HTTP_POST_FILES[$fname];
											$arFILE["MODULE_ID"] = "form";

											if (strlen($arFILE["name"])>0)
											{
												$original_name = $arFILE["name"];
												$fid = 0;
												$max_size = COption::GetOptionString("form", "MAX_FILESIZE");
												$upload_dir = COption::GetOptionString("form", "NOT_IMAGE_UPLOAD_DIR");

												$fid = CFile::SaveFile($arFILE, $upload_dir);
												$fid = intval($fid);
												if ($fid>0)
												{
													$md5 = md5(uniqid(mt_rand(), true).time());
													$z = CFormAnswer::GetByID($ANSWER_ID);
													if ($zr = $z->Fetch())
													{
														$arFields = array(
															"RESULT_ID"				=> $RESULT_ID,
															"FORM_ID"				=> $WEB_FORM_ID,
															"FIELD_ID"				=> $FIELD_ID,
															"ANSWER_ID"				=> $ANSWER_ID,
															"ANSWER_TEXT"			=> trim($zr["MESSAGE"]),
															"ANSWER_VALUE"			=> $zr["VALUE"],
															"USER_TEXT"				=> $original_name,
															"USER_FILE_ID"			=> $fid,
															"USER_FILE_NAME"		=> $original_name,
															"USER_FILE_IS_IMAGE"	=> "N",
															"USER_FILE_HASH"		=> $md5,
															"USER_FILE_SUFFIX"		=> $fes,
															"USER_FILE_SIZE"		=> $arFILE["size"],
														);
														$arrANSWER_TEXT[$FIELD_ID][] = ToUpper($arFields["ANSWER_TEXT"]);
														$arrANSWER_VALUE[$FIELD_ID][] = ToUpper($arFields["ANSWER_VALUE"]);
														$arrUSER_TEXT[$FIELD_ID][] = ToUpper($arFields["USER_TEXT"]);
														CFormResult::AddAnswer($arFields);
													}
												}
											}

										break;

									endswitch;
								}
								// update search fields
								$arrANSWER_TEXT_upd = $arrANSWER_TEXT[$FIELD_ID];
								$arrANSWER_VALUE_upd = $arrANSWER_VALUE[$FIELD_ID];
								$arrUSER_TEXT_upd = $arrUSER_TEXT[$FIELD_ID];
								TrimArr($arrANSWER_TEXT_upd);
								TrimArr($arrANSWER_VALUE_upd);
								TrimArr($arrUSER_TEXT_upd);
								if (is_array($arrANSWER_TEXT_upd)) $vl_ANSWER_TEXT = trim(implode(" ",$arrANSWER_TEXT_upd));
								if (is_array($arrANSWER_VALUE_upd)) $vl_ANSWER_VALUE = trim(implode(" ",$arrANSWER_VALUE_upd));
								if (is_array($arrUSER_TEXT_upd)) $vl_USER_TEXT = trim(implode(" ",$arrUSER_TEXT_upd));
								if (strlen($vl_ANSWER_TEXT)<=0) $vl_ANSWER_TEXT = false;
								if (strlen($vl_ANSWER_VALUE)<=0) $vl_ANSWER_VALUE = false;
								if (strlen($vl_USER_TEXT)<=0) $vl_USER_TEXT = false;
								$arFields = array(
									"ANSWER_TEXT_SEARCH"	=> $vl_ANSWER_TEXT,
									"ANSWER_VALUE_SEARCH"	=> $vl_ANSWER_VALUE,
									"USER_TEXT_SEARCH"		=> $vl_USER_TEXT
									);
								CFormResult::UpdateField($arFields, $RESULT_ID, $FIELD_ID);
							}
						}

						$dbEvents = GetModuleEvents('form', 'onAfterResultAdd');
						while ($arEvent = $dbEvents->Fetch())
						{
							ExecuteModuleEventEx($arEvent, array($WEB_FORM_ID, $RESULT_ID));
						}

						// call change status handler
						CForm::ExecHandlerAfterChangeStatus($RESULT_ID, "ADD");
					}
				}
			}
		}
		return intval($RESULT_ID)>0 ? intval($RESULT_ID) : false;
	}

	// update result
	
	/**
	* <p>Обновляет все значения <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответов</a> и <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">полей</a> <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a> веб-формы. В случае успеха возвращает "true", в противном случае - "false".</p>
	*
	*
	* @param int $result_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>.
	*
	* @param array $values = false Массив со значениями <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответов</a> и <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">полей</a> веб-формы. Массив имеет
	* следующую структуру: <pre>array( "<i>имя HTML поля 1</i>" =&gt; "<i>значение 1</i>",
	* "<i>имя HTML поля 2</i>" =&gt; "<i>значение 2</i>", ... "<i>имя HTML поля N</i>" =&gt;
	* "<i>значение N</i>" )</pre> Правила формирования "<i>имен HTML полей</i>" и
	* "<i>значений</i>" можно посмотреть <a
	* href="http://dev.1c-bitrix.ru/api_help/form/htmlnames.php">здесь</a>. <h5>Пример:</h5> <pre
	* style="height:450px">Array ( [form_text_586] =&gt; Иванов Иван Иванович [form_date_587] =&gt;
	* 10.03.1992 [form_textarea_588] =&gt; г. Мурманск [form_radio_VS_MARRIED] =&gt; 589 [form_checkbox_VS_INTEREST]
	* =&gt; Array ( [0] =&gt; 592 [1] =&gt; 593 [2] =&gt; 594 ) [form_dropdown_VS_AGE] =&gt; 597
	* [form_multiselect_VS_EDUCATION] =&gt; Array ( [0] =&gt; 603 [1] =&gt; 604 ) [form_text_606] =&gt; 2345 [form_image_607]
	* =&gt; 1045 [form_textarea_ADDITIONAL_149] =&gt; 155 ) </pre> Параметр необязательный. По
	* умолчанию - "false" (будет взят стандартный массив $_REQUEST).
	*
	* @param string $update_fields = "N" Флаг необходимости обновления <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">полей</a> веб-формы. Возможны
	* следующие значения: <ul> <li> <b>Y</b> - необходимо обновить; </li> <li> <b>N</b> -
	* не нужно обновлять. </li> </ul> Параметр необязательный. По умолчанию -
	* "N" (не нужно обновлять).
	*
	* @param string $check_rights = "Y" Флаг необходимости проверки прав текущего пользователя.
	* Возможны следующие значения: <ul> <li> <b>Y</b> - права необходимо
	* проверить; </li> <li> <b>N</b> - права не нужно проверять. </li> </ul> Для
	* успешного обновления <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a> необходимо
	* обладать следующими <a
	* href="http://dev.1c-bitrix.ru/api_help/form/permissions.php">правами</a>: <ol> <li>На веб-форму, к
	* которой принадлежит редактируемый результат: <br><br><b>[20] Работа со
	* всеми результатами в соответствии с их статусами</b> <br><br>или, в
	* случае, если вы являетесь создателем редактируемого результата,
	* достаточно права: <br><br><b>[15] Работа со своим результатом в
	* соответствии с его статусом</b> <br> </li> <li>На статус, в котором
	* находится редактируемый результат, необходимо иметь право:
	* <br><br><b>[EDIT] редактирование</b> </li> </ol> Параметр необязательный. По
	* умолчанию - "Y" (права необходимо проверить).
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // ID результата
	* $RESULT_ID = 186;
	* 
	* // массив описывающий загруженную на сервер фотографию
	* $arImage = CFile::MakeFileArray($_SERVER["DOCUMENT_ROOT"]."/images/photo.gif");
	* 
	* // массив значений ответов и полей веб-формы
	* $arValues = array (
	*     "form_text_586"                 =&gt; "Иванов Иван",    // "Фамилия, имя, отчество"
	*     "form_date_587"                 =&gt; "01.06.1904",     // "Дата рождения"
	*     "form_textarea_588"             =&gt; "г. Москва",      // "Адрес"
	*     "form_radio_VS_MARRIED"         =&gt; 590,              // "Женаты/замужем?"
	*     "form_checkbox_VS_INTEREST"     =&gt; array(612, 613),  // "Увлечения"
	*     "form_dropdown_VS_AGE"          =&gt; 601,              // "Возраст"
	*     "form_multiselect_VS_EDUCATION" =&gt; array(602, 603),  // "Образование"
	*     "form_text_606"                 =&gt; 300,              // "Доход"
	*     "form_image_607"                =&gt; $arImage,         // "Фотография"
	*     "form_textarea_ADDITIONAL_149"  =&gt; "155 рублей"      // "Рассчитанная сумма"
	* )
	* 
	* //обновим результат
	* if (<b>CFormResult::Update</b>($RESULT_ID, $arValues, "Y"))
	* {
	*     echo "Результат #".$RESULT_ID." успешно обновлен.";
	* }
	* else
	* {
	*     global $strError;
	*     echo $strError;
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/setfield.php">CFormResult::SetField</a>
	* </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/getdatabyidforhtml.php">CFormResult::GetDataByIDForHTML</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/add.php">CFormResult::Add</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/update.php
	* @author Bitrix
	*/
	public static function Update($RESULT_ID, $arrVALUES=false, $UPDATE_ADDITIONAL="N", $CHECK_RIGHTS="Y")
	{
		$err_mess = (CAllFormResult::err_mess())."<br>Function: Update<br>Line: ";
		global $DB, $USER, $_REQUEST, $HTTP_POST_VARS, $HTTP_GET_VARS, $HTTP_POST_FILES, $strError, $APPLICATION;
		if ($arrVALUES===false) $arrVALUES = $_REQUEST;

		InitBvar($UPDATE_ADDITIONAL);
		// check whether such result exists in db
		$RESULT_ID = intval($RESULT_ID);
		$z = CFormResult::GetByID($RESULT_ID);
		if ($zr=$z->Fetch())
		{
			$arrResult = $zr;
			$additional = ($UPDATE_ADDITIONAL=="Y") ? "ALL" : "N";
			// get form data
			$WEB_FORM_ID = CForm::GetDataByID($arrResult["FORM_ID"], $arForm, $arQuestions, $arAnswers, $arDropDown, $arMultiSelect, $additional);
			if ($WEB_FORM_ID>0)
			{
				// check form rights
				$F_RIGHT = ($CHECK_RIGHTS!="Y") ? 30 : intval(CForm::GetPermission($WEB_FORM_ID));
				if ($F_RIGHT>=20 || ($F_RIGHT>=15 && $arrResult["USER_ID"]==$USER->GetID()))
				{
					// check result rights (its status rights)
					$arrRESULT_PERMISSION = ($CHECK_RIGHTS!="Y") ? CFormStatus::GetMaxPermissions() : CFormResult::GetPermissions($RESULT_ID, $v);

					// if  rights're correct
					if (in_array("EDIT", $arrRESULT_PERMISSION))
					{
						// update result
						$arFields = array("TIMESTAMP_X"	=> $DB->GetNowFunction());
						$fname = "status_".$arForm["SID"];
						$STATUS_ID = intval($arrVALUES[$fname]);

						$bUpdateStatus = false;
						// if there's new status defined
						if (intval($STATUS_ID)>0)
						{
							// check new status rights
							$arrNEW_STATUS_PERMISSION = ($CHECK_RIGHTS!="Y") ? CFormStatus::GetMaxPermissions() : CFormStatus::GetPermissions($STATUS_ID);

							// if rights're correct
							if (in_array("MOVE",$arrNEW_STATUS_PERMISSION))
							{
								// update it
								$bUpdateStatus = true;
								$arFields["STATUS_ID"] = intval($arrVALUES[$fname]);
							}
						}

						if ($bUpdateStatus)
						{
							$dbEvents = GetModuleEvents('form', 'onBeforeResultStatusChange');
							while ($arEvent = $dbEvents->Fetch())
							{
								ExecuteModuleEventEx($arEvent, array($WEB_FORM_ID, $RESULT_ID, &$arFields["STATUS_ID"], $CHECK_RIGHTS));

								if ($ex = $APPLICATION->GetException())
									$strError .= $ex->GetString().'<br />';
							}
						}

						if (strlen($strError) <= 0)
						{
							// call status change handler
							CForm::ExecHandlerBeforeChangeStatus($RESULT_ID, "UPDATE", $arFields["STATUS_ID"]);

							$dbEvents = GetModuleEvents('form', 'onBeforeResultUpdate');
							while ($arEvent = $dbEvents->Fetch())
							{
								ExecuteModuleEventEx($arEvent, array($WEB_FORM_ID, $RESULT_ID, &$arFields, &$arrVALUES, $CHECK_RIGHTS));

								if ($ex = $APPLICATION->GetException())
									$strError .= $ex->GetString().'<br />';
							}
						}

						$rows = 0;

						if (strlen($strError) <= 0)
							$rows = $DB->Update("b_form_result", $arFields,"WHERE ID='".$RESULT_ID."'",$err_mess.__LINE__);

						if ($bUpdateStatus)
						{
							$dbEvents = GetModuleEvents('form', 'onAfterResultStatusChange');
							while ($arEvent = $dbEvents->Fetch())
							{
								ExecuteModuleEventEx($arEvent, array($WEB_FORM_ID, $RESULT_ID, &$arFields["STATUS_ID"], $CHECK_RIGHTS));
							}
						}

						// if update was successful
						if (intval($rows)>0)
						{
							$arrException = array();

							// gather files info
							$arrFILES = array();
							$strSql = "
								SELECT
									ANSWER_ID,
									USER_FILE_ID,
									USER_FILE_NAME,
									USER_FILE_IS_IMAGE,
									USER_FILE_HASH,
									USER_FILE_SUFFIX,
									USER_FILE_SIZE
								FROM
									b_form_result_answer
								WHERE
									RESULT_ID = $RESULT_ID
								and USER_FILE_ID>0
								";
							$q = $DB->Query($strSql,false,$err_mess.__LINE__);
							while ($qr = $q->Fetch()) $arrFILES[$qr["ANSWER_ID"]] = $qr;

							if (is_array($arrVALUES["ARR_CLS"])) $arrException = array_merge($arrException, $arrVALUES["ARR_CLS"]);

							// clear all questions and answers  for current result
							CFormResult::Reset($RESULT_ID, false, $UPDATE_ADDITIONAL, $arrException);

							// trace questions and additional fields
							foreach ($arQuestions as $arQuestion)
							{
								$FIELD_ID = $arQuestion["ID"];
								if (is_array($arrException) && count($arrException)>0)
								{
									if (in_array($FIELD_ID, $arrException)) continue;
								}
								$FIELD_SID = $arQuestion["SID"];
								if ($arQuestion["ADDITIONAL"]!="Y")
								{
									// update form questions
									$arrANSWER_TEXT = array();
									$arrANSWER_VALUE = array();
									$arrUSER_TEXT = array();
									$radio = "N";
									$checkbox = "N";
									$multiselect = "N";
									$dropdown = "N";
									// trace answers
									if (is_array($arAnswers[$FIELD_SID]))
									{
										foreach ($arAnswers[$FIELD_SID] as $key => $arAnswer)
										{
											$ANSWER_ID = 0;
											$FIELD_TYPE = $arAnswer["FIELD_TYPE"];
											$FIELD_PARAM = $arAnswer["FIELD_PARAM"];
											switch ($FIELD_TYPE) :

												case "radio":
												case "dropdown":

													if (($radio=="N" && $FIELD_TYPE=="radio") ||
														($dropdown=="N" && $FIELD_TYPE=="dropdown"))
													{
														$fname = "form_".$FIELD_TYPE."_".$FIELD_SID;
														$ANSWER_ID = intval($arrVALUES[$fname]);
														if ($ANSWER_ID>0)
														{
															$z = CFormAnswer::GetByID($ANSWER_ID);
															if ($zr = $z->Fetch())
															{
																$arFields = array(
																	"RESULT_ID"			=> $RESULT_ID,
																	"FORM_ID"			=> $WEB_FORM_ID,
																	"FIELD_ID"			=> $FIELD_ID,
																	"ANSWER_ID"			=> $ANSWER_ID,
																	"ANSWER_TEXT"		=> trim($zr["MESSAGE"]),
																	"ANSWER_VALUE"		=> $zr["VALUE"]
																);
																$arrANSWER_TEXT[$FIELD_ID][] = ToUpper($arFields["ANSWER_TEXT"]);
																$arrANSWER_VALUE[$FIELD_ID][] = ToUpper($arFields["ANSWER_VALUE"]);
																CFormResult::AddAnswer($arFields);
															}
															if ($FIELD_TYPE=="radio") $radio = "Y";
															if ($FIELD_TYPE=="dropdown") $dropdown = "Y";
														}
													}

												break;

												case "checkbox":
												case "multiselect":

													if (($checkbox=="N" && $FIELD_TYPE=="checkbox") ||
														($multiselect=="N" && $FIELD_TYPE=="multiselect"))
													{
														$fname = "form_".$FIELD_TYPE."_".$FIELD_SID;
														if (is_array($arrVALUES[$fname]) && count($arrVALUES[$fname])>0)
														{
															foreach($arrVALUES[$fname] as $ANSWER_ID)
															{
																$ANSWER_ID = intval($ANSWER_ID);
																if ($ANSWER_ID>0)
																{
																	$z = CFormAnswer::GetByID($ANSWER_ID);
																	if ($zr = $z->Fetch())
																	{
																		$arFields = array(
																		"RESULT_ID"			=> $RESULT_ID,
																		"FORM_ID"			=> $WEB_FORM_ID,
																		"FIELD_ID"			=> $FIELD_ID,
																		"ANSWER_ID"			=> $ANSWER_ID,
																		"ANSWER_TEXT"		=> trim($zr["MESSAGE"]),
																		"ANSWER_VALUE"		=> $zr["VALUE"]
																		);
																		$arrANSWER_TEXT[$FIELD_ID][] = ToUpper($arFields["ANSWER_TEXT"]);
																		$arrANSWER_VALUE[$FIELD_ID][] = ToUpper($arFields["ANSWER_VALUE"]);
																		CFormResult::AddAnswer($arFields);
																	}
																}
															}
															if ($FIELD_TYPE=="checkbox") $checkbox = "Y";
															if ($FIELD_TYPE=="multiselect") $multiselect = "Y";
														}
													}

												break;

												case "text":
												case "textarea":
												case "password":
												case "email":
												case "url":
												case "hidden":
													$fname = "form_".$FIELD_TYPE."_".$arAnswer["ID"];
													$ANSWER_ID = intval($arAnswer["ID"]);
													$z = CFormAnswer::GetByID($ANSWER_ID);
													if ($zr = $z->Fetch())
													{
														$arFields = array(
															"RESULT_ID"			=> $RESULT_ID,
															"FORM_ID"			=> $WEB_FORM_ID,
															"FIELD_ID"			=> $FIELD_ID,
															"ANSWER_ID"			=> $ANSWER_ID,
															"ANSWER_TEXT"		=> trim($zr["MESSAGE"]),
															"ANSWER_VALUE"		=> $zr["VALUE"],
															"USER_TEXT"			=> $arrVALUES[$fname]
														);
														$arrANSWER_TEXT[$FIELD_ID][] = ToUpper($arFields["ANSWER_TEXT"]);
														$arrANSWER_VALUE[$FIELD_ID][] = ToUpper($arFields["ANSWER_VALUE"]);
														$arrUSER_TEXT[$FIELD_ID][] = ToUpper($arFields["USER_TEXT"]);
														CFormResult::AddAnswer($arFields);
													}

												break;

												case "date":

													$fname = "form_".$FIELD_TYPE."_".$arAnswer["ID"];
													$ANSWER_ID = intval($arAnswer["ID"]);
													$USER_DATE = $arrVALUES[$fname];
													if (CheckDateTime($USER_DATE))
													{
														$z = CFormAnswer::GetByID($ANSWER_ID);
														if ($zr = $z->Fetch())
														{
															$arFields = array(
																"RESULT_ID"			=> $RESULT_ID,
																"FORM_ID"			=> $WEB_FORM_ID,
																"FIELD_ID"			=> $FIELD_ID,
																"ANSWER_ID"			=> $ANSWER_ID,
																"ANSWER_TEXT"		=> trim($zr["MESSAGE"]),
																"ANSWER_VALUE"		=> $zr["VALUE"],
																"USER_DATE"			=> $USER_DATE,
																"USER_TEXT"			=> $USER_DATE
															);
															$arrANSWER_TEXT[$FIELD_ID][] = ToUpper($arFields["ANSWER_TEXT"]);
															$arrANSWER_VALUE[$FIELD_ID][] = ToUpper($arFields["ANSWER_VALUE"]);
															$arrUSER_TEXT[$FIELD_ID][] = ToUpper($arFields["USER_TEXT"]);
															CFormResult::AddAnswer($arFields);
														}
													}
													break;

												case "image":

													$fname = "form_".$FIELD_TYPE."_".$arAnswer["ID"];
													$ANSWER_ID = intval($arAnswer["ID"]);
													$arIMAGE = isset($arrVALUES[$fname]) ? $arrVALUES[$fname] : $HTTP_POST_FILES[$fname];
													$arIMAGE["old_file"] = $arrFILES[$ANSWER_ID]["USER_FILE_ID"];
													$arIMAGE["del"] = $arrVALUES[$fname."_del"];
													$arIMAGE["MODULE_ID"] = "form";
													$fid = 0;
													if (strlen($arIMAGE["name"])>0 || strlen($arIMAGE["del"])>0)
													{
														$new_file="Y";
														if (strlen($arIMAGE["del"])>0 || strlen(CFile::CheckImageFile($arIMAGE))<=0)
														{
															$fid = CFile::SaveFile($arIMAGE, "form");
														}
													}
													else $fid = $arrFILES[$ANSWER_ID]["USER_FILE_ID"];

													$fid = intval($fid);
													if ($fid>0)
													{
														$z = CFormAnswer::GetByID($ANSWER_ID);
														if ($zr = $z->Fetch())
														{
															$arFields = array(
																"RESULT_ID"				=> $RESULT_ID,
																"FORM_ID"				=> $WEB_FORM_ID,
																"FIELD_ID"				=> $FIELD_ID,
																"ANSWER_ID"				=> $ANSWER_ID,
																"ANSWER_TEXT"			=> trim($zr["MESSAGE"]),
																"ANSWER_VALUE"			=> $zr["VALUE"],
																"USER_FILE_ID"			=> $fid,
																"USER_FILE_IS_IMAGE"	=> "Y"
																);
															if ($new_file=="Y")
															{
																$arFields["USER_FILE_NAME"] = $arIMAGE["name"];
																$arFields["USER_FILE_SIZE"] = $arIMAGE["size"];
																$arFields["USER_FILE_HASH"] = md5(uniqid(mt_rand(), true).time());

															}
															else
															{
																$arFields["USER_FILE_NAME"] = $arrFILES[$ANSWER_ID]["USER_FILE_NAME"];
																$arFields["USER_FILE_SIZE"] = $arrFILES[$ANSWER_ID]["USER_FILE_SIZE"];
																$arFields["USER_FILE_HASH"] = $arrFILES[$ANSWER_ID]["USER_FILE_HASH"];
															}
															$arFields["USER_TEXT"] = $arFields["USER_FILE_NAME"];

															$arrANSWER_TEXT[$FIELD_ID][] = ToUpper($arFields["ANSWER_TEXT"]);
															$arrANSWER_VALUE[$FIELD_ID][] = ToUpper($arFields["ANSWER_VALUE"]);
															$arrUSER_TEXT[$FIELD_ID][] = ToUpper($arFields["USER_TEXT"]);
															CFormResult::AddAnswer($arFields);
														}
													}

												break;

												case "file":

													$fname = "form_".$FIELD_TYPE."_".$arAnswer["ID"];
													$ANSWER_ID = intval($arAnswer["ID"]);
													$arFILE = isset($arrVALUES[$fname]) ? $arrVALUES[$fname] : $HTTP_POST_FILES[$fname];
													$arFILE["old_file"] = $arrFILES[$ANSWER_ID]["USER_FILE_ID"];
													$arFILE["del"] = $arrVALUES[$fname."_del"];
													$arFILE["MODULE_ID"] = "form";
													$new_file="N";
													$fid = 0;
													if (strlen(trim($arFILE["name"]))>0 || strlen(trim($arFILE["del"]))>0)
													{
														$new_file="Y";
														$original_name = $arFILE["name"];
														$max_size = COption::GetOptionString("form", "MAX_FILESIZE");
														$upload_dir = COption::GetOptionString("form", "NOT_IMAGE_UPLOAD_DIR");

														$fid = CFile::SaveFile($arFILE, $upload_dir, $max_size);
													}
													else $fid = $arrFILES[$ANSWER_ID]["USER_FILE_ID"];

													$fid = intval($fid);

													if ($fid>0)
													{
														$z = CFormAnswer::GetByID($ANSWER_ID);
														if ($zr = $z->Fetch())
														{
															$arFields = array(
																"RESULT_ID"				=> $RESULT_ID,
																"FORM_ID"				=> $WEB_FORM_ID,
																"FIELD_ID"				=> $FIELD_ID,
																"ANSWER_ID"				=> $ANSWER_ID,
																"ANSWER_TEXT"			=> trim($zr["MESSAGE"]),
																"ANSWER_VALUE"			=> $zr["VALUE"],
																"USER_FILE_ID"			=> $fid,
															);
															if ($new_file=="Y")
															{
																$arFields["USER_FILE_NAME"] = $original_name;
																$arFields["USER_FILE_IS_IMAGE"] = "N";
																$arFields["USER_FILE_HASH"] = md5(uniqid(mt_rand(), true).time());
																$arFields["USER_FILE_SUFFIX"] = $suffix;
																$arFields["USER_FILE_SIZE"] = $arFILE["size"];
															}
															else
															{
																$arFields["USER_FILE_NAME"] = $arrFILES[$ANSWER_ID]["USER_FILE_NAME"];
																$arFields["USER_FILE_IS_IMAGE"] = $arrFILES[$ANSWER_ID]["USER_FILE_IS_IMAGE"];
																$arFields["USER_FILE_HASH"] = $arrFILES[$ANSWER_ID]["USER_FILE_HASH"];
																$arFields["USER_FILE_SUFFIX"] = $arrFILES[$ANSWER_ID]["USER_FILE_SUFFIX"];
																$arFields["USER_FILE_SIZE"] = $arrFILES[$ANSWER_ID]["USER_FILE_SIZE"];
															}
															$arFields["USER_TEXT"] = $arFields["USER_FILE_NAME"];

															$arrANSWER_TEXT[$FIELD_ID][] = ToUpper($arFields["ANSWER_TEXT"]);
															$arrANSWER_VALUE[$FIELD_ID][] = ToUpper($arFields["ANSWER_VALUE"]);
															$arrUSER_TEXT[$FIELD_ID][] = ToUpper($arFields["USER_TEXT"]);
															CFormResult::AddAnswer($arFields);
														}
													}

												break;

											endswitch;
										}
									}
									// update fields for searching
									$arrANSWER_TEXT_upd = $arrANSWER_TEXT[$FIELD_ID];
									$arrANSWER_VALUE_upd = $arrANSWER_VALUE[$FIELD_ID];
									$arrUSER_TEXT_upd = $arrUSER_TEXT[$FIELD_ID];
									TrimArr($arrANSWER_TEXT_upd);
									TrimArr($arrANSWER_VALUE_upd);
									TrimArr($arrUSER_TEXT_upd);
									if (is_array($arrANSWER_TEXT_upd)) $vl_ANSWER_TEXT = trim(implode(" ",$arrANSWER_TEXT_upd));
									if (is_array($arrANSWER_VALUE_upd)) $vl_ANSWER_VALUE = trim(implode(" ",$arrANSWER_VALUE_upd));
									if (is_array($arrUSER_TEXT_upd)) $vl_USER_TEXT = trim(implode(" ",$arrUSER_TEXT_upd));
									if (strlen($vl_ANSWER_TEXT)<=0) $vl_ANSWER_TEXT = false;
									if (strlen($vl_ANSWER_VALUE)<=0) $vl_ANSWER_VALUE = false;
									if (strlen($vl_USER_TEXT)<=0) $vl_USER_TEXT = false;
									$arFields = array(
										"ANSWER_TEXT_SEARCH"	=> $vl_ANSWER_TEXT,
										"ANSWER_VALUE_SEARCH"	=> $vl_ANSWER_VALUE,
										"USER_TEXT_SEARCH"		=> $vl_USER_TEXT
										);
									CFormResult::UpdateField($arFields, $RESULT_ID, $FIELD_ID);
								}
								else // update additional fields
								{
									$FIELD_TYPE = $arQuestion["FIELD_TYPE"];
									switch ($FIELD_TYPE) :

										case "text":
											$fname = "form_textarea_ADDITIONAL_".$arQuestion["ID"];
											$arFields = array(
												"RESULT_ID"			=> $RESULT_ID,
												"FORM_ID"			=> $WEB_FORM_ID,
												"FIELD_ID"			=> $FIELD_ID,
												"USER_TEXT"			=> $arrVALUES[$fname],
												"USER_TEXT_SEARCH"	=> ToUpper($arrVALUES[$fname])
											);
											CFormResult::AddAnswer($arFields);
											break;

										case "integer":

											$fname = "form_text_ADDITIONAL_".$arQuestion["ID"];
											$arFields = array(
												"RESULT_ID"			=> $RESULT_ID,
												"FORM_ID"			=> $WEB_FORM_ID,
												"FIELD_ID"			=> $FIELD_ID,
												"USER_TEXT"			=> $arrVALUES[$fname],
												"USER_TEXT_SEARCH"	=> ToUpper($arrVALUES[$fname])
											);
											CFormResult::AddAnswer($arFields);

										break;

										case "date":

											$fname = "form_date_ADDITIONAL_".$arQuestion["ID"];
											$USER_DATE = $arrVALUES[$fname];
											if (CheckDateTime($USER_DATE))
											{
												$arFields = array(
													"RESULT_ID"			=> $RESULT_ID,
													"FORM_ID"			=> $WEB_FORM_ID,
													"FIELD_ID"			=> $FIELD_ID,
													"USER_DATE"			=> $USER_DATE,
													"USER_TEXT"			=> $USER_DATE,
													"USER_TEXT_SEARCH"	=> ToUpper($USER_DATE)
												);
												CFormResult::AddAnswer($arFields);
											}

										break;
									endswitch;
								}
							}

							$dbEvents = GetModuleEvents('form', 'onAfterResultUpdate');
							while ($arEvent = $dbEvents->Fetch())
							{
								ExecuteModuleEventEx($arEvent, array($WEB_FORM_ID, $RESULT_ID, $CHECK_RIGHTS));
							}

							// call "after status update" handler
							CForm::ExecHandlerAfterChangeStatus($RESULT_ID, "UPDATE");
							return true;
						}
					}
				}
			}
		}
		return false;
	}

	// set question or field value in existed result
	
	/**
	* <p>Для указанного <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a> обновляет значения <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> на <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопрос</a> или обновляет значение <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a>.</p>
	*
	*
	* @param int $result_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>.
	*
	* @param string $field_sid  
	*
	* @param mixed $value = false Символьный идентификатор <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопроса</a> или <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">поля</a>.
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre>
	* $RESULT_ID = 186;
	* 
	* //<*************************************************************
	*             Обновление значений ответов на вопросы
	* *************************************************************>//
	* 
	* // обновим ответ на вопрос "Фамилия, имя, отчество"
	* $arVALUE = array();
	* $FIELD_SID = "VS_NAME"; // символьный идентификатор вопроса
	* $ANSWER_ID = 586; // ID поля ответа
	* $arVALUE[$ANSWER_ID] = "Иванов Иван";
	* <b>CFormResult::SetField</b>($RESULT_ID, $FIELD_SID, $arVALUE);
	* 
	* // обновим ответ на вопрос "Дата рождения"
	* $arVALUE = array();
	* $FIELD_SID = "VS_BIRTHDAY"; // символьный идентификатор вопроса
	* $ANSWER_ID = 587; // ID поля ответа
	* $arVALUE[$ANSWER_ID] = "18.06.1975";
	* <b>CFormResult::SetField</b>($RESULT_ID, $FIELD_SID, $arVALUE);
	* 
	* // обновим ответ на вопрос "Какие области знаний вас интересуют?"
	* $arVALUE = array();
	* $FIELD_SID = "VS_INTEREST"; // символьный идентификатор вопроса
	* $arVALUE[612] = ""; // ID поля ответа "математика"
	* $arVALUE[613] = ""; // ID поля ответа "физика"
	* $arVALUE[614] = ""; // ID поля ответа "история"
	* <b>CFormResult::SetField</b>($RESULT_ID, $FIELD_SID, $arVALUE);
	* 
	* // обновим ответ на вопрос "Фотография"
	* $arVALUE = array();
	* $FIELD_SID = "VS_PHOTO"; // символьный идентификатор вопроса
	* $ANSWER_ID = 607; // ID поля ответа
	* $path = $_SERVER["DOCUMENT_ROOT"]."/images/news.gif"; // путь к файлу
	* $arVALUE[$ANSWER_ID] = CFile::MakeFileArray($path);
	* <b>CFormResult::SetField</b>($RESULT_ID, $FIELD_SID, $arVALUE);
	* 
	* // обновим ответ на вопрос "Резюме"
	* $arVALUE = array();
	* $FIELD_SID = "VS_RESUME"; // символьный идентификатор вопроса
	* $ANSWER_ID = 610; // ID поля ответа
	* $path = $_SERVER["DOCUMENT_ROOT"]."/docs/alawarauthorarea.doc"; // путь к файлу
	* $arVALUE[$ANSWER_ID] = CFile::MakeFileArray($path);
	* <b>CFormResult::SetField</b>($RESULT_ID, $FIELD_SID, $arVALUE);
	* 
	* //<*************************************************************
	*                 Обновление значений полей
	* *************************************************************>//
	* 
	* // обновим значение поля "Рассчитанная стоимость"
	* $FIELD_SID = "VS_PRICE"; // символьный идентификатор вопроса
	* $VALUE = "155";
	* <b>CFormResult::SetField</b>($RESULT_ID, $FIELD_SID, $VALUE);
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/update.php">CFormResult::Update</a> </li>
	* <li><a href="http://dev.1c-bitrix.ru/api_help/main/reference/cfile/makefilearray.php">CFile::MakeFileArray</a></li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/setfield.php
	* @author Bitrix
	*/
	public static function SetField($RESULT_ID, $FIELD_SID, $VALUE=false)
	{
		global $DB, $strError;
		$err_mess = (CAllFormResult::err_mess())."<br>Function: SetField<br>Line: ";
		$RESULT_ID = intval($RESULT_ID);
		if (intval($RESULT_ID)>0)
		{
			$strSql = "
				SELECT
					FORM_ID
				FROM
					b_form_result
				WHERE
					ID = $RESULT_ID
				";
			$z = $DB->Query($strSql, false, $err_mess.__LINE__);
			$zr = $z->Fetch();
			$WEB_FORM_ID = $zr["FORM_ID"];
			if (intval($WEB_FORM_ID)>0)
			{
				$strSql = "
					SELECT
						ID,
						FIELD_TYPE,
						ADDITIONAL
					FROM
						b_form_field
					WHERE
						FORM_ID = $WEB_FORM_ID
					and SID = '".$DB->ForSql($FIELD_SID,50)."'
					";
				$q = $DB->Query($strSql, false, $err_mess.__LINE__);
				if ($arField = $q->Fetch())
				{
					$FIELD_ID = $arField["ID"];
					$IS_FIELD = ($arField["ADDITIONAL"]=="Y") ? true : false;

					if ($IS_FIELD)
					{
						$strSql = "
							DELETE FROM
								b_form_result_answer
							WHERE
								RESULT_ID = $RESULT_ID
							and FIELD_ID = $FIELD_ID
							";
						//echo "<pre>".$strSql."</pre>";
						$DB->Query($strSql, false, $err_mess.__LINE__);

						if (strlen($VALUE)>0)
						{

							$FIELD_TYPE = $arField["FIELD_TYPE"];
							switch ($FIELD_TYPE) :

								case "text":
								case "integer":

									$arFields = array(
										"RESULT_ID"			=> $RESULT_ID,
										"FORM_ID"			=> $WEB_FORM_ID,
										"FIELD_ID"			=> $FIELD_ID,
										"USER_TEXT"			=> $VALUE,
										"USER_TEXT_SEARCH"	=> ToUpper($VALUE)
										);
									CFormResult::AddAnswer($arFields);
								break;

								case "date":

									if (CheckDateTime($VALUE))
									{
										$arFields = array(
											"RESULT_ID"			=> $RESULT_ID,
											"FORM_ID"			=> $WEB_FORM_ID,
											"FIELD_ID"			=> $FIELD_ID,
											"USER_DATE"			=> $VALUE,
											"USER_TEXT"			=> $VALUE,
											"USER_TEXT_SEARCH"	=> ToUpper($VALUE)
											);
										CFormResult::AddAnswer($arFields);
									}
								break;

							endswitch;
						}
					}
					else
					{
						$strSql = "
							SELECT
								USER_FILE_ID
							FROM
								b_form_result_answer
							WHERE
								RESULT_ID = $RESULT_ID
							and FIELD_ID = $FIELD_ID
							and USER_FILE_ID>0
							";
						$rsFiles = $DB->Query($strSql, false, $err_mess.__LINE__);
						while ($arFile = $rsFiles->Fetch()) CFile::Delete($arFile["USER_FILE_ID"]);

						$strSql = "
							DELETE FROM
								b_form_result_answer
							WHERE
								RESULT_ID = $RESULT_ID
							and FIELD_ID = $FIELD_ID
							";
						$DB->Query($strSql, false, $err_mess.__LINE__);

						if (is_array($VALUE) && count($VALUE)>0)
						{
							$arrANSWER_TEXT = array();
							$arrANSWER_VALUE = array();
							$arrUSER_TEXT = array();
							foreach ($VALUE as $ANSWER_ID => $val)
							{
								$rsAnswer = CFormAnswer::GetByID($ANSWER_ID);
								if ($arAnswer = $rsAnswer->Fetch())
								{
									switch ($arAnswer["FIELD_TYPE"]) :

										case "radio":
										case "dropdown":
										case "checkbox":
										case "multiselect":

											$arFields = array(
												"RESULT_ID"				=> $RESULT_ID,
												"FORM_ID"				=> $WEB_FORM_ID,
												"FIELD_ID"				=> $FIELD_ID,
												"ANSWER_ID"				=> $ANSWER_ID,
												"ANSWER_TEXT"			=> trim($arAnswer["MESSAGE"]),
												"ANSWER_VALUE"			=> $arAnswer["VALUE"],
											);
											CFormResult::AddAnswer($arFields);
											$arrANSWER_TEXT[$FIELD_ID][] = ToUpper($arFields["ANSWER_TEXT"]);
											$arrANSWER_VALUE[$FIELD_ID][] = ToUpper($arFields["ANSWER_VALUE"]);

										break;

										case "text":
										case "textarea":
										case "password":
										case "email":
										case "url":
										case "hidden":

											$arFields = array(
												"RESULT_ID"				=> $RESULT_ID,
												"FORM_ID"				=> $WEB_FORM_ID,
												"FIELD_ID"				=> $FIELD_ID,
												"ANSWER_ID"				=> $ANSWER_ID,
												"ANSWER_TEXT"			=> trim($arAnswer["MESSAGE"]),
												"ANSWER_VALUE"			=> $arAnswer["VALUE"],
												"USER_TEXT"				=> $val,
											);
											CFormResult::AddAnswer($arFields);
											$arrANSWER_TEXT[$FIELD_ID][] = ToUpper($arFields["ANSWER_TEXT"]);
											$arrANSWER_VALUE[$FIELD_ID][] = ToUpper($arFields["ANSWER_VALUE"]);
											$arrUSER_TEXT[$FIELD_ID][] = ToUpper($arFields["USER_TEXT"]);

										break;

										case "date":

											if (CheckDateTime($val))
											{
												$arFields = array(
													"RESULT_ID"				=> $RESULT_ID,
													"FORM_ID"				=> $WEB_FORM_ID,
													"FIELD_ID"				=> $FIELD_ID,
													"ANSWER_ID"				=> $ANSWER_ID,
													"ANSWER_TEXT"			=> trim($arAnswer["MESSAGE"]),
													"ANSWER_VALUE"			=> $arAnswer["VALUE"],
													"USER_TEXT"				=> $val,
													"USER_DATE"				=> $val
												);
												CFormResult::AddAnswer($arFields);
												$arrANSWER_TEXT[$FIELD_ID][] = ToUpper($arFields["ANSWER_TEXT"]);
												$arrANSWER_VALUE[$FIELD_ID][] = ToUpper($arFields["ANSWER_VALUE"]);
												$arrUSER_TEXT[$FIELD_ID][] = ToUpper($arFields["USER_TEXT"]);
											}

										break;

										case "image":

											$arIMAGE = $val;
											if (is_array($arIMAGE) && count($arIMAGE)>0)
											{
												$arIMAGE["MODULE_ID"] = "form";
												if (strlen(CFile::CheckImageFile($arIMAGE))<=0)
												{
													if (!array_key_exists("MODULE_ID", $arIMAGE) || strlen($arIMAGE["MODULE_ID"]) <= 0)
														$arIMAGE["MODULE_ID"] = "form";

													$fid = CFile::SaveFile($arIMAGE, "form");
													if (intval($fid)>0)
													{
														$arFields = array(
															"RESULT_ID"				=> $RESULT_ID,
															"FORM_ID"				=> $WEB_FORM_ID,
															"FIELD_ID"				=> $FIELD_ID,
															"ANSWER_ID"				=> $ANSWER_ID,
															"ANSWER_TEXT"			=> trim($arAnswer["MESSAGE"]),
															"ANSWER_VALUE"			=> $arAnswer["VALUE"],
															"USER_FILE_ID"			=> $fid,
															"USER_FILE_IS_IMAGE"	=> "Y",
															"USER_FILE_NAME"		=> $arIMAGE["name"],
															"USER_FILE_SIZE"		=> $arIMAGE["size"],
															"USER_TEXT"				=> $arIMAGE["name"]
															);
														CFormResult::AddAnswer($arFields);
														$arrANSWER_TEXT[$FIELD_ID][] = ToUpper($arFields["ANSWER_TEXT"]);
														$arrANSWER_VALUE[$FIELD_ID][] = ToUpper($arFields["ANSWER_VALUE"]);
														$arrUSER_TEXT[$FIELD_ID][] = ToUpper($arFields["USER_TEXT"]);
													}
												}
											}

										break;

										case "file":

											$arFILE = $val;
											if (is_array($arFILE) && count($arFILE)>0)
											{
												$arFILE["MODULE_ID"] = "form";
												$original_name = $arFILE["name"];
												$max_size = COption::GetOptionString("form", "MAX_FILESIZE");
												$upload_dir = COption::GetOptionString("form", "NOT_IMAGE_UPLOAD_DIR");
												$fid = CFile::SaveFile($arFILE, $upload_dir, $max_size);
												if (intval($fid)>0)
												{
													$arFields = array(
														"RESULT_ID"				=> $RESULT_ID,
														"FORM_ID"				=> $WEB_FORM_ID,
														"FIELD_ID"				=> $FIELD_ID,
														"ANSWER_ID"				=> $ANSWER_ID,
														"ANSWER_TEXT"			=> trim($arAnswer["MESSAGE"]),
														"ANSWER_VALUE"			=> $arAnswer["VALUE"],
														"USER_FILE_ID"			=> $fid,
														"USER_FILE_IS_IMAGE"	=> "N",
														"USER_FILE_NAME"		=> $original_name,
														"USER_FILE_HASH"		=> md5(uniqid(mt_rand(), true).time()),
														"USER_FILE_SIZE"		=> $arFILE["size"],
														"USER_FILE_SUFFIX"		=> $suffix,
														"USER_TEXT"				=> $original_name,
														);
													CFormResult::AddAnswer($arFields);
													$arrANSWER_TEXT[$FIELD_ID][] = ToUpper($arFields["ANSWER_TEXT"]);
													$arrANSWER_VALUE[$FIELD_ID][] = ToUpper($arFields["ANSWER_VALUE"]);
													$arrUSER_TEXT[$FIELD_ID][] = ToUpper($arFields["USER_TEXT"]);
												}
											}

										break;

									endswitch;
								}
							}
							// update search fields
							$arrANSWER_TEXT_upd = $arrANSWER_TEXT[$FIELD_ID];
							$arrANSWER_VALUE_upd = $arrANSWER_VALUE[$FIELD_ID];
							$arrUSER_TEXT_upd = $arrUSER_TEXT[$FIELD_ID];
							TrimArr($arrANSWER_TEXT_upd);
							TrimArr($arrANSWER_VALUE_upd);
							TrimArr($arrUSER_TEXT_upd);
							if (is_array($arrANSWER_TEXT_upd)) $vl_ANSWER_TEXT = trim(implode(" ",$arrANSWER_TEXT_upd));
							if (is_array($arrANSWER_VALUE_upd)) $vl_ANSWER_VALUE = trim(implode(" ",$arrANSWER_VALUE_upd));
							if (is_array($arrUSER_TEXT_upd)) $vl_USER_TEXT = trim(implode(" ",$arrUSER_TEXT_upd));
							if (strlen($vl_ANSWER_TEXT)<=0) $vl_ANSWER_TEXT = false;
							if (strlen($vl_ANSWER_VALUE)<=0) $vl_ANSWER_VALUE = false;
							if (strlen($vl_USER_TEXT)<=0) $vl_USER_TEXT = false;
							$arFields = array(
								"ANSWER_TEXT_SEARCH"	=> $vl_ANSWER_TEXT,
								"ANSWER_VALUE_SEARCH"	=> $vl_ANSWER_VALUE,
								"USER_TEXT_SEARCH"		=> $vl_USER_TEXT
								);
							CFormResult::UpdateField($arFields, $RESULT_ID, $FIELD_ID);
						}
					}
					return true;
				}
			}
		}
		return false;
	}

	// delete result
	
	/**
	* <p>Удаляет указанный <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результат</a>. В случае успеха метод возвращает "true", иначе - "false".</p>
	*
	*
	* @param int $result_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>.
	*
	* @param string $check_rights = "Y" Флаг необходимости проверки прав текущего пользователя.
	* Возможны следующие значения: <ul> <li> <b>Y</b> - права необходимо
	* проверить; </li> <li> <b>N</b> - права не нужно проверять. </li> </ul> Для
	* успешного удаления <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a> необходимо
	* обладать следующими <a
	* href="http://dev.1c-bitrix.ru/api_help/form/permissions.php">правами</a>: <ol> <li>На веб-форму, к
	* которой принадлежит редактируемый результат: <br><br><b>[20] Работа со
	* всеми результатами в соответствии с их статусами</b> <br><br>или, в
	* случае, если вы являетесь создателем удаляемого результата,
	* достаточно права <br><br><b>[15] Работа со своим результатом в
	* соответствии с его статусом.</b> <br> </li> <li>На статус в котором
	* находится редактируемый результат необходимо иметь право:
	* <br><br><b>[DELETE] удаление.</b> </li> </ol> Параметр необязательный. По
	* умолчанию - "Y" (права необходимо проверить).
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $RESULT_ID = 189; // ID результата
	* 
	* // удалим результат с проверкой прав текущего пользователя
	* if (<b>CFormResult::Delete</b>($RESULT_ID))
	* {
	*     echo "Результат # ".$RESULT_ID." успешно удален.";
	* }
	* else // ошибка
	* {
	*     global $strError;
	*     echo $strError;
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/delete.php">CForm::Delete</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/delete.php">CFormField::Delete</a>; </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformanswer/delete.php">CFormAnswer::Delete</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformstatus/delete.php">CFormStatus::Delete</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/delete.php
	* @author Bitrix
	*/
	public static function Delete($RESULT_ID, $CHECK_RIGHTS="Y")
	{
//		echo $RESULT_ID; exit();
		global $DB, $USER, $APPLICATION, $strError;

		$strError = '';

		$err_mess = (CAllFormResult::err_mess())."<br>Function: Delete<br>Line: ";
		$RESULT_ID = intval($RESULT_ID);
		$strSql = "SELECT FORM_ID FROM b_form_result WHERE ID='".$RESULT_ID."'";
		$q = $DB->Query($strSql,false,$err_mess.__LINE__);
		if ($qr = $q->Fetch())
		{
			// rights check
			$F_RIGHT = ($CHECK_RIGHTS!="Y") ? 20 : CForm::GetPermission($qr["FORM_ID"]);
			if ($F_RIGHT>=20) $RIGHT_OK = "Y";
			else
			{
				$strSql = "SELECT USER_ID FROM b_form_result WHERE ID='".$RESULT_ID."'";
				$z = $DB->Query($strSql,false,$err_mess.__LINE__);
				$zr = $z->Fetch();
				if ($F_RIGHT>=15 && intval($USER->GetID())==$zr["USER_ID"]) $RIGHT_OK = "Y";
			}

			if ($RIGHT_OK=="Y")
			{
				// rights check by status
				if ($CHECK_RIGHTS == 'Y')
				{
					$arrRESULT_PERMISSION = CFormResult::GetPermissions($RESULT_ID, $v);
					$RIGHT_OK = in_array("DELETE", $arrRESULT_PERMISSION) ? 'Y' : 'N';
				}

				if ($RIGHT_OK=="Y") // delete rights ok
				{
					$dbEvents = GetModuleEvents('form', 'onBeforeResultDelete');
					while ($arEvent = $dbEvents->Fetch())
					{
						ExecuteModuleEventEx($arEvent, array($qr["FORM_ID"], $RESULT_ID, $CHECK_RIGHTS));

						if ($ex = $APPLICATION->GetException())
						{
							$strError .= $ex->GetString().'<br />';
							$APPLICATION->ResetException();
						}
					}

					if (strlen($strError) <= 0)
					{
						CForm::ExecHandlerBeforeChangeStatus($RESULT_ID, "DELETE");
						if (CFormResult::Reset($RESULT_ID, true, "Y"))
						{
							// delete result
							$DB->Query("DELETE FROM b_form_result WHERE ID='$RESULT_ID'", false, $err_mess.__LINE__);
							return true;
						}
					}
				}
			}
			else $strError .= GetMessage("FORM_ERROR_ACCESS_DENIED")."<br>";
		}
		else $strError .= GetMessage("FORM_ERROR_RESULT_NOT_FOUND")."<br>";
		return false;
	}

	// clear result
	
	/**
	* <p>Удаляет все значения <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответов</a> на <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопросы</a> веб-формы, а также значения <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">полей</a> веб-формы для указанного <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>. Сам результат при этом остается. Если в процессе работы метода ошибок не возникло, то метод возвращает "true".</p>
	*
	*
	* @param int $result_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>.
	*
	* @param bool $del_files = true Флаг необходимости удаления файлов, загруженных в качестве
	* значения <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответа</a> на <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопрос</a>.<br> Параметр
	* необязательный. По умолчанию - "true" (файлы необходимо удалить).
	*
	* @param string $del_fields = "N" Флаг необходимости удаления значений <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">полей</a> веб-формы.<br> Параметр
	* необязательный. По умолчанию - "N" (значения <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">полей</a> необходимо удалить).
	*
	* @param array $exception = array() Массив ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопросов</a> и <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">полей</a> веб-формы, для которых
	* не нужно удалять значения.<br> Параметр необязательный. По
	* умолчанию - пустой массив.
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $RESULT_ID = 189; // ID результата
	* 
	* // удалим все значения ответов на вопросы и полей веб-формы
	* // вместе с файлами
	* // исключение составят вопросы с ID = 140 и ID = 141
	* <b>CFormResult::Reset</b>($RESULT_ID, true, "Y", array(140, 141));
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cform/reset.php">CForm::Reset</a>; </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/reset.php">CFormField::Reset</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/reset.php
	* @author Bitrix
	*/
	public static function Reset($RESULT_ID, $DELETE_FILES=true, $DELETE_ADDITIONAL="N", $arrException=array())
	{
		global $DB, $strError;
		$err_mess = (CAllFormResult::err_mess())."<br>Function: Reset<br>Line: ";
		$RESULT_ID = intval($RESULT_ID);
		$strExc = '';

		if (is_array($arrException) && count($arrException)>0)
		{
			foreach ($arrException as $field_id)
			{
				$strExc .= ($strExc === '' ? '' : "','").intval($field_id);
			}
		}

		if ($DELETE_FILES)
		{
			$sqlExc = "";
			if (strlen($strExc)>0) $sqlExc = " and FIELD_ID not in ('$strExc') ";
			// delete result files
			$strSql = "SELECT USER_FILE_ID, ANSWER_ID FROM b_form_result_answer WHERE RESULT_ID='$RESULT_ID' and USER_FILE_ID>0 $sqlExc";
			$z = $DB->Query($strSql, false, $err_mess.__LINE__);
			while ($zr = $z->Fetch()) CFile::Delete($zr["USER_FILE_ID"]);
		}

		if ($DELETE_ADDITIONAL=="Y")
		{
			$sqlExc = "";
			if (strlen($strExc)>0) $sqlExc = " and FIELD_ID not in ('$strExc') ";
			$DB->Query("DELETE FROM b_form_result_answer WHERE RESULT_ID='$RESULT_ID' $sqlExc", false, $err_mess.__LINE__);
		}
		else
		{
			$sqlExc = "";
			if (strlen($strExc)>0) $sqlExc = "and F.ID not in ('".$strExc."'')";
			$strSql = "
				SELECT
					F.ID
				FROM
					b_form_result R,
					b_form_field F
				WHERE
					R.ID = $RESULT_ID
				and F.FORM_ID = R.FORM_ID
				and F.ADDITIONAL = 'N'
				$sqlExc
				";
			$z = $DB->Query($strSql, false, $err_mess.__LINE__);
			while ($zr=$z->Fetch()) $arrD[] = $zr["ID"];
			if (is_array($arrD) && count($arrD)>0) $strD = implode(",",$arrD);
			if (strlen($strD)>0)
			{
				$DB->Query("DELETE FROM b_form_result_answer WHERE RESULT_ID='$RESULT_ID' and FIELD_ID in ($strD)", false, $err_mess.__LINE__);
			}
		}
		return true;
	}

	// update result status
	
	/**
	* <p>Устанавливает новый <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статус</a> для <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>. Возвращает "true" в случае успеха, в противном случае - "false".</p>
	*
	*
	* @param int $result_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>.
	*
	* @param int $status_id  ID нового <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статуса</a>.
	*
	* @param string $check_rights = "Y" Флаг необходимости проверки прав текущего пользователя.
	* Возможны следующие значения: <ul> <li> <b>Y</b> - права необходимо
	* проверить; </li> <li> <b>N</b> - права не нужно проверять. </li> </ul> Для
	* успешной установки нового <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#status">статуса</a> для указанного <a
	* href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a> необходимо
	* обладать следующими <a
	* href="http://dev.1c-bitrix.ru/api_help/form/permissions.php">правами</a>: <ol> <li>На веб-форму к
	* которой принадлежит редактируемый результат: <br><br><b>[20] Работа со
	* всеми результатами в соответствии с их статусами</b> <br><br>или, в
	* случае, если вы являетесь создателем удаляемого результата,
	* достаточно права: <br><br><b>[15] Работа со своим результатом в
	* соответствии с его статусом</b> <br> </li> <li>На статус, в котором
	* находится редактируемый результат, необходимо иметь право:
	* <br><br><b>[EDIT] редактирование</b> <br> </li> <li>На новый статус <i>status_id</i>
	* необходимо иметь право: <br><br><b>[MOVE] перевод результатов в данный
	* статус</b> </li> </ol> Параметр необязательный. По умолчанию - "Y" (права
	* необходимо проверить).
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $RESULT_ID = 189; // ID результата
	* $STATUS_ID = 1; // ID статуса "Опубликовано"
	* 
	* // установим новый статус для результата
	* // с проверкой прав текущего пользователя
	* if (<b>CFormResult::SetStatus</b>($RESULT_ID, $STATUS_ID))
	* {
	*     echo "Статус #".$STATUS_ID." для результата #".$RESULT_ID." успешно установлен.";
	* }
	* else // ошибка
	* {
	*     global $strError;
	*     echo $strError;
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/setstatus.php
	* @author Bitrix
	*/
	public static function SetStatus($RESULT_ID, $NEW_STATUS_ID, $CHECK_RIGHTS="Y")
	{
		$err_mess = (CAllFormResult::err_mess())."<br>Function: SetStatus<br>Line: ";
		global $DB, $USER, $strError, $APPLICATION;
		$NEW_STATUS_ID = intval($NEW_STATUS_ID);
		$RESULT_ID = intval($RESULT_ID);

		if ($RESULT_ID <= 0 || $NEW_STATUS_ID <= 0)
			return false;

		$strSql = "SELECT USER_ID, FORM_ID FROM b_form_result WHERE ID='".$RESULT_ID."'";
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		if ($zr = $z->Fetch())
		{
			$WEB_FORM_ID = intval($zr["FORM_ID"]);

			// rights check
			$RIGHT_OK = "N";
			if ($CHECK_RIGHTS!="Y")
			{
				$dbRes = CFormStatus::GetByID($NEW_STATUS_ID);
				if ($dbRes->Fetch())
				{
					$RIGHT_OK="Y";
				}
			}
			else
			{
				// form rights
				$F_RIGHT = CForm::GetPermission($WEB_FORM_ID);
				if ($F_RIGHT>=20 || ($F_RIGHT>=15 && $USER->GetID()==$zr["USER_ID"]))
				{
					// result rights
					$arrRESULT_PERMISSION = CFormResult::GetPermissions($RESULT_ID, $v);

					// new status rights
					$arrNEW_STATUS_PERMISSION = CFormStatus::GetPermissions($NEW_STATUS_ID);

					if (in_array("EDIT", $arrRESULT_PERMISSION) && in_array("MOVE", $arrNEW_STATUS_PERMISSION))
					{
						$RIGHT_OK = "Y";
					}
				}
			}

			if ($RIGHT_OK=="Y")
			{
				$dbEvents = GetModuleEvents('form', 'onBeforeResultStatusChange');
				while ($arEvent = $dbEvents->Fetch())
				{
					ExecuteModuleEventEx($arEvent, array($WEB_FORM_ID, $RESULT_ID, &$NEW_STATUS_ID, $CHECK_RIGHTS));

					if ($ex = $APPLICATION->GetException())
						$strError .= $ex->GetString().'<br />';
				}

				if (strlen($strError) <= 0)
				{
					// call handler before change status
					CForm::ExecHandlerBeforeChangeStatus($RESULT_ID, "SET_STATUS", $NEW_STATUS_ID);
					$arFields = Array(
						"TIMESTAMP_X"	=> $DB->GetNowFunction(),
						"STATUS_ID"		=> "'".intval($NEW_STATUS_ID)."'"
						);
					$DB->Update("b_form_result",$arFields,"WHERE ID='".$RESULT_ID."'",$err_mess.__LINE__);

					$dbEvents = GetModuleEvents('form', 'onAfterResultStatusChange');
					while ($arEvent = $dbEvents->Fetch())
					{
						ExecuteModuleEventEx($arEvent, array($WEB_FORM_ID, $RESULT_ID, $NEW_STATUS_ID, $CHECK_RIGHTS));
					}

					// call handler after change status
					CForm::ExecHandlerAfterChangeStatus($RESULT_ID, "SET_STATUS");
					return true;
				}
			}
			else $strError .= GetMessage("FORM_ERROR_ACCESS_DENIED")."<br>";
		}
		else $strError .= GetMessage("FORM_ERROR_RESULT_NOT_FOUND")."<br>";
		return false;
	}

	//send form event notification;
	
	/**
	* <p>Создает почтовое событие для отсылки данных <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a> по e-mail. Возвращает "true" в случае успеха, в противном случае - "false".</p>
	*
	*
	* @param int $result_id  ID <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результата</a>.
	*
	* @param mixed $template_id = false ID почтового шаблона.<br><br> Параметр необязательный. По умолчанию -
	* "false" (будут использованы почтовые шаблоны из настроек
	* соответствующей веб-формы).
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $RESULT_ID = 189; // ID результата
	* 
	* // создадим почтовое событие для отсылки по EMail данных результата
	* if (<b>CFormResult::Mail</b>($RESULT_ID))
	* {
	*     echo "Почтовое событие успешно создано.";
	* }
	* else // ошибка
	* {
	*     global $strError;
	*     echo $strError;
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/mail.php
	* @author Bitrix
	*/
	public static function Mail($RESULT_ID, $TEMPLATE_ID = false)
	{
		global $APPLICATION, $DB, $MESS, $strError;

		$err_mess = (CAllFormResult::err_mess())."<br>Function: Mail<br>Line: ";
		$RESULT_ID = intval($RESULT_ID);

		CTimeZone::Disable();
		$arrResult = CFormResult::GetDataByID($RESULT_ID, array(), $arrRES, $arrANSWER);
		CTimeZone::Enable();
		if ($arrResult)
		{
			$z = CForm::GetByID($arrRES["FORM_ID"]);
			if ($arrFORM = $z->Fetch())
			{
				$TEMPLATE_ID = intval($TEMPLATE_ID);

				$arrFormSites = CForm::GetSiteArray($arrRES["FORM_ID"]);
				$arrFormSites = (is_array($arrFormSites)) ? $arrFormSites : array();

				if (!defined('SITE_ID') || !in_array(SITE_ID, $arrFormSites))
					return true;

				$rs = CSite::GetList(($by="sort"), ($order="asc"), array('ID' => implode('|', $arrFormSites)));
				$arrSites = array();
				while ($ar = $rs->Fetch())
				{
					if ($ar["DEF"]=="Y") $def_site_id = $ar["ID"];
					$arrSites[$ar["ID"]] = $ar;
				}

				$arrFormTemplates = CForm::GetMailTemplateArray($arrRES["FORM_ID"]);
				$arrFormTemplates = (is_array($arrFormTemplates)) ? $arrFormTemplates : array();

				$arrTemplates = array();
				$rs = CEventMessage::GetList($by="id", $order="asc", array(
					"ACTIVE"		=> "Y",
					"SITE_ID"		=> SITE_ID,
					"EVENT_NAME"	=> $arrFORM["MAIL_EVENT_TYPE"]
					));

				while ($ar = $rs->Fetch())
				{
					if ($TEMPLATE_ID>0)
					{
						if ($TEMPLATE_ID == $ar["ID"])
						{
							$arrTemplates[$ar["ID"]] = $ar;
							break;
						}
					}
					elseif (in_array($ar["ID"],$arrFormTemplates)) $arrTemplates[$ar["ID"]] = $ar;
				}

				foreach($arrTemplates as $arrTemplate)
				{

					$OLD_MESS = $MESS;
					$MESS = array();
					IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/form/admin/form_mail.php", $arrSites[$arrTemplate["SITE_ID"]]["LANGUAGE_ID"]);

					$USER_AUTH = " ";
					if (intval($arrRES["USER_ID"])>0)
					{
						$w = CUser::GetByID($arrRES["USER_ID"]);
						$arrUSER = $w->Fetch();
						$USER_ID = $arrUSER["ID"];
						$USER_EMAIL = $arrUSER["EMAIL"];
						$USER_NAME = $arrUSER["NAME"]." ".$arrUSER["LAST_NAME"];
						if ($arrRES["USER_AUTH"]!="Y") $USER_AUTH="(".GetMessage("FORM_NOT_AUTHORIZED").")";
					}
					else
					{
						$USER_ID = GetMessage("FORM_NOT_REGISTERED");
						$USER_NAME = "";
						$USER_AUTH = "";
						$USER_EMAIL = "";
					}

					$arEventFields = array(
						"RS_FORM_ID"			=> $arrFORM["ID"],
						"RS_FORM_NAME"			=> $arrFORM["NAME"],
						"RS_FORM_VARNAME"		=> $arrFORM["SID"],
						"RS_FORM_SID"			=> $arrFORM["SID"],
						"RS_RESULT_ID"			=> $arrRES["ID"],
						"RS_DATE_CREATE"		=> $arrRES["DATE_CREATE"],
						"RS_USER_ID"			=> $USER_ID,
						"RS_USER_EMAIL"			=> $USER_EMAIL,
						"RS_USER_NAME"			=> $USER_NAME,
						"RS_USER_AUTH"			=> $USER_AUTH,
						"RS_STAT_GUEST_ID"		=> $arrRES["STAT_GUEST_ID"],
						"RS_STAT_SESSION_ID"	=> $arrRES["STAT_SESSION_ID"]
						);
					$w = CFormField::GetList($arrFORM["ID"], "ALL", $by, $order, array(), $is_filtered);
					while ($wr=$w->Fetch())
					{
						$answer = "";
						$answer_raw = '';
						if (is_array($arrResult[$wr["SID"]]))
						{
							$bHasDiffTypes = false;
							$lastType = '';
							foreach ($arrResult[$wr['SID']] as $arrA)
							{
								if ($lastType == '') $lastType = $arrA['FIELD_TYPE'];
								elseif ($arrA['FIELD_TYPE'] != $lastType)
								{
									$bHasDiffTypes = true;
									break;
								}
							}

							foreach($arrResult[$wr["SID"]] as $arrA)
							{
								if ($wr['ADDITIONAL'] == 'Y')
									$arrA['FIELD_TYPE'] = $wr['FIELD_TYPE'];

								$USER_TEXT_EXIST = (strlen(trim($arrA["USER_TEXT"]))>0);
								$ANSWER_TEXT_EXIST = (strlen(trim($arrA["ANSWER_TEXT"]))>0);
								$ANSWER_VALUE_EXIST = (strlen(trim($arrA["ANSWER_VALUE"]))>0);
								$USER_FILE_EXIST = (intval($arrA["USER_FILE_ID"])>0);

								if ($arrTemplate["BODY_TYPE"]=="html")
								{
									if (
										$bHasDiffTypes
										&&
										!$USER_TEXT_EXIST
										&&
										(
											$arrA['FIELD_TYPE'] == 'text'
											||
											$arrA['FIELD_TYPE'] == 'textarea'
										)
									)
										continue;

									if (strlen(trim($answer))>0) $answer .= "<br />";
									if (strlen(trim($answer_raw))>0) $answer_raw .= ",";

									if ($ANSWER_TEXT_EXIST)
										$answer .= $arrA["ANSWER_TEXT"].': ';

									switch ($arrA['FIELD_TYPE'])
									{
										case 'text':
										case 'textarea':
										case 'hidden':
										case 'date':
										case 'password':
										case 'integer':

											if ($USER_TEXT_EXIST)
											{
												$answer .= trim($arrA["USER_TEXT"]);
												$answer_raw .= trim($arrA["USER_TEXT"]);
											}

										break;

										case 'email':
										case 'url':

											if ($USER_TEXT_EXIST)
											{
												$answer .= '<a href="'.($arrA['FIELD_TYPE'] == 'email' ? 'mailto:' : '').trim($arrA["USER_TEXT"]).'">'.trim($arrA["USER_TEXT"]).'</a>';
												$answer_raw .= trim($arrA["USER_TEXT"]);
											}

										break;

										case 'checkbox':
										case 'multiselect':
										case 'radio':
										case 'dropdown':

											if ($ANSWER_TEXT_EXIST)
											{
												$answer = substr($answer, 0, -2).' ';
												$answer_raw .= $arrA['ANSWER_TEXT'];
											}

											if ($ANSWER_VALUE_EXIST)
											{
												$answer .= '('.$arrA['ANSWER_VALUE'].') ';
												if (!$ANSWER_TEXT_EXIST)
													$answer_raw .= $arrA['ANSWER_VALUE'];
											}

											if (!$ANSWER_VALUE_EXIST && !$ANSWER_TEXT_EXIST)
												$answer_raw .= $arrA['ANSWER_ID'];

											$answer .= '['.$arrA['ANSWER_ID'].']';

										break;

										case 'file':
										case 'image':

											if ($USER_FILE_EXIST)
											{
												$f = CFile::GetByID($arrA["USER_FILE_ID"]);
												if ($fr = $f->Fetch())
												{
													$file_size = CFile::FormatSize($fr["FILE_SIZE"]);
													$url = ($APPLICATION->IsHTTPS() ? "https://" : "http://").$_SERVER["HTTP_HOST"]. "/bitrix/tools/form_show_file.php?rid=".$RESULT_ID. "&hash=".$arrA["USER_FILE_HASH"]."&lang=".LANGUAGE_ID;

													if ($arrA["USER_FILE_IS_IMAGE"]=="Y")
													{
														$answer .= "<a href=\"$url\">".$arrA["USER_FILE_NAME"]."</a> [".$fr["WIDTH"]." x ".$fr["HEIGHT"]."] (".$file_size.")";
													}
													else
													{
														$answer .= "<a href=\"$url&action=download\">".$arrA["USER_FILE_NAME"]."</a> (".$file_size.")";
													}

													$answer_raw .= $arrA['USER_FILE_NAME'];
												}
											}

										break;
									}
								}
								else
								{
									if (
										$bHasDiffTypes
										&&
										!$USER_TEXT_EXIST
										&&
										(
											$arrA['FIELD_TYPE'] == 'text'
											||
											$arrA['FIELD_TYPE'] == 'textarea'
										)
									)
										continue;

									if (strlen(trim($answer)) > 0) $answer .= "\n";
									if (strlen(trim($answer_raw)) > 0) $answer_raw .= ",";

									if ($ANSWER_TEXT_EXIST)
										$answer .= $arrA["ANSWER_TEXT"].': ';

									switch ($arrA['FIELD_TYPE'])
									{
										case 'text':
										case 'textarea':
										case 'email':
										case 'url':
										case 'hidden':
										case 'date':
										case 'password':
										case 'integer':

											if ($USER_TEXT_EXIST)
											{
												$answer .= trim($arrA["USER_TEXT"]);
												$answer_raw .= trim($arrA["USER_TEXT"]);
											}

										break;

										case 'checkbox':
										case 'multiselect':
										case 'radio':
										case 'dropdown':

											if ($ANSWER_TEXT_EXIST)
											{
												$answer = substr($answer, 0, -2).' ';
												$answer_raw .= $arrA['ANSWER_TEXT'];
											}

											if ($ANSWER_VALUE_EXIST)
											{
												$answer .= '('.$arrA['ANSWER_VALUE'].') ';
												if (!$ANSWER_TEXT_EXIST)
												{
													$answer_raw .= $arrA['ANSWER_VALUE'];
												}
											}

											if (!$ANSWER_VALUE_EXIST && !$ANSWER_TEXT_EXIST)
											{
												$answer_raw .= $arrA['ANSWER_ID'];
											}

											$answer .= '['.$arrA['ANSWER_ID'].']';

										break;

										case 'file':
										case 'image':

											if ($USER_FILE_EXIST)
											{
												$f = CFile::GetByID($arrA["USER_FILE_ID"]);
												if ($fr = $f->Fetch())
												{
													$file_size = CFile::FormatSize($fr["FILE_SIZE"]);
													$url = ($APPLICATION->IsHTTPS() ? "https://" : "http://").$_SERVER["HTTP_HOST"]. "/bitrix/tools/form_show_file.php?rid=".$RESULT_ID. "&hash=".$arrA["USER_FILE_HASH"]."&action=download&lang=".LANGUAGE_ID;

													if ($arrA["USER_FILE_IS_IMAGE"]=="Y")
													{
														$answer .= $arrA["USER_FILE_NAME"]." [".$fr["WIDTH"]." x ".$fr["HEIGHT"]."] (".$file_size.")\n".$url;
													}
													else
													{
														$answer .= $arrA["USER_FILE_NAME"]." (".$file_size.")\n".$url."&action=download";
													}
												}

												$answer_raw .= $arrA['USER_FILE_NAME'];
											}

										break;
									}
								}
							}
						}

						$arEventFields[$wr["SID"]] = (strlen($answer)<=0) ? " " : $answer;
						$arEventFields[$wr["SID"].'_RAW'] = (strlen($answer_raw)<=0) ? " " : $answer_raw;
					}

					CEvent::Send($arrTemplate["EVENT_NAME"], $arrTemplate["SITE_ID"], $arEventFields, "Y", $arrTemplate["ID"]);
					$MESS = $OLD_MESS;
				} //foreach($arrTemplates as $arrTemplate)
				return true;
			}
			else $strError .= GetMessage("FORM_ERROR_FORM_NOT_FOUND")."<br>";
		}
		else $strError .= GetMessage("FORM_ERROR_RESULT_NOT_FOUND")."<br>";
		return false;
	}

	
	/**
	* <p>Возвращает количество <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#result">результатов</a> указанной веб-формы.</p>
	*
	*
	* @param int $form_id  ID веб-формы.</bod
	*
	* @return int 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $FORM_ID = 4; // ID веб-формы
	* echo "Количество результатов веб-формы #".$FORM_ID.": ".<b>CFormResult::GetCount</b>($FORM_ID);
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/getcount.php
	* @author Bitrix
	*/
	public static function GetCount($WEB_FORM_ID)
	{
		global $DB, $USER, $strError;
		$err_mess = (CAllFormResult::err_mess())."<br>Function: GetCount<br>Line: ";
		$strSql = "SELECT count(ID) C FROM b_form_result WHERE FORM_ID=".intval($WEB_FORM_ID);
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		$zr = $z->Fetch();
		return intval($zr["C"]);
	}

	// prepare array of parameters for result filter
	public static function PrepareFilter($WEB_FORM_ID, $arFilter)
	{
		$err_mess = (CAllFormResult::err_mess())."<br>Function: PrepareFilter<br>Line: ";
		global $DB, $strError;

		$arrFilterReturn = $arFilter;

		if (array_key_exists("FIELDS", $arFilter))
		{
			$arFilterFields = $arFilter["FIELDS"];

			$rsForm = CForm::GetByID($WEB_FORM_ID);
			$arForm = $rsForm->Fetch();

			$WEB_FORM_NAME = $arForm["SID"];

			if (is_array($arFilterFields) && count($arFilterFields) > 0)
			{
				foreach ($arFilterFields as $arr)
				{
					if (strlen($arr["SID"]) > 0)
						$arr["CODE"] = $arr["SID"];
					else
						$arr["SID"] = $arr["CODE"];

					$FIELD_SID = $arr["SID"];

					$FILTER_TYPE = (strlen($arr["FILTER_TYPE"]) > 0) ? $arr["FILTER_TYPE"] : "text";

					if (strtoupper($FILTER_TYPE) == "ANSWER_ID") $FILTER_TYPE = "dropdown";

					$PARAMETER_NAME = (strlen($arr["PARAMETER_NAME"]) > 0) ? $arr["PARAMETER_NAME"] : "USER";

					$PART = $arr["PART"];

					$FILTER_KEY = $arForm["SID"]."_".$FIELD_SID."_".$PARAMETER_NAME."_".$FILTER_TYPE;
					if (strlen($PART) > 0) $FILTER_KEY .= "_".intval($PART);

					$arrFilterReturn[$FILTER_KEY] = $arr["VALUE"];

					if ($FILTER_TYPE=="text")
					{
						$EXACT_MATCH = ($arr["EXACT_MATCH"]=="Y") ? "Y" : "N";
						$arrFilterReturn[$FILTER_KEY."_exact_match"] = $EXACT_MATCH;
					}
				}
			}
			unset($arrFilterReturn["FIELDS"]);
		}
		return $arrFilterReturn;
	}

	public static function SetCRMFlag($RESULT_ID, $flag_value)
	{
		return $GLOBALS['DB']->Query("UPDATE b_form_result SET SENT_TO_CRM='".($flag_value == 'N' ? 'N' : 'Y')."' WHERE ID='".intval($RESULT_ID)."'");
	}
}
?>