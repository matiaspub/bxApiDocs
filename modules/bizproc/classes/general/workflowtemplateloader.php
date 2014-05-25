<?
IncludeModuleLangFile(__FILE__);

// define("BP_EI_DIRECTION_EXPORT", 0);
// define("BP_EI_DIRECTION_IMPORT", 1);

/**
* Workflow templates service.
*/
class CAllBPWorkflowTemplateLoader
{
	protected $useGZipCompression = false;

	static public function __clone()
	{
		trigger_error('Clone in not allowed.', E_USER_ERROR);
	}

	public static function GetList($arOrder = array("ID" => "DESC"), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		$loader = CBPWorkflowTemplateLoader::GetLoader();
		return $loader->GetTemplatesList($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields);
	}

	private function ValidateTemplate($arActivity, $user)
	{
		$arErrors = CBPActivity::CallStaticMethod(
			$arActivity["Type"],
			"ValidateProperties",
			array($arActivity["Properties"], $user)
		);

		$pref = false;
		if (isset($arActivity["Properties"]) && isset($arActivity["Properties"]["Title"]))
			$pref = str_replace("#TITLE#", $arActivity["Properties"]["Title"], GetMessage("BPWTL_ERROR_MESSAGE_PREFIX"))." ";

		if ($pref !== false)
		{
			foreach ($arErrors as &$e)
				$e["message"] = $pref.$e["message"];
		}

		if (array_key_exists("Children", $arActivity) && count($arActivity["Children"]) > 0)
		{
			$bFirst = true;
			foreach ($arActivity["Children"] as $arChildActivity)
			{
				$arErrorsTmp = CBPActivity::CallStaticMethod(
					$arActivity["Type"],
					"ValidateChild",
					array($arChildActivity["Type"], $bFirst)
				);
				if ($pref !== false)
				{
					foreach ($arErrorsTmp as &$e)
						$e["message"] = $pref.$e["message"];
				}
				$arErrors = $arErrors + $arErrorsTmp;

				$bFirst = false;
				$arErrors = $arErrors + $this->ValidateTemplate($arChildActivity, $user);
			}
		}

		return $arErrors;
	}

