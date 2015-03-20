<?

IncludeModuleLangFile(__FILE__);
class CCrmPaySystem
{
	private static $arActFiles = array();
	private static $arCrmCompatibleActs = array('bill', 'bill_ua', 'bill_en', 'bill_de', 'quote');
	private static $paySystems = null;

	public static function LocalGetPSActionParams($fileName)
	{
		$arPSCorrespondence = array();

		if (file_exists($fileName) && is_file($fileName))
			include($fileName);

		return $arPSCorrespondence;
	}

	private static function LocalGetPSActionDescr($fileName)
	{
		$psTitle = "";
		$psDescription = "";

		if (file_exists($fileName) && is_file($fileName))
			include($fileName);

		return array($psTitle, $psDescription);
	}

	public static function getActions()
	{
		if (!CModule::IncludeModule('sale'))
			return array();

		if(!empty(self::$arActFiles))
			return self::$arActFiles;

		$arUserPSActions = array();
		$arSystemPSActions = array();

		$path2SystemPSFiles = "/bitrix/modules/sale/payment/";
		$path2UserPSFiles = COption::GetOptionString("sale", "path2user_ps_files", BX_PERSONAL_ROOT."/php_interface/include/sale_payment/");
		CheckDirPath($_SERVER["DOCUMENT_ROOT"].$path2UserPSFiles);

		$handle = @opendir($_SERVER["DOCUMENT_ROOT"].$path2UserPSFiles);
		if ($handle)
		{
			while (false !== ($dir = readdir($handle)))
			{
				if ($dir == "." || $dir == ".." )
					continue;

				$title = "";
				$description = "";

				if (is_dir($_SERVER["DOCUMENT_ROOT"].$path2UserPSFiles.$dir))
				{
					$newFormat = "Y";
					list($title, $description) = self::LocalGetPSActionDescr($_SERVER["DOCUMENT_ROOT"].$path2UserPSFiles.$dir."/.description.php");
					if (strlen($title) <= 0)
						$title = $dir;
					else
						$title .= " (".$dir.")";
				}

				if(strlen($title) > 0)
				{
					$arUserPSActions[] = array(
							"ID" => $dir,
							"PATH" => $path2UserPSFiles.$dir,
							"TITLE" => $title,
							"DESCRIPTION" => $description,
							"NEW_FORMAT" => $newFormat
						);
				}
			}
			@closedir($handle);
		}

		$handle = @opendir($_SERVER["DOCUMENT_ROOT"].$path2SystemPSFiles);
		if ($handle)
		{
			while (false !== ($dir = readdir($handle)))
			{
				if ($dir == "." || $dir == ".." || !in_array($dir, self::$arCrmCompatibleActs))
					continue;

				if (is_dir($_SERVER["DOCUMENT_ROOT"].$path2SystemPSFiles.$dir))
				{
					$newFormat = "Y";
					list($title, $description) = self::LocalGetPSActionDescr($_SERVER["DOCUMENT_ROOT"].$path2SystemPSFiles.$dir."/.description.php");
					if (strlen($title) <= 0)
						$title = $dir;
					else
						$title .= " (".$dir.")";
				}

				$arSystemPSActions[] = array(
						"ID" => $dir,
						"PATH" => $path2SystemPSFiles.$dir,
						"TITLE" => $title,
						"DESCRIPTION" => $description,
						"NEW_FORMAT" => $newFormat
					);
			}
			@closedir($handle);
		}

		foreach($arUserPSActions as $val)
			self::$arActFiles[$val['ID']] = $val;

		foreach($arSystemPSActions as $val)
			self::$arActFiles[$val['ID']] = $val;

		sortByColumn(self::$arActFiles, array("ID" => SORT_ASC));

		return self::$arActFiles;
	}

	public static function getActionsList()
	{
		$arReturn = array();
		$arAFF = self::getActions();

		foreach ($arAFF as $id => $arAction)
			$arReturn[$id] = $arAction['TITLE'];

		return $arReturn;
	}

	public static function getActionPath($actionId)
	{
		$arActions = self::getActions();

		if(isset($arActions[$actionId]['PATH']))
			return $arActions[$actionId]['PATH'];

		return false;
	}

