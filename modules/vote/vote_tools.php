<?
global $VOTE_CACHE_VOTING;
$VOTE_CACHE_VOTING = Array();

function GetAnswerTypeList()
{
	$arr = array(
		"reference_id" => array(0,1,2,3,4,5),
		"reference" => array("radio", "checkbox", "dropdown", "multiselect", "text", "textarea")
		);
	return $arr;
}

function GetVoteDiagramArray()
{
	$object =& CVoteDiagramType::getInstance();
	return $object->arType;
}

function GetVoteDiagramList()
{
	$object =& CVoteDiagramType::getInstance();

	return Array(
		"reference_id" => array_keys($object->arType),
		"reference" => array_values($object->arType)
		);
}

// vote data

/**
 * <p>Функция возвращает ID опроса в случае, если такой опрос был найден в базе, а также массивы описывающие опрос.</p>
 *
 *
 * @param int $VOTE_ID  ID опроса.</bo
 *
 * @param array &$arChannel  Массив описывающий группу заданного опроса. <br>Индексы массива:
 * <li> <b>ID</b> - ID группы </li> <li> <b>SYMBOLIC_NAME</b> - символическое имя </li> <li>
 * <b>ACTIVE</b> - флаг активности ["Y"|"N"] </li> <li> <b>TITLE</b> - заголовок группы </li>
 * <li> <b>LID</b> - ID сайта </li> <li> <b>C_SORT</b> - порядок сортировки </li> <li>
 * <b>TIMESTAMP_X</b> - время изменения записи в базе данных </li>
 *
 * @param array &$arVote  Массив описывающий заданный опрос. <br>Индексы массива: <li> <b>ID</b> - ID
 * опроса </li> <li> <b>CHANNEL_ID</b> - ID группы опроса </li> <li> <b>C_SORT</b> - порядок
 * сортировки </li> <li> <b>ACTIVE</b> - флаг активности ["Y"|"N"] </li> <li> <b>TIMESTAMP_X</b> -
 * время изменения записи в базе данных </li> <li> <b>DATE_START</b> - время
 * начала опроса </li> <li> <b>DATE_END</b> - время окончания опроса </li> <li>
 * <b>COUNTER</b> - количество голосований по опросу </li> <li> <b>TITLE</b> -
 * заголовок опроса </li> <li> <b>DESCRIPTION</b> - описание опроса </li> <li>
 * <b>DESCRIPTION_TYPE</b> - тип описания опроса ["html"|"text"] </li> <li> <b>IMAGE_ID</b> - ID
 * изображения </li> <li> <b> <code>EVENT1</code></b> - идентификатор типа события - "
 * <code>event1</code>" </li> <li> <b> <code>EVENT2</code></b> - идентификатор типа события - "
 * <code>event2</code>" </li> <li> <b> <code>EVENT3</code></b> - дополнительный параметр типа
 * события - " <code>event3</code>" </li> <li> <b>UNIQUE_TYPE</b> - тип уникальности
 * посетителей: 0 - не ограничено; 1 - не голосовать дважды в одной
 * сессии; 2 - не голосовать дважды в одной сессии либо с одним cookie; 3 -
 * не голосовать дважды в одной сессии либо с одним cookie либо с одного
 * IP; </li> <li> <b>KEEP_IP_SEC</b> - количество секунд в течении которых нельзя
 * голосовать с одного IP </li> <li> <b>TEMPLATE</b> - шаблон для показа формы
 * опроса </li> <li> <b>RESULT_TEMPLATE</b> - шаблон для показа результатов опроса
 * </li> <li> <b>QUESTIONS</b> - количество вопросов </li> <li> <b>LAMP</b> - индикатор: "red" -
 * флаг активности опроса снят либо текущая дата не попадает в
 * интервал проведения опроса; "green" - флаг активности опроса
 * установлен и текущая дата попадает в интервал проведения опроса.
 * </li>
 *
 * @param array &$arQuestions  Массив состоящий из массивов каждый из которых описывает один
 * вопрос. <br>Индексы массива: <li> <b>ID</b> - ID вопроса </li> <li> <b>ACTIVE</b> - флаг
 * активности ["Y"|"N"] </li> <li> <b>C_SORT</b> - порядок сортировки </li> <li> <b>QUESTION</b>
 * - текст вопроса </li> <li> <b>QUESTION_TYPE</b> - тип текста вопроса ["html"|"text"] </li>
 * <li> <b>IMAGE_ID</b> - ID изображения </li> <li> <b>DIAGRAM</b> - флаг включения вопроса
 * в результирующую диаграмму ["Y"|"N"] </li> <li> <b>TEMPLATE</b> - шаблон для
 * показа результатов вопроса </li> <li> <b>TIMESTAMP_X</b> - дата изменения
 * записи в базе данных </li>
 *
 * @param array &$arAnswers  Массив ответов, его индексами являются ID вопросов, а значениями -
 * список массивов, каждый из которых описывает один ответ.
 * <br>Индексы массива описывающего один ответ: <li> <b>ID</b> - ID ответа </li>
 * <li> <b>ACTIVE</b> - флаг активности ["Y"|"N"] </li> <li> <b>TIMESTAMP_X</b> - дата изменения
 * записи в базе данных </li> <li> <b>QUESTION_ID</b> - ID вопроса </li> <li> <b>C_SORT</b> -
 * порядок сортировки </li> <li> <b>MESSAGE</b> - текст сообщения который будет
 * выдан рядом с ответом в форме опроса и в качестве ответа в
 * диаграмме опроса </li> <li> <b>COUNTER</b> - количество раз когда был выбран
 * данный ответ </li> <li> <b>FIELD_TYPE</b> - тип поля ввода: 0 - radio, 1 - checkbox, 2 - dropdown
 * list; 3 - multiselect list, 4 - text; 5 - textarea </li> <li> <b>FIELD_WIDTH</b> - ширина поля ввода
 * </li> <li> <b>FIELD_HEIGHT</b> - высота поля ввода </li> <li> <b>FIELD_PARAM</b> -
 * дополнительные параметры поля ввода: стиль, класс </li> <li> <b>COLOR</b> -
 * RGB цвета элемента диаграммы (например: #FFOOCC) </li>
 *
 * @param array &$arDropDown  Массив с всеми элементами типа "2" (dropdown list) одного вопроса.
 * Индексом массива является ID вопроса, а значением - массив со
 * следующими индексами: <li> <b>REFERENCE</b> - текст ответа </li> <li> <b>REFERENCE_ID</b>
 * - ID ответа </li>
 *
 * @param array &$arMultiSelect  Массив с всеми элементами типа "3" (multiselect list) одного вопроса.
 * Индексом массива является ID вопроса, а значением - массив со
 * следующими индексами: <li> <b>REFERENCE</b> - текст ответа </li> <li> <b>REFERENCE_ID</b>
 * - ID ответа </li>
 *
 * @param array &$arGroupAnswers  Массив описывающий варианты ответов для элементов ввода типа "4"
 * (text) и "5" (textarea). Индексом массива является ID вопроса, а значением -
 * список массивов со следующими индексами: <li> <b>MESSAGE</b> - текст
 * ответа </li> <li> <b>COUNTER</b> - количество таких ответов </li>
 *
 * @param string $getGroupAnswers  Флаг принимающий следующие значения: "Y" - собирать массив arGroupAnswers;
 * "N" - собирать массив arGroupAnswers не нужно.
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre>
 * &lt;?
 * // возвращает форму заданного опроса с учётом прав пользователя
 * function ShowVote($VOTE_ID, $template1="")
 * {
 * 	global $MESS, $VOTING_LAMP, $VOTING_OK, $USER_ALREADY_VOTE, $USER_GROUP_PERMISSION, $VOTE_USER_ID, $VOTE_PERMISSION;
 * 	$VOTE_ID = <b>GetVoteDataByID</b>($VOTE_ID, $arChannel, $arVote, $arQuestions, $arAnswers, $arDropDown, $arMultiSelect, $arGroupAnswers, "N");
 * 	if (intval($VOTE_ID)&gt;0)
 * 	{
 * 		$perm = CVoteChannel::GetGroupPermission($arChannel["ID"]);
 * 		if (intval($perm)&gt;=2)
 * 		{
 * 			$template = (strlen($arVote["TEMPLATE"])&lt;=0) ? "default.php" : $arVote["TEMPLATE"];
 * 			$VOTE_PERMISSION = CVote::UserGroupPermission($arChannel["ID"]);
 * 			require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/vote/include.php");
 * 			@include_once (GetLangFileName($_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/lang/", "/".$template));
 * 			$path = COption::GetOptionString("vote", "VOTE_TEMPLATE_PATH", "/bitrix/php_interface/include/vote/show/");
 * 			if (strlen($template1)&gt;0) $template = $template1;
 * 			@include($_SERVER["DOCUMENT_ROOT"].$path.$template);
 * 		}
 * 	}
 * }
 * ?&gt;
 * 
 * 
 * 
 * &lt;?
 * // получаем данные по опросу
 * $VOTE_ID = <b>GetVoteDataByID</b>($PUBLIC_VOTE_ID, $arChannel, $arVote, $arQuestions, $arAnswers, $arDropDown, $arMultiSelect, $arGroupAnswers, "N", $template, $res_template);
 * $VOTE_ID = intval($VOTE_ID);
 * // если поступивший ID опроса корректный то
 * if ($VOTE_ID&gt;0 &amp;&amp; $arVote["LAMP"]=="green")
 * {
 * 	...
 * }
 * ?&gt;
 * </pre>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/vote/function/getvotedatabyid.php
 * @author Bitrix
 */
