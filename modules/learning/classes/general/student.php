<?

// 2012-04-16 Checked/modified for compatibility with new data model

/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/learning/classes/cstudent/index.php
 * @author Bitrix
 */
class CStudent
{
	// 2012-04-16 Checked/modified for compatibility with new data model
	public static function CheckFields(&$arFields, $ID = false)
	{
		global $DB, $APPLICATION;
		$arMsg = array();

		if ((is_set($arFields, "USER_ID") || $ID === false) && intval($arFields["USER_ID"]) <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("LEARNING_BAD_USER_ID"), "EMPTY_USER_ID");
			return false;
		}
		elseif (is_set($arFields, "USER_ID"))
		{
			$dbResult = CUser::GetByID($arFields["USER_ID"]);
			if (!$dbResult->Fetch())
			{
				$APPLICATION->ThrowException(GetMessage("LEARNING_BAD_USER_ID_EX"), "ERROR_NO_USER_ID");
				return false;
			}

			$dbResult = CStudent::GetList(Array(), Array("USER_ID" => $arFields["USER_ID"]));
			if ($dbResult->Fetch())
			{
				$APPLICATION->ThrowException(GetMessage("LEARNING_BAD_USER_ID_EXISTS"), "ERROR_USER_ID_EXISTS");
				return false;
			}
		}

		if ($ID === false && !is_set($arFields, "TRANSCRIPT"))
		{
			$arFields["TRANSCRIPT"] = CStudent::GenerateTranscipt();
		}
		elseif(is_set($arFields, "TRANSCRIPT") && !preg_match("~^[0-9]{6,}$~",$arFields["TRANSCRIPT"]))
		{
			$arFields["TRANSCRIPT"] = CStudent::GenerateTranscipt();
		}

		if (is_set($arFields, "PUBLIC_PROFILE") && $arFields["PUBLIC_PROFILE"] != "N")
			$arFields["ACTIVE"] = "Y";

