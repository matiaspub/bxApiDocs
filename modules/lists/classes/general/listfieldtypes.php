<?
IncludeModuleLangFile(__FILE__);

class CListFieldTypeList
{
	private static $types = array();

	static function IsExists($type_id)
	{
		self::_init();
		return isset(self::$types[$type_id]);
	}

	static function GetByID($type_id)
	{
		self::_init();
		if(isset(self::$types[$type_id]))
			return self::$types[$type_id];
		else
			return false;
	}

	static function IsField($type_id)
	{
		self::_init();
		if(isset(self::$types[$type_id]))
			return self::$types[$type_id]->IsField();
		else
			return false;
	}

	static function GetTypesNames()
	{
		static $type_names = array();

		if(count($type_names) == 0)
		{
			self::_init();
			foreach(self::$types as $type_id => $obType)
				$type_names[$type_id] = $obType->GetName();
		}

		return $type_names;
	}


	static private function _init()
	{
		if(count(self::$types) == 0)
		{
			self::$types = array();
			//Element fields
			self::$types["NAME"] = new CListFieldType("NAME", GetMessage("LISTS_LIST_FIELD_NAME"), CListFieldType::IS_FIELD, CListFieldType::NOT_READONLY);
			self::$types["SORT"] = new CListFieldType("SORT", GetMessage("LISTS_LIST_FIELD_SORT"), CListFieldType::IS_FIELD, CListFieldType::NOT_READONLY);
			self::$types["ACTIVE_FROM"] = new CListFieldType("ACTIVE_FROM", GetMessage("LISTS_LIST_FIELD_ACTIVE_FROM"), CListFieldType::IS_FIELD, CListFieldType::NOT_READONLY);
			self::$types["ACTIVE_TO"] = new CListFieldType("ACTIVE_TO", GetMessage("LISTS_LIST_FIELD_ACTIVE_TO"), CListFieldType::IS_FIELD, CListFieldType::NOT_READONLY);
			self::$types["PREVIEW_PICTURE"] = new CListFieldType("PREVIEW_PICTURE", GetMessage("LISTS_LIST_FIELD_PREVIEW_PICTURE"), CListFieldType::IS_FIELD, CListFieldType::NOT_READONLY);
			self::$types["PREVIEW_TEXT"] = new CListFieldType("PREVIEW_TEXT", GetMessage("LISTS_LIST_FIELD_PREVIEW_TEXT"), CListFieldType::IS_FIELD, CListFieldType::NOT_READONLY);
			self::$types["DETAIL_PICTURE"] = new CListFieldType("DETAIL_PICTURE", GetMessage("LISTS_LIST_FIELD_DETAIL_PICTURE"), CListFieldType::IS_FIELD, CListFieldType::NOT_READONLY);
			self::$types["DETAIL_TEXT"] = new CListFieldType("DETAIL_TEXT", GetMessage("LISTS_LIST_FIELD_DETAIL_TEXT"), CListFieldType::IS_FIELD, CListFieldType::NOT_READONLY);
			self::$types["DATE_CREATE"] = new CListFieldType("DATE_CREATE", GetMessage("LISTS_LIST_FIELD_DATE_CREATE"), CListFieldType::IS_FIELD, CListFieldType::IS_READONLY);
			self::$types["CREATED_BY"] = new CListFieldType("CREATED_BY", GetMessage("LISTS_LIST_FIELD_CREATED_BY"), CListFieldType::IS_FIELD, CListFieldType::IS_READONLY);
			self::$types["TIMESTAMP_X"] = new CListFieldType("TIMESTAMP_X", GetMessage("LISTS_LIST_FIELD_TIMESTAMP_X"), CListFieldType::IS_FIELD, CListFieldType::IS_READONLY);
			self::$types["MODIFIED_BY"] = new CListFieldType("MODIFIED_BY", GetMessage("LISTS_LIST_FIELD_MODIFIED_BY"), CListFieldType::IS_FIELD, CListFieldType::IS_READONLY);
			//Property types
			self::$types["S"] = new CListFieldType("S", GetMessage("LISTS_LIST_FIELD_S"), CListFieldType::NOT_FIELD, CListFieldType::NOT_READONLY);
			self::$types["N"] = new CListFieldType("N", GetMessage("LISTS_LIST_FIELD_N"), CListFieldType::NOT_FIELD, CListFieldType::NOT_READONLY);
			self::$types["L"] = new CListFieldType("L", GetMessage("LISTS_LIST_FIELD_L"), CListFieldType::NOT_FIELD, CListFieldType::NOT_READONLY);
			self::$types["F"] = new CListFieldType("F", GetMessage("LISTS_LIST_FIELD_F"), CListFieldType::NOT_FIELD, CListFieldType::NOT_READONLY);
			self::$types["G"] = new CListFieldType("G", GetMessage("LISTS_LIST_FIELD_G"), CListFieldType::NOT_FIELD, CListFieldType::NOT_READONLY);
			self::$types["E"] = new CListFieldType("E", GetMessage("LISTS_LIST_FIELD_E"), CListFieldType::NOT_FIELD, CListFieldType::NOT_READONLY);
			//User types
			$type = CIBlockProperty::GetUserType();
			if ($type)
			{
				foreach($type as  $ar)
				{
					if($ar && array_key_exists("GetPublicEditHTML", $ar))
					{
						$typeId = $ar["PROPERTY_TYPE"].":".$ar["USER_TYPE"];
						self::$types[$typeId] = new CListFieldType($typeId, $ar["DESCRIPTION"], CListFieldType::NOT_FIELD, CListFieldType::NOT_READONLY);
					}
				}
			}
		}
	}
}
?>