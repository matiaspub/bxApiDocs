<?
abstract class CAllFormCrm
{
	const LINK_AUTO = 'A';
	const LINK_MANUAL = 'M';

	private static $_ob;

	abstract public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array());

	public static function GetByID($ID)
	{
		return CFormCrm::GetList(array(), array('ID' => intval($ID)));
	}

	public static function GetByFormID($FORM_ID)
	{
		$query = "SELECT fcl.*
FROM b_form_crm_link fcl
LEFT JOIN b_form_crm fc ON fc.ID=fcl.CRM_ID
WHERE fcl.FORM_ID='".intval($FORM_ID)."' AND fc.ACTIVE='Y'";
		return $GLOBALS['DB']->Query($query);
	}

	public static function GetFields($LINK_ID)
	{
		$query = "SELECT FIELD_ID, FIELD_ALT, CRM_FIELD FROM b_form_crm_field WHERE LINK_ID='".intval($LINK_ID)."'";
		return $GLOBALS['DB']->Query($query);
	}

	private static function _addField($FORM_ID, $CRM_ID, $crm_field)
	{
		if (!self::$_ob)
			self::$_ob = new CFormCrmSender($CRM_ID);

		$arFields = self::$_ob->GetFields();
		foreach ($arFields as $arFld)
		{
			if ($arFld['ID'] == $crm_field)
			{
				$arAnswer = array();

				switch ($arFld['TYPE'])
				{
					case 'enum':
						if (is_array($arFld['VALUES']))
						{
							foreach ($arFld['VALUES'] as $arValue)
							{
								$arAnswer[] = array(
									'MESSAGE' => $arValue['NAME'],
									'VALUE' => $arValue['ID'],
									'FIELD_TYPE' => 'dropdown',
									'ACTIVE' => 'Y',
								);
							}
						}

					break;
					case 'boolean':
						$arAnswer[] = array(
							'MESSAGE' => ' ',
							'FIELD_TYPE' => 'checkbox',
							'ACTIVE' => 'Y',
						);
					break;
					default:
						$arAnswer[] = array(
							'MESSAGE' => ' ',
							'FIELD_TYPE' => 'text',
							'ACTIVE' => 'Y',
						);
				}

				return CFormField::Set(array(
					'SID' => $crm_field,
					'FORM_ID' => $FORM_ID,
					'ACTIVE' => 'Y',
					'TITLE' => $arFld['NAME'],
					'REQUIRED' => $arFld['REQUIRED'] == 'true' ? 'Y' : 'N',
					'arANSWER' => $arAnswer
				));
			}
		}
	}

	public static function SetForm($FORM_ID, $arParams)
	{
		global $DB;

		$FORM_ID = intval($FORM_ID);
		if ($FORM_ID > 0)
		{
			$dbRes = self::GetByFormID($FORM_ID);
			$arLink = $dbRes->Fetch();

			if (is_array($arLink))
			{
				$DB->Query("DELETE FROM b_form_crm_field WHERE LINK_ID='".intval($arLink['ID'])."'");
			}

			$arParams['CRM_ID'] = intval($arParams['CRM_ID']);
			if ($arParams['CRM_ID'] <= 0)
			{
				if (is_array($arLink))
				{
					$DB->Query("DELETE FROM b_form_crm_link WHERE ID='".intval($arLink['ID'])."'");
				}
			}
			else
			{
				$arLinkFields = array(
					'FORM_ID' => $FORM_ID,
					'CRM_ID' => $arParams['CRM_ID'],
					'LINK_TYPE' => $arParams['LINK_TYPE'] == self::LINK_MANUAL ? self::LINK_MANUAL : self::LINK_AUTO,
				);

				if (is_array($arLink))
				{
					$strUpdate = $DB->PrepareUpdate('b_form_crm_link', $arLinkFields);
					$query = "UPDATE b_form_crm_link SET ".$strUpdate." WHERE ID='".intval($arLink['ID'])."'";
					$dbRes = $DB->Query($query);
					if ($dbRes)
						$arLinkFields['ID'] = $arLink['ID'];
				}
				else
				{
					$arLinkFields['ID'] = $DB->Add('b_form_crm_link', $arLinkFields);
				}

				if ($arLinkFields['ID'] > 0)
				{
					if (is_array($arParams['CRM_FIELDS']) && is_array($arParams['FORM_FIELDS']))
					{
						$arMap = array();
						foreach ($arParams['CRM_FIELDS'] as $key => $crm_field)
						{
							$form_field = $arParams['FORM_FIELDS'][$key];

							if (strlen($crm_field) > 0 && strlen($form_field) > 0 && !array_key_exists($crm_field, $arMap))
							{
								$arMap[$crm_field] = true;

								$arFields = array(
									'LINK_ID' => $arLinkFields['ID'],
									'CRM_FIELD' => $crm_field
								);
								if (intval($form_field) > 0)
									$arFields['FIELD_ID'] = $form_field;
								elseif ($form_field == 'NEW')
									$arFields['FIELD_ID'] = self::_addField($FORM_ID, $arParams['CRM_ID'], $crm_field);
								else
									$arFields['FIELD_ALT'] = $form_field;

								$DB->Add('b_form_crm_field', $arFields);
							}
						}
					}
				}
			}
		}
	}

	public static function onResultAdded($FORM_ID, $RESULT_ID)
	{
		$dbRes = CFormCrm::GetByFormID($FORM_ID);
		$arLink = $dbRes->Fetch();
		if (is_array($arLink) && $arLink['LINK_TYPE'] == self::LINK_AUTO)
		{
			self::AddLead($FORM_ID, $RESULT_ID, $arLink);
		}
	}

	public static function AddLead($FORM_ID, $RESULT_ID, $arLink = null)
	{
		$FORM_ID = intval($FORM_ID);
		$RESULT_ID = intval($RESULT_ID);

		if ($FORM_ID <= 0 || $RESULT_ID <= 0)
			return false;

		if (!is_array($arLink))
		{
			$dbRes = CFormCrm::GetByFormID($FORM_ID);
			$arLink = $dbRes->Fetch();
		}

		if (!$arLink)
			return false;

		$arResultFields = array();
		$arAnswers = array();

		CFormResult::GetDataByID($RESULT_ID, array(), $arResultFields, $arAnswers);

		$ob = new CFormCrmSender($arLink['CRM_ID']);
		$arCrmF = $ob->GetFields();
		$arCrmFields = array();
		foreach ($arCrmF as $ar)
			$arCrmFields[$ar['ID']] = $ar;

		$arLeadFields = array();
		$dbRes = CFormCrm::GetFields($arLink['ID']);
		while ($arRes = $dbRes->Fetch())
		{
			if (intval($arRes['FIELD_ID']) > 0)
			{
				$bFound = false;
				foreach ($arAnswers as $sid => $arAnswer)
				{
					foreach ($arAnswer as $answer_id => $arAns)
					{
						if ($arAns['FIELD_ID'] == $arRes['FIELD_ID'])
						{
							$bFound = true;
							if ($arCrmFields[$arRes['CRM_FIELD']])
							{
								$value = '';
								switch ($arCrmFields[$arRes['CRM_FIELD']]['TYPE'])
								{
									case 'enum':
										$value = $arAns['ANSWER_TEXT'];
										break;
									case 'boolean':
										$value = 'Y';
										break;
									default:
										$value = (strlen($arAns['USER_TEXT']) > 0
											? $arAns['USER_TEXT']
											: (
											strlen($arAns['ANSWER_TEXT']) > 0
												? $arAns['ANSWER_TEXT']
												: $arAns['VALUE']
											)
										);
										break;
								}

								if($arCrmFields[$arRes['CRM_FIELD']]['MULTIPLE'] === "true")
								{
									$arLeadFields[$arRes['CRM_FIELD']] .=
										(empty($arLeadFields[$arRes['CRM_FIELD']]) ? '' : ',').$value;
								}
								else
								{
									$arLeadFields[$arRes['CRM_FIELD']] = $value;
								}
							}
						}
					}
				}

				if (!$bFound && $arCrmFields[$arRes['CRM_FIELD']] && $arCrmFields[$arRes['CRM_FIELD']]['TYPE'] == 'boolean')
				{
					$arLeadFields[$arRes['CRM_FIELD']] = 'N';
				}
			}
			elseif (strlen($arRes['FIELD_ALT']) > 0)
			{
				switch($arRes['FIELD_ALT'])
				{
					case 'RESULT_ID':
						$arLeadFields[$arRes['CRM_FIELD']] = $arResultFields['ID'];
					break;
					case 'FORM_SID':
						$arLeadFields[$arRes['CRM_FIELD']] = $arResultFields['SID'];
					break;
					case 'FORM_NAME':
						$arLeadFields[$arRes['CRM_FIELD']] = $arResultFields['NAME'];
					break;
					case 'SITE_ID':
						$arLeadFields[$arRes['CRM_FIELD']] = SITE_ID;
					break;
					case 'FORM_ALL':
						$arLeadFields[$arRes['CRM_FIELD']] = self::_getAllFormFields($FORM_ID, $RESULT_ID, $arAnswers);
					break;
					case 'FORM_ALL_HTML':
						$arLeadFields[$arRes['CRM_FIELD']] = self::_getAllFormFieldsHTML($FORM_ID, $RESULT_ID, $arAnswers);
					break;
				}
			}
		}

		$result = $ob->AddLead($arLeadFields);
		if ($result->code() != 201)
		{
			$GLOBALS['APPLICATION']->ThrowException($result->error(), $result->code());
			return false;
		}
		else
		{
			CFormResult::SetCRMFlag($RESULT_ID, 'Y');
			return $result->field('ID');
		}
	}

	public static function Add($arFields)
	{
		global $DB;

		$e = GetModuleEvents('form', 'OnBeforeFormCrmAdd');
		while ($a = $e->Fetch())
		{
			if (false === ExecuteModuleEventEx($a, array(&$arFields)))
				return false;
		}

		if (!self::CheckFields('ADD', $arFields))
			return false;

		$ID = $DB->Add('b_form_crm', $arFields);
		if ($ID > 0)
		{
			$arFields['ID'] = $ID;
/*
			if (isset($arFields['USERS']))
			{
				self::SetUsers($ID, $arFields['USERS'], false);
			}

			if (isset($arFields['FILES']))
			{
				self::SetFiles($ID, $arFields['FILES']);
			}
*/
			$e = GetModuleEvents('form', 'OnAfterFormCrmAdd');
			while ($a = $e->Fetch())
				ExecuteModuleEventEx($a, array($arFields));
		}

		return $ID;
	}

	public static function Update($ID, $arFields)
	{
		global $DB;

		if ($ID <= 0)
			return false;

		$arFields['ID'] = $ID;

		$e = GetModuleEvents('form', 'OnBeforeFormCrmUpdate');
		while ($a = $e->Fetch())
		{
			if (false === ExecuteModuleEventEx($a, array(&$arFields)))
			{
				return false;
			}
		}

		if (!self::CheckFields('UPDATE', $arFields))
			return false;

		$strUpdate = $DB->PrepareUpdate('b_form_crm', $arFields);
		$query = 'UPDATE b_form_crm SET '.$strUpdate.' WHERE ID=\''.intval($ID).'\'';

		$dbRes = $DB->Query($query);
		if ($dbRes)
		{
/*
			if (isset($arFields['USERS']))
			{
				self::SetUsers($ID, $arFields['USERS']);
			}

			if (isset($arFields['FILES']))
			{
				self::SetFiles($ID, $arFields['FILES']);
			}
*/
			$e = GetModuleEvents('form', 'OnAfterFormCrmUpdate');
			while ($a = $e->Fetch())
				ExecuteModuleEventEx($a, array($ID, $arFields));

			return $ID;
		}

		return false;
	}

	public static function Delete($ID)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID < 1)
			return false;

		$dbRes = CFormCrm::GetByID($ID);
		$arCrm = $dbRes->Fetch();
		if (is_array($arCrm))
		{
			$rsEvents = GetModuleEvents("form", "OnBeforeFormCrmDelete");
			while ($arEvent = $rsEvents->Fetch())
			{
				if (false === ExecuteModuleEventEx($arEvent, array($ID, $arCrm)))
				{
					return false;
				}
			}

			if ($DB->Query("DELETE FROM b_form_crm WHERE ID='".$ID."'"))
			{
				$rsEvents = GetModuleEvents("form", "OnAfterFormCrmDelete");
				while ($arEvent = $rsEvents->Fetch())
					ExecuteModuleEventEx($arEvent, array($ID));

				return true;
			}
		}

		return false;
	}

	protected static function _getAllFormFieldsHTML($WEB_FORM_ID, $RESULT_ID, $arAnswers)
	{
		global $APPLICATION;

		$strResult = "";

		$w = CFormField::GetList($WEB_FORM_ID, "ALL", $by, $order, array("ACTIVE" => "Y"), $is_filtered);
		while ($wr=$w->Fetch())
		{
			$answer = "";
			$answer_raw = '';
			if (is_array($arAnswers[$wr["SID"]]))
			{
				$bHasDiffTypes = false;
				$lastType = '';
				foreach ($arAnswers[$wr['SID']] as $arrA)
				{
					if ($lastType == '') $lastType = $arrA['FIELD_TYPE'];
					elseif ($arrA['FIELD_TYPE'] != $lastType)
					{
						$bHasDiffTypes = true;
						break;
					}
				}

				foreach($arAnswers[$wr["SID"]] as $arrA)
				{
					if ($wr['ADDITIONAL'] == 'Y')
					{
						$arrA['FIELD_TYPE'] = $wr['FIELD_TYPE'];
					}

					$USER_TEXT_EXIST = (strlen(trim($arrA["USER_TEXT"]))>0);
					$ANSWER_TEXT_EXIST = (strlen(trim($arrA["ANSWER_TEXT"]))>0);
					$ANSWER_VALUE_EXIST = (strlen(trim($arrA["ANSWER_VALUE"]))>0);
					$USER_FILE_EXIST = (intval($arrA["USER_FILE_ID"])>0);

					if (
						$bHasDiffTypes
						&&
						!$USER_TEXT_EXIST
						&&
						(
							$arrA['FIELD_TYPE'] == 'text'
							||
							$arrA['FIELD_TYPE'] == 'textarea'
						)
					)
						continue;

					if (strlen(trim($answer))>0) $answer .= "<br />";
					if (strlen(trim($answer_raw))>0) $answer_raw .= ",";

					if ($ANSWER_TEXT_EXIST)
						$answer .= $arrA["ANSWER_TEXT"].': ';

					switch ($arrA['FIELD_TYPE'])
					{
						case 'text':
						case 'textarea':
						case 'hidden':
						case 'date':
						case 'password':

							if ($USER_TEXT_EXIST)
							{
								$answer .= htmlspecialcharsbx(trim($arrA["USER_TEXT"]));
								$answer_raw .= htmlspecialcharsbx(trim($arrA["USER_TEXT"]));
							}

						break;

						case 'email':
						case 'url':

							if ($USER_TEXT_EXIST)
							{
								$answer .= '<a href="'.($arrA['FIELD_TYPE'] == 'email' ? 'mailto:' : '').trim($arrA["USER_TEXT"]).'">'.htmlspecialcharsbx(trim($arrA["USER_TEXT"])).'</a>';
								$answer_raw .= htmlspecialcharsbx(trim($arrA["USER_TEXT"]));
							}

						break;

						case 'checkbox':
						case 'multiselect':
						case 'radio':
						case 'dropdown':

							if ($ANSWER_TEXT_EXIST)
							{
								$answer = htmlspecialcharsbx(substr($answer, 0, -2).' ');
								$answer_raw .= htmlspecialcharsbx($arrA['ANSWER_TEXT']);
							}

							if ($ANSWER_VALUE_EXIST)
							{
								$answer .= '('.htmlspecialcharsbx($arrA['ANSWER_VALUE']).') ';
								if (!$ANSWER_TEXT_EXIST)
									$answer_raw .= htmlspecialcharsbx($arrA['ANSWER_VALUE']);
							}

							if (!$ANSWER_VALUE_EXIST && !$ANSWER_TEXT_EXIST)
								$answer_raw .= $arrA['ANSWER_ID'];

							$answer .= '['.$arrA['ANSWER_ID'].']';

						break;

						case 'file':
						case 'image':

							if ($USER_FILE_EXIST)
							{
								$f = CFile::GetByID($arrA["USER_FILE_ID"]);
								if ($fr = $f->Fetch())
								{
									$file_size = CFile::FormatSize($fr["FILE_SIZE"]);
									$url = ($APPLICATION->IsHTTPS() ? "https://" : "http://").$_SERVER["HTTP_HOST"]. "/bitrix/tools/form_show_file.php?rid=".$RESULT_ID. "&hash=".$arrA["USER_FILE_HASH"]."&lang=".LANGUAGE_ID;

									if ($arrA["USER_FILE_IS_IMAGE"]=="Y")
									{
										$answer .= "<a href=\"$url\">".htmlspecialcharsbx($arrA["USER_FILE_NAME"])."</a> [".$fr["WIDTH"]." x ".$fr["HEIGHT"]."] (".$file_size.")";
									}
									else
									{
										$answer .= "<a href=\"$url&action=download\">".htmlspecialcharsbx($arrA["USER_FILE_NAME"])."</a> (".$file_size.")";
									}

									$answer_raw .= htmlspecialcharsbx($arrA['USER_FILE_NAME']);
								}
							}

						break;
					}
				}
			}

			$strResult .= $wr["TITLE"].":<br />".(strlen($answer)<=0 ? " " : $answer)."<br /><br />";
		}

		return $strResult;
	}

	protected static function _getAllFormFields($WEB_FORM_ID, $RESULT_ID, $arAnswers)
	{
		global $APPLICATION;

		$strResult = "";

		$w = CFormField::GetList($WEB_FORM_ID, "ALL", $by, $order, array("ACTIVE" => "Y"), $is_filtered);
		while ($wr=$w->Fetch())
		{
			$answer = "";
			$answer_raw = '';
			if (is_array($arAnswers[$wr["SID"]]))
			{
				$bHasDiffTypes = false;
				$lastType = '';
				foreach ($arAnswers[$wr['SID']] as $arrA)
				{
					if ($lastType == '') $lastType = $arrA['FIELD_TYPE'];
					elseif ($arrA['FIELD_TYPE'] != $lastType)
					{
						$bHasDiffTypes = true;
						break;
					}
				}

				foreach($arAnswers[$wr["SID"]] as $arrA)
				{
					if ($wr['ADDITIONAL'] == 'Y')
					{
						$arrA['FIELD_TYPE'] = $wr['FIELD_TYPE'];
					}

					$USER_TEXT_EXIST = (strlen(trim($arrA["USER_TEXT"]))>0);
					$ANSWER_TEXT_EXIST = (strlen(trim($arrA["ANSWER_TEXT"]))>0);
					$ANSWER_VALUE_EXIST = (strlen(trim($arrA["ANSWER_VALUE"]))>0);
					$USER_FILE_EXIST = (intval($arrA["USER_FILE_ID"])>0);

					if (
						$bHasDiffTypes
						&& !$USER_TEXT_EXIST
						&& (
							$arrA['FIELD_TYPE'] == 'text'
							||
							$arrA['FIELD_TYPE'] == 'textarea'
						)
					)
					{
						continue;
					}

					if (strlen(trim($answer)) > 0)
						$answer .= "\n";
					if (strlen(trim($answer_raw)) > 0)
						$answer_raw .= ",";

					if ($ANSWER_TEXT_EXIST)
						$answer .= $arrA["ANSWER_TEXT"].': ';

					switch ($arrA['FIELD_TYPE'])
					{
						case 'text':
						case 'textarea':
						case 'email':
						case 'url':
						case 'hidden':
						case 'date':
						case 'password':

							if ($USER_TEXT_EXIST)
							{
								$answer .= trim($arrA["USER_TEXT"]);
								$answer_raw .= trim($arrA["USER_TEXT"]);
							}

						break;

						case 'checkbox':
						case 'multiselect':
						case 'radio':
						case 'dropdown':

							if ($ANSWER_TEXT_EXIST)
							{
								$answer = substr($answer, 0, -2).' ';
								$answer_raw .= $arrA['ANSWER_TEXT'];
							}

							if ($ANSWER_VALUE_EXIST)
							{
								$answer .= '('.$arrA['ANSWER_VALUE'].') ';
								if (!$ANSWER_TEXT_EXIST)
								{
									$answer_raw .= $arrA['ANSWER_VALUE'];
								}
							}

							if (!$ANSWER_VALUE_EXIST && !$ANSWER_TEXT_EXIST)
							{
								$answer_raw .= $arrA['ANSWER_ID'];
							}

							$answer .= '['.$arrA['ANSWER_ID'].']';

						break;

						case 'file':
						case 'image':

							if ($USER_FILE_EXIST)
							{
								$f = CFile::GetByID($arrA["USER_FILE_ID"]);
								if ($fr = $f->Fetch())
								{
									$file_size = CFile::FormatSize($fr["FILE_SIZE"]);
									$url = ($APPLICATION->IsHTTPS() ? "https://" : "http://").$_SERVER["HTTP_HOST"]. "/bitrix/tools/form_show_file.php?rid=".$RESULT_ID. "&hash=".$arrA["USER_FILE_HASH"]."&action=download&lang=".LANGUAGE_ID;

									if ($arrA["USER_FILE_IS_IMAGE"]=="Y")
									{
										$answer .= $arrA["USER_FILE_NAME"]." [".$fr["WIDTH"]." x ".$fr["HEIGHT"]."] (".$file_size.")\n".$url;
									}
									else
									{
										$answer .= $arrA["USER_FILE_NAME"]." (".$file_size.")\n".$url."&action=download";
									}
								}

								$answer_raw .= $arrA['USER_FILE_NAME'];
							}

						break;
					}
				}
			}

			$strResult .= $wr["TITLE"].":\r\n".(strlen($answer)<=0 ? " " : $answer)."\r\n\r\n";
		}

		return $strResult;
	}



	protected static function CheckFields($action, &$arFields)
	{
		if (isset($arFields['ID']))
			unset($arFields['ID']);

		if ($action == 'ADD' || isset($arFields['NAME']))
			$arFields['NAME'] = trim($arFields['NAME']);
		if ($action == 'ADD' || isset($arFields['URL']))
			$arFields['URL'] = trim($arFields['URL']);
		if ($action == 'ADD' || isset($arFields['AUTH_HASH']))
			$arFields['AUTH_HASH'] = trim($arFields['AUTH_HASH']);

		return true;
	}

	protected static function GetFilterOperation($key)
	{
		$strNegative = "N";
		if (substr($key, 0, 1)=="!")
		{
			$key = substr($key, 1);
			$strNegative = "Y";
		}

		$strOrNull = "N";
		if (substr($key, 0, 1)=="+")
		{
			$key = substr($key, 1);
			$strOrNull = "Y";
		}

		if (substr($key, 0, 2)==">=")
		{
			$key = substr($key, 2);
			$strOperation = ">=";
		}
		elseif (substr($key, 0, 1)==">")
		{
			$key = substr($key, 1);
			$strOperation = ">";
		}
		elseif (substr($key, 0, 2)=="<=")
		{
			$key = substr($key, 2);
			$strOperation = "<=";
		}
		elseif (substr($key, 0, 1)=="<")
		{
			$key = substr($key, 1);
			$strOperation = "<";
		}
		elseif (substr($key, 0, 1)=="@")
		{
			$key = substr($key, 1);
			$strOperation = "IN";
		}
		elseif (substr($key, 0, 1)=="~")
		{
			$key = substr($key, 1);
			$strOperation = "LIKE";
		}
		elseif (substr($key, 0, 1)=="%")
		{
			$key = substr($key, 1);
			$strOperation = "QUERY";
		}
		else
		{
			$strOperation = "=";
		}

		return array("FIELD" => $key, "NEGATIVE" => $strNegative, "OPERATION" => $strOperation, "OR_NULL" => $strOrNull);
	}

	protected static function PrepareSql(&$arFields, $arOrder, &$arFilter, $arGroupBy, $arSelectFields)
	{
		global $DB;

		$strSqlSelect = "";
		$strSqlFrom = "";
		$strSqlWhere = "";
		$strSqlGroupBy = "";
		$strSqlOrderBy = "";

		$arGroupByFunct = array("COUNT", "AVG", "MIN", "MAX", "SUM");

		$arAlreadyJoined = array();

		// GROUP BY -->
		if (is_array($arGroupBy) && count($arGroupBy)>0)
		{
			$arSelectFields = $arGroupBy;
			foreach ($arGroupBy as $key => $val)
			{
				$val = strtoupper($val);
				$key = strtoupper($key);
				if (array_key_exists($val, $arFields) && !in_array($key, $arGroupByFunct))
				{
					if (strlen($strSqlGroupBy) > 0)
						$strSqlGroupBy .= ", ";
					$strSqlGroupBy .= $arFields[$val]["FIELD"];

					if (isset($arFields[$val]["FROM"])
						&& strlen($arFields[$val]["FROM"]) > 0
						&& !in_array($arFields[$val]["FROM"], $arAlreadyJoined))
					{
						if (strlen($strSqlFrom) > 0)
							$strSqlFrom .= " ";
						$strSqlFrom .= $arFields[$val]["FROM"];
						$arAlreadyJoined[] = $arFields[$val]["FROM"];
					}
				}
			}
		}
		// <-- GROUP BY

		// SELECT -->
		$arFieldsKeys = array_keys($arFields);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSqlSelect = "COUNT(%%_DISTINCT_%% ".$arFields[$arFieldsKeys[0]]["FIELD"].") as CNT ";
		}
		else
		{
			if (isset($arSelectFields) && !is_array($arSelectFields) && is_string($arSelectFields) && strlen($arSelectFields)>0 && array_key_exists($arSelectFields, $arFields))
				$arSelectFields = array($arSelectFields);

			if (!isset($arSelectFields)
				|| !is_array($arSelectFields)
				|| count($arSelectFields)<=0
				|| in_array("*", $arSelectFields))
			{
				$cntFieldsKeys = count($arFieldsKeys);
				for ($i = 0; $i < $cntFieldsKeys; $i++)
				{
					if (isset($arFields[$arFieldsKeys[$i]]["WHERE_ONLY"])
						&& $arFields[$arFieldsKeys[$i]]["WHERE_ONLY"] == "Y")
					{
						continue;
					}

					if (strlen($strSqlSelect) > 0)
						$strSqlSelect .= ", ";

					if ($arFields[$arFieldsKeys[$i]]["TYPE"] == "datetime")
					{
						if ((strtoupper($DB->type)=="ORACLE" || strtoupper($DB->type)=="MSSQL") && (array_key_exists($arFieldsKeys[$i], $arOrder)))
							$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"]." as ".$arFieldsKeys[$i]."_X1, ";

						$strSqlSelect .= $DB->DateToCharFunction($arFields[$arFieldsKeys[$i]]["FIELD"], "FULL")." as ".$arFieldsKeys[$i];
					}
					elseif ($arFields[$arFieldsKeys[$i]]["TYPE"] == "date")
					{
						if ((strtoupper($DB->type)=="ORACLE" || strtoupper($DB->type)=="MSSQL") && (array_key_exists($arFieldsKeys[$i], $arOrder)))
							$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"]." as ".$arFieldsKeys[$i]."_X1, ";

						$strSqlSelect .= $DB->DateToCharFunction($arFields[$arFieldsKeys[$i]]["FIELD"], "SHORT")." as ".$arFieldsKeys[$i];
					}
					else
						$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"]." as ".$arFieldsKeys[$i];

					if (isset($arFields[$arFieldsKeys[$i]]["FROM"])
						&& strlen($arFields[$arFieldsKeys[$i]]["FROM"]) > 0
						&& !in_array($arFields[$arFieldsKeys[$i]]["FROM"], $arAlreadyJoined))
					{
						if (strlen($strSqlFrom) > 0)
							$strSqlFrom .= " ";
						$strSqlFrom .= $arFields[$arFieldsKeys[$i]]["FROM"];
						$arAlreadyJoined[] = $arFields[$arFieldsKeys[$i]]["FROM"];
					}
				}
			}
			else
			{
				foreach ($arSelectFields as $key => $val)
				{
					$val = strtoupper($val);
					$key = strtoupper($key);
					if (array_key_exists($val, $arFields))
					{
						if (strlen($strSqlSelect) > 0)
							$strSqlSelect .= ", ";

						if (in_array($key, $arGroupByFunct))
						{
							$strSqlSelect .= $key."(".$arFields[$val]["FIELD"].") as ".$val;
						}
						else
						{
							if ($arFields[$val]["TYPE"] == "datetime")
							{
								if ((strtoupper($DB->type)=="ORACLE" || strtoupper($DB->type)=="MSSQL") && (array_key_exists($val, $arOrder)))
									$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val."_X1, ";

								$strSqlSelect .= $DB->DateToCharFunction($arFields[$val]["FIELD"], "FULL")." as ".$val;
							}
							elseif ($arFields[$val]["TYPE"] == "date")
							{
								if ((strtoupper($DB->type)=="ORACLE" || strtoupper($DB->type)=="MSSQL") && (array_key_exists($val, $arOrder)))
									$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val."_X1, ";

								$strSqlSelect .= $DB->DateToCharFunction($arFields[$val]["FIELD"], "SHORT")." as ".$val;
							}
							else
								$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val;
						}

						if (isset($arFields[$val]["FROM"])
							&& strlen($arFields[$val]["FROM"]) > 0
							&& !in_array($arFields[$val]["FROM"], $arAlreadyJoined))
						{
							if (strlen($strSqlFrom) > 0)
								$strSqlFrom .= " ";
							$strSqlFrom .= $arFields[$val]["FROM"];
							$arAlreadyJoined[] = $arFields[$val]["FROM"];
						}
					}
				}
			}

			if (strlen($strSqlGroupBy) > 0)
			{
				if (strlen($strSqlSelect) > 0)
					$strSqlSelect .= ", ";
				$strSqlSelect .= "COUNT(%%_DISTINCT_%% ".$arFields[$arFieldsKeys[0]]["FIELD"].") as CNT";
			}
			else
				$strSqlSelect = "%%_DISTINCT_%% ".$strSqlSelect;
		}
		// <-- SELECT

		// WHERE -->
		$arSqlSearch = array();

		if (!is_array($arFilter))
			$filter_keys = array();
		else
			$filter_keys = array_keys($arFilter);

		$cntFilterKeys = count($filter_keys);
		for ($i = 0; $i < $cntFilterKeys; $i++)
		{
			$vals = $arFilter[$filter_keys[$i]];
			if (!is_array($vals))
				$vals = array($vals);
			else
				$vals = array_values($vals);

			$key = $filter_keys[$i];
			$key_res = self::GetFilterOperation($key);
			$key = $key_res["FIELD"];
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];
			$strOrNull = $key_res["OR_NULL"];

			if (array_key_exists($key, $arFields))
			{
				$arSqlSearch_tmp = array();
				$cntVals = count($vals);
				for ($j = 0; $j < $cntVals; $j++)
				{
					$val = $vals[$j];
					if (isset($arFields[$key]["WHERE"]))
					{
						$arSqlSearch_tmp1 = call_user_func_array(
							$arFields[$key]["WHERE"],
							array($val, $key, $strOperation, $strNegative, $arFields[$key]["FIELD"], $arFields, $arFilter)
						);
						if ($arSqlSearch_tmp1 !== false)
							$arSqlSearch_tmp[] = $arSqlSearch_tmp1;
					}
					else
					{
						if ($arFields[$key]["TYPE"] == "int")
						{
							if ((IntVal($val) == 0) && (strpos($strOperation, "=") !== False))
								$arSqlSearch_tmp[] = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND" : "OR")." ".(($strNegative == "Y") ? "NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." 0)";
							else
								$arSqlSearch_tmp[] = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".IntVal($val)." )";
						}
						elseif ($arFields[$key]["TYPE"] == "double")
						{
							$val = str_replace(",", ".", $val);

							if ((DoubleVal($val) == 0) && (strpos($strOperation, "=") !== False))
								$arSqlSearch_tmp[] = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND" : "OR")." ".(($strNegative == "Y") ? "NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." 0)";
							else
								$arSqlSearch_tmp[] = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".DoubleVal($val)." )";
						}
						elseif ($arFields[$key]["TYPE"] == "string" || $arFields[$key]["TYPE"] == "char")
						{
							if ($strOperation == "QUERY")
							{
								$arSqlSearch_tmp[] = GetFilterQuery($arFields[$key]["FIELD"], $val, "Y");
							}
							else
							{
								if ((strlen($val) == 0) && (strpos($strOperation, "=") !== False))
									$arSqlSearch_tmp[] = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND NOT" : "OR")." (".$DB->Length($arFields[$key]["FIELD"])." <= 0) ".(($strNegative == "Y") ? "AND NOT" : "OR")." (".$arFields[$key]["FIELD"]." ".$strOperation." '".$DB->ForSql($val)."' )";
								else
									$arSqlSearch_tmp[] = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." '".$DB->ForSql($val)."' )";
							}
						}
						elseif ($arFields[$key]["TYPE"] == "datetime")
						{
							if (strlen($val) <= 0)
								$arSqlSearch_tmp[] = ($strNegative=="Y"?"NOT":"")."(".$arFields[$key]["FIELD"]." IS NULL)";
							else
								$arSqlSearch_tmp[] = ($strNegative=="Y"?" ".$arFields[$key]["FIELD"]." IS NULL OR NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "FULL").")";
						}
						elseif ($arFields[$key]["TYPE"] == "date")
						{
							if (strlen($val) <= 0)
								$arSqlSearch_tmp[] = ($strNegative=="Y"?"NOT":"")."(".$arFields[$key]["FIELD"]." IS NULL)";
							else
								$arSqlSearch_tmp[] = ($strNegative=="Y"?" ".$arFields[$key]["FIELD"]." IS NULL OR NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "SHORT").")";
						}
					}
				}

				if (isset($arFields[$key]["FROM"])
					&& strlen($arFields[$key]["FROM"]) > 0
					&& !in_array($arFields[$key]["FROM"], $arAlreadyJoined))
				{
					if (strlen($strSqlFrom) > 0)
						$strSqlFrom .= " ";
					$strSqlFrom .= $arFields[$key]["FROM"];
					$arAlreadyJoined[] = $arFields[$key]["FROM"];
				}

				$strSqlSearch_tmp = "";
				$cntSqlSearch_tmp = count($arSqlSearch_tmp);

				for ($j = 0; $j < $cntSqlSearch_tmp; $j++)
				{
					if ($j > 0)
						$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
					$strSqlSearch_tmp .= "(".$arSqlSearch_tmp[$j].")";
				}
				if ($strOrNull == "Y")
				{
					if (strlen($strSqlSearch_tmp) > 0)
						$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
					$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." IS ".($strNegative=="Y" ? "NOT " : "")."NULL)";

					if (strlen($strSqlSearch_tmp) > 0)
						$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
					if ($arFields[$key]["TYPE"] == "int" || $arFields[$key]["TYPE"] == "double")
						$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." ".($strNegative=="Y" ? "<>" : "=")." 0)";
					elseif ($arFields[$key]["TYPE"] == "string" || $arFields[$key]["TYPE"] == "char")
						$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." ".($strNegative=="Y" ? "<>" : "=")." '')";
					else
						$strSqlSearch_tmp .= ($strNegative=="Y" ? " (1=1) " : " (1=0) ");
				}

				if ($strSqlSearch_tmp != "")
					$arSqlSearch[] = "(".$strSqlSearch_tmp.")";
			}
		}

		$cntSqlSearch = count($arSqlSearch);
		for ($i = 0; $i < $cntSqlSearch; $i++)
		{
			if (strlen($strSqlWhere) > 0)
				$strSqlWhere .= " AND ";
			$strSqlWhere .= "(".$arSqlSearch[$i].")";
		}
		// <-- WHERE

		// ORDER BY -->
		$arSqlOrder = Array();
		foreach ($arOrder as $by => $order)
		{
			$by = strtoupper($by);
			$order = strtoupper($order);

			if ($order != "ASC")
				$order = "DESC";
			else
				$order = "ASC";

			if (array_key_exists($by, $arFields))
			{
				$arSqlOrder[] = " ".$arFields[$by]["FIELD"]." ".$order." ";

				if (isset($arFields[$by]["FROM"])
					&& strlen($arFields[$by]["FROM"]) > 0
					&& !in_array($arFields[$by]["FROM"], $arAlreadyJoined))
				{
					if (strlen($strSqlFrom) > 0)
						$strSqlFrom .= " ";
					$strSqlFrom .= $arFields[$by]["FROM"];
					$arAlreadyJoined[] = $arFields[$by]["FROM"];
				}
			}
		}

		DelDuplicateSort($arSqlOrder);
		$cntSqlOrder = count($arSqlOrder);
		for ($i=0; $i<$cntSqlOrder; $i++)
		{
			if (strlen($strSqlOrderBy) > 0)
				$strSqlOrderBy .= ", ";

			if(strtoupper($DB->type)=="ORACLE")
			{
				if(substr($arSqlOrder[$i], -3)=="ASC")
					$strSqlOrderBy .= $arSqlOrder[$i]." NULLS FIRST";
				else
					$strSqlOrderBy .= $arSqlOrder[$i]." NULLS LAST";
			}
			else
				$strSqlOrderBy .= $arSqlOrder[$i];
		}
		// <-- ORDER BY

		return array(
			"SELECT" => $strSqlSelect,
			"FROM" => $strSqlFrom,
			"WHERE" => $strSqlWhere,
			"GROUPBY" => $strSqlGroupBy,
			"ORDERBY" => $strSqlOrderBy
		);
	}
}


