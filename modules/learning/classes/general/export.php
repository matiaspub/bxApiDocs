<?
// 2012-04-18 Checked/modified for compatibility with new data model

/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/learning/classes/ccoursepackage/index.php
 * @author Bitrix
 */
class CCoursePackage
{

	var $ID;
	var $charset;
	var $LAST_ERROR = "";
	var $arCourse = Array();
	var $arSite = Array();
	var $arItems = Array();
	var $arResources = Array();
	var $RefID = 1;
	var $strItems = "";
	var $strResourses = "";
	var $arDraftFields = Array("detail_text", "preview_text", "description");
	var $arPicture = Array("detail_picture", "preview_picture", "file_id");
	var $arDate = Array("active_from", "active_to", "timestamp_x", "date_create");
	private $replacingResId;

	// 2012-04-18 Checked/modified for compatibility with new data model
	
	/**
	* <p>Конструктор класса CCoursePackage инициализирует экспортируемый курс.</p>
	*
	*
	* @param int $COURSE_ID  Идентификатор курса.
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $COURSE_ID = 97;
	* 
	*     if (CCourse::GetPermission($COURSE_ID) &gt;= 'W')
	*     {
	*         @set_time_limit(0);
	*         $package = new CCoursePackage($COURSE_ID);
	* 
	*         if (strlen($package-&gt;LAST_ERROR) &gt; 0)
	*         {
	*             echo "Error: ".$package-&gt;LAST_ERROR;
	*         }
	*         else
	*         {
	*             $success = $package-&gt;CreatePackage($PACKAGE_DIR = "/upload/mypackage/");
	* 
	*             if (!$success)
	*                 echo "Error: ".$package-&gt;LAST_ERROR;
	*             else
	*                 echo "Ok!";
	*         }
	* 
	*     }
	* }
	* 
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccoursepackage/index.php">CCoursePackage</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccoursepackage/createpackage.php">CreatePackage</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccoursepackage/index.php">CCoursePackage</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccoursepackage/createmanifest.php">CreateManifest</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/ccoursepackage/ccoursepackage.php
	* @author Bitrix
	*/
	public function CCoursePackage($COURSE_ID)
	{
		global $DB;
		$this->ID = intval($COURSE_ID);

		//Course exists?
		$res = CCourse::GetByID($this->ID);
		if (!$this->arCourse = $res->Fetch())
		{
			$this->LAST_ERROR = GetMessage("LEARNING_BAD_COURSE_ID_EX");
			return false;
		}

		$res = CCourse::GetSite($this->ID);
		if ($arSite = $res->GetNext())
		{
			$charset = $arSite["CHARSET"];
		}
		else
		{
			$this->LAST_ERROR = GetMessage("LEARNING_BAD_SITE_ID_EX");
			return false;
		}

		//Define charset
		if (strlen($charset) <= 0)
		{
			if (defined("SITE_CHARSET") && strlen(SITE_CHARSET) > 0)
				$charset = SITE_CHARSET;
			else
				$charset = "windows-1251";
		}
		$this->charset = $charset;

		//Get chapters, lessons, questions
		$this->_GetCourseContent($this->arCourse['LESSON_ID']);

		//Get tests
		$strSql =
			"SELECT T.*, ".
			$DB->DateToCharFunction("T.TIMESTAMP_X")." as TIMESTAMP_X ".
			"FROM b_learn_test T ".
			"WHERE T.COURSE_ID = ".intval($this->ID)." ".
			"ORDER BY SORT ASC ";

		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while ($arRes= $res->Fetch())
		{
			$r = ++$this->RefID;
			$this->arItems[$r] = $this->_CreateContent("TES", $arRes, $r);
			$this->strItems .= '<item identifier="TES'.$r.'" identifierref="RES'.$r.'"><title>'.htmlspecialcharsbx($arRes["NAME"]).'</title>';

			$marksRes = $DB->Query(
				"SELECT * FROM b_learn_test_mark WHERE TEST_ID = '" . (string) ((int) $arRes['ID']) . "'", 
				false, "File: ".__FILE__."<br>Line: ".__LINE__
				);
			while ($arMarksRes= $marksRes->Fetch())
			{
				$r = ++$this->RefID;
				$this->arItems[$r] = $this->CreateTMK($arMarksRes, $r);
				$this->strItems .= '<item identifier="TMK'.$r.'" identifierref="RES'.$r.'">'
					. '<title>' . htmlspecialcharsbx($arMarksRes['MARK'] . ' (' . $arMarksRes['DESCRIPTION'] . ')') . '</title>'
					. '</item>';
			}

			$this->strItems .= '</item>';
			$this->strResourses  .= '<resource identifier="RES'.$r.'" type="webcontent" href="res'.$r.'.xml">'.$this->_GetResourceFiles($r).'</resource>';
		}
	}