function GetVoteDataByID($VOTE_ID, &$arChannel, &$arVote, &$arQuestions, &$arAnswers, &$arDropDown, &$arMultiSelect, &$arGroupAnswers, $arAddParams = "N")
{
	$VOTE_ID = intval($VOTE_ID);
	$arChannel = array();
	$arVote = array();
	$arQuestions = array();
	$arAnswers = array();
	$arDropDown = array();
	$arMultiSelect = array();
	$arAddParams = (is_array($arAddParams) ? $arAddParams : array("bGetMemoStat" => $arAddParams));

	$GLOBALS["VOTE_CACHE_VOTING"][$VOTE_ID] = (is_array($GLOBALS["VOTE_CACHE_VOTING"][$VOTE_ID]) ? $GLOBALS["VOTE_CACHE_VOTING"][$VOTE_ID] : array());

	if (empty($GLOBALS["VOTE_CACHE_VOTING"][$VOTE_ID]))
	{
		$db_res = CVote::GetByIDEx($VOTE_ID);
		if (!($db_res && $arVote = $db_res->GetNext()))
		{
			return false;
		}

		foreach ($arVote as $key => $res)
		{
			if (strpos($key, "CHANNEL_") === 0)
			{
				$arChannel[substr($key, 8)] = $res;
			}
			elseif (strpos($key, "~CHANNEL_") === 0)
			{
				$arChannel["~".substr($key, 9)] = $res;
			}
		}
		$by = "s_c_sort"; $order = "asc";
		$db_res = CVoteQuestion::GetList($VOTE_ID, $by, $order, array("ACTIVE" => "Y"), $is_filtered);
		while ($res = $db_res->GetNext())
		{
			$arQuestions[$res["ID"]] = $res + array("ANSWERS" => array());
		}
		if (!empty($arQuestions))
		{
			$db_res = CVoteAnswer::GetListEx(
				array("C_SORT" => "ASC"),
				array("VOTE_ID" => $VOTE_ID, "ACTIVE" => "Y", "@QUESTION_ID" => array_keys($arQuestions)));
			while ($res = $db_res->GetNext())
			{
				$arQuestions[$res["QUESTION_ID"]]["ANSWERS"][$res["ID"]] = $res;

				$arAnswers[$res["QUESTION_ID"]][] = $res;

				switch ($res["FIELD_TYPE"]) // dropdown and multiselect and text inputs
				{
					case 2:
						$arDropDown[$res["QUESTION_ID"]] = (is_array($arDropDown[$res["QUESTION_ID"]]) ? $arDropDown[$res["QUESTION_ID"]] :
							array("reference" => array(), "reference_id" => array(), "~reference" => array()));
						$arDropDown[$res["QUESTION_ID"]]["reference"][] = $res["MESSAGE"];
						$arDropDown[$res["QUESTION_ID"]]["~reference"][] = $res["~MESSAGE"];
						$arDropDown[$res["QUESTION_ID"]]["reference_id"][] = $res["ID"];
					break;
					case 3:
						$arMultiSelect[$res["QUESTION_ID"]] = (is_array($arMultiSelect[$res["QUESTION_ID"]]) ? $arMultiSelect[$res["QUESTION_ID"]] :
							array("reference" => array(), "reference_id" => array(), "~reference" => array()));
						$arMultiSelect[$res["QUESTION_ID"]]["reference"][] = $res["MESSAGE"];
						$arMultiSelect[$res["QUESTION_ID"]]["~reference"][] = $res["~MESSAGE"];
						$arMultiSelect[$res["QUESTION_ID"]]["reference_id"][] = $res["ID"];
					break;
				}
			}
			$event_id = intval($arAddParams["bRestoreVotedData"] == "Y" && !!$_SESSION["VOTE"]["VOTES"][$VOTE_ID] ?
				$_SESSION["VOTE"]["VOTES"][$VOTE_ID] : 0);
			$db_res = CVoteEvent::GetUserAnswerStat($VOTE_ID,
				array("bGetMemoStat" => "N", "bGetEventResults" => $event_id));
			if ($db_res && ($res = $db_res->Fetch()))
			{
				do
				{
					if (isset($arQuestions[$res["QUESTION_ID"]]) && is_array($arQuestions[$res["QUESTION_ID"]]["ANSWERS"][$res["ANSWER_ID"]]) && is_array($res))
					{
						$arQuestions[$res["QUESTION_ID"]]["ANSWERS"][$res["ANSWER_ID"]] += $res;
						if ($event_id > 0 && !empty($res["RESTORED_ANSWER_ID"]))
						{
							switch ($arQuestions[$res["QUESTION_ID"]]["ANSWERS"][$res["ANSWER_ID"]]["FIELD_TYPE"]):
								case 0: // radio
								case 2: // dropdown list
									$fieldName = ($arQuestions[$res["QUESTION_ID"]]["ANSWERS"][$res["ANSWER_ID"]]["FIELD_TYPE"] == 0 ?
										"vote_radio_" : "vote_dropdown_").$res["QUESTION_ID"];
									$_REQUEST[$fieldName] = $res["RESTORED_ANSWER_ID"];
									break;
								case 1: // checkbox
								case 3: // multiselect list
									$fieldName = ($arQuestions[$res["QUESTION_ID"]]["ANSWERS"][$res["ANSWER_ID"]]["FIELD_TYPE"] == 1 ?
										"vote_checkbox_" : "vote_multiselect_").$res["QUESTION_ID"];
									$_REQUEST[$fieldName] = (is_array($_REQUEST[$fieldName]) ? $_REQUEST[$fieldName] : array());
									$_REQUEST[$fieldName][] = $res["ANSWER_ID"];
									break;
								case 4: // field
								case 5: // text
									// do not restored
									break;
							endswitch;
						}
					}
				} while ($res = $db_res->Fetch());
			}
		}

		reset($arChannel);
		reset($arVote);
		reset($arQuestions);
		reset($arDropDown);
		reset($arMultiSelect);
		reset($arAnswers);

		$GLOBALS["VOTE_CACHE_VOTING"][$VOTE_ID] = array(
			"V" => $arVote,
			"C" => $arChannel,
			"QA" => array(
				"Q" => $arQuestions,
				"A" => $arAnswers,
				"M" => $arMultiSelect,
				"D" => $arDropDown,
				"G" => array(),
				"GA" => "N"
			)
		);
	}

	if ($arAddParams["bGetMemoStat"] == "Y" && $GLOBALS["VOTE_CACHE_VOTING"][$VOTE_ID]["QA"]["GA"] == "N")
	{
		$db_res = CVoteEvent::GetUserAnswerStat($VOTE_ID, array("bGetMemoStat" => "Y"));
		while($res = $db_res->GetNext(true, false))
		{
			$arGroupAnswers[$res['ANSWER_ID']][] = $res;
		}
		$GLOBALS["VOTE_CACHE_VOTING"][$VOTE_ID]["QA"]["G"] = $arGroupAnswers;
		$GLOBALS["VOTE_CACHE_VOTING"][$VOTE_ID]["QA"]["GA"] = "Y";
	}

	$arVote = $GLOBALS["VOTE_CACHE_VOTING"][$VOTE_ID]["V"];
	$arChannel = $GLOBALS["VOTE_CACHE_VOTING"][$VOTE_ID]["C"];
	$arQuestions =	$GLOBALS["VOTE_CACHE_VOTING"][$VOTE_ID]["QA"]["Q"];
	$arAnswers = $GLOBALS["VOTE_CACHE_VOTING"][$VOTE_ID]["QA"]["A"];
	$arMultiSelect = $GLOBALS["VOTE_CACHE_VOTING"][$VOTE_ID]["QA"]["M"];
	$arDropDown = $GLOBALS["VOTE_CACHE_VOTING"][$VOTE_ID]["QA"]["D"];
	$arGroupAnswers = $GLOBALS["VOTE_CACHE_VOTING"][$VOTE_ID]["QA"]["G"];
	return $arVote["ID"];
}