	protected function ParseFields(&$arFields, $id = 0)
	{
		global $DB;

		$id = intval($id);
		$updateMode = ($id > 0 ? true : false);
		$addMode = !$updateMode;

		if ($addMode && !is_set($arFields, "DOCUMENT_TYPE"))
			throw new CBPArgumentNullException("DOCUMENT_TYPE");

		if (is_set($arFields, "DOCUMENT_TYPE"))
		{
			$arDocumentType = CBPHelper::ParseDocumentId($arFields["DOCUMENT_TYPE"]);

			$arFields["MODULE_ID"] = $arDocumentType[0];
			$arFields["ENTITY"] = $arDocumentType[1];
			$arFields["DOCUMENT_TYPE"] = $arDocumentType[2];
		}
		else
		{
			unset($arFields["MODULE_ID"]);
			unset($arFields["ENTITY"]);
			unset($arFields["DOCUMENT_TYPE"]);
		}

		if (is_set($arFields, "NAME") || $addMode)
		{
			$arFields["NAME"] = trim($arFields["NAME"]);
			if (strlen($arFields["NAME"]) <= 0)
				throw new CBPArgumentNullException("NAME");
		}

		if ($addMode && !is_set($arFields, "TEMPLATE"))
			throw new CBPArgumentNullException("TEMPLATE");

		if (is_set($arFields, "TEMPLATE"))
		{
			if (!is_array($arFields["TEMPLATE"]))
			{
				throw new CBPArgumentTypeException("TEMPLATE", "array");
			}
			else
			{
				$userTmp = null;

				if (array_key_exists("MODIFIER_USER", $arFields))
				{
					if (is_object($arFields["MODIFIER_USER"]) && is_a($arFields["MODIFIER_USER"], "CBPWorkflowTemplateUser"))
						$userTmp = $arFields["MODIFIER_USER"];
					else
						$userTmp = new CBPWorkflowTemplateUser($arFields["MODIFIER_USER"]);
				}
				else
				{
					$userTmp = new CBPWorkflowTemplateUser();
				}

				$err = array();
				foreach ($arFields["TEMPLATE"] as $v)
					$err = $err + $this->ValidateTemplate($v, $userTmp);

				if (count($err) > 0)
				{
					$m = "";
					foreach ($err as $v)
					{
						$m = trim($v["message"]);
						if (substr($m, -1) != ".")
							$m .= ".";

					}
						$m .= $v["message"]." ";

					throw new Exception($m);
				}

				$arFields["TEMPLATE"] = $this->GetSerializedForm($arFields["TEMPLATE"]);
			}
		}

		if (is_set($arFields, "PARAMETERS"))
		{
			if ($arFields["PARAMETERS"] == null)
			{
				$arFields["PARAMETERS"] = false;
			}
			elseif (is_array($arFields["PARAMETERS"]))
			{
				if (count($arFields["PARAMETERS"]) > 0)
					$arFields["PARAMETERS"] = $this->GetSerializedForm($arFields["PARAMETERS"]);
				else
					$arFields["PARAMETERS"] = false;
			}
			else
			{
				throw new CBPArgumentTypeException("PARAMETERS");
			}
		}

		if (is_set($arFields, "VARIABLES"))
		{
			if ($arFields["VARIABLES"] == null)
			{
				$arFields["VARIABLES"] = false;
			}
			elseif (is_array($arFields["VARIABLES"]))
			{
				if (count($arFields["VARIABLES"]) > 0)
					$arFields["VARIABLES"] = $this->GetSerializedForm($arFields["VARIABLES"]);
				else
					$arFields["VARIABLES"] = false;
			}
			else
			{
				throw new CBPArgumentTypeException("VARIABLES");
			}
		}

		if(is_set($arFields, "ACTIVE") && $arFields["ACTIVE"] != 'N')
			$arFields["ACTIVE"] = 'Y';

		unset($arFields["MODIFIED"]);
	}

	public static function Add($arFields)
	{
		$loader = CBPWorkflowTemplateLoader::GetLoader();
		return $loader->AddTemplate($arFields);
	}

	public static function Update($id, $arFields)
	{
		$loader = CBPWorkflowTemplateLoader::GetLoader();
		return $loader->UpdateTemplate($id, $arFields);
	}

	private function GetSerializedForm($arTemplate)
	{
		$buffer = serialize($arTemplate);
		if ($this->useGZipCompression)
			$buffer = gzcompress($buffer, 9);
		return $buffer;
	}

	public static function Delete($id)
	{
		$loader = CBPWorkflowTemplateLoader::GetLoader();
		$loader->DeleteTemplate($id);
	}

	static public function DeleteTemplate($id)
	{
		global $DB;

		$id = intval($id);
		if ($id <= 0)
			throw new Exception("id");

		$dbResult = $DB->Query(
			"SELECT COUNT('x') as CNT ".
			"FROM b_bp_workflow_state WS ".
			"	INNER JOIN b_bp_workflow_instance WI ON (WS.ID = WI.ID) ".
			"WHERE WS.WORKFLOW_TEMPLATE_ID = ".intval($id)." "
		);

		if ($arResult = $dbResult->Fetch())
		{
			$cnt = intval($arResult["CNT"]);

			if ($cnt <= 0)
			{
				$DB->Query(
					"DELETE FROM b_bp_workflow_template ".
					"WHERE ID = ".intval($id)." "
				);
			}
			else
			{
				throw new CBPInvalidOperationException(GetMessage("BPCGWTL_CANT_DELETE"));
			}
		}
		else
		{
			throw new Exception(GetMessage("BPCGWTL_UNKNOWN_ERROR"));
		}
	}

