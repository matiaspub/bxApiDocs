<?
Class CIdeaManagmentIdea
{
	private $IdeaId = false;
	private $CacheStorage = array();
	private static $CategoryIB = false;
	private static $instance = null;

	public function __construct($IdeaId = false)
	{
		$this->SetId($IdeaId);
	}

	public static function GetInstance($IdeaId)
	{
		if (self::$instance === null || is_object(self::$instance) && self::$instance->IdeaId !== $IdeaId)
		{
			$c = __CLASS__;
			self::$instance = new $c($IdeaId);
		}

		return self::$instance;
	}

	public function IsAvailable()
	{
		return $this->IdeaId>0;
	}

	public function SetID($IdeaId)
	{
		$this->IdeaId = $IdeaId;
		return $this;
	}

	static public function SetCategoryListID($ID)
	{
		if(intval($ID)>0)
			self::$CategoryIB = intval($ID);

		return $this;
	}

	static public function GetCategoryListID()
	{
		return (int)self::$CategoryIB;
	}
	/*
	 * Not for USE Can be changed
	 */
	public function GetSubCategoryList($CategoryCode)
	{
		if(self::$CategoryIB <= 0)
			return array();

		$arCategoryList = $this->GetCategoryList();
		$arSubCategoryList = array($CategoryCode);
		$arSubCategoryListId = array();

		if(is_array($arCategoryList[$CategoryCode]) && $arCategoryList[$CategoryCode]["ID"]>0)
			$arSubCategoryListId[] = $arCategoryList[$CategoryCode]["ID"];

		if($arCategoryList && !empty($arSubCategoryListId))
		{
			foreach($arCategoryList as $key=>$arCategory)
			{
				if(in_array($arCategory["IBLOCK_SECTION_ID"], $arSubCategoryListId))
				{
					$arSubCategoryList[] = $key;
					$arSubCategoryListId[] = $arCategory["ID"];
				}
			}
		}

		return array("CODE" => $arSubCategoryList, "ID" => $arSubCategoryListId);
	}
	/*
	 * Not for USE Can be changed
	 */
	public function GetCategorySequence($CODE)
	{
		if(self::$CategoryIB <= 0 || !$CODE)
			return array();

		$arCategoryListXML = $this->GetCategoryList();
		$arCategoryList = array();
		foreach($arCategoryListXML as $arCategory)
			$arCategoryList[$arCategory["ID"]] = $arCategory;

		$arSequnce = array("CATEGORY_1" => false, "CATEGORY_2" => false);
		$CODE = ToUpper($CODE);

		$arFullSequence = array();
		while(array_key_exists($CODE, $arCategoryListXML))
		{
			array_unshift($arFullSequence, ToLower($CODE));
			if($arCategoryListXML[$CODE]["IBLOCK_SECTION_ID"]>0 && $arCategoryList[$arCategoryListXML[$CODE]["IBLOCK_SECTION_ID"]])
				$CODE = ToUpper($arCategoryList[$arCategoryListXML[$CODE]["IBLOCK_SECTION_ID"]]["CODE"]);
			else
				break;
		}

		if(array_key_exists(0, $arFullSequence))
			$arSequnce["CATEGORY_1"] = $arFullSequence[0];
		if(count($arFullSequence)>1)
			$arSequnce["CATEGORY_2"] = end($arFullSequence);

		$arSequnce["FULL"] = $arFullSequence;

		return $arSequnce;
	}

	public function GetCategoryList($CategoryIB = false)
	{
		if(self::$CategoryIB <= 0)
			return array();

		$arCategory = array();
		//Return an empty array if IB isn't set
		if($CategoryIB <= 0)
			if(($CategoryIB = self::$CategoryIB) === false)
				return $arCategory;

		if(is_array($this->CacheStorage["CATEGORY_LIST"]) && array_key_exists($CategoryIB, $this->CacheStorage["CATEGORY_LIST"]))
			return $this->CacheStorage["CATEGORY_LIST"][$CategoryIB];

		$obCache = new CPHPCache;
		$life_time = 60*60*24*30; //over 1 month
		$cache_id = 'idea_category_list_'.$CategoryIB; //no need to keep unique all time, just caching for 1 day if no changes
		$cache_path = '/'.SITE_ID.'/idea/category_list/'.$CategoryIB.'/';

		global $CACHE_MANAGER;

		if($obCache->StartDataCache($life_time, $cache_id, $cache_path))
		{
			if(defined("BX_COMP_MANAGED_CACHE")) //Tag Cache
			{
				$CACHE_MANAGER->StartTagCache($cache_path);
				$CACHE_MANAGER->RegisterTag("iblock_id_".$CategoryIB);
			}

			$obSec = CIBlockSection::GetList(array("left_margin"=>"ASC"), array("IBLOCK_ID" => $CategoryIB, "ACTIVE" => "Y"));
			while($r = $obSec->GetNext())
				if(strlen($r["CODE"])>0)
					$arCategory[ToUpper($r["CODE"])] = $r;
				//else
				//	$arCategory[$r["ID"]] = $r;

			if(!empty($arCategory))
			{
				if(defined("BX_COMP_MANAGED_CACHE")) //Tag Cache
					$CACHE_MANAGER->EndTagCache();

				$obCache->EndDataCache($arCategory);
			}
			else
				$obCache->AbortDataCache();
		}
		else
			$arCategory = $obCache->GetVars();

		return $this->CacheStorage["CATEGORY_LIST"][$CategoryIB] = $arCategory;
	}
	/*
	 * Not for USE Can be changed
	 */
	public function GetDefaultStatus($arStatusPriority = array())
	{
		if(!is_array($arStatusPriority))
			$arStatusPriority = array();

		$arDefaultStatus = array();
		$arStatusPriority = array_unique($arStatusPriority);
		$arStatusList = $this->GetStatusList();

		foreach ($arStatusPriority as $StatusId)
		{
			if(array_key_exists($StatusId, $arStatusList))
			{
				$arDefaultStatus = $arStatusList[$StatusId];
				break;
			}
		}
		//Not found in priority
		if(!$arDefaultStatus)
		{
			foreach($arStatusList as $arStatus)
			{
				if(!$arDefaultStatus)
					$arDefaultStatus = $arStatus;

				if($arStatus["DEF"] == "Y")
				{
					$arDefaultStatus = $arStatus;
					break;
				}
			}
		}

		return $arDefaultStatus;
	}

	public function GetStatusList($XML_ID = false)
	{
		if(is_array($this->CacheStorage["STATUS_LIST"]) && array_key_exists(intval($XML_ID), $this->CacheStorage["STATUS_LIST"]))
			return $this->CacheStorage["STATUS_LIST"][intval($XML_ID)];

		$obCache = new CPHPCache;
		$life_time = 60*60*24*30; //over 1 month
		$cache_id = 'idea_status_list'; //no need to keep unique all time, just caching for 1 day if no changes
		$cache_path = '/'.SITE_ID.'/idea/status_list/';

		$arStatus = array();
		if($obCache->StartDataCache($life_time, $cache_id, $cache_path))
		{
			$arStatusField = CUserTypeEntity::GetList(
				array(),
				array(
					"ENTITY_ID" => "BLOG_POST",
					"FIELD_NAME" => CIdeaManagment::UFStatusField
				)
			)->Fetch();
			if($arStatusField)
			{
				$oStatus = CUserFieldEnum::GetList(array(), array("USER_FIELD_ID" => $arStatusField["ID"]));
				while($r = $oStatus->Fetch())
					$arStatus[$r["ID"]] = $r;

				$obCache->EndDataCache($arStatus);
			}
			else
				$obCache->AbortDataCache();
		}
		else
			$arStatus = $obCache->GetVars();

		if($XML_ID)
		{
			$arStatusXML = array();
			foreach($arStatus as $Status)
				$arStatusXML[$Status["XML_ID"]] = $Status;
			$arStatus = $arStatusXML;
		}

		return $this->CacheStorage["STATUS_LIST"][intval($XML_ID)] = $arStatus;
	}

	public function SetStatus($StatusId)
	{
		if(!$this->IsAvailable())
			return false;

		$arStatusList = $this->GetStatusList();
		$arStatusListXML = $this->GetStatusList(true);

		$arPost = CBlogPost::GetList(
			array(),
			array("ID" => $this->IdeaId),
			false,
			false,
			array("ID", CIdeaManagment::UFStatusField)
		)->Fetch();

		$bUpdate = false;
		//Get Status ID from XML List
		if(array_key_exists($StatusId, $arStatusListXML))
			$StatusId = $arStatusListXML[$StatusId]["ID"];
		//Status Exists and not current
		if(array_key_exists($StatusId, $arStatusList))
			$bUpdate = $arPost[CIdeaManagment::UFStatusField] != $StatusId;

		if($arPost && $bUpdate)
			return CBlogPost::Update(
				$this->IdeaId,
				array(
					CIdeaManagment::UFStatusField => $StatusId,
				)
			);

		return false;
	}

	//%TODO%
	static public function BindDuplicate(){}
	//%TODO%
	static public function UnBindDuplicate(){}
}
?>