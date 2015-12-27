<?php


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/index.php
 * @author Bitrix
 */
class CCourse
{
	// 2012-04-17 Checked/modified for compatibility with new data model
	
	/**
	* <p>Возвращает список курсов, отсортированный в порядке arOrder. Учитываются права доступа текущего пользователя.</p>
	*
	*
	* @param array $arrayarOrder = array() Массив для сортировки результата. Массив вида <i>array("поле
	* сортировки"=&gt;"направление сортировки" [, ...])</i>.<br> Поле для
	* сортировки может принимать значения: <ul> <li> <b>ID</b> - идентификатор
	* курса;</li> <li> <b>NAME</b> - название курса;</li> <li> <b>ACTIVE</b> - активность
	* курса;</li> <li> <b>SORT</b> - индекс сортировки;</li> <li> <b>TIMESTAMP_X</b> - дата
	* изменения курса.</li> </ul> Направление сортировки может принимать
	* значения: <ul> <li> <b>asc</b> - по возрастанию;</li> <li> <b>desc</b> - по
	* убыванию;</li> </ul>
	*
	* @param array $arrayarFields = array() Массив из полей курса для фильтрации результирующего списка.
	*
	* @param array $arrayarNavParams = array() Массив настроек постраничной навигации.
	*
	* @return CDBResult <p>Возвращается объект <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p> </h
	*
	* <h4>Example</h4> 
	* <pre>
	* lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $res = CCourse::GetList(
	*         Array("SORT"=&gt;"ASC"), 
	*         Array("ACTIVE" =&gt; "Y", "CNT_ACTIVE" =&gt; "Y"), 
	*         $bIncCnt = true
	*     );
	* 
	*     while ($arCourse = $res-&gt;GetNext())
	*     {
	*         echo "Course name: ".$arCourse["NAME"]."&lt;br&gt;";
	*         echo "Active lessons: ".$arCourse["ELEMENT_CNT"]."&lt;br&gt;&lt;br&gt;";
	*     }
	* }
	* 
	* ?&gt;
	* 
	* &lt;?
	* 
	* if (CModule::IncludeModule("learning"))
	* {
	*     $res = CCourse::GetList(
	*         Array("SORT"=&gt;"ASC"), 
	*         Array("?NAME" =&gt; "Site")
	*     );
	* 
	*     while ($arCourse = $res-&gt;GetNext())
	*     {
	*         echo "Course name: ".$arCourse["NAME"]."&lt;br&gt;";
	*     }
	* }
	* ?&gt;
	* 
	* &lt;?
	* 
	* if (CModule::IncludeModule("learning"))
	* {
	*     $res = CCourse::GetList(
	*         Array("NAME" =&gt; "ASC", "SORT"=&gt;"ASC"), 
	*         Array("CHECK_PERMISSIONS" =&gt; "N")
	*     );
	* 
	*     while ($arCourse = $res-&gt;GetNext())
	*     {
	*         echo "Course name: ".$arCourse["NAME"]."&lt;br&gt;";
	*     }
	* }
	* 
	* ?&gt;
	* 
	* &lt;?
	* if(CModule::IncludeModule("learning")):
	* 
	*     $res = CCourse::GetList(Array("SORT" =&gt; "DESC"), Array("ACTIVE" =&gt; "Y", "ACTIVE_DATE" =&gt; "Y", "SITE_ID" =&gt; LANG));
	* 
	*     while ($arElement = $res-&gt;GetNext()):?&gt;
	* 
	*         &lt;font class="text"&gt;
	*         &lt;?if ($arElement["PREVIEW_PICTURE"]):?&gt;
	*             &lt;table cellpadding="0" cellspacing="0" border="0" align="left"&gt;
	* 
	*                 &lt;tr&gt;
	*                     &lt;td&gt;&lt;?echo ShowImage($arElement["PREVIEW_PICTURE"], 200, 200, "hspace='0' vspace='2' align='left' border='0'", "", true);?&gt;&lt;/td&gt;
	*                     &lt;td valign="top" width="0%"&gt;&lt;img src="/bitrix/images/1.gif" width="10" height="1"&gt;&lt;/td&gt;
	* 
	*                 &lt;/tr&gt;
	*             &lt;/table&gt;
	*         &lt;?endif;?&gt;
	*         &lt;a target="blank_" href="&lt;?=$COURSE_URL?&gt;?COURSE_ID=&lt;?=$arElement["ID"]?&gt;"&gt;&lt;?=$arElement["NAME"]?&gt;&lt;/a&gt;
	* 
	*         &lt;?=(strlen($arElement["PREVIEW_TEXT"])&gt;0 ? "&lt;br&gt;".$arElement["PREVIEW_TEXT"]: "")?&gt;
	*         &lt;/font&gt;&lt;br clear="all"&gt;&lt;br&gt;
	* 
	*     &lt;?endwhile?&gt;
	* &lt;?endif?&gt;
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/index.php">CCourse</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/getbyid.php">GetByID</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#course">Поля курса</a> </li> </ul> <a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/getlist.php
	* @author Bitrix
	*/
	final public static function GetList($arOrder = array(), $arFields = array(), $arNavParams = array())
	{
		// Lists only lesson-courses
		$arFields = array_merge (array('>LINKED_LESSON_ID' => 0), $arFields);

		foreach ($arOrder as $key => $value)
		{
			if (strtoupper($key) === 'ID')
			{
				$arOrder['COURSE_ID'] = $arOrder[$key];
				unset ($arOrder[$key]);
			}
		}

		// We must replace '...ID' => '...COURSE_ID', where '...' is some operation (such as '!', '<=', etc.)
		foreach ($arFields as $key => $value)
		{
			// If key ends with 'ID'
			if ((strlen($key) >= 2) && (strtoupper(substr($key, -2)) === 'ID'))
			{
				// And prefix before 'ID' doesn't contains letters
				if ( ! preg_match ("/[a-zA-Z_]+/", substr($key, 0, -2)) )
				{
					$prefix = '';
					if (strlen($key) > 2)
						$prefix = substr($key, 0, -2);

					$arFields[$prefix . 'COURSE_ID'] = $arFields[$key];
					unset ($arFields[$key]);
				}
			}
		}

		$arFields['#REPLACE_COURSE_ID_TO_ID'] = true;

		$res = CLearnLesson::GetList($arOrder, $arFields, array(), $arNavParams);
		return ($res);
	}


