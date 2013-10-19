<?
/**
 * @global $USER_FIELD_MANAGER CUserTypeManager
 */
global $USER_FIELD_MANAGER;

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
abstract class CAllTestAttempt
{
	// 2012-04-13 Checked/modified for compatibility with new data model
	public static function CheckFields(&$arFields, $ID = false)
	{
		global $DB, $APPLICATION;

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

		if (is_set($arFields, "DATE_START") &&  (!$DB->IsDate($arFields["DATE_START"], false, LANG, "FULL")))
		{
			$APPLICATION->ThrowException(GetMessage("LEARNING_BAD_DATE_START"), "ERROR_DATE_START");
			return false;
		}

		if (is_set($arFields, "DATE_END") && strlen($arFields["DATE_END"])>0 && (!$DB->IsDate($arFields["DATE_END"], false, LANG, "FULL")))
		{
			$APPLICATION->ThrowException(GetMessage("LEARNING_BAD_DATE_END"), "ERROR_DATE_END");
			return false;
		}

		//Defaults
		if (is_set($arFields, "STATUS") && !in_array($arFields["STATUS"], Array("B", "D", "F", "N")))
			$arFields["STATUS"] = "B";

		if (is_set($arFields, "COMPLETED") && $arFields["COMPLETED"] != "Y")
			$arFields["COMPLETED"] = "N";

		return true;
	}


	// 2012-04-13 Checked/modified for compatibility with new data model
	
	/**
	 * <p>Метод добавляет новую попытку.</p>
	 *
	 *
	 *
	 *
	 * @param array $arFields  Массив <b>Array("поле"=&gt;"значение", ...)</b>. Содержит значения <a
	 * href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#attempt">всех полей</a> попытки.
	 * Обязательные поля должны быть заполнены. <br>
	 *
	 *
	 *
	 * @return int <p>Метод возвращает идентификатор добавленной попытки, если
	 * добавление прошло успешно. При возникновении ошибки метод вернёт
	 * <i>false</i>, а в исключениях будут содержаться ошибки.</p>
	 *
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
	 *         "TEST_ID" =&gt; $TEST_ID,
	 *         "STUDENT_ID" =&gt; $STUDENT_ID,
	 *         "STATUS" =&gt; "B"
	 *     );
	 * 
	 *     $attempt = new CTestAttempt;
	 *     $ID = $attempt-&gt;Add($arFields);
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
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/learning/classes/ctestattempt/index.php">CTestAttempt</a>::<a
	 * href="http://dev.1c-bitrix.ru/api_help/learning/classes/ctestattempt/update.php">Update</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#attempt">Поля попытки</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/learning/classes/ctestattempt/add.php
	 * @author Bitrix
	 */
	public static function Add($arFields)
	{
		global $DB, $USER_FIELD_MANAGER;

		if(CTestAttempt::CheckFields($arFields) && $USER_FIELD_MANAGER->CheckFields("LEARN_ATTEMPT", 0, $arFields))
		{
			unset($arFields["ID"]);

			//$ID = $DB->Add("b_learn_attempt", $arFields, Array(""), "learning");

			$arInsert = $DB->PrepareInsert("b_learn_attempt", $arFields, "learning");

			$ID = CTestAttempt::DoInsert($arInsert, $arFields);

			CGradeBook::RecountAttempts($arFields["STUDENT_ID"], $arFields["TEST_ID"]);

			if ($ID)
			{
				$USER_FIELD_MANAGER->Update("LEARN_ATTEMPT", $ID, $arFields);
			}

			return $ID;
		}

		return false;
	}


	// 2012-04-13 Checked/modified for compatibility with new data model
	