// return vote id for channel sid with check permissions and ACTIVE vote

/**
 * <p>Функция возвращает ID текущего опроса в группе.</p>
 *
 *
 * @param GROUP_SYMBOLIC_NAM $E  Символическое имя группы.
 *
 * @param  $lid  ID сайта. По умолчанию - текущий - константа "LANG". Необязательный
 * параметр.
 *
 * @param  $access  Минимальный уровень доступа к опросу для текущего
 * пользователя:<li>0 - доступ закрыт</li> <li>1 - право на просмотр
 * результатов опроса</li> <li>2 - право на участие в опросе<br>По
 * умолчанию access = 1, т.е. для того чтобы функция GetCurrentVote возвратила ID
 * текущего опроса группы, у пользователя на данную группу должно
 * быть, как минимум, право на просмотр результатов. Необязательный
 * параметр.</li>
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre>
 * &lt;?
 * // возвращает форму текущего опроса для заданной группы
 * function ShowCurrentVote($GROUP_SYMBOLIC_NAME, $lid=LANG)
 * {
 * 	$CURRENT_VOTE_ID = <b>GetCurrentVote</b>($GROUP_SYMBOLIC_NAME, $lid, 2);
 * 	if (intval($CURRENT_VOTE_ID)&gt;0) ShowVote($CURRENT_VOTE_ID);
 * }
 * ?&gt;
 * </pre>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/vote/function/getcurrentvote.php
 * @author Bitrix
 */