	public function LoadWorkflow($workflowTemplateId)
	{
		$workflowTemplateId = intval($workflowTemplateId);
		if ($workflowTemplateId <= 0)
			throw new CBPArgumentOutOfRangeException("workflowTemplateId", $workflowTemplateId);

		$dbTemplatesList = $this->GetTemplatesList(array(), array("ID" => $workflowTemplateId), false, false, array("TEMPLATE", "VARIABLES", "PARAMETERS"));
		$arTemplatesListItem = $dbTemplatesList->Fetch();

		if (!$arTemplatesListItem)
			throw new Exception(str_replace("#ID#", $workflowTemplateId, GetMessage("BPCGWTL_INVALID_WF_ID")));

		$arWorkflowTemplate = $arTemplatesListItem["TEMPLATE"];
		$workflowVariablesTypes = $arTemplatesListItem["VARIABLES"];
		$workflowParametersTypes = $arTemplatesListItem["PARAMETERS"];

		if (!is_array($arWorkflowTemplate) || count($arWorkflowTemplate) <= 0)
			throw new Exception(str_replace("#ID#", $workflowTemplateId, GetMessage("BPCGWTL_EMPTY_TEMPLATE")));

		$arActivityNames = array();
		$rootActivity = $this->ParceWorkflowTemplate($arWorkflowTemplate, $arActivityNames, null);

		return array($rootActivity, $workflowVariablesTypes, $workflowParametersTypes);
	}

	private function ParceWorkflowTemplate($arWorkflowTemplate, &$arActivityNames, CBPActivity $parentActivity = null)
	{
		if (!is_array($arWorkflowTemplate))
			throw new CBPArgumentOutOfRangeException("arWorkflowTemplate");

		foreach ($arWorkflowTemplate as $activityFormatted)
		{
			if (in_array($activityFormatted["Name"], $arActivityNames))
				throw new Exception("DublicateAcrivityName");

			$arActivityNames[] = $activityFormatted["Name"];
			$activity = $this->CreateActivity($activityFormatted["Type"], $activityFormatted["Name"]);
			if ($activity == null)
				throw new Exception("Activity is not found.");

			$activity->InitializeFromArray($activityFormatted["Properties"]);
			if ($parentActivity)
				$parentActivity->FixUpParentChildRelationship($activity);

			if ($activityFormatted["Children"])
				$this->ParceWorkflowTemplate($activityFormatted["Children"], $arActivityNames, $activity);
		}

		return $activity;
	}

	private function CreateActivity($activityCode, $activityName)
	{
		if (CBPActivity::IncludeActivityFile($activityCode))
			return CBPActivity::CreateInstance($activityCode, $activityName);
		else
			throw new Exception('Activity is not found.');
	}

	public static function GetStatesOfTemplate($arWorkflowTemplate)
	{
		if (!is_array($arWorkflowTemplate))
			throw new CBPArgumentTypeException("arWorkflowTemplate", "array");

		if (!is_array($arWorkflowTemplate[0]))
			throw new CBPArgumentTypeException("arWorkflowTemplate");

		$arStates = array();
		foreach ($arWorkflowTemplate[0]["Children"] as $state)
			$arStates[$state["Name"]] = (strlen($state["Properties"]["Title"]) > 0 ? $state["Properties"]["Title"] : $state["Name"]);

		return $arStates;
	}

	private static function FindSetStateActivities($arWorkflowTemplate)
	{
		$arResult = array();

		if ($arWorkflowTemplate["Type"] == "SetStateActivity")
			$arResult[] = $arWorkflowTemplate["Properties"]["TargetStateName"];

		if (is_array($arWorkflowTemplate["Children"]))
		{
			foreach ($arWorkflowTemplate["Children"] as $key => $value)
				$arResult = $arResult + self::FindSetStateActivities($arWorkflowTemplate["Children"][$key]);
		}

		return $arResult;
	}

