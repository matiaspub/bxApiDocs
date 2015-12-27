<?php

// 2012-04-19 Checked/modified for compatibility with new data model

/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/learning/classes/ccourseimport/index.php
 * @author Bitrix
 */
class CCourseImport
{
	var $package_dir;
	var $LAST_ERROR = "";
	var $arManifest = Array();
	var $arSITE_ID = Array();
	var $COURSE_ID = 0;
	var $objXML;
	var $arDraftFields = Array("detail_text", "preview_text", "description");
	var $arUnsetFields = Array("id", "site_id", "timestamp_x", 'date_create', 
		"chapter_id", "course_id", "lesson_id", "question_id", 
		"created_by", 'created_user_name', 'linked_lesson_id',
		'childs_cnt', 'is_childs', 'description', 'description_type', 
		'was_chapter_id');
	var $arPicture = Array("detail_picture", "preview_picture", "file_id");
	var $arDate = Array("active_from", "active_to");
	var $arWarnings = Array();
	protected $arPreventUnsetFieldsForTest = array('description', 'description_type');


	// List of fields, writable to unilessons
	protected $arLessonWritableFields = array('NAME', 'ACTIVE', 'CODE',
		'PREVIEW_PICTURE', 'PREVIEW_TEXT', 'PREVIEW_TEXT_TYPE',
		'DETAIL_PICTURE', 'DETAIL_TEXT', 'DETAIL_TEXT_TYPE',
		'LAUNCH', 'KEYWORDS');


	// 2012-04-18 Checked/modified for compatibility with new data model
	public function __construct($PACKAGE_DIR, $arSITE_ID)
	{
		//Cut last slash
		if (substr($PACKAGE_DIR,-1, 1) == "/")
			$PACKAGE_DIR = substr($PACKAGE_DIR, 0, -1);

		$this->package_dir = $_SERVER["DOCUMENT_ROOT"].$PACKAGE_DIR;

		//Dir exists?
		if (!is_dir($this->package_dir))
		{
			$this->LAST_ERROR = GetMessage("LEARNING_BAD_PACKAGE")."<br>";
			return false;
		}

		//Manifest exists?
		if (!is_file($this->package_dir."/imsmanifest.xml"))
		{
			$this->LAST_ERROR = GetMessage("LEARNING_MANIFEST_NOT_FOUND")."<br>";
			return false;
		}

		//Sites check
		if (!is_array($arSITE_ID) || empty($arSITE_ID))
		{
			$this->LAST_ERROR = GetMessage("LEARNING_BAD_SITE_ID")."<br>";
			return false;
		}

		$this->arSITE_ID = $arSITE_ID;

		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/xml.php");

		$this->objXML = new CDataXML();
		if (!$this->objXML->Load($this->package_dir."/imsmanifest.xml"))
		{
			$this->LAST_ERROR = GetMessage("LEARNING_MANIFEST_NOT_FOUND")."<br>";
			return false;
		}

		return true;
	}


	// 2012-04-18 Checked/modified for compatibility with new data model
	protected function CreateCourse()
	{
		global $APPLICATION;

		if (strlen($this->LAST_ERROR)>0)
			return false;

		if (!$title = $this->objXML->SelectNodes("/manifest/organizations/organization/item/title"))
		{
			$this->LAST_ERROR = GetMessage("LEARNING_BAD_NAME");
			return false;
		}

		$arFields = Array(
			"NAME" => $title->content,
			"SITE_ID" => $this->arSITE_ID,
		);

		$course = new CCourse;
		$this->COURSE_ID = $course->Add($arFields);
		$res = ($this->COURSE_ID);

		if(!$res)
		{
			if($e = $APPLICATION->GetException())
				$this->LAST_ERROR = $e->GetString();
			return false;
		}

		$r = new CDataXML();
		if (!$r->Load($this->package_dir."/res1.xml"))
			return false;

		if (!$data = $r->SelectNodes("/coursetoc/"))
			return false;

		$ar = $data->__toArray();
		$arFields =  $this->_MakeFields($ar);

		$res = $course->Update($this->COURSE_ID, $arFields);

		if(!$res)
		{
			if($e = $APPLICATION->GetException())
				$this->LAST_ERROR = $e->GetString();
			return false;
		}

		CheckDirPath($_SERVER["DOCUMENT_ROOT"]."/".(COption::GetOptionString("main", "upload_dir", "upload"))."/learning/".$this->COURSE_ID);
		CLearnHelper::CopyDirFiles(
			$this->package_dir."/resources/res1", 
			$_SERVER["DOCUMENT_ROOT"] . "/" . (COption::GetOptionString("main", "upload_dir", "upload")) . "/learning/" . $this->COURSE_ID . "/res1",
			true);

		return true;
	}


