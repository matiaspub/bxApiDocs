<?php


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/learning/classes/ctestresult/index.php
 * @author Bitrix
 */
class CTestResult
{
	public static function CheckFields(&$arFields, $ID = false)
	{
		global $DB, $APPLICATION;

		if ($ID===false)
		{
			if (is_set($arFields, "ATTEMPT_ID"))
			{
				$r = CTestAttempt::GetByID($arFields["ATTEMPT_ID"]);
				if(!$r->Fetch())
				{
					$APPLICATION->ThrowException(GetMessage("LEARNING_BAD_ATTEMPT_ID_EX"), "ERROR_NO_ATTEMPT_ID");
					return false;
				}
			}
			else
			{
				$APPLICATION->ThrowException(GetMessage("LEARNING_BAD_ATTEMPT_ID"), "EMPTY_ATTEMPT_ID");
				return false;
			}

			if (is_set($arFields, "QUESTION_ID"))
			{
				$r = CLQuestion::GetByID($arFields["QUESTION_ID"]);
				if(!$r->Fetch())
				{
					$APPLICATION->ThrowException(GetMessage("LEARNING_BAD_QUESTION_ID"), "EMPTY_QUESTION_ID");
					return false;
				}
			}
			else
			{
				$APPLICATION->ThrowException(GetMessage("LEARNING_BAD_QUESTION_ID"), "EMPTY_QUESTION_ID");
				return false;
			}
		}

		if (is_set($arFields, "RESPONSE") && is_array($arFields["RESPONSE"]))
		{
			$s = "";
			foreach($arFields["RESPONSE"] as $val)
				$s .= $val.",";
			$arFields["RESPONSE"] = substr($s,0,-1);
		}

		/*
		if (is_set($arFields, "ANSWERED") && is_set($arFields, "RESPONSE"))
		{
			if ($arFields["ANSWERED"]=="Y" && strlen($arFields["RESPONSE"]) <= 0)
			{
				$APPLICATION->ThrowException(GetMessage("LEARNING_BAD_NO_ANSWERS"), "EMPTY_ANSWERS");
				return false;
			}
		}
		*/

		if (is_set($arFields, "CORRECT") && $arFields["CORRECT"] != "Y")
			$arFields["CORRECT"] = "N";

		return true;
	}


	
	/**
	* <p>Метод добавляет новый вопрос плана тестирования.</p>
	*
	*
	* @param array $arFields  Массив <b>Array("поле"=&gt;"значение", ...)</b>. Содержит значения <a
	* href="../../fields.php#test_result">всех полей</a> плана тестирования.
	* Обязательные поля должны быть заполнены. <br>
	*
	* @return int <p>Метод возвращает идентификатор добавленного вопроса плана
	* тестирования, если добавление прошло успешно. При возникновении
	* ошибки метод вернёт <i>false</i>, а в исключениях будут содержаться
	* ошибки.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $ATTEMPT_ID = 588;
	*     $QUESTION_ID = 128;
	* 
	*     $arFields = Array(
	*         "ATTEMPT_ID" =&gt; $ATTEMPT_ID,
	*         "QUESTION_ID" =&gt; $QUESTION_ID
	*     );
	* 
	*     $plan = new CTestResult;
	*     $ID = $plan-&gt;Add($arFields);
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
	* <h4>See Also</h4> 
	* <ul> <li> <a href="../ctestattempt/index.php">CTestAttempt</a>::<a
	* href="../ctestattempt/createattemptquestions.php">CreateAttemptQuestions</a> </li> <li> <a
	* href="index.php">CTestResult</a>::<a href="update.php">Update</a> </li> <li> <a href="index.php">CTestResult</a>::<a
	* href="addresponse.php">AddResponse</a> </li> <li><a href="../../fields.php#test_result">Поля плана
	* тестирования</a></li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/ctestresult/add.php
	* @author Bitrix
	*/
	public function Add($arFields)
	{
		global $DB;

		if($this->CheckFields($arFields))
		{
			unset($arFields["ID"]);

			$ID = $DB->Add("b_learn_test_result", $arFields, Array("RESPONSE"), "learning");

			return $ID;
		}

		return false;
	}


	
	/**
	* <p>Сохраняет ответ учащегося и устанавливает, дан ли на вопрос правильный ответ.</p>
	*
	*
	* @param int $TEST_RESULT_ID  Идентификатор вопроса в плане тестирования.
	*
	* @param mixed $RESPONSE  Идентификатор или массив идентификаторов <a
	* href="../../fields.php#answer">ответов на вопрос</a>.
	*
	* @return bool <p>Метод возвращает <i>true</i>, если изменение прошло успешно, при
	* возникновении ошибки метод вернёт <i>false</i>. При возникновении
	* ошибки в исключениях будет содержаться текст ошибки.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* if (CModule::IncludeModule("learning"))
	* {
	*     $TEST_TESULT_ID = 2962;
	*     $RESPONSE = 186; //< or Array(186,187); >//
	* 
	*     $res = CTestResult::AddResponse($TEST_RESULT_ID, $RESPONSE);
	*     if($res)
	*      echo "Response has been added ";
	*     else
	*      echo "Error";
	* }
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="index.php">CTestResult</a>::<a href="update.php">Update</a> </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/ctestresult/addresponse.php
	* @author Bitrix
	*/
	public static function AddResponse($TEST_RESULT_ID, $RESPONSE)
	{
		global $DB;

		$TEST_RESULT_ID = intval($TEST_RESULT_ID);
		if ($TEST_RESULT_ID < 1) return false;

		$rsTestResult = CTestResult::GetList(Array(), Array("ID" => $TEST_RESULT_ID, 'CHECK_PERMISSIONS' => 'N'));

		if ($arTestResult = $rsTestResult->GetNext())
		{
			if ($arTestResult["QUESTION_TYPE"] == "T")
			{
				$arFields = Array(
					"ANSWERED" => "Y",
					"RESPONSE" => $RESPONSE,
					"POINT"=> 0,
					"CORRECT"=> "N",
				);
			}
			else
			{
				if (!is_array($RESPONSE))
					$RESPONSE = Array($RESPONSE);

				$strSql =
				"SELECT A.ID, Q.POINT ".
				"FROM b_learn_test_result TR ".
				"INNER JOIN b_learn_question Q ON TR.QUESTION_ID = Q.ID ".
				"INNER JOIN b_learn_answer A ON Q.ID = A.QUESTION_ID ".
				"WHERE TR.ID = '".$TEST_RESULT_ID."' ".
				($arTestResult["QUESTION_TYPE"] != "R" ? "AND A.CORRECT = 'Y' " : "").
				"ORDER BY A.SORT ASC, A.ID ASC";

				if (!$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
					return false;

				$arAnswer = Array();
				while ($arRes = $res->Fetch())
				{
					$arAnswer[] = $arRes["ID"];
					$str_POINT = $arRes["POINT"];
				}

				if ($arTestResult["QUESTION_TYPE"] == "R")
				{
					if ($arAnswer != $RESPONSE)
						$str_POINT = "0";
				}
				else
				{
					$t1 = array_diff($arAnswer,$RESPONSE);
					$t2 = array_diff($RESPONSE,$arAnswer);
					if ($t1!=$t2 || $t2 != Array())
						$str_POINT = "0";
				}

				//echo "!".$str_POINT."!";

				$arFields = Array(
					"ANSWERED" => "Y",
					"RESPONSE" => $RESPONSE,
					"POINT"=> $str_POINT,
					"CORRECT"=> ($str_POINT == "0" ? "N" : "Y"),
				);
			}

			$tr = new CTestResult;
			if (!$res = $tr->Update($TEST_RESULT_ID, $arFields))
				return false;

			return $arFields;
		}
		else
		{
			return false;
		}
	}


	
	/**
	* <p>Метод изменяет параметры вопроса плана тестирования с идентификатором ID.</p>
	*
	*
	* @param int $ID  Идентификатор вопроса в плане тестирования.
	*
	* @param array $arFields  Массив Array("поле"=&gt;"значение", ...). Содержит значения <a
	* href="../../fields.php#test_result">всех полей</a> плана тестирования.
	* Обязательные поля должны быть заполнены. <br>
	*
	* @return bool <p>Метод возвращает <i>true</i>, если изменение прошло успешно, при
	* возникновении ошибки метгод вернёт <i>false</i>. При возникновении
	* ошибки в исключениях будет содержаться текст ошибки.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $TEST_TESULT_ID = 2962;
	* 
	*     $arFields = Array(
	*         "CORRECT" =&gt; "Y",
	*         "POINT" =&gt; "20"
	*     );
	* 
	*     $plan = new CTestResult;
	*     $success = $plan-&gt;Update($TEST_TESULT_ID, $arFields);
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
	* <h4>See Also</h4> 
	* <ul> <li><a href="../../fields.php#test_result">Поля плана тестирования</a></li> <li> <a
	* href="index.php">CTestResult</a>::<a href="add.php">Add</a> </li> <li> <a href="index.php">CTestResult</a>::<a
	* href="addresponse.php">AddResponse</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/ctestresult/update.php
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
			unset($arFields["QUESTION_ID"]);
			unset($arFields["ATTEMPT_ID"]);

			$arBinds=Array(
				"RESPONSE"=>$arFields["RESPONSE"]
			);

			$strUpdate = $DB->PrepareUpdate("b_learn_test_result", $arFields, "learning");
			$strSql = "UPDATE b_learn_test_result SET ".$strUpdate." WHERE ID=".$ID;
			$DB->QueryBind($strSql, $arBinds, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			return true;
		}

		return false;
	}


	
	/**
	* <p>Метод удаляет вопрос плана тестирования с идентификатором ID.</p>
	*
	*
	* @param int $ID  Идентификатор вопроса в плане тестирования.
	*
	* @return bool <p>Метод возвращает <i>true</i> в случае успешного удаления результата
	* тестирования, в противном случае возвращает <i>false</i>.</p> <a
	* name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $TEST_RESULT_ID = 2967;
	* 
	*     @set_time_limit(0);
	*     $DB-&gt;StartTransaction();
	*     if (!CTestResult::Delete($TEST_RESULT_ID))
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
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/ctestresult/delete.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID < 1) return false;

		$strSql = "DELETE FROM b_learn_test_result WHERE ID = ".$ID;

		if (!$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
			return false;

		return true;
	}


	
	/**
	* <p>Возвращает список вопросов плана тестирования по фильтру <b>arFilter</b>, отсортированный в порядке <b>arOrder</b>.</p>
	*
	*
	* @param array $arrayarOrder = Array("ID"=>"DESC") Массив для сортировки результата. Массив вида <i>array("поле
	* сортировки"=&gt;"направление сортировки" [, ...])</i>.<br>Поле для
	* сортировки может принимать значения: <ul> <li> <b>ID</b> - идентификатор
	* вопроса в плане тестирования; </li> <li> <b>ATTEMPT_ID</b> - идентификатор
	* попытки; </li> <li> <b>QUESTION_ID</b> - идентификатор вопроса; </li> <li> <b>POINT</b> -
	* количество баллов; </li> <li> <b>ANSWERED</b> - вопрос отвечен (Y|N); </li> <li>
	* <b>CORRECT</b> - вопрос правильно отвечен (Y|N); </li> <li> <b>QUESTION_NAME</b> -
	* название вопроса; </li> <li> <b>RAND</b> - случайный порядок. </li>
	* </ul>Направление сортировки может принимать значения: <ul> <li> <b>asc</b> -
	* по возрастанию; </li> <li> <b>desc</b> - по убыванию; </li> </ul>Необязательный.
	* По умолчанию сортируется по убыванию идентификатора вопроса в
	* плане тестирования.
	*
	* @param array $arrayarFilter = Array() Массив вида <i>array("фильтруемое поле"=&gt;"значение фильтра" [, ...])</i>.
	* Фильтруемое поле может принимать значения: <ul> <li> <b>ID</b> -
	* идентификатор вопроса в плане тестирования; </li> <li> <b>ATTEMPT_ID</b> -
	* идентификатор попытки; </li> <li> <b>QUESTION_ID</b> - идентификатор вопроса;
	* </li> <li> <b>POINT</b> - количество баллов; </li> <li> <b>RESPONSE</b> - ответ учащегося
	* (можно искать по шаблону [%_]); </li> <li> <b>QUESTION_NAME</b> - название вопроса
	* (можно искать по шаблону [%_]); </li> <li> <b>ANSWERED</b> - вопрос отвечен (Y|N);
	* </li> <li> <b>CORRECT</b> - вопрос правильно отвечен (Y|N). </li> </ul>Перед
	* названием фильтруемого поля может указать тип фильтрации: <ul> <li>"!"
	* - не равно </li> <li>"&lt;" - меньше </li> <li>"&lt;=" - меньше либо равно </li> <li>"&gt;"
	* - больше </li> <li>"&gt;=" - больше либо равно </li> </ul> <br>"<i>значения
	* фильтра</i>" - одиночное значение или массив.<br><br>Необязательный. По
	* умолчанию записи не фильтруются.
	*
	* @return CDBResult <p>Возвращается объект <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p> </h
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $ATTEMPT_ID = 590;
	*     $res = CTestResult::GetList(
	*         Array("ID" =&gt; "ASC"), 
	*         Array("ANSWERED" =&gt; "N", "ATTEMPT_ID" =&gt; $ATTEMPT_ID)
	*     );
	* 
	*     while ($arQuestionPlan = $res-&gt;GetNext())
	*     {
	*         echo "Question ID: ".$arQuestionPlan["QUESTION_ID"].<br>             "; Correct answer: ".$arQuestionPlan["CORRECT"].<br>             "; Question name:".$arQuestionPlan["QUESTION_NAME"]."&lt;b
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> </li> <li> <a
	* href="index.php">CTestResult</a>::<a href="getbyid.php">GetByID</a> </li> <li><a
	* href="../../fields.php#test_result">Поля плана тестирования</a></li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/ctestresult/getlist.php
	* @author Bitrix
	*/
	public static function GetList($arOrder=array(), $arFilter=array(), $arNavParams = array())
	{
		global $DB, $USER, $APPLICATION;

		if (!is_array($arFilter))
			$arFilter = Array();

		$oPermParser = new CLearnParsePermissionsFromFilter ($arFilter);
		$arSqlSearch = CTestResult::GetFilter($arFilter);

		// Remove empty strings from array
		$arSqlSearch = array_filter($arSqlSearch);

		if ($oPermParser->IsNeedCheckPerm())
			$arSqlSearch[] = " L.ID IN (" . $oPermParser->SQLForAccessibleLessons() . ") ";

		$strSqlSearch = ' ';
		if ( ! empty($arSqlSearch) )
		{
			$strSqlSearch = ' WHERE ';
			$strSqlSearch .= implode(' AND ', $arSqlSearch);
		}

		$strSqlFrom = "FROM b_learn_test_result TR 
			INNER JOIN b_learn_question Q ON TR.QUESTION_ID = Q.ID 
			INNER JOIN b_learn_lesson L ON Q.LESSON_ID = L.ID "
			. $strSqlSearch;

		$strSql = "SELECT TR.*, Q.QUESTION_TYPE, Q.NAME as QUESTION_NAME, 
			Q.POINT as QUESTION_POINT, Q.LESSON_ID "
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
				$arSqlOrder[] = " TR.ID ".$order." ";
			elseif ($by == "attempt_id")
				$arSqlOrder[] = " TR.ATTEMPT_ID ".$order." ";
			elseif ($by == "question_id")
				$arSqlOrder[] = " TR.QUESTION_ID ".$order." ";
			elseif ($by == "point")
				$arSqlOrder[] = " TR.POINT ".$order." ";
			elseif ($by == "correct")
				$arSqlOrder[] = " TR.CORRECT ".$order." ";
			elseif ($by == "answered")
				$arSqlOrder[] = " TR.ANSWERED ".$order." ";
			elseif ($by == "question_name")
				$arSqlOrder[] = " QUESTION_NAME ".$order." ";
			elseif ($by == "rand")
				$arSqlOrder[] = CTest::GetRandFunction();
			else
			{
				$arSqlOrder[] = " TR.ID ".$order." ";
				$by = "id";
			}
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

		if (is_array($arNavParams) && ( ! empty($arNavParams) ) )
		{
			if (isset($arNavParams['nTopCount']) && ((int) $arNavParams['nTopCount'] > 0))
			{
				$strSql = $DB->TopSql($strSql, (int) $arNavParams['nTopCount']);
				$res = $DB->Query($strSql, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);
			}
			else
			{
				$res_cnt = $DB->Query("SELECT COUNT(TR.ID) as C " . $strSqlFrom);
				$res_cnt = $res_cnt->fetch();
				$res = new CDBResult();
				$res->NavQuery($strSql, $res_cnt['C'], $arNavParams);
			}
		}
		else
			$res = $DB->Query($strSql, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);

		return $res;
	}


	
	/**
	* <p>Возвращает вопрос плана тестирования по идентификатору ID.</p>
	*
	*
	* @param int $ID  Идентификатор вопроса в плане тестирования.
	*
	* @return CDBResult <p>Возвращается объект <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p> </h
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $TEST_RESULT_ID = 2894;
	*     
	*     $res = CTestResult::GetByID($TEST_RESULT_ID);
	* 
	*     if ($arResult = $res-&gt;GetNext())
	*     {
	*         echo " Question name: ".$arResult["QUESTION_NAME"];
	*         echo " Answered: ".$arResult["ANSWERED"];
	*         echo " Point: ".$arResult["POINT"];
	*     }
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> </li> <li> <a
	* href="../../fields.php#test_result">Поля плана тестирования</a> </li> <li> <a
	* href="index.php">CTestResult</a>::<a href="getlist.php">GetList</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/ctestresult/getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		return CTestResult::GetList(Array(), Array("ID"=>$ID));
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
				case "ATTEMPT_ID":
				case "QUESTION_ID":
				case "POINT":
					$arSqlSearch[] = CLearnHelper::FilterCreate("TR.".$key, $val, "number", $bFullJoin, $cOperationType);
					break;

				case "RESPONSE":
					$arSqlSearch[] = CLearnHelper::FilterCreate("TR.".$key, $val, "string", $bFullJoin, $cOperationType);
					break;

				case "QUESTION_NAME":
					$arSqlSearch[] = CLearnHelper::FilterCreate("Q.NAME", $val, "string", $bFullJoin, $cOperationType);
					break;

				case "ANSWERED":
				case "CORRECT":
					$arSqlSearch[] = CLearnHelper::FilterCreate("TR.".$key, $val, "string_equal", $bFullJoin, $cOperationType);
					break;
			}
		}

		return $arSqlSearch;
	}


	public static function OnTestResultChange($TEST_RESULT_ID)
	{
		global $DB;

		$TEST_RESULT_ID = intval($TEST_RESULT_ID);

		if ($TEST_RESULT_ID < 1)
			return false;

		$strSql =
		"SELECT TR.* ".
		"FROM b_learn_test_result TR ".
		"WHERE TR.ID = '".$TEST_RESULT_ID."'";

		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if (!$arAttemptResult = $res->Fetch())
			return false;

		$strSql =
		"SELECT SUM(TR.POINT) as SUM_POINT, SUM( Q.POINT ) MAX_POINT ".
		"FROM b_learn_test_result TR ".
		"INNER JOIN b_learn_question Q ON TR.QUESTION_ID = Q.ID ".
		"WHERE TR.ATTEMPT_ID = '".$arAttemptResult["ATTEMPT_ID"]."'";

		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if (!$arSum = $res->Fetch())
			return false;

		$strSql =
		"UPDATE b_learn_attempt SET SCORE = '".$arSum["SUM_POINT"]."', MAX_SCORE ='".$arSum["MAX_POINT"]."' ".
		"WHERE ID = '".$arAttemptResult["ATTEMPT_ID"]."'";

		if (!$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
			return false;

		return CTestAttempt::OnAttemptChange($arAttemptResult["ATTEMPT_ID"]);
	}


	
	/**
	* <p>Возвращает количество отвеченных и неотвеченных вопросов плана тестирования.</p>
	*
	*
	* @param int $ATTEMPT_ID  Идентификатор попытки.
	*
	* @return array <p>Метод возвращает ассоциативный массив с ключами:</p> <ul> <li> <b>DONE</b>
	* - количество отвеченных вопросов теста.</li> <li> <b>TODO</b> - количество
	* неотвеченных вопросов теста.</li> </ul>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $ATTEMPT_ID = 588;
	* 
	*     $arStat = CTestResult::GetProgress($ATTEMPT_ID);
	* 
	*     echo $arStat["TODO"];
	*     echo $arStat["DONE"];
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="index.php">CTestResult</a>::<a href="getlist.php">GetList</a> </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/ctestresult/getprogress.php
	* @author Bitrix
	*/
	public static function GetProgress($ATTEMPT_ID)
	{
		global $DB;
		$ATTEMPT_ID = intval($ATTEMPT_ID);
		$res=array("DONE"=>0, "TODO"=>0);
		$strSql = "SELECT ANSWERED,COUNT(*) C ".
					"FROM b_learn_test_result ".
					"WHERE ATTEMPT_ID = ".$ATTEMPT_ID." ".
					"GROUP BY ANSWERED";
		$rs=$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while($ar=$rs->Fetch())
		{
			if($ar["ANSWERED"]=="Y")
				$res["DONE"]=$ar["C"];
			elseif($ar["ANSWERED"]=="N")
				$res["TODO"]=$ar["C"];
		}
		return $res;
	}


	
	/**
	* <p>Возвращает количество вопросов плана тестирования для указанной попытки.</p>
	*
	*
	* @param int $ATTEMPT_ID  Идентификатор попытки.
	*
	* @return int <p>Число - количество вопросов плана тестирования.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $ATTEMPT_ID = 588;
	* 
	*     $cnt = CTestResult::GetCount($ATTEMPT_ID);
	* 
	*     echo "Number of questions:".$cnt;
	* 
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="index.php">CTestResult</a>::<a href="getlist.php">GetList</a> </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/ctestresult/getcount.php
	* @author Bitrix
	*/
	public static function GetCount($ATTEMPT_ID)
	{
		global $DB;

		$strSql =
		"SELECT COUNT(*) as C ".
		"FROM b_learn_test_result TR ".
		"WHERE TR.ATTEMPT_ID = '".intval($ATTEMPT_ID)."'";

		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$res_cnt = $res->Fetch();

		return intval($res_cnt["C"]);

		/*$strSql =
		"SELECT COUNT(*) as CNT, SUM(Q.POINT) MAX_SCORE ".
		"FROM b_learn_test_result TR ".
		"INNER JOIN b_learn_question Q ON TR.QUESTION_ID = Q.ID ".
		"WHERE TR.ATTEMPT_ID = '".intval($ATTEMPT_ID)."'";
		*/
	}


	public static function GetPercent($ATTEMPT_ID)
	{
		global $DB;

		$strSql =
		"SELECT ROUND(SUM(CASE WHEN TR.CORRECT = 'Y' THEN Q.POINT ELSE 0 END) * 100 / SUM(Q.POINT), 4) as PCNT ".
		"FROM b_learn_test_result TR, b_learn_question Q ".
		"WHERE TR.ATTEMPT_ID = '".intval($ATTEMPT_ID)."' AND TR.QUESTION_ID = Q.ID";

		if (!$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
			return false;

		if (!$arStat = $res->Fetch())
			return false;

		// Round bottom in right way, some magic due to IEEE 754
		return ( (int) (floor($arStat["PCNT"] + 0.00001) + 0.00001) );
	}


	public static function GetCorrectCount($ATTEMPT_ID)
	{
		global $DB;

		$strSql = "SELECT SUM(CASE WHEN TR.CORRECT = 'Y' THEN 1 ELSE 0 END) AS CNT FROM b_learn_test_result TR WHERE TR.ATTEMPT_ID = ".intval($ATTEMPT_ID)." GROUP BY ATTEMPT_ID";

		if (!$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
			return 0;

		if (!$arStat = $res->Fetch())
			return 0;

		return $arStat["CNT"];
	}
}