	public static function GetTransfersOfState($arWorkflowTemplate, $stateName)
	{
		if (!is_array($arWorkflowTemplate))
			throw new CBPArgumentTypeException("arWorkflowTemplate", "array");

		if (!is_array($arWorkflowTemplate[0]))
			throw new CBPArgumentTypeException("arWorkflowTemplate");

		$stateName = trim($stateName);
		if (strlen($stateName) <= 0)
			throw new CBPArgumentNullException("stateName");

		$arTransfers = array();
		foreach ($arWorkflowTemplate[0]["Children"] as $state)
		{
			if ($stateName == $state["Name"])
			{
				foreach ($state["Children"] as $event)
					$arTransfers[$event["Name"]] = self::FindSetStateActivities($event);

				break;
			}
		}

		return $arTransfers;
	}

	private static function ParseDocumentTypeStates($arTemplatesListItem)
	{
		$arWorkflowTemplate = $arTemplatesListItem["TEMPLATE"];
		if (!is_array($arWorkflowTemplate))
			throw new CBPArgumentTypeException("arTemplatesListItem");

		$result = array(
			"ID" => "",
			"TEMPLATE_ID" => $arTemplatesListItem["ID"],
			"TEMPLATE_NAME" => $arTemplatesListItem["NAME"],
			"TEMPLATE_DESCRIPTION" => $arTemplatesListItem["DESCRIPTION"],
			"STATE_NAME" => "",
			"STATE_TITLE" => "",
			"TEMPLATE_PARAMETERS" => $arTemplatesListItem["PARAMETERS"],
			"STATE_PARAMETERS" => array(),
			"STATE_PERMISSIONS" => array(),
			"WORKFLOW_STATUS" => -1,
		);

		$type = "CBP".$arWorkflowTemplate[0]["Type"];
		$bStateMachine = false;
		while (strlen($type) > 0)
		{
			if ($type == "CBPStateMachineWorkflowActivity")
			{
				$bStateMachine = true;
				break;
			}
			$type = get_parent_class($type);
		}

		if ($bStateMachine)
		{
			//if (strlen($stateName) <= 0)
			$stateName = $arWorkflowTemplate[0]["Properties"]["InitialStateName"];

			if (is_array($arWorkflowTemplate[0]["Children"]))
			{
				foreach ($arWorkflowTemplate[0]["Children"] as $state)
				{
					if ($stateName == $state["Name"])
					{
						$result["STATE_NAME"] = $stateName;
						$result["STATE_TITLE"] = $state["Properties"]["Title"];
						$result["STATE_PARAMETERS"] = array();
						$result["STATE_PERMISSIONS"] = $state["Properties"]["Permission"];

						if (is_array($state["Children"]))
						{
							foreach ($state["Children"] as $event)
							{
								if ($event["Type"] == "EventDrivenActivity")
								{
									if ($event["Children"][0]["Type"] == "HandleExternalEventActivity")
									{
										$result["STATE_PARAMETERS"][] = array(
											"NAME" => $event["Children"][0]["Name"],
											"TITLE" => $event["Children"][0]["Properties"]["Title"],
											"PERMISSION" => $event["Children"][0]["Properties"]["Permission"],
										);
									}
								}
							}
						}

						break;
					}
				}
			}
		}
		else
		{
			$result["STATE_PERMISSIONS"] = $arWorkflowTemplate[0]["Properties"]["Permission"];
		}

		if (is_array($result["STATE_PERMISSIONS"]))
		{
			$arKeys = array_keys($result["STATE_PERMISSIONS"]);
			foreach ($arKeys as $key)
			{
				$ar = self::ExtractValuesFromVariables($result["STATE_PERMISSIONS"][$key], $arTemplatesListItem["VARIABLES"]);
				$result["STATE_PERMISSIONS"][$key] = CBPHelper::MakeArrayFlat($ar);
			}
		}

		return $result;
	}

