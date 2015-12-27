<?


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/learning/classes/ctest/index.php
 * @author Bitrix
 */
class CAllTest
{
	public static function CheckFields(&$arFields, $ID = false)
	{
		global $DB;
		$arMsg = array();

		if ( (is_set($arFields, "NAME") || $ID === false) && strlen($arFields["NAME"]) <= 0)
		{
			$arMsg[] = array("id"=>"NAME", "text"=> GetMessage("LEARNING_BAD_NAME"));
		}

		if ($ID===false && !is_set($arFields, "COURSE_ID"))
			$arMsg[] = array("id"=>"COURSE_ID", "text"=> GetMessage("LEARNING_BAD_COURSE_ID"));

		if (is_set($arFields, "COURSE_ID"))
		{
			$r = CCourse::GetByID($arFields["COURSE_ID"]);
			if(!$r->Fetch())
				$arMsg[] = array("id"=>"COURSE_ID", "text"=> GetMessage("LEARNING_BAD_COURSE_ID_EX"));
		}

		if ( $arFields["APPROVED"] == "Y" &&
			is_set($arFields, "COMPLETED_SCORE") &&
			(intval($arFields["COMPLETED_SCORE"]) <= 0 || intval($arFields["COMPLETED_SCORE"]) > 100)
		)
			$arMsg[] = array("id"=>"COMPLETED_SCORE", "text"=> GetMessage("LEARNING_BAD_COMPLETED_SCORE"));

		if (is_set($arFields, "PREVIOUS_TEST_ID") && intval($arFields["PREVIOUS_TEST_ID"]) != 0)
		{
			$r = CTest::GetByID($arFields["PREVIOUS_TEST_ID"]);
			if(!$r->Fetch())
				$arMsg[] = array("id"=>"PREVIOUS_TEST_ID", "text"=> GetMessage("LEARNING_BAD_PREVIOUS_TEST"));
		}

		if ( is_set($arFields, "PREVIOUS_TEST_SCORE") &&
			(intval($arFields["PREVIOUS_TEST_SCORE"]) <= 0 || intval($arFields["PREVIOUS_TEST_SCORE"]) > 100) &&
			intval($arFields["PREVIOUS_TEST_ID"]) != 0
		)
			$arMsg[] = array("id"=>"PREVIOUS_TEST_SCORE", "text"=> GetMessage("LEARNING_BAD_COMPLETED_SCORE"));

		if(!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}

		//Defaults
		if (is_set($arFields, "QUESTIONS_FROM") && !in_array($arFields["QUESTIONS_FROM"], array("A", "C", "L", "H", "S", 'R')))
			$arFields["QUESTIONS_FROM"] = "A";

		if (is_set($arFields, "QUESTIONS_AMOUNT") && intval($arFields["QUESTIONS_AMOUNT"]) <= 0)
			$arFields["QUESTIONS_AMOUNT"] = "0";

		if (is_set($arFields, "QUESTIONS_FROM_ID") && intval($arFields["QUESTIONS_FROM_ID"]) <= 0)
			$arFields["QUESTIONS_FROM_ID"] = "0";

		if (is_set($arFields, "ACTIVE") && $arFields["ACTIVE"] != "Y")
			$arFields["ACTIVE"] = "N";

		if (is_set($arFields, "APPROVED") && $arFields["APPROVED"] != "Y")
			$arFields["APPROVED"] = "N";

		if($arFields["APPROVED"] == "N")
			$arFields["COMPLETED_SCORE"] = "";

		if (is_set($arFields, "INCLUDE_SELF_TEST") && $arFields["INCLUDE_SELF_TEST"] != "Y")
			$arFields["INCLUDE_SELF_TEST"] = "N";

		if (is_set($arFields, "RANDOM_QUESTIONS") && $arFields["RANDOM_QUESTIONS"] != "Y")
			$arFields["RANDOM_QUESTIONS"] = "N";

		if (is_set($arFields, "RANDOM_ANSWERS") && $arFields["RANDOM_ANSWERS"] != "Y")
			$arFields["RANDOM_ANSWERS"] = "N";

		if (is_set($arFields, "DESCRIPTION_TYPE") && $arFields["DESCRIPTION_TYPE"] != "html")
			$arFields["DESCRIPTION_TYPE"] = "text";

		if (is_set($arFields, "PASSAGE_TYPE") && !in_array($arFields["PASSAGE_TYPE"], Array("0", "1", "2")))
			$arFields["PASSAGE_TYPE"] = "0";

		if (is_set($arFields, "INCORRECT_CONTROL") && $arFields["INCORRECT_CONTROL"] != "Y")
			$arFields["INCORRECT_CONTROL"] = "N";

		if (is_set($arFields, "SHOW_ERRORS") && $arFields["SHOW_ERRORS"] != "Y")
		{
			$arFields["SHOW_ERRORS"] = "N";
			$arFields["NEXT_QUESTION_ON_ERROR"] = "Y";
		}

		if (is_set($arFields, "NEXT_QUESTION_ON_ERROR") && $arFields["NEXT_QUESTION_ON_ERROR"] != "Y")
			$arFields["NEXT_QUESTION_ON_ERROR"] = "N";

		return true;
	}


	
	/**
	* <p>Метод добавляет новый тест.</p>
	*
	*
	* @param array $arFields  Массив <b>Array("поле"=&gt;"значение", ...)</b>. Содержит значения <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#test">всех полей</a> теста.
	* Обязательные поля должны быть заполнены. <br>
	*
	* @return int <p>Метод возвращает идентификатор добавленного теста, если
	* добавление прошло успешно. При возникновении ошибки метод вернёт
	* <i>false</i>, а в исключениях будут содержаться ошибки.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	* 
	*     $COURSE_ID = 97;
	* 
	*     $arFields = Array(
	*         "COURSE_ID" =&gt; $COURSE_ID,
	*         "NAME" =&gt; "New test!",
	*         "INCLUDE_SELF_TEST" =&gt; "Y"
	*     );
	* 
	*     $test = new CTest;
	*     $ID = $test-&gt;Add($arFields);
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
	* 
	* }
	* 
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/learning/classes/ctest/index.php">CTest</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ctest/update.php">Update</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#test">Поля теста</a> </li> </ul> <a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/ctest/add.php
	* @author Bitrix
	*/
	public function Add($arFields)
	{
		global $DB;

		if($this->CheckFields($arFields))
		{
			unset($arFields["ID"]);

			CLearnHelper::FireEvent('OnBeforeTestAdd', $arFields);

			$ID = $DB->Add("b_learn_test", $arFields, Array("DESCRIPTION"), "learning");

			$arFields['ID'] = $ID;
			CLearnHelper::FireEvent('OnAfterTestAdd', $arFields);

			return $ID;
		}

		return false;
	}


	
	/**
	* <p>Метод изменяет параметры теста с идентификатором ID.</p>
	*
	*
	* @param int $ID  Идентификатор теста.
	*
	* @param array $arFields  Массив Array("поле"=&gt;"значение", ...). Содержит значения <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#test">всех полей</a> теста.
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
	* 
	*     $TEST_ID = 99;
	* 
	*     $arFields = Array(
	*         "NAME" =&gt; "New name",
	*         "INCLUDE_SELF_TEST" =&gt; "N",
	*         "QUESTIONS_AMOUNT" =&gt; 5
	*     );
	* 
	*     $test = new CTest;
	*     $success = $test-&gt;Update($TEST_ID, $arFields);
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
	* 
	* }
	* 
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#test">Поля теста</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ctest/index.php">CTest</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ctest/add.php">Add</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/ctest/update.php
	* @author Bitrix
	*/
	public function Update($ID, $arFields)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID < 1) return false;

		if ($this->CheckFields($arFields, $ID))
		{
			unset($arFields["ID"]);

			$arBinds = array(
				"DESCRIPTION" => $arFields["DESCRIPTION"]
			);

			foreach(GetModuleEvents('learning', 'OnBeforeTestUpdate', true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($arFields, $ID));

			$strUpdate = $DB->PrepareUpdate("b_learn_test", $arFields, "learning");
			$strSql = "UPDATE b_learn_test SET ".$strUpdate." WHERE ID=".$ID;
			$DB->QueryBind($strSql, $arBinds, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			foreach(GetModuleEvents('learning', 'OnAfterTestUpdate', true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($arFields, $ID));

			return true;
		}

		return false;
	}


	
	/**
	* <p>Метод удаляет тест с идентификатором ID.</p>
	*
	*
	* @param int $ID  Идентификатор теста.
	*
	* @return bool <p>Метод возвращает <i>true</i> в случае успешного удаления теста, в
	* противном случае возвращает <i>false</i>.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	* 
	*     $TEST_ID = 99;
	*     $COURSE_ID = 97;
	* 
	*     if (CCourse::GetPermission($COURSE_ID) &gt;= 'W')
	*     {
	*         @set_time_limit(0);
	*         $DB-&gt;StartTransaction();
	*         if (!CTest::Delete($TEST_ID))
	*         {
	*             echo "Error!";
	*             $DB-&gt;Rollback();
	*         }
	*         else
	*             $DB-&gt;Commit();
	*     }
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/ctest/delete.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID < 1) return false;

		CLearnHelper::FireEvent('OnBeforeTestDelete', $ID);

		//Gradebook
		$records = CGradeBook::GetList(Array(), Array("TEST_ID" => $ID));
		while($arRecord = $records->Fetch())
		{
			if(!CGradeBook::Delete($arRecord["ID"]))
				return false;
		}

		//Attempts
		$attempts = CTestAttempt::GetList(Array(), Array("TEST_ID" => $ID));
		while($arAttempt = $attempts->Fetch())
		{
			if(!CTestAttempt::Delete($arAttempt["ID"]))
				return false;
		}

		//Marks
		$marks = CLTestMark::GetList(Array(), Array("TEST_ID" => $ID));
		while($arMark = $marks->Fetch())
		{
			if(!CLTestMark::Delete($arMark["ID"]))
				return false;
		}

		$strSql = "DELETE FROM b_learn_test WHERE ID = ".$ID;

		if (!$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
			return false;

		CEventLog::add(array(
			'AUDIT_TYPE_ID' => 'LEARNING_REMOVE_ITEM',
			'MODULE_ID'     => 'learning',
			'ITEM_ID'       => 'T #' . $ID,
			'DESCRIPTION'   => 'test removed'
		));

		CLearnHelper::FireEvent('OnAfterTestDelete', $ID);

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
				case "SORT":
				case "COURSE_ID":
				case "ATTEMPT_LIMIT":
				case "TIME_LIMIT":
					$arSqlSearch[] = CLearnHelper::FilterCreate("LT.".$key, $val, "number", $bFullJoin, $cOperationType);
					break;

				case "NAME":
				case "DESCRIPTION":
					$arSqlSearch[] = CLearnHelper::FilterCreate("LT.".$key, $val, "string", $bFullJoin, $cOperationType);
					break;

				case "ACTIVE":
				case "APPROVED":
				case "INCLUDE_SELF_TEST":
				case "RANDOM_ANSWERS":
				case "RANDOM_QUESTIONS":
				case "QUESTIONS_FROM":
				case "QUESTIONS_FROM_ID":
				case "PASSAGE_TYPE":
					$arSqlSearch[] = CLearnHelper::FilterCreate("LT.".$key, $val, "string_equal", $bFullJoin, $cOperationType);
					break;
			}

		}

		return $arSqlSearch;
	}


	
	/**
	* <p>Возвращает тест по идентификатору ID. Учитываются права доступа текущего пользователя.</p>
	*
	*
	* @param int $ID  Идентификатор теста.
	*
	* @return CDBResult <p>Возвращается объект <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p> </h
	*
	* <h4>See Also</h4> 
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> </li><li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#test">Поля теста</a> </li>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/ctest/getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		return CTest::GetList($arOrder=Array(), $arFilter=Array("ID" => $ID));
	}


	
	/**
	* <p>Возвращает количество тестов по заданному фильтру.</p>
	*
	*
	* @param array $arrayarFilter = Array() Массив вида <i> array("фильтруемое поле"=&gt;"значение фильтра" [, ...])</i>.
	* Описание фильтра см. в <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ctest/getlist.php">CTest::GetList</a>.<br> По
	* умолчанию тесты не фильтруются.
	*
	* @return int <p>Число - количество тестов.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $COURSE_ID = 97;
	*     
	*     $cnt = CTest::GetCount(Array("ACTIVE" =&gt; "Y", "COURSE_ID" =&gt; $COURSE_ID));
	* 
	*     echo "Number of tests: ".$cnt;
	* }
	* 
	* ?&gt;
	* 
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $COURSE_ID = 97;
	*     
	*     $cnt = CTest::GetCount(Array("CHECK_PERMISSIONS" =&gt; "N", "COURSE_ID" =&gt; $COURSE_ID));
	* 
	*     echo "Number of tests: ".$cnt;
	* }
	* 
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/learning/classes/ctest/index.php">CTest</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ctest/getlist.php">GetList</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/ctest/getcount.php
	* @author Bitrix
	*/
	public static function GetCount($arFilter = Array())
	{
		global $DB, $USER, $APPLICATION;

		if (!is_array($arFilter))
			$arFilter = Array();

		$oPermParser = new CLearnParsePermissionsFromFilter ($arFilter);

		$arSqlSearch = array_filter(CTest::GetFilter($arFilter));

		$strSqlSearch = "";

		if ( ! empty($arSqlSearch) )
			$strSqlSearch .= ' AND ' . implode(' AND ', $arSqlSearch) . ' ';

		$strSql = 
			"SELECT COUNT(*) as CNT 
			FROM b_learn_test LT 
			INNER JOIN b_learn_course C 
				ON LT.COURSE_ID = C.ID
			WHERE 1=1";

		if ($oPermParser->IsNeedCheckPerm())
			$strSql .= " AND C.LINKED_LESSON_ID IN (" . $oPermParser->SQLForAccessibleLessons() . ") ";

		$strSql .= $strSqlSearch;

		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($ar = $res->Fetch())
			return intval($ar["CNT"]);
		else
			return 0;
	}


	public static function isPrevPassed($ID, $SCORE)
	{
		global $DB, $USER;
		$ID = intval($ID);
		$SCORE = intval($SCORE);
		$strSql = "SELECT * FROM b_learn_gradebook WHERE STUDENT_ID = ".$USER->GetID()." AND TEST_ID = ".$ID." AND 1.0*RESULT/MAX_RESULT*100 >= ".$SCORE;
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res->Fetch())
			return true;
		else
			return false;
	}


	public static function GetStats($ID)
	{
		global $DB;

		$ID = intval($ID);
		$strSql = "SELECT COUNT(*) AS ALL_CNT, SUM(CASE WHEN COMPLETED = 'Y' THEN 1 ELSE 0 END) AS CORRECT_CNT FROM b_learn_attempt WHERE STATUS = 'F' AND TEST_ID = ".$ID;
		$rsStat = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arStat = $rsStat->GetNext())
		{
			return array("ALL_CNT" => intval($arStat["ALL_CNT"]), "CORRECT_CNT" => intval($arStat["CORRECT_CNT"]));
		}
		else
		{
			return array("ALL_CNT" => 0, "CORRECT_CNT" => 0);
		}
	}


	
	/**
	* <p>Возвращает список тестов по фильтру arFilter, отсортированный в порядке arOrder. Учитываются права доступа текущего пользователя.</p>
	*
	*
	* @param array $arrayarOrder = Array("TIMESTAMP_X"=>"DESC") Массив для сортировки результата. Массив вида <i>array("поле
	* сортировки"=&gt;"направление сортировки" [, ...])</i>.<br> Поле для
	* сортировки может принимать значения: <ul> <li> <b>ID</b> - идентификатор
	* теста;</li> <li> <b>SORT</b> - индекс сортировки;</li> <li> <b>NAME</b> - название
	* теста;</li> <li> <b>SORT</b> - индекс сортировки;</li> <li> <b>TIMESTAMP_X</b> - даты
	* изменения теста;</li> </ul> Направление сортировки может принимать
	* значения: <ul> <li> <b>asc</b> - по возрастанию;</li> <li> <b>desc</b> - по
	* убыванию;</li> </ul> Необязательный. По умолчанию сортируется по
	* убыванию даты изменения теста.
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
	* (A - со всего курса, C - с каждой главы, L - с каждого урока, S - все
	* вопросы с урока);</li> <li> <b>QUESTIONS_FROM_ID</b> - в тесте участвуют вопросы из
	* конкретного урока;</li> <li> <b>PASSAGE_TYPE</b> - Тип прохождения теста. 0 -
	* запретить переход к следующему вопросу без ответа на текущий
	* вопрос, пользователь не может изменять свои ответы; 1 - разрешить
	* переход к следующему вопросу без ответа на текущий вопрос,
	* пользователь не может изменять свои ответы; 3 - разрешить переход
	* к следующему вопросу без ответа на текущий вопрос, пользователь
	* может изменять свои ответы.</li> <li> <b>MIN_PERMISSION</b> - минимальный
	* уровень доcтупа. По умолчанию "R". Список прав доступа см. в <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/setpermission.php">CCourse::SetPermission</a>.</li> <li>
	* <b>CHECK_PERMISSIONS</b> - проверять уровень доступа. Если установлено
	* значение "N" - права доступа не проверяются.</li> </ul> Перед названием
	* фильтруемого поля может указать тип фильтрации: <ul> <li>"!" - не
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
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a></li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ctest/index.php">CTest</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ctest/getbyid.php">GetByID</a> </li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#test">Поля теста</a></li> </ul> <a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/ctest/getlist.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = array(), $arFilter = array(), $arNavParams = array())
	{
		global $DB, $USER;

		if (!is_array($arFilter))
			$arFilter = Array();

		$oPermParser = new CLearnParsePermissionsFromFilter ($arFilter);
		$arSqlSearch = CTest::GetFilter($arFilter);

		// Remove empty strings from array
		$arSqlSearch = array_filter($arSqlSearch);

		if ($oPermParser->IsNeedCheckPerm())
			$arSqlSearch[] = " C.LINKED_LESSON_ID IN (" . $oPermParser->SQLForAccessibleLessons() . ") ";

		$strSqlSearch = ' ';
		if ( ! empty($arSqlSearch) )
		{
			$strSqlSearch = ' WHERE ';
			$strSqlSearch .= implode(' AND ', $arSqlSearch);
		}

		$strSqlFrom = "FROM b_learn_test LT ".
			"INNER JOIN b_learn_course C ON LT.COURSE_ID = C.ID "
			. $strSqlSearch;

		$strSql =
			"SELECT LT.ID, LT.COURSE_ID, LT.SORT, LT.ACTIVE, LT.NAME, 
				LT.DESCRIPTION, LT.DESCRIPTION_TYPE, LT.ATTEMPT_LIMIT, 
				LT.TIME_LIMIT, LT.COMPLETED_SCORE, LT.QUESTIONS_FROM, 
				LT.QUESTIONS_FROM_ID, LT.QUESTIONS_AMOUNT, LT.RANDOM_QUESTIONS, 
				LT.RANDOM_ANSWERS, LT.APPROVED, LT.INCLUDE_SELF_TEST, 
				LT.PASSAGE_TYPE, LT.PREVIOUS_TEST_ID, LT.PREVIOUS_TEST_SCORE, 
				LT.INCORRECT_CONTROL, LT.CURRENT_INDICATION, 
				LT.FINAL_INDICATION, LT.MIN_TIME_BETWEEN_ATTEMPTS, 
				LT.SHOW_ERRORS, LT.NEXT_QUESTION_ON_ERROR, ".
			$DB->DateToCharFunction("LT.TIMESTAMP_X")." as TIMESTAMP_X "
			. $strSqlFrom;

		if (!is_array($arOrder))
			$arOrder = Array();

		foreach($arOrder as $by=>$order)
		{
			$by = strtolower($by);
			$order = strtolower($order);

			if ($order!="asc")
				$order = "desc";

			if ($by == "id")
				$arSqlOrder[] = " LT.ID ".$order." ";
			elseif ($by == "name")
				$arSqlOrder[] = " LT.NAME ".$order." ";
			elseif ($by == "active")
				$arSqlOrder[] = " LT.ACTIVE ".$order." ";
			elseif ($by == "sort")
				$arSqlOrder[] = " LT.SORT ".$order." ";
			else
			{
				$arSqlOrder[] = " LT.TIMESTAMP_X ".$order." ";
				$by = "timestamp_x";
			}
		}

		$strSqlOrder = "";
		DelDuplicateSort($arSqlOrder);

		if ( ! empty($arSqlOrder) )
			$strSqlOrder .= ' ORDER BY ' . implode(', ', $arSqlOrder) . ' ';

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
				$res_cnt = $DB->Query("SELECT COUNT(LT.ID) as C " . $strSqlFrom);
				$res_cnt = $res_cnt->fetch();
				$res = new CDBResult();
				$res->NavQuery($strSql, $res_cnt['C'], $arNavParams);
			}
		}
		else
			$res = $DB->Query($strSql, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);

		return $res;
	}
}