class CFormCrmSender
{
	const FIELDS_CACHE_TTL = 2592000;

	private $ID;
	private $arLink;
	private $arCRMFields;

	private $arAuth;
	private $authHash;

	private $lastResult = null;

	public function __construct($ID, $arAuth = null)
	{
		$this->ID = intval($ID);
		if ($this->ID > 0)
		{
			$dbRes = CFormCrm::GetByID($this->ID);
			$this->arLink = $dbRes->Fetch();
		}

		if (is_array($arAuth))
		{
			$this->arAuth = array('LOGIN' => $arAuth['LOGIN'], 'PASSWORD' => $arAuth['PASSWORD']);
		}
	}

	public function AddLead($arLeadFields)
	{
		return $this->_query('lead.add', $arLeadFields);
	}

	public function GetFields($bReload = false)
	{
		global $CACHE_MANAGER;

		if (!$this->arLink)
			return false;

		if ($bReload)
		{
			$CACHE_MANAGER->Clean($this->_cacheId(), 'form_crm_data');
			$this->arCRMFields = null;
		}

		if (!$this->arCRMFields)
		{
			if ($CACHE_MANAGER->Read(self::FIELDS_CACHE_TTL, $this->_cacheId(), 'form_crm_data') && !$bReload)
			{
				$this->arCRMFields = $CACHE_MANAGER->Get($this->_cacheId());
			}
			else
			{
				$result = $this->_query('lead.get_fields');
				if ($result !== false)
				{
					$data = $result->data();
					$this->arCRMFields = $data['FIELDS'];

					$CACHE_MANAGER->Set($this->_cacheId(), $this->arCRMFields);
				}
				else
				{
					return false;
				}
			}
		}

		return $this->arCRMFields;
	}

