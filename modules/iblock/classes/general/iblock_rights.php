<?
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/admin/task_description.php");

class CIBlockRights
{
	const GROUP_CODE = 1;
	const RIGHT_ID = 2;
	const TASK_ID = 3;

	const ANY_OPERATION = 1;
	const ALL_OPERATIONS = 2;
	const RETURN_OPERATIONS = 4;

	protected $IBLOCK_ID = 0;
	protected $id = 0;
	protected static $arLetterToTask = null;
	protected static $arLetterToOperations = null;

	function __construct($IBLOCK_ID)
	{
		$this->IBLOCK_ID = intval($IBLOCK_ID);
		$this->id = $this->IBLOCK_ID;
	}

	function GetIBlockID()
	{
		return $this->IBLOCK_ID;
	}

	function GetID()
	{
		return $this->id;
	}

	function _entity_type()
	{
		return "iblock";
	}

	function _self_check()
	{
		return $this->IBLOCK_ID == $this->id;
	}

	function Post2Array($ar)
	{
		$arRights = array();
		$RIGHT_ID = "";
		$i = 0;
		foreach($ar as $arRight)
		{
			if(isset($arRight["RIGHT_ID"]))
			{
				if(strlen($arRight["RIGHT_ID"]) > 0)
					$RIGHT_ID = $arRight["RIGHT_ID"];
				else
					$RIGHT_ID = "n".$i++;

				$arRights[$RIGHT_ID] = array(
					"GROUP_CODE" => "",
					"DO_CLEAN" => "N",
					"TASK_ID" => 0,
				);
			}
			elseif(isset($arRight["GROUP_CODE"]))
			{
				$arRights[$RIGHT_ID]["GROUP_CODE"] = $arRight["GROUP_CODE"];
			}
			elseif(isset($arRight["DO_CLEAN"]))
			{
				$arRights[$RIGHT_ID]["DO_CLEAN"] = $arRight["DO_CLEAN"] == "Y"? "Y": "N";
			}
			elseif(isset($arRight["TASK_ID"]))
			{
				$arRights[$RIGHT_ID]["TASK_ID"] = $arRight["TASK_ID"];
			}
		}

		foreach($arRights as $RIGHT_ID => $arRightSet)
		{
			if(substr($RIGHT_ID, 0, 1) == "n")
			{
				if(strlen($arRightSet["GROUP_CODE"]) <= 0)
					unset($arRights[$RIGHT_ID]);
				elseif($arRightSet["TASK_ID"] > 0)
				{
					//Mark inherited rights to overwrite
					foreach($arRights as $RIGHT_ID2 => $arRightSet2)
					{
						if(
							$RIGHT_ID2 > 0
							&& $arRightSet2["GROUP_CODE"] === $arRightSet["GROUP_CODE"]
						)
						{
							unset($arRights[$RIGHT_ID2]);
						}
					}
				}
			}
		}

		return $arRights;
	}

	private static function initTaskLetters()
	{
		if(!isset(self::$arLetterToTask))
		{
			$rs = CTask::GetList(
				array("LETTER"=>"asc"),
				array(
					"MODULE_ID" => "iblock",
					"BINDING" => "iblock",
					"SYS" => "Y",
				)
			);
			self::$arLetterToTask = array();
			while($ar = $rs->Fetch())
				self::$arLetterToTask[$ar["LETTER"]] = $ar["ID"];
		}
	}

	static function LetterToTask($letter = '')
	{
		self::initTaskLetters();

		if($letter == '')
			return self::$arLetterToTask;
		elseif(array_key_exists($letter, self::$arLetterToTask))
			return self::$arLetterToTask[$letter];
		else
			return 0;
	}

	static function TaskToLetter($task = 0)
	{
		self::initTaskLetters();

		if($task == 0)
			return array_flip(self::$arLetterToTask);
		else
			return array_search($task, self::$arLetterToTask);
	}

	static function LetterToOperations($letter = '')
	{
		if(!isset(self::$arLetterToOperations))
		{
			self::$arLetterToOperations = array();
			foreach(CIBlockRights::LetterToTask() as $l2 => $TASK_ID)
			{
				self::$arLetterToOperations[$l2] = array();
				foreach(CTask::GetOperations($TASK_ID, true) as $op)
					self::$arLetterToOperations[$l2][$op] = $op;
			}
		}

		if($letter == '')
			return self::$arLetterToOperations;
		elseif(array_key_exists($letter, self::$arLetterToOperations))
			return self::$arLetterToOperations[$letter];
		else
			return array();
	}

	function ConvertGroups($arGroups)
	{
		$i = 0;
		$arRights = array();
		foreach($arGroups as $GROUP_ID => $LETTER)
		{
			$TASK_ID = $this->LetterToTask($LETTER);
			if($TASK_ID > 0)
				$arRights["n".$i] = array(
					"GROUP_CODE" => "G".$GROUP_ID,
					"DO_INHERIT" => "Y",
					"DO_CLEAN" => "N",
					"TASK_ID" => $TASK_ID,
				);
			$i++;
		}

		return $arRights;
	}

	function GetRightsList($bTitle = true)
	{
		global $DB;
		$arResult = array();

		$rs = CTask::GetList(
			array("LETTER"=>"asc"),
			array(
				"MODULE_ID" => "iblock",
			)
		);

		while($ar = $rs->Fetch())
			$arResult[$ar["ID"]] = $bTitle? $ar["TITLE"]: $ar["NAME"];

		return $arResult;
	}

	function GetGroups($arOperations = false, $opMode = false)
	{
		$arResult = array();

		$arRights = $this->GetRights(array("operations" => $arOperations, "operations_mode" => $opMode));
		foreach($arRights as $arRight)
			$arResult[$arRight["GROUP_CODE"]] = $arRight["GROUP_CODE"];

		return $arResult;
	}