	// 2012-04-19 Checked/modified for compatibility with new data model
	protected function CreateContent($arItems = Array(), $PARENT_ID = 0)
	{
		if (strlen($this->LAST_ERROR)>0)
			return false;

		if (empty($arItems))
		{
			if ($items = $this->objXML->SelectNodes("/manifest/organizations/organization/item/"))
			{
				$arItems = $items->__toArray();
				$arItems = $arItems["#"]["item"];
			}
		}

		foreach ($arItems as $ar)
		{
			$type =  substr($ar["@"]["identifier"], 0, 3);
			$res_id = $ar["@"]["identifierref"];
			$title = $ar["#"]["title"][0]["#"];

			$ID = $this->_MakeItems($title, $type, $res_id, $PARENT_ID);

			if (is_set($ar["#"], "item"))
				$this->CreateContent($ar["#"]["item"], $ID);
		}
	}


	// 2012-04-19 Checked/modified for compatibility with new data model
	protected function _MakeItems($TITLE, $TYPE, $RES_ID, $PARENT_ID)
	{
		global $APPLICATION;

		if ($PARENT_ID === 0)
			$linkToParentLessonId = CCourse::CourseGetLinkedLesson ($this->COURSE_ID);
		else
			$linkToParentLessonId = (int) $PARENT_ID;

		$createUnilesson = false;

		if ($TYPE == "LES")
		{
			$arFields = Array(
				'NAME' => $TITLE
			);

			$createUnilesson = true;
		}
		elseif ($TYPE == "CHA")
		{
			$arFields = Array(
				'NAME' => $TITLE
			);

			$createUnilesson = true;
		}
		elseif ($TYPE == "QUE")
		{
			$arFields = Array(
				"NAME" => $TITLE,
				"LESSON_ID" => $linkToParentLessonId
			);

			$cl = new CLQuestion;
		}
		elseif ($TYPE == "TES")
		{
			$arFields = Array(
				"NAME" => $TITLE,
				"COURSE_ID" => $this->COURSE_ID
			);

			$cl = new CTest;
		}
		elseif ($TYPE === 'TMK')
		{
			$arFields = array();

			$cl = new CLTestMark;
		}
		else
			return $PARENT_ID;


		$r = new CDataXML();
		if (!$r->Load($this->package_dir."/".strtolower($RES_ID).".xml"))
			$r = false;

		if ($r !== false)
		{
			if ($TYPE == "QUE")
			{
				if (
					($data = $r->SelectNodes("/questestinterop/item/presentation/"))
					&&
					($resp = $r->SelectNodes("/questestinterop/item/resprocessing/"))
					)
				{
					$arQ = Array();
					$arData = $data->__toArray();
					$arResp = $resp->__toArray();

					if (is_set($arData["#"]["material"][0]["#"], "mattext"))
						$arQ["NAME"] = $arData["#"]["material"][0]["#"]["mattext"][0]["#"];

					if (is_set($arData["#"]["material"][0]["#"], "matimage"))
					{
						$imageDescription = '';
						if (is_set($arData["#"]["material"][0]["#"], 'image_description'))
							$imageDescription = $arData["#"]["material"][0]["#"]['image_description'][0]['#'];

						$arQ["FILE_ID"] = Array(
							"MODULE_ID" => "learning",
							"name" =>basename($arData["#"]["material"][0]["#"]["matimage"][0]["@"]["uri"]),
							"tmp_name" => $this->package_dir."/".$arData["#"]["material"][0]["#"]["matimage"][0]["@"]["uri"],
							"size" =>@filesize($this->package_dir."/".$arData["#"]["material"][0]["#"]["matimage"][0]["@"]["uri"]),
							"type" => $arData["#"]["material"][0]["#"]["matimage"][0]["@"]["imagtype"],
							'description' => $imageDescription
						);
					}

					if (is_set($arData["#"]["response_lid"][0]["@"], "rcardinality"))
					{
						switch ($arData["#"]["response_lid"][0]["@"]["rcardinality"])
						{
							case "Multiple":
								$arQ["QUESTION_TYPE"] = 'M';
								break;
							case "Text":
								$arQ["QUESTION_TYPE"] = 'T';
								break;
							case "Sort":
								$arQ["QUESTION_TYPE"] = 'R';
								break;
							default:
								$arQ["QUESTION_TYPE"] = 'S';
								break;
						}
					}

					if (is_set($arResp["#"]["respcondition"][0]["#"], "setvar"))
						$arQ["POINT"] = $arResp["#"]["respcondition"][0]["#"]["setvar"][0]['#'];

					//Additional
					if ($bx = $r->SelectNodes("/questestinterop/item/bitrix/"))
					{
						$arQ = array_merge($arQ, $this->_MakeFields($bx->__toArray(), $TYPE));
						unset($bx);
					}

					$arFields = array_merge($arFields,$arQ);

					$cl = new CLQuestion;
					$ID = $cl->Add($arFields);

					if ($ID > 0)
					{
						$PARENT_ID = $ID;
						$arCorrect = Array();
						if (
							is_set($arResp["#"]["respcondition"][0]["#"], "conditionvar")
							&&
							is_set($arResp["#"]["respcondition"][0]["#"]["conditionvar"][0]["#"], "varequal")
							)
						{

							foreach ($arResp["#"]["respcondition"][0]["#"]["conditionvar"][0]["#"]["varequal"] as $ar)
								$arCorrect[] = $ar["#"];
						}

						if (is_set($arData["#"]["response_lid"][0]["#"], "render_choice")
							&&
							is_set($arData["#"]["response_lid"][0]["#"]["render_choice"][0]["#"], "response_label")
							)
						{
							$i = 0;
							foreach ($arData["#"]["response_lid"][0]["#"]["render_choice"][0]["#"]["response_label"] as $ar)
							{
								$i +=10;
								$cl = new CLAnswer;
								$arFields = Array(
									"QUESTION_ID" => $PARENT_ID,
									"SORT" => $i,
									"CORRECT" => (in_array($ar["@"]["ident"],$arCorrect) ? "Y": "N"),
									"ANSWER" => $ar["#"]["material"][0]["#"]["mattext"][0]["#"],
								);

								$AswerID = $cl->Add($arFields);
								$res = ($AswerID > 0);
								if (!$res)
								{
									if ($e = $APPLICATION->GetException())
										$this->arWarnings[$TYPE][] = Array("TITLE" => $TITLE, "TEXT" =>$e->GetString());
								}
							}
						}
					}
					else
					{
						if ($e = $APPLICATION->GetException())
							$this->arWarnings[$TYPE][] = Array("TITLE" => $TITLE, "TEXT" =>$e->GetString());
					}

					unset($cl);
					unset($data);
					unset($arQ);
					unset($resp);
					unset($arData);
					unset($arResp);

					return $PARENT_ID;
				}
			}
			else
			{
				if ($data = $r->SelectNodes("/content/"))
				{
					$ar = $data->__toArray();
					$arFields = array_merge($arFields,$this->_MakeFields($ar, $TYPE));
					if ($TYPE === 'TMK')
						$arFields['TEST_ID'] = (int) $PARENT_ID;

					if (is_set($arFields, "COMPLETED_SCORE") && intval($arFields["COMPLETED_SCORE"]) <= 0)
						unset($arFields["COMPLETED_SCORE"]);
					if ((is_set($arFields, "PREVIOUS_TEST_ID") && intval($arFields["PREVIOUS_TEST_ID"]) <= 0) || !CTest::GetByID($arFields["PREVIOUS_TEST_ID"])->Fetch())
						unset($arFields["PREVIOUS_TEST_ID"], $arFields["PREVIOUS_TEST_SCORE"]);
				}
			}
		}

		if ($createUnilesson === false)
		{
			$ID = $cl->Add($arFields);
			unset($cl);
		}
		else
		{
			$bProhibitPublish = false;
			// properties (in context of parent) by default
			$arProperties = array('SORT' => 500);

			// Lesson's sort order in context of parent
			if (isset($arFields['EDGE_SORT']))
			{
				$arFields['SORT'] = (int) $arFields['EDGE_SORT'];
				unset ($arFields['EDGE_SORT']);
			}

			if (isset($arFields['SORT']))
			{
				$arProperties['SORT'] = (int) $arFields['SORT'];

				// Lessons doesn't have more SORT field
				unset ($arFields['SORT']);
			}

			if (isset($arFields['META_PUBLISH_PROHIBITED']))
			{
				if ($arFields['META_PUBLISH_PROHIBITED'] === 'Y')
					$bProhibitPublish = true;

				unset($arFields['META_PUBLISH_PROHIBITED']);
			}

			// unset fields, that are absent in unilesson
			$arUnilessonFields = $arFields;
			$arFieldsNames = array_keys($arUnilessonFields);
			foreach ($arFieldsNames as $fieldName)
			{
				if ( ! in_array(strtoupper($fieldName), $this->arLessonWritableFields) )
					unset ($arUnilessonFields[$fieldName]);
			}

			$ID = CLearnLesson::Add (
				$arUnilessonFields,
				false, 			// is it course? - No, it isn't.
				$linkToParentLessonId, 
				$arProperties
			);

			if ($bProhibitPublish && ($ID > 0))
				CLearnLesson::PublishProhibitionSetTo($ID, $linkToParentLessonId, $bProhibitPublish);
		}

		if ($ID > 0)
			return $ID;
		else
		{
			if($e = $APPLICATION->GetException())
				$this->arWarnings[$TYPE][] = Array("TITLE" => $TITLE, "TEXT" =>$e->GetString());
		}
	}