	/**
	 * <p>Метод изменяет параметры попытки с идентификатором ID.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Идентификатор попытки
	 *
	 *
	 *
	 * @param array $arFields  Массив Array("поле"=&gt;"значение", ...). Содержит значения <a
	 * href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#attempt">всех полей</a> попытки.
	 * Обязательные поля должны быть заполнены. <br>
	 *
	 *
	 *
	 * @return bool <p>Метод возвращает <i>true</i>, если изменение прошло успешно, при
	 * возникновении ошибки функция вернёт <i>false</i>. При возникновении
	 * ошибки в исключениях будет содержаться текст ошибки.</p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * if (CModule::IncludeModule("learning"))
	 * {
	 *     $ATTEMPT_ID = 596;
	 * 
	 *     $arFields = Array(
	 *         "STATUS" =&gt; "F",
	 *         "DATE_END" =&gt; ConvertTimeStamp(false,"FULL")
	 *     );
	 * 
	 *     $attempt = new CTestAttempt;
	 *     $ID = $attempt-&gt;Update($ATTEMPT_ID, $arFields);
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
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#attempt">Поля попытки</a> </li> <li>
	 * <a href="http://dev.1c-bitrix.ru/api_help/learning/classes/ctestattempt/index.php">CTestAttempt</a>::<a
	 * href="http://dev.1c-bitrix.ru/api_help/learning/classes/ctestattempt/add.php">Add</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/learning/classes/ctestattempt/update.php
	 * @author Bitrix
	 */
	public function Update($ID, $arFields)
	{
		global $DB, $USER_FIELD_MANAGER;

		$ID = intval($ID);
		if ($ID < 1) return false;

		if ($this->CheckFields($arFields, $ID) && $USER_FIELD_MANAGER->CheckFields("LEARN_ATTEMPT", 0, $arFields))
		{
			unset($arFields["ID"]);
			unset($arFields["TEST_ID"]);

			$arBinds=Array(
				//""=>$arFields[""]
			);

			$strUpdate = $DB->PrepareUpdate("b_learn_attempt", $arFields, "learning");
			$strSql = "UPDATE b_learn_attempt SET ".$strUpdate." WHERE ID=".$ID;
			$DB->QueryBind($strSql, $arBinds, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			$USER_FIELD_MANAGER->Update("LEARN_ATTEMPT", $ID, $arFields);

			return true;
		}

		return false;
	}


	// 2012-04-13 Checked/modified for compatibility with new data model
	
	/**
	 * <p>Метод удаляет попытку с идентификатором ID.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Идентификатор попытки.
	 *
	 *
	 *
	 * @return bool <p>Метод возвращает <i>true</i> в случае успешного удаления попытки, в
	 * противном случае возвращает <i>false</i>.</p><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * if (CModule::IncludeModule("learning"))
	 * {
	 *     $COURSE_ID = 83;
	 *     $ATTEMPT_ID = 596;
	 * 
	 *     if (CCourse::GetPermission($COURSE_ID) &gt;= 'W')
	 *     {
	 *         @set_time_limit(0);
	 *         $DB-&gt;StartTransaction();
	 *         if (!CTestAttempt::Delete($ATTEMPT_ID))
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
	 * @link http://dev.1c-bitrix.ru/api_help/learning/classes/ctestattempt/delete.php
	 * @author Bitrix
	 */
	public static function Delete($ID)
	{
		global $DB, $USER_FIELD_MANAGER;

		$ID = intval($ID);
		if ($ID < 1) return false;

		//Results
		$strSql = "DELETE FROM b_learn_test_result WHERE ATTEMPT_ID = ".$ID;
		if (!$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
			return false;

		//Attempt
		$strSql = "DELETE FROM b_learn_attempt WHERE ID = ".$ID;
		if (!$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
			return false;

		$USER_FIELD_MANAGER->Delete("LEARN_ATTEMPT", $ID);

		return true;
	}


	// 2012-04-13 Checked/modified for compatibility with new data model
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
				case "TEST_ID":
				case "STUDENT_ID":
				case "SCORE":
				case "MAX_SCORE":
				case "QUESTIONS":
					$arSqlSearch[] = CLearnHelper::FilterCreate("A.".$key, $val, "number", $bFullJoin, $cOperationType);
					break;

				case "STATUS":
				case "COMPLETED":
					$arSqlSearch[] = CLearnHelper::FilterCreate("A.".$key, $val, "string_equal", $bFullJoin, $cOperationType);
					break;

				case "DATE_START":
				case "DATE_END":
					$arSqlSearch[] = CLearnHelper::FilterCreate("A.".$key, $val, "date", $bFullJoin, $cOperationType);
					break;

				case "USER":
					$arSqlSearch[] = GetFilterQuery("U.ID, U.LOGIN, U.NAME, U.LAST_NAME",$val);
					break;
			}
		}

		return $arSqlSearch;
	}


	// 2012-04-13 Checked/modified for compatibility with new data model
	
	/**
	 * <p>Возвращает попытку по идентификатору ID. Учитываются права доступа текущего пользователя.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Идентификатор попытки.
	 *
	 *
	 *
	 * @return CDBResult <p>Возвращается объект <a
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p>
	 *
	 *
	 * <h4>See Also</h4> 
	 * <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> </li><li> <a
	 * href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#attempt">Поля попытки</a> </li>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/learning/classes/ctestattempt/getbyid.php
	 * @author Bitrix
	 */
	public static function GetByID($ID)
	{
		if ((int) $ID > 0)
			return CTestAttempt::GetList(array(), array("ID" => (int) $ID));
		else
			return (new CDBResult());
	}


	// 2012-04-13 Checked/modified for compatibility with new data model
	