	// 2012-04-18 Checked/modified for compatibility with new data model
	
	/**
	* <p>Создает архив с учебным курсом.</p>
	*
	*
	* @param int $PACKAGE_DIR  Путь (относительно корня сайта) к директории, куда будет
	* скопирован архив курса.
	*
	* @return bool <p>Метод возвращает <i>true</i>, если создание архива прошло успешно.
	* При возникновении ошибки метод вернет false, а в свойстве объекта
	* LAST_ERROR будет содержаться текст ошибки.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $COURSE_ID = 97;
	* 
	*     if (CCourse::GetPermission($COURSE_ID) &gt;= 'W')
	*     {
	*         @set_time_limit(0);
	*         $package = new CCoursePackage($COURSE_ID);
	* 
	*         if (strlen($package-&gt;LAST_ERROR) &gt; 0)
	*         {
	*             echo "Error: ".$package-&gt;LAST_ERROR;
	*         }
	*         else
	*         {
	*             $success = $package-&gt;CreatePackage($PACKAGE_DIR = "/upload/mypackage/");
	* 
	*             if (!$success)
	*                 echo "Error: ".$package-&gt;LAST_ERROR;
	*             else
	*                 echo "Ok!";
	*         }
	* 
	*     }
	* }
	* 
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccoursepackage/index.php">CCoursePackage</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccoursepackage/ccoursepackage.php">CCoursePackage</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccoursepackage/index.php">CCoursePackage</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccoursepackage/createmanifest.php">CreateManifest</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/ccoursepackage/createpackage.php
	* @author Bitrix
	*/
	public function CreatePackage($PACKAGE_DIR)
	{
		if (strlen($this->LAST_ERROR)>0)
			return false;

		//Add last slash
		if (substr($PACKAGE_DIR,-1, 1) != "/")
			$PACKAGE_DIR .= "/";

		$path = $_SERVER["DOCUMENT_ROOT"].$PACKAGE_DIR;

		CheckDirPath($path);

		if (!is_dir($path) || !is_writable($path))
		{
			$this->LAST_ERROR = GetMessage("LEARNING_BAD_PACKAGE");
			return false;
		}

		RewriteFile($path."/res1.xml", $this->_CreateCourseToc());
		RewriteFile($path."/imsmanifest.xml", $this->CreateManifest());

		//XML Resource Data
		foreach ($this->arItems as $res_id => $content)
		{
			RewriteFile($path."/res".$res_id.".xml", $content);
		}

		//Resource
		$dbres_path = $path."/dbresources/";
		CheckDirPath($dbres_path);

		foreach ($this->arResources as $res_id => $arFiles)
		{
			$res_path = $path."/resources/res".$res_id."/";

			CheckDirPath($res_path);
			foreach ($arFiles as $arFile)
			{
				if (array_key_exists("DB", $arFile))
				{
					$arTempFile = CFile::MakeFileArray($arFile["DB"]);
					if($arTempFile && isset($arTempFile["tmp_name"]))
						@copy($arTempFile["tmp_name"], $dbres_path.$arFile["ID"]);
				}
				else
				{
					@copy($_SERVER["DOCUMENT_ROOT"].$arFile["SRC"], $res_path.$arFile["ID"]);
				}
			}
		}

		return true;
	}


	// 2012-04-18 Checked/modified for compatibility with new data model
	