	public function GetAuthHash()
	{
		return $this->authHash;
	}

	public function GetLastResult()
	{
		return $this->lastResult;
	}

	private function _setAuthHash($hash)
	{
		if (strlen($hash) > 0)
		{
			$this->authHash = $hash;
			CFormCrm::Update($this->ID, array('AUTH_HASH' => $hash));
		}
	}

	private function _cacheId()
	{
		if ($this->CACHE_ID)
			return $this->CACHE_ID;
		else
			return ($this->CACHE_ID = 'FORM_CRM_'.$this->ID);
	}

	private function _query($method, $params = array())
	{
		global $APPLICATION;

		if ($this->arLink)
		{
			if (!$method)
				$method = 'lead.add';

			$arPostFields = array(
				'method' => $method
			);

			if ($this->arAuth)
			{
				$arPostFields['LOGIN'] = $this->arAuth['LOGIN'];
				$arPostFields['PASSWORD'] = $this->arAuth['PASSWORD'];
			}
			else
			{
				$arPostFields['AUTH'] = $this->arLink['AUTH_HASH'];
			}

			$arPostFields = array_merge($params, $arPostFields);
			$arPostFields = $APPLICATION->ConvertCharsetArray($arPostFields, LANG_CHARSET, 'UTF-8');

			$obHTTP = new CHTTP();
			$result_text = $obHTTP->Post($this->arLink['URL'], $arPostFields);

			$version_header = $obHTTP->headers['X-CRM-Version'];
			if (strlen($version_header) <= 0 || version_compare($version_header, "11.5.0") < 0)
			{
				$result_text = '{"error":"500","error_message":"'.GetMessage('FORM_CRM_VERSION_FAILURE').'"}';
			}
			else
			{
				$result_text = $APPLICATION->ConvertCharset($result_text, 'UTF-8', LANG_CHARSET);
			}

			$this->lastResult = new _CFormCrmSenderResult($result_text);

			if ($this->lastResult->field('AUTH'))
			{
				$this->_setAuthHash($this->lastResult->field('AUTH'));
			}

			return $this->lastResult;
		}

		return false;
	}
}

class _CFormCrmSenderResult
{
	private $bProcess = false;

	private $result_text;
	private $result;
	private $result_code;
	private $result_error;

	public function __construct($result_text)
	{
		$this->result_text = trim($result_text);
	}

	public function code()
	{
		$this->_process();
		return $this->result_code;
	}

	public function data()
	{
		$this->_process();
		return $this->result;
	}

	public function error()
	{
		$this->_process();
		return $this->result_error;
	}

	public function field($field)
	{
		$this->_process();
		return $this->result[$field];
	}

	private function _process()
	{
		if (!$this->bProcess)
		{
			if (strlen($this->result_text) > 0)
			{
				$this->result = CUtil::JsObjectToPhp($this->result_text);

				if (!is_array($this->result))
				{
					$this->result = null;
				}
				else
				{
					$this->result_code = intval($this->result['error']);
					if ($this->result_code >= 400)
					{
						$this->result_error = $this->result['error_message'];
					}
				}
			}

			$this->bProcess = true;
		}
	}
}
?>