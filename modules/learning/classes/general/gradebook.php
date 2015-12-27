<?php


/**
 * <br><br>
 *
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre>
 * // пример пересчета журнала
 * $gradebook = new CGradeBook; 
 * $gradebook-&gt;RecountAttempts($STUDENT_ID,$TEST_ID);
 * </pre>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/learning/classes/cgradebook/index.php
 * @author Bitrix
 */
class CAllGradeBook
{
	public static function LessonIdByGradeBookId ($certId)
	{
		$rc = CGradeBook::GetByID($certId);
		if ($rc === false)
			throw new LearnException('', LearnException::EXC_ERR_ALL_GIVEUP);

		$row = $rc->Fetch();

		if ( ! isset($row['LINKED_LESSON_ID']) )
			throw new LearnException('', LearnException::EXC_ERR_ALL_GIVEUP);

		return ( (int) $row['LINKED_LESSON_ID'] );
	}


	public static function CheckFields(&$arFields, $ID = false)
	{
		global $DB, $APPLICATION;

		if ($ID===false && !is_set($arFields, "STUDENT_ID"))
		{
			$APPLICATION->ThrowException(GetMessage("LEARNING_BAD_USER_ID"), "EMPTY_STUDENT_ID");
			return false;
		}
		elseif (is_set($arFields, "STUDENT_ID"))
		{
			$dbResult = CUser::GetByID($arFields["STUDENT_ID"]);
			if (!$dbResult->Fetch())
			{
				$APPLICATION->ThrowException(GetMessage("LEARNING_BAD_USER_ID_EX"), "ERROR_NO_STUDENT_ID");
				return false;
			}
		}

		if ($ID===false && !is_set($arFields, "TEST_ID"))
		{
			$APPLICATION->ThrowException(GetMessage("LEARNING_BAD_TEST_ID"), "EMPTY_TEST_ID");
			return false;
		}
		elseif (is_set($arFields, "TEST_ID"))
		{
			$r = CTest::GetByID($arFields["TEST_ID"]);
			if(!$r->Fetch())
			{
				$APPLICATION->ThrowException(GetMessage("LEARNING_BAD_TEST_ID_EX"), "ERROR_NO_TEST_ID");
				return false;
			}
		}

		if (is_set($arFields, "STUDENT_ID") && is_set($arFields, "TEST_ID"))
		{
			$res = CGradeBook::GetList(Array(), Array("STUDENT_ID" => $arFields["STUDENT_ID"], "TEST_ID" => $arFields["TEST_ID"]));
			if ($res->Fetch())
			{
				$APPLICATION->ThrowException(GetMessage("LEARNING_BAD_GRADEBOOK_DUPLICATE"), "ERROR_GRADEBOOK_DUPLICATE");
				return false;
			}
		}


		if (is_set($arFields, "COMPLETED") && $arFields["COMPLETED"] != "Y")
			$arFields["COMPLETED"] = "N";

		return true;
	}


	
	/**
	* <p>Метод добавляет новую запись в журнал.</p>
	*
	*
	* @param array $arFields  Массив <b>Array("поле"=&gt;"значение", ...)</b>. Содержит значения <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#gradebook">всех полей</a> журнала.
	* Обязательные поля должны быть заполнены. <br>
	*
	* @return int <p>Метод возвращает идентификатор добавленной записи в журнал,
	* если добавление прошло успешно. При возникновении ошибки метод
	* вернёт <i>false</i>, а в исключениях будут содержаться ошибки.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $TEST_ID = 32;
	*     $STUDENT_ID = 3;
	* 
	*     $arFields = Array(
	*         
	*         "TEST_ID" =&gt; $TEST_ID,
	*         "STUDENT_ID" =&gt; $STUDENT_ID,
	*         "RESULT" =&gt; 300,
	*         "MAX_RESULT" =&gt; 300
	*     );
	* 
	*     $gradebook = new CGradeBook;
	*     $ID = $gradebook-&gt;Add($arFields);
	*     $success = ($ID&gt;0);
	* 
	*     if($success)
	*     {
	*         echo "Ok!";
	*     }
	*     else
	*     {
	*         if($e = $APPLICATION-&gt;GetException())
	*             echo "Error: ".$e-&gt;GetString();
	*     }
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/learning/classes/cgradebook/index.php">CGradeBook</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/cgradebook/update.php">Update</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#gradebook">Поля журнала</a> </li> </ul> <a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/cgradebook/add.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		global $DB;

		if(CGradeBook::CheckFields($arFields))
		{
			unset($arFields["ID"]);

			$ID = $DB->Add("b_learn_gradebook", $arFields, Array(), "learning");

			return $ID;
		}

		return false;
	}


	
	/**
	* <p>Метод изменяет параметры записи в журнале с идентификатором ID.</p>
	*
	*
	* @param int $ID  Идентификатор записи в журнале.
	*
	* @param array $arFields  Массив Array("поле"=&gt;"значение", ...). Содержит значения <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#gradebook">всех полей</a> журнала.
	* Обязательные поля должны быть заполнены. <br>
	*
	* @return bool <p>Метод возвращает <i>true</i>, если изменение прошло успешно, при
	* возникновении ошибки метод вернет <i>false</i>. При возникновении
	* ошибки в исключениях будет содержаться текст ошибки.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $RECORD_ID = 96;
	* 
	*     $arFields = Array(
	*         "RESULT" =&gt; 250,
	*         "MAX_RESULT" =&gt; 300
	*     );
	* 
	*     $gradebook = new CGradeBook;
	*     $success = $gradebook-&gt;Update($RECORD_ID, $arFields);
	* 
	*     if($success)
	*     {
	*         echo "Ok!";
	*     }
	*     else
	*     {
	*         if($e = $APPLICATION-&gt;GetException())
	*             echo "Error: ".$e-&gt;GetString();
	*     }
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#gradebook">Поля журнала</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/learning/classes/cgradebook/index.php">CGradeBook</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/cgradebook/add.php">Add</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/cgradebook/update.php
	* @author Bitrix
	*/
	public static function Update($ID, $arFields)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID < 1) return false;

		if (CGradeBook::CheckFields($arFields, $ID))
		{
			unset($arFields["ID"]);
			unset($arFields["STUDENT_ID"]);
			unset($arFields["TEST_ID"]);

			$arBinds=Array();

			$strUpdate = $DB->PrepareUpdate("b_learn_gradebook", $arFields, "learning");
			$strSql = "UPDATE b_learn_gradebook SET ".$strUpdate." WHERE ID=".$ID;
			$DB->QueryBind($strSql, $arBinds, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			return true;
		}

		return false;
	}


	
	/**
	* <p>Метод удаляет запись в журнале с идентификатором ID.</p>
	*
	*
	* @param int $ID  Идентификатор записи. </htm
	*
	* @return bool <p>Метод возвращает <i>true</i> в случае успешного удаления записи, в
	* противном случае возвращает <i>false</i>.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $RECORD_ID = 96;
	* 
	*     @set_time_limit(0);
	*     $DB-&gt;StartTransaction();
	*     if (!CGradeBook::Delete($RECORD_ID))
	*     {
	*         echo "Error!";
	*         $DB-&gt;Rollback();
	*     }
	*     else
	*         $DB-&gt;Commit();
	* 
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/cgradebook/delete.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID < 1) return false;

		$strSql = "SELECT TEST_ID, STUDENT_ID FROM b_learn_gradebook WHERE ID = ".$ID;
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if (!$arGBook = $res->Fetch())
			return false;

		$attempts = CTestAttempt::GetList(Array(), Array("TEST_ID" => $arGBook["TEST_ID"], "STUDENT_ID" => $arGBook["STUDENT_ID"]));
		while($arAttempt = $attempts->Fetch())
		{
			if(!CTestAttempt::Delete($arAttempt["ID"]))
				return false;
		}

		$strSql = "DELETE FROM b_learn_gradebook WHERE ID = ".$ID;

		if (!$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
			return false;

		return true;
	}


	public static function GetFilter($arFilter)
	{
		if (!is_array($arFilter))
			$arFilter = Array();

		$arSqlSearch = Array();

		foreach ($arFilter as $key => $val)
		{
			$res = CLearnHelper::MkOperationFilter($key);
			$key = $res["FIELD"];
			$cOperationType = $res["OPERATION"];

			$key = strtoupper($key);

			switch ($key)
			{
				case "ID":
				case "STUDENT_ID":
				case "TEST_ID":
				case "RESULT":
				case "MAX_RESULT":
					$arSqlSearch[] = CLearnHelper::FilterCreate("G.".$key, $val, "number", $bFullJoin, $cOperationType);
					break;
				case "COMPLETED":
					$arSqlSearch[] = CLearnHelper::FilterCreate("G.".$key, $val, "string_equal", $bFullJoin, $cOperationType);
					break;
				case "USER":
					$arSqlSearch[] = GetFilterQuery("U.ID, U.LOGIN, U.NAME, U.LAST_NAME",$val);
					break;
			}

		}

		return $arSqlSearch;
	}


	
	/**
	* <p>Возвращает запись журнала по идентификатору ID. Учитываются права доступа текущего пользователя.</p>
	*
	*
	* @param int $ID  Идентификатор записи в журнале.
	*
	* @return CDBResult <p>Возвращается объект <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p> </h
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $RECORD_ID = 95;
	*     
	*     $res = CGradeBook::GetByID($RECORD_ID);
	* 
	*     if ($arGradeBook = $res-&gt;GetNext())
	*     {
	*         echo "Test: ".$arGradeBook["TEST_NAME"]." User: ".$arGradeBook["USER_NAME"]." Score: ".$arGradeBook["RESULT"];
	*     }
	* }
	* 
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#gradebook">Поля журнала</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/cgradebook/index.php">CGradeBook</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/cgradebook/getlist.php">GetList</a> </li> </ul> <a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/cgradebook/getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		return CGradeBook::GetList(Array(), Array("ID"=>$ID));
	}


	public static function RecountAttempts($STUDENT_ID,$TEST_ID)
	{
		global $DB;

		$STUDENT_ID = intval($STUDENT_ID);
		$TEST_ID = intval($TEST_ID);

		if ($TEST_ID < 1 || $STUDENT_ID < 1)
			return false;

		$strSql = "SELECT ID FROM b_learn_gradebook G WHERE STUDENT_ID = '".$STUDENT_ID."' AND TEST_ID = '".$TEST_ID."' ";
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if (!$arG = $res->Fetch())
		{

			$ID = CGradeBook::Add(Array(
					"STUDENT_ID" => $STUDENT_ID,
					"TEST_ID" => $TEST_ID,
					"RESULT" => "0",
					"MAX_RESULT" => "0",
					"COMPLETED" => "N"
				));

			return ($ID > 0);
		}

		$strSql = "SELECT SCORE, MAX_SCORE, COMPLETED ".
					"FROM b_learn_attempt ".
					"WHERE STUDENT_ID = '".$STUDENT_ID."' AND TEST_ID = '".$TEST_ID."' ".
					"ORDER BY COMPLETED DESC, SCORE DESC ";

		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$res->NavStart();

		if (intval($res->SelectedRowsCount()) == 0)
		{
			$strSql = "DELETE FROM b_learn_gradebook WHERE ID = ".$arG["ID"];

			if (!$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
				return false;

			return true;
		}

		if (!$ar = $res->Fetch())
			return false;

		$strSql = "UPDATE b_learn_gradebook SET ATTEMPTS = '".intval($res->SelectedRowsCount())."', COMPLETED = '".$ar["COMPLETED"]."', RESULT = '".intval($ar["SCORE"])."' , MAX_RESULT = '".intval($ar["MAX_SCORE"])."' WHERE STUDENT_ID = '".$STUDENT_ID."' AND TEST_ID = '".$TEST_ID."' ";
		if (!$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
			return false;

		return true;
	}


	public static function GetExtraAttempts($STUDENT_ID, $TEST_ID)
	{
		global $DB;

		$STUDENT_ID = intval($STUDENT_ID);
		$TEST_ID = intval($TEST_ID);

		$strSql = "SELECT EXTRA_ATTEMPTS FROM b_learn_gradebook WHERE STUDENT_ID = ".$STUDENT_ID." AND TEST_ID = ".$TEST_ID."";
		$rs = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if (!$ar = $rs->Fetch())
		{
			return 0;
		}
		else
		{
			return $ar["EXTRA_ATTEMPTS"];
		}
	}


	public static function AddExtraAttempts($STUDENT_ID, $TEST_ID, $COUNT = 1)
	{
		global $DB;

		$STUDENT_ID = intval($STUDENT_ID);
		$TEST_ID = intval($TEST_ID);
		$COUNT = intval($COUNT);

		$strSql = "SELECT ID, EXTRA_ATTEMPTS FROM b_learn_gradebook WHERE STUDENT_ID = ".$STUDENT_ID." AND TEST_ID = ".$TEST_ID."";
		$rs = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ( ! ($ar = $rs->Fetch()) )
		{
			$ID = CGradeBook::Add(Array(
					"STUDENT_ID" => $STUDENT_ID,
					"TEST_ID" => $TEST_ID,
					"RESULT" => "0",
					"MAX_RESULT" => "0",
					"COMPLETED" => "N",
					"EXTRA_ATTEMPTS" => $COUNT
			));

			return ($ID > 0);
		}
		else
		{
			$strSql = "UPDATE b_learn_gradebook SET EXTRA_ATTEMPTS = ".($ar["EXTRA_ATTEMPTS"] + $COUNT)." WHERE ID = ".$ar["ID"];
			if (!$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
				return false;
		}
	}


	
	/**
	* <p>Возвращает список записей журнала по фильтру arFilter, отсортированный в порядке arOrder. Учитываются права доступа текущего пользователя.</p>
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
	* @param array $arrayarFilter = Array() Массив вида <i> array("фильтруемое поле"=&gt;"значение фильтра" [, ...])</i>.
	* Фильтруемое поле может принимать значения: <ul> <li> <b>ID</b> -
	* идентификатор записи;</li> <li> <b>TEST_ID</b> - идентификатор теста;</li> <li>
	* <b>STUDENT_ID</b> - идентификатор студента;</li> <li> <b>RESULT</b> - количество
	* баллов;</li> <li> <b>MAX_RESULT</b> - максимальное количество баллов;</li> <li>
	* <b>COMPLETED</b> - тест пройден (Y|N);</li> <li> <b>USER</b> - пользователь (возможны
	* сложные условия по полям пользователя ID, LOGIN, NAME, LAST_NAME);</li> <li>
	* <b>MIN_PERMISSION</b> - минимальный уровень доcтупа. По умолчанию "R". Список
	* прав доступа см. в <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/setpermission.php">CCourse::SetPermission</a>.</li> <li>
	* <b>CHECK_PERMISSIONS</b> - проверять уровень доступа. Если установлено
	* значение "N" - права доступа не проверяются.</li> </ul> Перед названием
	* фильтруемого поля можно указать тип фильтрации: <ul> <li>"!" - не
	* равно</li> <li>"&lt;" - меньше</li> <li>"&lt;=" - меньше либо равно</li> <li>"&gt;" -
	* больше</li> <li>"&gt;=" - больше либо равно</li> </ul> <br> "<i>значения
	* фильтра</i>" - одиночное значение или массив.<br><br> Необязательный.
	* По умолчанию записи не фильтруются.
	*
	* @return CDBResult <p>Возвращается объект <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p> </h
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
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/cgradebook/index.php">CGradeBook</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/cgradebook/getbyid.php">GetByID</a> </li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#gradebook">Поля журнала</a></li> </ul> <a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/cgradebook/getlist.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = array(), $arFilter = array(), $arNavParams = array())
	{
		global $DB;

		$oPermParser = new CLearnParsePermissionsFromFilter ($arFilter);
		$arSqlSearch = array_filter(CGradeBook::GetFilter($arFilter));

		$strSqlSearch = '';
		if ( ! empty($arSqlSearch) )
			$strSqlSearch .= implode(' AND ', $arSqlSearch);

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

		$strSqlFrom = static::__getSqlFromClause($SqlSearchLang);

		if ($oPermParser->IsNeedCheckPerm())
			$strSqlFrom .= " AND TUL.ID IN (" . $oPermParser->SQLForAccessibleLessons() . ") ";

		if ($strSqlSearch !== '')
			$strSqlFrom .= ' AND ' . $strSqlSearch;

		$strSql =
		"SELECT G.*, T.NAME as TEST_NAME, T.COURSE_ID as COURSE_ID,
		T.APPROVED as TEST_APPROVED,
		(T.ATTEMPT_LIMIT + G.EXTRA_ATTEMPTS) AS ATTEMPT_LIMIT, TUL.NAME as COURSE_NAME,
		C.LINKED_LESSON_ID AS LINKED_LESSON_ID, ".
		$DB->Concat("'('",'U.LOGIN',"') '","CASE WHEN U.NAME IS NULL THEN '' ELSE U.NAME END","' '", "CASE WHEN U.LAST_NAME IS NULL THEN '' ELSE U.LAST_NAME END")." as USER_NAME, U.ID as USER_ID ".
		$strSqlFrom;

		if (!is_array($arOrder))
			$arOrder = array();

		foreach($arOrder as $by=>$order)
		{
			$by = strtolower($by);
			$order = strtolower($order);
			if ($order!="asc")
				$order = "desc";

			if ($by == "id")
				$arSqlOrder[] = " G.ID ".$order." ";
			elseif ($by == "student_id")
				$arSqlOrder[] = " G.STUDENT_ID ".$order." ";
			elseif ($by == "test_id")
				$arSqlOrder[] = " G.TEST_ID ".$order." ";
			elseif ($by == "completed")
				$arSqlOrder[] = " G.COMPLETED ".$order." ";
			elseif ($by == "result")
				$arSqlOrder[] = " G.RESULT ".$order." ";
			elseif ($by == "max_result")
				$arSqlOrder[] = " G.MAX_RESULT ".$order." ";
			elseif ($by == "user_name")
				$arSqlOrder[] = " USER_NAME ".$order." ";
			elseif ($by == "test_name")
				$arSqlOrder[] = " TEST_NAME ".$order." ";
			else
				$arSqlOrder[] = " G.ID ".$order." ";
		}

		$strSqlOrder = "";
		DelDuplicateSort($arSqlOrder);
		for ($i=0, $len = count($arSqlOrder); $i < $len; $i++)
		{
			if($i==0)
				$strSqlOrder = " ORDER BY ";
			else
				$strSqlOrder .= ",";

			$strSqlOrder .= $arSqlOrder[$i];
		}

		$strSql .= $strSqlOrder;

		if (is_array($arNavParams) && ( ! empty($arNavParams) ) )
		{
			if (isset($arNavParams['nTopCount']) && ((int) $arNavParams['nTopCount'] > 0))
			{
				$strSql = $DB->TopSql($strSql, (int) $arNavParams['nTopCount']);
				$res = $DB->Query($strSql, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);
			}
			else
			{
				$res_cnt = $DB->Query("SELECT COUNT(G.ID) as C " . $strSqlFrom);
				$res_cnt = $res_cnt->fetch();
				$res = new CDBResult();
				$res->NavQuery($strSql, $res_cnt['C'], $arNavParams);
			}
		}
		else
			$res = $DB->Query($strSql, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}


	/**
	 * This function is for internal use only.
	 * It can be changed without any notification.
	 * This is for MSSQL/Oracle. MySQL version of SQL-code redefined in ../mysql/gradebook.php
	 *
	 * @access private
	 */
	protected static function __getSqlFromClause($SqlSearchLang)
	{
		$strSqlFrom =
			"FROM b_learn_gradebook G ".
			"INNER JOIN b_learn_test T ON G.TEST_ID = T.ID ".
			"INNER JOIN b_user U ON U.ID = G.STUDENT_ID ".
			"LEFT JOIN b_learn_course C ON C.ID = T.COURSE_ID ".
			"LEFT JOIN b_learn_lesson TUL ON TUL.ID = C.LINKED_LESSON_ID ".
			"LEFT JOIN b_learn_test_mark TM ON G.TEST_ID = TM.TEST_ID ".
			"WHERE (TM.SCORE IS NULL
				OR TM.SCORE =
					(
					SELECT MIN(SCORE) AS SCORE
					FROM b_learn_test_mark
					WHERE
						TEST_ID = G.TEST_ID
							AND
						SCORE >=
							CASE WHEN G.MAX_RESULT > 0
							THEN
								(G.RESULT/G.MAX_RESULT*100)
							ELSE
								0
							END
					)
				) ".
			(strlen($SqlSearchLang)<=2?"":
				"AND
					EXISTS
					(	SELECT 'x' FROM b_learn_course_site CS
						WHERE C.ID = CS.COURSE_ID AND CS.SITE_ID IN (".$SqlSearchLang.") ) "
			);

		return ($strSqlFrom);
	}
}