	public static function getActionSelector($idCorr, $arCorr)
	{
		if ($arCorr['TYPE'] == 'SELECT' || $arCorr['TYPE'] == 'FILE')
		{
			$res  = '<select name="TYPE_'.$idCorr.'" id="TYPE_'.$idCorr.'" style="display: none;">';
			$res .= '<option selected value="'.$arCorr["TYPE"].'"></option>';
			$res .= '</select>';
		}
		else
		{
			$bSimple = self::isFormSimple();

			$res = '<select name="TYPE_'.$idCorr.'" id="TYPE_'.$idCorr.'"'.($bSimple ? ' style="display: none;"' : '').'>\n';
			$res .= '<option value=""'.($arCorr['TYPE'] == '' ? ' selected' : '').'>'.GetMessage("CRM_PS_TYPES_OTHER").'</option>\n';
			//$res .= '<option value="USER"'.($arCorr['TYPE'] == 'USER' ? ' selected' : '').'>'.GetMessage("CRM_PS_TYPES_USER").'</option>\n';
			$res .= '<option value="ORDER"'.($arCorr['TYPE'] == 'ORDER' ? ' selected' : '').'>'.GetMessage("CRM_PS_TYPES_ORDER").'</option>\n';
			$res .= '<option value="PROPERTY"'.($arCorr['TYPE'] == 'PROPERTY' ? ' selected' : '').'>'.GetMessage("CRM_PS_TYPES_PROPERTY").'</option>\n';
			$res .= '</select>';
		}

		return $res;
	}

	public static function getOrderPropsList($persTypeId = false)
	{
		static $arProps = array();

		if(empty($arProps) && CModule::IncludeModule('sale'))
		{
			$arPersTypeIds = self::getPersonTypeIDs();

			$dbOrderProps = CSaleOrderProps::GetList(
					array("SORT" => "ASC", "NAME" => "ASC"),
					array("PERSON_TYPE_ID" => $arPersTypeIds),
					false,
					false,
					array("ID", "CODE", "NAME", "TYPE", "SORT", "PERSON_TYPE_ID")
				);

			while ($arOrderProps = $dbOrderProps->Fetch())
			{
				$idx = strlen($arOrderProps["CODE"])>0 ? $arOrderProps["CODE"] : $arOrderProps["ID"];
				$arProps[$arOrderProps["PERSON_TYPE_ID"]][$idx] = $arOrderProps["NAME"];

				if ($arOrderProps["TYPE"] == "LOCATION")
				{
					$idx = strlen($arOrderProps["CODE"])>0 ? $arOrderProps["CODE"]."_COUNTRY" : $arOrderProps["ID"]."_COUNTRY";
					$arProps[$arOrderProps["PERSON_TYPE_ID"]][$idx] = $arOrderProps["NAME"]." (".GetMessage("CRM_PS_JCOUNTRY").")";

					$idx = strlen($arOrderProps["CODE"])>0 ? $arOrderProps["CODE"]."_CITY" : $arOrderProps["ID"]."_CITY";
					$arProps[$arOrderProps["PERSON_TYPE_ID"]][$idx] = $arOrderProps["NAME"]." (".GetMessage("CRM_PS_JCITY").")";
				}
			}
		}

		if($persTypeId && isset($arProps[$persTypeId]))
			$arReturn = $arProps[$persTypeId];
		elseif($persTypeId && !isset($arProps[$persTypeId]))
			$arReturn = false;
		else
			$arReturn = $arProps;

		return $arReturn;
	}

	public static function getOrderFieldsList()
	{
		return $arProps = array(
					"ID" => GetMessage("CRM_PS_ORDER_ID"),
					"ORDER_TOPIC" => GetMessage("CRM_FIELD_ORDER_TOPIC"),
					"DATE_INSERT" => GetMessage("CRM_PS_ORDER_DATETIME"),
					"DATE_INSERT_DATE" => GetMessage("CRM_PS_ORDER_DATE"),
					"DATE_BILL" => GetMessage("CRM_PS_ORDER_DATE_BILL"),
					"DATE_BILL_DATE" => GetMessage("CRM_PS_ORDER_DATE_BILL_DATE"),
					"DATE_PAY_BEFORE" => GetMessage("CRM_PS_ORDER_DATE_PAY_BEFORE"),
					"SHOULD_PAY" => GetMessage("CRM_PS_ORDER_PRICE"),
					"CURRENCY" => GetMessage("CRM_PS_ORDER_CURRENCY"),
					"PRICE" => GetMessage("CRM_PS_ORDER_SUM"),
					//"LID" => GetMessage("CRM_PS_ORDER_SITE"),
					"PRICE_DELIVERY" => GetMessage("CRM_PS_ORDER_PRICE_DELIV"),
					"DISCOUNT_VALUE" => GetMessage("CRM_PS_ORDER_DESCOUNT"),
					"USER_ID" => GetMessage("CRM_PS_ORDER_USER_ID"),
					"PAY_SYSTEM_ID" => GetMessage("CRM_PS_ORDER_PS"),
					"DELIVERY_ID" => GetMessage("CRM_PS_ORDER_DELIV"),
					"TAX_VALUE" => GetMessage("CRM_PS_ORDER_TAX"),
					"USER_DESCRIPTION" => GetMessage("CRM_PS_ORDER_USER_DESCRIPTION")
				);
	}