function GetCurrentVote($GROUP_SID, $site_id=SITE_ID, $access=1)
{
	$z = CVoteChannel::GetList($by, $order, array("SID"=>$GROUP_SID, "SID_EXACT_MATCH"=>"Y", "SITE"=>$site_id, "ACTIVE"=>"Y"), $is_filtered);
	if ($zr = $z->Fetch())
	{
		$perm = CVoteChannel::GetGroupPermission($zr["ID"]);
		if (intval($perm)>=$access)
		{
			$v = CVote::GetList($by, $order, array("CHANNEL_ID"=>$zr["ID"], "LAMP"=>"green"), $is_filtered);
			if ($vr = $v->Fetch()) return $vr["ID"];
		}
	}
	return 0;
}

// return PREIOUS vote id for channel sid with check permissions and ACTIVE vote

/**
 * <p>Функция возвращает ID предыдущего опроса в группе.</p>
 *
 *
 * @param GROUP_SYMBOLIC_NAM $E  Символическое имя группы.
 *
 * @param  $level  Уровень предыдущего опроса (1 - предыдущий, 2 - пред- предыдущий и
 * т.д.). По умолчанию - 1. Необязательный параметр.
 *
 * @param  $lid  ID сайта. По умолчанию - текущий (константа LANG). Необязательный
 * параметр.
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre>
 * &lt;?
 * // возвращает диаграмму результатов предыдущего опроса
 * function ShowPrevVoteResults($GROUP_SYMBOLIC_NAME, $level=1, $lid=LANG)
 * {
 * 	$PREV_VOTE_ID = <b>GetPrevVote</b>($GROUP_SYMBOLIC_NAME, $level, $lid);
 * 	if (intval($PREV_VOTE_ID)&gt;0) ShowVoteResults($PREV_VOTE_ID);
 * }
 * ?&gt;
 * </pre>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/vote/function/getprevvote.php
 * @author Bitrix
 */
function GetPrevVote($GROUP_SID, $level=1, $site_id=SITE_ID, $access=1)
{
	$VOTE_ID = 0;
	$z = CVoteChannel::GetList($by, $order, array("SID"=>$GROUP_SID, "SID_EXACT_MATCH"=>"Y", "SITE"=>$site_id, "ACTIVE"=>"Y"), $is_filtered);
	if ($zr = $z->Fetch())
	{
		$perm = CVoteChannel::GetGroupPermission($zr["ID"]);
		if (intval($perm)>=$access)
		{
			$v = CVote::GetList(($by = "s_date_start"), ($order = "desc"), array("CHANNEL_ID"=>$zr["ID"], "LAMP"=>"red"), $is_filtered);
			$i = 0;
			while ($vr=$v->Fetch())
			{
				$i++;
				if ($level==$i) 
				{
					$VOTE_ID = $vr["ID"];
					break;
				}
			}
		}
	}
	return intval($VOTE_ID);
}

// return votes list id for channel sid with check permissions and ACTIVE vote

