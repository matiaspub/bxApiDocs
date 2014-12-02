<?
IncludeModuleLangFile(__FILE__);

class CListPermissions
{
	const WRONG_IBLOCK_TYPE = -1;
	const WRONG_IBLOCK = -2;
	const LISTS_FOR_SONET_GROUP_DISABLED = -3;

	const ACCESS_DENIED = 'D';
	const CAN_READ = 'R';
	const CAN_BIZPROC = 'U';
	const CAN_WRITE = 'W';
	const IS_ADMIN = 'X';

	/**
	 * @param $USER CUser
	 * @param $iblock_type_id string
	 * @param bool $iblock_id int
	 * @param int $socnet_group_id int
	 * @return int|string
	 */
	static public function CheckAccess($USER, $iblock_type_id, $iblock_id = false, $socnet_group_id = 0)
	{
		if($socnet_group_id > 0 && CModule::IncludeModule('socialnetwork'))
		{
			if(CSocNetFeatures::IsActiveFeature(SONET_ENTITY_GROUP, $socnet_group_id, "group_lists"))
			{
				if($iblock_id !== false)
					return CListPermissions::_socnet_check($USER, $iblock_type_id, $iblock_id, intval($socnet_group_id));
				else
					return CListPermissions::_socnet_type_check($USER, $iblock_type_id, $socnet_group_id);
			}
			else
			{
				return CListPermissions::LISTS_FOR_SONET_GROUP_DISABLED;
			}
		}
		else
		{
			if($iblock_id !== false)
				return CListPermissions::_lists_check($USER, $iblock_type_id, $iblock_id);
			else
				return CListPermissions::_lists_type_check($USER, $iblock_type_id);
		}
	}

	/**
	 * @param $USER CUser
	 * @param $iblock_type_id string
	 * @param $iblock_id int
	 * @param $socnet_group_id int
	 * @return string
	 */
	static protected function _socnet_check($USER, $iblock_type_id, $iblock_id, $socnet_group_id)
	{
		$type_check = CListPermissions::_socnet_type_check($USER, $iblock_type_id, $socnet_group_id);
		if($type_check < 0)
			return $type_check;

		$iblock_check = CListPermissions::_iblock_check($iblock_type_id, $iblock_id);
		if($iblock_check < 0)
			return $iblock_check;

		$iblock_socnet_group_id = CIBlock::GetArrayByID($iblock_id, "SOCNET_GROUP_ID");
		if($iblock_socnet_group_id != $socnet_group_id)
			return CListPermissions::ACCESS_DENIED;

		$socnet_role = CSocNetUserToGroup::GetUserRole($USER->GetID(), $socnet_group_id);

		if($socnet_role !== "A" && CIBlock::GetArrayByID($iblock_id, "RIGHTS_MODE") === "E")
			return '';

		static $roles = array("A", "E", "K", "T");
		if(!in_array($socnet_role, $roles))
		{
			if($USER->IsAuthorized())
				$socnet_role = "L";
			else
				$socnet_role = "N";
		}

		if (!CSocNetFeaturesPerms::CanPerformOperation($USER->GetID(), SONET_ENTITY_GROUP, $socnet_group_id, "group_lists", "write", CSocNetUser::IsCurrentUserModuleAdmin()))
		{
			return "D";
		}
		else
		{
			$arSocnetPerm = CLists::GetSocnetPermission($iblock_id);
			return $arSocnetPerm[$socnet_role];
		}
	}

	/**
	 * @param $USER CUser
	 * @param $iblock_type_id string
	 * @param $socnet_group_id int
	 * @return int|string
	 */
	static protected function _socnet_type_check($USER, $iblock_type_id, $socnet_group_id)
	{
		if($iblock_type_id === COption::GetOptionString("lists", "socnet_iblock_type_id"))
		{
			$socnet_role = CSocNetUserToGroup::GetUserRole($USER->GetID(), $socnet_group_id);

			if (
				$socnet_role == "A"
				&& CSocNetFeaturesPerms::CanPerformOperation($USER->GetID(), SONET_ENTITY_GROUP, $socnet_group_id, "group_lists", "write", CSocNetUser::IsCurrentUserModuleAdmin())
			)
			{
				return CListPermissions::IS_ADMIN;
			}
			else
			{
				return CListPermissions::CAN_READ;
			}
		}
		else
		{
			return CListPermissions::WRONG_IBLOCK_TYPE;
		}
	}

