<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if (!Loader::includeModule('bizproc'))
{
	return;
}

class BizprocDocument extends CIBlockDocument
{
	const DOCUMENT_TYPE_PREFIX = 'iblock_';
	private static $cachedTasks;

	public static function generateDocumentType($iblockId)
	{
		$iblockId = (int)$iblockId;
		return self::DOCUMENT_TYPE_PREFIX . $iblockId;
	}

	public static function generateDocumentComplexType($iblockType, $iblockId)
	{
		if($iblockType == COption::GetOptionString("lists", "livefeed_iblock_type_id"))
			return array('lists', get_called_class(), self::generateDocumentType($iblockId));
		else
			return array('iblock', 'CIBlockDocument', self::generateDocumentType($iblockId));
	}

	public static function getDocumentComplexId($iblockType, $documentId)
	{
		if($iblockType == COption::getOptionString("lists", "livefeed_iblock_type_id"))
			return array('lists', get_called_class(), $documentId);
		else
			return array('iblock', 'CIBlockDocument', $documentId);
	}

	static public function OnAfterIBlockElementDelete($fields)
	{
		$errors = array();
		if(Loader::includeModule('socialnetwork'))
		{
			$states = CBPStateService::getDocumentStates(array('lists', get_called_class(), $fields['ID']));
			foreach ($states as $workflowId => $state)
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
		CBPDocument::onDocumentDelete(array('lists', get_called_class(), $fields['ID']), $errors);
	}

	public static function deleteDataIblock($iblockId)
	{
		$iblockId = intval($iblockId);
		$documentType = array('lists', get_called_class(), self::generateDocumentType($iblockId));
		$errors = array();
		$templateObject = CBPWorkflowTemplateLoader::getList(
			array('ID' => 'DESC'),
			array('DOCUMENT_TYPE' => $documentType),
			false,
			false,
			array('ID')
		);
		while($template = $templateObject->fetch())
		{
			CBPDocument::deleteWorkflowTemplate($template['ID'], $documentType, $errors);
		}
	}

	/**
	 * Method returns document icon (image source path)
	 * @param $documentId
	 * @return null|string
	 * @throws CBPArgumentNullException
	 */

	public static function getDocumentIcon($documentId)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException('documentId');

		$db = CIBlockElement::getList(
			array(),
			array('ID' => $documentId, 'SHOW_NEW'=>'Y', 'SHOW_HISTORY' => 'Y'),
			false,
			false,
			array('ID', 'IBLOCK_ID')
		);
		if ($element = $db->fetch())
		{
			$iblockPicture = CIBlock::getArrayByID($element['IBLOCK_ID'], 'PICTURE');
			$imageFile = CFile::getFileArray($iblockPicture);
			if(!empty($imageFile['SRC']))
				return $imageFile['SRC'];
		}

		return null;
	}

	/**
	 * @param string $documentId - document id.
	 * @return array - document fields array.
	 */
	static public function GetDocument($documentId)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		$result = null;

		$dbDocumentList = CIBlockElement::getList(
			array(),
			array("ID" => $documentId, "SHOW_NEW"=>"Y", "SHOW_HISTORY" => "Y")
		);
		if ($objDocument = $dbDocumentList->getNextElement(false, true))
		{
			$fields = $objDocument->getFields();
			$properties = $objDocument->getProperties();

			foreach ($fields as $fieldKey => $fieldValue)
			{
				if (substr($fieldKey, 0, 1) == "~")
					continue;

				$result[$fieldKey] = $fieldValue;
				if (in_array($fieldKey, array("MODIFIED_BY", "CREATED_BY")))
				{
					$result[$fieldKey] = "user_".$fieldValue;
					$result[$fieldKey."_PRINTABLE"] = $fields[($fieldKey == "MODIFIED_BY") ? "USER_NAME" : "CREATED_USER_NAME"];
				}
				elseif (in_array($fieldKey, array("PREVIEW_TEXT", "DETAIL_TEXT")))
				{
					if ($fields[$fieldKey."_TYPE"] == "html")
						$result[$fieldKey] = HTMLToTxt($fields["~".$fieldKey]);
				}
			}

			foreach ($properties as $propertyKey => $propertyValue)
			{
				if (strlen($propertyValue["USER_TYPE"]) > 0)
				{
					if ($propertyValue["USER_TYPE"] == "UserID"
						|| $propertyValue["USER_TYPE"] == "employee" && (COption::getOptionString("bizproc", "employee_compatible_mode", "N") != "Y"))
					{
						$propertyValueArray = $propertyValue["VALUE"];
						$propertyKeyArray = isset($propertyValue["VALUE_ENUM_ID"]) ? $propertyValue["VALUE_ENUM_ID"] : $propertyValue["PROPERTY_VALUE_ID"];
						if (!is_array($propertyValueArray))
						{
							$userQuery = CUser::getByID($propertyValueArray);
							if ($userArray = $userQuery->getNext())
							{
								$result["PROPERTY_".$propertyKey] = "user_".intval($propertyValueArray);
								$result["PROPERTY_".$propertyKey."_PRINTABLE"] = "(".$userArray["LOGIN"].")".((strlen($userArray["NAME"]) > 0 || strlen($userArray["LAST_NAME"]) > 0) ? " " : "").$userArray["NAME"].((strlen($userArray["NAME"]) > 0 && strlen($userArray["LAST_NAME"]) > 0) ? " " : "").$userArray["LAST_NAME"];
							}
						}
						else
						{
							for ($i = 0, $cnt = count($propertyValueArray); $i < $cnt; $i++)
							{
								$userQuery = CUser::getByID($propertyValueArray[$i]);
								if ($userArray = $userQuery->getNext())
								{
									$result["PROPERTY_".$propertyKey][$propertyKeyArray[$i]] = "user_".intval($propertyValueArray[$i]);
									$result["PROPERTY_".$propertyKey."_PRINTABLE"][$propertyKeyArray[$i]] = "(".$userArray["LOGIN"].")".((strlen($userArray["NAME"]) > 0 || strlen($userArray["LAST_NAME"]) > 0) ? " " : "").$userArray["NAME"].((strlen($userArray["NAME"]) > 0 && strlen($userArray["LAST_NAME"]) > 0) ? " " : "").$userArray["LAST_NAME"];
								}
							}
						}
					}
					else
					{
						$result["PROPERTY_".$propertyKey] = $propertyValue["VALUE"];
					}
				}
				elseif ($propertyValue["PROPERTY_TYPE"] == "L")
				{
					$propertyValueArray = $propertyValue["VALUE"];
					$propertyKeyArray = ($propertyValue["VALUE_XML_ID"]);
					if (!is_array($propertyValueArray))
					{
						$propertyValueArray = array($propertyValueArray);
						$propertyKeyArray = array($propertyKeyArray);
					}

					for ($i = 0, $cnt = count($propertyValueArray); $i < $cnt; $i++)
						$result["PROPERTY_".$propertyKey][$propertyKeyArray[$i]] = $propertyValueArray[$i];
				}
				elseif ($propertyValue["PROPERTY_TYPE"] == "F")
				{
					$propertyValueArray = $propertyValue["VALUE"];
					if (!is_array($propertyValueArray))
						$propertyValueArray = array($propertyValueArray);

					foreach ($propertyValueArray as $v)
					{
						$userArray = CFile::getFileArray($v);
						if ($userArray)
						{
							$result["PROPERTY_".$propertyKey][intval($v)] = $userArray["SRC"];
							$result["PROPERTY_".$propertyKey."_printable"][intval($v)] = "[url=/bitrix/tools/bizproc_show_file.php?f=".htmlspecialcharsbx($userArray["FILE_NAME"])."&i=".$v."&h=".md5($userArray["SUBDIR"])."]".htmlspecialcharsbx($userArray["ORIGINAL_NAME"])."[/url]";
						}
					}
				}
				else
				{
					$result["PROPERTY_".$propertyKey] = $propertyValue["VALUE"];
				}
			}

			$documentFields = static::getDocumentFields(static::getDocumentType($documentId));
			foreach ($documentFields as $fieldKey => $field)
			{
				if (!array_key_exists($fieldKey, $result))
					$result[$fieldKey] = null;
			}
		}