	// 2012-04-18 Checked/modified for compatibility with new data model
	protected function _MakeFields(&$arFields, $itemType = null)
	{
		$arRes = Array();
		$upload_dir = COption::GetOptionString("main", "upload_dir", "upload");

		$arStopList = array();
		foreach($arFields["#"] as $field => $arValue)
		{
			if (in_array($field, $arStopList))
				continue;

			if (in_array($field, $this->arUnsetFields) && ($itemType !== 'TMK') && ($itemType !== 'QUE'))
			{
				if ( ! ($itemType === 'TES' && in_array($field, $this->arPreventUnsetFieldsForTest)) )
					continue;
			}

			if (in_array($field, $this->arDraftFields) && ($itemType !== 'TMK'))
			{
				if (is_set($arValue[0]["#"], "cdata-section"))
				{
					$arRes[strtoupper($field)] = preg_replace(
						"~([\"'])(cid:resources/(.+?))(\\1)~is", 
						"\\1/".$upload_dir."/learning/".$this->COURSE_ID."/\\3\\1",
						$arValue[0]["#"]["cdata-section"][0]["#"]);
					continue;
				}
				elseif (isset($arValue[0]["#"]))
				{
					$arRes[strtoupper($field)] = preg_replace(
						"~([\"'])(cid:resources/(.+?))(\\1)~is", 
						"\\1/".$upload_dir."/learning/".$this->COURSE_ID."/\\3\\1",
						$arValue[0]["#"]);
					continue;
				}
			}

			if (in_array($field, $this->arDate) && strlen($arValue[0]["#"]) > 0)
			{
				$time = date("His", $arValue[0]["#"]);
				$arRes[strtoupper($field)] = ConvertTimeStamp($arValue[0]["#"], $time == "000000" ? "SHORT" : "FULL");
				continue;
			}

			if (in_array($field, $this->arPicture) && intval($arValue[0]["#"]) > 0)
			{
				$file = $this->package_dir."/dbresources/".$arValue[0]["#"];

				if (method_exists('CFile', 'GetImageSize'))
				{
					$aImage = @CFile::GetImageSize($file);
					if($aImage === false)
							continue;

					if (function_exists("image_type_to_mime_type"))
						$image_type_to_mime_type = image_type_to_mime_type($aImage[2]);
					else
						$image_type_to_mime_type = CCourseImport::ImageTypeToMimeType($aImage[2]);
				}
				else
					$image_type_to_mime_type = self::ImageTypeToMimeTypeByFileName($file);

				$arRes[strtoupper($field)] = array(
					"MODULE_ID" => "learning",
					"name" =>$arValue[0]["#"],
					"tmp_name" => $file,
					"size" =>@filesize($file),
					"type" => $image_type_to_mime_type
				);

				if (isset($arFields["#"][$field . '_description'][0]['#']))
				{
					$arRes[strtoupper($field)]['description'] = $arFields["#"][$field . '_description'][0]['#'];
					$arStopList[] = $field . '_description';
				}

				continue;
			}

			$arRes[strtoupper($field)] = $arValue[0]["#"];
		}
		unset($arFields);
		return $arRes;
	}


