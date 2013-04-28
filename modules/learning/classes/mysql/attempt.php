<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/learning/classes/general/attempt.php");

// 2012-04-14 Checked/modified for compatibility with new data model

/**
 * 
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/learning/classes/ctestattempt/index.php
 * @author Bitrix
 */
class CTestAttempt extends CAllTestAttempt
{
	// 2012-04-13 Checked/modified for compatibility with new data model
	public static function DoInsert($arInsert, $arFields)
	{
		global $DB;

		if (strlen($arInsert[0]) <= 0 || strlen($arInsert[0])<= 0)		// BUG ?
			return false;

		$strSql =
			"INSERT INTO b_learn_attempt(DATE_START, ".$arInsert[0].") ".
			"VALUES(".$DB->CurrentTimeFunction().", ".$arInsert[1].")";

		if($DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
			return $DB->LastID();

		return false;
	}


	// 2012-04-14 Checked/modified for compatibility with new data model
	public static function _CreateAttemptQuestionsSQLFormer($ATTEMPT_ID, $arTest, $clauseAllChildsLessons, $courseLessonId)
	{
		$strSql =
		"INSERT INTO b_learn_test_result (ATTEMPT_ID, QUESTION_ID)
		SELECT " . ($ATTEMPT_ID + 0) . " ,Q.ID
		FROM b_learn_lesson L
		INNER JOIN b_learn_question Q ON L.ID = Q.LESSON_ID
		WHERE (L.ID IN (" . $clauseAllChildsLessons . ") OR (L.ID = " . ($courseLessonId + 0) . ") ) 
		AND Q.ACTIVE = 'Y' "
		. ($arTest["INCLUDE_SELF_TEST"] != "Y" ? "AND Q.SELF = 'N' " : "").
		"ORDER BY " . ($arTest["RANDOM_QUESTIONS"] == "Y" ? CTest::GetRandFunction() : "Q.SORT ").
		($arTest["QUESTIONS_AMOUNT"] > 0 ? "LIMIT " . ($arTest["QUESTIONS_AMOUNT"] + 0) : "");

		return ($strSql);
	}


	// 2012-04-14 Checked/modified for compatibility with new data model
	
	/**
	 * <p>Создаёт план вопросов для указанной попытки.</p>
	 *
	 *
	 *
	 *
	 * @param int $ATTEMPT_ID  Идентификатор попытки.
	 *
	 *
	 *
	 * @return bool <p>Метод возвращает <i>true</i>, если создание плана вопросов прошло
	 * успешно. При возникновении ошибки метод вернёт <i>false</i>, а в
	 * исключениях будут содержаться ошибки.</p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * if (CModule::IncludeModule("learning"))
	 * {
	 *     $ATTEMPT_ID = 563;
	 * 
	 *     $success = CTestAttempt::CreateAttemptQuestions($ATTEMPT_ID);
	 * 
	 *     if($success)
	 *     {
	 *         echo "Questions have been created.";
	 *     }
	 *     else
	 *     {
	 *         if($ex = $APPLICATION-&gt;GetException())
	 *             echo "Error: ".$ex-&gt;GetString();
	 *     }
	 * 
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul><li> <a href="http://dev.1c-bitrix.ruapi_help/learning/classes/ctestresult/index.php">CTestResult</a>::<a
	 * href="http://dev.1c-bitrix.ruapi_help/learning/classes/ctestresult/add.php">Add</a> </li></ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/learning/classes/ctestattempt/createattemptquestions.php
	 * @author Bitrix
	 */
	public static function CreateAttemptQuestions($ATTEMPT_ID)
	{
		// This function generates database-specific SQL code
		$arCallbackSqlFormer = array ('CTestAttempt', '_CreateAttemptQuestionsSQLFormer');

		return (self::_CreateAttemptQuestions($arCallbackSqlFormer, $ATTEMPT_ID));
	}


	// 2012-04-13 Checked/modified for compatibility with new data model
	public static function _GetListSQLFormer ($sSelect, $obUserFieldsSql, $bCheckPerm, $USER, $arFilter, $strSqlSearch)
	{
		$oPermParser = new CLearnParsePermissionsFromFilter ($arFilter);

		$strSql =
		"SELECT DISTINCT ".
		$sSelect." ".
		$obUserFieldsSql->GetSelect()." ".
		"FROM b_learn_attempt A ".
		"INNER JOIN b_learn_test T ON A.TEST_ID = T.ID ".
		"INNER JOIN b_user U ON U.ID = A.STUDENT_ID ".
		"LEFT JOIN b_learn_course C ON C.ID = T.COURSE_ID ".
		"LEFT JOIN b_learn_test_mark TM ON A.TEST_ID = TM.TEST_ID ".
		$obUserFieldsSql->GetJoin("A.ID") .
		" WHERE 
			(TM.SCORE IS NULL 
			OR TM.SCORE = 
				(SELECT MIN(SCORE) 
					FROM b_learn_test_mark 
					WHERE SCORE >= 
						CASE WHEN A.STATUS = 'F' 
							THEN 1.0*A.SCORE/A.MAX_SCORE*100 
							ELSE 0 
						END 
						AND TEST_ID = A.TEST_ID
				)
			) ";

		if ($oPermParser->IsNeedCheckPerm())
			$strSql .= " AND C.LINKED_LESSON_ID IN (" . $oPermParser->SQLForAccessibleLessons() . ") ";

		$strSql .= $strSqlSearch;

		/* was:
		$strSql =
		"SELECT DISTINCT ".
		$sSelect." ".
		$obUserFieldsSql->GetSelect()." ".
		"FROM b_learn_attempt A ".
		"INNER JOIN b_learn_test T ON A.TEST_ID = T.ID ".
		"INNER JOIN b_user U ON U.ID = A.STUDENT_ID ".
		"LEFT JOIN b_learn_course C ON C.ID = T.COURSE_ID ".
		"LEFT JOIN b_learn_test_mark TM ON A.TEST_ID = TM.TEST_ID ".
		$obUserFieldsSql->GetJoin("A.ID")." ".
		($bCheckPerm ? "LEFT JOIN b_learn_course_permission CP ON CP.COURSE_ID = C.ID " : "").
		"WHERE 1=1 ".
		"AND (TM.SCORE IS NULL OR TM.SCORE = (SELECT MIN(SCORE) FROM b_learn_test_mark WHERE SCORE >= CASE WHEN A.STATUS = 'F' THEN 1.0*A.SCORE/A.MAX_SCORE*100 ELSE 0 END AND TEST_ID = A.TEST_ID)) ".
		($bCheckPerm ?
		"AND CP.USER_GROUP_ID IN (".$USER->GetGroups().") ".
		"AND CP.PERMISSION >= '".(strlen($arFilter["MIN_PERMISSION"])==1 ? $arFilter["MIN_PERMISSION"] : "R")."' ".
		"AND (CP.PERMISSION='X' OR C.ACTIVE='Y')"
		:"").
		$strSqlSearch;
		*/

		return ($strSql);
	}


	// 2012-04-13 Checked/modified for compatibility with new data model
	
	/**
	 * <p>Возвращает список попыток по фильтру <b>arFilter</b>, отсортированный в порядке <b>arOrder</b>. Учитываются права доступа текущего пользователя.</p>
	 *
	 *
	 *
	 *
	 * @param array $arrayarOrder = Array("ID"=>"DESC") Массив для сортировки результата. Массив вида <i>array("поле
	 * сортировки"=&gt;"направление сортировки" [, ...])</i>.<br>Поле для
	 * сортировки может принимать значения: <ul> <li> <b>ID</b> - идентификатор
	 * попытки; </li> <li> <b>TEST_ID</b> - идентификатор теста; </li> <li> <b>STUDENT_ID</b> -
	 * идентификатор студента ; </li> <li> <b>DATE_START</b> - дата начала попытки; </li>
	 * <li> <b>DATE_END</b> - дата окончания попытки; </li> <li> <b>STATUS</b> - статус
	 * попытки; </li> <li> <b>SCORE</b> - количество баллов; </li> <li> <b>MAX_SCORE</b> -
	 * максимальное количество баллов; </li> <li> <b>COMPLETED</b> - тест пройден; </li>
	 * <li> <b>QUESTIONS</b> - количество вопросов; </li> <li> <b>USER_NAME</b> - имя студента ;
	 * </li> <li> <b>TEST_NAME</b> - название теста. </li> </ul>Направление сортировки
	 * может принимать значения: <ul> <li> <b>asc</b> - по возрастанию; </li> <li>
	 * <b>desc</b> - по убыванию; </li> </ul>Необязательный. По умолчанию
	 * фильтруется по убыванию идентификатора попытки.
	 *
	 *
	 *
	 * @param array $arrayarFilter = Array() Массив вида <i>array("фильтруемое поле"=&gt;"значение фильтра" [, ...])</i>.
	 * Фильтруемое поле может принимать значения: <ul> <li> <b>ID</b> -
	 * идентификатор попытки; </li> <li> <b>TEST_ID</b> - идентификатор теста; </li> <li>
	 * <b>STUDENT_ID</b> - идентификатор студента; </li> <li> <b>SCORE</b> - количество
	 * баллов; </li> <li> <b>MAX_SCORE</b> - максимальное количество баллов; </li> <li>
	 * <b>QUESTIONS</b> - количество вопросов; </li> <li> <b>STATUS</b> - статус попытки (B -
	 * тестирование началось, D - тест прерван, F - тест закончен.); </li> <li>
	 * <b>COMPLETED</b> - тест пройден (Y|N); </li> <li> <b>DATE_START</b> - дата начала попытки;
	 * </li> <li> <b>DATE_END</b> - дата окончания попытки; </li> <li> <b>USER</b> -
	 * пользователь (возможны сложные условия по полям пользователя ID,
	 * LOGIN, NAME, LAST_NAME); </li> <li> <b>MIN_PERMISSION</b> - минимальный уровень доcтупа. По
	 * умолчанию "R". Список прав доступа см. в <a
	 * href="http://dev.1c-bitrix.ruapi_help/learning/classes/ccourse/setpermission.php">CCourse::SetPermission</a>. </li> <li>
	 * <b>CHECK_PERMISSIONS</b> - проверять уровень доступа. Если установлено
	 * значение "N" - права доступа не проверяются. </li> </ul>Перед названием
	 * фильтруемого поля может указать тип фильтрации: <ul> <li>"!" - не равно
	 * </li> <li>"&lt;" - меньше </li> <li>"&lt;=" - меньше либо равно </li> <li>"&gt;" - больше
	 * </li> <li>"&gt;=" - больше либо равно </li> </ul> <br>"<i>значения фильтра</i>" -
	 * одиночное значение или массив.<br><br>Необязательный. По умолчанию
	 * записи не фильтруются.
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
	 *     $res = CTestAttempt::GetList(
	 *         Array("ID" =&gt; "ASC"), 
	 *         Array("TEST_ID" =&gt; $TEST_ID)
	 *     );
	 * 
	 *     while ($arAttempt = $res-&gt;GetNext())
	 *     {
	 *         echo "Attempt ID:".$arAttempt["ID"]."; Date start: ".$arAttempt["DATE_START"]."; Test name: ".$arAttempt["TEST_NAME"]."&lt;br&gt;";
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
	 *     $res = CTestAttempt::GetList(
	 *         Array("SCORE" =&gt; "DESC"), 
	 *         Array("CHECK_PERMISSIONS" =&gt; "N", "TEST_ID" =&gt; $TEST_ID, "STUDENT_ID" =&gt; $STUDENT_ID)
	 *     );
	 * 
	 *     while ($arAttempt = $res-&gt;GetNext())
	 *     {
	 *         echo "Attempt ID:".$arAttempt["ID"]."; Date start: ".$arAttempt["DATE_START"]."; Test name: ".$arAttempt["TEST_NAME"]."&lt;br&gt;";
	 *     }
	 * }
	 * 
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/learning/classes/ctestattempt/index.php">CTestAttempt</a>::<a
	 * href="http://dev.1c-bitrix.ruapi_help/learning/classes/ctestattempt/getbyid.php">GetByID</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/learning/fields.php#attempt">Поля попытки</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/learning/classes/ctestattempt/getlist.php
	 * @author Bitrix
	 */
	public static function GetList($arOrder=array(), $arFilter=array(), $arSelect = array())
	{
		// This function generates database-specific SQL code
		$arCallbackSqlFormer = array ('CTestAttempt', '_GetListSQLFormer');

		return (self::_GetList($arOrder, $arFilter, $arSelect, $arCallbackSqlFormer));
	}
}