	/**
	* <p>Возвращает манифест (в xml-формате) архива учебного курса.</p>
	*
	*
	* @return string <p>Метод возвращает строку - содержимое файла imsmanifest.xml архива
	* учебного курса.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     $COURSE_ID = 97;
	* 
	*     if (CCourse::GetPermission($COURSE_ID) &gt;= 'W')
	*     {
	*         @set_time_limit(0);
	*         $package = new CCoursePackage($COURSE_ID);
	* 
	*         if (strlen($package-&gt;LAST_ERROR) &gt; 0)
	*         {
	*             echo "Error: ".$package-&gt;LAST_ERROR;
	*         }
	*         else
	*         {
	*             echo htmlspecialchars($package-&gt;CreateManifest());
	*         }
	* 
	*     }
	* }
	* 
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccoursepackage/index.php">CCoursePackage</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccoursepackage/ccoursepackage.php">CCoursePackage</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccoursepackage/index.php">CCoursePackage</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccoursepackage/createpackage.php">CreatePackage</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/ccoursepackage/createmanifest.php
	* @author Bitrix
	*/
	public function CreateManifest()
	{
		global $DB;

		if (strlen($this->LAST_ERROR)>0)
			return false;

		$this->createQuestionItems($this->arCourse["LESSON_ID"]);

		$strManifest = "<"."?xml version=\"1.0\" encoding=\"".$this->charset."\"?".">\n";
		$strManifest .= '<manifest xmlns="http://www.imsproject.org/xsd/imscp_rootv1p1p2" identifier="toc1">';
		//<Organization>
		$strManifest .= '<organizations default="man1"><organization identifier="man1" structure="hierarchical">';
		$strManifest .= '<item identifier="COR1" identifierref="RES1" parameters=""><title>'.htmlspecialcharsbx($this->arCourse["NAME"]).'</title>';
		$strManifest .= $this->strItems;
		$strManifest .= '</item>';
		$strManifest .= '</organization></organizations>';
		//<Resource>
		$strManifest .= '<resources><resource identifier="RES1" type="webcontent" href="res1.xml">'.$this->_GetResourceFiles(1).'</resource>';
		$strManifest .= $this->strResourses;
		$strManifest .= '</resources>';
		$strManifest .= '</manifest>';

		return $strManifest;
	}

	// 2012-04-18 Checked/modified for compatibility with new data model
	public function _GetCourseContent($parentLessonId, $DEPTH_LEVEL = 1)
	{
		global $DB;

		$res = CLearnLesson::GetListOfImmediateChilds (
			$parentLessonId, 
			array(	 	// order
				'EDGE_SORT' => 'asc'
				)
			);

		while ($arRes= $res->Fetch())
		{
			$arRes['ID'] = $arRes['LESSON_ID'];		// for compatibility

			if ($arRes['IS_CHILDS'] == '1')
				$itemType = 'CHA';
			else
				$itemType = 'LES';

			if (CLearnLesson::IsPublishProhibited($arRes['LESSON_ID'], $parentLessonId))
				$arRes['META_PUBLISH_PROHIBITED'] = 'Y';
			else
				$arRes['META_PUBLISH_PROHIBITED'] = 'N';

			$r = ++$this->RefID;
			$this->arItems[$r] = $this->_CreateContent($itemType, $arRes, $r);
			$this->strItems .= '<item identifier="' . $itemType . $r . '" identifierref="RES'.$r.'"><title>'.htmlspecialcharsbx($arRes["NAME"]).'</title>';
			$this->strResourses  .= '<resource identifier="RES'.$r.'" type="webcontent" href="res'.$r.'.xml">'.$this->_GetResourceFiles($r).'</resource>';

			$this->createQuestionItems($arRes["ID"]);

			// Load content recursively for chapters
			if ($arRes['IS_CHILDS'] == '1')
				$this->_GetCourseContent($arRes["ID"], $DEPTH_LEVEL+1);

			$this->strItems .= "</item>";
		}
	}


	// 2012-04-18 Checked/modified for compatibility with new data model
	public function _CreateCourseToc()
	{
		$str = "<"."?xml version=\"1.0\" encoding=\"".$this->charset."\"?".">\n";
		$str .= "<coursetoc>";

		foreach ($this->arCourse as $key => $val)
		{
			$strDelayed = '';
			$key = strtolower($key);

			if ($key === 'site_id')
				continue;

			$str .= "<".$key.">";
			if (in_array($key, $this->arDraftFields) && strlen($val) > 0)
			{
				$str .= "<![CDATA[".$this->_ReplaceImages($val, 1)."]]>";
			}
			elseif (in_array($key, $this->arDate) && strlen($val) > 0)
			{
				$str .= MakeTimeStamp($val);
			}
			elseif (in_array($key, $this->arPicture) && strlen($val) > 0)
			{
				$src = CFile::GetPath($val);
				$ext = GetFileExtension($src);
				$this->arResources[1][] = Array("DB" => $val, "SRC"=>$src, "ID"=>$val.".".$ext);
				$str .= $val.".".$ext;

				$rs = CFile::GetByID($val);
				if ($arFileData = $rs->Fetch())
				{
					$strDelayed = '<' . $key . '_description' . '>'
						. htmlspecialcharsbx($arFileData['DESCRIPTION'])
						. '</' . $key . '_description' . '>';
				}
			}
			else
			{
				$str .= htmlspecialcharsbx($val);
			}
			$str .= "</".$key.">\n";
			$str .= $strDelayed;
		}

		$str .= "</coursetoc>";

		return $str;
	}

