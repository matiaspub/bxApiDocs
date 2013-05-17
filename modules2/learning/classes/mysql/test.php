<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/learning/classes/general/test.php");

// 2012-04-13 Checked/modified for compatibility with new data model

/**
 * 
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/learning/classes/ctest/index.php
 * @author Bitrix
 */
class CTest extends CAllTest
{
	// 2012-04-13 Checked/modified for compatibility with new data model
	
	/**
	 * <p>Возвращает список тестов по фильтру arFilter, отсортированный в порядке arOrder. Учитываются права доступа текущего пользователя.</p>
	 *
	 *
	 *
	 *
	 * @param array $arrayarOrder = Array("TIMESTAMP_X"=>"DESC") Массив для сортировки результата. Массив вида <i>array("поле
	 * сортировки"=&gt;"направление сортировки" [, ...])</i>.<br> Поле для
	 * сортировки может принимать значения: <ul> <li> <b>ID</b> - идентификатор
	 * теста;</li> <li> <b>SORT</b> - индекс сортировки;</li> <li> <b>NAME</b> - название
	 * теста;</li> <li> <b>SORT</b> - индекс сортировки;</li> <li> <b>TIMESTAMP_X</b> - даты
	 * изменения теста;</li> </ul> Направление сортировки может принимать
	 * значения: <ul> <li> <b>asc</b> - по возрастанию;</li> <li> <b>desc</b> - по
	 * убыванию;</li> </ul> Необязательный. По умолчанию фильтруется по
	 * убыванию даты изменения теста.
	 *
	 *
	 *
	 * @param array $arrayarFilter = Array() Массив вида <i> array("фильтруемое поле"=&gt;"значение фильтра" [, ...])</i>.
	 * Фильтруемое поле может принимать значения: <ul> <li> <b>ID</b> -
	 * идентификатор теста;</li> <li> <b>SORT</b> - индекс сортировки;</li> <li>
	 * <b>COURSE_ID</b> - идентификатор курса;</li> <li> <b>ATTEMPT_LIMIT</b> - количество
	 * попыток;</li> <li> <b>TIME_LIMIT</b> - ограничение времени прохождения теста
	 * (в минутах);</li> <li> <b>NAME</b> - название теста (можно искать по шаблону
	 * [%_]);</li> <li> <b>DESCRIPTION</b> - описание теста (можно искать по шаблону
	 * [%_]);</li> <li> <b>ACTIVE</b> - фильтр по активности (Y|N);</li> <li> <b>APPROVED</b> -
	 * автоматическая проверка результатов (Y|N);</li> <li> <b>INCLUDE_SELF_TEST</b> -
	 * включать вопросы для самопроверки (Y|N);</li> <li> <b>RANDOM_QUESTIONS</b> -
	 * случайный порядок вопросов (Y|N);</li> <li> <b>RANDOM_ANSWERS</b> - случайный
	 * порядок ответов (Y|N);</li> <li> <b>QUESTIONS_FROM</b> - в тесте участвуют вопросы
	 * (A - со всего курса, C - с каждой главы, L - с каждого урока);</li> <li>
	 * <b>PASSAGE_TYPE</b> - Тип прохождения теста. 0 - запретить переход к
	 * следующему вопросу без ответа на текущий вопрос, пользователь не
	 * может изменять свои ответы; 1 - разрешить переход к следующему
	 * вопросу без ответа на текущий вопрос, пользователь не может
	 * изменять свои ответы; 3 - разрешить переход к следующему вопросу
	 * без ответа на текущий вопрос, пользователь может изменять свои
	 * ответы.</li> <li> <b>MIN_PERMISSION</b> - минимальный уровень доcтупа. По
	 * умолчанию "R". Список прав доступа см. в <a
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
	 *     $COURSE_ID = 97;
	 *     $res = CTest::GetList(
	 *         Array("SORT"=&gt;"ASC"), 
	 *         Array("ACTIVE" =&gt; "Y", "COURSE_ID" =&gt; $COURSE_ID)
	 *     );
	 * 
	 *     while ($arTest = $res-&gt;GetNext())
	 *     {
	 *         echo "Test name: ".$arTest["NAME"]."&lt;br&gt;";
	 *     }
	 * }
	 * 
	 * ?&gt;
	 * 
	 * &lt;?
	 * 
	 * if (CModule::IncludeModule("learning"))
	 * {
	 *     $res = CTest::GetList(
	 *         Array("SORT"=&gt;"ASC"), 
	 *         Array("?NAME" =&gt; "Site")
	 *     );
	 * 
	 *     while ($arTest = $res-&gt;GetNext())
	 *     {
	 *         echo "Test name: ".$arTest["NAME"]."&lt;br&gt;";
	 *     }
	 * }
	 * ?&gt;
	 * 
	 * &lt;?
	 * 
	 * if (CModule::IncludeModule("learning"))
	 * {
	 *     $COURSE_ID = 97;
	 * 
	 *     $res = CTest::GetList(
	 *         Array("NAME" =&gt; "ASC", "SORT"=&gt;"ASC"), 
	 *         Array("COURSE_ID" =&gt; $COURSE_ID, "APPROVED" =&gt; "Y")
	 *     );
	 * 
	 *     while ($arTest = $res-&gt;GetNext())
	 *     {
	 *         echo "Test name: ".$arTest["NAME"]."&lt;br&gt;";
	 *     }
	 * }
	 * 
	 * ?&gt;
	 * 
	 * &lt;?
	 * 
	 * if (CModule::IncludeModule("learning"))
	 * {
	 *     $COURSE_ID = 97;
	 * 
	 *     $res = CTest::GetList(
	 *         Array("TIMESTAMP_X" =&gt; "ASC", "SORT"=&gt;"ASC"), 
	 *         Array("CHECK_PERMISSIONS" =&gt; "N", "COURSE_ID" =&gt; $COURSE_ID)
	 *     );
	 * 
	 *     while ($arTest = $res-&gt;GetNext())
	 *     {
	 *         echo "Test name: ".$arTest["NAME"]."&lt;br&gt;";
	 *     }
	 * }
	 * 
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li><a href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a></li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/learning/classes/ctest/index.php">CTest</a>::<a
	 * href="http://dev.1c-bitrix.ruapi_help/learning/classes/ctest/getbyid.php">GetByID</a> </li> <li><a
	 * href="http://dev.1c-bitrix.ruapi_help/learning/fields.php#test">Поля теста</a></li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/learning/classes/ctest/getlist.php
	 * @author Bitrix
	 */
	public static function GetList($arOrder=Array(), $arFilter=Array())
	{
		global $DB, $USER;

		if (!is_array($arFilter))
			$arFilter = Array();

		$oPermParser = new CLearnParsePermissionsFromFilter ($arFilter);
		$arSqlSearch = CTest::GetFilter($arFilter);

		$strSqlSearch = "";
		for($i=0; $i<count($arSqlSearch); $i++)
			if(strlen($arSqlSearch[$i])>0)
				$strSqlSearch .= " AND ".$arSqlSearch[$i]." ";

		$strSql =
			"SELECT DISTINCT T.*, ".
			$DB->DateToCharFunction("T.TIMESTAMP_X")." as TIMESTAMP_X ".
			"FROM b_learn_test T ".
			"INNER JOIN b_learn_course C ON T.COURSE_ID = C.ID ".
			"WHERE 1=1 ";

		if ($oPermParser->IsNeedCheckPerm())
			$strSql .= " AND C.LINKED_LESSON_ID IN (" . $oPermParser->SQLForAccessibleLessons() . ") ";

		$strSql .= $strSqlSearch;

		/* was:
		$bCheckPerm = ($APPLICATION->GetUserRight("learning") < "W" && !$USER->IsAdmin() && $arFilter["CHECK_PERMISSIONS"] != "N");

		$userID = $USER->GetID() ? $USER->GetID() : 0;
		$strSql =
			"SELECT DISTINCT T.*, ".
			$DB->DateToCharFunction("T.TIMESTAMP_X")." as TIMESTAMP_X ".
			"FROM b_learn_test T ".
			"INNER JOIN b_learn_course C ON T.COURSE_ID = C.ID ".
			($bCheckPerm ?
			"LEFT JOIN b_learn_course_permission CP ON CP.COURSE_ID = C.ID "
			: "").
			"WHERE 1=1 ".
			($bCheckPerm ?
			"AND CP.USER_GROUP_ID IN (".$USER->GetGroups().") ".
			"AND CP.PERMISSION >= '".(strlen($arFilter["MIN_PERMISSION"])==1 ? $arFilter["MIN_PERMISSION"] : "R")."' ".
			"AND (CP.PERMISSION='X' OR C.ACTIVE='Y') "
			:"").
			$strSqlSearch;
		*/

		if (!is_array($arOrder))
			$arOrder = Array();

		foreach($arOrder as $by=>$order)
		{
			$by = strtolower($by);
			$order = strtolower($order);
			if ($order!="asc")
				$order = "desc";

			if ($by == "id")						$arSqlOrder[] = " T.ID ".$order." ";
			elseif ($by == "name")			$arSqlOrder[] = " T.NAME ".$order." ";
			elseif ($by == "active")			$arSqlOrder[] = " T.ACTIVE ".$order." ";
			elseif ($by == "sort")				$arSqlOrder[] = " T.SORT ".$order." ";
			else
			{
				$arSqlOrder[] = " T.TIMESTAMP_X ".$order." ";
				$by = "timestamp_x";
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

	// 2012-04-13 Checked/modified for compatibility with new data model
	public static function GetRandFunction()
	{
		return " RAND(".rand(0, 1000000).") ";
	}

}




?>