<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

class CLists
{
	public static function SetPermission($iblock_type_id, $arGroups)
	{
		global $DB, $CACHE_MANAGER;

		$grp = array();
		foreach($arGroups as $group_id)
		{
			$group_id = intval($group_id);
			if($group_id)
				$grp[$group_id] = $group_id;
		}

		$DB->Query("
			delete from b_lists_permission
			where IBLOCK_TYPE_ID = '".$DB->ForSQL($iblock_type_id)."'
		", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		if(count($grp))
		{
			$DB->Query("
				insert into b_lists_permission
				select ibt.ID, ug.ID
				from
					b_iblock_type ibt
					,b_group ug
				where
					ibt.ID =  '".$DB->ForSQL($iblock_type_id)."'
					and ug.ID in (".implode(", ", $grp).")
			", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		}

		if(CACHED_b_lists_permission !== false)
			$CACHE_MANAGER->Clean("b_lists_permission");
	}

	public static function GetPermission($iblock_type_id = false)
	{
		global $DB, $CACHE_MANAGER;

		$arResult = false;
		if(CACHED_b_lists_permission !== false)
		{
			if($CACHE_MANAGER->Read(CACHED_b_lists_permission, "b_lists_permission"))
				$arResult = $CACHE_MANAGER->Get("b_lists_permission");
		}

		if($arResult === false)
		{
			$arResult = array();
			$res = $DB->Query("select IBLOCK_TYPE_ID, GROUP_ID from b_lists_permission", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
			while($ar = $res->Fetch())
				$arResult[$ar["IBLOCK_TYPE_ID"]][] = $ar["GROUP_ID"];

			if(CACHED_b_lists_permission !== false)
				$CACHE_MANAGER->Set("b_lists_permission", $arResult);
		}

		if($iblock_type_id === false)
			return $arResult;
		else
			return $arResult[$iblock_type_id];
	}

	public static function GetDefaultSocnetPermission()
	{
		return array(
			"A" => "X", //Group owner
			"E" => "W", //Group moderator
			"K" => "W", //Group member
			"L" => "D", //Authorized users
			"N" => "D", //Everyone
			"T" => "D", //Banned
			"Z" => "D", //Request?
		);
	}

	public static function SetSocnetPermission($iblock_id, $arRoles)
	{
		global $DB, $CACHE_MANAGER;
		$iblock_id = intval($iblock_id);

		$arToDB = CLists::GetDefaultSocnetPermission();
		foreach($arToDB as $role => $permission)
			if(isset($arRoles[$role]))
				$arToDB[$role] = substr($arRoles[$role], 0, 1);
		$arToDB["A"] = "X"; //Group owner always in charge
		$arToDB["T"] = "D"; //Banned
		$arToDB["Z"] = "D"; //and Request never get to list

		$DB->Query("
			delete from b_lists_socnet_group
			where IBLOCK_ID = ".$iblock_id."
		", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		foreach($arToDB as $role => $permission)
		{
			$DB->Query("
				insert into b_lists_socnet_group
				(IBLOCK_ID, SOCNET_ROLE, PERMISSION)
				values
				(".$iblock_id.", '".$role."', '".$permission."')
			", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		}

		if(CACHED_b_lists_permission !== false)
			$CACHE_MANAGER->Clean("b_lists_perm".$iblock_id);
	}

	public static function GetSocnetPermission($iblock_id)
	{
		global $DB, $CACHE_MANAGER;
		$iblock_id = intval($iblock_id);

		$arCache = array();
		if(!array_key_exists($iblock_id, $arCache))
		{
			$arCache[$iblock_id] = CLists::GetDefaultSocnetPermission();

			if(CACHED_b_lists_permission !== false)
			{
				$cache_id = "b_lists_perm".$iblock_id;

				if($CACHE_MANAGER->Read(CACHED_b_lists_permission, $cache_id))
				{
					$arCache[$iblock_id] = $CACHE_MANAGER->Get($cache_id);
				}
				else
				{
					$res = $DB->Query("
						select SOCNET_ROLE, PERMISSION
						from b_lists_socnet_group
						where IBLOCK_ID=".$iblock_id."
					", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
					while($ar = $res->Fetch())
						$arCache[$iblock_id][$ar["SOCNET_ROLE"]] = $ar["PERMISSION"];

					$CACHE_MANAGER->Set($cache_id, $arCache[$iblock_id]);
				}
			}
			else
			{
				$res = $DB->Query("
					select SOCNET_ROLE, PERMISSION
					from b_lists_socnet_group
					where IBLOCK_ID=".$iblock_id."
				", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
				while($ar = $res->Fetch())
					$arCache[$iblock_id][$ar["SOCNET_ROLE"]] = $ar["PERMISSION"];
			}

			$arCache[$iblock_id]["A"] = "X"; //Group owner always in charge
			$arCache[$iblock_id]["T"] = "D"; //Banned
			$arCache[$iblock_id]["Z"] = "D"; //and Request never get to list
		}

		return $arCache[$iblock_id];
	}

	public static function GetIBlockPermission($iblock_id, $user_id)
	{
		global $USER;

		//IBlock permissions by default
		$Permission = CIBlock::GetPermission($iblock_id, $user_id);
		if($Permission < "W")
		{
			$arIBlock = CIBlock::GetArrayByID($iblock_id);
			if($arIBlock)
			{
				//Check if iblock is list
				$arListsPerm = CLists::GetPermission($arIBlock["IBLOCK_TYPE_ID"]);
				if(count($arListsPerm))
				{
					//User groups
					if($user_id == $USER->GetID())
						$arUserGroups = $USER->GetUserGroupArray();
					else
						$arUserGroups = $USER->GetUserGroup($user_id);

					//One of lists admins
					if(count(array_intersect($arListsPerm, $arUserGroups)))
						$Permission = "X";
				}
			}
		}

		if(
			$Permission < "W"
			&& $arIBlock["SOCNET_GROUP_ID"]
			&& CModule::IncludeModule('socialnetwork')
		)
		{
			$arSocnetPerm = CLists::GetSocnetPermission($iblock_id);
			$socnet_role = CSocNetUserToGroup::GetUserRole($USER->GetID(), $arIBlock["SOCNET_GROUP_ID"]);
			$Permission = $arSocnetPerm[$socnet_role];
		}
		return $Permission;
	}

	public static function GetIBlockTypes($language_id = false)
	{
		global $DB;
		$res = $DB->Query("
			SELECT IBLOCK_TYPE_ID, NAME
			FROM b_iblock_type_lang
			WHERE
				LID = '".$DB->ForSQL($language_id===false? LANGUAGE_ID: $language_id)."'
				AND EXISTS (
					SELECT *
					FROM b_lists_permission
					WHERE b_lists_permission.IBLOCK_TYPE_ID = b_iblock_type_lang.IBLOCK_TYPE_ID
				)
		", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		return $res;
	}

	public static function GetIBlocks($iblock_type_id, $check_permissions, $socnet_group_id = false)
	{
		$arOrder = array(
			"SORT" => "ASC",
			"NAME" => "ASC",
		);

		$arFilter = array(
			"ACTIVE" => "Y",
			"SITE_ID" => SITE_ID,
			"TYPE" => $iblock_type_id,
			"CHECK_PERMISSIONS" => ($check_permissions? "Y": "N"), //This cancels iblock permissions for trusted users
		);
		if($socnet_group_id > 0)
			$arFilter["=SOCNET_GROUP_ID"] = $socnet_group_id;

		$arResult = array();
		$rsIBlocks = CIBlock::GetList($arOrder, $arFilter);
		while($ar = $rsIBlocks->Fetch())
		{
			$arResult[$ar["ID"]] = $ar["NAME"];
		}
		return $arResult;
	}

	public static function OnIBlockDelete($iblock_id)
	{
		global $DB, $CACHE_MANAGER;
		$iblock_id = intval($iblock_id);

		$DB->Query("delete from b_lists_url where IBLOCK_ID=".$iblock_id, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		$DB->Query("delete from b_lists_socnet_group where IBLOCK_ID=".$iblock_id, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		$CACHE_MANAGER->Clean("b_lists_perm".$iblock_id);

		CListFieldList::DeleteFields($iblock_id);
	}

	public static function OnAfterIBlockDelete($iblock_id)
	{
		if(CModule::includeModule('bizproc'))
			BizProcDocument::deleteDataIblock($iblock_id);
	}

	public static function IsEnabledSocnet()
	{
		$bActive = false;
		foreach (GetModuleEvents("socialnetwork", "OnFillSocNetFeaturesList", true) as $arEvent)
		{
			if(
				$arEvent["TO_MODULE_ID"] == "lists"
				&& $arEvent["TO_CLASS"] == "CListsSocnet"
			)
			{
				$bActive = true;
				break;
			}
		}
		return $bActive;
	}

	public static function EnableSocnet($bActive = false)
	{
		if($bActive)
		{
			if(!CLists::IsEnabledSocnet())
			{
				RegisterModuleDependences("socialnetwork", "OnFillSocNetFeaturesList", "lists", "CListsSocnet", "OnFillSocNetFeaturesList");
				RegisterModuleDependences("socialnetwork", "OnFillSocNetMenu", "lists", "CListsSocnet", "OnFillSocNetMenu");
				RegisterModuleDependences("socialnetwork", "OnParseSocNetComponentPath", "lists", "CListsSocnet", "OnParseSocNetComponentPath");
				RegisterModuleDependences("socialnetwork", "OnInitSocNetComponentVariables", "lists", "CListsSocnet", "OnInitSocNetComponentVariables");
			}
		}
		else
		{
			if(CLists::IsEnabledSocnet())
			{
				UnRegisterModuleDependences("socialnetwork", "OnFillSocNetFeaturesList", "lists", "CListsSocnet", "OnFillSocNetFeaturesList");
				UnRegisterModuleDependences("socialnetwork", "OnFillSocNetMenu", "lists", "CListsSocnet", "OnFillSocNetMenu");
				UnRegisterModuleDependences("socialnetwork", "OnParseSocNetComponentPath", "lists", "CListsSocnet", "OnParseSocNetComponentPath");
				UnRegisterModuleDependences("socialnetwork", "OnInitSocNetComponentVariables", "lists", "CListsSocnet", "OnInitSocNetComponentVariables");
			}
		}
	}

	public static function OnSharepointCreateProperty($arInputFields)
	{
		global $DB;
		$iblock_id = intval($arInputFields["IBLOCK_ID"]);
		if($iblock_id > 0)
		{
			//Check if there is at list one field defined for given iblock
			$rsFields = $DB->Query("
				SELECT * FROM b_lists_field
				WHERE IBLOCK_ID = ".$iblock_id."
				ORDER BY SORT ASC
			", false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if($rsFields->Fetch())
			{

				$arNewFields = array(
					"SORT" => 500,
					"NAME" => $arInputFields["SP_FIELD"],
				);

				if(substr($arInputFields["FIELD_ID"], 0, 9) == "PROPERTY_")
				{
					$arNewFields["ID"] = substr($arInputFields["FIELD_ID"], 9);
					$arNewFields["TYPE"] = "S";
				}
				else
					$arNewFields["TYPE"] = $arInputFields["FIELD_ID"];

				//Publish property on the list
				$obList = new CList($iblock_id);
				$obList->AddField($arNewFields);
			}
		}
	}

	public static function OnSharepointCheckAccess($iblock_id)
	{
		global $USER;
		$arIBlock = CIBlock::GetArrayByID($iblock_id);
		if($arIBlock)
		{
			//Check if iblock is list
			$arListsPerm = CLists::GetPermission($arIBlock["IBLOCK_TYPE_ID"]);
			if(count($arListsPerm))
			{
				//User groups
				$arUserGroups = $USER->GetUserGroupArray();
				//One of lists admins
				if(count(array_intersect($arListsPerm, $arUserGroups)))
					return true;
				else
					return false;
			}
		}
	}

	public static function setLiveFeed($checked, $iblockId)
	{
		global $DB;
		$iblockId = intval($iblockId);
		$checked = intval($checked);

		$resultQuery = $DB->Query("SELECT LIVE_FEED FROM b_lists_url WHERE IBLOCK_ID = ".$iblockId);
		$resultData = $resultQuery->fetch();

		if($resultData)
		{
			if($resultData["LIVE_FEED"] != $checked)
				$DB->Query("UPDATE b_lists_url SET LIVE_FEED = '".$checked."' WHERE IBLOCK_ID = ".$iblockId);
		}
		else
		{
			$url = '/'.$iblockId.'/element/#section_id#/#element_id#/';
			$DB->Query("INSERT INTO b_lists_url (IBLOCK_ID, URL, LIVE_FEED) values (".$iblockId.", '".$DB->ForSQL($url)."', ".$checked.")");
		}
	}

    public static function getLiveFeed($iblockId)
	{
		global $DB;
		$iblockId = intval($iblockId);

		$resultQuery = $DB->Query("SELECT LIVE_FEED FROM b_lists_url WHERE IBLOCK_ID = ".$iblockId);
		$resultData = $resultQuery->fetch();

		if ($resultData)
			return $resultData["LIVE_FEED"];
		else
			return "";
	}

	public static function getCountProcessesUser($userId, $iblockTypeId)
	{
		$userId = intval($userId);
		return CIBlockElement::getList(
			array(),
			array('CREATED_BY' => $userId, 'IBLOCK_TYPE' => $iblockTypeId),
			true,
			false,
			array('ID')
		);
	}

	public static function generateMnemonicCode($integerCode = 0)
	{
		if(!$integerCode)
			$integerCode = time();

		$code = '';
		for ($i = 1; $integerCode >= 0 && $i < 10; $i++)
		{
			$code = chr(0x41 + ($integerCode % pow(26, $i) / pow(26, $i - 1))) . $code;
			$integerCode -= pow(26, $i);
		}
		return $code;
	}

	static public function OnAfterIBlockElementDelete($fields)
	{
		if(CModule::includeModule('bizproc'))
		{
			$errors = array();

			$iblockType = COption::getOptionString("lists", "livefeed_iblock_type_id");

			$iblockQuery = CIBlock::getList(array(), array('ID' => $fields['IBLOCK_ID']));
			if($iblock = $iblockQuery->fetch())
			{
				$iblockType = $iblock["IBLOCK_TYPE_ID"];
			}

			$states = CBPStateService::getDocumentStates(BizprocDocument::getDocumentComplexId($iblockType, $fields['ID']));
			$listWorkflowId = array();
			foreach ($states as $workflowId => $state)
			{
				$listWorkflowId[] = $workflowId;
			}

			self::deleteSocnetLog($listWorkflowId);

			CBPDocument::onDocumentDelete(BizprocDocument::getDocumentComplexId($iblockType, $fields['ID']), $errors);
		}

		$propertyQuery = CIBlockElement::getProperty(
			$fields['IBLOCK_ID'], $fields['ID'], 'sort', 'asc', array('ACTIVE'=>'Y'));
		while($property = $propertyQuery->fetch())
		{
			$userType = \CIBlockProperty::getUserType($property['USER_TYPE']);
			if (array_key_exists('DeleteAllAttachedFiles', $userType))
			{
				call_user_func_array($userType['DeleteAllAttachedFiles'], array($fields['ID']));
			}
		}
	}

	/**
	 * @param string $workflowId
	 * @param string $iblockType
	 * @param int $elementId
	 * @param int $iblockId
	 * @param string $action Action stop or delete
	 * @return string error
	 */
	public static function completeWorkflow($workflowId, $iblockType, $elementId, $iblockId, $action)
	{
		if(!Loader::includeModule('bizproc'))
		{
			return Loc::getMessage('LISTS_MODULE_BIZPROC_NOT_INSTALLED');
		}

		global $USER;
		$userId = $USER->getID();

		$documentType = BizprocDocument::generateDocumentComplexType($iblockType, $iblockId);
		$documentId = BizprocDocument::getDocumentComplexId($iblockType, $elementId);
		$documentStates = CBPDocument::getDocumentStates($documentType, $documentId);

		$permission = CBPDocument::canUserOperateDocument(
			($action == 'stop') ? CBPCanUserOperateOperation::StartWorkflow :
				CBPCanUserOperateOperation::CreateWorkflow,
			$userId,
			$documentId,
			array("DocumentStates" => $documentStates)
		);

		if(!$permission)
		{
			return Loc::getMessage('LISTS_ACCESS_DENIED');
		}

		$stringError = '';

		if($action == 'stop')
		{
			$errors = array();
			CBPDocument::terminateWorkflow(
				$workflowId,
				$documentId,
				$errors
			);

			if (!empty($errors))
			{
				$stringError = '';
				foreach ($errors as $error)
					$stringError .= $error['message'];
				$listError[] = array('id' => 'stopBizproc', 'text' => $stringError);
			}
		}
		else
		{
			$errors = array();
			if (isset($documentStates[$workflowId]['WORKFLOW_STATUS']) &&
				$documentStates[$workflowId]['WORKFLOW_STATUS'] !== null)
			{
				CBPDocument::terminateWorkflow(
					$workflowId,
					$documentId,
					$errors
				);
			}

			if (!empty($errors))
			{
				$stringError = '';
				foreach ($errors as $error)
					$stringError .= $error['message'];
				$listError[] = array('id' => 'stopBizproc', 'text' => $stringError);
			}
			else
			{
				CBPTaskService::deleteByWorkflow($workflowId);
				CBPTrackingService::deleteByWorkflow($workflowId);
				CBPStateService::deleteWorkflow($workflowId);
			}
		}

		if(empty($listError) && Loader::includeModule('socialnetwork') &&
			$iblockType == COption::getOptionString("lists", "livefeed_iblock_type_id"))
		{
			$sourceId = CBPStateService::getWorkflowIntegerId($workflowId);
			$resultQuery = CSocNetLog::getList(
				array(),
				array('EVENT_ID' => 'lists_new_element', 'SOURCE_ID' => $sourceId),
				false,
				false,
				array('ID')
			);
			while ($log = $resultQuery->fetch())
			{
				CSocNetLog::delete($log['ID']);
			}
		}

		if (!empty($listError))
		{
			$errorObject = new CAdminException($listError);
			$stringError = $errorObject->getString();
		}

		return $stringError;
	}

	public static function deleteSocnetLog(array $listWorkflowId)
	{
		if(CModule::includeModule('socialnetwork'))
		{
			foreach ($listWorkflowId as $workflowId)
			{
				$sourceId = CBPStateService::getWorkflowIntegerId($workflowId);
				$resultQuery = CSocNetLog::getList(
					array(),
					array('EVENT_ID' => 'lists_new_element', 'SOURCE_ID' => $sourceId),
					false,
					false,
					array('ID')
				);
				while ($log = $resultQuery->fetch())
				{
					CSocNetLog::delete($log['ID']);
				}
			}
		}
	}

	/**
	 * @param $iblockId
	 * @param array $errors - an array of errors that occurred array(0 => 'error message')
	 * @return bool
	 */
	public static function copyIblock($iblockId, array &$errors)
	{
		$iblockId = (int)$iblockId;
		if(!$iblockId)
		{
			$errors[] = Loc::getMessage('LISTS_REQUIRED_PARAMETER', array('#parameter#' => 'iblockId'));
			return false;
		}

		/* We obtain data on old iblock and add a new iblock */
		$query = CIBlock::getList(array(), array('ID' => $iblockId), true);
		$iblock = $query->fetch();
		if(!$iblock)
		{
			$errors[] = Loc::getMessage('LISTS_COPY_IBLOCK_ERROR_GET_DATA');
			return false;
		}
		$iblockMessage = CIBlock::getMessages($iblockId);
		$iblock = array_merge($iblock, $iblockMessage);

		$iblock['NAME'] = $iblock['NAME'].Loc::getMessage('LISTS_COPY_IBLOCK_NAME_TITLE');
		if(!empty($iblock['PICTURE']))
		{
			$iblock['PICTURE'] = CFile::makeFileArray($iblock['PICTURE']);
		}
		$iblockObject = new CIBlock;
		if(!$iblockObject)
		{
			$errors[] = Loc::getMessage('LISTS_COPY_IBLOCK_ERROR_GET_DATA');
			return false;
		}
		$copyIblockId = $iblockObject->add($iblock);
		if(!$copyIblockId)
		{
			$errors[] = Loc::getMessage('LISTS_COPY_IBLOCK_ERROR_GET_DATA');
			return false;
		}

		/* Set right */
		$rights = array();
		if($iblock['RIGHTS_MODE'] == 'E')
		{
			$rightObject = new CIBlockRights($iblockId);
			$i = 0;
			foreach($rightObject->getRights() as $right)
			{
				$rights['n'.($i++)] = array(
					'GROUP_CODE' => $right['GROUP_CODE'],
					'DO_CLEAN' => 'N',
					'TASK_ID' => $right['TASK_ID'],
				);
			}
		}
		else
		{
			$i = 0;
			if(!empty($iblock['SOCNET_GROUP_ID']))
			{
				$socnetPerm = self::getSocnetPermission($iblockId);
				foreach($socnetPerm as $role => $permission)
				{
					if($permission > "W")
						$permission = "W";
					switch($role)
					{
						case "A":
						case "E":
						case "K":
							$rights['n'.($i++)] = array(
								"GROUP_CODE" => "SG".$iblock['SOCNET_GROUP_ID']."_".$role,
								"IS_INHERITED" => "N",
								"TASK_ID" => CIBlockRights::letterToTask($permission),
							);
							break;
						case "L":
							$rights['n'.($i++)] = array(
								"GROUP_CODE" => "AU",
								"IS_INHERITED" => "N",
								"TASK_ID" => CIBlockRights::letterToTask($permission),
							);
							break;
						case "N":
							$rights['n'.($i++)] = array(
								"GROUP_CODE" => "G2",
								"IS_INHERITED" => "N",
								"TASK_ID" => CIBlockRights::letterToTask($permission),
							);
							break;
					}
				}
			}
			else
			{
				$groupPermissions = CIBlock::getGroupPermissions($iblockId);
				foreach($groupPermissions as $groupId => $permission)
				{
					if($permission > 'W')
						$rights['n'.($i++)] = array(
							'GROUP_CODE' => 'G'.$groupId,
							'IS_INHERITED' => 'N',
							'TASK_ID' => CIBlockRights::letterToTask($permission),
						);
				}
			}

		}
		$iblock['RIGHTS'] = $rights;
		$resultIblock = $iblockObject->update($copyIblockId, $iblock);
		if(!$resultIblock)
			$errors[] = Loc::getMessage('LISTS_COPY_IBLOCK_ERROR_SET_RIGHT');

		/* Add fields */
		$listObject = new CList($iblockId);
		$fields = $listObject->getFields();
		$copyListObject = new CList($copyIblockId);
		foreach($fields as $fieldId => $field)
		{
			$copyFields = array(
				'NAME' => $field['NAME'],
				'SORT' => $field['SORT'],
				'MULTIPLE' => $field['MULTIPLE'],
				'IS_REQUIRED' => $field['IS_REQUIRED'],
				'IBLOCK_ID' => $copyIblockId,
				'SETTINGS' => $field['SETTINGS'],
				'DEFAULT_VALUE' => $field['DEFAULT_VALUE'],
				'TYPE' => $field['TYPE'],
				'PROPERTY_TYPE' => $field['PROPERTY_TYPE'],
			);

			if(!$listObject->is_field($fieldId))
			{
				if($field['TYPE'] == 'L')
				{
					$enum = CIBlockPropertyEnum::getList(array(), array('PROPERTY_ID' => $field['ID']));
					while($listData = $enum->fetch())
					{
						$copyFields['VALUES'][] = array(
							'XML_ID' => $listData['XML_ID'],
							'VALUE' => $listData['VALUE'],
							'DEF' => $listData['DEF'],
							'SORT' => $listData['SORT']
						);
					}
				}

				$copyFields['CODE'] = $field['CODE'];
				$copyFields['LINK_IBLOCK_ID'] = $field['LINK_IBLOCK_ID'];
				if(!empty($field['PROPERTY_USER_TYPE']['USER_TYPE']))
					$copyFields['USER_TYPE'] = $field['PROPERTY_USER_TYPE']['USER_TYPE'];
				if(!empty($field['ROW_COUNT']))
					$copyFields['ROW_COUNT'] = $field['ROW_COUNT'];
				if(!empty($field['COL_COUNT']))
					$copyFields['COL_COUNT'] = $field['COL_COUNT'];
				if(!empty($field['USER_TYPE_SETTINGS']))
					$copyFields['USER_TYPE_SETTINGS'] = $field['USER_TYPE_SETTINGS'];
			}

			if($fieldId == 'NAME')
			{
				$resultUpdateField = $copyListObject->updateField("NAME", $copyFields);
				if($resultUpdateField)
					$copyListObject->save();
				else
					$errors[] = Loc::getMessage('LISTS_COPY_IBLOCK_ERROR_ADD_FIELD',
						array('#field#' => $field['NAME']));

				continue;
			}

			$copyFieldId = $copyListObject->addField($copyFields);
			if($copyFieldId)
				$copyListObject->save();
			else
				$errors[] = Loc::getMessage('LISTS_COPY_IBLOCK_ERROR_ADD_FIELD',
					array('#field#' => $field['NAME']));
		}

		/* Copy Workflow Template */
		// Make a copy workflow templates

		return true;
	}

	public static function checkChangedFields($iblockId, $elementId, array $select, array $elementFields, array $elementProperty)
	{
		$changedFields = array();
		/* We get the new data element. */
		$elementNewData = array();
		$elementQuery = CIBlockElement::getList(
			array(), array('IBLOCK_ID' => $iblockId, '=ID' => $elementId), false, false, $select);
		$elementObject = $elementQuery->getNextElement();

		if(is_object($elementObject))
			$elementNewData = $elementObject->getFields();

		$elementOldData = $elementFields;
		unset($elementNewData["TIMESTAMP_X"]);
		unset($elementOldData["TIMESTAMP_X"]);

		$elementNewData["PROPERTY_VALUES"] = array();
		if(is_object($elementObject))
		{
			$propertyQuery = CIBlockElement::getProperty(
				$iblockId,
				$elementId,
				array("sort"=>"asc", "id"=>"asc", "enum_sort"=>"asc", "value_id"=>"asc"),
				array("ACTIVE"=>"Y", "EMPTY"=>"N")
			);
			while($property = $propertyQuery->fetch())
			{
				$propertyId = $property["ID"];
				if(!array_key_exists($propertyId, $elementNewData["PROPERTY_VALUES"]))
				{
					$elementNewData["PROPERTY_VALUES"][$propertyId] = $property;
					unset($elementNewData["PROPERTY_VALUES"][$propertyId]["DESCRIPTION"]);
					unset($elementNewData["PROPERTY_VALUES"][$propertyId]["VALUE_ENUM_ID"]);
					unset($elementNewData["PROPERTY_VALUES"][$propertyId]["VALUE_ENUM"]);
					unset($elementNewData["PROPERTY_VALUES"][$propertyId]["VALUE_XML_ID"]);
					$elementNewData["PROPERTY_VALUES"][$propertyId]["FULL_VALUES"] = array();
					$elementNewData["PROPERTY_VALUES"][$propertyId]["VALUES_LIST"] = array();
				}

				$elementNewData["PROPERTY_VALUES"][$propertyId]["FULL_VALUES"][$property["PROPERTY_VALUE_ID"]] = array(
					"VALUE" => $property["VALUE"],
					"DESCRIPTION" => $property["DESCRIPTION"],
				);
				$elementNewData["PROPERTY_VALUES"][$propertyId]["VALUES_LIST"][$property["PROPERTY_VALUE_ID"]] = $property["VALUE"];
			}
		}

		$elementOldData["PROPERTY_VALUES"] = $elementProperty;

		/* Check added or deleted fields. */
		$listNewFieldIdToDelete = array();
		$listOldFieldIdToDelete = array();
		$differences = array_diff_key($elementNewData, $elementOldData);
		foreach(array_keys($differences) as $fieldId)
		{
			if($fieldId[0] === '~')
				continue;
			$changedFields[] = $fieldId;
			$listNewFieldIdToDelete["FIELD"][] = $fieldId;
		}
		$differences = array_diff_key($elementOldData, $elementNewData);
		foreach(array_keys($differences) as $fieldId)
		{
			if($fieldId[0] === '~')
				continue;
			$changedFields[] = $fieldId;
			$listOldFieldIdToDelete["FIELD"][] = $fieldId;
		}

		$differences = array_diff_key(
			$elementNewData["PROPERTY_VALUES"],
			$elementOldData["PROPERTY_VALUES"]
		);
		foreach(array_keys($differences) as $fieldId)
		{
			$listNewFieldIdToDelete["PROPERTY"][] = $fieldId;

			if(!empty($elementNewData["PROPERTY_VALUES"][$fieldId]["CODE"]))
				$fieldId = "PROPERTY_".$elementNewData["PROPERTY_VALUES"][$fieldId]["CODE"];
			else
				$fieldId = "PROPERTY_".$fieldId;
			$changedFields[] = $fieldId;
		}
		$differences = array_diff_key(
			$elementOldData["PROPERTY_VALUES"],
			$elementNewData["PROPERTY_VALUES"]
		);
		foreach(array_keys($differences) as $fieldId)
		{
			$listOldFieldIdToDelete["PROPERTY"][] = $fieldId;

			if(!empty($elementOldData["PROPERTY_VALUES"][$fieldId]["CODE"]))
				$fieldId = "PROPERTY_".$elementOldData["PROPERTY_VALUES"][$fieldId]["CODE"];
			else
				$fieldId = "PROPERTY_".$fieldId;
			$changedFields[] = $fieldId;
		}

		foreach($listNewFieldIdToDelete as $typeField => $listField)
		{
			if($typeField == "FIELD")
				foreach($listField as $fieldId)
					unset($elementNewData[$fieldId]);
			elseif($typeField == "PROPERTY")
				foreach($listField as $fieldId)
					unset($elementNewData["PROPERTY_VALUES"][$fieldId]);
		}
		foreach($listOldFieldIdToDelete as $typeField => $listField)
		{
			if($typeField == "FIELD")
				foreach($listField as $fieldId)
					unset($elementOldData[$fieldId]);
			elseif($typeField == "PROPERTY")
				foreach($listField as $fieldId)
					unset($elementOldData["PROPERTY_VALUES"][$fieldId]);
		}

		/* Preparing arrays to compare */
		$listObject = new CList($iblockId);
		foreach($elementNewData as $fieldId => $fieldValue)
		{
			if(!$listObject->is_field($fieldId) && $fieldId != "PROPERTY_VALUES")
			{
				unset($elementNewData[$fieldId]);
			}
			elseif($fieldId == "PROPERTY_VALUES")
			{
				foreach($fieldValue as $propertyId => $propertyData)
				{
					if(!empty($propertyData["CODE"]))
						$elementNewData["PROPERTY_".$propertyData["CODE"]] = $propertyData["VALUES_LIST"];
					else
						$elementNewData["PROPERTY_".$propertyData["ID"]] = $propertyData["VALUES_LIST"];

					unset($elementNewData["PROPERTY_VALUES"][$propertyId]);
				}
				unset($elementNewData["PROPERTY_VALUES"]);
			}
		}
		foreach($elementOldData as $fieldId => $fieldValue)
		{
			if(!$listObject->is_field($fieldId) && $fieldId != "PROPERTY_VALUES")
			{
				unset($elementOldData[$fieldId]);
			}
			elseif($fieldId == "PROPERTY_VALUES")
			{
				foreach($fieldValue as $propertyId => $propertyData)
				{
					if(!empty($propertyData["CODE"]))
						$elementOldData["PROPERTY_".$propertyData["CODE"]] = $propertyData["VALUES_LIST"];
					else
						$elementOldData["PROPERTY_".$propertyData["ID"]] = $propertyData["VALUES_LIST"];

					unset($elementOldData["PROPERTY_VALUES"][$propertyId]);
				}
				unset($elementOldData["PROPERTY_VALUES"]);
			}
		}

		/* Compares the value */
		foreach($elementNewData as $fieldName => $fieldValue)
		{
			if(is_array($fieldValue))
			{
				if(is_array(current($fieldValue)))
				{
					$firstValues = array();
					$secondValues = array();
					foreach($fieldValue as $values)
						$firstValues = $values;
					foreach($elementOldData[$fieldName] as $values)
						$secondValues = $values;

					if(array_key_exists("TEXT", $firstValues))
					{
						$differences = array_diff($firstValues, $secondValues);
						if(!empty($differences))
							$changedFields[] = $fieldName;
					}
					else
					{
						if(count($firstValues) != count($secondValues))
							$changedFields[] = $fieldName;
					}
				}
				else
				{
					$differences = array_diff($fieldValue, $elementOldData[$fieldName]);
					if(!empty($differences))
						$changedFields[] = $fieldName;
				}
			}
			else
			{
				if(strcmp((string)$fieldValue, (string)$elementOldData[$fieldName]) !== 0)
					$changedFields[] = $fieldName;
			}
		}

		return $changedFields;
	}

	public static function deleteListsUrl($iblockId)
	{
		global $DB;
		$iblockId = intval($iblockId);
		$DB->Query("delete from b_lists_url where IBLOCK_ID="
			.$iblockId, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
	}

}
?>