/**
 * <p>Функция возвращает выборку из базы по опросам.</p>
 *
 *
 * @param  $GROUP_SYMBOLIC_NAME  Символическое имя группы опросов (по умолчанию функция вернет
 * выборку опросов из всех групп). Необязательный параметр.
 *
 * @param  $strSqlOrder  SQL код, содержащий параметры сортировки для выборки.
 * Необязательный параметр. По умолчанию: "ORDER BY C.C_SORT, C.ID, V.DATE_START desc"
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre>
 * Файл в публичной части сайта - "vote_list.php":
 * &lt;?
 * // Отображает список опросов (архив)
 * require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
 * if (CModule::IncludeModule("vote")) require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/vote/include.php");
 * if (strlen($APPLICATION-&gt;GetTitle())&lt;=0) $APPLICATION-&gt;SetTitle(GetMessage("VOTE_LIST_TITLE"));
 * require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_after.php");
 * ?&gt;&lt;?
 * if (CModule::IncludeModule("vote"))
 * { 
 * 	$votes = <b>GetVoteList</b>(); // список опросов
 * 	require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/include/vote/list/default.php"); // шаблон показа
 * }
 * require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog.php");
 * ?&gt;
 * 
 * 
 * 
 * &lt;?
 * 
 * // вывод всех активных опросов на текущем сайте, отсортированных в следующем порядке: по индексу сортировки канала, ID канала, индексу сортировки опроса, времени начала опроса. Активный опрос в
 * // данном контексте - это опрос, у которого атрибут ACTIVE установлен в Y, а время начала голосования меньше текущего.
 * 
 * require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
 * $APPLICATION-&gt;SetTitle("Результаты опроса");
 * $APPLICATION-&gt;AddChainItem("Архив опросов", "vote_list.php");
 * require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_after.php");
 * 
 * if (CModule::IncludeModule("vote"))
 * {
 *    $db_res = GetVoteList("");
 *    if (!!$db_res)
 *    {
 *       if (!empty($arResult["NAV_STRING"]))
 *       {
 *          ?&gt;;&lt;div class="vote-navigation-box vote-navigation-top"&gt;
 *             &lt;div class="vote-page-navigation"&gt;
 *                &lt;?=$arResult["NAV_STRING"]?&gt;
 *             &lt;/div&gt;&lt;div class="vote-clear-float"&gt;&lt;/div&gt;
 *          &lt;/div&gt;&lt;?
 *       }
 * 
 *       ?&gt;&lt;ol class="vote-items-list voting-list-box"&gt;&lt;?
 *       while ($arVote = $db_res-&gt;Fetch()) {
 *       ?&gt;&lt;li class="vote-item-vote &lt;?
 *          ?&gt;&lt;?=($arVote["LAMP"]=="green" ? "vote-item-vote-active " : ($arVote["LAMP"]=="red" ? "vote-item-vote-disable " : ""))?&gt;"&gt;
 *          &lt;div class="vote-item-header"&gt;&lt;?
 *             if (!empty($arVote["TITLE"])) { ?&gt;&lt;span class="vote-item-title"&gt;&lt;?=$arVote["TITLE"];?&gt;&lt;/span&gt;&lt;? }?&gt;
 *             &lt;div class="vote-clear-float"&gt;&lt;/div&gt;
 *          &lt;/div&gt;
 *          &lt;?
 *          $arDateBlocks = array();
 *          if (!!$arVote["DATE_START"])
 *             $arDateBlocks[] = '&lt;span class="vote-item-date-start"&gt;'.FormatDate($DB-&gt;DateFormatToPHP(CSite::GetDateFormat('FULL')), MakeTimeStamp($arVote["DATE_START"])).'&lt;/span&gt;';
 *          if (!!$arVote["DATE_END"] &amp;&amp; $arVote["DATE_END"] != "31.12.2030 23:59:59")
 *             $arDateBlocks[] = '&lt;span class="vote-item-date-end"&gt;'.FormatDate($DB-&gt;DateFormatToPHP(CSite::GetDateFormat('FULL')), MakeTimeStamp($arVote["DATE_END"])).'&lt;/span&gt;';
 *          if (!empty($arDateBlocks)) {
 *             ?&gt;&lt;div class="vote-item-date"&gt;=implode('vspan class="vote-item-date-sep"&gt; - &lt;/span&gt;', $arDateBlocks)?&gt;&lt;/div&gt;&lt;?
 *          }
 *          if ($arVote["COUNTER"] &gt; 0){
 *             ?&amp;qt;&lt;div class="vote-item-counter"&gt;&lt;span&gt;Голосов:&lt;/span&gt; &lt;?=$arVote["COUNTER"]?&gt;&lt;/div&gt;&lt;?
 *          }
 * 
 *          if (!empty($arVote["IMAGE"]) || !empty($arVote["DESCRIPTION"])):
 *          ?&gt;
 *          &lt;div class="vote-item-footer"&gt;
 *             &lt;?if (!empty($arVote["IMAGE"])):?&gt;
 *             &lt;div class="vote-item-image"&gt;
 *                &lt;img src="=$arVote["IMAGE"]["SRC"]?&gt;" width="&lt;?=$arVote["IMAGE"]["WIDTH"]?&gt;" height="&lt;?=$arVote["IMAGE"]["HEIGHT"]?&gt;" border="0" /&gt;
 *             &lt;/div&gt;
 *             &lt;?endif;
 *             if (!empty($arVote["DESCRIPTION"])):?&gt;
 *             &lt;div class="vote-item-description"&gt;&lt;?=$arVote["DESCRIPTION"];?&gt;&lt;/div&gt;
 *             &lt;?endif?&gt;
 *             &lt;div class="vote-clear-float"&gt;&lt;/div&gt;
 *          &lt;/div&gt;
 *          &lt;?
 *          endif;
 *          ?&gt;
 *       
 *       &lt;?
 *       }
 *    ?&gt;&lt;?
 *    }
 * 
 * }
 * ?&gt;&lt;style&gt;
 *    ol.vote-items-list, ol.vote-items-list li {
 *       margin: 0; padding: 0; border: none; font-size: 100%; list-style-type: none;}
 *    ol.vote-items-list li {
 *       padding: 0.55em;
 *       border: 1px solid #ccc;
 *       border-top: none;}
 *    ol.vote-items-list li:first-child { border-top: 1px solid #ccc; }
 *    .vote-item-title { font-weight:bold; }
 *    div.vote-item-date { font-style: italic; }
 *    div.vote-item-header { margin-bottom: 0.5em; }
 *    div.vote-item-footer { margin-top: 0.5em; }
 *    div.vote-item-image { float:left; padding-right:0.55em; }
 *    div.vote-clear-float { clear: both; }
 * &lt;/style&gt;&lt;?
 * require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog.php");
 * ?&gt;
 * </pre>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/vote/function/getvotelist.php
 * @author Bitrix
 */
function GetVoteList($GROUP_SID = "", $params = array(), $site_id = SITE_ID)
{
	$strSqlOrder = (is_string($params) ? $params : "ORDER BY C.C_SORT, C.ID, V.C_SORT, V.DATE_START desc");
	$params = (is_array($params) ? $params : array());
	if (array_key_exists("order", $params))
		$strSqlOrder = $params["order"];
	$arFilter["SITE"] = (array_key_exists("SITE_ID", $params)  ? $params["SITE_ID"] : $site_id);

	if (is_array($GROUP_SID) && !empty($GROUP_SID))
	{
		$arr = array();
		foreach ($GROUP_SID as $v)
		{
			if (!empty($v))
				$arr[] = $v;
		}
		if (!empty($arr))
			$arFilter["CHANNEL"] = $arr;
	}
	elseif (!empty($GROUP_SID))
	{
		$arFilter["CHANNEL"] = $GROUP_SID;
	}
	$z = CVote::GetPublicList($arFilter, $strSqlOrder, $params);
	return $z;
}

// return true if user already vote on this vote
function IsUserVoted($PUBLIC_VOTE_ID)
{
	global $USER, $APPLICATION;
	$PUBLIC_VOTE_ID = intval($PUBLIC_VOTE_ID);

	if ($PUBLIC_VOTE_ID <= 0)
		return false;

	$res = CVote::GetByID($PUBLIC_VOTE_ID);
	if($res && ($arVote = $res->GetNext(true, false)))
	{
		$VOTE_USER_ID = intval($APPLICATION->get_cookie("VOTE_USER_ID"));
		$res = CVote::UserAlreadyVote($arVote["ID"], $VOTE_USER_ID, $arVote["UNIQUE_TYPE"], $arVote["KEEP_IP_SEC"], $USER->GetID());
		return ($res != false);
	}

	return false;
}

// return random unvoted vote id for user whith check permissions