	/**
	 * Gets id of lesson corresponded to given course
	 * @param integer id of course
	 * @throws LearnException with error bit set (one of):
	 *         - LearnException::EXC_ERR_ALL_GIVEUP
	 *         - LearnException::EXC_ERR_ALL_LOGIC
	 * @return integer/bool id of linked (corresponded) lesson or 
	 *         FALSE if there is no lesson corresponded to the course.
	 */
	final public static function CourseGetLinkedLesson ($courseId)
	{
		$arMap = CLearnLesson::GetCourseToLessonMap();

		if ( ! isset($arMap['C' . $courseId]) )
		{
			return false;
		}

		// return id of corresponded lesson
		return ($arMap['C' . $courseId]);
	}


	// 2012-04-17 Checked/modified for compatibility with new data model
	public static function CheckFields($arFields, $ID = false)
	{
		global $DB;
		$arMsg = array();

		if ( (is_set($arFields, "NAME") || $ID === false) && strlen(trim($arFields["NAME"])) <= 0)
		{
			$arMsg[] = array("id"=>"NAME", "text"=> GetMessage("LEARNING_BAD_NAME"));
		}

		if (is_set($arFields, "ACTIVE_FROM") && strlen($arFields["ACTIVE_FROM"])>0 && (!$DB->IsDate($arFields["ACTIVE_FROM"], false, LANG, "FULL")))
		{
			$arMsg[] = array("id"=>"ACTIVE_FROM", "text"=> GetMessage("LEARNING_BAD_ACTIVE_FROM"));
		}

		if (is_set($arFields, "ACTIVE_TO") && strlen($arFields["ACTIVE_TO"])>0 && (!$DB->IsDate($arFields["ACTIVE_TO"], false, LANG, "FULL")))
		{
			$arMsg[] = array("id"=>"ACTIVE_TO", "text"=> GetMessage("LEARNING_BAD_ACTIVE_TO"));
		}

		if (is_set($arFields, "PREVIEW_PICTURE") && is_array($arFields["PREVIEW_PICTURE"]))
		{
			$error = CFile::CheckImageFile($arFields["PREVIEW_PICTURE"]);
			if (strlen($error)>0)
			{
				$arMsg[] = array("id"=>"PREVIEW_PICTURE", "text"=> $error);
			}
		}

		//Sites
		if (
			($ID === false && !is_set($arFields, "SITE_ID"))
			||
			(is_set($arFields, "SITE_ID"))
			&&
			(!is_array($arFields["SITE_ID"]) || empty($arFields["SITE_ID"]))
			)
		{
			$arMsg[] = array("id"=>"SITE_ID[]", "text"=> GetMessage("LEARNING_BAD_SITE_ID"));
		}
		elseif (is_set($arFields, "SITE_ID"))
		{
			$tmp = "";
			foreach($arFields["SITE_ID"] as $lang)
			{
				$res = CSite::GetByID($lang);
				if(!$res->Fetch())
				{
					$tmp .= "'".$lang."' - ".GetMessage("LEARNING_BAD_SITE_ID_EX")."<br>";
				}
			}
			if ($tmp!="") $arMsg[] = array("id"=>"SITE_ID[]", "text"=> $tmp);
		}

		if(!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}

		return true;
	}


	// 2012-04-17 Checked/modified for compatibility with new data model
	
