<?
IncludeModuleLangFile(__FILE__);

abstract class CListField
{
	/** @var int */
	protected $_iblock_id;
	/** @var  string */
	protected $_field_id;
	/** @var  string */
	protected $_label;
	/** @var CListFieldType */
	protected $_type;
	/** @var int */
	protected $_sort;

	private static $prop_cache = array();

	public function __construct($iblock_id, $field_id, $label, $sort)
	{
		global $DB;

		$this->_iblock_id = intval($iblock_id);
		$this->_field_id = $field_id;
		$this->_label = $label;
		$this->_sort = intval($sort);

		if($this->_iblock_id > 0 && strlen($this->_field_id))
		{
			$arField = $this->_read_from_cache($this->_field_id);
			if(!$arField)
			{
				$DB->Add("b_lists_field", array(
					"ID" => 1, //This makes Oracle version happy
					"IBLOCK_ID" => $this->_iblock_id,
					"FIELD_ID" => $this->_field_id,
					"SORT" => $this->_sort,
					"NAME" => $this->_label,
				));
				$this->_clear_cache();
			}
			elseif(
				$arField["SORT"] != $this->_sort
				|| $arField["NAME"] != $this->_label
			)
			{
				$DB->Query("
					UPDATE b_lists_field
					SET SORT = ".$this->_sort."
					,NAME = '".$DB->ForSQL($this->_label)."'
					WHERE IBLOCK_ID = ".$this->_iblock_id."
					AND FIELD_ID = '".$DB->ForSQL($this->_field_id)."'
				", false, "File: ".__FILE__."<br>Line: ".__LINE__);
				$this->_clear_cache();
			}
		}
	}

	private function _read_from_cache($field_id)
	{
		global $DB;

		if($this->_iblock_id > 0 && !isset(self::$prop_cache[$this->_iblock_id]))
		{
			$rsFields = $DB->Query("
				SELECT * FROM b_lists_field
				WHERE IBLOCK_ID = ".$this->_iblock_id."
			", false, "File: ".__FILE__."<br>Line: ".__LINE__);

			self::$prop_cache[$this->_iblock_id] = array();
			while($arField = $rsFields->Fetch())
				self::$prop_cache[$this->_iblock_id][$arField["FIELD_ID"]] = $arField;
		}

		if(isset(self::$prop_cache[$this->_iblock_id][$field_id]))
			return self::$prop_cache[$this->_iblock_id][$field_id];
		else
			return false;
	}

	private function _clear_cache()
	{
		if(isset(self::$prop_cache[$this->_iblock_id]))
			unset(self::$prop_cache[$this->_iblock_id]);
	}

	public function GetID()
	{
		return $this->_field_id;
	}

	public function GetLabel()
	{
		return $this->_label;
	}

	public function GetTypeID()
	{
		return $this->_type->GetID();
	}

	public function IsReadOnly()
	{
		return $this->_type->IsReadonly();
	}

	public function GetSort()
	{
		return $this->_sort;
	}

	public function GetSettingsDefaults()
	{
		switch($this->_field_id)
		{
		case "PREVIEW_TEXT":
			return array(
				"USE_EDITOR" => "N",
				"WIDTH" => "40",
				"HEIGHT" => "3",
			);
		default:
			return false;
		}
	}

	public function GetSettings()
	{
		$arField = $this->_read_from_cache($this->_field_id);
		if($arField)
		{
			$res = unserialize($arField["SETTINGS"]);
			if(is_array($res))
				return $res;
		}
		return $this->GetSettingsDefaults();
	}

	public function SetSettings($arSettings)
	{
		global $DB;

		$arStore = false;
		switch($this->_field_id)
		{
			case "PREVIEW_TEXT":
				if(preg_match('/\s*(\d+)\s*(px|%|)/', $arSettings["WIDTH"], $match) && ($match[1] > 0))
					$width = $match[1].$match[2];
				else
					$width = "40";

				if(preg_match('/\s*(\d+)\s*(px|%|)/', $arSettings["HEIGHT"], $match) && ($match[1] > 0))
					$height = $match[1].$match[2];
				else
					$height = "3";

				$arStore = array(
					"USE_EDITOR" => $arSettings["USE_EDITOR"]=="Y"? "Y": "N",
					"WIDTH" => $width,
					"HEIGHT" => $height,
					"SHOW_ADD_FORM" => $arSettings["SHOW_ADD_FORM"],
					"SHOW_EDIT_FORM" => $arSettings["SHOW_EDIT_FORM"]
				);
				break;
			default:
				$arStore = $arSettings;
		}

		$arFields = array();
		if(is_array($arStore))
			$arFields["SETTINGS"] = serialize($arStore);
		else
			$arFields["SETTINGS"] = false;

		$strUpdate = $DB->PrepareUpdate("b_lists_field", $arFields);
		if($strUpdate!="")
		{
			$strSql = "
				UPDATE b_lists_field
				SET ".$strUpdate."
				WHERE IBLOCK_ID = ".$this->_iblock_id."
				AND FIELD_ID = '".$DB->ForSQL($this->_field_id)."'
			";
			$arBinds = array(
				"SETTINGS" => $arFields["SETTINGS"],
			);
			$DB->QueryBind($strSql, $arBinds);

			$this->_clear_cache();
		}
	}

	abstract public function IsRequired();
	abstract public function IsMultiple();
	abstract public function GetDefaultValue();
	abstract public function SetSort($sort);
	abstract public function GetArray();

	public function Delete()
	{
		global $DB;
		$DB->Query("
			DELETE FROM b_lists_field
			WHERE IBLOCK_ID = ".$this->_iblock_id."
			AND FIELD_ID = '".$DB->ForSQL($this->_field_id)."'
		", false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	abstract public function Update($arFields);
	static public function Add($iblock_id, $arFields)
	{
	}
}

class CListElementField extends CListField
{
	private $_iblock_field;

	public function __construct($iblock_id, $field_id, $label, $sort)
	{
		parent::__construct($iblock_id, $field_id, $label, $sort);

		$this->_type = CListFieldTypeList::GetByID($field_id);

		if($this->_iblock_id > 0)
			$arIBlockFields = CIBlock::GetArrayByID($this->_iblock_id, "FIELDS");
		else
			$arIBlockFields = CIBlock::GetFieldsDefaults();

		$this->_iblock_field = $arIBlockFields[$field_id];
	}

	public function IsRequired()
	{
		return $this->_iblock_field["IS_REQUIRED"] == "Y";
	}

	static public function IsMultiple()
	{
		return false;
	}

	public function GetDefaultValue()
	{
		return $this->_iblock_field["DEFAULT_VALUE"];
	}

	public function SetSort($sort)
	{
		$this->_sort = intval($sort);
	}

	//This is only backward compatibility method
	public function GetArray()
	{
		return array(
			"SORT" => $this->_sort,
			"NAME" => $this->_label,
			"IS_REQUIRED" => $this->_iblock_field["IS_REQUIRED"],
			"MULTIPLE" => "N",
			"DEFAULT_VALUE" => $this->_iblock_field["DEFAULT_VALUE"],
			"TYPE" => $this->GetTypeID(),
			"PROPERTY_TYPE" => false,
			"PROPERTY_USER_TYPE" => false,
			"SETTINGS" => $this->GetSettings(),
		);
	}

	public function Delete()
	{
		/** @global CStackCacheManager $stackCacheManager */
		global $stackCacheManager;
		if($this->_iblock_field["IS_REQUIRED"] == "Y")
		{
			if($this->_iblock_id > 0)
			{
				$arIBlockFields = CIBlock::GetArrayByID($this->_iblock_id, "FIELDS");
				$arIBlockFields[$this->_field_id]["IS_REQUIRED"] = "N";
				CIBlock::SetFields($this->_iblock_id, $arIBlockFields);
				$stackCacheManager->Clear("b_iblock");
			}
			$this->_iblock_field["IS_REQUIRED"] = "N";
		}

		parent::Delete();
		return true;
	}

	public function Update($arFields)
	{
		/** @global CStackCacheManager $stackCacheManager */
		global $stackCacheManager;
		if(isset($arFields["TYPE"]))
			$newType = $arFields["TYPE"];
		else
			$newType = $this->GetTypeID();

		if($this->_iblock_id > 0 && CListFieldTypeList::IsField($newType))
		{
			$arIBlockFields = CIBlock::GetArrayByID($this->_iblock_id, "FIELDS");
			$arIBlockFields[$newType] = $arFields;
			CIBlock::SetFields($this->_iblock_id, $arIBlockFields);
			$stackCacheManager->Clear("b_iblock");

			if($newType != $this->GetTypeID())
				$this->Delete();

			return new CListElementField($this->_iblock_id, $newType, $arFields["NAME"], $arFields["SORT"]);
		}

		return null;
	}

	static public function Add($iblock_id, $arFields)
	{
		/** @global CStackCacheManager $stackCacheManager */
		global $stackCacheManager;
		if($iblock_id > 0)
		{
			$arIBlockFields = CIBlock::GetArrayByID($iblock_id, "FIELDS");
			$arIBlockFields[$arFields["TYPE"]] = $arFields;
			CIBlock::SetFields($iblock_id, $arIBlockFields);
			$stackCacheManager->Clear("b_iblock");
		}
		return new CListElementField($iblock_id, $arFields["TYPE"], $arFields["NAME"], $arFields["SORT"]);
	}
}

class CListPropertyField extends CListField
{
	private $_property = false;
	private static $prop_cache = array();

	public function __construct($iblock_id, $field_id, $label, $sort)
	{
		parent::__construct($iblock_id, $field_id, $label, $sort);

		if(preg_match("/^PROPERTY_(\\d+)$/", $field_id, $match))
		{
			$this->_property = $this->getPropertyArrayFromCache($match[1]);
		}

		if ($this->_property)
		{
			if($this->_property["USER_TYPE"])
				$this->_type = CListFieldTypeList::GetByID($this->_property["PROPERTY_TYPE"].":".$this->_property["USER_TYPE"]);
			else
				$this->_type = CListFieldTypeList::GetByID($this->_property["PROPERTY_TYPE"]);
		}

		if(!is_object($this->_type))
			$this->_type = CListFieldTypeList::GetByID("S");
	}

	private function getPropertyArrayFromCache($id)
	{
		//Cache iblock metadata in order to reduce queries
		if(!array_key_exists($this->_iblock_id, self::$prop_cache))
		{
			self::$prop_cache[$this->_iblock_id] = array();

			$rsProperties = CIBlockProperty::GetList(array(), array(
				"IBLOCK_ID" => $this->_iblock_id,
				"CHECK_PERMISSIONS" => "N",
				"ACTIVE" => "Y",
			));
			while($arProperty = $rsProperties->Fetch())
				self::$prop_cache[$this->_iblock_id][$arProperty["ID"]] = $arProperty;
		}
		return self::$prop_cache[$this->_iblock_id][$id];
	}

	private static function resetPropertyArrayCache()
	{
		self::$prop_cache = array();
	}

	public function IsRequired()
	{
		return is_array($this->_property) && $this->_property["IS_REQUIRED"] == "Y";
	}

	public function IsMultiple()
	{
		return is_array($this->_property) && $this->_property["MULTIPLE"] == "Y";
	}

	public function GetDefaultValue()
	{
		return is_array($this->_property) && $this->_property["DEFAULT_VALUE"];
	}

	public function SetSort($sort)
	{
		if(is_array($this->_property))
		{
			$old_sort = intval($this->_property["SORT"]);
			$new_sort = intval($sort);

			if($old_sort != $new_sort)
			{
				$this->_property["SORT"] = $new_sort;
				$obProperty = new CIBlockProperty;
				$obProperty->Update($this->_property["ID"], $this->_property);
				$this->_sort = $new_sort;
			}
		}
	}

	//This is only backward compatibility method
	public function GetArray()
	{
		if(is_array($this->_property))
		{
			return array(
				"SORT" => $this->_sort,
				"NAME" => $this->_property["NAME"],
				"IS_REQUIRED" => $this->_property["IS_REQUIRED"],
				"MULTIPLE" => $this->_property["MULTIPLE"],
				"DEFAULT_VALUE" => $this->_property["DEFAULT_VALUE"],
				"TYPE" => $this->GetTypeID(),
				"PROPERTY_TYPE" => $this->_property["PROPERTY_TYPE"],
				"PROPERTY_USER_TYPE" => $this->_property["USER_TYPE"]? CIBlockProperty::GetUserType($this->_property["USER_TYPE"]): false,
				"CODE" => $this->_property["CODE"],
				"ID" => $this->_property["ID"],
				"LINK_IBLOCK_ID" => $this->_property["LINK_IBLOCK_ID"],
				"ROW_COUNT" =>  $this->_property["ROW_COUNT"],
				"COL_COUNT" =>  $this->_property["COL_COUNT"],
				"USER_TYPE_SETTINGS" => $this->_property["USER_TYPE_SETTINGS"],
				"SETTINGS" => $this->GetSettings(),
			);
		}
		else
		{
			return false;
		}
	}

	public function Delete()
	{
		if(is_array($this->_property))
		{
			$obProperty = new CIBlockProperty;
			if($obProperty->Delete($this->_property["ID"]))
			{
				$this->resetPropertyArrayCache();
				$this->_property = false;
			}
		}

		parent::Delete();
		return true;
	}

	public function Update($arFields)
	{
		if(isset($arFields["TYPE"]))
			$newType = $arFields["TYPE"];
		else
			$newType = $this->GetTypeID();

		if(is_array($this->_property) && !CListFieldTypeList::IsField($newType))
		{
			foreach($this->GetArray() as $id => $val)
				if(array_key_exists($id, $arFields) && $id != "IBLOCK_ID")
					$this->_property[$id] = $arFields[$id];

			if(strpos($newType, ":")!==false)
				list($this->_property["PROPERTY_TYPE"], $this->_property["USER_TYPE"]) = explode(":", $newType);
			else
			{
				$this->_property["PROPERTY_TYPE"] = $newType;
				$this->_property["USER_TYPE"] = "";
			}

			$this->_property["CHECK_PERMISSIONS"] = "N";
			$this->_property["ACTIVE"] = "Y";

			$obProperty = new CIBlockProperty;
			if($obProperty->Update($this->_property["ID"], $this->_property))
			{
				self::resetPropertyArrayCache();

				if($this->_property["PROPERTY_TYPE"] == "L" && is_array($arFields["LIST"]))
					CList::UpdatePropertyList($this->_property["ID"], $arFields["LIST"]);

				return new CListPropertyField($this->_property["IBLOCK_ID"], "PROPERTY_".$this->_property["ID"], $arFields["NAME"], $arFields["SORT"]);
			}
		}

		return null;
	}

	static public function Add($iblock_id, $arFields)
	{
		if($iblock_id > 0)
		{
			$property_id = intval($arFields["ID"]);
			if($property_id > 0)
			{
				return new CListPropertyField($iblock_id, "PROPERTY_".$property_id, $arFields["NAME"], $arFields["SORT"]);
			}
			else
			{
				$arFields["IBLOCK_ID"] = $iblock_id;
				if(strpos($arFields["TYPE"], ":")!==false)
					list($arFields["PROPERTY_TYPE"], $arFields["USER_TYPE"]) = explode(":", $arFields["TYPE"]);
				else
					$arFields["PROPERTY_TYPE"] = $arFields["TYPE"];
				$arFields["MULTIPLE_CNT"] = 1;
				$arFields["CHECK_PERMISSIONS"] = "N";
				$arFields["CODE"] = $arFields["CODE"] ? $arFields["CODE"] : CLists::generateMnemonicCode();

				$obProperty = new CIBlockProperty;
				$res = $obProperty->Add($arFields);
				if($res)
				{
					self::resetPropertyArrayCache();

					if($arFields["PROPERTY_TYPE"] == "L" && is_array($arFields["LIST"]))
						CList::UpdatePropertyList($res, $arFields["LIST"]);

					return new CListPropertyField($iblock_id, "PROPERTY_".$res, $arFields["NAME"], $arFields["SORT"]);
				}
			}
		}
		return null;
	}
}
?>