/**
 * <p>Функция возвращает ID первого попавшегося опроса по которому пользователь ещё не голосовал, с учётом прав пользователя, у которого должно быть право "на участие в опросе" (&gt;=2)</p>
 *
 *
 * @param li $d  Сайт группы опросов. По умолчанию - текущий (константа LANG).
 * Необязательный параметр.
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre>
 * Файл в публичной части сайта - "vote_any.php":
 * &lt;?
 * require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
 * // Отображает форму первого попавшегося опроса по которому пользователь ещё не голосовал
 * // если пользователь проголосовал, то перенаправляет его на файл отображающий результат данного опроса
 * if (CModule::IncludeModule("vote") &amp;&amp; $VOTING_OK=="Y") 
 * {
 * 	$VOTE_DIR = COption::GetOptionString("vote", "VOTE_DIR", "");
 * 	$z = CLang::GetByID(LANG);
 * 	$zr = $z-&gt;Fetch();
 * 	$LANG_DIR = $zr["DIR"];
 * 	$LANG_DIR = TrimExAll($LANG_DIR,"/");
 * 	$VOTE_DIR = TrimExAll($VOTE_DIR,"/");
 * 	LocalRedirect("/".$LANG_DIR."/".$VOTE_DIR."/vote_result.php?VOTE_ID=". $PUBLIC_VOTE_ID."&amp;VOTING_OK=".$VOTING_OK);
 * }
 * if (strlen($APPLICATION-&gt;GetTitle())&lt;=0) $APPLICATION-&gt;SetTitle(GetMessage("VOTE_VOTING_TITLE"));
 * require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_after.php");
 * ?&gt;&lt;?
 * if (CModule::IncludeModule("vote")) 
 * {
 * 	$VID = <b>GetAnyAccessibleVote</b>();
 * 	ShowVote($VID);
 * }
 * require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog.php");
 * ?&gt;
 * </pre>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/vote/function/getanyaccessiblevote.php
 * @author Bitrix
 */
function GetAnyAccessibleVote($site_id=SITE_ID, $channel_id=null)
{
	$arParams = array("ACTIVE"=>"Y","SITE"=>$site_id);

	if ($channel_id !== null)
	{
		$arParams['SID'] = $channel_id;
		$arParams['SID_EXACT_MATCH'] = 'Y';
	}

	$z = CVoteChannel::GetList($by="s_c_sort", $order="asc", $arParams, $is_filtered);
	$arResult = array();

	while ($zr = $z->Fetch())
	{
		$perm = CVoteChannel::GetGroupPermission($zr["ID"]);

		if (intval($perm)>=2)
		{
			$v = CVote::GetList($by, $order, array("CHANNEL_ID"=>$zr["ID"], "LAMP"=>"green"), $is_filtered);
			while ($vr = $v->Fetch()) 
			{
				if (!(IsUserVoted($vr['ID']))) $arResult[] = $vr['ID'];
			}
		}
	}

	if (sizeof($arResult) > 0)
		return array_rand(array_flip($arResult));

	return false;
}


