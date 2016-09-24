<?
//System, not for use
use Bitrix\Main\Application;
use Bitrix\Main\Entity\ExpressionField;

Class CIdeaManagmentEmailNotify
{
	const SUBSCRIBE_ALL = 'A';
	const SUBSCRIBE_ALL_IDEA = 'AI';
	//const SUBSCRIBE_ALL_IDEA_COMMENT = 'AIC';
	const SUBSCRIBE_IDEA_COMMENT = 'I';

	private $Notify = NULL;
	private static $Enable = true;

	public function __construct($parent)
	{
		$this->Notify = $parent;
	}

	public function IsAvailable()
	{
		return CModule::IncludeModule('blog') && NULL!=$this->Notify && self::$Enable;
	}

	public static function Add($Entity)
	{
		$notifyEmail = new \Bitrix\Idea\NotifyEmail();
		$db_res = $notifyEmail->addIdea($Entity);
		return (is_object($db_res) || is_array($db_res) && !empty($db_res));
	}

	public static function Delete($Entity)
	{
		$notifyEmail = new \Bitrix\Idea\NotifyEmail();
		if ($Entity == 'AI' || $Entity == 'A')
			$notifyEmail->deleteCategory('');
		else if (substr($Entity, 0, strlen(self::SUBSCRIBE_IDEA_COMMENT)) == self::SUBSCRIBE_IDEA_COMMENT)
			$notifyEmail->deleteIdea(substr($Entity, strlen(self::SUBSCRIBE_IDEA_COMMENT)));
		else if (strlen(intval($Entity)) == strlen($Entity))
			$notifyEmail->deleteIdea($Entity);
		return true;
	}

	public static function GetList($order = Array(), $arFilter = Array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		$filter = array(
			"LOGIC" => "AND"
		);
		if (is_array($arFilter))
		{
			foreach($arFilter as $dkey => $val)
			{
				$key = CSqlUtil::GetFilterOperation($dkey);
				if ($key["FIELD"] == "ID" && is_array($val))
				{
					$res = array(
						"LOGIC" => "OR"
					);
					foreach ($val as $v)
					{
						if ($v == self::SUBSCRIBE_ALL)
						{
							$res[] = array("=SUBSCRIBE_TYPE" => \Bitrix\Idea\NotifyEmailTable::SUBSCRIBE_TYPE_ALL);
						}
						else if (strpos($v, self::SUBSCRIBE_IDEA_COMMENT) === 0)
						{
							$res[] = array(
								"=ENTITY_TYPE" => \Bitrix\Idea\NotifyEmailTable::ENTITY_TYPE_IDEA,
								"=ENTITY_CODE" => str_replace(self::SUBSCRIBE_IDEA_COMMENT, "", $v)
							);
						}
					}
					$filter[] = $res;
				}
				else if ($key["FIELD"] == "USER_ID" || $key["FIELD"] == "USER_EMAIL")
					$filter[] = array($dkey => $val);
			}
		}
		$select = array();
		$runtime = array();
		if (is_array($arSelectFields))
		{
			$select = array_intersect($arSelectFields, array_keys(\Bitrix\Idea\NotifyEmailTable::getMap()));
			if (in_array("USER_EMAIL", $arSelectFields))
				$select["USER_EMAIL"] = "USER.EMAIL";
			if (in_array("ID", $arSelectFields))
			{
				$select["ID"] = 'RUNTIME_ID';
				$runtime[] = new ExpressionField(
					'RUNTIME_ID',
					Application::getConnection()->getSqlHelper()->getConcatFunction(
						"CASE ".
							"WHEN %s='".\Bitrix\Idea\NotifyEmailTable::ENTITY_TYPE_IDEA."' AND %s='' THEN '".self::SUBSCRIBE_ALL."' ".
							"WHEN %s='".\Bitrix\Idea\NotifyEmailTable::ENTITY_TYPE_IDEA."' THEN '".self::SUBSCRIBE_IDEA_COMMENT."' ".
							"WHEN %s='".\Bitrix\Idea\NotifyEmailTable::ENTITY_TYPE_CATEGORY."' AND %s='' THEN '".self::SUBSCRIBE_ALL_IDEA."' ".
							"WHEN %s='".\Bitrix\Idea\NotifyEmailTable::ENTITY_TYPE_CATEGORY."' THEN '".\Bitrix\Idea\NotifyEmailTable::ENTITY_TYPE_CATEGORY."' ".
							"ELSE 'UNK' END",
						"%s"),
					array(
						"ENTITY_TYPE", "ENTITY_CODE", "ENTITY_TYPE", "ENTITY_TYPE", "ENTITY_CODE", "ENTITY_TYPE", "ENTITY_CODE")
				);
			}
		}
		$db_res = \Bitrix\Idea\NotifyEmailTable::getList(
			array(
				'filter' => $filter,
				'select' => $select,
				'order' => $order,
				'runtime' => $runtime
			)
		);
		return new CDBResult($db_res);
	}

	public function Send()
	{
		if(!$this->IsAvailable())
			return false;

		$arNotification = $this->Notify->getNotification();

		//No need to send about updates;
		if($arNotification["ACTION"] == "UPDATE")
			return 0;
		$category = ToUpper($arNotification["CATEGORY"]);
		$arEmailSubscribe = array();
		if (!array_key_exists("CATEGORIES", $arNotification))
			$arNotification["CATEGORIES"] = \CIdeaManagment::getInstance()->Idea()->GetCategoryList();
		if (array_key_exists($category, $arNotification["CATEGORIES"]) && \CIdeaManagment::getInstance()->Idea()->GetCategoryListID() > 0)
			$category = $arNotification["CATEGORIES"][$category];
		else
			$category = null;

		if ($arNotification["TYPE"] == "IDEA") // (COMMENT, IDEA)
		{
			$filter = array(
				"LOGIC" => "OR",
				array(
					"=ENTITY_TYPE" => \Bitrix\Idea\NotifyEmailTable::ENTITY_TYPE_CATEGORY,
					"=ENTITY_CODE" => '',
				)
			);
			if (!is_null($category))
			{
				$filter[] = array(
					"=ENTITY_TYPE" => \Bitrix\Idea\NotifyEmailTable::ENTITY_TYPE_CATEGORY,
					"=ASCENDED_CATEGORIES.IBLOCK_ID" => \CIdeaManagment::getInstance()->Idea()->GetCategoryListID(),
					"<=ASCENDED_CATEGORIES.DEPTH_LEVEL" => $category["DEPTH_LEVEL"],
					"<=ASCENDED_CATEGORIES.LEFT_MARGIN" => $category["LEFT_MARGIN"],
					">=ASCENDED_CATEGORIES.RIGHT_MARGIN" => $category["RIGHT_MARGIN"]
				);
			}
		}
		else
		{
			$filter = array(
				"LOGIC" => "OR",
				array(
					"=ENTITY_TYPE" => \Bitrix\Idea\NotifyEmailTable::ENTITY_TYPE_IDEA,
					"=ENTITY_CODE" => $arNotification["POST_ID"],
				),
				array(
					"=SUBSCRIBE_TYPE" => \Bitrix\Idea\NotifyEmailTable::SUBSCRIBE_TYPE_ALL,
					"=ENTITY_TYPE" => \Bitrix\Idea\NotifyEmailTable::ENTITY_TYPE_CATEGORY,
					"=ENTITY_CODE" => ''
				)
			);
			if (!is_null($category))
			{
				$filter[] = array(
					"=SUBSCRIBE_TYPE" => \Bitrix\Idea\NotifyEmailTable::SUBSCRIBE_TYPE_ALL,
					"=ENTITY_TYPE" => \Bitrix\Idea\NotifyEmailTable::ENTITY_TYPE_CATEGORY,
					"=ASCENDED_CATEGORIES.IBLOCK_ID" => \CIdeaManagment::getInstance()->Idea()->GetCategoryListID(),
					"<=ASCENDED_CATEGORIES.DEPTH_LEVEL" => $category["DEPTH_LEVEL"],
					"<=ASCENDED_CATEGORIES.LEFT_MARGIN" => $category["LEFT_MARGIN"],
					">=ASCENDED_CATEGORIES.RIGHT_MARGIN" => $category["RIGHT_MARGIN"]
				);
			}
		}

		$db_res = \Bitrix\Idea\NotifyEmailTable::getList(
			array(
				'filter' => $filter,
				'select' => array("USER_ID", "USER_EMAIL" => "USER.EMAIL")
			)
		);

		if (!is_null($category))
			$arNotification["CATEGORY"] = $category["NAME"];
		unset($arNotification["CATEGORIES"]);
		if (!array_key_exists("IDEA_TITLE", $arNotification))
			$arNotification["IDEA_TITLE"] = $arNotification["TITLE"];

		while($r = $db_res->Fetch())
		{
			if($r["USER_ID"] != $arNotification["AUTHOR_ID"] && !array_key_exists($r["USER_ID"], $arEmailSubscribe) && check_email($r["USER_EMAIL"]))
			{
				$arEmailSubscribe[$r["USER_ID"]] = $r["USER_EMAIL"];

				$arNotification["EMIAL_TO"] = $r["USER_EMAIL"]; //This is for backward compatibility
				$arNotification["EMAIL_TO"] = $r["USER_EMAIL"];
				//ADD_IDEA_COMMENT, ADD_IDEA
				CEvent::Send($arNotification["ACTION"].'_'.$arNotification["TYPE"], SITE_ID, $arNotification);
			}
		}
		return count($arEmailSubscribe) > 0;
	}

	static public function Disable()
	{
		self::$Enable = false;
	}

	static public function Enable()
	{
		self::$Enable = true;
	}
}
?>