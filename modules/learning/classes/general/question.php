<?
// 2012-04-11 Checked/modified for compatibility with new data model

/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/learning/classes/clquestion/index.php
 * @author Bitrix
 */
class CLQuestion
{
	public function CheckFields(&$arFields, $ID = false)
	{
		global $DB, $USER;
		$arMsg = Array();

		if ( (is_set($arFields, "NAME") || $ID === false) && strlen(trim($arFields["NAME"])) <= 0)
			$arMsg[] = array("id"=>"NAME", "text"=> GetMessage("LEARNING_BAD_NAME"));


		if (is_set($arFields, "FILE_ID"))
		{
			$error = CFile::CheckImageFile($arFields["FILE_ID"]);
			if (strlen($error)>0)
				$arMsg[] = array("id"=>"FILE_ID", "text"=> $error);
		}

		if(strlen($this->LAST_ERROR)<=0)
		{
			if (
				($ID === false && !is_set($arFields, "LESSON_ID"))
				||
				(is_set($arFields, "LESSON_ID") && intval($arFields["LESSON_ID"]) < 1)
				)
			{
				$arMsg[] = array("id"=>"LESSON_ID", "text"=> GetMessage("LEARNING_BAD_LESSON_ID"));
			}
			elseif (is_set($arFields, "LESSON_ID"))
			{
				$res = CLearnLesson::GetByID($arFields["LESSON_ID"]);
				if($arRes = $res->Fetch())
				{
					$oAccess = CLearnAccess::GetInstance($USER->GetID());

					$bAccessLessonModify =
						$oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_WRITE)
						|| $oAccess->IsLessonAccessible ($arFields["LESSON_ID"], CLearnAccess::OP_LESSON_WRITE);

					if ( ! $bAccessLessonModify )
						$arMsg[] = array("id"=>"LESSON_ID", "text"=> GetMessage("LEARNING_BAD_LESSON_ID_EX"));
				}
				else
				{
					$arMsg[] = array("id"=>"LESSON_ID", "text"=> GetMessage("LEARNING_BAD_LESSON_ID_EX"));
				}
			}
		}

		if(!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}

		if (is_set($arFields, "QUESTION_TYPE") && !in_array($arFields["QUESTION_TYPE"], Array("S", "M", "T", "R")))
			$arFields["QUESTION_TYPE"] = "S";

		if (is_set($arFields, "DESCRIPTION_TYPE") && $arFields["DESCRIPTION_TYPE"] != "html")
			$arFields["DESCRIPTION_TYPE"] = "text";

		if (is_set($arFields, "DIRECTION") && $arFields["DIRECTION"] != "H")
			$arFields["DIRECTION"] = "V";

		if (is_set($arFields, "SELF") && $arFields["SELF"] != "Y")
			$arFields["SELF"] = "N";

		if (is_set($arFields, "ACTIVE") && $arFields["ACTIVE"] != "Y")
			$arFields["ACTIVE"] = "N";

		if (is_set($arFields, "EMAIL_ANSWER") && $arFields["EMAIL_ANSWER"] != "Y")
			$arFields["EMAIL_ANSWER"] = "N";

		if (is_set($arFields, "CORRECT_REQUIRED") && $arFields["CORRECT_REQUIRED"] != "Y")
			$arFields["CORRECT_REQUIRED"] = "N";