	// 2012-04-18 Checked/modified for compatibility with new data model
	
	/**
	* <p>Метод импортирует курс.</p>
	*
	*
	* @return bool <p>Метод возвращает <i>true</i>, если создание курса прошло успешно. При
	* возникновении ошибки метод вернёт <i>false</i>, а в свойстве объекта
	* LAST_ERROR будет содержаться текст ошибки.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CModule::IncludeModule("learning"))
	* {
	*     if ($USER-&gt;IsAdmin())
	*     {
	*         @set_time_limit(0);
	*         $package = new CCourseImport($PACKAGE_DIR = "/upload/mypackage/", Array("ru", "en"));
	* 
	*         if (strlen($package-&gt;LAST_ERROR) &gt; 0)
	*         {
	*             echo "Error: ".$package-&gt;LAST_ERROR;
	*         }
	*         else
	*         {
	*             $success = $package-&gt;ImportPackage();
	* 
	*             if (!$success)
	*                 echo "Error: ".$package-&gt;LAST_ERROR;
	*             else
	*                 echo "Ok!";
	*         }
	*     }
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccourseimport/index.php">CCourseImport</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/learning/classes/ccourseimport/ccourseimport.php">CCourseImport</a> </li></ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/learning/classes/ccourseimport/importpackage.php
	* @author Bitrix
	*/
	public function ImportPackage()
	{
		if (!$this->CreateCourse())
			return false;

		$this->CreateContent();

		CLearnHelper::CopyDirFiles(
			$this->package_dir."/resources", 
			$_SERVER["DOCUMENT_ROOT"] . "/" . (COption::GetOptionString("main", "upload_dir", "upload")) . "/learning/" . $this->COURSE_ID,
			true,
			true);

		return true;
	}