		return $result;
	}

	static public function GetDocumentFields($documentType)
	{
		$iblockId = intval(substr($documentType, strlen("iblock_")));
		if ($iblockId <= 0)
			throw new CBPArgumentOutOfRangeException("documentType", $documentType);

		$documentFieldTypes = self::getDocumentFieldTypes($documentType);

		$result = array(
			"ID" => array(
				"Name" => GetMessage("IBD_FIELD_ID"),
				"Type" => "int",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			"TIMESTAMP_X" => array(
				"Name" => GetMessage("IBD_FIELD_TIMESTAMP_X"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"MODIFIED_BY" => array(
				"Name" => GetMessage("IBD_FIELD_MODYFIED"),
				"Type" => "user",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"MODIFIED_BY_PRINTABLE" => array(
				"Name" => GetMessage("IBD_FIELD_MODIFIED_BY_USER_PRINTABLE"),
				"Type" => "string",
				"Filterable" => false,
				"Editable" => false,
				"Required" => false,
			),
			"DATE_CREATE" => array(
				"Name" => GetMessage("IBD_FIELD_DATE_CREATE"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"CREATED_BY" => array(
				"Name" => GetMessage("IBD_FIELD_CREATED"),
				"Type" => "user",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			"CREATED_BY_PRINTABLE" => array(
				"Name" => GetMessage("IBD_FIELD_CREATED_BY_USER_PRINTABLE"),
				"Type" => "string",
				"Filterable" => false,
				"Editable" => false,
				"Required" => false,
			),
			"IBLOCK_ID" => array(
				"Name" => GetMessage("IBD_FIELD_IBLOCK_ID"),
				"Type" => "int",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"ACTIVE" => array(
				"Name" => GetMessage("IBD_FIELD_ACTIVE"),
				"Type" => "bool",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"BP_PUBLISHED" => array(
				"Name" => GetMessage("IBD_FIELD_BP_PUBLISHED"),
				"Type" => "bool",
				"Filterable" => false,
				"Editable" => true,
				"Required" => false,
			),
			"CODE" => array(
				"Name" => GetMessage("IBD_FIELD_CODE"),
				"Type" => "string",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"XML_ID" => array(
				"Name" => GetMessage("IBD_FIELD_XML_ID"),
				"Type" => "string",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
		);

		$keys = array_keys($result);
		foreach ($keys as $key)
			$result[$key]["Multiple"] = false;

		$dbProperties = CIBlockProperty::getList(
			array("sort" => "asc", "name" => "asc"),
			array("IBLOCK_ID" => $iblockId, 'ACTIVE' => 'Y')
		);
		$ignoreProperty = array();
		while ($property = $dbProperties->fetch())
		{
			if (strlen(trim($property["CODE"])) > 0)
			{
				$key = "PROPERTY_".$property["CODE"];
				$ignoreProperty["PROPERTY_".$property["ID"]] = "PROPERTY_".$property["CODE"];
			}
			else
			{
				$key = "PROPERTY_".$property["ID"];
				$ignoreProperty["PROPERTY_".$property["ID"]] = 0;
			}

			$result[$key] = array(
				"Name" => $property["NAME"],
				"Filterable" => ($property["FILTRABLE"] == "Y"),
				"Editable" => true,
				"Required" => ($property["IS_REQUIRED"] == "Y"),
				"Multiple" => ($property["MULTIPLE"] == "Y"),
				"TypeReal" => $property["PROPERTY_TYPE"],
			);

			if (strlen($property["USER_TYPE"]) > 0)
			{
				$result[$key]["TypeReal"] = $property["PROPERTY_TYPE"].":".$property["USER_TYPE"];

				if ($property["USER_TYPE"] == "UserID"
					|| $property["USER_TYPE"] == "employee" && (COption::getOptionString("bizproc", "employee_compatible_mode", "N") != "Y"))
				{
					$result[$key]["Type"] = "user";
					$result[$key."_PRINTABLE"] = array(
						"Name" => $property["NAME"].GetMessage("IBD_FIELD_USERNAME_PROPERTY"),
						"Filterable" => false,
						"Editable" => false,
						"Required" => false,
						"Multiple" => ($property["MULTIPLE"] == "Y"),
						"Type" => "string",
					);
					$result[$key]["DefaultValue"] = $property["DEFAULT_VALUE"];
				}
				elseif ($property["USER_TYPE"] == "DateTime")
				{
					$result[$key]["Type"] = "datetime";
					$result[$key]["DefaultValue"] = $property["DEFAULT_VALUE"];
				}
				elseif ($property["USER_TYPE"] == "Date")
				{
					$result[$key]["Type"] = "date";
					$result[$key]["DefaultValue"] = $property["DEFAULT_VALUE"];
				}
				elseif ($property["USER_TYPE"] == "EList")
				{
					$result[$key]["Type"] = "E:EList";
					$result[$key]["Options"] = $property["LINK_IBLOCK_ID"];
				}
				elseif ($property["USER_TYPE"] == "HTML")
				{
					$result[$key]["Type"] = "S:HTML";
					$result[$key]["DefaultValue"] = $property["DEFAULT_VALUE"];
				}
				else
				{
					$result[$key]["Type"] = "string";
					$result[$key]["DefaultValue"] = $property["DEFAULT_VALUE"];
				}
			}
			elseif ($property["PROPERTY_TYPE"] == "L")
			{
				$result[$key]["Type"] = "select";

				$result[$key]["Options"] = array();
				$dbPropertyEnums = CIBlockProperty::getPropertyEnum($property["ID"]);
				while ($propertyEnum = $dbPropertyEnums->getNext())
				{
					$result[$key]["Options"][$propertyEnum["XML_ID"]] = $propertyEnum["VALUE"];
					if($propertyEnum["DEF"] == "Y")
						$result[$key]["DefaultValue"] = $propertyEnum["VALUE"];
				}
			}
			elseif ($property["PROPERTY_TYPE"] == "N")
			{
				$result[$key]["Type"] = "int";
				$result[$key]["DefaultValue"] = $property["DEFAULT_VALUE"];
			}
			elseif ($property["PROPERTY_TYPE"] == "F")
			{
				$result[$key]["Type"] = "file";
				$result[$key."_printable"] = array(
					"Name" => $property["NAME"].GetMessage("IBD_FIELD_USERNAME_PROPERTY"),
					"Filterable" => false,
					"Editable" => false,
					"Required" => false,
					"Multiple" => ($property["MULTIPLE"] == "Y"),
					"Type" => "string",
				);
			}
			elseif ($property["PROPERTY_TYPE"] == "S")
			{
				$result[$key]["Type"] = "string";
				$result[$key]["DefaultValue"] = $property["DEFAULT_VALUE"];
			}
			else
			{
				$result[$key]["Type"] = "string";
				$result[$key]["DefaultValue"] = $property["DEFAULT_VALUE"];
			}
		}

		$keys = array_keys($result);
		foreach ($keys as $k)
		{
			$result[$k]["BaseType"] = $documentFieldTypes[$result[$k]["Type"]]["BaseType"];
			$result[$k]["Complex"] = $documentFieldTypes[$result[$k]["Type"]]["Complex"];
		}

		$list = new CList($iblockId);
		$fields = $list->getFields();
		foreach($fields as $fieldId => $field)
		{
			if(empty($field["SETTINGS"]))
				$field["SETTINGS"] = array("SHOW_ADD_FORM" => 'Y', "SHOW_EDIT_FORM"=>'Y');

			if(array_key_exists($fieldId, $ignoreProperty))
			{
				$ignoreProperty[$fieldId] ? $key = $ignoreProperty[$fieldId] : $key = $fieldId;
				$result[$key]["sort"] =  $field["SORT"];
				$result[$key]["settings"] =  $field["SETTINGS"];
				if($field["ROW_COUNT"] && $field["COL_COUNT"])
				{
					$result[$key]["row_count"] = $field["ROW_COUNT"];
					$result[$key]["col_count"] = $field["COL_COUNT"];
				}
			}
			else
			{
				if (!isset($result[$fieldId]))
				{
					$result[$fieldId] = array(
						'Name' => $field['NAME'],
						'Filterable' => false,
						'Editable' => true,
						'Required' => $field['IS_REQUIRED'],
						'Multiple' => $field['MULTIPLE'],
						'Type' => $field['TYPE'],
					);
				}
				$result[$fieldId]["sort"] =  $field["SORT"];
				$result[$fieldId]["settings"] =  $field["SETTINGS"];
				if($field["ROW_COUNT"] && $field["COL_COUNT"])
				{
					$result[$fieldId]["row_count"] =  $field["ROW_COUNT"];
					$result[$fieldId]["col_count"] =  $field["COL_COUNT"];
				}
			}
		}
		
		return $result;
	}

	public static function generateMnemonicCode($integerCode)
	{
		$code = '';
		for ($i = 1; $integerCode >= 0 && $i < 10; $i++)
		{
			$code = chr(0x41 + ($integerCode % pow(26, $i) / pow(26, $i - 1))) . $code;
			$integerCode -= pow(26, $i);
		}
		return $code;
	}

	static public function AddDocumentField($documentType, $fields)
	{
		$iblockId = intval(substr($documentType, strlen("iblock_")));
		if ($iblockId <= 0)
			throw new CBPArgumentOutOfRangeException("documentType", $documentType);

		if (substr($fields["code"], 0, strlen("PROPERTY_")) == "PROPERTY_")
			$fields["code"] = substr($fields["code"], strlen("PROPERTY_"));

		$fieldsTemporary = array(
			"NAME" => $fields["name"],
			"ACTIVE" => "Y",
			"SORT" => $fields["sort"] ? $fields["sort"] : 900,
			"CODE" => $fields["code"],
			'MULTIPLE' => $fields['multiple'] == 'Y' || (string)$fields['multiple'] === '1' ? 'Y' : 'N',
			'IS_REQUIRED' => $fields['required'] == 'Y' || (string)$fields['required'] === '1' ? 'Y' : 'N',
			"IBLOCK_ID" => $iblockId,
			"FILTRABLE" => "Y",
			"SETTINGS" => $fields["settings"] ? $fields["settings"] : array("SHOW_ADD_FORM" => 'Y', "SHOW_EDIT_FORM"=>'Y'),
			"DEFAULT_VALUE" => $fields['DefaultValue']
		);

		if (strpos("0123456789", substr($fieldsTemporary["CODE"], 0, 1))!==false)
			$fieldsTemporary["CODE"] = self::generateMnemonicCode($fieldsTemporary["CODE"]);

		if (array_key_exists("additional_type_info", $fields))
			$fieldsTemporary["LINK_IBLOCK_ID"] = intval($fields["additional_type_info"]);

		if (strstr($fields["type"], ":") !== false)
		{
			list($fieldsTemporary["TYPE"], $fieldsTemporary["USER_TYPE"]) = explode(":", $fields["type"], 2);
			if ($fields["type"] == "E:EList")
				$fieldsTemporary["LINK_IBLOCK_ID"] = $fields["options"];
		}
		elseif ($fields["type"] == "user")
		{
			$fieldsTemporary["TYPE"] = "S:employee";
			$fieldsTemporary["USER_TYPE"]= "UserID";
		}
		elseif ($fields["type"] == "date")
		{
			$fieldsTemporary["TYPE"] = "S:Date";
			$fieldsTemporary["USER_TYPE"]= "Date";
		}
		elseif ($fields["type"] == "datetime")
		{
			$fieldsTemporary["TYPE"] = "S:DateTime";
			$fieldsTemporary["USER_TYPE"]= "DateTime";
		}
		elseif ($fields["type"] == "file")
		{
			$fieldsTemporary["TYPE"] = "F";
			$fieldsTemporary["USER_TYPE"]= "";
		}
		elseif ($fields["type"] == "select")
		{
			$fieldsTemporary["TYPE"] = "L";
			$fieldsTemporary["USER_TYPE"]= false;

			if (is_array($fields["options"]))
			{
				$i = 10;
				foreach ($fields["options"] as $k => $v)
				{
					$def = "N";
					if($fields['DefaultValue'] == $v)
						$def = "Y";
					$fieldsTemporary["VALUES"][] = array("XML_ID" => $k, "VALUE" => $v, "DEF" => $def, "SORT" => $i);
					$i = $i + 10;
				}
			}
			elseif (is_string($fields["options"]) && (strlen($fields["options"]) > 0))
			{
				$a = explode("\n", $fields["options"]);
				$i = 10;
				foreach ($a as $v)
				{
					$v = trim(trim($v), "\r\n");
					if (!$v)
						continue;
					$v1 = $v2 = $v;
					if (substr($v, 0, 1) == "[" && strpos($v, "]") !== false)
					{
						$v1 = substr($v, 1, strpos($v, "]") - 1);
						$v2 = trim(substr($v, strpos($v, "]") + 1));
					}
					$def = "N";
					if($fields['DefaultValue'] == $v2)
						$def = "Y";
					$fieldsTemporary["VALUES"][] = array("XML_ID" => $v1, "VALUE" => $v2, "DEF" => $def, "SORT" => $i);
					$i = $i + 10;
				}
			}
		}
		elseif($fields["type"] == "string")
		{
			$fieldsTemporary["TYPE"] = "S";

			if($fields["row_count"] && $fields["col_count"])
			{
				$fieldsTemporary["ROW_COUNT"] = $fields["row_count"];
				$fieldsTemporary["COL_COUNT"] = $fields["col_count"];
			}
			else
			{
				$fieldsTemporary["ROW_COUNT"] = 1;
				$fieldsTemporary["COL_COUNT"] = 30;
			}
		}
		elseif($fields["type"] == "text")
		{
			$fieldsTemporary["TYPE"] = "S";
			if($fields["row_count"] && $fields["col_count"])
			{
				$fieldsTemporary["ROW_COUNT"] = $fields["row_count"];
				$fieldsTemporary["COL_COUNT"] = $fields["col_count"];
			}
			else
			{
				$fieldsTemporary["ROW_COUNT"] = 4;
				$fieldsTemporary["COL_COUNT"] = 30;
			}
		}
		elseif($fields["type"] == "int" || $fields["type"] == "double")
		{
			$fieldsTemporary["TYPE"] = "N";
		}
		elseif($fields["type"] == "bool")
		{
			$fieldsTemporary["TYPE"] = "L";
			$fieldsTemporary["VALUES"][] = array("XML_ID" => 'yes', "VALUE" => GetMessage("BPVDX_YES"), "DEF" => "N", "SORT" => 10);
			$fieldsTemporary["VALUES"][] = array("XML_ID" => 'no', "VALUE" => GetMessage("BPVDX_NO"), "DEF" => "N", "SORT" => 20);
		}
		else
		{
			$fieldsTemporary["TYPE"] = $fields["type"];
			$fieldsTemporary["USER_TYPE"] = false;
		}

		$idField = false;
		$properties = CIBlockProperty::getList(
			array(),
			array("IBLOCK_ID" => $fieldsTemporary["IBLOCK_ID"], "CODE" => $fieldsTemporary["CODE"])
		);
		if(!$properties->fetch())
		{
			$listObject = new CList($iblockId);
			$idField = $listObject->addField($fieldsTemporary);
		}

		if($idField)
		{
			global $CACHE_MANAGER;
			$CACHE_MANAGER->clearByTag("lists_list_".$iblockId);
			return $idField;
		}
		return false;
	}

	public static function onTaskChange($documentId, $taskId, $taskData, $status)
	{
		CListsLiveFeed::setMessageLiveFeed($taskData['USERS'], $documentId, $taskData['WORKFLOW_ID'], false);
		if ($status == CBPTaskChangedStatus::Delegate)
		{
			$runtime = CBPRuntime::getRuntime();
			/**
			 * @var CBPAllStateService $stateService
			 */
			$stateService = $runtime->getService('StateService');
			$stateService->setStatePermissions(
				$taskData['WORKFLOW_ID'],
				array('R' => array('user_'.$taskData['USERS'][0])),
				array('setMode' => CBPSetPermissionsMode::Hold, 'setScope' => CBPSetPermissionsMode::ScopeDocument)
			);
		}
	}

	public static function onWorkflowStatusChange($documentId, $workflowId, $status)
	{
		if ($status == CBPWorkflowStatus::Completed)
		{
			CListsLiveFeed::setMessageLiveFeed(array(), $documentId, $workflowId, true);
		}
	}

	static public function getDocumentAdminPage($documentId)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		$elementQuery = CIBlockElement::getList(
			array(),
			array("ID" => $documentId, "SHOW_NEW"=>"Y", "SHOW_HISTORY" => "Y"),
			false,
			false,
			array("ID", "IBLOCK_ID", "IBLOCK_TYPE_ID", "DETAIL_PAGE_URL")
		);
		if ($element = $elementQuery->fetch())
		{
			return COption::getOptionString('lists', 'livefeed_url').'?livefeed=y&list_id='.$element["IBLOCK_ID"].'&element_id='.$documentId;
		}

		return null;
	}

	protected static function getRightsTasks()
	{
		if (self::$cachedTasks === null)
		{
			$iterator = CTask::getList(
				array("LETTER"=>"asc"),
				array(
					"MODULE_ID" => "iblock",
					"BINDING" => "iblock"
				)
			);

			while($ar = $iterator->fetch())
			{
				self::$cachedTasks[$ar["LETTER"]] = $ar;
			}
		}
		return self::$cachedTasks;
	}

	static public function GetAllowableOperations($documentType)
	{
		$iblockId = intval(substr($documentType, strlen("iblock_")));
		if ($iblockId <= 0)
			throw new CBPArgumentOutOfRangeException("documentType", $documentType);

		if (CIBlock::getArrayByID($iblockId, "RIGHTS_MODE") === "E")
		{
			$operations = array();
			$tasks = self::getRightsTasks();

			foreach($tasks as $ar)
			{
				$key = empty($ar['LETTER']) ? $ar['ID'] : $ar['LETTER'];
				$operations[$key] = $ar['TITLE'];
			}

			return $operations;
		}
		return parent::getAllowableOperations($documentType);
	}

	static public function toInternalOperations($documentType, $permissions)
	{
		$permissions = (array) $permissions;
		$tasks = self::getRightsTasks();

		$normalized = array();
		foreach ($permissions as $key => $value)
		{
			if (isset($tasks[$key]))
				$key = $tasks[$key]['ID'];
			$normalized[$key] = $value;
		}

		return $normalized;
	}

	static public function toExternalOperations($documentType, $permissions)
	{
		$permissions = (array) $permissions;
		$tasks = self::getRightsTasks();
		$letters = array();
		foreach ($tasks as $k => $t)
		{
			$letters[$t['ID']] = $k;
		}
		unset($tasks);

		$normalized = array();
		foreach ($permissions as $key => $value)
		{
			if (isset($letters[$key]))
				$key = $letters[$key];
			$normalized[$key] = $value;
		}

		return $normalized;
	}

	public static function CanUserOperateDocument($operation, $userId, $documentId, $parameters = array())
	{
		$documentId = trim($documentId);
		if (strlen($documentId) <= 0)
			return false;

		if (!array_key_exists("IBlockId", $parameters)
			&& (
				!array_key_exists("IBlockPermission", $parameters)
				|| !array_key_exists("DocumentStates", $parameters)
				|| !array_key_exists("IBlockRightsMode", $parameters)
				|| array_key_exists("IBlockRightsMode", $parameters) && ($parameters["IBlockRightsMode"] === "E")
			)
			|| !array_key_exists("CreatedBy", $parameters) && !array_key_exists("AllUserGroups", $parameters))
		{
			$elementListQuery = CIBlockElement::getList(
				array(),
				array("ID" => $documentId, "SHOW_NEW" => "Y", "SHOW_HISTORY" => "Y"),
				false,
				false,
				array("ID", "IBLOCK_ID", "CREATED_BY")
			);
			$elements = $elementListQuery->fetch();

			if (!$elements)
				return false;

			$parameters["IBlockId"] = $elements["IBLOCK_ID"];
			$parameters["CreatedBy"] = $elements["CREATED_BY"];
		}

		if (!array_key_exists("IBlockRightsMode", $parameters))
			$parameters["IBlockRightsMode"] = CIBlock::getArrayByID($parameters["IBlockId"], "RIGHTS_MODE");

		if ($parameters["IBlockRightsMode"] === "E")
		{
			if ($operation === CBPCanUserOperateOperation::ReadDocument)
				return CIBlockElementRights::userHasRightTo($parameters["IBlockId"], $documentId, "element_read");
			elseif ($operation === CBPCanUserOperateOperation::WriteDocument)
				return CIBlockElementRights::userHasRightTo($parameters["IBlockId"], $documentId, "element_edit");
			elseif (
				$operation === CBPCanUserOperateOperation::StartWorkflow
				|| $operation === CBPCanUserOperateOperation::ViewWorkflow
			)
			{
				if (CIBlockElementRights::userHasRightTo($parameters["IBlockId"], $documentId, "element_edit"))
					return true;

				if (!array_key_exists("WorkflowId", $parameters))
					return false;

				if (!CIBlockElementRights::userHasRightTo($parameters["IBlockId"], $documentId, "element_read"))
					return false;

				$userId = intval($userId);
				if (!array_key_exists("AllUserGroups", $parameters))
				{
					if (!array_key_exists("UserGroups", $parameters))
						$parameters["UserGroups"] = CUser::getUserGroup($userId);

					$parameters["AllUserGroups"] = $parameters["UserGroups"];
					if ($userId == $parameters["CreatedBy"])
						$parameters["AllUserGroups"][] = "Author";
				}

				if (!array_key_exists("DocumentStates", $parameters))
				{
					if ($operation === CBPCanUserOperateOperation::StartWorkflow)
						$parameters["DocumentStates"] = CBPWorkflowTemplateLoader::getDocumentTypeStates(array('lists', get_called_class(), self::generateDocumentType($parameters["IBlockId"])));
					else
						$parameters["DocumentStates"] = CBPDocument::getDocumentStates(
							array('lists', get_called_class(), self::generateDocumentType($parameters["IBlockId"])),
							array('lists', get_called_class(), $documentId)
						);
				}

				if (array_key_exists($parameters["WorkflowId"], $parameters["DocumentStates"]))
					$parameters["DocumentStates"] = array($parameters["WorkflowId"] => $parameters["DocumentStates"][$parameters["WorkflowId"]]);
				else
					return false;

				$allowableOperations = CBPDocument::getAllowableOperations(
					$userId,
					$parameters["AllUserGroups"],
					$parameters["DocumentStates"],
					true
				);

				if (!is_array($allowableOperations))
					return false;

				if (($operation === CBPCanUserOperateOperation::ViewWorkflow) && in_array("read", $allowableOperations)
					|| ($operation === CBPCanUserOperateOperation::StartWorkflow) && in_array("write", $allowableOperations))
					return true;

				$chop = ($operation === CBPCanUserOperateOperation::ViewWorkflow) ? "element_read" : "element_edit";

				$tasks = self::getRightsTasks();
				foreach ($allowableOperations as $op)
				{
					if (isset($tasks[$op]))
						$op = $tasks[$op]['ID'];
					$ar = CTask::getOperations($op, true);
					if (in_array($chop, $ar))
						return true;
				}
			}
			elseif (
				$operation === CBPCanUserOperateOperation::CreateWorkflow
			)
			{
				return CBPDocument::canUserOperateDocumentType(
					CBPCanUserOperateOperation::CreateWorkflow,
					$userId,
					array('lists', get_called_class(), $documentId),
					$parameters
				);
			}

			return false;
		}

		if (!array_key_exists("IBlockPermission", $parameters))
		{
			if (CModule::includeModule('lists'))
				$parameters["IBlockPermission"] = CLists::getIBlockPermission($parameters["IBlockId"], $userId);
			else
				$parameters["IBlockPermission"] = CIBlock::getPermission($parameters["IBlockId"], $userId);
		}

		if ($parameters["IBlockPermission"] <= "R")
			return false;
		elseif ($parameters["IBlockPermission"] >= "W")
			return true;

		$userId = intval($userId);
		if (!array_key_exists("AllUserGroups", $parameters))
		{
			if (!array_key_exists("UserGroups", $parameters))
				$parameters["UserGroups"] = CUser::getUserGroup($userId);

			$parameters["AllUserGroups"] = $parameters["UserGroups"];
			if ($userId == $parameters["CreatedBy"])
				$parameters["AllUserGroups"][] = "Author";
		}

		if (!array_key_exists("DocumentStates", $parameters))
		{
			$parameters["DocumentStates"] = CBPDocument::getDocumentStates(
				array("lists", get_called_class(), "iblock_".$parameters["IBlockId"]),
				array('lists', get_called_class(), $documentId)
			);
		}

		if (array_key_exists("WorkflowId", $parameters))
		{
			if (array_key_exists($parameters["WorkflowId"], $parameters["DocumentStates"]))
				$parameters["DocumentStates"] = array($parameters["WorkflowId"] => $parameters["DocumentStates"][$parameters["WorkflowId"]]);
			else
				return false;
		}

		$allowableOperations = CBPDocument::getAllowableOperations(
			$userId,
			$parameters["AllUserGroups"],
			$parameters["DocumentStates"]
		);

		if (!is_array($allowableOperations))
			return false;

		$r = false;
		switch ($operation)
		{
			case CBPCanUserOperateOperation::ViewWorkflow:
				$r = in_array("read", $allowableOperations);
				break;
			case CBPCanUserOperateOperation::StartWorkflow:
				$r = in_array("write", $allowableOperations);
				break;
			case CBPCanUserOperateOperation::CreateWorkflow:
				$r = false;
				break;
			case CBPCanUserOperateOperation::WriteDocument:
				$r = in_array("write", $allowableOperations);
				break;
			case CBPCanUserOperateOperation::ReadDocument:
				$r = in_array("read", $allowableOperations) || in_array("write", $allowableOperations);
				break;
			default:
				$r = false;
		}

		return $r;
	}

	public static function CanUserOperateDocumentType($operation, $userId, $documentType, $parameters = array())
	{
		$documentType = trim($documentType);
		if (strlen($documentType) <= 0)
			return false;

		$parameters["IBlockId"] = intval(substr($documentType, strlen("iblock_")));
		$parameters['sectionId'] = !empty($parameters['sectionId']) ? (int)$parameters['sectionId'] : 0;

		if (!array_key_exists("IBlockRightsMode", $parameters))
			$parameters["IBlockRightsMode"] = CIBlock::getArrayByID($parameters["IBlockId"], "RIGHTS_MODE");

		if ($parameters["IBlockRightsMode"] === "E")
		{
			if ($operation === CBPCanUserOperateOperation::CreateWorkflow)
				return CIBlockRights::userHasRightTo($parameters["IBlockId"], $parameters["IBlockId"], "iblock_rights_edit");
			elseif ($operation === CBPCanUserOperateOperation::WriteDocument)
				return CIBlockSectionRights::userHasRightTo($parameters["IBlockId"], $parameters["sectionId"], "section_element_bind");
			elseif ($operation === CBPCanUserOperateOperation::ViewWorkflow
				|| $operation === CBPCanUserOperateOperation::StartWorkflow)
			{
				if (!array_key_exists("WorkflowId", $parameters))
					return false;

				if ($operation === CBPCanUserOperateOperation::ViewWorkflow)
					return CIBlockRights::userHasRightTo($parameters["IBlockId"], 0, "element_read");

				if ($operation === CBPCanUserOperateOperation::StartWorkflow)
					return CIBlockSectionRights::userHasRightTo($parameters["IBlockId"], $parameters['sectionId'], "section_element_bind");


				$userId = intval($userId);
				if (!array_key_exists("AllUserGroups", $parameters))
				{
					if (!array_key_exists("UserGroups", $parameters))
						$parameters["UserGroups"] = CUser::getUserGroup($userId);

					$parameters["AllUserGroups"] = $parameters["UserGroups"];
					$parameters["AllUserGroups"][] = "Author";
				}

				if (!array_key_exists("DocumentStates", $parameters))
				{
					if ($operation === CBPCanUserOperateOperation::StartWorkflow)
						$parameters["DocumentStates"] = CBPWorkflowTemplateLoader::getDocumentTypeStates(array("lists", get_called_class(), "iblock_".$parameters["IBlockId"]));
					else
						$parameters["DocumentStates"] = CBPDocument::getDocumentStates(
							array("lists", get_called_class(), "iblock_".$parameters["IBlockId"]),
							null
						);
				}

				if (array_key_exists($parameters["WorkflowId"], $parameters["DocumentStates"]))
					$parameters["DocumentStates"] = array($parameters["WorkflowId"] => $parameters["DocumentStates"][$parameters["WorkflowId"]]);
				else
					return false;

				$allowableOperations = CBPDocument::getAllowableOperations(
					$userId,
					$parameters["AllUserGroups"],
					$parameters["DocumentStates"],
					true
				);

				if (!is_array($allowableOperations))
					return false;

				if (($operation === CBPCanUserOperateOperation::ViewWorkflow) && in_array("read", $allowableOperations)
					|| ($operation === CBPCanUserOperateOperation::StartWorkflow) && in_array("write", $allowableOperations))
					return true;

				$chop = ($operation === CBPCanUserOperateOperation::ViewWorkflow) ? "element_read" : "section_element_bind";

				$tasks  = self::getRightsTasks();
				foreach ($allowableOperations as $op)
				{
					if (isset($tasks[$op]))
						$op = $tasks[$op]['ID'];
					$ar = CTask::getOperations($op, true);
					if (in_array($chop, $ar))
						return true;
				}
			}

			return false;
		}

		if (!array_key_exists("IBlockPermission", $parameters))
		{
			if(CModule::includeModule('lists'))
				$parameters["IBlockPermission"] = CLists::getIBlockPermission($parameters["IBlockId"], $userId);
			else
				$parameters["IBlockPermission"] = CIBlock::getPermission($parameters["IBlockId"], $userId);
		}

		if ($parameters["IBlockPermission"] <= "R")
			return false;
		elseif ($parameters["IBlockPermission"] >= "W")
			return true;

		$userId = intval($userId);
		if (!array_key_exists("AllUserGroups", $parameters))
		{
			if (!array_key_exists("UserGroups", $parameters))
				$parameters["UserGroups"] = CUser::getUserGroup($userId);

			$parameters["AllUserGroups"] = $parameters["UserGroups"];
			$parameters["AllUserGroups"][] = "Author";
		}

		if (!array_key_exists("DocumentStates", $parameters))
		{
			$parameters["DocumentStates"] = CBPDocument::getDocumentStates(
				array("lists", get_called_class(), "iblock_".$parameters["IBlockId"]),
				null
			);
		}

		if (array_key_exists("WorkflowId", $parameters))
		{
			if (array_key_exists($parameters["WorkflowId"], $parameters["DocumentStates"]))
				$parameters["DocumentStates"] = array($parameters["WorkflowId"] => $parameters["DocumentStates"][$parameters["WorkflowId"]]);
			else
				return false;
		}

		$allowableOperations = CBPDocument::getAllowableOperations(
			$userId,
			$parameters["AllUserGroups"],
			$parameters["DocumentStates"]
		);

		if (!is_array($allowableOperations))
			return false;

		$r = false;
		switch ($operation)
		{
			case CBPCanUserOperateOperation::ViewWorkflow:
				$r = in_array("read", $allowableOperations);
				break;
			case CBPCanUserOperateOperation::StartWorkflow:
				$r = in_array("write", $allowableOperations);
				break;
			case CBPCanUserOperateOperation::CreateWorkflow:
				$r = in_array("write", $allowableOperations);
				break;
			case CBPCanUserOperateOperation::WriteDocument:
				$r = in_array("write", $allowableOperations);
				break;
			case CBPCanUserOperateOperation::ReadDocument:
				$r = false;
				break;
			default:
				$r = false;
		}

		return $r;
	}

	/**
	 * @param $documentType
	 * @param bool $withExtended
	 * @return array|bool
	 */

	static public function GetAllowableUserGroups($documentType, $withExtended = false)
	{
		$documentType = trim($documentType);
		if (strlen($documentType) <= 0)
			return false;

		$iblockId = intval(substr($documentType, strlen("iblock_")));

		$result = array("Author" => GetMessage("IBD_DOCUMENT_AUTHOR"));

		$groupsId = array(1);
		$extendedGroupsCode = array();
		if(CIBlock::getArrayByID($iblockId, "RIGHTS_MODE") === "E")
		{
			$rights = new CIBlockRights($iblockId);
			foreach($rights->getGroups(/*"element_bizproc_start"*/) as $iblockGroupCode)
				if(preg_match("/^G(\\d+)\$/", $iblockGroupCode, $match))
					$groupsId[] = $match[1];
				else
					$extendedGroupsCode[] = $iblockGroupCode;
		}
		else
		{
			foreach(CIBlock::getGroupPermissions($iblockId) as $groupId => $perm)
			{
				if ($perm > "R")
					$groupsId[] = $groupId;
			}
		}

		$groupsIterator = CGroup::getListEx(array("NAME" => "ASC"), array("ID" => $groupsId));
		while ($group = $groupsIterator->fetch())
			$result[$group["ID"]] = $group["NAME"];

		if ($withExtended && $extendedGroupsCode)
		{
			foreach ($extendedGroupsCode as $groupCode)
			{
				$result['group_'.$groupCode] = CBPHelper::getExtendedGroupName($groupCode);
			}
		}

		return $result;
	}

	static public function SetPermissions($documentId, $workflowId, $permissions, $rewrite = true)
	{
		$permissions = self::toInternalOperations(null, $permissions);
		parent::setPermissions($documentId, $workflowId, $permissions, $rewrite);
	}

	static public function GetFieldInputControl($documentType, $fieldType, $fieldName, $fieldValue, $allowSelection = false, $publicMode = false)
	{
		$iblockId = intval(substr($documentType, strlen("iblock_")));
		if ($iblockId <= 0)
			throw new CBPArgumentOutOfRangeException("documentType", $documentType);

		static $documentFieldTypes = array();
		if (!array_key_exists($documentType, $documentFieldTypes))
			$documentFieldTypes[$documentType] = self::getDocumentFieldTypes($documentType);

		$fieldType["BaseType"] = "string";
		$fieldType["Complex"] = false;
		if (array_key_exists($fieldType["Type"], $documentFieldTypes[$documentType]))
		{
			$fieldType["BaseType"] = $documentFieldTypes[$documentType][$fieldType["Type"]]["BaseType"];
			$fieldType["Complex"] = $documentFieldTypes[$documentType][$fieldType["Type"]]["Complex"];
		}

		if (!is_array($fieldValue) || is_array($fieldValue) && CBPHelper::isAssociativeArray($fieldValue))
			$fieldValue = array($fieldValue);

		$customMethodName = "";
		$customMethodNameMulty = "";
		if (strpos($fieldType["Type"], ":") !== false)
		{
			$ar = CIBlockProperty::getUserType(substr($fieldType["Type"], 2));
			if (array_key_exists("GetPublicEditHTML", $ar))
				$customMethodName = $ar["GetPublicEditHTML"];
			if (array_key_exists("GetPublicEditHTMLMulty", $ar))
				$customMethodNameMulty = $ar["GetPublicEditHTMLMulty"];
		}

		ob_start();

		if ($fieldType["Type"] == "select")
		{
			$fieldValueTmp = $fieldValue;
			?>
			<select id="id_<?= htmlspecialcharsbx($fieldName["Field"]) ?>" name="<?= htmlspecialcharsbx($fieldName["Field"]).($fieldType["Multiple"] ? "[]" : "") ?>"<?= ($fieldType["Multiple"] ? ' size="5" multiple' : '') ?>>
				<?
				if (!$fieldType["Required"])
					echo '<option value="">['.GetMessage("BPCGHLP_NOT_SET").']</option>';
				foreach ($fieldType["Options"] as $k => $v)
				{
					if (is_array($v) && count($v) == 2)
					{
						$v1 = array_values($v);
						$k = $v1[0];
						$v = $v1[1];
					}

					$ind = array_search($k, $fieldValueTmp);
					echo '<option value="'.htmlspecialcharsbx($k).'"'.($ind !== false ? ' selected' : '').'>'.htmlspecialcharsbx($v).'</option>';
					if ($ind !== false)
						unset($fieldValueTmp[$ind]);
				}
				?>
			</select>
			<?
			if ($allowSelection)
			{
				?>
				<br /><input type="text" id="id_<?= htmlspecialcharsbx($fieldName["Field"]) ?>_text" name="<?= htmlspecialcharsbx($fieldName["Field"]) ?>_text" value="<?
			if (count($fieldValueTmp) > 0)
			{
				$a = array_values($fieldValueTmp);
				echo htmlspecialcharsbx($a[0]);
			}
			?>">
				<input type="button" value="..." onclick="BPAShowSelector('id_<?= htmlspecialcharsbx($fieldName["Field"]) ?>_text', 'select');">
			<?
			}
		}
		elseif ($fieldType["Type"] == "user")
		{
			$fieldValue = CBPHelper::usersArrayToString($fieldValue, null, array("lists", get_called_class(), $documentType));
			?><input type="text" size="40" id="id_<?= htmlspecialcharsbx($fieldName["Field"]) ?>" name="<?= htmlspecialcharsbx($fieldName["Field"]) ?>" value="<?= htmlspecialcharsbx($fieldValue) ?>"><input type="button" value="..." onclick="BPAShowSelector('id_<?= htmlspecialcharsbx($fieldName["Field"]) ?>', 'user');"><?
		}
		elseif ((strpos($fieldType["Type"], ":") !== false)
			&& $fieldType["Multiple"]
			&& (
				is_array($customMethodNameMulty) && count($customMethodNameMulty) > 0
				|| !is_array($customMethodNameMulty) && strlen($customMethodNameMulty) > 0
			)
		)
		{
			if (!is_array($fieldValue))
				$fieldValue = array();

			if ($allowSelection)
			{
				$fieldValueTmp1 = array();
				$fieldValueTmp2 = array();
				foreach ($fieldValue as $v)
				{
					$vTrim = trim($v);
					if (\CBPDocument::IsExpression($vTrim))
						$fieldValueTmp1[] = $vTrim;
					else
						$fieldValueTmp2[] = $v;
				}
			}
			else
			{
				$fieldValueTmp1 = array();
				$fieldValueTmp2 = $fieldValue;
			}

			if (($fieldType["Type"] == "S:employee") && COption::getOptionString("bizproc", "employee_compatible_mode", "N") != "Y")
				$fieldValueTmp2 = CBPHelper::stripUserPrefix($fieldValueTmp2);

			foreach ($fieldValueTmp2 as &$fld)
				if (!isset($fld['VALUE']))
					$fld = array("VALUE" => $fld);

			if ($fieldType["Type"] == "E:EList")
			{
				static $fl = true;
				if ($fl)
				{
					if (!empty($_SERVER['HTTP_BX_AJAX']))
						$GLOBALS["APPLICATION"]->showAjaxHead();
					$GLOBALS["APPLICATION"]->addHeadScript('/bitrix/js/iblock/iblock_edit.js');
				}
				$fl = false;
			}
			echo call_user_func_array(
				$customMethodNameMulty,
				array(
					array("LINK_IBLOCK_ID" => $fieldType["Options"]),
					$fieldValueTmp2,
					array(
						"FORM_NAME" => $fieldName["Form"],
						"VALUE" => htmlspecialcharsbx($fieldName["Field"])
					),
					true
				)
			);

			if ($allowSelection)
			{
				?>
				<br /><input type="text" id="id_<?= htmlspecialcharsbx($fieldName["Field"]) ?>_text" name="<?= htmlspecialcharsbx($fieldName["Field"]) ?>_text" value="<?
			if (count($fieldValueTmp1) > 0)
			{
				$a = array_values($fieldValueTmp1);
				echo htmlspecialcharsbx($a[0]);
			}
			?>">
				<input type="button" value="..." onclick="BPAShowSelector('id_<?= htmlspecialcharsbx($fieldName["Field"]) ?>_text', 'user', '<?= $fieldType["Type"] == 'S:employee'? 'employee' : '' ?>');">
			<?
			}
		}
		else
		{
			if (!array_key_exists("CBPVirtualDocumentCloneRowPrinted", $GLOBALS) && $fieldType["Multiple"])
			{
				$GLOBALS["CBPVirtualDocumentCloneRowPrinted"] = 1;
				?>
				<script language="JavaScript">
					function CBPVirtualDocumentCloneRow(tableID)
					{
						var tbl = document.getElementById(tableID);
						var cnt = tbl.rows.length;
						var oRow = tbl.insertRow(cnt);
						var oCell = oRow.insertCell(0);
						var sHTML = tbl.rows[cnt - 1].cells[0].innerHTML;
						var p = 0;
						while (true)
						{
							var s = sHTML.indexOf('[n', p);
							if (s < 0)
								break;
							var e = sHTML.indexOf(']', s);
							if (e < 0)
								break;
							var n = parseInt(sHTML.substr(s + 2, e - s));
							sHTML = sHTML.substr(0, s) + '[n' + (++n) + ']' + sHTML.substr(e + 1);
							p = s + 1;
						}
						var p = 0;
						while (true)
						{
							var s = sHTML.indexOf('__n', p);
							if (s < 0)
								break;
							var e = sHTML.indexOf('_', s + 2);
							if (e < 0)
								break;
							var n = parseInt(sHTML.substr(s + 3, e - s));
							sHTML = sHTML.substr(0, s) + '__n' + (++n) + '_' + sHTML.substr(e + 1);
							p = e + 1;
						}
						oCell.innerHTML = sHTML;
						var patt = new RegExp('<' + 'script' + '>[^\000]*?<' + '\/' + 'script' + '>', 'ig');
						var code = sHTML.match(patt);
						if (code)
						{
							for (var i = 0; i < code.length; i++)
							{
								if (code[i] != '')
								{
									var s = code[i].substring(8, code[i].length - 9);
									jsUtils.EvalGlobal(s);
								}
							}
						}
					}
					function createAdditionalHtmlEditor(tableId)
					{
						var tbl = document.getElementById(tableId);
						var cnt = tbl.rows.length-1;
						var name = tableId.replace(/(?:CBPVirtualDocument_)(.*)(?:_Table)/, '$1')
						var idEditor = 'id_'+name+'__n'+cnt+'_';
						var inputNameEditor = name+'[n'+cnt+']';
						window.BXHtmlEditor.Show(
							{
								'id':idEditor,
								'inputName':inputNameEditor,
								'content':'',
								'useFileDialogs':false,
								'width':'100%',
								'height':'200',
								'allowPhp':false,
								'limitPhpAccess':false,
								'templates':[],
								'templateId':'',
								'templateParams':[],
								'componentFilter':'',
								'snippets':[],
								'placeholder':'Text here...',
								'actionUrl':'/bitrix/tools/html_editor_action.php',
								'cssIframePath':'/bitrix/js/fileman/html_editor/iframe-style.css?1412693817',
								'bodyClass':'',
								'bodyId':'',
								'spellcheck_path':'/bitrix/js/fileman/html_editor/html-spell.js?v=1412693817',
								'usePspell':'N',
								'useCustomSpell':'Y',
								'bbCode':false,
								'askBeforeUnloadPage':true,
								'settingsKey':'user_settings_1',
								'showComponents':true,
								'showSnippets':true,
								'view':'wysiwyg',
								'splitVertical':false,
								'splitRatio':'1',
								'taskbarShown':false,
								'taskbarWidth':'250',
								'lastSpecialchars':false,
								'cleanEmptySpans':true,
								'lazyLoad':false,
								'showTaskbars':false,
								'showNodeNavi':false,
								'controlsMap':[
									{'id':'Bold','compact':true,'sort':'80'},
									{'id':'Italic','compact':true,'sort':'90'},
									{'id':'Underline','compact':true,'sort':'100'},
									{'id':'Strikeout','compact':true,'sort':'110'},
									{'id':'RemoveFormat','compact':true,'sort':'120'},
									{'id':'Color','compact':true,'sort':'130'},
									{'id':'FontSelector','compact':false,'sort':'135'},
									{'id':'FontSize','compact':false,'sort':'140'},
									{'separator':true,'compact':false,'sort':'145'},
									{'id':'OrderedList','compact':true,'sort':'150'},
									{'id':'UnorderedList','compact':true,'sort':'160'},
									{'id':'AlignList','compact':false,'sort':'190'},
									{'separator':true,'compact':false,'sort':'200'},
									{'id':'InsertLink','compact':true,'sort':'210','wrap':'bx-b-link-'+idEditor},
									{'id':'InsertImage','compact':false,'sort':'220'},
									{'id':'InsertVideo','compact':true,'sort':'230','wrap':'bx-b-video-'+idEditor},
									{'id':'InsertTable','compact':false,'sort':'250'},
									{'id':'Code','compact':true,'sort':'260'},
									{'id':'Quote','compact':true,'sort':'270','wrap':'bx-b-quote-'+idEditor},
									{'id':'Smile','compact':false,'sort':'280'},
									{'separator':true,'compact':false,'sort':'290'},
									{'id':'Fullscreen','compact':false,'sort':'310'},
									{'id':'BbCode','compact':true,'sort':'340'},
									{'id':'More','compact':true,'sort':'400'}],
								'autoResize':true,
								'autoResizeOffset':'40',
								'minBodyWidth':'350',
								'normalBodyWidth':'555'
							});
						var htmlEditor = BX.findChildrenByClassName(BX(tableId), 'bx-html-editor');
						for(var k in htmlEditor)
						{
							var editorId = htmlEditor[k].getAttribute('id');
							var frameArray = BX.findChildrenByClassName(BX(editorId), 'bx-editor-iframe');
							if(frameArray.length > 1)
							{
								for(var i = 0; i < frameArray.length - 1; i++)
								{
									frameArray[i].parentNode.removeChild(frameArray[i]);
								}
							}

						}
					}
				</script>
			<?
			}

			if ($fieldType["Multiple"])
				echo '<table width="100%" border="0" cellpadding="2" cellspacing="2" id="CBPVirtualDocument_'.htmlspecialcharsbx($fieldName["Field"]).'_Table">';

			$fieldValueTmp = $fieldValue;

			if (sizeof($fieldValue) == 0)
				$fieldValue[] = null;

			$ind = -1;
			foreach ($fieldValue as $key => $value)
			{
				$ind++;
				$fieldNameId = 'id_'.htmlspecialcharsbx($fieldName["Field"]).'__n'.$ind.'_';
				$fieldNameName = htmlspecialcharsbx($fieldName["Field"]).($fieldType["Multiple"] ? "[n".$ind."]" : "");

				if ($fieldType["Multiple"])
					echo '<tr><td>';

				if (is_array($customMethodName) && count($customMethodName) > 0 || !is_array($customMethodName) && strlen($customMethodName) > 0)
				{
					if($fieldType["Type"] == "S:HTML")
					{
						if (Loader::includeModule("fileman"))
						{
							$editor = new CHTMLEditor;
							$res = array_merge(
								array(
									'useFileDialogs' => false,
									'height' => 200,
									'useFileDialogs' => false,
									'minBodyWidth' => 350,
									'normalBodyWidth' => 555,
									'bAllowPhp' => false,
									'limitPhpAccess' => false,
									'showTaskbars' => false,
									'showNodeNavi' => false,
									'askBeforeUnloadPage' => true,
									'bbCode' => false,
									'siteId' => SITE_ID,
									'autoResize' => true,
									'autoResizeOffset' => 40,
									'saveOnBlur' => true,
									'controlsMap' => array(
										array('id' => 'Bold',  'compact' => true, 'sort' => 80),
										array('id' => 'Italic',  'compact' => true, 'sort' => 90),
										array('id' => 'Underline',  'compact' => true, 'sort' => 100),
										array('id' => 'Strikeout',  'compact' => true, 'sort' => 110),
										array('id' => 'RemoveFormat',  'compact' => true, 'sort' => 120),
										array('id' => 'Color',  'compact' => true, 'sort' => 130),
										array('id' => 'FontSelector',  'compact' => false, 'sort' => 135),
										array('id' => 'FontSize',  'compact' => false, 'sort' => 140),
										array('separator' => true, 'compact' => false, 'sort' => 145),
										array('id' => 'OrderedList',  'compact' => true, 'sort' => 150),
										array('id' => 'UnorderedList',  'compact' => true, 'sort' => 160),
										array('id' => 'AlignList', 'compact' => false, 'sort' => 190),
										array('separator' => true, 'compact' => false, 'sort' => 200),
										array('id' => 'InsertLink',  'compact' => true, 'sort' => 210, 'wrap' => 'bx-b-link-'.$fieldNameId),
										array('id' => 'InsertImage',  'compact' => false, 'sort' => 220),
										array('id' => 'InsertVideo',  'compact' => true, 'sort' => 230, 'wrap' => 'bx-b-video-'.$fieldNameId),
										array('id' => 'InsertTable',  'compact' => false, 'sort' => 250),
										array('id' => 'Code',  'compact' => true, 'sort' => 260),
										array('id' => 'Quote',  'compact' => true, 'sort' => 270, 'wrap' => 'bx-b-quote-'.$fieldNameId),
										array('id' => 'Smile',  'compact' => false, 'sort' => 280),
										array('separator' => true, 'compact' => false, 'sort' => 290),
										array('id' => 'Fullscreen',  'compact' => false, 'sort' => 310),
										array('id' => 'BbCode',  'compact' => true, 'sort' => 340),
										array('id' => 'More',  'compact' => true, 'sort' => 400)
									)
								),
								array(
									'name' => $fieldNameName,
									'inputName' => $fieldNameName,
									'id' => $fieldNameId,
									'width' => '100%',
									'content' => htmlspecialcharsBack($value),
								)
							);
							$editor->show($res);
						}
						else
						{
							?><textarea rows="5" cols="40" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>"><?= htmlspecialcharsbx($value) ?></textarea><?
						}
					}
					else
					{
						$value1 = $value;
						if ($allowSelection && \CBPDocument::IsExpression(trim($value1)))
							$value1 = null;
						else
							unset($fieldValueTmp[$key]);

						if (($fieldType["Type"] == "S:employee") && COption::getOptionString("bizproc", "employee_compatible_mode", "N") != "Y")
							$value1 = CBPHelper::stripUserPrefix($value1);

						echo call_user_func_array(
							$customMethodName,
							array(
								array("LINK_IBLOCK_ID" => $fieldType["Options"]),
								array("VALUE" => $value1),
								array(
									"FORM_NAME" => $fieldName["Form"],
									"VALUE" => $fieldNameName
								),
								true
							)
						);
					}
				}
				else
				{
					switch ($fieldType["Type"])
					{
						case "int":
						case "double":
							unset($fieldValueTmp[$key]);
							?><input type="text" size="10" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>" value="<?= htmlspecialcharsbx($value) ?>"><?
							break;
						case "file":
							if ($publicMode)
							{
								//unset($fieldValueTmp[$key]);
								?><input type="file" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>"><?
							}
							break;
						case "bool":
							if (in_array($value, array("Y", "N")))
								unset($fieldValueTmp[$key]);
							?>
							<select id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>">
								<?
								if (!$fieldType["Required"])
									echo '<option value="">['.GetMessage("BPCGHLP_NOT_SET").']</option>';
								?>
								<option value="Y"<?= (in_array("Y", $fieldValue) ? ' selected' : '') ?>><?= GetMessage("BPCGHLP_YES") ?></option>
								<option value="N"<?= (in_array("N", $fieldValue) ? ' selected' : '') ?>><?= GetMessage("BPCGHLP_NO") ?></option>
							</select>
							<?
							break;
						case "text":
							unset($fieldValueTmp[$key]);
							?><textarea rows="5" cols="40" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>"><?= htmlspecialcharsbx($value) ?></textarea><?
							break;
						case "date":
						case "datetime":
							if (defined("ADMIN_SECTION") && ADMIN_SECTION)
							{
								$v = "";
								if (!\CBPDocument::IsExpression(trim($value)))
								{
									$v = $value;
									unset($fieldValueTmp[$key]);
								}
								require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/init_admin.php");
								echo CAdminCalendar::calendarDate($fieldNameName, $v, 19, ($fieldType["Type"] != "date"));
							}
							else
							{
								$value1 = $value;
								if ($allowSelection && \CBPDocument::IsExpression(trim($value1)))
									$value1 = null;
								else
									unset($fieldValueTmp[$key]);

								if($fieldType["Type"] == "date")
									$type = "Date";
								else
									$type = "DateTime";
								$ar = CIBlockProperty::getUserType($type);
								echo call_user_func_array(
									$ar["GetPublicEditHTML"],
									array(
										array("LINK_IBLOCK_ID" => $fieldType["Options"]),
										array("VALUE" => $value1),
										array(
											"FORM_NAME" => $fieldName["Form"],
											"VALUE" => $fieldNameName
										),
										true
									)
								);
							}

							break;
						default:
							unset($fieldValueTmp[$key]);
							?><input type="text" size="40" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>" value="<?= htmlspecialcharsbx($value) ?>"><?
					}
				}

				if ($allowSelection)
				{
					if (!in_array($fieldType["Type"], array("file", "bool", "date", "datetime")) && (is_array($customMethodName) && count($customMethodName) <= 0 || !is_array($customMethodName) && strlen($customMethodName) <= 0))
					{
						?><input type="button" value="..." onclick="BPAShowSelector('<?= $fieldNameId ?>', '<?= htmlspecialcharsbx($fieldType["BaseType"]) ?>');"><?
					}
				}

				if ($fieldType["Multiple"])
					echo '</td></tr>';
			}

			if ($fieldType["Multiple"])
				echo "</table>";

			if ($fieldType["Multiple"] && $fieldType["Type"] != "S:HTML" && (($fieldType["Type"] != "file") || $publicMode))
			{
				echo '<input type="button" value="'.GetMessage("BPCGHLP_ADD").'" onclick="CBPVirtualDocumentCloneRow(\'CBPVirtualDocument_'.$fieldName["Field"].'_Table\')"/><br />';
			}
			elseif($fieldType["Multiple"] && $fieldType["Type"] == "S:HTML")
			{
				$functionOnclick = 'CBPVirtualDocumentCloneRow(\'CBPVirtualDocument_'.$fieldName["Field"].'_Table\');createAdditionalHtmlEditor(\'CBPVirtualDocument_'.$fieldName["Field"].'_Table\');';
				echo '<input type="button" value="'.GetMessage("BPCGHLP_ADD").'" onclick="'.$functionOnclick.'"/><br />';
			}

			if ($allowSelection)
			{
				if (in_array($fieldType["Type"], array("file", "bool", "date", "datetime")) || (is_array($customMethodName) && count($customMethodName) > 0 || !is_array($customMethodName) && strlen($customMethodName) > 0))
				{
					?>
					<input type="text" id="id_<?= htmlspecialcharsbx($fieldName["Field"]) ?>_text" name="<?= htmlspecialcharsbx($fieldName["Field"]) ?>_text" value="<?
					if (count($fieldValueTmp) > 0)
					{
						$a = array_values($fieldValueTmp);
						echo htmlspecialcharsbx($a[0]);
					}
					?>">
					<input type="button" value="..." onclick="BPAShowSelector('id_<?= htmlspecialcharsbx($fieldName["Field"]) ?>_text', '<?= htmlspecialcharsbx($fieldType["BaseType"]) ?>', '<?= $fieldType["Type"] == 'S:employee'? 'employee' : '' ?>');">
				<?
				}
			}
		}

		$s = ob_get_contents();
		ob_end_clean();

		return $s;
	}

	static public function GetFieldInputValue($documentType, $fieldType, $fieldName, $request, &$errors)
	{
		$iblockId = intval(substr($documentType, strlen("iblock_")));
		if ($iblockId <= 0)
			throw new CBPArgumentOutOfRangeException("documentType", $documentType);

		$result = array();

		if ($fieldType["Type"] == "user")
		{
			$value = $request[$fieldName["Field"]];
			if (strlen($value) > 0)
			{
				$result = CBPHelper::usersStringToArray($value, array("lists", get_called_class(), $documentType), $errors);
				if (count($errors) > 0)
				{
					foreach ($errors as $e)
						$errors[] = $e;
				}
			}
			else
				$result = null;
		}
		elseif (array_key_exists($fieldName["Field"], $request) || array_key_exists($fieldName["Field"]."_text", $request))
		{
			$valueArray = array();
			if (array_key_exists($fieldName["Field"], $request))
			{
				$valueArray = $request[$fieldName["Field"]];
				if (!is_array($valueArray) || is_array($valueArray) && CBPHelper::isAssociativeArray($valueArray))
					$valueArray = array($valueArray);
			}
			if (array_key_exists($fieldName["Field"]."_text", $request))
				$valueArray[] = $request[$fieldName["Field"]."_text"];

			foreach ($valueArray as $value)
			{
				if (is_array($value) || !is_array($value) && !\CBPDocument::IsExpression(trim($value)))
				{
					if ($fieldType["Type"] == "int")
					{
						if (strlen($value) > 0)
						{
							$value = str_replace(" ", "", $value);
							if ($value."|" == intval($value)."|")
							{
								$value = intval($value);
							}
							else
							{
								$value = null;
								$errors[] = array(
									"code" => "ErrorValue",
									"message" => GetMessage("LISTS_BIZPROC_INVALID_INT"),
									"parameter" => $fieldName["Field"],
								);
							}
						}
						else
						{
							$value = null;
						}
					}
					elseif ($fieldType["Type"] == "double")
					{
						if (strlen($value) > 0)
						{
							$value = str_replace(" ", "", str_replace(",", ".", $value));
							if (is_numeric($value))
							{
								$value = doubleval($value);
							}
							else
							{
								$value = null;
								$errors[] = array(
									"code" => "ErrorValue",
									"message" => GetMessage("LISTS_BIZPROC_INVALID_INT"),
									"parameter" => $fieldName["Field"],
								);
							}
						}
						else
						{
							$value = null;
						}
					}
					elseif ($fieldType["Type"] == "select")
					{
						if (!is_array($fieldType["Options"]) || count($fieldType["Options"]) <= 0 || strlen($value) <= 0)
						{
							$value = null;
						}
						else
						{
							$ar = array_values($fieldType["Options"]);
							if (is_array($ar[0]))
							{
								$b = false;
								foreach ($ar as $a)
								{
									if ($a[0] == $value)
									{
										$b = true;
										break;
									}
								}
								if (!$b)
								{
									$value = null;
									$errors[] = array(
										"code" => "ErrorValue",
										"message" => GetMessage("LISTS_BIZPROC_INVALID_SELECT"),
										"parameter" => $fieldName["Field"],
									);
								}
							}
							else
							{
								if (!array_key_exists($value, $fieldType["Options"]))
								{
									$value = null;
									$errors[] = array(
										"code" => "ErrorValue",
										"message" => GetMessage("LISTS_BIZPROC_INVALID_SELECT"),
										"parameter" => $fieldName["Field"],
									);
								}
							}
						}
					}
					elseif ($fieldType["Type"] == "bool")
					{
						if ($value !== "Y" && $value !== "N")
						{
							if ($value === true)
							{
								$value = "Y";
							}
							elseif ($value === false)
							{
								$value = "N";
							}
							elseif (strlen($value) > 0)
							{
								$value = strtolower($value);
								if (in_array($value, array("y", "yes", "true", "1")))
								{
									$value = "Y";
								}
								elseif (in_array($value, array("n", "no", "false", "0")))
								{
									$value = "N";
								}
								else
								{
									$value = null;
									$errors[] = array(
										"code" => "ErrorValue",
										"message" => GetMessage("BPCGWTL_INVALID45"),
										"parameter" => $fieldName["Field"],
									);
								}
							}
							else
							{
								$value = null;
							}
						}
					}
					elseif ($fieldType["Type"] == "file")
					{
						if (is_array($value) && array_key_exists("name", $value) && strlen($value["name"]) > 0)
						{
							if (!array_key_exists("MODULE_ID", $value) || strlen($value["MODULE_ID"]) <= 0)
								$value["MODULE_ID"] = "bizproc";

							$value = CFile::saveFile($value, "bizproc_wf", true, true);
							if (!$value)
							{
								$value = null;
								$errors[] = array(
									"code" => "ErrorValue",
									"message" => GetMessage("BPCGWTL_INVALID915"),
									"parameter" => $fieldName["Field"],
								);
							}
						}
						else
						{
							$value = null;
						}
					}
					elseif ($fieldType["Type"] == "date")
					{
						if (strlen($value) > 0)
						{
							if(!CheckDateTime($value, FORMAT_DATE))
							{
								$value = null;
								$errors[] = array(
									"code" => "ErrorValue",
									"message" => GetMessage("LISTS_BIZPROC_INVALID_DATE"),
									"parameter" => $fieldName["Field"],
								);
							}
						}
						else
						{
							$value = null;
						}

					}
					elseif ($fieldType["Type"] == "datetime")
					{
						if (strlen($value) > 0)
						{
							$valueTemporary = array();
							$valueTemporary["VALUE"] = $value;
							$result = CIBlockPropertyDateTime::checkFields('', $valueTemporary);
							if (!empty($result))
							{
								$message = '';
								foreach ($result as $error)
									$message .= $error;

								$value = null;
								$errors[] = array(
									"code" => "ErrorValue",
									"message" => $message,
									"parameter" => $fieldName["Field"],
								);
							}
						}
						else
						{
							$value = null;
						}
					}
					elseif (strpos($fieldType["Type"], ":") !== false && $fieldType["Type"] != "S:HTML")
					{
						$customType = CIBlockProperty::getUserType(substr($fieldType["Type"], 2));
						if (array_key_exists("GetLength", $customType))
						{
							if (call_user_func_array(
									$customType["GetLength"],
									array(
										array("LINK_IBLOCK_ID" => $fieldType["Options"]),
										array("VALUE" => $value)
									)
								) <= 0)
							{
								$value = null;
							}
						}

						if (($value != null) && array_key_exists("CheckFields", $customType))
						{
							$errorsTemporary = call_user_func_array(
								$customType["CheckFields"],
								array(
									array("LINK_IBLOCK_ID" => $fieldType["Options"]),
									array("VALUE" => $value)
								)
							);
							if (count($errorsTemporary) > 0)
							{
								$value = null;
								foreach ($errorsTemporary as $e)
									$errors[] = array(
										"code" => "ErrorValue",
										"message" => $e,
										"parameter" => $fieldName["Field"],
									);
							}
						}
						elseif (!array_key_exists("GetLength", $customType) && $value === '')
							$value = null;

						if (
							$value !== null &&
							$fieldType["Type"] == "S:employee" &&
							COption::getOptionString("bizproc", "employee_compatible_mode", "N") != "Y"
						)
						{
							$value = "user_".$value;
						}
					}
					else
					{
						if (!is_array($value) && strlen($value) <= 0)
							$value = null;
					}
				}

				if ($value !== null)
					$result[] = $value;
			}
		}

		if (!$fieldType["Multiple"])
		{
			if (is_array($result) && count($result) > 0)
				$result = $result[0];
			else
				$result = null;
		}

		return $result;
	}

	static public function GetFieldInputValuePrintable($documentType, $fieldType, $fieldValue)
	{
		$result = $fieldValue;

		switch ($fieldType['Type'])
		{
			case "user":
				if (!is_array($fieldValue))
					$fieldValue = array($fieldValue);

				$result = CBPHelper::usersArrayToString($fieldValue, null, array("lists", get_called_class(), $documentType));
				break;

			case "bool":
				if (is_array($fieldValue))
				{
					$result = array();
					foreach ($fieldValue as $r)
						$result[] = ((strtoupper($r) != "N" && !empty($r)) ? GetMessage("BPVDX_YES") : GetMessage("BPVDX_NO"));
				}
				else
				{
					$result = ((strtoupper($fieldValue) != "N" && !empty($fieldValue)) ? GetMessage("BPVDX_YES") : GetMessage("BPVDX_NO"));
				}
				break;

			case "file":
				if (is_array($fieldValue))
				{
					$result = array();
					foreach ($fieldValue as $r)
					{
						$r = intval($r);
						$imgQuery = CFile::getByID($r);
						if ($img = $imgQuery->fetch())
							$result[] = "[url=/bitrix/tools/bizproc_show_file.php?f=".htmlspecialcharsbx($img["FILE_NAME"])."&i=".$r."&h=".md5($img["SUBDIR"])."]".htmlspecialcharsbx($img["ORIGINAL_NAME"])."[/url]";
					}
				}
				else
				{
					$fieldValue = intval($fieldValue);
					$imgQuery = CFile::getByID($fieldValue);
					if ($img = $imgQuery->fetch())
						$result = "[url=/bitrix/tools/bizproc_show_file.php?f=".htmlspecialcharsbx($img["FILE_NAME"])."&i=".$fieldValue."&h=".md5($img["SUBDIR"])."]".htmlspecialcharsbx($img["ORIGINAL_NAME"])."[/url]";
				}
				break;

			case "select":
				if (is_array($fieldType["Options"]))
				{
					if (is_array($fieldValue))
					{
						$result = array();
						foreach ($fieldValue as $r)
						{
							if (array_key_exists($r, $fieldType["Options"]))
								$result[] = $fieldType["Options"][$r];
						}
					}
					else
					{
						if (array_key_exists($fieldValue, $fieldType["Options"]))
							$result = $fieldType["Options"][$fieldValue];
					}
				}
				break;
		}

		if (strpos($fieldType['Type'], ":") !== false)
		{
			if ($fieldType["Type"] == "S:employee")
				$fieldValue = CBPHelper::stripUserPrefix($fieldValue);

			$customType = CIBlockProperty::getUserType(substr($fieldType['Type'], 2));
			if (array_key_exists("GetPublicViewHTML", $customType))
			{
				if (is_array($fieldValue) && !CBPHelper::isAssociativeArray($fieldValue))
				{
					$result = array();
					foreach ($fieldValue as $value)
					{
						$r = call_user_func_array(
							$customType["GetPublicViewHTML"],
							array(
								array("LINK_IBLOCK_ID" => $fieldType["Options"]),
								array("VALUE" => $value),
								""
							)
						);

						$result[] = HTMLToTxt($r);
					}
				}
				else
				{
					$result = call_user_func_array(
						$customType["GetPublicViewHTML"],
						array(
							array("LINK_IBLOCK_ID" => $fieldType["Options"]),
							array("VALUE" => $fieldValue),
							""
						)
					);

					$result = HTMLToTxt($result);
				}
			}
		}

		return $result;
	}

	static public function UnlockDocument($documentId, $workflowId)
	{
		global $DB;

		$strSql = "
			SELECT * FROM b_iblock_element_lock
			WHERE IBLOCK_ELEMENT_ID = ".intval($documentId)."
		";
		$query = $DB->query($strSql, false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
		if($query->fetch())
		{
			$strSql = "
				DELETE FROM b_iblock_element_lock
				WHERE IBLOCK_ELEMENT_ID = ".intval($documentId)."
				AND (LOCKED_BY = '".$DB->forSQL($workflowId, 32)."' OR '".$DB->forSQL($workflowId, 32)."' = '')
			";
			$query = $DB->query($strSql, false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
			$result = $query->affectedRowsCount();
		}
		else
		{//Success unlock when there is no locks at all
			$result = 1;
		}

		if ($result > 0)
		{
			foreach (GetModuleEvents("iblock", "CIBlockDocument_OnUnlockDocument", true) as $event)
			{
				ExecuteModuleEventEx($event, array(array("lists", get_called_class(), $documentId)));
			}
		}

		return $result > 0;
	}

	/**
	 * Метод публикует документ. То есть делает его доступным в публичной части сайта.
	 *
	 * @param string $documentId - код документа.
	 */
public static 	public function PublishDocument($documentId)
	{
		global $DB;
		$ID = intval($documentId);

		$db_element = CIBlockElement::getList(array(), array("ID"=>$ID, "SHOW_HISTORY"=>"Y"), false, false,
			array(
				"ID",
				"TIMESTAMP_X",
				"MODIFIED_BY",
				"DATE_CREATE",
				"CREATED_BY",
				"IBLOCK_ID",
				"ACTIVE",
				"ACTIVE_FROM",
				"ACTIVE_TO",
				"SORT",
				"NAME",
				"PREVIEW_PICTURE",
				"PREVIEW_TEXT",
				"PREVIEW_TEXT_TYPE",
				"DETAIL_PICTURE",
				"DETAIL_TEXT",
				"DETAIL_TEXT_TYPE",
				"WF_STATUS_ID",
				"WF_PARENT_ELEMENT_ID",
				"WF_NEW",
				"WF_COMMENTS",
				"IN_SECTIONS",
				"CODE",
				"TAGS",
				"XML_ID",
				"TMP_ID",
			)
		);
		if($element = $db_element->fetch())
		{
			$parentId = intval($element["WF_PARENT_ELEMENT_ID"]);
			if($parentId)
			{
				$elementObject = new CIBlockElement;
				$element["WF_PARENT_ELEMENT_ID"] = false;

				if($element["PREVIEW_PICTURE"])
					$element["PREVIEW_PICTURE"] = CFile::makeFileArray($element["PREVIEW_PICTURE"]);
				else
					$element["PREVIEW_PICTURE"] = array("tmp_name" => "", "del" => "Y");

				if($element["DETAIL_PICTURE"])
					$element["DETAIL_PICTURE"] = CFile::makeFileArray($element["DETAIL_PICTURE"]);
				else
					$element["DETAIL_PICTURE"] = array("tmp_name" => "", "del" => "Y");

				$element["IBLOCK_SECTION"] = array();
				if($element["IN_SECTIONS"] == "Y")
				{
					$sectionsQuery = CIBlockElement::getElementGroups($element["ID"], true, array('ID', 'IBLOCK_ELEMENT_ID'));
					while($section = $sectionsQuery->fetch())
						$element["IBLOCK_SECTION"][] = $section["ID"];
				}

				$element["PROPERTY_VALUES"] = array();
				$props = &$element["PROPERTY_VALUES"];

				//Delete old files
				$propsQuery = CIBlockElement::getProperty($element["IBLOCK_ID"], $parentId, array("value_id" => "asc"), array("PROPERTY_TYPE" => "F", "EMPTY" => "N"));
				while($prop = $propsQuery->fetch())
				{
					if(!array_key_exists($prop["ID"], $props))
						$props[$prop["ID"]] = array();
					$props[$prop["ID"]][$prop["PROPERTY_VALUE_ID"]] = array(
						"VALUE" => array("tmp_name" => "", "del" => "Y"),
						"DESCRIPTION" => false,
					);
				}

				//Add new proiperty values
				$propsQuery = CIBlockElement::getProperty($element["IBLOCK_ID"], $element["ID"], array("value_id" => "asc"));
				$i = 0;
				while($prop = $propsQuery->fetch())
				{
					$i++;
					if(!array_key_exists($prop["ID"], $props))
						$props[$prop["ID"]] = array();

					if($prop["PROPERTY_VALUE_ID"])
					{
						if($prop["PROPERTY_TYPE"] == "F")
							$props[$prop["ID"]]["n".$i] = array(
								"VALUE" => CFile::makeFileArray($prop["VALUE"]),
								"DESCRIPTION" => $prop["DESCRIPTION"],
							);
						else
							$props[$prop["ID"]]["n".$i] = array(
								"VALUE" => $prop["VALUE"],
								"DESCRIPTION" => $prop["DESCRIPTION"],
							);
					}
				}

				$elementObject->update($parentId, $element);
				CBPDocument::mergeDocuments(
					array("lists", get_called_class(), $parentId),
					array("lists", get_called_class(), $documentId)
				);
				CIBlockElement::delete($ID);
				CIBlockElement::wF_CleanUpHistoryCopies($parentId, 0);
				$strSql = "update b_iblock_element set WF_STATUS_ID='1', WF_NEW=NULL WHERE ID=".$parentId." AND WF_PARENT_ELEMENT_ID IS NULL";
				$DB->Query($strSql, false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
				CIBlockElement::updateSearch($parentId);
				return $parentId;
			}
			else
			{
				CIBlockElement::wF_CleanUpHistoryCopies($ID, 0);
				$strSql = "update b_iblock_element set WF_STATUS_ID='1', WF_NEW=NULL WHERE ID=".$ID." AND WF_PARENT_ELEMENT_ID IS NULL";
				$DB->Query($strSql, false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
				CIBlockElement::updateSearch($ID);
				return $ID;
			}
		}
		return false;
	}

	/**
	 * Method return array with all information about document. Array used for method RecoverDocumentFromHistory.
	 *
	 * @param string $documentId - document id.
	 * @return array - document information array.
	 */
public static 	public function GetDocumentForHistory($documentId, $historyIndex)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		$result = null;

		$dbDocumentList = CIBlockElement::getList(
			array(),
			array("ID" => $documentId, "SHOW_NEW"=>"Y", "SHOW_HISTORY" => "Y")
		);
		if ($objDocument = $dbDocumentList->getNextElement())
		{
			$fields = $objDocument->getFields();
			$properties = $objDocument->getProperties();

			$result["NAME"] = $fields["~NAME"];

			$result["FIELDS"] = array();
			foreach ($fields as $fieldKey => $fieldValue)
			{
				if ($fieldKey == "~PREVIEW_PICTURE" || $fieldKey == "~DETAIL_PICTURE")
				{
					$result["FIELDS"][substr($fieldKey, 1)] = CBPDocument::prepareFileForHistory(
						array("lists", get_called_class(), $documentId),
						$fieldValue,
						$historyIndex
					);
				}
				elseif (substr($fieldKey, 0, 1) == "~")
				{
					$result["FIELDS"][substr($fieldKey, 1)] = $fieldValue;
				}
			}

			$result["PROPERTIES"] = array();
			foreach ($properties as $propertyKey => $propertyValue)
			{
				if (strlen($propertyValue["USER_TYPE"]) > 0)
				{
					$result["PROPERTIES"][$propertyKey] = array(
						"VALUE" => $propertyValue["VALUE"],
						"DESCRIPTION" => $propertyValue["DESCRIPTION"]
					);
				}
				elseif ($propertyValue["PROPERTY_TYPE"] == "L")
				{
					$result["PROPERTIES"][$propertyKey] = array(
						"VALUE" => $propertyValue["VALUE_ENUM_ID"],
						"DESCRIPTION" => $propertyValue["DESCRIPTION"]
					);
				}
				elseif ($propertyValue["PROPERTY_TYPE"] == "F")
				{
					$result["PROPERTIES"][$propertyKey] = array(
						"VALUE" => CBPDocument::prepareFileForHistory(
							array("lists", get_called_class(), $documentId),
							$propertyValue["VALUE"],
							$historyIndex
						),
						"DESCRIPTION" => $propertyValue["DESCRIPTION"]
					);
				}
				else
				{
					$result["PROPERTIES"][$propertyKey] = array(
						"VALUE" => $propertyValue["VALUE"],
						"DESCRIPTION" => $propertyValue["DESCRIPTION"]
					);
				}
			}
		}

		return $result;
	}
}