		return true;
	}


	
	/**
	* <p>Метод добавляет новый вопрос.</p>
	*
	*
	* @param array $arFields  Массив <b>Array("поле"=&gt;"значение", ...)</b>. Содержит значения <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#question">всех полей</a> вопроса.
	* Обязательные поля должны быть заполнены. <br>
	*
	* @return int <p>Метод возвращает идентификатор добавленного вопроса, если
	* добавление прошло успешно. При возникновении ошибки метод вернет
	* <i>false</i>, а в исключениях будут содержаться ошибки.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $LESSON_ID = 431;
	* 
	*     $arFields = Array(
	*         "ACTIVE" =&gt; "Y",
	*         "LESSON_ID" =&gt; $LESSON_ID,
	*         "NAME" =&gt; "Lesson 1",
	*         "SORT" =&gt; "1",
	*         "NAME" =&gt; "Let us assume that there are several sites in the system. 
	*         Is it possible to assign users permissions on access to viewing statistics individually for each site?"
	*     );
	* 
	*     $question = new CLQuestion;
	*     $ID = $question-&gt;Add($arFields);
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
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/learning/classes/clquestion/index.php">CLQuestion</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/clquestion/update.php">Update</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#question">Поля вопроса</a> </li> </ul> <a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/clquestion/add.php
	* @author Bitrix
	*/
	public function Add($arFields)
	{
		global $DB, $USER_FIELD_MANAGER;

		if (
			$this->CheckFields($arFields)
			&& $USER_FIELD_MANAGER->CheckFields('LEARNING_QUESTIONS', 0, $arFields)
		)
		{
			unset($arFields["ID"]);

			if (
				array_key_exists("FILE_ID", $arFields)
				&& is_array($arFields["FILE_ID"])
				&& (
					!array_key_exists("MODULE_ID", $arFields["FILE_ID"])
					|| strlen($arFields["FILE_ID"]["MODULE_ID"]) <= 0
				)
			)
				$arFields["FILE_ID"]["MODULE_ID"] = "learning";

			CFile::SaveForDB($arFields, "FILE_ID", "learning");

			$ID = $DB->Add("b_learn_question", $arFields, array("DESCRIPTION", 'COMMENT_TEXT', 'INCORRECT_MESSAGE'));

			if ($ID)
				$USER_FIELD_MANAGER->Update('LEARNING_QUESTIONS', $ID, $arFields);

			foreach(GetModuleEvents('learning', 'OnAfterQuestionAdd', true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($ID, $arFields));

			return $ID;
		}

		return false;
	}


	
	/**
	* <p>Метод изменяет параметры вопроса с идентификатором ID.</p>
	*
	*
	* @param int $ID  Идентификатор вопроса.
	*
	* @param array $arFields  Массив <b>Array("поле"=&gt;"значение", ...)</b>. Содержит значения <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#question">всех полей</a> вопроса.
	* Обязательные поля должны быть заполнены. <br>
	*
	* @return bool <p>Метод возвращает <i>true</i>, если изменение прошло успешно, при
	* возникновении ошибки метод вернёт <i>false</i>. При возникновении
	* ошибки в исключениях будет содержаться текст ошибки.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $QUESTION_ID = 600;
	* 
	*     $arFields = Array(
	*         "ACTIVE" =&gt; "N",
	*         "NAME" =&gt; "New name of question",
	*         "SORT" =&gt; "555",
	*     );
	* 
	*     $question = new CLQuestion;
	*     $success = $question-&gt;Update($QUESTION_ID, $arFields);
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
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#question">Поля вопроса</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/learning/classes/clquestion/index.php">CLQuestion</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/clquestion/add.php">Add</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/clquestion/update.php
	* @author Bitrix
	*/
	public function Update($ID, $arFields)
	{
		global $DB, $USER_FIELD_MANAGER;

		$ID = intval($ID);
		if ($ID < 1) return false;

		if (is_set($arFields, "FILE_ID"))
		{
			if(strlen($arFields["FILE_ID"]["name"])<=0 && strlen($arFields["FILE_ID"]["del"])<=0 && strlen($arFields["FILE_ID"]["description"])<=0)
				unset($arFields["FILE_ID"]);
			else
			{
				$pic_res = $DB->Query("SELECT FILE_ID FROM b_learn_question WHERE ID=".$ID);
				if($pic_res = $pic_res->Fetch())
					$arFields["FILE_ID"]["old_file"]=$pic_res["FILE_ID"];
			}
		}

		if (
			$this->CheckFields($arFields, $ID)
			&& $USER_FIELD_MANAGER->CheckFields('LEARNING_QUESTIONS', $ID, $arFields)
		)
		{
			unset($arFields["ID"]);

			$arBinds=Array(
				"DESCRIPTION"       => $arFields["DESCRIPTION"],
				'COMMENT_TEXT'      => $arFields['COMMENT_TEXT'],
				'INCORRECT_MESSAGE' => $arFields['INCORRECT_MESSAGE']
			);

			if (
				array_key_exists("FILE_ID", $arFields)
				&& is_array($arFields["FILE_ID"])
				&& (
					!array_key_exists("MODULE_ID", $arFields["FILE_ID"])
					|| strlen($arFields["FILE_ID"]["MODULE_ID"]) <= 0
				)
			)
				$arFields["FILE_ID"]["MODULE_ID"] = "learning";

			CFile::SaveForDB($arFields, "FILE_ID", "learning");

			$USER_FIELD_MANAGER->Update('LEARNING_QUESTIONS', $ID, $arFields);
			$strUpdate = $DB->PrepareUpdate("b_learn_question", $arFields);
			if ($strUpdate !== '')
			{
				$strSql = "UPDATE b_learn_question SET ".$strUpdate." WHERE ID=".$ID;
				$DB->QueryBind($strSql, $arBinds, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}

			foreach(GetModuleEvents('learning', 'OnAfterQuestionUpdate', true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($ID, $arFields));

			return true;
		}
		return false;
	}


	
	/**
	* <p>Метод удаляет вопрос с идентификатором ID.</p>
	*
	*
	* @param int $ID  Идентификатор вопроса.
	*
	* @return bool <p>Метод возвращает <i>true</i> в случае успешного удаления вопроса, в
	* противном случае возвращает <i>false</i>.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $COURSE_ID = 97;
	*     $QUESTION_ID = 600;
	* 
	*     if (CCourse::GetPermission($COURSE_ID) &gt;= 'W')
	*     {
	*         @set_time_limit(0);
	*         $DB-&gt;StartTransaction();
	*         if (!CLQuestion::Delete($QUESTION_ID))
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
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/learning/classes/clquestion/index.php">CLQuestion</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/clquestion/add.php">Add</a> </li></ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/clquestion/delete.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB, $USER_FIELD_MANAGER;

		$ID = intval($ID);
		if ($ID < 1) return false;

		$strSql = "SELECT FILE_ID FROM b_learn_question WHERE ID = ".$ID;
		$r = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if (!$arQuestion = $r->Fetch())
			return false;

		$answers = CLAnswer::GetList(Array(), Array("QUESTION_ID" => $ID));
		while($arAnswer = $answers->Fetch())
		{
			if(!CLAnswer::Delete($arAnswer["ID"]))
				return false;
		}

		$arAttempts = Array();
		$strSql = "SELECT ATTEMPT_ID FROM b_learn_test_result WHERE QUESTION_ID = ".$ID;
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while($ar = $res->Fetch())
			$arAttempts[] = $ar["ATTEMPT_ID"]; //Attempts to recount

		//Results
		$strSql = "DELETE FROM b_learn_test_result WHERE QUESTION_ID = ".$ID;
		if (!$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
			return false;

		foreach($arAttempts as $ATTEMPT_ID)
		{
			CTestAttempt::RecountQuestions($ATTEMPT_ID);
			CTestAttempt::OnAttemptChange($ATTEMPT_ID);
		}

		$strSql = "DELETE FROM b_learn_question WHERE ID = ".$ID;

		if (!$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
			return false;

		$USER_FIELD_MANAGER->delete('LEARNING_QUESTIONS', $ID);

		CFile::Delete($arQuestion["FILE_ID"]);

		CEventLog::add(array(
			'AUDIT_TYPE_ID' => 'LEARNING_REMOVE_ITEM',
			'MODULE_ID'     => 'learning',
			'ITEM_ID'       => 'Q #' . $ID,
			'DESCRIPTION'   => 'question removed'
		));

		foreach(GetModuleEvents('learning', 'OnAfterQuestionDelete', true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID, $arQuestion));

		return true;
	}


	
	/**
	* <p>Возвращает вопрос по идентификатору.</p>
	*
	*
	* @param int $ID  Идентификатор вопроса.
	*
	* @return CDBResult <p>Возвращается объект <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p> </h
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $QUESTION_ID = 289;
	*     
	*     $res = CLQuestion::GetByID($QUESTION_ID);
	* 
	*     if ($arQuestion = $res-&gt;GetNext())
	*     {
	*         echo "Name: ".$arQuestion["NAME"];
	*     }
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#question">Поля вопроса</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/clquestion/index.php">CLQuestion</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/clquestion/getlist.php">GetList</a> </li> </ul> <a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/clquestion/getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		return CLQuestion::GetList($arOrder=Array(), $arFilter=Array("ID" => $ID));
	}


	public static function GetFilter($arFilter)
	{
		global $DBType;

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
				case "LESSON_ID":
				case "POINT":
					$arSqlSearch[] = CLearnHelper::FilterCreate("CQ.".$key, $val, "number", $bFullJoin, $cOperationType);
					break;

				case "COURSE_ID":
					// was:	$arSqlSearch[] = CLearnHelper::FilterCreate("C.".$key, $val, "number", $bFullJoin, $cOperationType);

					$courseLessonId = CCourse::CourseGetLinkedLesson ($val);
					if ($courseLessonId === false)
						break;	// it is not a course, so skipping

					if ($DBType === 'oracle')
					{
						// This subquery gets ids of all childs lesson for given $courseLessonId
						$subQuery = "
							SELECT TLE.TARGET_NODE
							FROM b_learn_lesson_edges TLE
							START WITH TLE.SOURCE_NODE=" . ($courseLessonId + 0) . "
							CONNECT BY NOCYCLE PRIOR TLE.TARGET_NODE = TLE.SOURCE_NODE";

						// But we need also $courseLessonId itself, so final clause will be:
						$arSqlSearch[] = '(CQ.LESSON_ID IN (' . $subQuery . ')
							OR CQ.LESSON_ID = ' . ($courseLessonId + 0) . ')';
					}
					elseif (($DBType === 'mysql') || ($DBType === 'mssql'))
					{
						// MySQL & MSSQL supports "WHERE IN(...)" clause for more than 10 000 elements

						// add to sql "WHERE" constraint: lessons id only from given array
						$sqlCourseLessonsIdsList = '';

						$oTree = CLearnLesson::GetTree($courseLessonId);
						$arChildLessonForCourse = $oTree->GetLessonsIdListInTree();

						// root lesson not in tree, so add it
						$arChildLessonForCourse[] = $courseLessonId;

						// We need escape data for SQL
						$arChildLessonForCourseEscaped = array_map('intval', $arChildLessonForCourse);

						$sqlCourseLessonsIdsList = implode (', ', $arChildLessonForCourseEscaped);

						if (strlen($sqlCourseLessonsIdsList) > 0)
							$arSqlSearch[] = 'CQ.LESSON_ID IN (' . $sqlCourseLessonsIdsList . ')';
					}
					else
						throw new LearnException('Unsupported DB engine: ' . $DBType, LearnException::EXC_ERR_ALL_GIVEUP);

					break;

				case "NAME":
					$arSqlSearch[] = CLearnHelper::FilterCreate("CQ.".$key, $val, "string", $bFullJoin, $cOperationType);
					break;

				case "QUESTION_TYPE":
				case "ACTIVE":
				case "SELF":
				case "CORRECT_REQUIRED":
					$arSqlSearch[] = CLearnHelper::FilterCreate("CQ.".$key, $val, "string_equal", $bFullJoin, $cOperationType);
					break;
			}

		}

		return $arSqlSearch;
	}


	
	/**
	* <p>Возвращает список вопросов по фильтру arFilter, отсортированный в порядке arOrder.</p>
	*
	*
	* @param array $arrayarOrder = Array("TIMESTAMP_X"=>"DESC") Массив для сортировки результата. Массив вида <i>array("поле
	* сортировки"=&gt;"направление сортировки" [, ...])</i>.<br> Поле для
	* сортировки может принимать значения: <ul> <li> <b>ID</b> - идентификатор
	* вопроса;</li> <li> <b>NAME</b> - название вопроса;</li> <li> <b>ACTIVE</b> - активность
	* вопроса;</li> <li> <b>SORT</b> - индекс сортировки;</li> <li> <b>SELF</b> - вопрос для
	* самопроверки;</li> <li> <b>POINT</b> - баллы;</li> <li> <b>TYPE</b> - тип вопроса;</li> <li>
	* <b>TIMESTAMP_X</b> - дата изменения вопроса.</li> </ul> Направление сортировки
	* может принимать значения: <ul> <li> <b>asc</b> - по возрастанию;</li> <li>
	* <b>desc</b> - по убыванию;</li> </ul> Необязательный. По умолчанию
	* сортируется по убыванию даты изменения вопроса.
	*
	* @param array $arrayarFilter = Array() Массив вида <i> array("фильтруемое поле"=&gt;"значение фильтра" [, ...])</i>.
	* Фильтруемое поле может принимать значения: <ul> <li> <b>ID</b> -
	* идентификатор вопроса;</li> <li> <b>NAME</b> - название вопроса (можно
	* искать по шаблону [%_]);</li> <li> <b>SORT</b> - индекс сортировки;</li> <li>
	* <b>ACTIVE</b> - фильтр по активности (Y|N);</li> <li> <b>LESSON_ID</b> - идентификатор
	* урока;</li> <li> <b>POINT</b> - баллы;</li> <li> <b>COURSE_ID</b> - идентификатор
	* курса;</li> <li> <b>QUESTION_TYPE</b> - тип вопроса (S - одиночный выбор, M -
	* множественный выбор);</li> <li> <b>SELF</b> - вопрос для самопроверки (Y|N).</li>
	* </ul> Перед названием фильтруемого поля может указать тип
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
	* 
	* if (CModule::IncludeModule("learning"))
	* {
	*     $LESSON_ID = 426;
	* 
	*     $res = CLQuestion::GetList(
	*         Array("TIMESTAMP_X" =&gt; "ASC", "SORT"=&gt;"ASC"), 
	*         Array("LESSON_ID" =&gt; $LESSON_ID)
	*     );
	* 
	*     while ($arQuestion = $res-&gt;GetNext())
	*     {
	*         echo "Question name: ".$arQuestion["NAME"]."&lt;br&gt;";
	*     }
	* }
	* 
	* ?&gt;
	* 
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $COURSE_ID = 97;
	*     $res = CLQuestion::GetList(
	*         Array("SORT"=&gt;"ASC"), 
	*         Array("ACTIVE" =&gt; "Y", "COURSE_ID" =&gt; $COURSE_ID)
	*     );
	* 
	*     while ($arQuestion = $res-&gt;GetNext())
	*     {
	*         echo "Question name: ".$arQuestion["NAME"]."&lt;br&gt;";
	*     }
	* }
	* 
	* ?&gt;
	* 
	* &lt;?
	* 
	* if (CModule::IncludeModule("learning"))
	* {
	*     $res = CLQuestion::GetList(
	*         Array("SORT"=&gt;"ASC"), 
	*         Array("?NAME" =&gt; "Site")
	*     );
	* 
	*     while ($arQuestion = $res-&gt;GetNext())
	*     {
	*         echo "Question name: ".$arQuestion["NAME"]."&lt;br&gt;";
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
	*     $res = CLQuestion::GetList(
	*         Array("NAME" =&gt; "ASC", "SORT"=&gt;"ASC"), 
	*         Array("COURSE_ID" =&gt; $COURSE_ID, "!SELF" =&gt; "Y")
	*     );
	* 
	*     while ($arQuestion = $res-&gt;GetNext())
	*     {
	*         echo "Question name: ".$arQuestion["NAME"]."&lt;br&gt;";
	*     }
	* }
	* 
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/clquestion/index.php">CLQuestion</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/clquestion/getbyid.php">GetByID</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#question">Поля вопроса</a> </li> </ul> <a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/clquestion/getlist.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = array(), $arFilter = array(), $bHz = false, $arNavParams = array(), $arSelect = array())
	{
		global $DB, $USER, $USER_FIELD_MANAGER;

		$obUserFieldsSql = new CUserTypeSQL();
		$obUserFieldsSql->SetEntity('LEARNING_QUESTIONS', "CQ.ID");
		$obUserFieldsSql->SetSelect($arSelect);
		$obUserFieldsSql->SetFilter($arFilter);
		$obUserFieldsSql->SetOrder($arOrder);

		$arSqlSearch = array_filter(CLQuestion::GetFilter($arFilter));

		$strSqlFrom = "FROM b_learn_question CQ "
			. "INNER JOIN b_learn_lesson CL ON CQ.LESSON_ID = CL.ID "
			. $obUserFieldsSql->GetJoin("CQ.ID")
			. " WHERE ";

		$r = $obUserFieldsSql->GetFilter();
		if (strlen($r) > 0)
			$arSqlSearch[] = "(".$r.")";

		if ( ! empty($arSqlSearch) )
			$strSqlFrom .= implode(' AND ', $arSqlSearch);
		else
			$strSqlFrom .= ' 1=1 ';

		$strSql = "SELECT CQ.ID, CQ.ACTIVE, CQ.LESSON_ID, CQ.QUESTION_TYPE,
				CQ.NAME, CQ.SORT, CQ.DESCRIPTION, CQ.DESCRIPTION_TYPE,
				CQ.COMMENT_TEXT, CQ.FILE_ID, CQ.SELF, CQ.POINT, CQ.DIRECTION,
				CQ.CORRECT_REQUIRED, CQ.EMAIL_ANSWER, CQ.INCORRECT_MESSAGE,"
			. $DB->DateToCharFunction("CQ.TIMESTAMP_X")." as TIMESTAMP_X "
			. $obUserFieldsSql->GetSelect()
			. " "
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
				$arSqlOrder[] = " CQ.ID ".$order." ";
			elseif ($by == "name")
				$arSqlOrder[] = " CQ.NAME ".$order." ";
			elseif ($by == "sort")
				$arSqlOrder[] = " CQ.SORT ".$order." ";
			elseif ($by == "point")
				$arSqlOrder[] = " CQ.POINT ".$order." ";
			elseif ($by == "type")
				$arSqlOrder[] = " CQ.QUESTION_TYPE ".$order." ";
			elseif ($by == "self")
				$arSqlOrder[] = " CQ.SELF ".$order." ";
			elseif ($by == "active")
				$arSqlOrder[] = " CQ.ACTIVE ".$order." ";
			elseif ($by == "correct_required")
				$arSqlOrder[] = " CQ.CORRECT_REQUIRED ".$order." ";
			elseif ($s = $obUserFieldsSql->getOrder($by))
				$arSqlOrder[] = ' ' . $s . ' ' . $order . ' ';
			else
				$arSqlOrder[] = " CQ.TIMESTAMP_X ".$order." ";
		}

		$strSqlOrder = "";
		DelDuplicateSort($arSqlOrder);
		$cnt = count($arSqlOrder);
		for ($i=0; $i<$cnt; $i++)
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
				$res_cnt = $DB->Query("SELECT COUNT(CQ.ID) as C " . $strSqlFrom);
				$res_cnt = $res_cnt->fetch();
				$res = new CDBResult();
				$res->NavQuery($strSql, $res_cnt['C'], $arNavParams);
			}
		}
		else
			$res = $DB->Query($strSql, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);

		$res->SetUserFields($USER_FIELD_MANAGER->GetUserFields('LEARNING_QUESTIONS'));

		return $res;
	}


	
	/**
	* <p>Метод возвращает количество вопросов по заданному фильтру.</p>
	*
	*
	* @param array $arrayarFilter = Array() Массив вида <i> array("фильтруемое поле"=&gt;"значение фильтра" [, ...])</i>.
	* Описание фильтра см. в <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/clquestion/getlist.php">CLQuestion::GetList</a>.<br> По
	* умолчанию вопросы не фильтруются.
	*
	* @return int <p>Число - количество вопросов.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $COURSE_ID = 97;
	*     
	*     $cnt = CLQuestion::GetCount(Array("ACTIVE" =&gt; "Y", "COURSE_ID" =&gt; $COURSE_ID));
	* 
	*     echo "Number of questions: ".$cnt;
	* }
	* 
	* ?&gt;
	* 
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $LESSON_ID = 426;
	*     
	*     $cnt = CLQuestion::GetCount(Array("LESSON_ID" =&gt; $LESSON_ID));
	* 
	*     echo "Number of questions: ".$cnt;
	* }
	* 
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/learning/classes/clquestion/index.php">CLQuestion</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/clquestion/getlist.php">GetList</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/clquestion/getcount.php
	* @author Bitrix
	*/
	public static function GetCount($arFilter=Array())
	{
		global $DB;

		$arSqlSearch = CLQuestion::GetFilter($arFilter);

		$strSqlSearch = "";
		$cnt = count($arSqlSearch);
		for($i=0; $i<$cnt; $i++)
			if(strlen($arSqlSearch[$i])>0)
				$strSqlSearch .= " AND ".$arSqlSearch[$i]." ";

		$strSql =
		"SELECT COUNT(DISTINCT CQ.ID) as C ".
		"FROM b_learn_question CQ ".
		"INNER JOIN b_learn_lesson CL ON CQ.LESSON_ID = CL.ID ".
		"WHERE 1=1 ".
		$strSqlSearch;

		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$res_cnt = $res->Fetch();

		return intval($res_cnt["C"]);
	}
}