	protected static function ImageTypeToMimeTypeByFileName ($file)
	{
		$ext = strtolower(pathinfo ($file, PATHINFO_EXTENSION));

		switch ($ext)
		{
			case 'jpg':
			case 'jpeg':
				$type = 'image/jpeg';
			break;

			case 'jp2':
				$type = 'image/jp2';
			break;

			case 'gif':
				$type = 'image/gif';
			break;

			case 'png':
				$type = 'image/png';
			break;

			case 'bmp':
				$type = 'image/bmp';
			break;

			default:
				$type = 'application/octet-stream';
			break;
		}

		return ($type);
	}


	// 2012-04-18 Checked/modified for compatibility with new data model
	public static function ImageTypeToMimeType($type)
	{
		$aTypes = array(
			1 => "image/gif",
			2 => "image/jpeg",
			3 => "image/png",
			4 => "application/x-shockwave-flash",
			5 => "image/psd",
			6 => "image/bmp",
			7 => "image/tiff",
			8 => "image/tiff",
			9 => "application/octet-stream",
			10 => "image/jp2",
			11 => "application/octet-stream",
			12 => "application/octet-stream",
			13 => "application/x-shockwave-flash",
			14 => "image/iff",
			15 => "image/vnd.wap.wbmp",
			16 => "image/xbm"
		);
		if(!empty($aTypes[$type]))
			return $aTypes[$type];
		else
			return "application/octet-stream";
	}
}
