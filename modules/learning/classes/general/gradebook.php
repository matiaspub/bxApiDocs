<?

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


	// 2012-04-10 Checked/modified for compatibility with new data model
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


	// 2012-04-10 Checked/modified for compatibility with new data model
	
	/**
	 * <p>Метод добавляет новую запись в журнал.</p>
	 *
	 *
	 *
	 *
	 * @param array $arFields  Массив <b>Array("поле"=&gt;"значение", ...)</b>. Содержит значения <a
	 * href="http://dev.1c-bitrix.ruapi_help/learning/fields.php#gradebook">всех полей</a> журнала.
	 * Обязательные поля должны быть заполнены. <br>
	 *
	 *
	 *
	 * @return int <p>Метод возвращает идентификатор добавленной записи в журнал,
	 * если добавление прошло успешно. При возникновении ошибки метод
	 * вернёт <i>false</i>, а в исключениях будут содержаться ошибки.</p>
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
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/learning/classes/cgradebook/index.php">CGradeBook</a>::<a
	 * href="http://dev.1c-bitrix.ruapi_help/learning/classes/cgradebook/update.php">Update</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/learning/fields.php#gradebook">Поля журнала</a> </li> </ul><a
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


	// 2012-04-10 Checked/modified for compatibility with new data model
	
	/**
	 * <p>Метод изменяет параметры записи в журнале с идентификатором ID.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Идентификатор записи в журнале.
	 *
	 *
	 *
	 * @param array $arFields  Массив Array("поле"=&gt;"значение", ...). Содержит значения <a
	 * href="http://dev.1c-bitrix.ruapi_help/learning/fields.php#gradebook">всех полей</a> журнала.
	 * Обязательные поля должны быть заполнены. <br>
	 *
	 *
	 *
	 * @return bool <p>Метод возвращает <i>true</i>, если изменение прошло успешно, при
	 * возникновении ошибки функция вернет <i>false</i>. При возникновении
	 * ошибки в исключениях будет содержаться текст ошибки.</p>
	 *
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
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/learning/fields.php#gradebook">Поля журнала</a> </li> <li>
	 * <a href="http://dev.1c-bitrix.ruapi_help/learning/classes/cgradebook/index.php">CGradeBook</a>::<a
	 * href="http://dev.1c-bitrix.ruapi_help/learning/classes/cgradebook/add.php">Add</a> </li> </ul><a name="examples"></a>
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


	// 2012-04-10 Checked/modified for compatibility with new data model
	
	/**
	 * <p>Метод удаляет запись в журнале с идентификатором ID.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Идентификатор записи.
	 *
	 *
	 *
	 * @return bool <p>Метод возвращает <i>true</i> в случае успешного удаления записи, в
	 * противном случае возвращает <i>false</i>.</p><a name="examples"></a>
	 *
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


	// 2012-04-10 Checked/modified for compatibility with new data model
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


	// 2012-04-10 Checked/modified for compatibility with new data model
	
	/**
	 * <p>Возвращает запись журнала по идентификатору ID. Учитываются права доступа текущего пользователя.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Идентификатор записи в журнале.
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
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/learning/fields.php#gradebook">Поля журнала</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/learning/classes/cgradebook/index.php">CGradeBook</a>::<a
	 * href="http://dev.1c-bitrix.ruapi_help/learning/classes/cgradebook/getlist.php">GetList</a> </li> </ul><a
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


	// 2012-04-10 Checked/modified for compatibility with new data model
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

	// 2012-04-10 Checked/modified for compatibility with new data model
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

	// 2012-04-10 Checked/modified for compatibility with new data model
	public static function AddExtraAttempts($STUDENT_ID, $TEST_ID, $COUNT = 1)
	{
		global $DB;

		$STUDENT_ID = intval($STUDENT_ID);
		$TEST_ID = intval($TEST_ID);
		$COUNT = intval($COUNT);

		$strSql = "SELECT ID, EXTRA_ATTEMPTS FROM b_learn_gradebook WHERE STUDENT_ID = ".$STUDENT_ID." AND TEST_ID = ".$TEST_ID."";
		$rs = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if (!ar == $rs->Fetch())
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

}

?>