	private static function ExtractValuesFromVariables($ar, $arVars)
	{
		if (is_string($ar) && preg_match("/^\{=([A-Za-z0-9_]+)\:([A-Za-z0-9_]+)\}$/i", $ar, $arMatches))
			$ar = array($arMatches[1], $arMatches[2]);

		if (is_array($ar))
		{
			if (!CBPHelper::IsAssociativeArray($ar))
			{
				if ((count($ar) == 2) && ($ar[0] == "Variable"))
				{
					if (is_array($arVars) && array_key_exists($ar[1], $arVars))
						return array($arVars[$ar[1]]["Default"]);
				}

				$arResult = array();

				foreach ($ar as $ar1)
					$arResult[] = self::ExtractValuesFromVariables($ar1, $arVars);

				return $arResult;
			}
		}

		return $ar;
	}

	public static function GetDocumentTypeStates($documentType, $autoExecute = -1, $stateName = "")
	{
		$result = array();

		$arFilter = array("DOCUMENT_TYPE" => $documentType);
		$autoExecute = intval($autoExecute);
		if ($autoExecute >= 0)
			$arFilter["AUTO_EXECUTE"] = $autoExecute;
		$arFilter["ACTIVE"] = "Y";

		$dbTemplatesList = self::GetList(
			array(),
			$arFilter,
			false,
			false,
			array("ID", "NAME", "DESCRIPTION", "TEMPLATE", "PARAMETERS", "VARIABLES")
		);
		while ($arTemplatesListItem = $dbTemplatesList->Fetch())
			$result[$arTemplatesListItem["ID"]] = self::ParseDocumentTypeStates($arTemplatesListItem);

		return $result;
	}

	public static function GetTemplateState($workflowTemplateId, $stateName = "")
	{
		$workflowTemplateId = intval($workflowTemplateId);
		if ($workflowTemplateId <= 0)
			throw new CBPArgumentOutOfRangeException("workflowTemplateId", $workflowTemplateId);

		$result = null;

		$dbTemplatesList = self::GetList(
			array(),
			array("ID" => $workflowTemplateId),
			false,
			false,
			array("ID", "NAME", "DESCRIPTION", "TEMPLATE", "PARAMETERS", "VARIABLES")
		);
		if ($arTemplatesListItem = $dbTemplatesList->Fetch())
			$result = self::ParseDocumentTypeStates($arTemplatesListItem);
		else
			throw new Exception(str_replace("#ID#", $workflowTemplateId, GetMessage("BPCGWTL_INVALID_WF_ID")));

		return $result;
	}

	public static function CheckWorkflowParameters($arTemplateParameters, $arPossibleValues, $documentType, &$arErrors)
	{
		$arErrors = array();
		$arWorkflowParameters = array();

		if (count($arTemplateParameters) <= 0)
			return array();

		$runtime = CBPRuntime::GetRuntime();
		$runtime->StartRuntime();
		$documentService = $runtime->GetService("DocumentService");

		foreach ($arTemplateParameters as $parameterKey => $arParameter)
		{
			$arErrorsTmp = array();

			$arWorkflowParameters[$parameterKey] = $documentService->GetFieldInputValue(
				$documentType,
				$arParameter,
				$parameterKey,
				$arPossibleValues,
				$arErrorsTmp
			);

			if ($arParameter["Required"]
			    && ($arParameter["Multiple"] && count($arWorkflowParameters[$parameterKey]) <= 0
			        || !$arParameter["Multiple"] && $arWorkflowParameters[$parameterKey] === null))
			{
				$arErrorsTmp[] = array(
					"code" => "RequiredValue",
					"message" => str_replace("#NAME#", $arParameter["Name"], GetMessage("BPCGWTL_INVALID8")),
					"parameter" => $parameterKey,
				);
			}

			$arErrors = array_merge($arErrors, $arErrorsTmp);
		}

		return $arWorkflowParameters;
	}