	/**
	 * @param $USER CUser
	 * @param $iblock_type_id string
	 * @return string
	 */
	static protected function _lists_type_check($USER, $iblock_type_id)
	{
		$arListsPerm = CLists::GetPermission($iblock_type_id);
		if(!count($arListsPerm))
			return CListPermissions::ACCESS_DENIED;

		$arUSER_GROUPS = $USER->GetUserGroupArray();
		if(count(array_intersect($arListsPerm, $arUSER_GROUPS)) > 0)
			return CListPermissions::IS_ADMIN;

		return CListPermissions::CAN_READ;
	}

	/**
	 * @param $USER CUser
	 * @param $iblock_type_id string
	 * @param $iblock_id int
	 * @return int|string
	 */
	static protected function _lists_check($USER, $iblock_type_id, $iblock_id)
	{
		$iblock_check = CListPermissions::_iblock_check($iblock_type_id, $iblock_id);
		if($iblock_check < 0)
			return $iblock_check;

		$arListsPerm = CLists::GetPermission($iblock_type_id);
		if(!count($arListsPerm))
			return CListPermissions::ACCESS_DENIED;

		$arUSER_GROUPS = $USER->GetUserGroupArray();
		if(count(array_intersect($arListsPerm, $arUSER_GROUPS)) > 0)
			return CListPermissions::IS_ADMIN;

		return CIBlock::GetPermission($iblock_id);
	}

	/**
	 * @param $iblock_type_id string
	 * @param $iblock_id int
	 * @return int
	 */
	static protected function _iblock_check($iblock_type_id, $iblock_id)
	{
		$iblock_id = intval($iblock_id);
		if($iblock_id > 0)
		{
			$iblock_type = CIBlock::GetArrayByID($iblock_id, "IBLOCK_TYPE_ID");
			if($iblock_type_id === $iblock_type)
				return 0;
			else
				return CListPermissions::WRONG_IBLOCK;
		}
		else
		{
			return CListPermissions::WRONG_IBLOCK;
		}
	}

	static public function MergeRights($IBLOCK_TYPE_ID, $DB, $POST)
	{
		$arResult = array();

		//1) Put into result protected from changes rights
		$arListsPerm = CLists::GetPermission($IBLOCK_TYPE_ID);
		foreach($DB as $RIGHT_ID => $arRight)
		{
			//1) protect groups from module settings
			if(
				preg_match("/^G(\\d)\$/", $arRight["GROUP_CODE"], $match)
				&& is_array($arListsPerm)
				&& in_array($match[1], $arListsPerm)
			)
				$arResult[$RIGHT_ID] = $arRight;
			else
			{
				//2) protect groups with iblock_% operations
				$arOperations = CTask::GetOperations($arRight['TASK_ID'], true);
				foreach($arOperations as $operation)
				{
					if(preg_match("/^iblock_(?!admin)/", $operation))
					{
						$arResult[$RIGHT_ID] = $arRight;
						break;
					}
				}
			}
		}

		//2) Leave in POST only safe rights
		foreach($POST as $RIGHT_ID => $arRight)
		{
			//1) protect groups from module settings
			if(
				preg_match("/^G(\\d)\$/", $arRight["GROUP_CODE"], $match)
				&& is_array($arListsPerm)
				&& in_array($match[1], $arListsPerm)
			)
				unset($POST[$RIGHT_ID]);
			else
			{
				//2) protect groups with iblock_% operations
				$arOperations = CTask::GetOperations($arRight['TASK_ID'], true);
				foreach($arOperations as $operation)
				{
					if(preg_match("/^iblock_(?!admin)/", $operation))
					{
						unset($POST[$RIGHT_ID]);
						break;
					}
				}
			}
		}

		//3) Join POST to result
		foreach($POST as $RIGHT_ID => $arRight)
		{
			foreach($arResult as $RIGHT_ID2 => $arRight2)
			{
				if($arRight["GROUP_CODE"] == $arRight2["GROUP_CODE"])
					unset($arResult[$RIGHT_ID2]);
			}
			$arResult[$RIGHT_ID] = $arRight;
		}

		return $arResult;
	}

	/**
	 * @param $iblockId int
	 * @param $fieldId int
	 */
	public static function CheckFieldId($iblock_id, $field_id)
	{
		if ($field_id === "DETAIL_PICTURE")
			return true;
		elseif ($field_id === "PREVIEW_PICTURE")
			return true;
		elseif ($field_id === "PICTURE")
			return true;
		elseif ($iblock_id <= 0)
			return false;
		elseif (!preg_match("/^PROPERTY_(.+)\$/", $field_id, $match))
			return false;
		else
		{
			$db_prop = CIBlockProperty::GetPropertyArray($match[1], $iblock_id);
			if(is_array($db_prop) && $db_prop["PROPERTY_TYPE"] === "F")
				return true;
		}
		return false;
	}
}
?>