	// 2012-04-18 Checked/modified for compatibility with new data model
	public function _GetResourceFiles($res_id)
	{
		$str = "";

		if (is_set($this->arResources,$res_id))
			foreach ($this->arResources[$res_id] as $arFile)
			if (is_set($arFile,"DB"))
				$str .= '<file href="dbresources/'.$arFile["ID"].'" />';
			else
				$str .= '<file href="resources/res'.$res_id.'/'.$arFile["ID"].'" />';
		return $str;
	}

	// 2012-04-18 Checked/modified for compatibility with new data model
	public function _CreateContent($TYPE, $arParams, $res_id)
	{
		$str = "<"."?xml version=\"1.0\" encoding=\"".$this->charset."\"?".">\n";
		$str .= '<content type="'.$TYPE.'">';

		foreach ($arParams as $key => $val)
		{
			$strDelayed = '';

			$key = strtolower($key);

			if ($key === 'site_id')
				continue;

			$str .= "<".$key.">";
			if (in_array($key, $this->arDraftFields) && strlen($val) > 0)
			{
				$str .= "<![CDATA[".$this->_ReplaceImages($val, $res_id)."]]>";
			}
			elseif (in_array($key, $this->arPicture) && strlen($val) > 0)
			{
				$src = CFile::GetPath($val);
				$ext = GetFileExtension($src);
				$this->arResources[$res_id][] = Array("DB" => $val, "SRC"=>$src,  "ID"=>$val.".".$ext);
				$str .= $val.".".$ext;

				$rs = CFile::GetByID($val);
				if ($arFileData = $rs->Fetch())
				{
					$strDelayed = '<' . $key . '_description' . '>'
						. htmlspecialcharsbx($arFileData['DESCRIPTION'])
						. '</' . $key . '_description' . '>';
				}
			}
			elseif (in_array($key, $this->arDate) && strlen($val) > 0)
			{
				$str .= MakeTimeStamp($val);
			}
			else
			{
				$str .= htmlspecialcharsbx($val);
			}
			$str .= "</".$key.">\n";

			$str .= $strDelayed;
		}

		$str .= "</content>";
		return $str;
	}

	// 2012-04-18 Checked/modified for compatibility with new data model
	public function _replace_img($matches)
	{
		$src = $matches[3];
		if($src <> "" && is_file($_SERVER["DOCUMENT_ROOT"].$src))
		{
			$dest = basename($src);
			$uid = RandString(5);

			$res_id = 1;
			$this->arResources[$this->replacingResId][] = array("SRC"=>$src, "ID"=> $uid.".".$dest);
			return stripslashes($matches[1].$matches[2]."cid:resources/res".$this->replacingResId."/".$uid.".".$dest.$matches[4].$matches[5]);
		}
		return stripslashes($matches[0]);
	}

	// 2012-04-18 Checked/modified for compatibility with new data model
	public function _ReplaceImages($text, $res_id)
	{
		$this->replacingResId = $res_id;
		return preg_replace_callback("/(<.+?src\\s*=\\s*)([\"']?)(.*?)(\\2)(.*?>)/is", array($this, "_replace_img"), $text);
	}

	private function createQuestionItems($lessonId)
	{
		global $DB;

		$strSql = "SELECT * FROM b_learn_question WHERE LESSON_ID=".$lessonId." ORDER BY SORT ASC ";
		$q = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while ($arQRes = $q->Fetch())
		{
			$r = ++$this->RefID;
			$this->arItems[$r] = $this->CreateQTI($arQRes, $r);
			$this->strItems .= '<item identifier="QUE'.$r.'" identifierref="RES'.$r.'"><title>'.htmlspecialcharsbx($arQRes["NAME"]).'</title></item>';
			$this->strResourses  .= '<resource identifier="RES'.$r.'" type="imsqti_xmlv1p1" href="res'.$r.'.xml">'.$this->_GetResourceFiles($r).'</resource>';
		}
	}