	public static function SearchTemplatesByDocumentType($documentType, $autoExecute = -1)
	{
		$result = array();

		$arFilter = array("DOCUMENT_TYPE" => $documentType);
		$autoExecute = intval($autoExecute);
		if ($autoExecute >= 0)
			$arFilter["AUTO_EXECUTE"] = $autoExecute;

		$dbTemplatesList = self::GetList(
			array(),
			$arFilter,
			false,
			false,
			array("ID", "NAME", "DESCRIPTION", "AUTO_EXECUTE")
		);
		while ($arTemplatesListItem = $dbTemplatesList->Fetch())
		{
			$result[] = array(
				"ID" => $arTemplatesListItem["ID"],
				"NAME" => $arTemplatesListItem["NAME"],
				"DESCRIPTION" => $arTemplatesListItem["DESCRIPTION"],
				"AUTO_EXECUTE" => $arTemplatesListItem["AUTO_EXECUTE"],
			);
		}

		return $result;
	}

	public static function &FindActivityByName(&$arWorkflowTemplate, $activityName)
	{
		foreach ($arWorkflowTemplate as $key => $value)
		{
			if ($value["Name"] == $activityName)
				return $arWorkflowTemplate[$key];

			if (is_array($value["Children"]))
			{
				if ($res = &self::FindActivityByName($arWorkflowTemplate[$key]["Children"], $activityName))
					return $res;
			}
		}
		return null;
	}

	public static function &FindParentActivityByName(&$arWorkflowTemplate, $activityName)
	{
		foreach ($arWorkflowTemplate as $key => $value)
		{
			if (is_array($value["Children"]))
			{
				for ($i = 0; $i < count($value["Children"]); $i++)
				{
					if ($value["Children"][$i]["Name"] == $activityName)
						return $arWorkflowTemplate[$key];
				}

				if ($res = &self::FindParentActivityByName($arWorkflowTemplate[$key]["Children"], $activityName))
					return $res;
			}
		}
		return null;
	}

	private static function ConvertValueCharset($s, $direction)
	{
		if ("utf-8" == strtolower(LANG_CHARSET))
			return $s;

		if (is_numeric($s))
			return $s;

		if ($direction == BP_EI_DIRECTION_EXPORT)
			$s = $GLOBALS["APPLICATION"]->ConvertCharset($s, LANG_CHARSET, "UTF-8");
		else
			$s = $GLOBALS["APPLICATION"]->ConvertCharset($s, "UTF-8", LANG_CHARSET);

		return $s;
	}

	private static function ConvertArrayCharset($value, $direction = BP_EI_DIRECTION_EXPORT)
	{
		if (is_array($value))
		{
			$valueNew = array();
			foreach ($value as $k => $v)
			{
				$k = self::ConvertValueCharset($k, $direction);
				$v = self::ConvertArrayCharset($v, $direction);
				$valueNew[$k] = $v;
			}
			$value = $valueNew;
		}
		else
		{
			$value = self::ConvertValueCharset($value, $direction);
		}

		return $value;
	}

	public static function ExportTemplate($id, $bCompress = true)
	{
		$id = intval($id);
		if ($id <= 0)
			return false;

		$db = self::GetList(array("ID" => "DESC"), array("ID" => $id), false, false, array("TEMPLATE", "PARAMETERS", "VARIABLES", "MODULE_ID", "ENTITY", "DOCUMENT_TYPE"));
		if ($ar = $db->Fetch())
		{
			$datum = array(
				"VERSION" => 2,
				"TEMPLATE" => self::ConvertArrayCharset($ar["TEMPLATE"], BP_EI_DIRECTION_EXPORT),
				"PARAMETERS" => self::ConvertArrayCharset($ar["PARAMETERS"], BP_EI_DIRECTION_EXPORT),
				"VARIABLES" => self::ConvertArrayCharset($ar["VARIABLES"], BP_EI_DIRECTION_EXPORT),
			);

			$runtime = CBPRuntime::GetRuntime();
			$runtime->StartRuntime();

			$documentService = $runtime->GetService("DocumentService");
			$arDocumentFieldsTmp = $documentService->GetDocumentFields($ar["DOCUMENT_TYPE"]);
			$arDocumentFields = array();
			$len = strlen("_PRINTABLE");
			foreach ($arDocumentFieldsTmp as $k => $v)
			{
				if (substr($k, -$len) != "_PRINTABLE")
					$arDocumentFields[$k] = $v;
			}

			$datum["DOCUMENT_FIELDS"] = self::ConvertArrayCharset($arDocumentFields, BP_EI_DIRECTION_EXPORT);

			$datum = serialize($datum);
			if ($bCompress && function_exists("gzcompress"))
				$datum = gzcompress($datum, 9);

			return $datum;
		}

		return false;
	}