	public static function getUserPropsList()
	{
		return $arProps = array(
					"ID" => GetMessage("CRM_PS_USER_ID"),
					"LOGIN" => GetMessage("CRM_PS_USER_LOGIN"),
					"NAME" => GetMessage("CRM_PS_USER_NAME"),
					"SECOND_NAME" => GetMessage("CRM_PS_USER_SECOND_NAME"),
					"LAST_NAME" => GetMessage("CRM_PS_USER_LAST_NAME"),
					"EMAIL" => "EMail",
					//"LID" => GetMessage("CRM_PS_USER_SITE"),
					"PERSONAL_PROFESSION" => GetMessage("CRM_PS_USER_PROF"),
					"PERSONAL_WWW" => GetMessage("CRM_PS_USER_WEB"),
					"PERSONAL_ICQ" => GetMessage("CRM_PS_USER_ICQ"),
					"PERSONAL_GENDER" => GetMessage("CRM_PS_USER_SEX"),
					"PERSONAL_FAX" => GetMessage("CRM_PS_USER_FAX"),
					"PERSONAL_MOBILE" => GetMessage("CRM_PS_USER_PHONE"),
					"PERSONAL_STREET" => GetMessage("CRM_PS_USER_ADDRESS"),
					"PERSONAL_MAILBOX" => GetMessage("CRM_PS_USER_POST"),
					"PERSONAL_CITY" => GetMessage("CRM_PS_USER_CITY"),
					"PERSONAL_STATE" => GetMessage("CRM_PS_USER_STATE"),
					"PERSONAL_ZIP" => GetMessage("CRM_PS_USER_ZIP"),
					"PERSONAL_COUNTRY" => GetMessage("CRM_PS_USER_COUNTRY"),
					"WORK_COMPANY" => GetMessage("CRM_PS_USER_COMPANY"),
					"WORK_DEPARTMENT" => GetMessage("CRM_PS_USER_DEPT"),
					"WORK_POSITION" => GetMessage("CRM_PS_USER_DOL"),
					"WORK_WWW" => GetMessage("CRM_PS_USER_COM_WEB"),
					"WORK_PHONE" => GetMessage("CRM_PS_USER_COM_PHONE"),
					"WORK_FAX" => GetMessage("CRM_PS_USER_COM_FAX"),
					"WORK_STREET" => GetMessage("CRM_PS_USER_COM_ADDRESS"),
					"WORK_MAILBOX" => GetMessage("CRM_PS_USER_COM_POST"),
					"WORK_CITY" => GetMessage("CRM_PS_USER_COM_CITY"),
					"WORK_STATE" => GetMessage("CRM_PS_USER_COM_STATE"),
					"WORK_ZIP" => GetMessage("CRM_PS_USER_COM_ZIP"),
					"WORK_COUNTRY" => GetMessage("CRM_PS_USER_COM_COUNTRY")
		);
	}

	public static function getSelectPropsList($values)
	{
		$arProps = array();

		foreach ($values as $k => $value)
		{
			$arProps[$k] = $value['NAME'];
		}

		return $arProps;
	}