	/**
	 * <p>Возвращает количество попыток студента для указанного теста.</p>
	 *
	 *
	 *
	 *
	 * @param int $TEST_ID  Идентификатор теста.
	 *
	 *
	 *
	 * @param int $STUDENT_ID  Идентификатор студента.
	 *
	 *
	 *
	 * @return int <p>Число - количество попыток.</p>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/learning/classes/ctestattempt/getcount.php
	 * @author Bitrix
	 */
	public static function GetCount($TEST_ID, $STUDENT_ID)
	{
		global $DB;

		$strSql =
		"SELECT COUNT(*) as C ".
		"FROM b_learn_attempt A ".
		"WHERE A.TEST_ID = '".intval($TEST_ID)."' AND A.STUDENT_ID = '".intval($STUDENT_ID)."'";

		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$res_cnt = $res->Fetch();

		return intval($res_cnt["C"]);
	}


	// 2012-04-13 Checked/modified for compatibility with new data model
	
	/**
	 * <p>Проверяет, пройден ли тест.</p>
	 *
	 *
	 *
	 *
	 * @param int $ATTEMPT_ID  Идентификатор попытки.
	 *
	 *
	 *
	 * @param int $PERCENT  Количество процентов, необходимых для прохождения теста.
	 *
	 *
	 *
	 * @return bool <p>Метод возвращает <i>true</i>, если тест пройден, иначе - <i>false</i>.</p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * if (CModule::IncludeModule("learning"))
	 * {
	 *     $ATTEMPT_ID = 563;
	 *     $PERCENT = 30;
	 * 
	 *     $complete = CTestAttempt::IsTestCompleted($ATTEMPT_ID, $PERCENT);
	 * 
	 *     if ($complete)
	 *         echo "Test has been completed";
	 *     else
	 *         echo "Try again";
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/learning/classes/ctestattempt/istestcompleted.php
	 * @author Bitrix
	 */
	public static function IsTestCompleted($ATTEMPT_ID, $PERCENT)
	{
		global $DB;

		$strSql =
		"SELECT * ".
		"FROM b_learn_test_result TR, b_learn_question Q ".
		"WHERE TR.QUESTION_ID = Q.ID AND TR.CORRECT = 'N' AND Q.CORRECT_REQUIRED = 'Y' AND TR.ATTEMPT_ID = '".intval($ATTEMPT_ID)."'";

		if (!$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
			return false;

		if ($arStat = $res->Fetch())
			return false;

		$strSql =
		"SELECT SUM(Q.POINT) as CNT_ALL, SUM(CASE WHEN TR.CORRECT = 'Y' THEN Q.POINT ELSE 0 END) as CNT_RIGHT ".
		"FROM b_learn_test_result TR, b_learn_question Q ".
		"WHERE TR.ATTEMPT_ID = '".intval($ATTEMPT_ID)."' AND TR.QUESTION_ID = Q.ID";

		if (!$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
			return false;

		if (!$arStat = $res->Fetch())
			return false;

		if($arStat["CNT_RIGHT"]<=0 || $arStat["CNT_ALL"] == 0)
			return false;

		// Do some magic due to IEEE 754
		$epsilon = 0.001;

		$scoreForSuccess = (float) ((int) $PERCENT);
		$userScore       = round( ($arStat["CNT_RIGHT"] / $arStat["CNT_ALL"]) * 100, 2);

		$delta = abs($userScore - $scoreForSuccess);

		$isTestComplete = false;
		if ($userScore > $scoreForSuccess)
			$isTestComplete = true;
		elseif ($delta < $epsilon)		// it means, that $userScore == $scoreForSuccess
			$isTestComplete = true;

		return ($isTestComplete);
	}


	// 2012-04-13 Checked/modified for compatibility with new data model
	public static function OnAttemptChange($ATTEMPT_ID, $bCOMPLETED = false)
	{
		global $DB;

		$ATTEMPT_ID = intval($ATTEMPT_ID);

		if ($ATTEMPT_ID < 1)
			return false;

		$strSql = "SELECT A.*, T.APPROVED, T.COMPLETED_SCORE, T.COURSE_ID ".
		"FROM b_learn_attempt A ".
		"INNER JOIN b_learn_test T ON A.TEST_ID = T.ID ".
		"WHERE A.ID = '".$ATTEMPT_ID."' AND A.STATUS = 'F' ";
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if (!$arAttempt = $res->Fetch())
			return false;


		$COMPLETED = "N";
		if (
			$arAttempt["APPROVED"] == "Y" &&
			intval($arAttempt["COMPLETED_SCORE"])>0 &&
			CTestAttempt::IsTestCompleted($ATTEMPT_ID,$arAttempt["COMPLETED_SCORE"])
		)
			$COMPLETED = "Y";

		if ($bCOMPLETED)
			$COMPLETED = "Y";

		$strSql =
		"UPDATE b_learn_attempt SET COMPLETED = '".$COMPLETED."' ".
		"WHERE ID = '".$ATTEMPT_ID."'";

		if (!$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
			return false;

		$strSql = "SELECT * FROM b_learn_gradebook WHERE STUDENT_ID='".$arAttempt["STUDENT_ID"]."' AND TEST_ID='".$arAttempt["TEST_ID"]."'";
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if (!$arGradeBook = $res->Fetch())
		{
			$arFields = Array(
					"STUDENT_ID" => $arAttempt["STUDENT_ID"],
					"TEST_ID" => $arAttempt["TEST_ID"],
					"RESULT" => $arAttempt["SCORE"],
					"MAX_RESULT" => intval($arAttempt["MAX_SCORE"]),
					"COMPLETED" => $COMPLETED,
			);

			$at = new CGradeBook;

			if (!$res = $at->Add($arFields))
				return false;

			CCertification::Certificate($arAttempt["STUDENT_ID"], $arAttempt["COURSE_ID"]);
		}
		else
		{
			$strSql =
			"SELECT A.SCORE, A.MAX_SCORE FROM b_learn_attempt A ".
			"WHERE A.STUDENT_ID = '".$arAttempt["STUDENT_ID"]."' AND A.TEST_ID = '".$arAttempt["TEST_ID"]."'  ORDER BY COMPLETED DESC, SCORE DESC";
			//AND A.COMPLETED = 'Y'
			$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if (!$arMaxScore = $res->Fetch())
				return false;

			if ($arGradeBook["COMPLETED"] == "Y")
				$COMPLETED = "Y";

			$strSql =
				"UPDATE b_learn_gradebook SET RESULT = '".intval($arMaxScore["SCORE"])."', MAX_RESULT = '".intval($arMaxScore["MAX_SCORE"])."',COMPLETED = '".$COMPLETED."' ".
				"WHERE ID = '".$arGradeBook["ID"]."'";

			if (!$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
				return false;

			CCertification::Certificate($arAttempt["STUDENT_ID"], $arAttempt["COURSE_ID"]);
		}

		return true;
	}


	// 2012-04-13 Checked/modified for compatibility with new data model
	
	/**
	 * <p>Переводит попытку в статус "Закончена" и пересчитывает набранные баллы.</p>
	 *
	 *
	 *
	 *
	 * @param int $ATTEMPT_ID  Идентификатор попытки.
	 *
	 *
	 *
	 * @return bool <p>Метод возвращает <i>true</i>, если операция выполнилась успешно,
	 * иначе <i>false</i>.</p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * if (CModule::IncludeModule("learning"))
	 * {
	 *     $ATTEMPT_ID = 23;
	 *     $oTestAttempt = new CTestAttempt;
	 *     $success = $oTestAttempt-&gt;AttemptFinished($ATTEMPT_ID);
	 *     if ($success)
	 *         echo "Attempt has been finished";
	 *     else
	 *         echo "Error!";
	 * }
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/learning/classes/ctestattempt/index.php">CTestAttempt</a>::<a
	 * href="http://dev.1c-bitrix.ru/api_help/learning/classes/ctestattempt/update.php">Update</a> </li></ul><a
	 * name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/learning/classes/ctestattempt/attemptfinished.php
	 * @author Bitrix
	 */
	public function AttemptFinished($ATTEMPT_ID)
	{
		global $DB;

		$ATTEMPT_ID = intval($ATTEMPT_ID);

		if ($ATTEMPT_ID < 1)
			return false;

		$strSql =
		"SELECT SUM(TR.POINT) as SCORE, SUM(Q.POINT) MAX_SCORE ".
		"FROM b_learn_test_result TR ".
		"INNER JOIN b_learn_question Q ON TR.QUESTION_ID = Q.ID ".
		"WHERE ATTEMPT_ID = '".$ATTEMPT_ID."' ";

		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if (!$ar = $res->Fetch())
			return false;

		$res = $this->Update($ATTEMPT_ID,
			array(
				"SCORE" => $ar["SCORE"],
				"MAX_SCORE" => $ar["MAX_SCORE"],
				"STATUS"=>"F",
				"~DATE_END"=>CDatabase::CurrentTimeFunction(),
			)
		);

		if($res)
			return CTestAttempt::OnAttemptChange($ATTEMPT_ID);
		else
			return false;
	}


	// 2012-04-13 Checked/modified for compatibility with new data model
	public static function RecountQuestions($ATTEMPT_ID)
	{
		global $DB;

		$ATTEMPT_ID = intval($ATTEMPT_ID);

		if ($ATTEMPT_ID < 1)
			return false;

		$strSql = "SELECT COUNT(*) CNT, SUM(TR.POINT) CNT_SUM, SUM(Q.POINT) MAX_POINT ".
					"FROM b_learn_test_result TR ".
					"INNER JOIN b_learn_question Q ON TR.QUESTION_ID = Q.ID ".
					"WHERE TR.ATTEMPT_ID = ".$ATTEMPT_ID;
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if (!$ar = $res->Fetch())
			return false;

		$strSql = "UPDATE b_learn_attempt SET QUESTIONS = '".intval($ar["CNT"])."', SCORE = '".intval($ar["CNT_SUM"])."', MAX_SCORE = '".intval($ar["MAX_POINT"])."' WHERE ID = ".$ATTEMPT_ID;
		if (!$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
			return false;

		return true;
	}


	// 2012-04-13 Checked/modified for compatibility with new data model
	public static function IsTestFailed($ATTEMPT_ID, $PERCENT)
	{
		global $DB;

		$strSql =
		"SELECT * ".
		"FROM b_learn_test_result TR, b_learn_question Q ".
		"WHERE TR.QUESTION_ID = Q.ID AND TR.CORRECT = 'N' AND TR.ANSWERED = 'Y' AND Q.CORRECT_REQUIRED = 'Y' AND TR.ATTEMPT_ID = '".intval($ATTEMPT_ID)."'";


		if (!$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
			return true;

		if ($arStat = $res->Fetch())
			return true;

		$strSql =
		"SELECT SUM(Q.POINT) as CNT_ALL, SUM(CASE WHEN TR.CORRECT = 'N' AND TR.ANSWERED = 'Y' THEN Q.POINT ELSE 0 END) as CNT_WRONG ".
		"FROM b_learn_test_result TR, b_learn_question Q ".
		"WHERE TR.ATTEMPT_ID = '".intval($ATTEMPT_ID)."' AND TR.QUESTION_ID = Q.ID";

		if (!$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
			return true;

		if (!$arStat = $res->Fetch())
			return true;

		if($arStat["CNT_ALL"] == 0)
		{
			return true;
		}
		elseif ($arStat["CNT_WRONG"]==0)
		{
			return false;
		}



		// Do some work due to IEEE 754
		$epsilon = 0.001;

		$cntNotFailed = $arStat["CNT_ALL"] - $arStat["CNT_WRONG"];

		$scoreForSuccess = (float) ((int) $PERCENT);
		$userScore       = round( ($cntNotFailed / $arStat["CNT_ALL"]) * 100, 2);

		$delta = abs($userScore - $scoreForSuccess);

		$isTestFailed = true;
		if ($userScore > $scoreForSuccess)
			$isTestFailed = false;
		elseif ($delta < $epsilon)		// it means, that $userScore == $scoreForSuccess
			$isTestFailed = false;

		return ($isTestFailed);
	}


	// 2012-04-13 Checked/modified for compatibility with new data model
	
	/**
	 * <p>Возвращает список попыток по фильтру <b>arFilter</b>, отсортированный в порядке <b>arOrder</b>. Учитываются права доступа текущего пользователя.</p>
	 *
	 *
	 *
	 *
	 * @param array $arrayarOrder = Array("ID"=>"DESC") 
	 *
	 *
	 *
	 * @param array $arrayarFilter = Array() 
	 *
	 *
	 *
	 * @param array $arrayarSelect = Array() 9.5.2
	 *
	 *
	 *
	 * @param array $arrayarNavParams = Array() 12.5.1
	 *
	 *
	 *
	 * @return CDBResult <p>Возвращается объект <a
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p>
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
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ru/api_help/learning/classes/ctestattempt/index.php">CTestAttempt</a>::<a
	 * href="http://dev.1c-bitrix.ru/api_help/learning/classes/ctestattempt/getbyid.php">GetByID</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#attempt">Поля попытки</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/learning/classes/ctestattempt/getlist.php
	 * @author Bitrix
	 */
	public static function GetList($arOrder=array(), $arFilter=array(), $arSelect = array(), $arNavParams = array())
	{
		global $DB, $USER, $USER_FIELD_MANAGER;

		$obUserFieldsSql = new CUserTypeSQL;
		$obUserFieldsSql->SetEntity("LEARN_ATTEMPT", "A.ID");
		$obUserFieldsSql->SetSelect($arSelect);
		$obUserFieldsSql->SetFilter($arFilter);
		$obUserFieldsSql->SetOrder($arOrder);

		$arFields = array(
			"ID" => "A.ID",
			"TEST_ID" => "A.TEST_ID",
			"OBY_DATE_END" => "A.DATE_END",
			"STUDENT_ID" => "A.STUDENT_ID",
			"DATE_START" => $DB->DateToCharFunction("A.DATE_START", "FULL"),
			"DATE_END" => $DB->DateToCharFunction("A.DATE_END", "FULL"),
			"STATUS" => "A.STATUS",
			"COMPLETED" =>  "A.COMPLETED",
			"SCORE" => "A.SCORE",
			"MAX_SCORE" => "A.MAX_SCORE",
			"QUESTIONS" => "A.QUESTIONS",
			"TEST_NAME" => "T.NAME",
			"USER_NAME" => $DB->Concat("'('",'U.LOGIN',"') '","CASE WHEN U.NAME IS NULL THEN '' ELSE U.NAME END","' '", "CASE WHEN U.LAST_NAME IS NULL THEN '' ELSE U.LAST_NAME END"),
			"USER_ID" => "U.ID",
			"MARK" => "TM.MARK",
			"MESSAGE" => "TM.DESCRIPTION",
		);

		if (count($arSelect) <= 0 || in_array("*", $arSelect))
			$arSelect = array_keys($arFields);

		$arSqlSelect = array();
		foreach($arSelect as $field)
		{
			$field = strtoupper($field);
			if(array_key_exists($field, $arFields))
				$arSqlSelect[$field] = $arFields[$field]." AS ".$field;
		}

		$sSelect = implode(",\n", $arSqlSelect);

		if (!is_array($arFilter))
			$arFilter = Array();

		$arSqlSearch = CTestAttempt::GetFilter($arFilter);

		$strSqlSearch = "";
		$arSqlSearchCnt = count($arSqlSearch);
		for($i=0; $i<$arSqlSearchCnt; $i++)
			if(strlen($arSqlSearch[$i])>0)
				$strSqlSearch .= " AND ".$arSqlSearch[$i]." ";

		$r = $obUserFieldsSql->GetFilter();
		if(strlen($r)>0)
			$strSqlSearch .= " AND (".$r.") ";

		$bCheckPerm = 'ORPHANED VAR';

		$strSql = static::_GetListSQLFormer($sSelect, $obUserFieldsSql, $bCheckPerm, $USER, $arFilter, $strSqlSearch);

		if (!is_array($arOrder))
			$arOrder = Array();

		foreach($arOrder as $by=>$order)
		{
			$by = strtolower($by);
			$order = strtolower($order);
			if ($order!="asc")
				$order = "desc";

			if ($by == "id")
				$arSqlOrder[] = " A.ID ".$order." ";
			elseif ($by == "test_id")
				$arSqlOrder[] = " A.TEST_ID ".$order." ";
			elseif ($by == "student_id")
				$arSqlOrder[] = " A.STUDENT_ID ".$order." ";
			elseif ($by == "date_start")
				$arSqlOrder[] = " A.DATE_START ".$order." ";
			elseif ($by == "date_end")
				$arSqlOrder[] = " A.DATE_END ".$order." ";
			elseif ($by == "status")
				$arSqlOrder[] = " A.STATUS ".$order." ";
			elseif ($by == "score")
				$arSqlOrder[] = " A.SCORE ".$order." ";
			elseif ($by == "max_score")
				$arSqlOrder[] = " A.MAX_SCORE ".$order." ";
			elseif ($by == "completed")
				$arSqlOrder[] = " A.COMPLETED ".$order." ";
			elseif ($by == "questions")
				$arSqlOrder[] = " A.QUESTIONS ".$order." ";
			elseif ($by == "user_name")
				$arSqlOrder[] = " USER_NAME ".$order." ";
			elseif ($by == "test_name")
				$arSqlOrder[] = " TEST_NAME ".$order." ";
			elseif ($s = $obUserFieldsSql->GetOrder($by))
				$arSqlOrder[$by] = " ".$s." ".$order." ";
			else
				$arSqlOrder[] = " A.ID ".$order." ";
		}

		$strSqlOrder = "";
		DelDuplicateSort($arSqlOrder);
		$arSqlOrderCnt = count($arSqlOrder);
		for ($i=0; $i<$arSqlOrderCnt; $i++)
		{
			if($i==0)
				$strSqlOrder = " ORDER BY ";
			else
				$strSqlOrder .= ",";

			$strSqlOrder .= $arSqlOrder[$i];
		}

		$strSql .= $strSqlOrder;

		if (isset($arNavParams['NAV_PARAMS']['nPageTop']) && ($arNavParams['NAV_PARAMS']['nPageTop'] > 0))
			$strSql = $DB->TopSql($strSql, $arNavParams['NAV_PARAMS']['nPageTop']);

		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$res->SetUserFields($USER_FIELD_MANAGER->GetUserFields("LEARN_ATTEMPT"));

		return $res;
	}


	// 2012-04-14 Checked/modified for compatibility with new data model
	protected static function _CreateAttemptQuestions($arCallbackSqlFormer, $ATTEMPT_ID)
	{
		global $APPLICATION, $DB;

		$ATTEMPT_ID = intval($ATTEMPT_ID);

		$attempt = CTestAttempt::GetByID($ATTEMPT_ID);
		if (!$arAttempt = $attempt->Fetch())
		{
			$APPLICATION->ThrowException(GetMessage("LEARNING_BAD_ATTEMPT_ID_EX"), "ERROR_NO_ATTEMPT_ID");
			return false;
		}

		$test = CTest::GetByID($arAttempt["TEST_ID"]);
		if (!$arTest = $test->Fetch())
		{
			$APPLICATION->ThrowException(GetMessage("LEARNING_BAD_TEST_ID_EX"), "ERROR_NO_TEST_ID");
			return false;
		}

		$strSql = "DELETE FROM b_learn_test_result WHERE ATTEMPT_ID = ".$ATTEMPT_ID;
		if (!$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
			return false;

		/**
		 * QUESTIONS_FROM values:
		 * 'L' - X questions from every lesson in course
		 * 'C' - X questions from every lesson from every chapter in the course
		 *       In this case questions taken from immediate lessons of all chapters (X per chapter) in the course.
		 *       In new data model it means, get X questions from every lesson in the course, except
		 *       1) immediate lessons-childs of the course and
		 *       2) lessons, contains other lessons (because, in old data model chapters doesn't contains questions)
		 * 
		 * 'H' - all questions from the selected chapter (recursive) in the course 
		 *       This case must be ignored, because converter to new data model updates 'H' to 'R', but in case
		 *       when chapter is not exists updates didn't become. So QUESTIONS_FROM stayed in 'H' value. And it means,
		 *       that there is no chapter exists with QUESTIONS_FROM_ID, so we can't do work. And we should just
		 *       ignore, for backward compatibility (so, don't throw an error).
		 * 'S' - all questions from the selected lesson (unilesson_id in QUESTIONS_FROM_ID)
		 * 'A' - all questions of the course (nothing interesting in QUESTIONS_FROM_ID)
		 * 
		 * new values:
		 * 'R' - all questions from the tree with root at selected lesson (include questions of selected lesson) 
		 *       in the course (unilesson_id in QUESTIONS_FROM_ID)
		 */

		if ($arTest["QUESTIONS_FROM"] == "C" || $arTest["QUESTIONS_FROM"] == "L")
		{
			$courseId = $arTest['COURSE_ID'] + 0;
			$courseLessonId = CCourse::CourseGetLinkedLesson ($courseId);
			if ($courseLessonId === false)
			{
				$APPLICATION->ThrowException(GetMessage("LEARNING_BAD_TEST_IS_EMPTY"), "ERROR_TEST_IS_EMPTY");
				return false;
			}

			$clauseAllChildsLessons = CLearnHelper::SQLClauseForAllSubLessons ($courseLessonId);

			if ($arTest["QUESTIONS_FROM"] == "C")	// X questions from every lessons from every chapter in the course
			{
				$strSql =
				"SELECT Q.ID as QUESTION_ID, TLEUP.SOURCE_NODE as FROM_ID
				FROM b_learn_lesson L
				INNER JOIN b_learn_question Q ON L.ID = Q.LESSON_ID
				INNER JOIN b_learn_lesson_edges TLEUP ON L.ID = TLEUP.TARGET_NODE
				LEFT OUTER JOIN b_learn_lesson_edges TLEDOWN ON L.ID = TLEDOWN.SOURCE_NODE "	
				. "WHERE L.ID IN (" . $clauseAllChildsLessons . ") \n"		// only lessons from COURSE_ID = $arTest['COURSE_ID']
				. " AND TLEDOWN.SOURCE_NODE IS NULL \n"						// exclude lessons, contains other lessons ("chapters")
					
					// include lessons in current course tree context only (and exclude immediate childs of course)
				. " AND TLEUP.SOURCE_NODE IN (" . $clauseAllChildsLessons . ") \n"

				. " AND Q.ACTIVE = 'Y' "		// active questions only
				. ($arTest["INCLUDE_SELF_TEST"] != "Y" ? "AND Q.SELF = 'N' " : "")
				. "ORDER BY ".($arTest["RANDOM_QUESTIONS"] == "Y" ? CTest::GetRandFunction() : "L.SORT, Q.SORT");
			}
			else	// 'L' X questions from every lesson in course
			{
				$strSql =
				"SELECT Q.ID as QUESTION_ID, L.ID as FROM_ID ".
				"FROM b_learn_lesson L ".
				"INNER JOIN b_learn_question Q ON L.ID = Q.LESSON_ID ".
				"WHERE L.ID IN (" . $clauseAllChildsLessons . ") AND Q.ACTIVE = 'Y' ".
				($arTest["INCLUDE_SELF_TEST"] != "Y" ? "AND Q.SELF = 'N' " : "").
				"ORDER BY ".($arTest["RANDOM_QUESTIONS"] == "Y" ? CTest::GetRandFunction() : "L.SORT, Q.SORT");
			}

			if (!$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
				return false;

			$Values = Array();
			$tmp = Array();
			while ($arRecord = $res->Fetch())
			{
				if (is_set($tmp, $arRecord["FROM_ID"]))
				{
					if ($tmp[$arRecord["FROM_ID"]] < $arTest["QUESTIONS_AMOUNT"])
						$tmp[$arRecord["FROM_ID"]]++;
					else
						continue;
				}
				else
				{
					$tmp[$arRecord["FROM_ID"]] = 1;
				}
				$Values[]= $arRecord["QUESTION_ID"];
			}

			if (empty($Values))
			{
				$APPLICATION->ThrowException(GetMessage("LEARNING_BAD_TEST_IS_EMPTY"), "ERROR_TEST_IS_EMPTY");
				return false;
			}

			$DB->StartTransaction();
			foreach ($Values as $ID)
			{
				$strSql = "INSERT INTO b_learn_test_result (ATTEMPT_ID, QUESTION_ID) VALUES (".$ATTEMPT_ID.",".$ID.")";
				if (!$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
				{
					$DB->Rollback();
					return false;
				}
			}
			$DB->Commit();
		}
		elseif (($arTest["QUESTIONS_FROM"] == "H" || $arTest["QUESTIONS_FROM"] == "S" || $arTest["QUESTIONS_FROM"] == "R") && $arTest["QUESTIONS_FROM_ID"])
		{
			$WHERE = '';
			if ($arTest["QUESTIONS_FROM"] == "H")
			{
				/**
				 * 'H' - all questions from the selected chapter (recursive) in the course 
				 *       This case must be ignored, because converter to new data model updates 'H' to 'R', but in case
				 *       when chapter is not exists updates didn't become. So QUESTIONS_FROM stayed in 'H' value. And it means,
				 *       that there is no chapter exists with QUESTIONS_FROM_ID, so we can't do work. And we should just
				 *       ignore, for backward compatibility (so, don't throw an error).
				 */
				$APPLICATION->ThrowException(GetMessage("LEARNING_BAD_TEST_IS_EMPTY"), "ERROR_TEST_IS_EMPTY");
				return false;
			}
			elseif ($arTest["QUESTIONS_FROM"] == 'R')	// all questions from the tree with root at selected lesson (include questions of selected lesson) in the course (unilesson_id in QUESTIONS_FROM_ID)
			{
				$clauseAllChildsLessons = CLearnHelper::SQLClauseForAllSubLessons ($arTest['QUESTIONS_FROM_ID']);
				$WHERE = " (L.ID IN(" . $clauseAllChildsLessons . ") OR (L.ID = " . ($arTest['QUESTIONS_FROM_ID'] + 0) . ")) ";
			}
			elseif ($arTest["QUESTIONS_FROM"] == 'S')	// 'S' - all questions from the selected lesson (unilesson_id in QUESTIONS_FROM_ID)
			{
				$clauseAllChildsLessons = $arTest["QUESTIONS_FROM_ID"] + 0;
				$WHERE = " (L.ID IN(" . $clauseAllChildsLessons . ") OR (L.ID = " . ($arTest['QUESTIONS_FROM_ID'] + 0) . ")) ";
			}
			else
			{
				return (false);
			}

			$strSql =
			"INSERT INTO b_learn_test_result (ATTEMPT_ID, QUESTION_ID) ".
			"SELECT ".$ATTEMPT_ID." ,Q.ID ".
			"FROM b_learn_lesson L ".
			"INNER JOIN b_learn_question Q ON L.ID = Q.LESSON_ID ".
			"WHERE " . $WHERE . " AND Q.ACTIVE = 'Y' ".
			($arTest["INCLUDE_SELF_TEST"] != "Y" ? "AND Q.SELF = 'N' " : "").
			"ORDER BY ".($arTest["RANDOM_QUESTIONS"] == "Y" ? CTest::GetRandFunction() : "Q.SORT ").
			($arTest["QUESTIONS_AMOUNT"] > 0 ? "LIMIT ".$arTest["QUESTIONS_AMOUNT"] :"");

			//echo $strSql;exit;

			$q = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if (!$q || intval($q->AffectedRowsCount())<=0)
			{
				$APPLICATION->ThrowException(GetMessage("LEARNING_BAD_TEST_IS_EMPTY"), "ERROR_TEST_IS_EMPTY");
				return false;
			}
		}
		elseif ($arTest["QUESTIONS_FROM"] == 'A')
		{
			$courseId = $arTest['COURSE_ID'] + 0;
			$courseLessonId = CCourse::CourseGetLinkedLesson ($courseId);
			if ($courseLessonId === false)
			{
				$APPLICATION->ThrowException(GetMessage("LEARNING_BAD_TEST_IS_EMPTY"), "ERROR_TEST_IS_EMPTY");
				return false;
			}

			$clauseAllChildsLessons = CLearnHelper::SQLClauseForAllSubLessons ($courseLessonId);

			$strSql = call_user_func($arCallbackSqlFormer, $ATTEMPT_ID, $arTest, $clauseAllChildsLessons, $courseLessonId);

			//echo $strSql; exit;

			$q = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if (!$q || intval($q->AffectedRowsCount())<=0)
			{
				$APPLICATION->ThrowException(GetMessage("LEARNING_BAD_TEST_IS_EMPTY"), "ERROR_TEST_IS_EMPTY");
				return false;
			}

		}
		else
			return (false);

		$strSql = "UPDATE b_learn_attempt SET QUESTIONS = '".CTestResult::GetCount($ATTEMPT_ID)."' WHERE ID = ".$ATTEMPT_ID;
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return true;
	}
}