	private static function WalkThroughWorkflowTemplate(&$arWorkflowTemplate, $callback, $user)
	{
		foreach ($arWorkflowTemplate as $key => $value)
		{
			if (!call_user_func_array($callback, array($value, $user)))
				return false;

			if (is_array($value["Children"]))
			{
				if (!self::WalkThroughWorkflowTemplate($arWorkflowTemplate[$key]["Children"], $callback, $user))
					return false;
			}
		}
		return true;
	}

	private static function ImportTemplateChecker($arActivity, $user)
	{
		$arErrors = CBPActivity::CallStaticMethod($arActivity["Type"], "ValidateProperties", array($arActivity["Properties"], $user));
		if (count($arErrors) > 0)
		{
			$m = "";
			foreach ($arErrors as $er)
				$m .= $er["message"].". ";

			throw new Exception($m);

			return false;
		}

		return true;
	}

	public static function ImportTemplate($id, $documentType, $autoExecute, $name, $description, $datum)
	{
		$id = intval($id);
		if ($id <= 0)
			$id = 0;

		$datumTmp = @unserialize($datum);
		if (!is_array($datumTmp) || is_array($datumTmp) && !array_key_exists("TEMPLATE", $datumTmp))
		{
			if (function_exists("gzcompress"))
			{
				$datumTmp = @gzuncompress($datum);
				$datumTmp = @unserialize($datumTmp);
			}
		}

		if (!is_array($datumTmp) || is_array($datumTmp) && !array_key_exists("TEMPLATE", $datumTmp))
			throw new Exception(GetMessage("BPCGWTL_WRONG_TEMPLATE"));

		if (array_key_exists("VERSION", $datumTmp) && $datumTmp["VERSION"] == 2)
		{
			$datumTmp["TEMPLATE"] = self::ConvertArrayCharset($datumTmp["TEMPLATE"], BP_EI_DIRECTION_IMPORT);
			$datumTmp["PARAMETERS"] = self::ConvertArrayCharset($datumTmp["PARAMETERS"], BP_EI_DIRECTION_IMPORT);
			$datumTmp["VARIABLES"] = self::ConvertArrayCharset($datumTmp["VARIABLES"], BP_EI_DIRECTION_IMPORT);
			$datumTmp["DOCUMENT_FIELDS"] = self::ConvertArrayCharset($datumTmp["DOCUMENT_FIELDS"], BP_EI_DIRECTION_IMPORT);
		}

		if (!self::WalkThroughWorkflowTemplate($datumTmp["TEMPLATE"], array("CBPWorkflowTemplateLoader", "ImportTemplateChecker"), new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser)))
			return false;