		return true;
	}


	// 2012-04-16 Checked/modified for compatibility with new data model
	
	/**
	* <p>Возвращает случайный числовой идентификатор.</p>
	*
	*
	* @param int $TranscriptLength = 8 Длина числового идентификатора. По умолчанию равна 8.
	*
	* @return int <p>Случайное число.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     echo CStudent::GenerateTranscipt();
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/learning/classes/cstudent/index.php">CStudent</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/cstudent/add.php">Add</a> </li></ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/cstudent/generatetranscipt.php
	* @author Bitrix
	*/
	public static function GenerateTranscipt($TranscriptLength = 8)
	{
		$TranscriptLength = intval($TranscriptLength);

		$digits = "312467589";
		$max = strlen($digits) - 1;

		$str = "";

		for ($i = 0; $i < $TranscriptLength; $i++)
			$str .= $digits[mt_rand(0,$max)];

		return $str;
	}


	// 2012-04-16 Checked/modified for compatibility with new data model
	
	/**
	* <p>Метод добавляет новую учетную запись студента.</p>
	*
	*
	* @param array $arFields  Массив Array("поле"=&gt;"значение", ...). Содержит значения <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#student">всех полей</a> учётной записи
	* студента. Обязательные поля должны быть заполнены. <br>
	*
	* @return int <p>Метод возвращает идентификатор добавленной учетной записи
	* студента (равный <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/index.php">коду
	* пользователя</a>), если добавление прошло успешно. При
	* возникновении ошибки метод вернёт <i>false</i>, а в исключениях будут
	* содержаться ошибки.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $arFields = Array(
	*         "USER_ID" =&gt; 151,
	*         "RESUME" =&gt; "My resume"
	*     );
	* 
	*     $student = new CStudent;
	*     $ID = $student-&gt;Add($arFields);
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
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/learning/classes/cstudent/index.php">CStudent</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/cstudent/update.php">Update</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#student">Поля учетной записи
	* студента</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/cstudent/add.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		global $DB;

		if(CStudent::CheckFields($arFields))
		{
			CLearnHelper::FireEvent('OnBeforeStudentAdd', $arFields);

			$arInsert = $DB->PrepareInsert("b_learn_student", $arFields, "learning");

			if (strlen($arInsert[0]) <= 0 || strlen($arInsert[0])<= 0)
				return false;

			$strSql =
				"INSERT INTO b_learn_student(".$arInsert[0].") ".
				"VALUES(".$arInsert[1].")";

			if(!$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
				return false;

			CLearnHelper::FireEvent('OnAfterStudentAdd', $arFields);

			return $arFields["USER_ID"];
		}

		return false;
	}


	// 2012-04-16 Checked/modified for compatibility with new data model
	
	/**
	* <p>Метод изменяет параметры учётной записи студента с идентификатором ID.</p>
	*
	*
	* @param int $USER_ID  Код пользователя. </h
	*
	* @param array $arFields  Массив <b>Array("поле"=&gt;"значение", ...)</b>. Содержит значения <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#student">всех полей</a> учетной записи
	* студента. Обязательные поля должны быть заполнены. <br>
	*
	* @return bool <p>Метод возвращает <i>true</i>, если изменение прошло успешно, при
	* возникновении ошибки метод вернёт <i>false</i>. При возникновении
	* ошибки в исключениях будет содержаться текст ошибки</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $USER_ID = 151;
	* 
	*     $arFields = Array(
	*         "RESUME" =&gt; "My new CP",
	*     );
	* 
	*     $student = new CStudent;
	*     $success = $student-&gt;Update($USER_ID, $arFields);
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
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#student">Поля учетной записи
	* студента</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/cstudent/index.php">CStudent</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/cstudent/add.php">Add</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/cstudent/update.php
	* @author Bitrix
	*/
	public static function Update($ID, $arFields)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID < 1) return false;

		unset($arFields["USER_ID"]);

		if (CStudent::CheckFields($arFields, $ID))
		{

			$arBinds=Array(
				"RESUME"=>$arFields["RESUME"]
			);

			CLearnHelper::FireEvent('OnBeforeStudentUpdate', $arFields);

			$strUpdate = $DB->PrepareUpdate("b_learn_student", $arFields, "learning");
			$strSql = "UPDATE b_learn_student SET ".$strUpdate." WHERE USER_ID=".$ID;
			$DB->QueryBind($strSql, $arBinds, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			CLearnHelper::FireEvent('OnAfterStudentUpdate', $arFields);

			return true;
		}

		return false;
	}


	// 2012-04-16 Checked/modified for compatibility with new data model
	
	/**
	* <p>Метод удаляет учётную запись студента с кодом пользователя USER_ID.</p>
	*
	*
	* @param int $USER_ID  Код пользователя. </h
	*
	* @return bool <p>Метод возвращает <i>true</i> в случае успешного удаления учётной
	* записи студента, в противном случае возвращает <i>false</i>.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $USER_ID = 3;
	*     if ($USER-&gt;IsAdmin())
	*     {
	*         @set_time_limit(0);
	*         $DB-&gt;StartTransaction();
	*         if (!CStudent::Delete($USER_ID))
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
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/learning/classes/cstudent/index.php">CStudent</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/cstudent/add.php">Add</a> </li></ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/cstudent/delete.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID < 1) return false;

		CLearnHelper::FireEvent('OnBeforeStudentDelete', $ID);

		//Certification
		$records = CCertification::GetList(Array(), Array("STUDENT_ID" => $ID));
		while($arRecord = $records->Fetch())
		{
			if(!CCertification::Delete($arRecord["ID"]))
				return false;
		}

		$strSql = "DELETE FROM b_learn_student WHERE USER_ID = ".$ID;

		if (!$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
			return false;

		CLearnHelper::FireEvent('OnAfterStudentDelete', $ID);

		return true;
	}


	// 2012-04-16 Checked/modified for compatibility with new data model
	
	/**
	* <p>Возвращает учётную запись студента по коду пользователя USER_ID.</p>
	*
	*
	* @param int $USER_ID  Код пользователя. </h
	*
	* @return CDBResult <p>Возвращается объект <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p> </h
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $USER_ID = 3;
	*     
	*     $res = CStudent::GetByID($USER_ID);
	* 
	*     if ($arStudent = $res-&gt;GetNext())
	*     {
	*         echo "CP: ".$arStudent["RESUME"];
	*     }
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#student">Поля учетной записи
	* студента</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/cstudent/index.php">CStudent</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/cstudent/getlist.php">GetList</a> </li> </ul> <a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/cstudent/getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		return CStudent::GetList(Array(),Array("USER_ID"=> $ID));
	}


	// 2012-04-16 Checked/modified for compatibility with new data model
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
				case "USER_ID":
				case "TRANSCRIPT":
					$arSqlSearch[] = CLearnHelper::FilterCreate("S.".$key, $val, "number", $bFullJoin, $cOperationType);
					break;

				case "PUBLIC_PROFILE":
					$arSqlSearch[] = CLearnHelper::FilterCreate("S.".$key, $val, "string_equal", $bFullJoin, $cOperationType);
					break;

				case "RESUME":
					$arSqlSearch[] = CLearnHelper::FilterCreate("S.".$key, $val, "string", $bFullJoin, $cOperationType);
					break;
			}

		}

		return $arSqlSearch;
	}


	// 2012-04-16 Checked/modified for compatibility with new data model
	
	/**
	* <p>Возвращает список учётных записей студентов по фильтру <b>arFilter</b>, отсортированный в порядке <b>arOrder</b>.</p>
	*
	*
	* @param array $arrayarOrder = Array("ID"=>"DESC") Массив для сортировки результата. Массив вида <i>array("поле
	* сортировки"=&gt;"направление сортировки" [, ...])</i>.<br>Поле для
	* сортировки может принимать значения: <ul> <li> <b>USER_ID</b> - <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/index.php">Код пользователя</a>; </li> <li>
	* <b>PUBLIC_PROFILE</b> - профиль доступен публично (Y/N); </li> </ul>Направление
	* сортировки может принимать значения: <ul> <li> <b>asc</b> - по возрастанию;
	* </li> <li> <b>desc</b> - по убыванию; </li> </ul>Необязательный. По умолчанию
	* сортируется по убыванию кода пользователя.
	*
	* @param array $arrayarFilter = Array() Массив вида <i>array("фильтруемое поле"=&gt;"значение фильтра" [, ...])</i>.
	* Фильтруемое поле может принимать значения: <ul> <li> <b>USER_ID</b> - <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/index.php">Код пользователя</a>; </li> <li>
	* <b>PUBLIC_PROFILE</b> - профиль доступен публично (Y/N); </li> <li> <b>TRANSCRIPT</b> -
	* числовой случайный идентификатор; </li> <li> <b>RESUME</b> - резюме студента
	* (можно искать по шаблону [%_]); </li> </ul>Перед названием фильтруемого
	* поля можно указать тип фильтрации: <ul> <li>"!" - не равно </li> <li>"&lt;" -
	* меньше </li> <li>"&lt;=" - меньше либо равно </li> <li>"&gt;" - больше </li> <li>"&gt;=" -
	* больше либо равно </li> </ul> <br>"<i>значения фильтра</i>" - одиночное
	* значение или массив.<br><br>Необязательный. По умолчанию записи не
	* фильтруются.
	*
	* @return CDBResult <p>Возвращается объект <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p> </h
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $USER_ID = 1; $TRANSCRIPT = 46785643;
	*     $res = CStudent::GetList(Array(), Array("USER_ID" =&gt; $USER_ID, "TRANSCRIPT" =&gt; $TRANSCRIPT));
	* 
	*     while ($arProfile = $res-&gt;GetNext())
	*     {
	*         echo $arProfile["RESUME"];
	*     }
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/cstudent/index.php">CStudent</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/cstudent/getbyid.php">GetByID</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#student">Поля студента</a> </li> </ul> <a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/cstudent/getlist.php
	* @author Bitrix
	*/
	public static function GetList($arOrder=Array(), $arFilter=Array())
	{
		global $DB, $USER;

		$arSqlSearch = CStudent::GetFilter($arFilter);

		$strSqlSearch = "";
		for($i=0; $i<count($arSqlSearch); $i++)
			if(strlen($arSqlSearch[$i])>0)
				$strSqlSearch .= " AND ".$arSqlSearch[$i]." ";

		$strSql =
		"SELECT S.* ".
		//$DB->Concat("'('",'U.LOGIN',"') '",'U.NAME',"' '", 'U.LAST_NAME')." as USER_NAME ".
		"FROM b_learn_student S ".
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

			if ($by == "user_id")						$arSqlOrder[] = " S.USER_ID ".$order." ";
			elseif ($by == "public_profile")		$arSqlOrder[] = " S.PUBLIC_PROFILE ".$order." ";
			else
			{
				$arSqlOrder[] = " S.USER_ID ".$order." ";
				$by = "user_id";
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
}