/********************************************************************
				Functions for old templates
/*******************************************************************/
function GetTemplateList($type="SV", $path="xxx")
{
	$arReferenceId = array();
	$arReference = array();
	if ($path=="xxx")
	{
		if ($type=="SV")
			$path = COption::GetOptionString("vote", "VOTE_TEMPLATE_PATH");
		elseif ($type=="RV")
			$path = COption::GetOptionString("vote", "VOTE_TEMPLATE_PATH_VOTE");
		elseif ($type=="RQ")
			$path = COption::GetOptionString("vote", "VOTE_TEMPLATE_PATH_QUESTION");
	}
	if (is_dir($_SERVER["DOCUMENT_ROOT"].$path))
	{
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
	$arr = array("reference" => $arReference,"reference_id" => $arReferenceId);
	return $arr;
}

function arrAnswersSort(&$arr, $order="desc")
{
	$count = count($arr);
	for ($key1=0; $key1<$count; $key1++)
	{
		for ($key2=0; $key2<$count; $key2++)
		{
			$sort1 = intval($arr[$key1]["COUNTER"]);
			$sort2 = intval($arr[$key2]["COUNTER"]);
			if ($order=="asc")
			{
				if ($sort1<$sort2)
				{
					$arr_tmp = $arr[$key1];
					$arr[$key1] = $arr[$key2];
					$arr[$key2] = $arr_tmp;
				}
			}
			else
			{
				if ($sort1>$sort2)
				{
					$arr_tmp = $arr[$key1];
					$arr[$key1] = $arr[$key2];
					$arr[$key2] = $arr_tmp;
				}
			}
		}
	}
}

// return current vote form for channel

/**
 * <p>Функция выводит HTML-код текущего опроса группы.</p>
 *
 *
 * @param GROUP_SYMBOLIC_NAM $E  Символическое имя группы.
 *
 * @param  $lid  ID сайта. Необязательный параметр. По умолчанию - текущий
 * (константа LANG).
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre>
 * Файл в публичной части сайта - "vote_current.php":
 * &lt;?
 * require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
 * // Отображает форму текущего опроса заданной группы,
 * // после голосования перенаправляет на файл отображающий результат опроса
 * if (CModule::IncludeModule("vote") &amp;&amp; $VOTING_OK=="Y") 
 * {
 * 	$VOTE_DIR = COption::GetOptionString("vote", "VOTE_DIR", "");
 * 	$z = CLang::GetByID(LANG);
 * 	$zr = $z-&gt;Fetch();
 * 	$LANG_DIR = $zr["DIR"];
 * 	$LANG_DIR = TrimExAll($LANG_DIR,"/");
 * 	$VOTE_DIR = TrimExAll($VOTE_DIR,"/");
 * 	LocalRedirect("/".$LANG_DIR."/".$VOTE_DIR."/vote_result.php?VOTE_ID=".$PUBLIC_VOTE_ID."&amp;VOTING_OK=Y");
 * }
 * if (strlen($APPLICATION-&gt;GetTitle())&lt;=0) $APPLICATION-&gt;SetTitle(GetMessage("VOTE_VOTING_TITLE"));
 * require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_after.php");
 * ?&gt;&lt;?
 * if (CModule::IncludeModule("vote")) <b>ShowCurrentVote</b>("ANKETA");
 * require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog.php");
 * ?&gt;
 * </pre>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/vote/function/showcurrentvote.php
 * @author Bitrix
 */
function ShowCurrentVote($GROUP_SID, $site_id=SITE_ID)
{
	$CURRENT_VOTE_ID = GetCurrentVote($GROUP_SID, $site_id, 2);
	if (intval($CURRENT_VOTE_ID)>0) ShowVote($CURRENT_VOTE_ID);
}
// return previous vote results
function ShowPrevVoteResults($GROUP_SID, $level=1, $site_id=SITE_ID)
{
	$PREV_VOTE_ID = GetPrevVote($GROUP_SID, $level, $site_id);
	if (intval($PREV_VOTE_ID)>0) ShowVoteResults($PREV_VOTE_ID);
}
// return current vote results
function ShowCurrentVoteResults($GROUP_SID, $site_id=SITE_ID)
{
	$CURRENT_VOTE_ID = GetCurrentVote($GROUP_SID,  $site_id);
	if (intval($CURRENT_VOTE_ID)>0) ShowVoteResults($CURRENT_VOTE_ID);
}

// return current vote form with check permissions

/**
 * <p>Функция выводит HTML-код формы опроса.</p>
 *
 *
 * @param VOTE_I $D  ID опроса.</bo
 *
 * @param  $template  Имя файла - шаблона для показа опроса. По умолчанию будет
 * использован шаблон, заданный в параметрах опроса. Необязательный
 * параметр.
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre>
 * Файл в публичной части сайта - "vote.php":
 * &lt;?
 * // Отображает форму опроса по заданному ID
 * require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
 * if (CModule::IncludeModule("vote") &amp;&amp; $VOTING_OK=="Y") 
 * {
 * 	$VOTE_DIR = COption::GetOptionString("vote", "VOTE_DIR", "");
 * 	$z = CLang::GetByID(LANG);
 * 	$zr = $z-&gt;Fetch();
 * 	$LANG_DIR = $zr["DIR"];
 * 	$LANG_DIR = TrimExAll($LANG_DIR,"/");
 * 	$VOTE_DIR = TrimExAll($VOTE_DIR,"/");
 * 	LocalRedirect("/".$LANG_DIR."/".$VOTE_DIR."/vote_result.php?VOTE_ID=".$PUBLIC_VOTE_ID."&amp;VOTING_OK=Y");
 * }
 * if (strlen($APPLICATION-&gt;GetTitle())&lt;=0) $APPLICATION-&gt;SetTitle(GetMessage("VOTE_VOTING_TITLE"));
 * require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_after.php");
 * ?&gt;&lt;?
 * if (CModule::IncludeModule("vote")) <b>ShowVote</b>($VOTE_ID);
 * require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog.php");
 * ?&gt;
 * </pre>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/vote/function/showvote.php
 * @author Bitrix
 */
function ShowVote($VOTE_ID, $template1="")
{
	global $VOTING_LAMP, $VOTING_OK, $USER_ALREADY_VOTE, $USER_GROUP_PERMISSION, $APPLICATION;

	$VOTING_LAMP = ($VOTING_LAMP == "green") ? $VOTING_LAMP : "red";
	$VOTING_OK = ($VOTING_OK == "Y") ? $VOTING_OK : "N";
	$USER_ALREADY_VOTE = ($USER_ALREADY_VOTE == "Y") ? $USER_ALREADY_VOTE : "N";
	$USER_GROUP_PERMISSION = intval($USER_GROUP_PERMISSION);
	if ($USER_GROUP_PERMISSION > 2) $USER_GROUP_PERMISSION = 0;

	$VOTE_ID = GetVoteDataByID($VOTE_ID, $arChannel, $arVote, $arQuestions, $arAnswers, $arDropDown, $arMultiSelect, $arGroupAnswers, "N");
	if (intval($VOTE_ID)>0)
	{
		$perm = CVoteChannel::GetGroupPermission($arChannel["ID"]);
		/***** for old pre-component templates **********/
		$GLOBALS["VOTE_PERMISSION"] = $perm;
		/***** /old *************************************/
		if (intval($perm)>=2)
		{
			$template = (strlen($arVote["TEMPLATE"])<=0) ? "default.php" : $arVote["TEMPLATE"];
			require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/vote/include.php");
			IncludeModuleLangFile(__FILE__);
			$path = COption::GetOptionString("vote", "VOTE_TEMPLATE_PATH");
			if (strlen($template1)>0) $template = $template1;

			if ($APPLICATION->GetShowIncludeAreas())
			{
				$arIcons = Array();
				if (CModule::IncludeModule("fileman"))
				{
					$arIcons[] = Array(
								"URL" => "/bitrix/admin/fileman_file_edit.php?lang=".LANGUAGE_ID."&site=".SITE_ID."&full_src=Y&path=". urlencode($path.$template),
								"SRC" => "/bitrix/images/vote/panel/edit_template.gif",
								"ALT" => GetMessage("VOTE_PUBLIC_ICON_TEMPLATE")
							);
					$arrUrl = parse_url($_SERVER["REQUEST_URI"]);
					$arIcons[] = Array(
								"URL" => "/bitrix/admin/fileman_file_edit.php?lang=".LANGUAGE_ID."&site=".SITE_ID."&full_src=Y&path=". urlencode($arrUrl["path"]),
								"SRC" => "/bitrix/images/vote/panel/edit_file.gif",
								"ALT" => GetMessage("VOTE_PUBLIC_ICON_HANDLER")
							);
				}
				$arIcons[] = Array(
							"URL" => "/bitrix/admin/vote_edit.php?lang=".LANGUAGE_ID."&ID=".$VOTE_ID,
							"SRC" => "/bitrix/images/vote/panel/edit_vote.gif",
							"ALT" => GetMessage("VOTE_PUBLIC_ICON_SETTINGS")
						);
				echo $APPLICATION->IncludeStringBefore($arIcons);
			}
			$template = Rel2Abs('/', $template);
			include($_SERVER["DOCUMENT_ROOT"].$path.$template);
			if ($APPLICATION->GetShowIncludeAreas())
			{
				echo $APPLICATION->IncludeStringAfter();
			}
		}
	}
}
// return current vote results with check permissions

/**
 * <p>Функция выводит HTML-код с диаграммой результатов опроса.</p>
 *
 *
 * @param VOTE_I $D  ID опроса.</bo
 *
 * @param  $template  Имя файла - шаблона для показа результатов опроса. По умолчанию
 * будет использован шаблон, заданный в параметрах опроса.
 * Необязательный параметр.
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre>
 * Файл в публичной части сайта - "vote_result.php":
 * &lt;?
 * // Отображает результат опроса по заданному ID
 * require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
 * if (strlen($APPLICATION-&gt;GetTitle())&lt;=0) $APPLICATION-&gt;SetTitle(GetMessage("VOTE_RESULTS_TITLE"));
 * //$APPLICATION-&gt;AddChainItem(GetMessage("VOTE_VOTES_LIST"), "vote_list.php");
 * require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_after.php");
 * ?&gt;&lt;?
 * if (CModule::IncludeModule("vote")) <b>ShowVoteResults</b>($VOTE_ID);
 * require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog.php");
 * ?&gt;
 * </pre>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/vote/function/showvoteresults.php
 * @author Bitrix
 */
function ShowVoteResults($VOTE_ID, $template1="")
{
	global $APPLICATION;
	$VOTE_ID = GetVoteDataByID($VOTE_ID, $arChannel, $arVote, $arQuestions, $arAnswers, $arDropDown, $arMultiSelect, $arGroupAnswers, "Y");
	if (intval($VOTE_ID)>0)
	{
		/***** for old pre-component templates **********/
		global $VOTE_PERMISSION;
		$VOTE_PERMISSION = CVote::UserGroupPermission($arChannel["ID"]);
		/***** /old *************************************/

		$perm = CVoteChannel::GetGroupPermission($arChannel["ID"]);
		if (intval($perm)>=1)
		{
			$template = (strlen($arVote["RESULT_TEMPLATE"])<=0) ? "default.php" : $arVote["RESULT_TEMPLATE"];
			require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/vote/include.php");
			IncludeModuleLangFile(__FILE__);
			$path = COption::GetOptionString("vote", "VOTE_TEMPLATE_PATH_VOTE");
			if (strlen($template1)>0) $template = $template1;
			if ($APPLICATION->GetShowIncludeAreas())
			{
				$arIcons = Array();
				if (CModule::IncludeModule("fileman"))
				{
					$arIcons[] =
							Array(
								"URL" => "/bitrix/admin/fileman_file_edit.php?lang=".LANGUAGE_ID."&site=".SITE_ID."&full_src=Y&path=". urlencode($path.$template),
								"SRC" => "/bitrix/images/vote/panel/edit_template.gif",
								"ALT" => GetMessage("VOTE_PUBLIC_ICON_TEMPLATE")
							);
					$arrUrl = parse_url($_SERVER["REQUEST_URI"]);
					$arIcons[] =
							Array(
								"URL" => "/bitrix/admin/fileman_file_edit.php?lang=".LANGUAGE_ID."&site=".SITE_ID."&full_src=Y&path=". urlencode($arrUrl["path"]),
								"SRC" => "/bitrix/images/vote/panel/edit_file.gif",
								"ALT" => GetMessage("VOTE_PUBLIC_ICON_HANDLER")
							);
				}
				$arIcons[] =
						Array(
							"URL" => "/bitrix/admin/vote_edit.php?lang=".LANGUAGE_ID."&ID=".$VOTE_ID,
							"SRC" => "/bitrix/images/vote/panel/edit_vote.gif",
							"ALT" => GetMessage("VOTE_PUBLIC_ICON_SETTINGS")
						);
				echo $APPLICATION->IncludeStringBefore($arIcons);
			}
			$template = Rel2Abs('/', $template);
			include($_SERVER["DOCUMENT_ROOT"].$path.$template);
			if ($APPLICATION->GetShowIncludeAreas())
			{
				echo $APPLICATION->IncludeStringAfter();
			}
		}
	}
}

function fill_arc($start, $end, $color)
{
	global $diameter, $centerX, $centerY, $im, $radius;
	$radius = $diameter/2;
	imagearc($im, $centerX, $centerY, $diameter, $diameter, $start, $end+1, $color);
	imageline($im, $centerX, $centerY, $centerX + cos(deg2rad($start)) * $radius, $centerY + sin(deg2rad($start)) * $radius, $color);
	imageline($im, $centerX, $centerY, $centerX + cos(deg2rad($end)) * $radius, $centerY + sin(deg2rad($end)) * $radius, $color);
	$x = $centerX + $radius * 0.5 * cos(deg2rad($start+($end-$start)/2));
	$y = $centerY + $radius * 0.5 * sin(deg2rad($start+($end-$start)/2));
	imagefill ($im, $x, $y, $color);
}

function DecRGBColor($hex, &$dec1, &$dec2, &$dec3)
{
	if (substr($hex,0,1)!="#") $hex = "#".$hex;
	$dec1 = hexdec(substr($hex,1,2));
	$dec2 = hexdec(substr($hex,3,2));
	$dec3 = hexdec(substr($hex,5,2));
}

function DecColor($hex)
{
	if (substr($hex,0,1)!="#") $hex = "#".$hex;
	$dec = hexdec(substr($hex,1,6));
	return intval($dec);
}

function HexColor($dec)
{
	$hex = sprintf("%06X",$dec); 
	return $hex;
}

function GetNextColor(&$color, &$current_color, $total, $start_color="0000CC", $end_color="FFFFCC")
{
	if (substr($start_color,0,1)=="#") $start_color = substr($start_color,1,6);
	if (substr($end_color,0,1)=="#") $end_color = substr($end_color,1,6);
	if (substr($current_color,0,1)=="#") $current_color = substr($current_color,1,6);
	if (strlen($current_color)<=0) $color = "#".$start_color;
	else
	{
		$step = round((hexdec($end_color)-hexdec($start_color))/$total);
		if (intval($step)<=0) $step = "1500";
		$dec = DecColor($current_color)+intval($step);
		if ($dec<hexdec($start_color)) $dec = $start_color;
		elseif ($dec>hexdec($end_color)) $dec = $end_color;
		elseif ($dec>hexdec("FFFFFF")) $dec = "000000"; 
		else $dec = HexColor($dec);
		$color = "#".$dec;
	}
	$current_color = $color;
}