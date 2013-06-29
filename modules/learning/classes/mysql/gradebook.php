<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/learning/classes/general/gradebook.php");

// 2012-04-10 Checked/modified for compatibility with new data model

/**
 * 
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/learning/classes/cgradebook/index.php
 * @author Bitrix
 */
class CGradeBook extends CAllGradeBook
{
	// 2012-04-10 Checked/modified for compatibility with new data model
	
	/**
	 * <p>Возвращает список записей журнала по фильтру arFilter, отсортированный в порядке arOrder. Учитываются права доступа текущего пользователя.</p>
	 *
	 *
	 *
	 *
	 * @param array $arrayarOrder = Array("ID"=>"DESC") Массив для сортировки результата. Массив вида <i>array("поле
	 * сортировки"=&gt;"направление сортировки" [, ...])</i>.<br> Поле для
	 * сортировки может принимать значения: <ul> <li> <b>ID</b> - идентификатор
	 * записи;</li> <li> <b>TEST_ID</b> - идентификатор теста;</li> <li> <b>STUDENT_ID</b> -
	 * идентификатор студента ;</li> <li> <b>RESULT</b> - количество баллов;</li> <li>
	 * <b>MAX_RESULT</b> - максимальное количество баллов;</li> <li> <b>COMPLETED</b> - тест
	 * пройден;</li> <li> <b>USER_NAME</b> - имя студента;</li> <li> <b>TEST_NAME</b> - название
	 * теста.</li> </ul> Направление сортировки может принимать значения: <ul>
	 * <li> <b>asc</b> - по возрастанию;</li> <li> <b>desc</b> - по убыванию;</li> </ul>
	 * Необязательный. По умолчанию фильтруется по убыванию
	 * идентификатора записи журнала.
	 *
	 *
	 *
	 * @param array $arrayarFilter = Array() Массив вида <i> array("фильтруемое поле"=&gt;"значение фильтра" [, ...])</i>.
	 * Фильтруемое поле может принимать значения: <ul> <li> <b>ID</b> -
	 * идентификатор записи;</li> <li> <b>TEST_ID</b> - идентификатор теста;</li> <li>
	 * <b>STUDENT_ID</b> - идентификатор студента;</li> <li> <b>RESULT</b> - количество
	 * баллов;</li> <li> <b>MAX_RESULT</b> - максимальное количество баллов;</li> <li>
	 * <b>COMPLETED</b> - тест пройден (Y|N);</li> <li> <b>USER</b> - пользователь (возможны
	 * сложные условия по полям пользователя ID, LOGIN, NAME, LAST_NAME);</li> <li>
	 * <b>MIN_PERMISSION</b> - минимальный уровень доcтупа. По умолчанию "R". Список
	 * прав доступа см. в <a
	 * href="http://dev.1c-bitrix.ruapi_help/learning/classes/ccourse/setpermission.php">CCourse::SetPermission</a>.</li> <li>
	 * <b>CHECK_PERMISSIONS</b> - проверять уровень доступа. Если установлено
	 * значение "N" - права доступа не проверяются.</li> </ul> Перед названием
	 * фильтруемого поля может указать тип фильтрации: <ul> <li>"!" - не
	 * равно</li> <li>"&lt;" - меньше</li> <li>"&lt;=" - меньше либо равно</li> <li>"&gt;" -
	 * больше</li> <li>"&gt;=" - больше либо равно</li> </ul> <br> "<i>значения
	 * фильтра</i>" - одиночное значение или массив.<br><br> Необязательный.
	 * По умолчанию записи не фильтруются.
	 *
	 *
	 *
	 * @return CDBResult <p>Возвращается объект <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a>.</p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * if (CModule::IncludeModule("learning"))
	 * {
	 *     $TEST_ID = 45;
	 *     $res = CGradebook::GetList(
	 *         Array("ID" =&gt; "ASC"), 
	 *         Array("TEST_ID" =&gt; $TEST_ID)
	 *     );
	 * 
	 *     while ($arGradebook = $res-&gt;GetNext())
	 *     {
	 *         echo "Student: ".$arGradebook["USER_NAME"]."; Test name: ".$arGradebook["TEST_NAME"]."; Completed: ".$arGradebook["COMPLETED"]."&lt;br&gt;";
	 *     }
	 * }
	 * 
	 * ?&gt;
	 * 
	 * &lt;?
	 * 
	 * if (CModule::IncludeModule("learning"))
	 * {
	 *     $TEST_ID = 45;
	 *     $STUDENT_ID = 3;
	 * 
	 *     $res = CGradebook::GetList(
	 *         Array("ID" =&gt; "ASC"), 
	 *         Array("CHECK_PERMISSIONS" =&gt; "N", "TEST_ID" =&gt; $TEST_ID, "STUDENT_ID" =&gt; $STUDENT_ID)
	 *     );
	 * 
	 *     while ($arGradebook = $res-&gt;GetNext())
	 *     {
	 *         echo "Student: ".$arGradebook["USER_NAME"]."; Test name: ".$arGradebook["TEST_NAME"]."; Completed: ".$arGradebook["COMPLETED"]."&lt;br&gt;";
	 *     }
	 * 
	 * }
	 * 
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/learning/classes/cgradebook/index.php">CGradeBook</a>::<a
	 * href="http://dev.1c-bitrix.ruapi_help/learning/classes/cgradebook/getbyid.php">GetByID</a> </li> <li><a
	 * href="http://dev.1c-bitrix.ruapi_help/learning/fields.php#gradebook">Поля журнала</a></li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/learning/classes/cgradebook/getlist.php
	 * @author Bitrix
	 */
	public static function GetList($arOrder=Array(), $arFilter=Array())
	{
		global $DB, $USER, $APPLICATION;

		$oPermParser = new CLearnParsePermissionsFromFilter ($arFilter);
		$arSqlSearch = CGradeBook::GetFilter($arFilter);

		$strSqlSearch = "";
		for($i=0; $i<count($arSqlSearch); $i++)
			if(strlen($arSqlSearch[$i])>0)
				$strSqlSearch .= " AND ".$arSqlSearch[$i]." ";

		//Sites
		$SqlSearchLang = "''";
		if (array_key_exists("SITE_ID", $arFilter))
		{
			$arLID = Array();

			if(is_array($arFilter["SITE_ID"]))
				$arLID = $arFilter["SITE_ID"];
			else
			{
				if (strlen($arFilter["SITE_ID"]) > 0)
					$arLID[] = $arFilter["SITE_ID"];
			}

			foreach($arLID as $v)
				$SqlSearchLang .= ", '".$DB->ForSql($v, 2)."'";
		}

		$strSql =
		"SELECT DISTINCT G.*, T.NAME as TEST_NAME, T.COURSE_ID as COURSE_ID, 
		T.APPROVED as TEST_APPROVED,
		(T.ATTEMPT_LIMIT + G.EXTRA_ATTEMPTS) AS ATTEMPT_LIMIT, TUL.NAME as COURSE_NAME, 
		C.LINKED_LESSON_ID AS LINKED_LESSON_ID, ".
		$DB->Concat("'('",'U.LOGIN',"') '","CASE WHEN U.NAME IS NULL THEN '' ELSE U.NAME END","' '", "CASE WHEN U.LAST_NAME IS NULL THEN '' ELSE U.LAST_NAME END")." as USER_NAME, U.ID as USER_ID ".
		"FROM b_learn_gradebook G ".
		"INNER JOIN b_learn_test T ON G.TEST_ID = T.ID ".
		"INNER JOIN b_user U ON U.ID = G.STUDENT_ID ".
		"LEFT JOIN b_learn_course C ON C.ID = T.COURSE_ID ".
		"LEFT JOIN b_learn_lesson TUL ON TUL.ID = C.LINKED_LESSON_ID ".
		"LEFT JOIN b_learn_test_mark TM ON G.TEST_ID = TM.TEST_ID ".
		(strlen($SqlSearchLang) > 2 ? "LEFT JOIN b_learn_course_site CS ON C.ID = CS.COURSE_ID " : "")
		. "WHERE 
			(TM.SCORE IS NULL 
			OR TM.SCORE = 
				(SELECT SCORE 
				FROM b_learn_test_mark 
				WHERE SCORE >= (G.RESULT/G.MAX_RESULT*100) 
				ORDER BY SCORE ASC 
				LIMIT 1)
			) ";

		if ($oPermParser->IsNeedCheckPerm())
			$strSql .= " AND TUL.ID IN (" . $oPermParser->SQLForAccessibleLessons() . ") ";

		$strSql .= $strSqlSearch;
		
		if (strlen($SqlSearchLang) > 2)
			$strSql .= " AND CS.SITE_ID IN (" . $SqlSearchLang . ")";


		/* was:
		$bCheckPerm = ($APPLICATION->GetUserRight("learning") < "W" && !$USER->IsAdmin() && $arFilter["CHECK_PERMISSIONS"] != "N");

		$strSql =
		"SELECT DISTINCT G.*, T.NAME as TEST_NAME, T.COURSE_ID as COURSE_ID, (T.ATTEMPT_LIMIT + G.EXTRA_ATTEMPTS) AS ATTEMPT_LIMIT, C.NAME as COURSE_NAME, C.LINKED_LESSON_ID AS LINKED_LESSON_ID ".
		$DB->Concat("'('",'U.LOGIN',"') '","CASE WHEN U.NAME IS NULL THEN '' ELSE U.NAME END","' '", "CASE WHEN U.LAST_NAME IS NULL THEN '' ELSE U.LAST_NAME END")." as USER_NAME, U.ID as USER_ID ".
		"FROM b_learn_gradebook G ".
		"INNER JOIN b_learn_test T ON G.TEST_ID = T.ID ".
		"INNER JOIN b_user U ON U.ID = G.STUDENT_ID ".
		"LEFT JOIN b_learn_course C ON C.ID = T.COURSE_ID ".
		"LEFT JOIN b_learn_lesson TUL ON TUL.ID = C.LINKED_LESSON_ID ".
		"LEFT JOIN b_learn_test_mark TM ON G.TEST_ID = TM.TEST_ID ".
		(strlen($SqlSearchLang) > 2 ? "LEFT JOIN b_learn_course_site CS ON C.ID = CS.COURSE_ID " : "").
		($bCheckPerm ? "LEFT JOIN b_learn_course_permission CP ON CP.COURSE_ID = C.ID " : "").
		"WHERE 1=1 ".
		"AND (TM.SCORE IS NULL OR TM.SCORE = (SELECT SCORE FROM b_learn_test_mark WHERE SCORE >= (G.RESULT/G.MAX_RESULT*100) ORDER BY SCORE ASC LIMIT 1)) ".
		($bCheckPerm ?
		"AND CP.USER_GROUP_ID IN (".$USER->GetGroups().") ".
		"AND CP.PERMISSION >= '".(strlen($arFilter["MIN_PERMISSION"])==1 ? $arFilter["MIN_PERMISSION"] : "R")."' ".
		"AND (CP.PERMISSION='X' OR TUL.ACTIVE='Y')"
		:"").
		$strSqlSearch.
		(strlen($SqlSearchLang) > 2 ? " AND CS.SITE_ID IN (".$SqlSearchLang.")" : "");
		*/

		if (!is_array($arOrder))
			$arOrder = Array();

		foreach($arOrder as $by=>$order)
		{
			$by = strtolower($by);
			$order = strtolower($order);
			if ($order!="asc")
				$order = "desc";

			if ($by == "id")							$arSqlOrder[] = " G.ID ".$order." ";
			elseif ($by == "student_id")		$arSqlOrder[] = " G.STUDENT_ID ".$order." ";
			elseif ($by == "test_id")				$arSqlOrder[] = " G.TEST_ID ".$order." ";
			elseif ($by == "completed")		$arSqlOrder[] = " G.COMPLETED ".$order." ";
			elseif ($by == "result")				$arSqlOrder[] = " G.RESULT ".$order." ";
			elseif ($by == "max_result")		$arSqlOrder[] = " G.MAX_RESULT ".$order." ";
			elseif ($by == "user_name")		$arSqlOrder[] = " USER_NAME ".$order." ";
			elseif ($by == "test_name")		$arSqlOrder[] = " TEST_NAME ".$order." ";
			else
			{
				$arSqlOrder[] = " G.ID ".$order." ";
				$by = "id";
			}
		}

		$strSqlOrder = "";
		DelDuplicateSort($arSqlOrder);
		for ($i=0; $i<count($arSqlOrder); $i++)
		{
			if($i==0)
				$strSqlOrder = " ORDER BY ";
			else
				$strSqlOrder .= ",";

			$strSqlOrder .= $arSqlOrder[$i];
		}

		$strSql .= $strSqlOrder;

		//echo $strSql;
		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

	}
}
?>