		if ($id > 0)
		{
			self::Update(
				$id,
				array(
					"DOCUMENT_TYPE" => $documentType,
					"AUTO_EXECUTE" => $autoExecute,
					"NAME" => $name,
					"DESCRIPTION" => $description,
					"TEMPLATE" => $datumTmp["TEMPLATE"],
					"PARAMETERS" => $datumTmp["PARAMETERS"],
					"VARIABLES" => $datumTmp["VARIABLES"],
					"USER_ID" => $GLOBALS["USER"]->GetID(),
					"MODIFIER_USER" => new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser),
				)
			);
		}
		else
		{
			$id = self::Add(
				array(
					"DOCUMENT_TYPE" => $documentType,
					"AUTO_EXECUTE" => $autoExecute,
					"NAME" => $name,
					"DESCRIPTION" => $description,
					"TEMPLATE" => $datumTmp["TEMPLATE"],
					"PARAMETERS" => $datumTmp["PARAMETERS"],
					"VARIABLES" => $datumTmp["VARIABLES"],
					"USER_ID" => $GLOBALS["USER"]->GetID(),
					"ACTIVE" => "Y",
					"MODIFIER_USER" => new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser),
				)
			);
		}

		$runtime = CBPRuntime::GetRuntime();
		$runtime->StartRuntime();

		$documentService = $runtime->GetService("DocumentService");
		$arDocumentFields = $documentService->GetDocumentFields($documentType);

		if (is_array($datumTmp["DOCUMENT_FIELDS"]))
		{
			$len = strlen("_PRINTABLE");
			foreach ($datumTmp["DOCUMENT_FIELDS"] as $code => $field)
			{
				if (!array_key_exists($code, $arDocumentFields) && (substr($code, -$len) != "_PRINTABLE"))
				{
					$arFieldsTmp = array(
						"name" => $field["Name"],
						"code" => $code,
						"type" => $field["Type"],
						"multiple" => $field["Multiple"],
						"required" => $field["Required"],
					);

					if (is_array($field["Options"]) && count($field["Options"]) > 0)
					{
						foreach ($field["Options"] as $k => $v)
							$arFieldsTmp["options"] .= "[".$k."]".$v."\n";
					}

					$documentService->AddDocumentField($documentType, $arFieldsTmp);
				}
			}
		}

		return $id;
	}
}

class CBPWorkflowTemplateResult extends CDBResult
{
	private $useGZipCompression = false;

	public function __construct($res, $useGZipCompression)
	{
		$this->useGZipCompression = $useGZipCompression;
		parent::CDBResult($res);
	}

	private function GetFromSerializedForm($value)
	{
		if (strlen($value) > 0)
		{
			if ($this->useGZipCompression)
			{
				$value1 = @gzuncompress($value);
				if ($value1 !== false)
					$value = $value1;
			}

			$value = unserialize($value);
			if (!is_array($value))
				$value = array();
		}
		else
		{
			$value = array();
		}
		return $value;
	}

	public function Fetch()
	{
		$res = parent::Fetch();

		if ($res)
		{
			if (array_key_exists("DOCUMENT_TYPE", $res))
				$res["DOCUMENT_TYPE"] = array($res["MODULE_ID"], $res["ENTITY"], $res["DOCUMENT_TYPE"]);
			if (array_key_exists("TEMPLATE", $res))
				$res["TEMPLATE"] = $this->GetFromSerializedForm($res["TEMPLATE"]);
			if (array_key_exists("VARIABLES", $res))
				$res["VARIABLES"] = $this->GetFromSerializedForm($res["VARIABLES"]);
			if (array_key_exists("PARAMETERS", $res))
			{
				$res["PARAMETERS"] = $this->GetFromSerializedForm($res["PARAMETERS"]);
				$arParametersKeys = array_keys($res["PARAMETERS"]);
				foreach ($arParametersKeys as $parameterKey)
					$res["PARAMETERS"][$parameterKey]["Type"] = $res["PARAMETERS"][$parameterKey]["Type"];
			}
		}

		return $res;
	}
}

class CBPWorkflowTemplateUser
{
	const CurrentUser = "CurrentUser";

	private $isAdmin = false;

	public function __construct($userId = null)
	{
		$this->isAdmin = false;

		if (is_int($userId))
		{
			$userGroups = CUser::GetUserGroup($userId);
			$this->isAdmin = in_array(1, $userGroups);
		}
		elseif ($userId == self::CurrentUser)
		{
			global $USER;

			if ($USER->IsAuthorized())
				$this->isAdmin = $USER->IsAdmin();
		}
	}

	public function IsAdmin()
	{
		return $this->isAdmin;
	}
}
?>