	/**
	* <p>Метод добавляет новый курс.</p>
	*
	*
	* @param array $arFields  Массив Array("поле"=&gt;"значение", ...). Содержит значения <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#course">всех полей</a> курса.
	* Обязательные поля должны быть заполнены. <br> Дополнительно в поле
	* SITE_ID должен находиться массив идентификаторов сайтов, к которым
	* привязан добавляемый курс. <br> Кроме того, с помощью поля "GROUP_ID",
	* значением которого должен быть массив соответствий кодов групп
	* правам доступа, можно установить права для разных групп на доступ
	* к курсу (см. <a href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/index.php">CCourse</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/setpermission.php">SetPermission</a>).
	*
	* @return int <p>Метод возвращает идентификатор добавленного курса, если
	* добавление прошло успешно. При возникновении ошибки метод вернет
	* <i>false</i>, а в исключениях будут содержаться ошибки.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* 
	* if (CModule::IncludeModule("learning"))
	* {
	*     $arFields = Array(
	*         "ACTIVE" =&gt; "Y",
	*         "NAME" =&gt; "My First Course",
	*         "SITE_ID" =&gt; Array("ru", "en"), //Sites
	*         "GROUP_ID" =&gt; Array("2" =&gt; "R"), //Permissions: Everyone can read my course
	*         "SORT" =&gt; "100",
	*         "DESCRIPTION" =&gt; "It's my first e-Learning course",
	*         "DESCRIPTION_TYPE" =&gt; "text",
	*     );
	* 
	*     $course = new CCourse;
	*     $ID = $course-&gt;Add($arFields);
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
	*         
	*     }
	* 
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/index.php">CCourse</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/update.php">Update</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#course">Поля курса</a> </li> </ul> <a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/add.php
	* @author Bitrix
	*/
	public function Add($arFields)
	{
		global $DB;

		if (is_set($arFields, "ACTIVE") && $arFields["ACTIVE"] != "Y")
			$arFields["ACTIVE"] = "N";

		if (is_set($arFields, "DETAIL_TEXT_TYPE") && $arFields["DETAIL_TEXT_TYPE"] != "html")
			$arFields["DETAIL_TEXT_TYPE"] = "text";

		if (is_set($arFields, "PREVIEW_TEXT_TYPE") && $arFields["PREVIEW_TEXT_TYPE"] != "html")
			$arFields["PREVIEW_TEXT_TYPE"]="text";

		if (is_set($arFields, "PREVIEW_PICTURE") && strlen($arFields["PREVIEW_PICTURE"]["name"])<=0 && strlen($arFields["PREVIEW_PICTURE"]["del"])<=0)
			unset($arFields["PREVIEW_PICTURE"]);

		if (is_set($arFields, "RATING") && !in_array($arFields["RATING"], Array("Y", "N")))
			$arFields["RATING"] = "N";

		if (is_set($arFields, "RATING_TYPE") && !in_array($arFields["RATING_TYPE"], Array("like", "standart_text", "like_graphic", "standart")))
			$arFields["RATING_TYPE"] = NULL;

		if($this->CheckFields($arFields))
		{
			unset($arFields["ID"]);

			$arFieldsLesson = $arFields;
			$arFieldsToUnset = array ('GROUP_ID', 'SITE_ID');

			// Some fields mustn't be in unilesson
			foreach ($arFieldsToUnset as $key => $value)
				if (array_key_exists($value, $arFieldsLesson))
					unset ($arFieldsLesson[$value]);

			$lessonId = CLearnLesson::Add ($arFieldsLesson, $isCourse = true);
			$ID = CLearnLesson::GetLinkedCourse ($lessonId);
			if ($ID === false)
				return (false);

			//Sites
			$str_LID = "''";
			foreach($arFields["SITE_ID"] as $lang)
					$str_LID .= ", '".$DB->ForSql($lang)."'";
			$strSql = "DELETE FROM b_learn_course_site WHERE COURSE_ID=".$ID;
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			$strSql =
				"INSERT INTO b_learn_course_site(COURSE_ID, SITE_ID) ".
				"SELECT ".$ID.", LID ".
				"FROM b_lang ".
				"WHERE LID IN (".$str_LID.") ";

			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			CLearnCacheOfLessonTreeComponent::MarkAsDirty();

			if ($ID && (is_set($arFields, "NAME") || is_set($arFields, "DETAIL_TEXT")))
			{
				if (CModule::IncludeModule("search"))
				{
					$rsCourse = CCourse::GetByID($ID);
					if ($arCourse = $rsCourse->Fetch())
					{
						$arGroupPermissions = CCourse::GetGroupPermissions($arCourse["ID"]);

						if(is_set($arFields, "SITE_ID"))
						{
							$arSiteIds = array();
							foreach($arFields["SITE_ID"] as $lang)
							{
								$rsSitePaths = CSitePath::GetList(Array(), Array("SITE_ID" => $lang, "TYPE" => "C"));
								if ($arSitePaths = $rsSitePaths->Fetch())
								{
									$strPath = $arSitePaths["PATH"];
								}
								else
								{
									$strPath = "";
								}
								$arSiteIds[$lang] = str_replace("#COURSE_ID#", $ID, $strPath);
							}

							$detailText = '';
							if ($arCourse["DETAIL_TEXT_TYPE"] !== 'text')
								$detailText = CSearch::KillTags($arCourse['DETAIL_TEXT']);
							else
								$detailText = strip_tags($arCourse['DETAIL_TEXT']);

							$dataBody = '';
							if (strlen($detailText) > 0)
								$dataBody = $detailText;
							else
								$dataBody = $arCourse['NAME'];

							$arSearchIndex = Array(
								"LAST_MODIFIED"	=> $arCourse["TIMESTAMP_X"],
								"TITLE" => $arCourse["NAME"],
								"BODY" => $dataBody,
								"SITE_ID" => $arSiteIds,
								"PERMISSIONS" => $arGroupPermissions,
								"PARAM1" => "C".$ID
							);

							CSearch::Index("learning", "C".$ID, $arSearchIndex);
						}
					}
				}
			}

			return $ID;
		}
		return false;
	}


	// 2012-04-17 Checked/modified for compatibility with new data model
	