	public static function getActionValueSelector($idCorr, $arCorr, $persTypeId, $actionFileName = '', $userFields = null)
	{
		if ($arCorr['TYPE'] == 'FILE')
		{
			$res = '<input type="file" name="VALUE1_'.$idCorr.'" id="VALUE1_'.$idCorr.'" size="40">';

			if ($arCorr['VALUE'])
			{
				$res .= '<span><br>' . $arCorr['VALUE'];
				$res .= '<br><input type="checkbox" name="' . $idCorr . '_del" value="Y" id="' . $idCorr . '_del" >';
				$res .= '<label for="' . $idCorr . '_del">' . GetMessage("CRM_PS_DEL_FILE") . '</label></span>';
			}
		}
		else
		{
			$res = '<select name="VALUE1_'.$idCorr.'" id="VALUE1_'.$idCorr.'"'.($arCorr['TYPE'] == '' ? ' style="display: none;"' : '').'>';

			$arProps = array();

			if($arCorr['TYPE'] == 'USER')
			{
				$arProps = self::getUserPropsList();
			}
			if($arCorr['TYPE'] == 'ORDER')
			{
				$arProps = self::getOrderFieldsList();
			}
			elseif($arCorr['TYPE'] == 'PROPERTY')
			{
				$arProps = self::getOrderPropsList($persTypeId);

				if( is_array($userFields)
					&& is_string($actionFileName)
					&& preg_match('/^([a-z]+)(?:_([a-z]+))?$/i', $actionFileName, $matches) === 1
					&& isset($userFields[$matches[1]]))
				{
					$arProps = array_merge($arProps, $userFields[$matches[1]]);
				}
			}
			elseif ($arCorr['TYPE'] == 'SELECT')
			{
				$arProps = self::getSelectPropsList($arCorr['OPTIONS']);
			}

			if(!empty($arProps))
				foreach ($arProps as $id => $propName)
					$res .= '<option value="'.$id.'"'.($arCorr['VALUE'] == $id ? ' selected' : '').'>'.$propName.'</option>\n';

			if ($arCorr['TYPE'] != 'SELECT')
			{
				if ($arCorr['TYPE'] != '')
					$arCorr['VALUE'] = '';

				$res .= '<input type="text" value="'.htmlspecialcharsbx($arCorr['VALUE']);
				$res .= '" name="VALUE2_'.$idCorr;
				$res .= '" id="VALUE2_'.$idCorr;
				$res .= '" size="40"'.($arCorr['TYPE'] == '' ? '' : ' style="display: none;"').'>';
			}

			$res .= '</select>';
		}

		return $res;
	}

	public static function getPersonTypeIDs()
	{
		if (!CModule::IncludeModule('sale'))
			return array();

		static $arPTIDs = array();

		if(!empty($arPTIDs))
			return $arPTIDs;

		$dbPersonType = CSalePersonType::GetList(
				array('SORT' => "ASC", 'NAME' => 'ASC'),
				array('NAME' => array('CRM_COMPANY', 'CRM_CONTACT'))
		);

		while($arPT = $dbPersonType->GetNext())
		{
			if($arPT['NAME'] == 'CRM_COMPANY')
				$arPTIDs['COMPANY'] = $arPT['ID'];

			if($arPT['NAME'] == 'CRM_CONTACT')
				$arPTIDs['CONTACT'] = $arPT['ID'];
		}

		return $arPTIDs;
	}

	public static function getPersonTypesList($getEmpty = false)
	{
		$arPtIDs = self::getPersonTypeIDs();

		if(empty($arPtIDs) || !CModule::IncludeModule('sale'))
			return array();

		$arReturn = array();

		if($getEmpty)
			$arReturn[""] = GetMessage('CRM_ANY');

		$dbPersonType = CSalePersonType::GetList(
			array('SORT' => "ASC", 'NAME' => 'ASC'),
			array('ID' => array($arPtIDs['COMPANY'], $arPtIDs['CONTACT']))
		);

		while($arPT = $dbPersonType->GetNext())
			$arReturn[$arPT['ID']] = GetMessage($arPT['NAME']."_PT");

		return $arReturn;
	}

	public static function resolveOwnerTypeID($personTypeID)
	{
		$personTypeID = intval($personTypeID);
		$personTypeIDs = self::getPersonTypeIDs();
		if(isset($personTypeIDs['COMPANY']) && intval($personTypeIDs['COMPANY']) === $personTypeID)
		{
			return CCrmOwnerType::Company;
		}
		if(isset($personTypeIDs['CONTACT']) && intval($personTypeIDs['CONTACT']) === $personTypeID)
		{
			return CCrmOwnerType::Contact;
		}
		return CCrmOwnerType::Undefined;
	}

