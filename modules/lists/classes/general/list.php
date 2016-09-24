<?
IncludeModuleLangFile(__FILE__);

class CList
{
	/** @var CListFieldList */
	var $fields = null;
	var $new_ids = array();
	protected $iblock_id = 0;

	public function __construct($iblock_id)
	{
		$this->iblock_id = intval($iblock_id);
		$this->fields = new CListFieldList($iblock_id);
	}

	public static function is_field($type_id)
	{
		return CListFieldTypeList::IsField($type_id);
	}

	public function is_readonly($field_id)
	{
		$obField = $this->fields->GetByID($field_id);
		if(is_object($obField))
			return $obField->IsReadonly();
		else
			return false;
	}

	public function GetFields()
	{
		$arFields = array();

		foreach($this->fields->GetFields() as $FIELD_ID)
			$arFields[$FIELD_ID] = $this->fields->GetArrayByID($FIELD_ID);

		return $arFields;
	}

	public static function GetAllTypes()
	{
		return CListFieldTypeList::GetTypesNames();
	}

	public function GetAvailableTypes($ID = "")
	{
		$arTypeNames = CListFieldTypeList::GetTypesNames();
		foreach($this->fields->GetFields() as $FIELD_ID)
			if($FIELD_ID != $ID)
				unset($arTypeNames[$FIELD_ID]);
		return $arTypeNames;
	}

	public function DeleteField($field_id)
	{
		return $this->fields->DeleteField($field_id);
	}

	public function AddField($arFields)
	{
		return $this->fields->AddField($arFields);
	}

	public function GetNewID($TEMP_ID)
	{
		return $this->new_ids[$TEMP_ID];
	}

	public function UpdateField($field_id, $arFields)
	{
		$arFields["NAME"] = trim($arFields["NAME"], " \n\r\t");
		$this->new_ids[$field_id] = $this->fields->UpdateField($field_id, $arFields);
		return $this->new_ids[$field_id];
	}

	function Save()
	{
	}

	public static function UpdatePropertyList($prop_id, $list)
	{
		foreach($list as $id => $arEnum)
		{
			$value = trim($arEnum["VALUE"], " \t\n\r");
			if(strlen($value))
			{
				$dbEnum = CIBlockPropertyEnum::GetByID($id);
				if(is_array($dbEnum))
				{
					$def = isset($arEnum["DEF"])? $arEnum["DEF"]: $dbEnum["DEF"];
					$sort = intval($arEnum["SORT"]);
					if(
						$dbEnum["VALUE"] != $value
						|| $dbEnum["SORT"] != $sort
						|| $dbEnum["DEF"] != $def
					)
					{
						$dbEnum["VALUE"] = $value;
						$dbEnum["SORT"] = $sort;
						$dbEnum["DEF"] = $def;
						unset($dbEnum["ID"]);
						CIBlockPropertyEnum::Update($id, $dbEnum);
					}
				}
				else
				{
					$arEnum["PROPERTY_ID"] = $prop_id;
					CIBlockPropertyEnum::Add($arEnum);
				}
			}
			else
			{
				CIBlockPropertyEnum::Delete($id);
			}
		}
	}

	public function ActualizeDocumentAdminPage($url)
	{
		global $DB;
		static $urlCache = array();

		if(!array_key_exists($this->iblock_id, $urlCache))
		{
			$rs = $DB->Query("SELECT URL FROM b_lists_url WHERE IBLOCK_ID = ".$this->iblock_id);
			$urlCache[$this->iblock_id] = $rs->Fetch();
		}

		if($urlCache[$this->iblock_id])
		{
			if($urlCache[$this->iblock_id]["URL"] != $url)
				$DB->Query("UPDATE b_lists_url SET URL = '".$DB->ForSQL($url)."' WHERE IBLOCK_ID = ".$this->iblock_id);
		}
		else
		{
			$DB->Query("INSERT INTO b_lists_url (IBLOCK_ID, URL) values (".$this->iblock_id.", '".$DB->ForSQL($url)."')");
		}

		$urlCache[$this->iblock_id] = array("URL" => $url);
	}

	public static function OnGetDocumentAdminPage($arElement)
	{
		$url = self::getUrlByIblockId($arElement["IBLOCK_ID"]);
		if ($url != "")
		{
			return str_replace(
				array("#section_id#", "#element_id#"),
				array(intval($arElement["IBLOCK_SECTION_ID"]), intval($arElement["ID"])),
				$url
			);
		}
		return "";
	}

	public static function OnSearchGetURL($arFields)
	{

		if (
			$arFields["MODULE_ID"] === "iblock"
			&& $arFields["ITEM_ID"] > 0
			&& substr($arFields["URL"], 0, 1) === "="
		)
		{
			$url = self::getUrlByIblockId($arFields["PARAM2"]);
			if ($url != "")
			{
				$arElement = array();
				parse_str(substr($arFields["URL"], 1), $arElement);

				return str_replace(
					array("#section_id#", "#element_id#"),
					array(intval($arElement["IBLOCK_SECTION_ID"]), intval($arElement["ID"])),
					$url
				);
			}
		}

		return $arFields["URL"];
	}

	public static function getUrlByIblockId($IBLOCK_ID)
	{
		global $DB;
		static $cache = array();
		$IBLOCK_ID = intval($IBLOCK_ID);

		if (!isset($cache[$IBLOCK_ID]))
		{
			$rs = $DB->Query("SELECT URL FROM b_lists_url WHERE IBLOCK_ID = ".$IBLOCK_ID);
			$cache[$IBLOCK_ID] = $rs->Fetch();
		}

		if ($cache[$IBLOCK_ID])
			return $cache[$IBLOCK_ID]["URL"];
		else
			return "";
	}
}
?>