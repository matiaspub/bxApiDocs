<?

// 2012-04-20 Checked/modified for compatibility with new data model
class CCourseSCORM
{
	var $package_dir;
	var $LAST_ERROR = "";
	var $arManifest = Array();
	var $arSITE_ID = Array();
	var $COURSE_ID = 0;
	var $objXML;
	var $arDraftFields = Array("detail_text", "preview_text", "description");
	var $arUnsetFields = Array("id", "timestamp_x", "chapter_id", "course_id", "lesson_id", "question_id", "created_by");
	var $arPicture = Array("detail_picture", "preview_picture", "file_id");
	var $arDate = Array("active_from", "active_to", "date_create");
	var $arWarnings = Array();
	var $arResources = array();


	// 2012-04-19 Checked/modified for compatibility with new data model
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
			return;
		}

		//Manifest exists?
		if (!is_file($this->package_dir."/imsmanifest.xml"))
		{
			$this->LAST_ERROR = GetMessage("LEARNING_MANIFEST_NOT_FOUND")."<br>";
			return;
		}

		//Sites check
		if (!is_array($arSITE_ID) || empty($arSITE_ID))
		{
			$this->LAST_ERROR = GetMessage("LEARNING_BAD_SITE_ID")."<br>";
			return;
		}

		$this->arSITE_ID = $arSITE_ID;

		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/xml.php");

		$this->objXML = new CDataXML();
		if (!$this->objXML->Load($this->package_dir."/imsmanifest.xml"))
		{
			$this->LAST_ERROR = GetMessage("LEARNING_MANIFEST_NOT_FOUND")."<br>";
			return;
		}
	}


	// 2012-04-19 Checked/modified for compatibility with new data model
	protected function CreateCourse()
	{
		global $APPLICATION;

		if (strlen($this->LAST_ERROR)>0)
			return false;

		if (!$title = $this->objXML->SelectNodes("/manifest/organizations/organization/title"))
		{
			$this->LAST_ERROR = GetMessage("LEARNING_BAD_NAME");
			return false;
		}

		$arFields = Array(
			"NAME" => $title->content,
			"SITE_ID" => $this->arSITE_ID,
			"SCORM" => "Y",
		);

		$course = new CCourse;
		$this->COURSE_ID = $course->Add($arFields);

		if ($this->COURSE_ID === false)
		{
			if($err = $APPLICATION->GetException())
				$this->LAST_ERROR = $err->GetString();
			return false;
		}

		return true;
	}


	// 2012-04-19 Checked/modified for compatibility with new data model
	protected function CreateContent($arItems = array(), $PARENT_ID = 0)
	{
		if (strlen($this->LAST_ERROR)>0)
			return false;

		if (empty($arItems))
		{
			if ($items = $this->objXML->SelectNodes("/manifest/organizations/organization/"))
			{
				$arItems = $items->__toArray();
				$arItems = $arItems["#"]["item"];
			}
		}

		foreach ($arItems as $ar)
		{
			$title = $ar["#"]["title"][0]["#"];
			$type = (!is_set($ar["#"], "item") && is_set($ar["@"], "identifierref")) ? "LES" : "CHA";
			$launch = "";
			if ($type == "LES")
			{
				foreach($this->arResources as $res)
				{
					if ($res["@"]["identifier"] == $ar["@"]["identifierref"])
					{
						$launch = "/".(COption::GetOptionString("main", "upload_dir", "upload"))."/learning/scorm/".$this->COURSE_ID."/";
						$launch .= $res["@"]["href"];
						if(is_set($ar["@"]["parameters"]))
						{
							$launch .= $ar["@"]["parameters"];
						}
					}
				}

			}

			$ID = $this->_MakeItems($title, $type, $launch, $PARENT_ID);

			if (is_set($ar["#"], "item"))
				$this->CreateContent($ar["#"]["item"], $ID);
		}
	}


	// 2012-04-20 Checked/modified for compatibility with new data model
	protected function _MakeItems($TITLE, $TYPE, $LAUNCH, $PARENT_ID)
	{
		global $APPLICATION;

		if ($PARENT_ID === 0)
		{
			$linkToParentLessonId = CCourse::CourseGetLinkedLesson ($this->COURSE_ID);
		}
		else
		{
			$linkToParentLessonId = (int) $PARENT_ID;
		}

		if ($TYPE == "LES")
		{
			$arFields = Array(
				'NAME'             => $TITLE,
				'LAUNCH'           => $LAUNCH,
				'DETAIL_TEXT_TYPE' => "file"
			);
		}
		elseif ($TYPE == "CHA")
		{
			$arFields = Array(
				'NAME' => $TITLE
			);
		}
		else
		{
			return $PARENT_ID;
		}

		// properties (in context of parent) by default
		$arProperties = array('SORT' => 500);

		$ID = CLearnLesson::Add (
			$arFields, 
			false, 			// is it course? - No, it isn't.
			$linkToParentLessonId, 
			$arProperties);

		if ($ID > 0)
			return $ID;
		else
		{
			if($e = $APPLICATION->GetException())
				$this->arWarnings[$TYPE][] = Array("TITLE" => $TITLE, "TEXT" =>$e->GetString());
		}
	}


	// 2012-04-19 Checked/modified for compatibility with new data model
	public function ImportPackage()
	{
		$resources = $this->objXML->SelectNodes("/manifest/resources/");
		$this->arResources = $resources->__toArray();
		$this->arResources = $this->arResources["#"]["resource"];

		if (!$this->CreateCourse())
			return false;

		$this->CreateContent();

		CLearnHelper::CopyDirFiles(
			$this->package_dir, 
			$_SERVER["DOCUMENT_ROOT"]."/".(COption::GetOptionString("main", "upload_dir", "upload"))."/learning/scorm/".$this->COURSE_ID, 
			true, 
			true);

		return true;
	}
}