	/**
	* <p>Метод изменяет параметры курса с идентификатором ID.</p>
	*
	*
	* @param int $ID  Идентификатор изменяемого курса.
	*
	* @param array $arFields  Массив Array("поле"=&gt;"значение", ...). Содержит значения <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#course">всех полей</a> курса.
	* Обязательные поля должны быть заполнены. <br>Дополнительно в поле
	* SITE_ID должен находиться массив идентификаторов сайтов, к которым
	* привязан добавляемый курс. <br>Кроме того, с помощью поля "GROUP_ID",
	* значением которого должен быть массив соответствий кодов групп
	* правам доступа, можно установить права для разных групп на доступ
	* к курсу (см. <a href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/index.php">CCourse</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/setpermission.php">SetPermission</a>).
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
	*     $arFields = Array(
	*         "ACTIVE" =&gt; "Y",
	*         "NAME" =&gt; "New name",
	*         "SITE_ID" =&gt; Array("en"), //Sites
	*     );
	* 
	*     $ID = 1;//Course ID
	* 
	*     $course = new CCourse;
	*     $success = $course-&gt;Update($ID, $arFields);
	* 
	*     if($success)
	*     {
	*         echo "Ok!";
	*         
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
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#course">Поля курса</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/index.php">CCourse</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/add.php">Add</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/update.php
	* @author Bitrix
	*/
	public function Update($ID, $arFields)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID < 1) return false;

		if (is_set($arFields, "ACTIVE") && $arFields["ACTIVE"] != "Y")
			$arFields["ACTIVE"] = "N";

		if (is_set($arFields, "DESCRIPTION_TYPE") && $arFields["DESCRIPTION_TYPE"] != "html")
			$arFields["DESCRIPTION_TYPE"] = "text";

		if (is_set($arFields, "DETAIL_TEXT_TYPE") && $arFields["DETAIL_TEXT_TYPE"] != "html")
			$arFields["DETAIL_TEXT_TYPE"] = "text";

		if (is_set($arFields, "PREVIEW_TEXT_TYPE") && $arFields["PREVIEW_TEXT_TYPE"] != "html")
			$arFields["PREVIEW_TEXT_TYPE"]="text";

		if (is_set($arFields, "RATING") && !in_array($arFields["RATING"], Array("Y", "N")))
			$arFields["RATING"] = NULL;

		if (is_set($arFields, "RATING_TYPE") && !in_array($arFields["RATING_TYPE"], Array("like", "standart_text", "like_graphic", "standart")))
			$arFields["RATING_TYPE"] = NULL;

		$lessonId = self::CourseGetLinkedLesson ($ID);
		if ($this->CheckFields($arFields, $ID) && $lessonId !== false)
		{
			if (array_key_exists('ID', $arFields))
				unset($arFields["ID"]);

			$arFieldsLesson = $arFields;
			$arFieldsToUnset = array ('GROUP_ID', 'SITE_ID');

			foreach ($arFieldsToUnset as $key => $value)
				if (array_key_exists($value, $arFieldsLesson))
					unset ($arFieldsLesson[$value]);

			//Sites
			if(is_set($arFields, "SITE_ID"))
			{
				$str_LID = "''";
				foreach($arFields["SITE_ID"] as $lang)
					$str_LID .= ", '".$DB->ForSql($lang)."'";

				$strSql = "DELETE FROM b_learn_course_site WHERE COURSE_ID=".$ID;
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

				$strSql =
					"INSERT INTO b_learn_course_site(COURSE_ID, SITE_ID) ".
					"SELECT ".$ID.", LID ".
					"FROM b_lang ".
					"WHERE LID IN (".$str_LID.") ";

				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			}

			CLearnLesson::Update($lessonId, $arFieldsLesson);

			if ($ID && (is_set($arFields, "NAME") || is_set($arFields, "DESCRIPTION") || is_set($arFields, 'DETAIL_TEXT')))
			{
				if (CModule::IncludeModule("search"))
				{
					$rsCourse = CCourse::GetByID($ID);
					if ($arCourse = $rsCourse->Fetch())
					{
						$arGroupPermissions = CCourse::GetGroupPermissions($arCourse["ID"]);

						if(is_set($arFields, "SITE_ID"))
						{
							$arSiteIds = array();
							foreach($arFields["SITE_ID"] as $lang)
							{
								$rsSitePaths = CSitePath::GetList(Array(), Array("SITE_ID" => $lang, "TYPE" => "C"));
								if ($arSitePaths = $rsSitePaths->Fetch())
								{
									$strPath = $arSitePaths["PATH"];
								}
								else
								{
									$strPath = "";
								}
								$arSiteIds[$lang] = str_replace("#COURSE_ID#", $ID, $strPath);
							}

							$detailText = '';
							if ($arCourse["DETAIL_TEXT_TYPE"] !== 'text')
								$detailText = CSearch::KillTags($arCourse['DETAIL_TEXT']);
							else
								$detailText = strip_tags($arCourse['DETAIL_TEXT']);

							$dataBody = '';
							if (strlen($detailText) > 0)
								$dataBody = $detailText;
							else
								$dataBody = $arCourse['NAME'];

							$arSearchIndex = Array(
								"LAST_MODIFIED"	=> $arCourse["TIMESTAMP_X"],
								"TITLE" => $arCourse["NAME"],
								"BODY" => $dataBody,
								"SITE_ID" => $arSiteIds,
								"PERMISSIONS" => $arGroupPermissions,
							);

							CSearch::Index("learning", "C".$ID, $arSearchIndex);
						}

						CSearch::ChangePermission("learning", $arGroupPermissions, false, "C".$arCourse["ID"]);
					}
				}
			}

			global $CACHE_MANAGER;
			$CACHE_MANAGER->ClearByTag('LEARN_COURSE_'.$ID);

			return true;
		}

		return false;
	}


	// 2012-04-17 Checked/modified for compatibility with new data model
	/**
	 * Removes course (as node, not recursively)
	 */
	
	/**
	* <p>Метод удаляет курс с идентификатором ID.</p> <p> </p> <div class="note"> <b>Примечание</b> <p>Если есть сертификаты, полученные за указанный курс, метод возвратит <i>false</i>.</p> </div>
	*
	*
	* @param int $ID  Идентификатор курса.
	*
	* @return bool <p>Метод возвращает <i>true</i> в случае успешного удаления курса, в
	* противном случае возвращает <i>false</i>.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* 
	* if (CModule::IncludeModule("learning"))
	* {
	* 
	*     $ID = 109;//Course ID
	* 
	*     if($USER-&gt;IsAdmin())
	*     {
	*         @set_time_limit(0);
	*         $DB-&gt;StartTransaction();
	*         if(!CCourse::Delete($ID))
	*             $DB-&gt;Rollback();
	*         else
	*             $DB-&gt;Commit();
	*     }
	* 
	* }
	* 
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/delete.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID < 1)
			return false;

		$lessonId = CCourse::CourseGetLinkedLesson($ID);
		if ($lessonId === false)
		{
			return false;
		}

		CLearnLesson::Delete($lessonId);

		return true;
	}


	static public function IsCertificatesExists($courseId)
	{
		// Check certificates (if exists => forbid removing course)
		$certificate = CCertification::GetList(Array(), Array("COURSE_ID" => $courseId, 'CHECK_PERMISSIONS' => 'N'));
		if ( ($certificate === false) || ($certificate->GetNext()) )
			return true;
		else
			return false;
	}


	// 2012-04-17 Checked/modified for compatibility with new data model
	
	/**
	* <p>Возвращает поля курса по его коду ID. Учитываются права доступа текущего пользователя.</p>
	*
	*
	* @param int $ID  Идентификатор курса.
	*
	* @return CDBResult <p>Возвращается объект <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p> </h
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $course = CCourse::GetByID($COURSE_ID);
	*     if ($arCourse = $course-&gt;GetNext())
	*     {
	*         echo $arCourse["NAME"];
	*     }
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#course">Поля курса</a></li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/index.php">CCourse</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/getlist.php">GetList</a> </li> </ul> <a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		return CCourse::GetList(Array(),Array("ID" => $ID));
	}


	// 2012-04-17 Checked/modified for compatibility with new data model
	
	/**
	* <p>Возвращает права доступа к учебному курсу с идентификатором COURSE_ID для всех групп пользователей.</p>
	*
	*
	* @param int $COURSE_ID  Идентификатор курса.
	*
	* @return array <p> Массив прав вида Array("ID группы"=&gt;"Право доступа"[, ...]). Право
	* доступа может принимать значение: "D" - запрещён, "R" - чтение, "W" -
	* изменение, "X" - полный доступ (изменение + право изменять права
	* доступа).</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $arPerm = CCourse::GetGroupPermissions($COURSE = 8);
	*     print_r($arPerm);
	* 
	*     //<
	*     The above example will output something similar to:
	* 
	*     Array
	*     (
	*         [2] =&gt; R
	*         [22] =&gt; W
	*     )
	*     >//
	* }
	* 
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/index.php">CCourse</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/getpermission.php">GetPermission</a> </li></ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/getgrouppermissions.php
	* @author Bitrix
	*/
	public static function GetGroupPermissions($COURSE_ID)
	{
		$linkedLessonId      = CCourse::CourseGetLinkedLesson($COURSE_ID);
		$arGroupPermissions  = CLearnAccess::GetSymbolsAccessibleToLesson ($linkedLessonId, CLearnAccess::OP_LESSON_READ);
		return ($arGroupPermissions);
	}


	// 2012-04-17 Checked/modified for compatibility with new data model
	
	/**
	* <p>Возвращает список сайтов для учебного курса с идентификатором COURSE_ID.</p>
	*
	*
	* @param int $COURSE_ID  Идентификатор курса.
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
	* 
	*     $ID = 106;//Course ID
	* 
	*     $arSite = Array();
	*     $db_SITE_ID = CCourse::GetSite($ID);
	*     while($ar_SITE_ID = $db_SITE_ID-&gt;Fetch())
	*         $arSite[] = $ar_SITE_ID["LID"];
	* 
	* }
	* 
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> </li></ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/getsite.php
	* @author Bitrix
	*/
	public static function GetSite($COURSE_ID)
	{
		global $DB;
		$strSql = "SELECT L.*, CS.* FROM b_learn_course_site CS, b_lang L WHERE L.LID=CS.SITE_ID AND CS.COURSE_ID=".intval($COURSE_ID);

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}


	public static function GetSiteId($COURSE_ID)
	{
		global $DB;
		$strSql = "SELECT SITE_ID FROM b_learn_course_site WHERE COURSE_ID=" . ((int) $COURSE_ID);

		$rc = $DB->Query($strSql, true);
		if ($rc === false)
			throw new LearnException ('EA_SQLERROR', LearnException::EXC_ERR_ALL_GIVEUP);

		$row = $rc->Fetch();
		if ( ! isset($row['SITE_ID']) )
			throw new LearnException ('EA_NOT_EXISTS', LearnException::EXC_ERR_ALL_NOT_EXISTS);

		return ($row['SITE_ID']);
	}


	public static function GetSitePathes($siteId, $in_type = 'U')
	{
		global $DB;

		$in_type = strtoupper($in_type);
		switch ($in_type)
		{
			case 'L':
			case 'C':
			case 'H':
			case 'U':
				$type = $DB->ForSql($in_type);
			break;

			default:
				throw new LearnException ('EA_PARAMS', LearnException::EXC_ERR_ALL_PARAMS);
			break;
		}

		$strSql = 
		"SELECT TSP.PATH 
		FROM b_learn_site_path TSP 
		WHERE TSP.SITE_ID='" . $DB->ForSql($siteId) . "' AND TSP.TYPE = '" . $type . "'";

		$rc = $DB->Query($strSql, true);
		if ($rc === false)
			throw new LearnException ('EA_SQLERROR', LearnException::EXC_ERR_ALL_GIVEUP);

		$arPathes = array();
		while ($row = $rc->Fetch())
			$arPathes[] = $row['PATH'];

		return ($arPathes);
	}


	// 2012-04-17 Checked/modified for compatibility with new data model
	public static function MkOperationFilter($key)
	{
		// refactored: body of function moved to CLearnHelper class
		return (CLearnHelper::MkOperationFilter($key));
	}


	// 2012-04-17 Checked/modified for compatibility with new data model
	public static function FilterCreate($fname, $vals, $type, &$bFullJoin, $cOperationType=false, $bSkipEmpty = true)
	{
		// refactored: body of function moved to CLearnHelper class
		return (CLearnHelper::FilterCreate($fname, $vals, $type, $bFullJoin, $cOperationType, $bSkipEmpty));
	}


	// 2012-04-18 Checked/modified for compatibility with new data model
	
	/**
	* <p>Возвращает список активных глав и уроков, отсортированный по возрастанию индекса сортировки.</p> <p> </p> <div class="note"> <b>Примечание</b> <p>Возвращаемый список содержит одноименные поля глав и уроков. Обязательные поля списка: ID - идентификатор урока или главы; NAME - название; CHAPTER_ID - идентификатор родительской главы; SORT - индекс сортировки; DEPTH_LEVEL - уровень вложенности; TYPE - тип ("LE" - урок, "CH" - глава). Для вывода остальных полей используйте массив <i>arAddSelectFileds</i>. Метод предназначен для вывода "дерева" курса. Если поля "DETAIL_TEXT", "DETAIL_TEXT_TYPE", "DETAIL_PICTURE" не используются - рекомендуется <i>arAddSelectFileds</i> оставлять пустым (arAddSelectFileds = Array()). </p> </div>
	*
	*
	* @param int $COURSE_ID  Идентификатор курса.
	*
	* @param array $arAddSelectFileds = Array("DETAIL_TEXT" Массив дополнительных полей. Допустимые поля:<br><i>PREVIEW_TEXT</i> -
	* Предварительное описание (анонс);<br><i>PREVIEW_TEXT_TYPE</i> - Тип
	* предварительного описания (text/html);<br><i>PREVIEW_PICTURE</i> - Код картинки в
	* таблице файлов для предварительного просмотра
	* (анонса);<br><i>DETAIL_TEXT_TYPE</i> - Тип детального описания
	* (text/html);<br><i>DETAIL_PICTURE</i> - Код картинки в таблице файлов для
	* детального просмотра;<br><i>DETAIL_TEXT</i> - Детальное описание;<br> По
	* умолчанию массив arAddSelectFileds = Array("DETAIL_TEXT", "DETAIL_TEXT_TYPE", "DETAIL_PICTURE");
	*
	* @param DETAIL_TEXT_TYP $E  
	*
	* @param DETAIL_PICTUR $E  
	*
	* @return CDBResult <p>Возвращается объект <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.</p> </h
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $res = CCourse::GetCourseContent($COURSE_ID = 105, Array());
	* 
	*     while ($arContent = $res-&gt;GetNext())
	*     {
	*         echo str_repeat("&amp;nbsp;&amp;nbsp;&amp;nbsp;&amp;nbsp;", $arContent["DEPTH_LEVEL"]);
	*         echo ($arContent["TYPE"]=="CH" ? "+": "-").$arContent["NAME"]."&lt;br&gt;";
	*     }
	* 
	*     //<
	*     The above example will output something similar to:
	* 
	*     +Chapter 1
	*       +Chapter 1.1
	*         -Lesson 1.1.1
	*       +Chapter 1.2
	*     +Chapter 2
	*       -Lesson 2
	*     +Chapter 3
	*       +Chapter 3.1
	*         -Lesson 3.1.1
	*         -Lesson 3.1.2
	*         +Chapter 3.1.1
	* 
	*     >//
	* 
	* }
	* 
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#lesson">Поля урока</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/learning/fields.php#chapter">Поля главы</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a></li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/clesson/index.php">CLesson</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/clesson/getlist.php">GetList</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/cchapter/index.php">CChapter</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/cchapter/getlist.php">GetList</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/cchapter/index.php">CChapter</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/cchapter/gettreelist.php">GetTreeList</a> </li> </ul> <a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/getcoursecontent.php
	* @author Bitrix
	*/
	public static function GetCourseContent(
		$COURSE_ID, 
		$arAddSelectFileds = array("DETAIL_TEXT", "DETAIL_TEXT_TYPE", "DETAIL_PICTURE"), 
		$arSelectFields = array()
	)
	{
		global $DB;

		$COURSE_ID = intval($COURSE_ID);

		$CACHE_ID = ((string) $COURSE_ID) . sha1(serialize($arSelectFields));

		if ( ! (
			array_key_exists($CACHE_ID, $GLOBALS["LEARNING_CACHE_COURSE"]) 
			&& is_array($GLOBALS["LEARNING_CACHE_COURSE"][$CACHE_ID])
			)
		)
		{
			$oTree = CLearnLesson::GetTree(
				CCourse::CourseGetLinkedLesson($COURSE_ID),
				array(
					'EDGE_SORT' => 'asc'
					),
				array(
					'ACTIVE'            => 'Y',
					'CHECK_PERMISSIONS' => 'N'
					),
				true,		// $publishProhibitionMode,
				$arSelectFields
				);

			$arTree = $oTree->GetTreeAsListOldMode();

			$GLOBALS["LEARNING_CACHE_COURSE"][$CACHE_ID] = $arTree;
		}

		$r = new CDBResult();
		$r->InitFromArray($GLOBALS["LEARNING_CACHE_COURSE"][$CACHE_ID]);
		return $r;
	}


	// Handlers:

	// 2012-04-17 Checked/modified for compatibility with new data model
	public static function OnGroupDelete($GROUP_ID)
	{
		global $DB;

		$rc = $DB->Query("DELETE FROM b_learn_rights WHERE SUBJECT_ID='G" . (int) $GROUP_ID . "'", true)
			&& $DB->Query("DELETE FROM b_learn_rights_all WHERE SUBJECT_ID='G" . (int) $GROUP_ID . "'", true);

		CLearnCacheOfLessonTreeComponent::MarkAsDirty();

		return ($rc);
	}


	// 2012-04-17 Checked/modified for compatibility with new data model
	public static function OnBeforeLangDelete($lang)
	{
		global $APPLICATION;
		$r = CCourse::GetList(array(), array("SITE_ID"=>$lang));

		$bAllowDelete = true;

		// Is any data exists for this site?
		if ($r->Fetch())
			$bAllowDelete = false;

		if ( ! $bAllowDelete )
			$APPLICATION->ThrowException(GetMessage('LEARNING_PREVENT_LANG_REMOVE'));

		return ($bAllowDelete);
	}


	// 2012-04-17 Checked/modified for compatibility with new data model
	public static function OnUserDelete($user_id)
	{
		return CStudent::Delete($user_id);
	}


	// 2012-04-17 Checked/modified for compatibility with new data model
	
	/**
	* <p>Возвращает количество дней, часов, минут и секунд в виде строки, содержащихся в <i>seconds</i>.</p>
	*
	*
	* @param int $seconds  Количество секунд. </ht
	*
	* @return string <p>Метод возвращает строку вида "DDдн. HHч. MMмин. SSсек.".</p> <a
	* name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* 
	* if (CModule::IncludeModule("learning"))
	* {
	*     $seconds = 56789;
	*     $time = CCourse::TimeToStr($seconds);
	*     echo $time; //print 15 ч. 46 мин. 29 сек.
	* }
	* 
	* ?&gt;
	* </bod
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/timetostr.php
	* @author Bitrix
	*/
	public static function TimeToStr($seconds)
	{
		$str = "";

		$seconds = intval($seconds);
		if ($seconds <= 0)
			return $str;

		$days = intval($seconds/86400);
		if ($days>0)
		{
			$str .= $days."&nbsp;".GetMessage("LEARNING_DAYS")." ";
			$seconds = $seconds - $days*86400;
		}

		$hours = intval($seconds/3600);
		if ($hours>0)
		{
			$str .= $hours."&nbsp;".GetMessage("LEARNING_HOURS")." ";
			$seconds = $seconds - $hours*3600;
		}

		$minutes = intval($seconds/60);
		if ($minutes>0)
		{
			$str .= $minutes."&nbsp;".GetMessage("LEARNING_MINUTES")." ";
			$seconds = $seconds - $minutes*60;
		}

		$str .= ($seconds%60)."&nbsp;".GetMessage("LEARNING_SECONDS");

		return $str;
	}


	// provided compatibility to new data model at 04.05.2012
	public static function OnSearchReindex($NS = array(), $oCallback = null, $callback_method = '')
	{
		global $DB;
		static $arCourseToSiteCache = array();

		$arResult         = array();
		$arAllSitesPathes = array();
		$elementStartId   = 0;
		$indexElementType = 'C';	// start reindex from courses
		$by = $order = '';

		$sites = CLang::GetList($by, $order, Array('TYPE' => 'C'));
		while($site = $sites->Fetch())
		{
			$arAllSitesPathes[$site['LID']] = array(
				'C' => CCourse::GetSitePathes($site['LID'], 'C'),
				'H' => CCourse::GetSitePathes($site['LID'], 'H'),
				'L' => CCourse::GetSitePathes($site['LID'], 'L')
			);
		}

		$arCoursesFilter = array();
		$arLessonsFilter = array('LINKED_LESSON_ID' => '');
		if ($NS['MODULE'] === 'learning' && (strlen($NS['ID']) > 0))
		{
			$indexElementType = substr($NS['ID'], 0, 1);
			$elementStartId   = (int) substr($NS['ID'], 1);

			if (strlen($NS['SITE_ID']) > 0)
				$arCoursesFilter['SITE_ID'] = $NS['SITE_ID'];
		}

		$arCoursesFilter['>ID'] = $elementStartId;

		if ($indexElementType === 'C')
		{
			$rsCourse = CCourse::GetList(
				array('ID' => 'ASC'), 
				$arCoursesFilter
			);

			while ($arCourse = $rsCourse->Fetch())
			{
				try
				{
					$arCourse["SITE_ID"] = CCourse::GetSiteId($arCourse['ID']);
					$arPathes            = $arAllSitesPathes[$arCourse['SITE_ID']]['C'];
					$linkedLessonId      = CCourse::CourseGetLinkedLesson($arCourse['ID']);
					if ($linkedLessonId === false)
					{
						continue;
					}
					$arGroupPermissions  = CLearnAccess::GetSymbolsAccessibleToLesson ($linkedLessonId, CLearnAccess::OP_LESSON_READ);
				}
				catch (LearnException $e)
				{
					continue;	// skip indexation of this item
				}

				$arSiteIds = array();
				foreach ($arPathes as $k => $path)
				{
					$arCourse["PATH"] = $path;
					$Url = str_replace("#COURSE_ID#", $arCourse["ID"], $arCourse["PATH"]);
					$arSiteIds[$arCourse['SITE_ID']] = $Url;
				}

				if ($arCourse["DETAIL_TEXT_TYPE"] !== 'text')
					$detailText = CSearch::KillTags($arCourse['DETAIL_TEXT']);
				else
					$detailText = strip_tags($arCourse['DETAIL_TEXT']);

				if (strlen($detailText) > 0)
					$dataBody = $detailText;
				else
					$dataBody = $arCourse['NAME'];

				$Result = array(
					"ID"            => "C" . $arCourse["ID"],
					"LAST_MODIFIED"	=> $arCourse["TIMESTAMP_X"],
					"TITLE"         => $arCourse["NAME"],
					"BODY"          => $dataBody,
					"SITE_ID"       => $arSiteIds,
					"PERMISSIONS"   => $arGroupPermissions,
					"COURSE_ID"     => "C" . $arCourse["ID"]
				);

				if ($oCallback)
				{
					$res = call_user_func(array($oCallback, $callback_method), $Result);
					if ( ! $res )
						return ("C" . $arCourse["ID"]);
				}
				else
					$arResult[] = $Result;
			}

			// Reindex of courses finished. Let's reindex lessons now.
			$indexElementType = 'U';
			$elementStartId   = 0;
		}

		$arLessonsFilter['>LESSON_ID'] = $elementStartId;
		if ($indexElementType === 'U')
		{
			$rsLessons = CLearnLesson::GetList(
				array('LESSON_ID' => 'ASC'),
				$arLessonsFilter
			);

			while ($arLessonFromDb = $rsLessons->Fetch())
			{
				$arLessonsWithCourse = array();	// list of lessons in context of some course

				$arOParentPathes = CLearnLesson::GetListOfParentPathes($arLessonFromDb['LESSON_ID']);

				foreach ($arOParentPathes as $oParentPath)
				{
					$arParentLessons = $oParentPath->GetPathAsArray();

					foreach ($arParentLessons as $lessonId)
					{
						$linkedCourseId = CLearnLesson::GetLinkedCourse($lessonId);
						if (($linkedCourseId !== false) && ($linkedCourseId > 0))
							$arLessonsWithCourse[] = array_merge($arLessonFromDb, array('PARENT_COURSE_ID' => $linkedCourseId));
					}

				}

				foreach ($arLessonsWithCourse as $arLesson)
				{
					try
					{
						$arGroupPermissions = CLearnAccess::GetSymbolsAccessibleToLesson ($arLesson['LESSON_ID'], CLearnAccess::OP_LESSON_READ);

						$courseId = $arLesson['PARENT_COURSE_ID'];

						if ( ! isset($arCourseToSiteCache[$courseId]) )
						{
							$strSql = "SELECT SITE_ID FROM b_learn_course_site WHERE COURSE_ID=" . (int) $courseId;
							$rc = $DB->Query($strSql, true);

							if ($rc === false)
								continue;

							$arCourseToSiteCache[$courseId] = array();
							while ($arCourseSite = $rc->fetch())
								$arCourseToSiteCache[$courseId][] = $arCourseSite['SITE_ID'];
						}

						$arAllowedSites = $arCourseToSiteCache[$courseId];

						if (empty($arAllowedSites))
							continue;

						$arSiteIds = array();
						$lessonType = 'L';
						if ($arLesson['IS_CHILDS'])
							$lessonType = 'H';

						foreach ($arAllSitesPathes as $siteId => $arSitePathesByLessonType)
						{
							if ( ! in_array($siteId, $arAllowedSites, true) )
								continue;

							foreach ($arSitePathesByLessonType as $someLessonType => $arPathes)
							{
								// skip wrong types of lessons
								if ($lessonType !== $someLessonType)
									continue;

								foreach ($arPathes as $k => $path)
								{
									if ($lessonType == 'H')
										$Url = str_replace("#CHAPTER_ID#", '0' . $arLesson['LESSON_ID'], $path);
									else
										$Url = str_replace("#LESSON_ID#", $arLesson['LESSON_ID'], $path);

									$Url = str_replace("#COURSE_ID#", $arLesson['PARENT_COURSE_ID'], $Url);
									$arSiteIds[$siteId] = $Url;
								}
							}
						}
					}
					catch (LearnException $e)
					{
						continue;	// skip indexation of this item
					}

					if ($arLesson["DETAIL_TEXT_TYPE"] !== 'text')
						$detailText = CSearch::KillTags($arLesson['DETAIL_TEXT']);
					else
						$detailText = strip_tags($arLesson['DETAIL_TEXT']);

					if (strlen($detailText) > 0)
						$dataBody = $detailText;
					else
						$dataBody = $arLesson['NAME'];

					$Result = array(
						"ID"            => 'U' . $arLesson['LESSON_ID'],
						"LAST_MODIFIED"	=> $arLesson['TIMESTAMP_X'],
						"TITLE"         => $arLesson['NAME'],
						"BODY"          => $dataBody,
						"SITE_ID"       => $arSiteIds,
						"PERMISSIONS"   => $arGroupPermissions
					);

					if ($oCallback)
					{
						$res = call_user_func(array($oCallback, $callback_method), $Result);
						if ( ! $res )
							return ('U' . $arLesson['LESSON_ID']);
					}
					else
						$arResult[] = $Result;
				}
			}
		}

		if ($oCallback)
			$rc = false;
		else
			$rc = $arResult;

		return $rc;
	}


	public static function _Upper($str)
	{
		return $str;
	}


	// Functions below are for temporary backward compatibility, don't relay on it!

	/**
	 * Stupid stub
	 * 
	 * @deprecated this code can be removed at any time without any notice
	 */
	
	/**
	* <p>Метод устанавливает права доступа <i>arPERMISSIONS</i> для учебного курса с идентификатором <i>COURSE_ID</i>.</p>
	*
	*
	* @param int $COURSE_ID  Идентификатор курса.
	*
	* @param array $arPERMISSIONS  массив вида Array("код группы"=&gt;"право доступа", ....),<br>где <i>право
	* доступа</i>: <ul> <li>D - доступ запрещён;</li> <li>R - чтение;</li> <li>W -
	* запись;</li> <li>X - полный доступ (запись + назначение прав доступа на
	* данный курс).</li> </ul>
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* CCourse::SetPermission($COURSE_ID, Array("2"=&gt;"R", "3"=&gt;"W"));
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/index.php">CCourse</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/getpermission.php">GetPermission</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/index.php">CCourse</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/update.php">Update()</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/setpermission.php
	* @author Bitrix
	* @deprecated this code can be removed at any time without any notice
	*/
	public static function SetPermission ($param1, $param2)
	{
		return;
	}


	/**
	 * Simple proxy
	 * 
	 * @deprecated this code can be removed at any time without any notice
	 * @return character 'D', 'R', 'W' or 'X'
	 */
	
	/**
	* <p>Возвращает право доступа к учебному курсу с идентификатором <i>courseId</i> для текущего пользователя.</p>
	*
	*
	* @param int $courseId  Идентификатор курса. <br><br> До версии 12.0.0 параметр назывался COURSE_ID.
	*
	* @return string <p>Символ права доступа: "D" - запрещён, "R" - чтение, "W" - изменение, "X" -
	* полный доступ (изменение + право изменять права доступа). </p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $permission = CCourse::GetPermission($id);
	* if ($permission&lt;"X")
	*     return false;
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/index.php">CCourse</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/setpermission.php">SetPermission</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/index.php">CCourse</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/getgrouppermissions.php">GetGroupPermissions</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/ccourse/getpermission.php
	* @author Bitrix
	* @deprecated this code can be removed at any time without any notice
	*/
	public static function GetPermission ($courseId)
	{
		global $USER;
		static $accessMatrix = false;

		$courseId = (int) $courseId;

		if ( ! ($courseId > 0) )
			return ('D');		// access denied

		$linkedLessonId = CCourse::CourseGetLinkedLesson($courseId);

		if ( ! ($linkedLessonId > 0) )
			return ('D');		// some troubles, access denied

		$oAccess = CLearnAccess::GetInstance($USER->GetID());

		if ($accessMatrix === false)
		{
			$accessMatrix = array(
				// full access
				'X' => CLearnAccess::OP_LESSON_READ 
					| CLearnAccess::OP_LESSON_CREATE 
					| CLearnAccess::OP_LESSON_WRITE 
					| CLearnAccess::OP_LESSON_REMOVE 
					| CLearnAccess::OP_LESSON_LINK_TO_PARENTS 
					| CLearnAccess::OP_LESSON_UNLINK_FROM_PARENTS 
					| CLearnAccess::OP_LESSON_LINK_DESCENDANTS 
					| CLearnAccess::OP_LESSON_UNLINK_DESCENDANTS 
					| CLearnAccess::OP_LESSON_MANAGE_RIGHTS,

				// write access
				'W' => CLearnAccess::OP_LESSON_READ 
					| CLearnAccess::OP_LESSON_CREATE 
					| CLearnAccess::OP_LESSON_WRITE 
					| CLearnAccess::OP_LESSON_REMOVE,

				// read-only access
				'R' => CLearnAccess::OP_LESSON_READ
			);
		}

		foreach ($accessMatrix as $oldAccessSymbol => $operations)
		{
			if ($oAccess->IsBaseAccess($operations)
				|| $oAccess->IsLessonAccessible($linkedLessonId, $operations)
			)
			{
				return ($oldAccessSymbol);
			}
		}

		// by default, access denied
		return ('D');
	}
}
