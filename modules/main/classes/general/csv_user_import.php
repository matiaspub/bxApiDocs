<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

IncludeModuleLangFile(__FILE__);

class CSVUserImport
{
	var $csv;
	var $arHeader = false;
	var $isErrorOccured = false;
	var $errorMessage = "";
	var $ignoreDuplicate = false;
	var $userGroups = false;
	var $callback = null;
	var $defaultEmail = false;
	var $imageFilePath = null;
	var $externalAuthID = null;

	var $attachIBlockID = 0;
	var $userPropertyName = "UF_DEPARTMENT";

	var $arSectionCache = Array();
	var $isUserPropertyCreate = false;

	public function __construct($csvFilePath, $delimiter)
	{
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/csv_data.php");

		$this->csv = new CCSVData($fields_type = "R");
		$this->csv->LoadFile($csvFilePath);
		$this->csv->MoveFirst();
		$this->csv->SetDelimiter($delimiter);
		$this->csv->SetFirstHeader(false);

		if (!$this->arHeader = $this->csv->Fetch())
		{
			$this->isErrorOccured = true;
			$this->errorMessage = GetMessage("CSV_IMPORT_HEADER_NOT_FOUND");
			return;
		}

		foreach($this->arHeader as $key => $val)
			$this->arHeader[$key] = strtoupper($val);

		if (!$this->CheckRequiredFields())
		{
			$this->isErrorOccured = true;
			return;
		}
	}

	public function CheckRequiredFields()
	{
		if ($this->isErrorOccured || !is_array($this->arHeader) || count($this->arHeader) <= 1)
		{
			$this->errorMessage = GetMessage("CSV_IMPORT_DELIMETER_NOT_FOUND");
			return false;
		}

		$success = array_search("NAME", $this->arHeader);
		if ($success === false || $success === null)
		{
			$this->errorMessage = GetMessage("CSV_IMPORT_NAME_NOT_FOUND");
			return false;
		}

		$success = array_search("LAST_NAME", $this->arHeader);
		if ($success === false || $success === null)
		{
			$this->errorMessage = GetMessage("CSV_IMPORT_LAST_NAME_NOT_FOUND");
			return false;
		}

		return true;
	}

	public function AttachUsersToIBlock($iblockID)
	{
		$iblockID = intval($iblockID);
		if (CModule::IncludeModule("iblock") && $iblockID > 0)
		{
			$dbIblock = CIBlock::GetByID($iblockID);
			if ($dbIblock->Fetch())
				$this->attachIBlockID = $iblockID;
		}
	}

	public function SetUserPropertyName($userPropertyName)
	{
		$userPropertyName = trim($userPropertyName);

		if (strlen($userPropertyName) > 0)
			$this->userPropertyName = $userPropertyName;
	}

	public static function GenerateUserPassword($pass_len=10)
	{
		static $allchars = "abcdefghijklnmopqrstuvwxyzABCDEFGHIJKLNMOPQRSTUVWXYZ0123456789";
		$n = 61;

		$string = "";
		for ($i = 0; $i < $pass_len; $i++)
			$string .= $allchars[mt_rand(0, $n)];

		return $string;
	}

	public function IsErrorOccured()
	{
		return $this->isErrorOccured;
	}

	public function SetExternalAuthID($externalAuthID)
	{
		if (strlen($externalAuthID) > 0)
			$this->externalAuthID = $externalAuthID;
	}

	public function GetErrorMessage()
	{
		return $this->errorMessage;
	}

	public function IgnoreDuplicate($ignore = true)
	{
		$this->ignoreDuplicate = (bool)$ignore;
	}

	public function SetCallback($functionName)
	{
		if (is_callable($functionName))
			$this->callback = $functionName;
	}

	function &GetCsvObject()
	{
		return $this->csv;
	}

	public function SetDefaultEmail($email)
	{
		if (check_email($email))
			$this->defaultEmail = $email;
	}

	public function GetDefaultEmail()
	{
		if ($this->defaultEmail !== false)
			return $this->defaultEmail;

		return COption::GetOptionString("main", "email_from", "admin@".$_SERVER["SERVER_NAME"]);
	}

	public function SetUserGroups($arGroups)
	{
		if (!is_array($arGroups))
			return;

		foreach ($arGroups as $groupID)
		{
			$groupID = intval($groupID);
			$rsGroup = CGroup::GetByID($groupID);
			if (!$rsGroup->Fetch())
				continue;

			if (!is_array($this->userGroups))
				$this->userGroups = Array();

			$this->userGroups[] = $groupID;
		}
	}

