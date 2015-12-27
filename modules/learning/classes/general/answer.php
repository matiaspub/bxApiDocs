<?


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/learning/classes/clanswer/index.php
 * @author Bitrix
 */
class CLAnswer
{
	public static function CheckFields(&$arFields, $ID = false)
	{
		global $DB;
		$arMsg = Array();

		if ( (is_set($arFields, "ANSWER") || $ID === false) && strlen(trim($arFields["ANSWER"])) <= 0)
			$arMsg[] = array("id"=>"NAME", "text"=> GetMessage("LEARNING_BAD_NAME"));


		if (
			($ID === false && !is_set($arFields, "QUESTION_ID"))
			||
			(is_set($arFields, "QUESTION_ID") && intval($arFields["QUESTION_ID"]) < 1)
			)
		{
			$arMsg[] = array("id"=>"QUESTION_ID", "text"=> GetMessage("LEARNING_BAD_QUESTION_ID"));
		}
		elseif (is_set($arFields, "QUESTION_ID"))
		{
			$res = CLQuestion::GetByID($arFields["QUESTION_ID"]);
			if(!$arRes = $res->Fetch())
				$arMsg[] = array("id"=>"QUESTION_ID", "text"=> GetMessage("LEARNING_BAD_QUESTION_ID"));
		}

		if(!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}

		if (is_set($arFields, "CORRECT") && $arFields["CORRECT"] != "Y")
			$arFields["CORRECT"] = "N";

		return true;
	}


	
	/**
	* <p>Метод добавляет новый ответ на вопрос.</p>
	*
	*
	* @param array $arFields  Массив Array("поле"=&gt;"значение", ...). Содержит значения <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#answer">всех полей</a> ответа.
	* Обязательные поля должны быть заполнены. <br>
	*
	* @return int <p>Метод возвращает идентификатор добавленного ответа, если
	* добавление прошло успешно. При возникновении ошибки метод вернет
	* <i>false</i>, а в исключениях будут содержаться ошибки.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $QUESTION_ID = 289;
	* 
	*     $arFields = Array(
	*         "ANSWER" =&gt; "Another answer",
	*         "QUESTION_ID" =&gt; $QUESTION_ID,
	*     );
	* 
	*     $answer = new CLAnswer;
	*     $ID = $answer-&gt;Add($arFields);
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
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/learning/classes/clanswer/index.php">CLAnswer</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/clanswer/update.php">Update</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#answer">Поля ответа</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/clanswer/add.php
	* @author Bitrix
	*/
	public function Add($arFields)
	{
		global $DB;

		if($this->CheckFields($arFields))
		{
			unset($arFields["ID"]);

			$ID = $DB->Add("b_learn_answer", $arFields, Array("ANSWER", "FEEDBACK", "MATCH_ANSWER"), "learning");

			return $ID;
		}

		return false;
	}


	
	/**
	* <p>Метод изменяет параметры ответа с идентификатором ID.</p>
	*
	*
	* @param int $ID  Идентификатор ответа. </htm
	*
	* @param array $arFields  Массив Array("поле"=&gt;"значение", ...). Содержит значения <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#answer">всех полей</a> ответа.
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
	*     $ANSWER_ID = 1553;
	* 
	*     $arFields = Array(
	*         "ANSWER" =&gt; "New answer name",
	*         "SORT" =&gt; "1",
	*     );
	* 
	*     $answer = new CLAnswer;
	*     $success = $answer-&gt;Update($ANSWER_ID, $arFields);
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
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#answer">Поля ответа</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/clanswer/index.php">CLAnswer</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/clanswer/add.php">Add</a> </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/clanswer/update.php
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

			$arBinds=Array(
				"ANSWER" => $arFields["ANSWER"],
				"FEEDBACK" => $arFields["FEEDBACK"],
				"MATCH_ANSWER" => $arFields["MATCH_ANSWER"],
			);

			$strUpdate = $DB->PrepareUpdate("b_learn_answer", $arFields, "learning");
			$strSql = "UPDATE b_learn_answer SET ".$strUpdate." WHERE ID=".$ID;
			$DB->QueryBind($strSql, $arBinds, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			return true;
		}
		return false;
	}


	
	/**
	* <p>Метод удаляет ответ с идентификатором ID.</p>
	*
	*
	* @param int $ID  Идентификатор ответа. </htm
	*
	* @return bool <p>Метод возвращает <i>true</i> в случае успешного удаления ответа, в
	* противном случае возвращает <i>false</i>.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $COURSE_ID = 97;
	*     $ANSWER_ID = 1553;
	* 
	*     if (CCourse::GetPermission($COURSE_ID) &gt;= 'W')
	*     {
	*         @set_time_limit(0);
	*         $DB-&gt;StartTransaction();
	*         if (!CLAnswer::Delete($ANSWER_ID))
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
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/clanswer/delete.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID < 1) return false;

		$strSql = "DELETE FROM b_learn_answer WHERE ID = ".$ID;

		if (!$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
			return false;

		return true;
	}


	
	/**
	* <p>Возвращает ответ по его коду ID.</p>
	*
	*
	* @param int $ID  Идентификатор ответа. </htm
	*
	* @return CDBResult <p>Возвращается объект <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p> </h
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $ANSWER_ID = 573;
	*     
	*     $res = CLAnswer::GetByID($ANSWER_ID);
	* 
	*     if ($arAnswer = $res-&gt;GetNext())
	*     {
	*         echo "Name: ".$arAnswer["ANSWER"];
	*     }
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#answer">Поля ответа</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/clanswer/index.php">CLAnswer</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/clanswer/getlist.php">GetList</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/clanswer/getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		return CLAnswer::GetList($arOrder=Array(), $arFilter=Array("ID" => $ID));
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
				case "QUESTION_ID":
					$arSqlSearch[] = CLearnHelper::FilterCreate("CA.".$key, $val, "number", $bFullJoin, $cOperationType);
					break;

				case "ANSWER":
					$arSqlSearch[] = CLearnHelper::FilterCreate("CA.".$key, $val, "string", $bFullJoin, $cOperationType);
					break;

				case "CORRECT":
					$arSqlSearch[] = CLearnHelper::FilterCreate("CA.".$key, $val, "string_equal", $bFullJoin, $cOperationType);
					break;
			}

		}

		return $arSqlSearch;
	}


	
	/**
	* <p>Возвращает список ответов по фильтру arFilter, отсортированный в порядке arOrder.</p>
	*
	*
	* @param array $arrayarOrder = Array("ID"=>"DESC") Массив для сортировки результата. Массив вида <i>array("поле
	* сортировки"=&gt;"направление сортировки" [, ...])</i>.<br> Поле для
	* сортировки может принимать значения: <ul> <li> <b>ID</b> - идентификатор
	* ответа;</li> <li> <b>SORT</b> - индекс сортировки;</li> <li> <b>CORRECT</b> -
	* правильность ответа;</li> <li> <b>ANSWER</b> - текст ответа;</li> <li> <b>RAND</b> -
	* случайный порядок;</li> </ul> Направление сортировки может принимать
	* значения: <ul> <li> <b>asc</b> - по возрастанию;</li> <li> <b>desc</b> - по
	* убыванию;</li> </ul> Необязательный. По умолчанию фильтруется по
	* убыванию идентификатора ответа.
	*
	* @param array $arrayarFilter = Array() Массив вида <i> array("фильтруемое поле"=&gt;"значение фильтра" [, ...])</i>.
	* Фильтруемое поле может принимать значения: <ul> <li> <b>ID</b> -
	* идентификатор ответа;</li> <li> <b>SORT</b> - индекс сортировки;</li> <li>
	* <b>QUESTION_ID</b> - идентификатор вопроса;</li> <li> <b>ANSWER</b> - текст ответа
	* (можно искать по шаблону [%_]);</li> <li> <b>CORRECT</b> - правильность ответа
	* (Y|N);</li> </ul> Перед названием фильтруемого поля можно указать тип
	* фильтрации: <ul> <li>"!" - не равно</li> <li>"&lt;" - меньше</li> <li>"&lt;=" - меньше
	* либо равно</li> <li>"&gt;" - больше</li> <li>"&gt;=" - больше либо равно</li> </ul> <br>
	* "<i>значения фильтра</i>" - одиночное значение или массив.<br><br>
	* Необязательный. По умолчанию записи не фильтруются.
	*
	* @return CDBResult <p>Возвращается объект <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p> </h
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $QUESTION_ID = 290;
	*     $res = CLAnswer::GetList(
	*         Array("SORT"=&gt;"DESC"), 
	*         Array("QUESTION_ID" =&gt; $QUESTION_ID)
	*     );
	* 
	*     while ($arAnswer = $res-&gt;GetNext())
	*     {
	*         echo "Answer name: ".$arAnswer["ANSWER"]."&lt;br&gt;";
	*     }
	* }
	* 
	* ?&gt;
	* 
	* &lt;?
	* 
	* if (CModule::IncludeModule("learning"))
	* {
	*     $QUESTION_ID = 290;
	* 
	*     $res = CLAnswer::GetList(
	*         Array("SORT"=&gt;"ASC"), 
	*         Array("QUESTION_ID" =&gt; $QUESTION_ID, "?ANSWER" =&gt; "sys")
	*     );
	* 
	*     while ($arAnswer = $res-&gt;GetNext())
	*     {
	*         echo "Answer name: ".$arAnswer["ANSWER"]."&lt;br&gt;";
	*     }
	* }
	* ?&gt;
	* 
	* &lt;?
	* 
	* if (CModule::IncludeModule("learning"))
	* {
	*     $QUESTION_ID = 290;
	* 
	*     $res = CLAnswer::GetList(
	*         Array(), 
	*         Array("QUESTION_ID" =&gt; $QUESTION_ID, "CORRECT" =&gt; "Y")
	*     );
	* 
	*     while ($arAnswer = $res-&gt;GetNext())
	*     {
	*         echo "Answer name: ".$arAnswer["ANSWER"]."&lt;br&gt;";
	*     }
	* }
	* 
	* ?&gt;
	* 
	* &lt;?
	* 
	* if (CModule::IncludeModule("learning"))
	* {
	*     $QUESTION_ID = 290;
	* 
	*     $res = CLAnswer::GetList(
	*         Array("TIMESTAMP_X" =&gt; "ASC", "SORT"=&gt;"ASC"), 
	*         Array("QUESTION_ID" =&gt; $QUESTION_ID)
	*     );
	* 
	*     while ($arAnswer = $res-&gt;GetNext())
	*     {
	*         echo "Answer name: ".$arAnswer["ANSWER"]."&lt;br&gt;";
	*     }
	* }
	* 
	* ?&gt;
	* 
	* &lt;?
	* 
	* if (CModule::IncludeModule("learning"))
	* {
	*     $QUESTION_ID = 290;
	* 
	*     $res = CLAnswer::GetList(
	*         Array("RAND"=&gt;""), 
	*         Array("QUESTION_ID" =&gt; $QUESTION_ID)
	*     );
	* 
	*     while ($arAnswer = $res-&gt;GetNext())
	*     {
	*         echo "Answer name: ".$arAnswer["ANSWER"]."&lt;br&gt;";
	*     }
	* }
	* 
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a></li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/clanswer/index.php">CLAnswer</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/clanswer/getbyid.php">GetByID</a> </li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#answer">Поля ответа</a></li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/clanswer/getlist.php
	* @author Bitrix
	*/
	public static function GetList($arOrder=Array(), $arFilter=Array())
	{
		global $DB, $USER;

		$arSqlSearch = CLAnswer::GetFilter($arFilter);

		$strSqlSearch = "";
		for($i=0; $i<count($arSqlSearch); $i++)
			if(strlen($arSqlSearch[$i])>0)
				$strSqlSearch .= " AND ".$arSqlSearch[$i]." ";

		$strSql =
		"SELECT CA.*, CQ.ID AS QUESTION_ID, CQ.NAME AS QUESTION_NAME ".
		"FROM b_learn_answer CA ".
		"INNER JOIN b_learn_question CQ ON CA.QUESTION_ID = CQ.ID ".
		"WHERE 1=1 ".
		$strSqlSearch;

		if (!is_array($arOrder))
			$arOrder = Array();

		foreach($arOrder as $by=>$order)
		{
			$by = strtolower($by);
			$order = strtolower($order);
			if ($order!="asc")
				$order = "desc";

			if ($by == "id") $arSqlOrder[] = " CA.ID ".$order." ";
			elseif ($by == "sort") $arSqlOrder[] = " CA.SORT ".$order." ";
			elseif ($by == "correct") $arSqlOrder[] = " CA.CORRECT ".$order." ";
			elseif ($by == "answer") $arSqlOrder[] = " CA.ANSWER ".$order." ";
			elseif ($by == "rand") $arSqlOrder[] = CTest::GetRandFunction();
			else
			{
				$arSqlOrder[] = " CA.ID ".$order." ";
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


	public static function GetStats($ID)
	{
		global $DB;

		$ID = intval($ID);
		$strSql = "SELECT COUNT(*) AS ALL_CNT, SUM(CASE WHEN CORRECT = 'Y' THEN 1 ELSE 0 END) AS CORRECT_CNT FROM b_learn_test_result WHERE ANSWERED = 'Y' AND QUESTION_ID = ".$ID;
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


	public static function getMultiStats($arIds)
	{
		global $DB;

		if ( ! is_array($arIds) )
			return (false);

		$arResult = array();

		$arIds = array_filter($arIds);

		if ( ! empty($arIds) )
		{
			$arIds = array_map('intval', $arIds);			

			$strSql = "SELECT QUESTION_ID, COUNT(*) AS ALL_CNT, SUM(CASE WHEN CORRECT = 'Y' THEN 1 ELSE 0 END) AS CORRECT_CNT 
				FROM b_learn_test_result 
				WHERE ANSWERED = 'Y' AND QUESTION_ID IN (" . implode(',', $arIds) . ")
				GROUP BY QUESTION_ID ";

			$rsStat = $DB->Query($strSql, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);
			while ($arStat = $rsStat->fetch())
			{
				$arResult[$arStat['QUESTION_ID']] = array(
					'ALL_CNT'     => (int) $arStat['ALL_CNT'],
					'CORRECT_CNT' => (int) $arStat['CORRECT_CNT']
				);
			}
		}

		return ($arResult);
	}
}