	// 2012-04-18 Checked/modified for compatibility with new data model
	public function CreateQTI($arParams, $res_id = 1)
	{
		global $DB;

		if (strlen($this->LAST_ERROR)>0)
			return false;

		$str = "<"."?xml version=\"1.0\" encoding=\"".$this->charset."\"?".">\n";
		$str .= "<questestinterop>";

		$str .= '<item ident="QUE'.$res_id.'">';
		$str .= '<presentation><material><mattext>'.htmlspecialcharsbx($arParams["NAME"]).'</mattext>';

		if (intval($arParams["FILE_ID"]) > 0)
		{
			$arFile = CFile::GetFileArray($arParams["FILE_ID"]);
			if ($arFile)
			{
				$name = $arFile["ID"].'.'.GetFileExtension($arFile["FILE_NAME"]);
				$this->arResources[$res_id][] = Array("DB" => $arFile["ID"], "SRC"=>$arFile["SRC"],  "ID"=>$name);

				$str .= '<matimage imagtype="'.$arFile["CONTENT_TYPE"].'" width="'.$arFile["WIDTH"].'" height="'.$arFile["HEIGHT"].'" uri="dbresources/'.$name.'"></matimage>';
				$str .= '<image_description>' . htmlspecialcharsbx($arFile['DESCRIPTION']) . '</image_description>';
			}
		}

		$str .= "</material>";
		switch ($arParams["QUESTION_TYPE"])
		{
			case "M":
				$qType = 'Multiple';
				break;
			case "T":
				$qType = 'Text';
				break;
			case "R":
				$qType = 'Sort';
				break;
			default:
				$qType = 'Single';
				break;
		}
		$str .= '<response_lid ident="LID'.$res_id.'" rcardinality="'.$qType.'"><render_choice>';

		$strSql =
		"SELECT * FROM b_learn_answer WHERE QUESTION_ID = '".intval($arParams["ID"])."' ORDER BY SORT ASC ";
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);


		$cond = "";
		while ($arAnwer = $res->Fetch())
		{
			if ($arAnwer["CORRECT"] == "Y")
				$cond .= '<varequal respident="LID'.$res_id.'">ANS'.$arAnwer["ID"].'</varequal>';
			$str .= '<response_label ident="ANS'.$arAnwer["ID"].'"><material><mattext>'.htmlspecialcharsbx($arAnwer["ANSWER"]).'</mattext></material></response_label>';
		}


		$str .= "</render_choice></response_lid></presentation>";

		$str .= "<resprocessing>";
		$str .= "<respcondition><conditionvar>".$cond."</conditionvar><setvar>".$arParams["POINT"]."</setvar></respcondition>";
		$str .= "</resprocessing>";

		$str .= "<bitrix>";
		$str .= "<description>";
		if (strlen($arParams["DESCRIPTION"])>0)
		{
			$str .= "<![CDATA[".$this->_ReplaceImages($arParams["DESCRIPTION"], $res_id)."]]>";
		}
		$str .= "</description>";

		$str .= "<description_type>".$arParams["DESCRIPTION_TYPE"]."</description_type>";
		$str .= "<incorrect_message>" . htmlspecialcharsbx($arParams["INCORRECT_MESSAGE"]) . "</incorrect_message>";
		$str .= "<self>".$arParams["SELF"]."</self>";
		$str .= "<sort>".$arParams["SORT"]."</sort>";
		$str .= "<active>".$arParams["ACTIVE"]."</active>";
		$str .= "</bitrix>";

		$str .= "</item></questestinterop>";

		return $str;
	}

	public function CreateTMK($arParams, $res_id = 1)
	{
		$str = "<"."?xml version=\"1.0\" encoding=\"".$this->charset."\"?".">\n"
			. '<content type="TMK">'
			. '<score>' . (int) $arParams['SCORE'] . '</score>'
			. '<mark>' . htmlspecialcharsbx($arParams['MARK']) . '</mark>'
			. '<description>' . htmlspecialcharsbx($arParams['DESCRIPTION']) . '</description>'
			. '</content>';
		return $str;
	}
}