	public function SetImageFilePath($relativePath)
	{
		$relativePath = Rel2Abs("/", $relativePath);
		if (is_dir($_SERVER["DOCUMENT_ROOT"].$relativePath))
			$this->imageFilePath = rtrim($_SERVER["DOCUMENT_ROOT"].$relativePath, "/");
	}

	public function __CreateUserProperty()
	{
		if ($this->attachIBlockID < 1)
			return false;

		$success = true;
		$dbRes = CUserTypeEntity::GetList(Array(), Array("ENTITY_ID" => "USER", "FIELD_NAME" => $this->userPropertyName));
		if (!$dbRes->Fetch())
		{
			$arLabelNames = Array();
			$rsLanguage = CLanguage::GetList($by, $order, array());
			while($arLanguage = $rsLanguage->Fetch())
			{
				IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/csv_user_import_labels.php", $arLanguage["LID"]);
				$arLabelNames[$arLanguage["LID"]] = GetMessage("DEPARTMENT_USER_PROPERTY_NAME");
			}

			$arFields = Array(
				'ENTITY_ID' => 'USER',
				'FIELD_NAME' => $this->userPropertyName,
				'USER_TYPE_ID' => 'iblock_section',
				'XML_ID' => '',
				'SORT' => 100,
				'MULTIPLE' => 'Y',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'I',
				'SHOW_IN_LIST' => 'Y',
				'EDIT_IN_LIST' => 'Y',
				'IS_SEARCHABLE' => 'Y',
				'SETTINGS' => array(
					'DISPLAY' => 'LIST',
					'LIST_HEIGHT' => '8',
					'IBLOCK_ID' => $this->attachIBlockID,
				),

				"EDIT_FORM_LABEL" => $arLabelNames,
				"LIST_COLUMN_LABEL" => $arLabelNames,
				"LIST_FILTER_LABEL" => $arLabelNames,
			);

			$userType = new CUserTypeEntity();
			$success = (bool)$userType->Add($arFields);
		}

		return $success;
	}

	public function __GetIBlockSectionID(&$arFields)
	{
		$sectionID = 0;
		$i = 0;

		while(true)
		{
			$i++;

			$csvSectionCode = "IBLOCK_SECTION_NAME_".$i;
			if (!array_key_exists($csvSectionCode, $arFields))
				break;

			$sectionName = trim($arFields[$csvSectionCode]);
			if (strlen($sectionName) < 1)
				break;

			$cacheID = md5($csvSectionCode."_".$sectionName."_".$sectionID);
			if (array_key_exists($cacheID, $this->arSectionCache))
			{
				$sectionID = $this->arSectionCache[$cacheID];
				continue;
			}

			$dbSection = CIBlockSection::GetList(Array(), Array("IBLOCK_ID" => $this->attachIBlockID, "NAME" => $sectionName, "SECTION_ID" => $sectionID));
			if ($arGroup = $dbSection->Fetch())
			{
				$sectionID = $arGroup["ID"];
				$this->arSectionCache[$cacheID] = $sectionID;
				continue;
			}

			$iblockSection = new CIBlockSection;
			$arSectionFields = Array(
				"ACTIVE" => "Y",
				"IBLOCK_SECTION_ID" => $sectionID,
				"IBLOCK_ID" => $this->attachIBlockID,
				"NAME" => $sectionName,
			);

			$sectionID = (int)$iblockSection->Add($arSectionFields);
			if ($sectionID > 1)
				$this->arSectionCache[$cacheID] = $sectionID;
			else
				return 0;
		}

		return $sectionID;
	}