	public static function getPSCorrespondence($actFile)
	{
		if(!$actFile || !CModule::IncludeModule('sale'))
			return false;

		$arPSCorrespondence = array();

		$file = CCrmPaySystem::getActionPath($actFile);

		$path2SystemPSFiles = "/bitrix/modules/sale/payment/";
		$path2UserPSFiles = COption::GetOptionString("sale", "path2user_ps_files", BX_PERSONAL_ROOT."/php_interface/include/sale_payment/");

		if (substr($path2UserPSFiles, strlen($path2UserPSFiles) - 1, 1) != "/")
			$path2UserPSFiles .= "/";

		$bSystemPSFile = (substr($file, 0, strlen($path2SystemPSFiles)) == $path2SystemPSFiles);

		if (!$bSystemPSFile)
		{
			if (substr($path2UserPSFiles, strlen($path2UserPSFiles) - 1, 1) != "/")
				$path2UserPSFiles .= "/";
			$bUserPSFile = (substr($file, 0, strlen($path2UserPSFiles)) == $path2UserPSFiles);
		}

		if ($bUserPSFile || $bSystemPSFile)
		{
			if ($bUserPSFile)
				$fileName = substr($file, strlen($path2UserPSFiles));
			else
				$fileName = substr($file, strlen($path2SystemPSFiles));

			$fileName = preg_replace("#[^A-Za-z0-9_.-]#i", "", $fileName);

			$arPSCorrespondence = CCrmPaySystem::LocalGetPSActionParams($_SERVER["DOCUMENT_ROOT"].(($bUserPSFile) ? $path2UserPSFiles : $path2SystemPSFiles).$fileName."/.description.php");
		}

		return $arPSCorrespondence;
	}

	public static function isFormSimple()
	{
		return CUserOptions::GetOption("crm", "simplePSForm", "Y") == "Y";
	}

	public static function setFormSimple($bSimple = true)
	{
		return CUserOptions::SetOption("crm", "simplePSForm", ($bSimple ? "Y" : "N"));
	}

	public static function unSetFormSimple()
	{
		self::setFormSimple(false);
	}

	public static function GetPaySystems($personTypeId)
	{
		if(!CModule::IncludeModule('sale'))
		{
			return false;
		}

		if (self::$paySystems === null)
		{
			$arPersonTypes = self::getPersonTypeIDs();
			if (!isset($arPersonTypes['COMPANY']) || !isset($arPersonTypes['CONTACT']) ||
				$arPersonTypes['COMPANY'] <= 0 || $arPersonTypes['CONTACT'] <= 0)
				return false;

			$companyPaySystems = CSalePaySystem::DoLoadPaySystems($arPersonTypes['COMPANY']);
			$contactPaySystems = CSalePaySystem::DoLoadPaySystems($arPersonTypes['CONTACT']);

			self::$paySystems = array(
				$arPersonTypes['COMPANY'] => $companyPaySystems,
				$arPersonTypes['CONTACT'] => $contactPaySystems,
			);
		}

		if (!in_array($personTypeId, array_keys(self::$paySystems)))
			return false;

		return self::$paySystems[$personTypeId];
	}

	public static function GetPaySystemsListItems($personTypeId)
	{
		$arItems = array();

		$arPaySystems = self::GetPaySystems($personTypeId);
		if (is_array($arPaySystems))
			foreach ($arPaySystems as $paySystem)
				$arItems[$paySystem['~ID']] = $paySystem['~NAME'];

		return $arItems;
	}

	/**
	* Checks if is filled company-name at least in one pay system
	*/
	public static function isNameFilled()
	{
		if (!CModule::IncludeModule('sale'))
			return false;

		$result = false;
		$arCrmPtIDs = CCrmPaySystem::getPersonTypeIDs();
		$dbPaySystems = CSalePaySystem::GetList(array(), array( "PERSON_TYPE_ID" => $arCrmPtIDs ));

		while($arPaySys = $dbPaySystems->Fetch())
		{
			$params = $arPaySys['PSA_PARAMS'];
			$params = unserialize($arPaySys['PSA_PARAMS']);

			if(strlen(trim($params['SELLER_NAME']['VALUE'])) > 0)
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	public static function isUserMustFillPSProps()
	{
		if(CUserOptions::GetOption('crm', 'crmInvoicePSPropsFillDialogViewedByUser', 'N') === 'Y')
			return false;

		$CrmPerms = new CCrmPerms($GLOBALS['USER']->GetID());

		if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
			return false;

		if(self::isNameFilled())
			return false;

		return true;
	}

	public static function markPSFillPropsDialogAsViewed()
	{
		return CUserOptions::SetOption('crm', 'crmInvoicePSPropsFillDialogViewedByUser', 'Y');
	}
}

?>