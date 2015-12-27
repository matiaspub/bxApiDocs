<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2014 Bitrix
 */

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/learning/classes/general/certification.php");


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/learning/classes/ccertification/index.php
 * @author Bitrix
 */
class CCertification extends CAllCertification
{
	
	/**
	* <p>Возвращает список сертификатов по фильтру <b>arFilter</b>, отсортированный в порядке <b>arOrder</b>. Учитываются права доступа текущего пользователя.</p>
	*
	*
	* @param array $arrayarOrder = Array("ID"=>"DESC") Массив для сортировки результата. Массив вида <i>array("поле
	* сортировки"=&gt;"направление сортировки" [, ...])</i>. <br> Поле для
	* сортировки может принимать значения: <ul> <li> <b>ID</b> - идентификатор
	* сертификата; </li> <li> <b>STUDENT_ID</b> - идентификатор студента ; </li> <li>
	* <b>COURSE_ID</b> - идентификатор курса; </li> <li> <b>SUMMARY</b> - cумма баллов,
	* набранных за прохождение всех тестов курса; </li> <li> <b>MAX_SUMMARY</b> -
	* максимально возможная сумма баллов за прохождение всех тестов
	* курса; </li> <li> <b>SORT</b> - индекс сортировки; </li> <li> <b>ACTIVE</b> - фильтр по
	* активности (Y|N); </li> <li> <b>FROM_ONLINE</b> - сертификат получен через
	* online-обучение (Y/N); </li> <li> <b>PUBLIC_PROFILE</b> - публиковать сертификат в
	* профиле (Y/N); </li> <li> <b>DATE_CREATE</b> - дата создания сертификата; </li> <li>
	* <b>TIMESTAMP_X</b> - дата изменения студента. </li> </ul> Направление
	* сортировки может принимать значения: <ul> <li> <b>asc</b> - по возрастанию;
	* </li> <li> <b>desc</b> - по убыванию; </li> </ul> Необязательный. По умолчанию
	* фильтруется по убыванию идентификатора сертификата.
	*
	* @param array $arrayarFilter = Array() Массив вида <i>array("фильтруемое поле"=&gt;"значение фильтра" [, ...])</i>.
	* Фильтруемое поле может принимать значения: <ul> <li> <b>ID</b> -
	* идентификатор сертификата; </li> <li> <b>STUDENT_ID</b> - идентификатор
	* студента ; </li> <li> <b>COURSE_ID</b> - идентификатор курса; </li> <li> <b>SUMMARY</b> -
	* cумма баллов, набранных за прохождение всех тестов курса; </li> <li>
	* <b>MAX_SUMMARY</b> - максимально возможная сумма баллов за прохождение
	* всех тестов курса; </li> <li> <b>SORT</b> - индекс сортировки; </li> <li> <b>ACTIVE</b> -
	* фильтр по активности (Y|N); </li> <li> <b>FROM_ONLINE</b> - сертификат получен
	* через online-обучение (Y/N); </li> <li> <b>PUBLIC_PROFILE</b> - публиковать сертификат
	* в профиле (Y/N); </li> <li> <b>DATE_CREATE</b> - дата создания сертификата; </li> <li>
	* <b>TIMESTAMP_X</b> - дата изменения студента. </li> <li> <b>USER</b> - пользователь
	* (возможны сложные условия по полям пользователя ID, LOGIN, NAME, LAST_NAME);
	* </li> <li> <b>MIN_PERMISSION</b> - минимальный уровень доступа. По умолчанию "R".
	* Список прав доступа см. в <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/setpermission.php">CCourse::SetPermission</a>. </li>
	* <li> <b>CHECK_PERMISSIONS</b> - проверять уровень доступа. Если установлено
	* значение "N" - права доступа не проверяются. </li> </ul> Перед названием
	* фильтруемого поля можно указать тип фильтрации: <ul> <li>"!" - не равно
	* </li> <li>"&lt;" - меньше </li> <li>"&lt;=" - меньше либо равно </li> <li>"&gt;" - больше
	* </li> <li>"&gt;=" - больше либо равно </li> </ul> <br> "<i>значения фильтра</i>" -
	* одиночное значение или массив. <br><br> Необязательный. По умолчанию
	* записи не фильтруются.
	*
	* @return CDBResult <p>Возвращается объект <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p> </h
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>if (CModule::IncludeModule("learning"))<br>{<br>    $COURSE_ID = 100;<br>    $res = CCertification::GetList(<br>        Array("SUMMARY" =&gt; "DESC", "SORT"=&gt;"ASC"), <br>        Array("ACTIVE" =&gt; "Y", "COURSE_ID" =&gt; $COURSE_ID)<br>    );<br><br>    while ($arCertification = $res-&gt;GetNext())<br>    {<br>        echo "User:".$arCertification["USER_NAME"].<br>             "; Course name: ".$arCertification["COURSE_NAME"]."&lt;br&gt;";<br>    }<br>}<br>?&gt;&lt;?<br>if (CModule::IncludeModule("learning"))<br>{<br>    $COURSE_ID = 100;<br>    $res = CCertification::GetList(<br>        Array("SUMMARY" =&gt; "DESC", "SORT"=&gt;"ASC"), <br>        Array("ACTIVE" =&gt; "Y", "CHECK_PERMISSIONS" =&gt; "N")<br>    );<br><br>    while ($arCertification = $res-&gt;GetNext())<br>    {<br>        echo "User:".$arCertification["USER_NAME"].<br>             "; Course name: ".$arCertification["COURSE_NAME"]."&lt;br&gt;";<br>    }<br>}<br>?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a></li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccertification/index.php">CCertification</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccertification/getbyid.php">GetByID</a> </li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php">Поля сертификата</a></li> </ul> </ht<a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/ccertification/getlist.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = array(), $arFilter = array(), $arNavParams = array())
	{
		global $DB;

		$oPermParser = new CLearnParsePermissionsFromFilter ($arFilter);
		$arSqlSearch = CCertification::GetFilter($arFilter);

		$strSqlSearch = "";
		if ( ! empty($arSqlSearch) )
		{
			$arSqlSearch = array_filter($arSqlSearch);

			if ( ! empty($arSqlSearch) )
				$strSqlSearch .= ' AND ' . implode(' AND ', $arSqlSearch);
		}

		$strSql =
		"SELECT CER.*, C.NAME as COURSE_NAME, COURSEOLD.ID as COURSE_ID, "
		. "COURSEOLD.ACTIVE_FROM as ACTIVE_FROM, COURSEOLD.ACTIVE_TO as ACTIVE_TO, COURSEOLD.RATING as RATING, "
		. "COURSEOLD.RATING_TYPE as RATING_TYPE, COURSEOLD.SCORM as SCORM, "
		. $DB->Concat("'('",'U.LOGIN',"') '","CASE WHEN U.NAME IS NULL THEN '' ELSE U.NAME END","' '", "CASE WHEN U.LAST_NAME IS NULL THEN '' ELSE U.LAST_NAME END")." as USER_NAME, U.ID as USER_ID, ".
		$DB->DateToCharFunction("CER.TIMESTAMP_X")." as TIMESTAMP_X, ".
		$DB->DateToCharFunction("CER.DATE_CREATE")." as DATE_CREATE ";

		$strSqlFrom = "FROM b_learn_certification CER ".
			"INNER JOIN b_learn_course COURSEOLD ON CER.COURSE_ID = COURSEOLD.ID ".
			"INNER JOIN b_learn_lesson C ON C.ID = COURSEOLD.LINKED_LESSON_ID ".
			"INNER JOIN b_user U ON U.ID = CER.STUDENT_ID ".
			"WHERE 1=1 ";

		if ($oPermParser->IsNeedCheckPerm())
			$strSqlFrom .= " AND C.ID IN (" . $oPermParser->SQLForAccessibleLessons() . ") ";

		$strSqlFrom .= $strSqlSearch;

		if (!is_array($arOrder))
			$arOrder = Array();

		foreach($arOrder as $by=>$order)
		{
			$by = strtolower($by);
			$order = strtolower($order);
			if ($order!="asc")
				$order = "desc";

			if ($by == "id")
				$arSqlOrder[] = " CER.ID ".$order." ";
			elseif ($by == "student_id")
				$arSqlOrder[] = " CER.STUDENT_ID ".$order." ";
			elseif ($by == "course_id")
				$arSqlOrder[] = " CER.COURSE_ID ".$order." ";
			elseif ($by == "summary")
				$arSqlOrder[] = " CER.SUMMARY ".$order." ";
			elseif ($by == "sort")
				$arSqlOrder[] = " CER.SORT ".$order." ";
			elseif ($by == "active")
				$arSqlOrder[] = " CER.ACTIVE ".$order." ";
			elseif ($by == "from_online")
				$arSqlOrder[] = " CER.FROM_ONLINE ".$order." ";
			elseif ($by == "public")
				$arSqlOrder[] = " CER.PUBLIC ".$order." ";
			elseif ($by == "public_profile")
				$arSqlOrder[] = " CER.PUBLIC ".$order." ";
			elseif ($by == "date_create")
				$arSqlOrder[] = " CER.DATE_CREATE ".$order." ";
			elseif ($by == "summary")
				$arSqlOrder[] = " CER.SUMMARY ".$order." ";
			elseif ($by == "max_summary")
				$arSqlOrder[] = " CER.MAX_SUMMARY ".$order." ";
			elseif ($by == "timestamp_x")
				$arSqlOrder[] = " CER.TIMESTAMP_X ".$order." ";
			else
				$arSqlOrder[] = " CER.ID ".$order." ";
		}

		$strSqlOrder = "";
		DelDuplicateSort($arSqlOrder);
		if ( ! empty($arSqlOrder) )
			$strSqlOrder .= " ORDER BY " . implode(', ', $arSqlOrder);

		$strSql .= $strSqlFrom . $strSqlOrder;

		if (is_array($arNavParams) && ( ! empty($arNavParams) ) )
		{
			if (isset($arNavParams['nTopCount']) && ((int) $arNavParams['nTopCount'] > 0))
			{
				$strSql = $DB->TopSql($strSql, (int) $arNavParams['nTopCount']);
				$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
			else
			{
				$res_cnt = $DB->Query("SELECT COUNT(CER.ID) as CNT " . $strSqlFrom, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				$res_cnt = $res_cnt->fetch();
				$res = new CDBResult();
				$rc = $res->NavQuery($strSql, $res_cnt['CNT'], $arNavParams, true);
				if ($rc === false)
					throw new LearnException ('EA_SQLERROR', LearnException::EXC_ERR_ALL_GIVEUP);
			}
		}
		else
			$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return ($res);
	}
}