	public function ImportUser()
	{
		if ($this->isErrorOccured)
			return false;

		$this->errorMessage = "";

		$defaultEmail = $this->GetDefaultEmail();

		if (!$arUser = $this->csv->FetchDelimiter())
			return false;

		$arFields = Array();
		foreach($this->arHeader as $index => $key)
			if(($f = trim($arUser[$index])) <> '')
				$arFields[$key] = $f;

		if (!array_key_exists("NAME", $arFields) || strlen($arFields["NAME"]) < 1)
		{
			$this->errorMessage = GetMessage("CSV_IMPORT_NO_NAME")." (".implode(", ", $arFields).").<br>";
			return true;
		}

		if (!array_key_exists("LAST_NAME", $arFields) || strlen($arFields["LAST_NAME"]) < 1)
		{
			$this->errorMessage = GetMessage("CSV_IMPORT_NO_LASTNAME")." (".implode(", ", $arFields).").<br>";
			return true;
		}

		if (!array_key_exists("PASSWORD", $arFields) || strlen($arFields["PASSWORD"]) < 1)
			$arFields["PASSWORD"] = $this->GenerateUserPassword(6);
		$arFields["CONFIRM_PASSWORD"] = $arFields["PASSWORD"];

		if (!array_key_exists("EMAIL", $arFields) || strlen($arFields["EMAIL"]) < 3 || !check_email($arFields["EMAIL"]))
			$arFields["EMAIL"] = $defaultEmail;

		if (!array_key_exists("LOGIN", $arFields))
			$arFields["LOGIN"] = ToLower($arFields["NAME"]." ".$arFields["LAST_NAME"]);

		if (array_key_exists("PERSONAL_BIRTHDAY", $arFields) && (strlen($arFields["PERSONAL_BIRTHDAY"]) < 2 || !CheckDateTime($arFields["PERSONAL_BIRTHDAY"])))
			unset($arFields["PERSONAL_BIRTHDAY"]);

		if (array_key_exists("DATE_REGISTER", $arFields) && (strlen($arFields["DATE_REGISTER"]) < 2 || !CheckDateTime($arFields["DATE_REGISTER"])))
			unset($arFields["DATE_REGISTER"]);

		if ($this->externalAuthID !== null && !array_key_exists("EXTERNAL_AUTH_ID", $arFields))
			$arFields["EXTERNAL_AUTH_ID"] = $this->externalAuthID;

		if (!array_key_exists("XML_ID", $arFields))
			$arFields["XML_ID"] = md5(uniqid(rand(), true));

		if(!array_key_exists("CHECKWORD", $arFields) || strlen($arFields["CHECKWORD"]) <= 0)
			$arFields["CHECKWORD"] = md5(CMain::GetServerUniqID().uniqid());

		if ($this->imageFilePath !== null)
		{
			if (array_key_exists("PERSONAL_PHOTO", $arFields) && strlen($arFields["PERSONAL_PHOTO"]) > 0)
			{
				$arFile = CFile::MakeFileArray($this->imageFilePath."/".$arFields["PERSONAL_PHOTO"]);
				$arFile["MODULE_ID"] = "main";
				$arFields["PERSONAL_PHOTO"] = $arFile;
			}

			if (array_key_exists("WORK_LOGO", $arFields) && strlen($arFields["WORK_LOGO"]) > 0)
			{
				$arFile = CFile::MakeFileArray($this->imageFilePath."/".$arFields["WORK_LOGO"]);
				$arFile["MODULE_ID"] = "main";
				$arFields["WORK_LOGO"] = $arFile;
			}
		}
		else
		{
			unset($arFields["PERSONAL_PHOTO"]);
			unset($arFields["WORK_LOGO"]);
		}

		$arFields["GROUP_ID"] = $this->userGroups;

		$user = new CUser;
		$userID = (int)$user->Add($arFields);

		if($userID <= 0)
		{
			if($user->LAST_ERROR <> '')
				$this->errorMessage = $arFields["NAME"]." ".$arFields["LAST_NAME"].": ".$user->LAST_ERROR;
		}

		if ($userID <= 0 && $this->ignoreDuplicate === false)
		{
			$postFix = 2;
			$login = $arFields["LOGIN"];
			do
			{
				$rsUser = CUser::GetByLogin($arFields["LOGIN"]);
				if (!$rsUser->Fetch())
					break;

				$arFields["LOGIN"] = $login.$postFix;
				$userID = (int)$user->Add($arFields);
				if ($userID > 1)
					break;

				$postFix++;

			} while(true);
		}

		if ($userID > 0)
		{
			if ($this->attachIBlockID > 0)
			{
				$iblockSectionID = $this->__GetIBlockSectionID($arFields);
				if ($iblockSectionID > 0)
				{
					if (!$this->isUserPropertyCreate)
						$this->isUserPropertyCreate = $this->__CreateUserProperty();

					$arUpdate = Array();
					$arUpdate[$this->userPropertyName] = Array($iblockSectionID);

					$user->Update($userID, $arUpdate);
				}
			}

			if ($this->callback !== null)
				call_user_func_array($this->callback, Array(&$arFields, &$userID));
		}

		return true;

	}
}