	function GetList($arFilter)
	{
		global $DB;

		$obQueryWhere = new CSQLWhere;
		$obQueryWhere->SetFields(array(
			"IBLOCK_ID" => array(
				"TABLE_ALIAS" => "BR",
				"FIELD_NAME" => "BR.IBLOCK_ID",
				"MULTIPLE" => "N",
				"FIELD_TYPE" => "int",
				"JOIN" => false,
			),
			"ITEM_ID" => array(
				"TABLE_ALIAS" => "BR",
				"FIELD_NAME" => "BR.IBLOCK_ID",
				"MULTIPLE" => "N",
				"FIELD_TYPE" => "int",
				"JOIN" => false,
			),
		));

		$strWhere = $obQueryWhere->GetQuery($arFilter);

		return $DB->Query("
			SELECT
				BR.IBLOCK_ID ITEM_ID
				,BR.ID RIGHT_ID
				,BR.GROUP_CODE
				,BR.TASK_ID
				,BR.DO_INHERIT
				,'N' IS_INHERITED
				,BR.XML_ID
			FROM
				b_iblock_right BR
			".($strWhere? "WHERE ".$strWhere: "")."
			ORDER BY
				BR.ID
		");
	}

	function GetRights($arOptions = array())
	{
		global $DB;
		$arResult = array();

		if(
			!isset($arOptions["operations"])
			|| !is_array($arOptions["operations"])
			|| empty($arOptions["operations"])
		)
		{
			$rs = $DB->Query("
				SELECT
					BR.ID
					,BR.GROUP_CODE
					,BR.TASK_ID
					,BR.DO_INHERIT
					,'N' IS_INHERITED
					,BR.XML_ID
					,BR.ENTITY_TYPE
					,BR.ENTITY_ID
				FROM
					b_iblock_right BR
				WHERE
					BR.IBLOCK_ID = ".$this->IBLOCK_ID."
					AND BR.ENTITY_TYPE = 'iblock'
				ORDER BY
					BR.ID
			");
		}
		elseif(
			isset($arOptions["operations_mode"])
			&& $arOptions["operations_mode"] == CIBlockRights::ALL_OPERATIONS
			&& count($arOptions["operations"]) > 1
		)
		{
			$arOperations = array_map(array($DB, "ForSQL"), $arOptions["operations"]);
			$rs = $DB->Query("
				SELECT
					BR.ID, BR.GROUP_CODE, BR.TASK_ID, BR.DO_INHERIT, 'N' IS_INHERITED, BR.XML_ID
				FROM
					b_iblock_right BR
					INNER JOIN b_task_operation T ON T.TASK_ID = BR.TASK_ID
					INNER JOIN b_operation O ON O.ID = T.OPERATION_ID
				WHERE
					BR.IBLOCK_ID = ".$this->IBLOCK_ID."
					AND BR.ENTITY_TYPE = 'iblock'
					AND O.NAME IN ('".implode("', '", $arOperations)."')
				GROUP BY
					BR.ID, BR.GROUP_CODE, BR.TASK_ID, BR.DO_INHERIT
				HAVING
					COUNT(DISTINCT O.ID) = ".count($arOperations)."
				ORDER BY
					BR.ID
			");
		}
		else//if($opMode == CIBlockRights::ANY_OPERATION)
		{
			$arOperations = array_map(array($DB, "ForSQL"), $arOptions["operations"]);
			$rs = $DB->Query("
				SELECT DISTINCT
					BR.ID, BR.GROUP_CODE, BR.TASK_ID, BR.DO_INHERIT, 'N' IS_INHERITED, BR.XML_ID
				FROM
					b_iblock_right BR
					INNER JOIN b_task_operation T ON T.TASK_ID = BR.TASK_ID
					INNER JOIN b_operation O ON O.ID = T.OPERATION_ID
				WHERE
					BR.IBLOCK_ID = ".$this->IBLOCK_ID."
					AND BR.ENTITY_TYPE = 'iblock'
					AND O.NAME IN ('".implode("', '", $arOperations)."')
				ORDER BY
					BR.ID
			");
		}

		$obStorage = $this->_storage_object();
		while($ar = $rs->Fetch())
		{
			$arResult[$ar["ID"]] = array(
				"GROUP_CODE" => $ar["GROUP_CODE"],
				"DO_INHERIT" => $ar["DO_INHERIT"],
				"IS_INHERITED" => $ar["IS_INHERITED"],
				"OVERWRITED" => isset($arOptions["count_overwrited"]) && $arOptions["count_overwrited"]? $obStorage->CountOverWrited($ar["GROUP_CODE"]): 0,
				"TASK_ID" => $ar["TASK_ID"],
				"XML_ID" => $ar["XML_ID"],
			);
			if(isset($ar["ENTITY_TYPE"]))
				$arResult[$ar["ID"]]["ENTITY_TYPE"] = $ar["ENTITY_TYPE"];
			if(isset($ar["ENTITY_ID"]))
				$arResult[$ar["ID"]]["ENTITY_ID"] = $ar["ENTITY_ID"];
		}

		return $arResult;
	}

	function DeleteAllRights()
	{
		$stor = $this->_storage_object();
		$stor->CleanUp(/*$bFull=*/true);
	}

	function Recalculate()
	{
		$stor = $this->_storage_object();
		$stor->Recalculate();
	}

	function ChangeParents($arOldParents, $arNewParents)
	{
		$obStorage = $this->_storage_object();

		foreach($arOldParents as $id)
		{
			$ob = $this->_get_parent_object($id);
			if(is_object($ob))
			{
				$arRights = $ob->GetRights();
				foreach($arRights as $RIGHT_ID => $arRight)
				{
					if($arRight["DO_INHERIT"] === "Y")
					{
						$obStorage->DeleteSelfSet($RIGHT_ID, CIBlockRights::RIGHT_ID);
						$obStorage->DeleteChildrenSet($RIGHT_ID, CIBlockRights::RIGHT_ID);
					}
				}
			}
		}

		$arOwnGroupCodes = array();
		$arDBRights = $this->GetRights();
		foreach($arDBRights as $RIGHT_ID => $arOwnRight)
			$arOwnGroupCodes[$arOwnRight["GROUP_CODE"]] = $RIGHT_ID;

		foreach($arNewParents as $id)
		{
			$ob = $this->_get_parent_object($id);
			if(is_object($ob))
			{
				$arRights = $ob->GetRights();
				foreach($arRights as $RIGHT_ID => $arRight)
				{
					if(
						$arRight["DO_INHERIT"] === "Y"
						&& !array_key_exists($arRight["GROUP_CODE"], $arOwnGroupCodes)
					)
					{
						$obStorage->_set_section($id);
						$obStorage->AddSelfSet($RIGHT_ID, /*$bInherited=*/true);
						$obStorage->AddChildrenSet($RIGHT_ID, $arRight["GROUP_CODE"], /*$bInherited=*/true);
					}
				}
			}
		}
	}

	function _get_parent_object($id)
	{
		if($id <= 0)
			return new CIBlockRights($this->IBLOCK_ID, $id);
		else
			return new CIBlockSectionRights($this->IBLOCK_ID, $id);
	}

	function SetRights($arRights)
	{
		global $DB;

		if(!$this->_self_check())
			return false;

		$arDBRights = $this->GetRights();
		$arTasks = $this->GetRightsList(false);

		$arAddedCodes = array();
		$arUniqCodes = array();
		foreach($arRights as $RIGHT_ID => $arRightSet)
		{
			if(strlen($arRightSet["GROUP_CODE"]) > 0)
			{
				if(isset($arUniqCodes[$arRightSet["GROUP_CODE"]]))
					unset($arRights[$RIGHT_ID]);
				else
					$arUniqCodes[$arRightSet["GROUP_CODE"]] = true;
			}
		}

		//Fix broken TASK_ID
		foreach($arRights as $RIGHT_ID => $arRightSet)
		{
			if(
				!is_array($arRightSet["TASK_ID"])
				&& !array_key_exists($arRightSet["TASK_ID"], $arTasks)
				&& array_key_exists($RIGHT_ID, $arDBRights)
			)
				$arRights[$RIGHT_ID]["TASK_ID"] = $arDBRights[$RIGHT_ID]["TASK_ID"];
		}

		$bCleanUp = false;
		$obStorage = $this->_storage_object();
		foreach($arRights as $RIGHT_ID => $arRightSet)
		{
			$ID = intval($RIGHT_ID);
			$GROUP_CODE = $arRightSet["GROUP_CODE"];
			$bInherit = true;//$arRightSet["DO_INHERIT"] == "Y";

			if(strlen($GROUP_CODE) <= 0 || is_array($arRightSet["TASK_ID"]))
				continue;

			if(!array_key_exists($arRightSet["TASK_ID"], $arTasks))
				continue;

			if(
				array_key_exists($RIGHT_ID, $arDBRights)
				&& isset($arRightSet["DO_CLEAN"])
				&& $arRightSet["DO_CLEAN"] == "Y"
			)
			{
				$obStorage->DeleteChildrenSet($GROUP_CODE, CIBlockRights::GROUP_CODE);
				$bCleanUp = true;
			}

			if(substr($RIGHT_ID, 0, 1) == "n")
			{
				$arAddedCodes[$GROUP_CODE] = $GROUP_CODE;
				$NEW_RIGHT_ID = $this->_add(
					$GROUP_CODE,
					$bInherit,
					$arRightSet["TASK_ID"],
					isset($arRightSet["XML_ID"])? $arRightSet["XML_ID"]: false
				);

				if(!isset($arRightSet["DO_CLEAN"]) || $arRightSet["DO_CLEAN"] !== "NOT")
					$obStorage->DeleteSelfSet($GROUP_CODE, CIBlockRights::GROUP_CODE);
				$obStorage->AddSelfSet($NEW_RIGHT_ID);

				if(!isset($arRightSet["DO_CLEAN"]) || $arRightSet["DO_CLEAN"] !== "NOT")
					$obStorage->DeleteChildrenSet($GROUP_CODE, CIBlockRights::GROUP_CODE);
				if($bInherit)
					$obStorage->AddChildrenSet($NEW_RIGHT_ID, $GROUP_CODE, /*$bInherited=*/true);
			}
			elseif(
				array_key_exists($ID, $arDBRights)
				&& $arDBRights[$ID]["IS_INHERITED"] != "Y"
			)
			{
				$this->_update($ID, $GROUP_CODE, $bInherit, $arRightSet["TASK_ID"]);
				//This not possible to change group code in _update
				//$obStorage->DeleteChildrenSet($ID, CIBlockRights::RIGHT_ID);
				//if($bInherit)
				//	$obStorage->AddChildrenSet($ID, $GROUP_CODE, /*$bInherited=*/true);

				unset($arDBRights[$ID]);
			}
		}

		foreach($arDBRights as $RIGHT_ID => $arRightSet)
		{

			if($arRightSet["IS_INHERITED"] == "Y")
				continue;

			$obStorage->DeleteSelfSet($RIGHT_ID, CIBlockRights::RIGHT_ID);
			if($arRightSet["DO_INHERIT"] == "Y")
				$obStorage->DeleteChildrenSet($RIGHT_ID, CIBlockRights::RIGHT_ID);

			$this->_delete($RIGHT_ID);

			if(!isset($arAddedCodes[$arRightSet["GROUP_CODE"]]))
			{
				foreach($obStorage->FindParentWithInherit($arRightSet["GROUP_CODE"]) as $SECTION_ID => $PARENT_RIGHT)
				{
					$obStorage->AddSelfSet($PARENT_RIGHT, /*$bInherited=*/true);
					$obStorage->AddChildrenSet($PARENT_RIGHT, $arRightSet["GROUP_CODE"], /*$bInherited=*/true);
				}
			}
		}

		if ($bCleanUp)
			$obStorage->CleanUp();

		if(defined("BX_COMP_MANAGED_CACHE"))
			$GLOBALS["CACHE_MANAGER"]->ClearByTag("iblock_id_".$this->IBLOCK_ID);

		return true;
	}

	function _add($GROUP_CODE, $bInherit, $TASK_ID, $XML_ID)
	{
		global $DB;
		$arOperations = CTask::GetOperations($TASK_ID, /*$return_names=*/true);

		$NEW_RIGHT_ID = $DB->Add("b_iblock_right", array(
			"IBLOCK_ID" => $this->IBLOCK_ID,
			"GROUP_CODE" => $GROUP_CODE,
			"ENTITY_TYPE" => $this->_entity_type(),
			"ENTITY_ID" => $this->id,
			"DO_INHERIT" => $bInherit? "Y": "N",
			"TASK_ID" => $TASK_ID,
			"OP_SREAD" => in_array("section_read", $arOperations)? "Y": "N",
			"OP_EREAD" => in_array("element_read", $arOperations)? "Y": "N",
			"XML_ID" => (strlen($XML_ID) > 0? $XML_ID: false),
		));

		return $NEW_RIGHT_ID;
	}

	function _update($RIGHT_ID, $GROUP_CODE, $bInherit, $TASK_ID)
	{
		global $DB;
		$RIGHT_ID = intval($RIGHT_ID);
		$arOperations = CTask::GetOperations($TASK_ID, /*$return_names=*/true);

		$strUpdate = $DB->PrepareUpdate("b_iblock_right", array(
			//"GROUP_CODE" => $GROUP_CODE,
			"DO_INHERIT" => $bInherit? "Y": "N",
			"TASK_ID" => $TASK_ID,
			"OP_SREAD" => in_array("section_read", $arOperations)? "Y": "N",
			"OP_EREAD" => in_array("element_read", $arOperations)? "Y": "N",
		));
		$DB->Query("UPDATE b_iblock_right SET ".$strUpdate." WHERE ID = ".$RIGHT_ID);
	}

	function _delete($RIGHT_ID)
	{
		global $DB;
		$RIGHT_ID = intval($RIGHT_ID);
		$DB->Query("DELETE FROM b_iblock_right WHERE ID = ".$RIGHT_ID);
	}

	function _storage_object()
	{
		return new CIBlockRightsStorage($this->IBLOCK_ID, 0, 0);
	}

	static function UserHasRightTo($IBLOCK_ID, $ID, $permission, $flags = 0)
	{
		$acc = new CAccess;
		$acc->UpdateCodes();

		$obRights = new CIBlockRights($IBLOCK_ID);

		return CIBlockRights::_check_if_user_has_right($obRights, $ID, $permission, $flags);
	}

	static function _check_if_user_has_right($obRights, $ID, $permission, $flags = 0)
	{
		global $DB, $USER;
		$USER_ID = 0;

		if($USER_ID > 0 && (!is_object($USER) || $USER_ID != $USER->GetID()))
		{
			$user_id = intval($USER_ID);
			$arGroups = CUser::GetUserGroup($USER_ID);

			if(
				in_array(1, $arGroups)
				&& COption::GetOptionString("main", "controller_member", "N") != "Y"
				&& COption::GetOptionString("main", "~controller_limited_admin", "N") != "Y"
			)
			{
				return CIBlockRights::_mk_result($ID, CIBlockRights::LetterToOperations("X"), true, $flags);
			}
		}
		elseif(!is_object($USER))
		{
			return CIBlockRights::_mk_result($ID, array(), false, $flags);
		}
		elseif($USER->IsAdmin())
		{
			return CIBlockRights::_mk_result($ID, CIBlockRights::LetterToOperations("X"), true, $flags);
		}

		$user_id = intval($USER->GetID());
		$arGroups = $USER->GetUserGroupArray();

		$RIGHTS_MODE = CIBlock::GetArrayByID($obRights->GetIBlockID(), "RIGHTS_MODE");
		if($RIGHTS_MODE === "E")
		{
			static $Ecache;
			if(is_array($ID))
				$arOperations = $obRights->GetUserOperations($ID, $user_id);
			else
			{
				$cache_id = $user_id."|".$ID;
				if(!isset($Ecache[$cache_id]))
					$Ecache[$cache_id] = $obRights->GetUserOperations($ID, $user_id);
				$arOperations = $Ecache[$cache_id];
			}

			if($flags & CIBlockRights::RETURN_OPERATIONS)
				return $arOperations;
			else
				return isset($arOperations[$permission]);
		}
		else//if($RIGHTS_MODE === "S")
		{
			$letter = CIBlock::GetPermission($obRights->GetIBlockID());
			$arOperations = CIBlockRights::_mk_result($ID, CIBlockRights::LetterToOperations($letter), CIBlockRights::LetterToOperations($letter), $flags);

			if($flags & CIBlockRights::RETURN_OPERATIONS)
				return $arOperations;
			else
				return isset($arOperations[$permission]);
		}
	}

	static function _mk_result($ID, $arOperations, $bAllow, $flags)
	{
		if($flags & CIBlockRights::RETURN_OPERATIONS)
		{
			if(is_array($ID))
			{
				$result = array();
				foreach($ID as $id)
					$result[$id] = $arOperations;
			}
			else
			{
				$result = $arOperations;
			}
		}
		else
		{
			$result = $bAllow;
		}
		return $result;
	}

	static function GetUserOperations($arID, $USER_ID = 0)
	{
		global $DB, $USER;
		$USER_ID = intval($USER_ID);

		if(is_object($USER))
		{
			if($USER_ID <= 0)
				$USER_ID = intval($USER->GetID());
			$bAuthorized = $USER->IsAuthorized();
		}
		else
		{
			$bAuthorized = false;
		}

		if(!is_array($arID))
			$sqlID = array(intval($arID));
		else
			$sqlID = array_map('intval', $arID);

		$rs = $DB->Query("
			SELECT IBR.ENTITY_ID ID, O.NAME
			FROM b_iblock_right IBR
			INNER JOIN b_task_operation T ON T.TASK_ID = IBR.TASK_ID
			INNER JOIN b_operation O ON O.ID = T.OPERATION_ID
			".($USER_ID > 0? "LEFT": "INNER")." JOIN b_user_access UA ON UA.ACCESS_CODE = IBR.GROUP_CODE AND UA.USER_ID = ".$USER_ID."
			WHERE IBR.ENTITY_TYPE = 'iblock'
			AND IBR.ENTITY_ID in (".implode(", ", $sqlID).")
			AND (UA.USER_ID IS NOT NULL ".($bAuthorized? "OR IBR.GROUP_CODE = 'AU'": "").")
		");

		$arResult = array();
		while($ar = $rs->Fetch())
			$arResult[$ar["ID"]][$ar["NAME"]] = $ar["NAME"];

		if(is_array($arID))
			return $arResult;
		elseif(array_key_exists($arID, $arResult))
			return $arResult[$arID];
		else
			return array();
	}
}

class CIBlockSectionRights extends CIBlockRights
{
	function __construct($IBLOCK_ID, $SECTION_ID)
	{
		parent::__construct($IBLOCK_ID);
		$this->id = intval($SECTION_ID);
	}

	function _self_check()
	{
		global $DB;
		$rs = $DB->Query("
			SELECT ID
			FROM b_iblock_section
			WHERE IBLOCK_ID = ".$this->IBLOCK_ID."
			AND ID = ".$this->id."
		");
		return is_array($rs->Fetch());
	}

	function _entity_type()
	{
		return "section";
	}

	function _storage_object()
	{
		return new CIBlockRightsStorage($this->IBLOCK_ID, $this->id, 0);
	}

	function GetList($arFilter)
	{
		global $DB;

		$obQueryWhere = new CSQLWhere;
		$obQueryWhere->SetFields(array(
			"IBLOCK_ID" => array(
				"TABLE_ALIAS" => "SR",
				"FIELD_NAME" => "SR.IBLOCK_ID",
				"MULTIPLE" => "N",
				"FIELD_TYPE" => "int",
				"JOIN" => false,
			),
			"ITEM_ID" => array(
				"TABLE_ALIAS" => "SR",
				"FIELD_NAME" => "SR.SECTION_ID",
				"MULTIPLE" => "N",
				"FIELD_TYPE" => "int",
				"JOIN" => false,
			),
		));

		$strWhere = $obQueryWhere->GetQuery($arFilter);

		return $DB->Query("
			SELECT
				SR.SECTION_ID ITEM_ID
				,BR.ID RIGHT_ID
				,BR.GROUP_CODE
				,BR.TASK_ID
				,BR.DO_INHERIT
				,SR.IS_INHERITED
				,BR.XML_ID
			FROM
				b_iblock_section_right SR
				INNER JOIN b_iblock_right BR ON BR.ID = SR.RIGHT_ID
			".($strWhere? "WHERE ".$strWhere: "")."
			ORDER BY
				BR.ID
		");
	}

	function GetRights($arOptions = array())
	{
		global $DB;
		$arResult = array();

		if($this->id <= 0)
			return parent::GetRights($arOptions);

		if(
			!isset($arOptions["operations"])
			|| !is_array($arOptions["operations"])
			|| empty($arOptions["operations"])
		)
		{
			$rs = $DB->Query("
				SELECT
					BR.ID
					,BR.GROUP_CODE
					,BR.TASK_ID
					,BR.DO_INHERIT
					,SR.IS_INHERITED
					,BR.XML_ID
					,BR.ENTITY_TYPE
					,BR.ENTITY_ID
				FROM
					b_iblock_section_right SR
					INNER JOIN b_iblock_right BR ON BR.ID = SR.RIGHT_ID
				WHERE
					SR.IBLOCK_ID = ".$this->IBLOCK_ID."
					AND SR.SECTION_ID = ".$this->id."
				ORDER BY
					BR.ID
			");
		}
		elseif(
			isset($arOptions["operations_mode"])
			&& $arOptions["operations_mode"] == CIBlockRights::ALL_OPERATIONS
			&& count($arOptions["operations"]) > 1
		)
		{
			$arOperations = array_map(array($DB, "ForSQL"), $arOptions["operations"]);
			$rs = $DB->Query("
				SELECT
					BR.ID, BR.GROUP_CODE, BR.TASK_ID, BR.DO_INHERIT, SR.IS_INHERITED, BR.XML_ID
				FROM
					b_iblock_section_right SR
					INNER JOIN b_iblock_right BR ON BR.ID = SR.RIGHT_ID
					INNER JOIN b_task_operation T ON T.TASK_ID = BR.TASK_ID
					INNER JOIN b_operation O ON O.ID = T.OPERATION_ID
				WHERE
					SR.IBLOCK_ID = ".$this->IBLOCK_ID."
					AND SR.SECTION_ID = ".$this->id."
					AND O.NAME IN ('".implode("', '", $arOperations)."')
				GROUP BY
					BR.ID, BR.GROUP_CODE, BR.TASK_ID, BR.DO_INHERIT, SR.IS_INHERITED
				HAVING
					COUNT(DISTINCT O.ID) = ".count($arOperations)."
				ORDER BY
					BR.ID
			");
		}
		else//if($opMode == CIBlockRights::ANY_OPERATION)
		{
			$arOperations = array_map(array($DB, "ForSQL"), $arOptions["operations"]);
			$rs = $DB->Query("
				SELECT DISTINCT
					BR.ID, BR.GROUP_CODE, BR.TASK_ID, BR.DO_INHERIT, SR.IS_INHERITED, BR.XML_ID
				FROM
					b_iblock_section_right SR
					INNER JOIN b_iblock_right BR ON BR.ID = SR.RIGHT_ID
					INNER JOIN b_task_operation T ON T.TASK_ID = BR.TASK_ID
					INNER JOIN b_operation O ON O.ID = T.OPERATION_ID
				WHERE
					SR.IBLOCK_ID = ".$this->IBLOCK_ID."
					AND SR.SECTION_ID = ".$this->id."
					AND O.NAME IN ('".implode("', '", $arOperations)."')
				ORDER BY
					BR.ID
			");
		}

		if(isset($arOptions["parent"]))
		{
			$obParentRights = new CIBlockSectionRights($this->IBLOCK_ID, $arOptions["parent"]);
			$arParentRights = $obParentRights->GetRights();
			foreach($arParentRights as $RIGHT_ID => $arRight)
			{
				$arResult[$RIGHT_ID] = array(
					"GROUP_CODE" => $arRight["GROUP_CODE"],
					"DO_INHERIT" => $arRight["DO_INHERIT"],
					"IS_INHERITED" => "Y",
					"IS_OVERWRITED" => "Y",
					"TASK_ID" => $arRight["TASK_ID"],
					"XML_ID" => $arRight["XML_ID"],
				);
				if(isset($arRight["ENTITY_TYPE"]))
					$arResult[$RIGHT_ID]["ENTITY_TYPE"] = $arRight["ENTITY_TYPE"];
				if(isset($arRight["ENTITY_ID"]))
					$arResult[$RIGHT_ID]["ENTITY_ID"] = $arRight["ENTITY_ID"];
			}
		}

		$obStorage = $this->_storage_object();
		while($ar = $rs->Fetch())
		{
			$arResult[$ar["ID"]] = array(
				"GROUP_CODE" => $ar["GROUP_CODE"],
				"DO_INHERIT" => $ar["DO_INHERIT"],
				"IS_INHERITED" => $ar["IS_INHERITED"],
				"OVERWRITED" => isset($arOptions["count_overwrited"]) && $arOptions["count_overwrited"]? $obStorage->CountOverWrited($ar["GROUP_CODE"]): 0,
				"TASK_ID" => $ar["TASK_ID"],
				"XML_ID" => $ar["XML_ID"],
			);
			if(isset($ar["ENTITY_TYPE"]))
				$arResult[$ar["ID"]]["ENTITY_TYPE"] = $ar["ENTITY_TYPE"];
			if(isset($ar["ENTITY_ID"]))
				$arResult[$ar["ID"]]["ENTITY_ID"] = $ar["ENTITY_ID"];
		}

		return $arResult;
	}

	function DeleteAllRights()
	{
		$stor = $this->_storage_object();
		$stor->DeleteRights();
		$stor->CleanUp(/*$bFull=*/false);
	}

	static function UserHasRightTo($IBLOCK_ID, $ID, $permission, $flags = 0)
	{
		$acc = new CAccess;
		$acc->UpdateCodes();

		if($ID > 0)
		{
			$obRights = new CIBlockSectionRights($IBLOCK_ID, 0);
			$ID2CHECK = $ID;
		}
		else
		{
			$obRights = new CIBlockRights($IBLOCK_ID);
			$ID2CHECK = $IBLOCK_ID;
		}

		return CIBlockRights::_check_if_user_has_right($obRights, $ID2CHECK, $permission, $flags);
	}

	static function GetUserOperations($arID, $USER_ID = 0)
	{
		global $DB, $USER;
		$USER_ID = intval($USER_ID);

		if(is_object($USER))
		{
			if($USER_ID <= 0)
				$USER_ID = intval($USER->GetID());
			$bAuthorized = $USER->IsAuthorized();
		}
		else
		{
			$bAuthorized = false;
		}

		if(!is_array($arID))
			$sqlID = array(intval($arID));
		elseif(empty($arID))
			return array();
		else
			$sqlID = array_map('intval', $arID);

		$rs = $DB->Query("
			SELECT SR.SECTION_ID ID, O.NAME
			FROM b_iblock_section BS
			INNER JOIN b_iblock_section_right SR ON SR.SECTION_ID = BS.ID
			INNER JOIN b_iblock_right IBR ON IBR.ID = SR.RIGHT_ID
			INNER JOIN b_task_operation T ON T.TASK_ID = IBR.TASK_ID
			INNER JOIN b_operation O ON O.ID = T.OPERATION_ID
			".($USER_ID > 0? "LEFT": "INNER")." JOIN b_user_access UA ON UA.ACCESS_CODE = IBR.GROUP_CODE AND UA.USER_ID = ".$USER_ID."
			WHERE BS.ID in (".implode(", ", $sqlID).")
			".($bAuthorized || $USER_ID > 0? "
				AND (UA.USER_ID IS NOT NULL
				".($bAuthorized? "OR IBR.GROUP_CODE = 'AU'": "")."
				".($USER_ID > 0? "OR (IBR.GROUP_CODE = 'CR' AND BS.CREATED_BY = ".$USER_ID.")": "")."
			)": "")."
		");

		$arResult = array();
		while($ar = $rs->Fetch())
			$arResult[$ar["ID"]][$ar["NAME"]] = $ar["NAME"];

		if(is_array($arID))
			return $arResult;
		elseif(array_key_exists($arID, $arResult))
			return $arResult[$arID];
		else
			return array();
	}
}

class CIBlockElementRights extends CIBlockRights
{
	function __construct($IBLOCK_ID, $ELEMENT_ID)
	{
		parent::__construct($IBLOCK_ID);
		$this->id = intval($ELEMENT_ID);
	}

	function _self_check()
	{
		global $DB;
		$rs = $DB->Query("
			SELECT ID
			FROM b_iblock_element
			WHERE IBLOCK_ID = ".$this->IBLOCK_ID."
			AND ID = ".$this->id."
		");
		return is_array($rs->Fetch());
	}

	function _entity_type()
	{
		return "element";
	}

	function _storage_object()
	{
		return new CIBlockRightsStorage($this->IBLOCK_ID, 0, $this->id);
	}

	function GetList($arFilter)
	{
		global $DB;

		$obQueryWhere = new CSQLWhere;
		$obQueryWhere->SetFields(array(
			"IBLOCK_ID" => array(
				"TABLE_ALIAS" => "ER",
				"FIELD_NAME" => "ER.IBLOCK_ID",
				"MULTIPLE" => "N",
				"FIELD_TYPE" => "int",
				"JOIN" => false,
			),
			"ITEM_ID" => array(
				"TABLE_ALIAS" => "ER",
				"FIELD_NAME" => "ER.ELEMENT_ID",
				"MULTIPLE" => "N",
				"FIELD_TYPE" => "int",
				"JOIN" => false,
			),
		));

		$strWhere = $obQueryWhere->GetQuery($arFilter);

		return $DB->Query("
			SELECT
				ER.ELEMENT_ID ITEM_ID
				,BR.ID RIGHT_ID
				,BR.GROUP_CODE
				,BR.TASK_ID
				,BR.DO_INHERIT
				,ER.IS_INHERITED
				,BR.XML_ID
			FROM
				b_iblock_element_right ER
				INNER JOIN b_iblock_right BR ON BR.ID = ER.RIGHT_ID
			".($strWhere? "WHERE ".$strWhere: "")."
			ORDER BY
				BR.ID
		");
	}

	function GetRights($arOptions = array())
	{
		global $DB;
		$arResult = array();

		if(
			!isset($arOptions["operations"])
			|| !is_array($arOptions["operations"])
			|| empty($arOptions["operations"])
		)
		{
			$rs = $DB->Query("
				SELECT
					BR.ID
					,BR.GROUP_CODE
					,BR.TASK_ID
					,BR.DO_INHERIT
					,ER.IS_INHERITED
					,BR.XML_ID
					,BR.ENTITY_TYPE
					,BR.ENTITY_ID
				FROM
					b_iblock_element_right ER
					INNER JOIN b_iblock_right BR ON BR.ID = ER.RIGHT_ID
				WHERE
					ER.IBLOCK_ID = ".$this->IBLOCK_ID."
					AND ER.ELEMENT_ID = ".$this->id."
				ORDER BY
					BR.ID
			");
		}
		elseif(
			isset($arOptions["operations_mode"])
			&& $arOptions["operations_mode"] == CIBlockRights::ALL_OPERATIONS
			&& count($arOptions["operations"]) > 1
		)
		{
			$arOperations = array_map(array($DB, "ForSQL"), $arOptions["operations"]);
			$rs = $DB->Query("
				SELECT
					BR.ID, BR.GROUP_CODE, BR.TASK_ID, BR.DO_INHERIT, ER.IS_INHERITED, BR.XML_ID
				FROM
					b_iblock_element_right ER
					INNER JOIN b_iblock_right BR ON BR.ID = ER.RIGHT_ID
					INNER JOIN b_task_operation T ON T.TASK_ID = BR.TASK_ID
					INNER JOIN b_operation O ON O.ID = T.OPERATION_ID
				WHERE
					ER.IBLOCK_ID = ".$this->IBLOCK_ID."
					AND ER.ELEMENT_ID = ".$this->id."
					AND O.NAME IN ('".implode("', '", $arOperations)."')
				GROUP BY
					BR.ID, BR.GROUP_CODE, BR.TASK_ID, BR.DO_INHERIT, ER.IS_INHERITED
				HAVING
					COUNT(DISTINCT O.ID) = ".count($arOperations)."
				ORDER BY
					BR.ID
			");
		}
		else//if($opMode == CIBlockRights::ANY_OPERATION)
		{
			$arOperations = array_map(array($DB, "ForSQL"), $arOptions["operations"]);
			$rs = $DB->Query("
				SELECT DISTINCT
					BR.ID, BR.GROUP_CODE, BR.TASK_ID, BR.DO_INHERIT, ER.IS_INHERITED, BR.XML_ID
				FROM
					b_iblock_element_right ER
					INNER JOIN b_iblock_right BR ON BR.ID = ER.RIGHT_ID
					INNER JOIN b_task_operation T ON T.TASK_ID = BR.TASK_ID
					INNER JOIN b_operation O ON O.ID = T.OPERATION_ID
				WHERE
					ER.IBLOCK_ID = ".$this->IBLOCK_ID."
					AND ER.ELEMENT_ID = ".$this->id."
					AND O.NAME IN ('".implode("', '", $arOperations)."')
				ORDER BY
					BR.ID
			");
		}

		if(isset($arOptions["parents"]) && is_array($arOptions["parents"]))
		{
			foreach($arOptions["parents"] as $parent)
			{
				$obParentRights = new CIBlockSectionRights($this->IBLOCK_ID, $parent);
				$arParentRights = $obParentRights->GetRights();
				foreach($arParentRights as $RIGHT_ID => $arRight)
				{
					$arResult[$RIGHT_ID] = array(
						"GROUP_CODE" => $arRight["GROUP_CODE"],
						"DO_INHERIT" => $arRight["DO_INHERIT"],
						"IS_INHERITED" => "Y",
						"IS_OVERWRITED" => "Y",
						"TASK_ID" => $arRight["TASK_ID"],
						"XML_ID" => $arRight["XML_ID"],
					);
					if(isset($arRight["ENTITY_TYPE"]))
						$arResult[$RIGHT_ID]["ENTITY_TYPE"] = $arRight["ENTITY_TYPE"];
					if(isset($arRight["ENTITY_ID"]))
						$arResult[$RIGHT_ID]["ENTITY_ID"] = $arRight["ENTITY_ID"];
				}
			}
		}

		$obStorage = $this->_storage_object();
		while($ar = $rs->Fetch())
		{
			$arResult[$ar["ID"]] = array(
				"GROUP_CODE" => $ar["GROUP_CODE"],
				"DO_INHERIT" => $ar["DO_INHERIT"],
				"IS_INHERITED" => $ar["IS_INHERITED"],
				"OVERWRITED" => 0,
				"TASK_ID" => $ar["TASK_ID"],
				"XML_ID" => $ar["XML_ID"],
			);
			if(isset($ar["ENTITY_TYPE"]))
				$arResult[$ar["ID"]]["ENTITY_TYPE"] = $ar["ENTITY_TYPE"];
			if(isset($ar["ENTITY_ID"]))
				$arResult[$ar["ID"]]["ENTITY_ID"] = $ar["ENTITY_ID"];
		}

		return $arResult;
	}

	function DeleteAllRights()
	{
		$stor = $this->_storage_object();
		$stor->DeleteRights();
		$stor->CleanUp(/*$bFull=*/false);
	}

	static function UserHasRightTo($IBLOCK_ID, $ID, $permission, $flags = 0)
	{
		$acc = new CAccess;
		$acc->UpdateCodes();

		$obRights = new CIBlockElementRights($IBLOCK_ID, 0);

		return CIBlockRights::_check_if_user_has_right($obRights, $ID, $permission, $flags);
	}

	static function GetUserOperations($arID, $USER_ID = 0)
	{
		global $DB, $USER;
		$USER_ID = intval($USER_ID);

		if(is_object($USER))
		{
			if($USER_ID <= 0)
				$USER_ID = intval($USER->GetID());
			$bAuthorized = $USER->IsAuthorized();
		}
		else
		{
			$bAuthorized = false;
		}

		if ($USER_ID > 0)
		{
			$acc = new CAccess;
			$acc->UpdateCodes(array('USER_ID' => $USER_ID));
		}

		if(!is_array($arID))
			$sqlID = array(intval($arID));
		elseif(empty($arID))
			return array();
		else
			$sqlID = array_map('intval', $arID);

		$rs = $DB->Query("
			SELECT ER.ELEMENT_ID ID, O.NAME
			FROM b_iblock_element E
			INNER JOIN b_iblock_element_right ER ON ER.ELEMENT_ID = E.ID
			INNER JOIN b_iblock_right IBR ON IBR.ID = ER.RIGHT_ID
			INNER JOIN b_task_operation T ON T.TASK_ID = IBR.TASK_ID
			INNER JOIN b_operation O ON O.ID = T.OPERATION_ID
			".($USER_ID > 0? "LEFT": "INNER")." JOIN b_user_access UA ON UA.ACCESS_CODE = IBR.GROUP_CODE AND UA.USER_ID = ".$USER_ID."
			WHERE E.ID in (".implode(", ", $sqlID).")
			".($bAuthorized || $USER_ID > 0? "
				AND (UA.USER_ID IS NOT NULL
				".($bAuthorized? "OR IBR.GROUP_CODE = 'AU'": "")."
				".($USER_ID > 0? "OR (IBR.GROUP_CODE = 'CR' AND E.CREATED_BY = ".$USER_ID.")": "")."
			)": "")."
		");

		$arResult = array();
		while($ar = $rs->Fetch())
			$arResult[$ar["ID"]][$ar["NAME"]] = $ar["NAME"];

		if(is_array($arID))
			return $arResult;
		elseif(array_key_exists($arID, $arResult))
			return $arResult[$arID];
		else
			return array();
	}
}

class CIBlockRightsStorage
{
	protected $IBLOCK_ID = 0;
	protected $SECTION_ID = 0;
	protected $ELEMENT_ID = 0;
	protected $arSection = null;
	function __construct($IBLOCK_ID, $SECTION_ID, $ELEMENT_ID)
	{
		$this->IBLOCK_ID = intval($IBLOCK_ID);
		$this->SECTION_ID = intval($SECTION_ID);
		$this->ELEMENT_ID = intval($ELEMENT_ID);
	}

	function _set_section($SECTION_ID)
	{
		$this->SECTION_ID = intval($SECTION_ID);
		$this->arSection = null;
	}

	function _get_section()
	{
		if(!isset($this->arSection))
		{
			if($this->SECTION_ID > 0)
			{
				$rsSection = CIBlockSection::GetList(array(), array(
					"IBLOCK_ID" => $this->IBLOCK_ID,
					"=ID" => $this->SECTION_ID,
					"CHECK_PERMISSIONS" => "N",
				), false, array("LEFT_MARGIN", "RIGHT_MARGIN"));
				$this->arSection = $rsSection->Fetch();

				//We have to resort sections in some cases
				if(
					$this->arSection
					&& (
						$this->arSection["LEFT_MARGIN"] <= 0
						|| $this->arSection["RIGHT_MARGIN"] <= 0
					)
				)
				{
					CIBlockSection::Resort($this->IBLOCK_ID);

					$rsSection = CIBlockSection::GetList(array(), array(
						"IBLOCK_ID" => $this->IBLOCK_ID,
						"=ID" => $this->SECTION_ID,
						"CHECK_PERMISSIONS" => "N",
					), false, array("LEFT_MARGIN", "RIGHT_MARGIN"));
					$this->arSection = $rsSection->Fetch();
				}
			}
			else
			{
				$this->arSection = false;
			}
		}
		return $this->arSection;
	}

	function CountOverWrited($GROUP_CODE)
	{
		global $DB;
		$arResult = array(0,0);

		if($this->ELEMENT_ID > 0)
		{
		}
		elseif(is_array($this->_get_section()))
		{
			//Count subsections
			$rs = $DB->Query("
				SELECT
					COUNT(DISTINCT SR.SECTION_ID) CNT
				FROM
					b_iblock_right BR
					INNER JOIN b_iblock_section_right SR ON SR.RIGHT_ID = BR.ID
					INNER JOIN b_iblock_section BS ON BS.ID = SR.SECTION_ID
				WHERE
					BR.IBLOCK_ID = ".$this->IBLOCK_ID."
					AND BR.GROUP_CODE = '".$DB->ForSQL($GROUP_CODE, 32)."'
					AND SR.IS_INHERITED = 'N'
					AND BS.LEFT_MARGIN  > ".$this->arSection["LEFT_MARGIN"]."
					AND BS.RIGHT_MARGIN < ".$this->arSection["RIGHT_MARGIN"]."
			");
			$ar = $rs->Fetch();
			if($ar)
				$arResult[0] = $ar["CNT"];

			//Count elements in subsections
			$rs = $DB->Query("
				SELECT
					COUNT(DISTINCT ER.ELEMENT_ID) CNT
				FROM
					b_iblock_right BR
					INNER JOIN b_iblock_element_right ER ON ER.RIGHT_ID = BR.ID
					INNER JOIN b_iblock_section_element BSE ON BSE.IBLOCK_ELEMENT_ID = ER.ELEMENT_ID AND ADDITIONAL_PROPERTY_ID IS NULL
					INNER JOIN b_iblock_section BS ON BS.ID = BSE.IBLOCK_SECTION_ID
				WHERE
					BR.IBLOCK_ID = ".$this->IBLOCK_ID."
					AND BR.GROUP_CODE = '".$DB->ForSQL($GROUP_CODE, 32)."'
					AND ER.IS_INHERITED = 'N'
					AND BS.LEFT_MARGIN  >= ".$this->arSection["LEFT_MARGIN"]."
					AND BS.RIGHT_MARGIN <= ".$this->arSection["RIGHT_MARGIN"]."
			");

			$ar = $rs->Fetch();
			if($ar)
				$arResult[1] = $ar["CNT"];
		}
		else
		{
			//Count subsections
			$rs = $DB->Query("
				SELECT
					COUNT(DISTINCT SR.SECTION_ID) CNT
				FROM
					b_iblock_right BR
					INNER JOIN b_iblock_section_right SR ON SR.RIGHT_ID = BR.ID
				WHERE
					BR.IBLOCK_ID = ".$this->IBLOCK_ID."
					AND BR.GROUP_CODE = '".$DB->ForSQL($GROUP_CODE, 32)."'
					AND SR.IS_INHERITED = 'N'
			");
			$ar = $rs->Fetch();
			if($ar)
				$arResult[0] = $ar["CNT"];

			//Count elements in subsections
			$rs = $DB->Query("
				SELECT
					COUNT(DISTINCT ER.ELEMENT_ID) CNT
				FROM
					b_iblock_right BR
					INNER JOIN b_iblock_element_right ER ON ER.RIGHT_ID = BR.ID
				WHERE
					BR.IBLOCK_ID = ".$this->IBLOCK_ID."
					AND BR.GROUP_CODE = '".$DB->ForSQL($GROUP_CODE, 32)."'
					AND ER.IS_INHERITED = 'N'
			");
			$ar = $rs->Fetch();
			if($ar)
				$arResult[1] = $ar["CNT"];
		}

		return $arResult;
	}

	function DeleteSelfSet($ID, $TYPE)
	{
		global $DB;

		$strRightSubQuery = "
			SELECT ID FROM b_iblock_right
			WHERE IBLOCK_ID = ".$this->IBLOCK_ID."
			".($TYPE == CIBlockRights::GROUP_CODE?
				"AND GROUP_CODE = '".$DB->ForSQL($ID, 32)."'":
				"AND ID = ".intval($ID)
			)."
		";

		if($this->ELEMENT_ID > 0)
		{
			$DB->Query("
				DELETE FROM b_iblock_element_right
				WHERE IBLOCK_ID = ".$this->IBLOCK_ID."
				AND ELEMENT_ID = ".$this->ELEMENT_ID."
				AND RIGHT_ID IN ($strRightSubQuery)
			");
		}
		elseif(is_array($this->_get_section()))
		{
			$DB->Query("
				DELETE FROM b_iblock_section_right
				WHERE IBLOCK_ID = ".$this->IBLOCK_ID."
				AND SECTION_ID = ".$this->SECTION_ID."
				AND RIGHT_ID IN ($strRightSubQuery)
			");
		}
		else
		{
		}
	}

	function DeleteChildrenSet($ID, $TYPE)
	{
		global $DB;

		$strRightSubQuery = "
			SELECT ID FROM b_iblock_right
			WHERE IBLOCK_ID = ".$this->IBLOCK_ID."
			".($TYPE == CIBlockRights::GROUP_CODE?
				"AND GROUP_CODE = '".$DB->ForSQL($ID, 32)."'":
				"AND ID = ".intval($ID)
			)."
		";

		if($this->ELEMENT_ID > 0)
		{
		}
		elseif(is_array($this->_get_section()))
		{
			if($DB->type === "MYSQL")
			{
				$DB->Query("
					DELETE b_iblock_section_right.*
					FROM b_iblock_section_right
					INNER JOIN b_iblock_section BS ON BS.ID = b_iblock_section_right.SECTION_ID
					WHERE b_iblock_section_right.IBLOCK_ID = ".$this->IBLOCK_ID."
					AND BS.IBLOCK_ID = ".$this->IBLOCK_ID."
					AND LEFT_MARGIN  > ".$this->arSection["LEFT_MARGIN"]."
					AND RIGHT_MARGIN < ".$this->arSection["RIGHT_MARGIN"]."
					AND RIGHT_ID IN ($strRightSubQuery)
				");
				$DB->Query("
					DELETE b_iblock_element_right.*
					FROM b_iblock_element_right
					INNER JOIN b_iblock_section_element BSE ON BSE.IBLOCK_ELEMENT_ID = b_iblock_element_right.ELEMENT_ID
					INNER JOIN b_iblock_section BS ON BSE.IBLOCK_SECTION_ID = BS.ID AND BSE.ADDITIONAL_PROPERTY_ID IS NULL
					WHERE b_iblock_element_right.IBLOCK_ID = ".$this->IBLOCK_ID."
					AND BS.IBLOCK_ID = ".$this->IBLOCK_ID."
					AND BS.LEFT_MARGIN <= ".$this->arSection["RIGHT_MARGIN"]."
					AND BS.RIGHT_MARGIN >= ".$this->arSection["LEFT_MARGIN"]."
					AND (SECTION_ID IN (
						SELECT ID FROM b_iblock_section
						WHERE IBLOCK_ID = ".$this->IBLOCK_ID."
						AND LEFT_MARGIN  >= ".$this->arSection["LEFT_MARGIN"]."
						AND RIGHT_MARGIN <= ".$this->arSection["RIGHT_MARGIN"]."
					) OR SECTION_ID = 0)
					AND RIGHT_ID IN ($strRightSubQuery)
				");
			}
			else
			{
				$DB->Query("
					DELETE FROM b_iblock_section_right
					WHERE IBLOCK_ID = ".$this->IBLOCK_ID."
					AND SECTION_ID IN (
						SELECT ID FROM b_iblock_section
						WHERE IBLOCK_ID = ".$this->IBLOCK_ID."
						AND LEFT_MARGIN  > ".$this->arSection["LEFT_MARGIN"]."
						AND RIGHT_MARGIN < ".$this->arSection["RIGHT_MARGIN"]."
					)
					AND RIGHT_ID IN ($strRightSubQuery)
				");
				$DB->Query("
					DELETE FROM b_iblock_element_right
					WHERE IBLOCK_ID = ".$this->IBLOCK_ID."
					AND ELEMENT_ID IN (
						SELECT BSE.IBLOCK_ELEMENT_ID
						FROM b_iblock_section_element BSE
						INNER JOIN b_iblock_section BS ON BSE.IBLOCK_SECTION_ID = BS.ID AND BSE.ADDITIONAL_PROPERTY_ID IS NULL
						WHERE BS.IBLOCK_ID = ".$this->IBLOCK_ID."
						AND BS.LEFT_MARGIN <= ".$this->arSection["RIGHT_MARGIN"]."
						AND BS.RIGHT_MARGIN >= ".$this->arSection["LEFT_MARGIN"]."
					)
					AND (SECTION_ID IN (
						SELECT ID FROM b_iblock_section
						WHERE IBLOCK_ID = ".$this->IBLOCK_ID."
						AND LEFT_MARGIN  >= ".$this->arSection["LEFT_MARGIN"]."
						AND RIGHT_MARGIN <= ".$this->arSection["RIGHT_MARGIN"]."
					) OR SECTION_ID = 0)
					AND RIGHT_ID IN ($strRightSubQuery)
				");
			}
		}
		else
		{
			$DB->Query("
				DELETE FROM b_iblock_section_right
				WHERE IBLOCK_ID = ".$this->IBLOCK_ID."
				AND RIGHT_ID IN ($strRightSubQuery)
			");

			$DB->Query("
				DELETE FROM b_iblock_element_right
				WHERE IBLOCK_ID = ".$this->IBLOCK_ID."
				AND RIGHT_ID IN ($strRightSubQuery)
			");
		}
	}

	function AddSelfSet($RIGHT_ID, $bInherited = false)
	{
		global $DB;

		if($this->ELEMENT_ID > 0)
		{
			$DB->Query("
				INSERT INTO b_iblock_element_right (IBLOCK_ID, SECTION_ID, ELEMENT_ID, RIGHT_ID, IS_INHERITED)
				SELECT ".$this->IBLOCK_ID.", ".$this->SECTION_ID.", BE.ID, ".intval($RIGHT_ID).", '".($bInherited? "Y": "N")."'
				FROM
					b_iblock_element BE
					LEFT JOIN b_iblock_element_right ER ON ER.ELEMENT_ID = BE.ID AND ER.SECTION_ID = ".$this->SECTION_ID." AND ER.RIGHT_ID = ".intval($RIGHT_ID)."
				WHERE
					BE.ID = ".$this->ELEMENT_ID."
					AND ER.SECTION_ID IS NULL
			");
		}
		elseif(is_array($this->_get_section()))
		{
			$DB->Query("
				INSERT INTO b_iblock_section_right (IBLOCK_ID, SECTION_ID, RIGHT_ID, IS_INHERITED)
				SELECT ".$this->IBLOCK_ID.", BS.ID, ".intval($RIGHT_ID).", '".($bInherited? "Y": "N")."'
				FROM
					b_iblock_section BS
					LEFT JOIN b_iblock_section_right SR ON SR.SECTION_ID = BS.ID AND SR.RIGHT_ID = ".intval($RIGHT_ID)."
				WHERE
					BS.ID = ".$this->SECTION_ID."
					AND SR.SECTION_ID IS NULL
			");
		}
		else
		{
		}
	}

	function AddChildrenSet($RIGHT_ID, $GROUP_CODE, $bInherited)
	{
		global $DB;

		if($this->ELEMENT_ID > 0)
		{
		}
		elseif(is_array($this->_get_section()))
		{
			$DB->Query("
				INSERT INTO b_iblock_section_right (IBLOCK_ID, SECTION_ID, RIGHT_ID, IS_INHERITED)
				SELECT ".$this->IBLOCK_ID.", BS.ID, ".intval($RIGHT_ID).", '".($bInherited? "Y": "N")."'
				FROM
					b_iblock_section BS
				WHERE
					BS.IBLOCK_ID = ".$this->IBLOCK_ID."
					AND BS.LEFT_MARGIN  > ".$this->arSection["LEFT_MARGIN"]."
					AND BS.RIGHT_MARGIN < ".$this->arSection["RIGHT_MARGIN"]."
					AND BS.ID NOT IN (
						SELECT SR.SECTION_ID
						FROM
							b_iblock_right BR
							INNER JOIN b_iblock_section_right SR ON SR.RIGHT_ID = BR.ID
						WHERE
							BR.GROUP_CODE = '".$DB->ForSQL($GROUP_CODE, 32)."'
							AND BR.IBLOCK_ID = ".$this->IBLOCK_ID."
					)
			");

			$DB->Query("
				INSERT INTO b_iblock_element_right (IBLOCK_ID, SECTION_ID, ELEMENT_ID, RIGHT_ID, IS_INHERITED)
				SELECT ".$this->IBLOCK_ID.", BSE.IBLOCK_SECTION_ID, BSE.IBLOCK_ELEMENT_ID, ".intval($RIGHT_ID).", '".($bInherited? "Y": "N")."'
				FROM
					b_iblock_section_element BSE
				WHERE
					BSE.ADDITIONAL_PROPERTY_ID IS NULL
					AND BSE.IBLOCK_SECTION_ID IN (
						SELECT SECTION_ID
						FROM b_iblock_section_right
						WHERE RIGHT_ID = ".intval($RIGHT_ID)."
					)
					AND BSE.IBLOCK_ELEMENT_ID NOT IN (
						SELECT ER.ELEMENT_ID
						FROM
							b_iblock_right BR
							INNER JOIN b_iblock_element_right ER ON ER.RIGHT_ID = BR.ID
						WHERE
							BR.GROUP_CODE = '".$DB->ForSQL($GROUP_CODE, 32)."'
							AND BR.IBLOCK_ID = ".$this->IBLOCK_ID."
							AND (ER.SECTION_ID = BSE.IBLOCK_SECTION_ID OR ER.SECTION_ID = 0)
					)
			");
		}
		else
		{
			$DB->Query("
				INSERT INTO b_iblock_section_right (IBLOCK_ID, SECTION_ID, RIGHT_ID, IS_INHERITED)
				SELECT ".$this->IBLOCK_ID.", BS.ID, ".intval($RIGHT_ID).", '".($bInherited? "Y": "N")."'
				FROM
					b_iblock_section BS
				WHERE
					BS.IBLOCK_ID = ".$this->IBLOCK_ID."
					AND BS.ID NOT IN (
						SELECT SR.SECTION_ID
						FROM
							b_iblock_right BR
							INNER JOIN b_iblock_section_right SR ON SR.RIGHT_ID = BR.ID
						WHERE
							BR.GROUP_CODE = '".$DB->ForSQL($GROUP_CODE, 32)."'
							AND BR.IBLOCK_ID = ".$this->IBLOCK_ID."
					)
			");

			$DB->Query("
				INSERT INTO b_iblock_element_right (IBLOCK_ID, SECTION_ID, ELEMENT_ID, RIGHT_ID, IS_INHERITED)
				SELECT ".$this->IBLOCK_ID.", 0, BE.ID, ".intval($RIGHT_ID).", '".($bInherited? "Y": "N")."'
				FROM
					b_iblock_element BE
				WHERE
					BE.IBLOCK_ID = ".$this->IBLOCK_ID."
					AND BE.IN_SECTIONS = 'N'
					AND BE.ID NOT IN (
						SELECT ER.ELEMENT_ID
						FROM
							b_iblock_right BR
							INNER JOIN b_iblock_element_right ER ON ER.RIGHT_ID = BR.ID
						WHERE
							BR.GROUP_CODE = '".$DB->ForSQL($GROUP_CODE, 32)."'
							AND BR.IBLOCK_ID = ".$this->IBLOCK_ID."
							AND ER.SECTION_ID = 0
					)
			");

			$DB->Query("
				INSERT INTO b_iblock_element_right (IBLOCK_ID, SECTION_ID, ELEMENT_ID, RIGHT_ID, IS_INHERITED)
				SELECT ".$this->IBLOCK_ID.", BSE.IBLOCK_SECTION_ID, BSE.IBLOCK_ELEMENT_ID, ".intval($RIGHT_ID).", '".($bInherited? "Y": "N")."'
				FROM
					b_iblock_section_element BSE
				WHERE
					BSE.ADDITIONAL_PROPERTY_ID IS NULL
					AND BSE.IBLOCK_SECTION_ID IN (
						SELECT SECTION_ID
						FROM b_iblock_section_right
						WHERE RIGHT_ID = ".intval($RIGHT_ID)."
					)
					AND BSE.IBLOCK_ELEMENT_ID NOT IN (
						SELECT ER.ELEMENT_ID
						FROM
							b_iblock_right BR
							INNER JOIN b_iblock_element_right ER ON ER.RIGHT_ID = BR.ID
						WHERE
							BR.GROUP_CODE = '".$DB->ForSQL($GROUP_CODE, 32)."'
							AND BR.IBLOCK_ID = ".$this->IBLOCK_ID."
							AND ER.SECTION_ID = BSE.IBLOCK_SECTION_ID
					)
			");
		}
	}

	function FindParentWithInherit($GROUP_CODE)
	{
		global $DB;
		$arResult = array();

		if($this->ELEMENT_ID > 0)
		{
			$rs = $DB->Query("
				SELECT SR.SECTION_ID, SR.RIGHT_ID
				FROM
					b_iblock_section_element BSE
					INNER JOIN b_iblock_section_right SR ON SR.SECTION_ID = BSE.IBLOCK_SECTION_ID
					INNER JOIN b_iblock_right BR ON BR.ID = SR.RIGHT_ID
				WHERE
					BSE.IBLOCK_ELEMENT_ID = ".$this->ELEMENT_ID."
					AND BSE.ADDITIONAL_PROPERTY_ID IS NULL
					AND BR.GROUP_CODE = '".$DB->ForSQL($GROUP_CODE)."'
			");
			while($ar = $rs->Fetch())
				$arResult[$ar["SECTION_ID"]] = $ar["RIGHT_ID"];
		}
		elseif(is_array($this->_get_section()))
		{
			$rs = $DB->Query("
				SELECT BS.IBLOCK_SECTION_ID, SR.RIGHT_ID
				FROM
					b_iblock_section BS
					INNER JOIN b_iblock_section_right SR ON SR.SECTION_ID = BS.IBLOCK_SECTION_ID
					INNER JOIN b_iblock_right BR ON BR.ID = SR.RIGHT_ID
				WHERE
					BS.ID = ".$this->SECTION_ID."
					AND BR.GROUP_CODE = '".$DB->ForSQL($GROUP_CODE)."'
			");
			while($ar = $rs->Fetch())
				$arResult[$ar["IBLOCK_SECTION_ID"]] = $ar["RIGHT_ID"];
		}
		else
		{
			return array(); //iblock does not have parent
		}

		//Root section or element
		if(empty($arResult))
		{
			$rs = $DB->Query("
				SELECT BR.ID
				FROM
					b_iblock_right BR
				WHERE
					BR.IBLOCK_ID = ".$this->IBLOCK_ID."
					AND BR.GROUP_CODE = '".$DB->ForSQL($GROUP_CODE)."'
					AND ENTITY_TYPE = 'iblock'
			");
			while($ar = $rs->Fetch())
				$arResult[0] = $ar["ID"];
		}

		return $arResult;
	}

	function DeleteRights()
	{
		global $DB;

		if($this->ELEMENT_ID > 0)
		{
			$DB->Query("DELETE FROM b_iblock_element_right WHERE ELEMENT_ID = ".$this->ELEMENT_ID);
		}
		elseif(is_array($this->_get_section()))
		{
			$DB->Query("DELETE FROM b_iblock_section_right WHERE SECTION_ID = ".$this->SECTION_ID);
		}
		else
		{
		}
	}

	function CleanUp($bFull = false)
	{
		global $DB;

		if($bFull)
		{
			$DB->Query("DELETE FROM b_iblock_element_right WHERE IBLOCK_ID = ".$this->IBLOCK_ID);
			$DB->Query("DELETE FROM b_iblock_section_right WHERE IBLOCK_ID = ".$this->IBLOCK_ID);
			$DB->Query("DELETE FROM b_iblock_right WHERE IBLOCK_ID = ".$this->IBLOCK_ID);
		}
		else
		{
			$DB->Query("
				DELETE FROM b_iblock_right
				WHERE IBLOCK_ID = ".$this->IBLOCK_ID."
				AND ENTITY_TYPE <> 'iblock'
				AND ID NOT IN (
					SELECT RIGHT_ID
					FROM b_iblock_section_right
					WHERE IBLOCK_ID = ".$this->IBLOCK_ID."
				)
				AND ID NOT IN (
					SELECT RIGHT_ID
					FROM b_iblock_element_right
					WHERE IBLOCK_ID = ".$this->IBLOCK_ID."
				)
			");
		}
	}

	function Recalculate()
	{
		global $DB;

		$DB->Query("DELETE FROM b_iblock_element_right WHERE IBLOCK_ID = ".$this->IBLOCK_ID);
		$DB->Query("DELETE FROM b_iblock_section_right WHERE IBLOCK_ID = ".$this->IBLOCK_ID);
		//Elements
		$DB->Query("
			INSERT INTO b_iblock_element_right (IBLOCK_ID, SECTION_ID, ELEMENT_ID, RIGHT_ID, IS_INHERITED)
			SELECT BE.IBLOCK_ID, 0, BE.ID, BR.ID, 'N'
			FROM
				b_iblock_right BR
				INNER JOIN b_iblock_element BE ON BE.ID = BR.ENTITY_ID and BE.IBLOCK_ID = BR.IBLOCK_ID
			WHERE
				BR.IBLOCK_ID = ".$this->IBLOCK_ID."
				AND BR.ENTITY_TYPE = 'element'
				AND BE.ID NOT IN (
					SELECT ER0.ELEMENT_ID
					FROM
						b_iblock_right BR0
						INNER JOIN b_iblock_element_right ER0 ON ER0.RIGHT_ID = BR0.ID
					WHERE
						BR0.IBLOCK_ID = ".$this->IBLOCK_ID."
						AND BR0.ENTITY_TYPE = 'element'
				)
		");
		//Sections
		$rs = $DB->Query("
			SELECT BR.ID RIGHT_ID, BS.ID SECTION_ID, BR.GROUP_CODE
			FROM
				b_iblock_right BR
				INNER JOIN b_iblock_section BS ON BS.ID = BR.ENTITY_ID and BS.IBLOCK_ID = BR.IBLOCK_ID
			WHERE
				BR.IBLOCK_ID = ".$this->IBLOCK_ID."
				AND BR.ENTITY_TYPE = 'section'
			ORDER BY
				BS.DEPTH_LEVEL DESC
		");
		while($ar = $rs->Fetch())
		{
			$this->_set_section($ar["SECTION_ID"]);
			$this->AddSelfSet($ar["RIGHT_ID"]);
			$this->AddChildrenSet($ar["RIGHT_ID"], $ar["GROUP_CODE"], /*$bInherited=*/true);
		}
		//IBlock
		$this->_set_section(0);
		$rs = $DB->Query("
			SELECT BR.ID RIGHT_ID, BR.GROUP_CODE
			FROM
				b_iblock_right BR
			WHERE
				BR.IBLOCK_ID = ".$this->IBLOCK_ID."
				AND BR.ENTITY_TYPE = 'iblock'
		");
		while($ar = $rs->Fetch())
		{
			$this->AddChildrenSet($ar["RIGHT_ID"], $ar["GROUP_CODE"], /*$bInherited=*/true);
		}
	}

	function OnTaskOperationsChanged($TASK_ID, $arOld, $arNew)
	{
		global $DB;
		$TASK_ID = intval($TASK_ID);

		if(!in_array("element_read", $arOld) && in_array("element_read", $arNew))
			$DB->Query("UPDATE b_iblock_right SET OP_EREAD = 'Y' WHERE TASK_ID = ".$TASK_ID);
		elseif(in_array("element_read", $arOld) && !in_array("element_read", $arNew))
			$DB->Query("UPDATE b_iblock_right SET OP_EREAD = 'N' WHERE TASK_ID = ".$TASK_ID);

		if(!in_array("section_read", $arOld) && in_array("section_read", $arNew))
			$DB->Query("UPDATE b_iblock_right SET OP_SREAD = 'Y' WHERE TASK_ID = ".$TASK_ID);
		elseif(in_array("section_read", $arOld) && !in_array("section_read", $arNew))
			$DB->Query("UPDATE b_iblock_right SET OP_SREAD = 'N' WHERE TASK_ID = ".$TASK_ID);
	}

	function OnGroupDelete($GROUP_ID)
	{
		global $DB;
		$GROUP_ID = intval($GROUP_ID);

		$DB->Query("
			DELETE FROM b_iblock_element_right WHERE RIGHT_ID IN (
				SELECT ID FROM b_iblock_right WHERE GROUP_CODE = 'G".$GROUP_ID."'
			)
		");
		$DB->Query("
			DELETE FROM b_iblock_section_right WHERE RIGHT_ID IN (
				SELECT ID FROM b_iblock_right WHERE GROUP_CODE = 'G".$GROUP_ID."'
			)
		");
		$DB->Query("
			DELETE FROM b_iblock_right WHERE GROUP_CODE = 'G".$GROUP_ID."'
		");
	}

	function OnUserDelete($USER_ID)
	{
		global $DB;
		$USER_ID = intval($USER_ID);

		$DB->Query("
			DELETE FROM b_iblock_element_right WHERE RIGHT_ID IN (
				SELECT ID FROM b_iblock_right WHERE GROUP_CODE = 'U".$USER_ID."'
			)
		");
		$DB->Query("
			DELETE FROM b_iblock_section_right WHERE RIGHT_ID IN (
				SELECT ID FROM b_iblock_right WHERE GROUP_CODE = 'U".$USER_ID."'
			)
		");
		$DB->Query("
			DELETE FROM b_iblock_right WHERE GROUP_CODE = 'U".$USER_ID."'
		");
	}
}
//d m p(array($this, __CLASS__, __METHOD__, func_get_args()));
//d m p(array_shift(debug_backtrace()));
//if(CModule::IncludeModule('perfmon')) CPerfomanceSQL::_console_explain($strSql.$strSqlOrder);
?>
