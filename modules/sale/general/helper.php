<?
IncludeModuleLangFile(__FILE__);

class CSaleHelper
{
	public static function IsAssociativeArray($ar)
	{
		if (count($ar) <= 0)
			return false;

		$fl = false;

		$arKeys = array_keys($ar);
		$ind = -1;
		foreach ($arKeys as $key)
		{
			$ind++;
			if ($key."!" !== $ind."!" && "".$key !== "n".$ind)
			{
				$fl = true;
				break;
			}
		}

		return $fl;
	}

	/**
	* Writes to /bitrix/modules/sale.log
	*
	* @param string $text message to write
	* @param array $arVars array (varname => value) to print out variables
	* @param string $code log record tag
	*/
	public static function WriteToLog($text, $arVars = array(), $code = "")
	{
		$filename = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale.log";

		if ($f = fopen($filename, "a"))
		{
			fwrite($f, date("Y-m-d H:i:s")." - ".$code." - ".$text."\n");

			if (is_array($arVars))
			{
				foreach ($arVars as $varName => $varData)
				{
					fwrite($f, $varName.": ");
					fwrite($f, print_r($varData, true));
					fwrite($f, "\n");
				}
			}

			fwrite($f, "\n");
			fclose($f);
		}
	}

	public static function getAdminHtml($fieldId, $arField, $fieldName, $formName)
	{
		$arField["VALUE"] = CSaleDeliveryHelper::getConfValue($arField);
		$resultHtml = '';
		$name = htmlspecialcharsbx($fieldName.(strlen($fieldId) > 0 ? '['.$fieldId.']' : ''));

		if(isset($arField['PRE_TEXT']))
			$resultHtml = $arField['PRE_TEXT'].' ';

		if(isset($arField['BLOCK_HIDEABLE']))
			$resultHtml .= '<a href="javascript:void(0);" style="border-bottom: 1px dashed; text-decoration: none;">';

		switch ($arField["TYPE"])
		{
			case "TEXT_RO":  //read only text

				$resultHtml .= htmlspecialcharsbx($arField["VALUE"]);

			break;

			case "CHECKBOX":

				$resultHtml .= '<input '.
									'type="checkbox" '.
									'name="'.$name.'" '.
									'value="Y" '.
									($arField["VALUE"] == "Y" ? "checked=\"checked\"" : "");

				if(isset($arField['HIDE_BY_NAMES']) && is_array($arField['HIDE_BY_NAMES']))
						$resultHtml .= 'onclick="hideFormElementsByNames(this, '.CUtil::PhpToJSObject($arField['HIDE_BY_NAMES']).');"';

				$resultHtml .= '/>';

				if(isset($arField['HIDE_BY_NAMES']) && is_array($arField['HIDE_BY_NAMES']))
				{
					$resultHtml .= '
					<script language="JavaScript">
						BX.ready(
							function(){
								var cbObj = document.forms["'.$formName.'"]["'.$name.'"];

								if(cbObj)
									hideFormElementsByNames(cbObj, '.CUtil::PhpToJSObject($arField['HIDE_BY_NAMES']).');
							}
						);
					</script>';
				}

			break;

			case "RADIO":

				foreach ($arField["VALUES"] as $value => $title)
				{
					$resultHtml .= '<input type="radio"
										id="hc_'.htmlspecialcharsbx($fieldId).'_'.htmlspecialcharsEx($value).'"'.
										'name="'.$name.'" '.
										'value="'.htmlspecialcharsEx($value).'"'.
										($value == $arField["VALUE"] ? " checked=\"checked\"" : "").' />'.
										'<label for="hc_'.htmlspecialcharsbx($fieldId).'_'.htmlspecialcharsEx($value).'">'.
										htmlspecialcharsEx($title).'</label><br />';
				}

			break;

			case "PASSWORD":

				$resultHtml .= '<input '.
									'type="password" '.
									'name="'.$name.'" '.
									'value="'.htmlspecialcharsbx($arField["VALUE"]).'" />';

			break;

			case "DROPDOWN":

				$resultHtml .= '<select name="'.$name.'" ';

				if(isset($arField['ONCHANGE']))
					$resultHtml .= ' onchange = "'.$arField['ONCHANGE'].'"';

				$resultHtml .='>';

				foreach ($arField["VALUES"] as $value => $title)
				{
					$resultHtml .= '<option '.
										'value="'.htmlspecialcharsEx($value).'"'.
										($value == $arField["VALUE"] ? " selected=\"selected\"" : "").'>'.
										htmlspecialcharsEx($title).
									'</option>';
				}

				$resultHtml .= '</select>';

			break;

			case "MULTISELECT":
				$resultHtml .= '<select name="'.$name.'" multiple="multiple">';

				foreach ($arField["VALUES"] as $value => $title)
					$resultHtml .= '<option '.
										'value="'.htmlspecialcharsEx($value).'"'.
										(in_array($value, $arField["VALUE"]) ? " selected=\"selected\"" : "").'>'.
										htmlspecialcharsEx($title).
									'</option>';

				$resultHtml .= '</select>';
			break;

			case "SECTION":
			case "TEXT_CENTERED":
			case "MULTI_CONTROL_STRING":

				$resultHtml .= htmlspecialcharsbx($arField["TITLE"]);

			break;

			case "CUSTOM":
				$resultHtml .=  $arField["VALUE"];
			break;

			default:
				$resultHtml .= '<input type="text"'.
									'name="'.$name.'" '.
									'value="'.htmlspecialcharsbx($arField["VALUE"]).'" '.
									(isset($arField["SIZE"]) ? 'size="'.$arField["SIZE"].'"' : '').
								'/>';
		}

		if(isset($arField['BLOCK_HIDEABLE']))
			$resultHtml .= '</a>';

		if(isset($arField['POST_TEXT'])):
			$resultHtml .= ' '.$arField['POST_TEXT'];
		endif;

		return $resultHtml;
	}

	public static function getAdminMultilineControl($arMultiControlQuery)
	{
		$resultHtml = '';

		if(is_array($arMultiControlQuery))
		{
			reset($arMultiControlQuery);
			$key = key($arMultiControlQuery);
			if(isset($arMultiControlQuery[$key]['ITEMS']) && isset($arMultiControlQuery[$key]['CONFIG']))
			{
				$multiHtml = implode(' ', $arMultiControlQuery[$key]['ITEMS']);
				$resultHtml = self::wrapAdminHtml($multiHtml, $arMultiControlQuery[$key]['CONFIG']);
			}
		}

		return $resultHtml;
	}

	public static function wrapAdminHtml($controlHtml, &$arConfig)
	{
		$wrapHtml = '';

		$tdStyle = isset($arConfig["TOP_LINE"]) && $arConfig["TOP_LINE"] == "Y" ? ' border-top: 1px solid #DDDDDD;' : '';

		switch ($arConfig["TYPE"])
		{
			case "SECTION":
				$wrapHtml .= '<tr class="heading"><td colspan="2">'.$controlHtml.'</td></tr>';
			break;

			case "TEXT_CENTERED":
				$wrapHtml .= '<tr';

				if(isset($arConfig["BLOCK_HIDEABLE"]))
					$wrapHtml .= ' onclick="BX.Sale.PaySystem.toggleNextSiblings(this,'.intval($arConfig["BLOCK_LENGTH"]).');" class="ps-admin-hide" ';

				$wrapHtml .= '><td style="text-align: center; font-weight: bold;'.$tdStyle.'" colspan="2">'.$controlHtml;

				if(isset($arConfig["BLOCK_DELETABLE"]))
					$wrapHtml .= '&nbsp;&nbsp;<a href="javascript:void(0);" onclick="BX.Sale.PaySystem.deleteObjectAndNextSiblings(this,'.intval($arConfig["BLOCK_LENGTH"]).',2);" style="border-bottom: 1px dashed; text-decoration: none;">'.GetMessage("SALE_HELPER_DELETE").'</a>';

				$wrapHtml .= '</td></tr>';
			break;

			default:
				$wrapHtml .=	'<tr>'.
									'<td style="'.$tdStyle.'" class="field-name"'.(($arConfig["TYPE"] == "MULTISELECT") ? ' valign="top"' : '').' width="40%" align="right">'.htmlspecialcharsbx($arConfig["TITLE"]).':</td>'.
									'<td style="'.$tdStyle.'" valign="top" width="60%">'.
										$controlHtml.
									'</td>'.
								'</tr>';
		}

		return $wrapHtml;
	}

	public static function getOptionOrImportValues($optName, $importFuncName = false, $arFuncParams = array(), $siteId = "")
	{
		$arResult = array();

		if(strlen(trim($optName)) >= 0)
		{
			$optValue = COption::GetOptionString('sale', $optName, '', $siteId);
			$arOptValue = unserialize($optValue);

			if(empty($arOptValue))
			{
				if($importFuncName !== false && is_callable($importFuncName))
				{
					$arResult = call_user_func_array($importFuncName, $arFuncParams);
					COption::SetOptionString('sale', $optName, serialize($arResult), false, $siteId);
				}
			}
			else
			{
				$arResult = $arOptValue;
			}
		}

		return $arResult;
	}

	private static function getShopLocationParams($siteId = false)
	{
		$loc_diff = COption::GetOptionString('sale', 'ADDRESS_different_set', 'N');

		if ($loc_diff == "Y" && ($siteId !== false || defined(SITE_ID)))
		{
			if($siteId === false)
				$siteId = SITE_ID;

			$locId = COption::GetOptionString('sale', 'location', '', $siteId);
			$locZip = COption::GetOptionString('sale', 'location_zip', '', $siteId);
		}
		else
		{
			$locId = COption::GetOptionString('sale', 'location', '');
			$locZip = COption::GetOptionString('sale', 'location_zip', '');

			if(strlen($locId) <= 0)
			{
				static $defSite = null;
				if (!isset($defSite))
					$defSite =  CSite::GetDefSite();

				if($defSite)
				{
					$locId = COption::GetOptionString('sale', 'location', '', $defSite);
					$locZip = COption::GetOptionString('sale', 'location_zip', '', $defSite);
				}
			}
		}

		if((string) $locId != '')
		{
			$location = self::getLocationByIdHitCached($locId);

			if(intval($location['ID']))
				$locId = $location['ID'];
		}

		return array(
			'ID' => $locId,
			'ZIP' => $locZip
		);
	}

	public static function getShopLocationId($siteId)
	{
		static $shopLocationId = array();

		if(!isset($shopLocationId[$siteId]))
		{
			$locParams = self::getShopLocationParams($siteId);

			if(isset($locParams['ID']) && strlen($locParams['ID']) > 0)
				$shopLocationId[$siteId] = $locParams['ID'];
		}

		return $shopLocationId[$siteId];
	}

	public static function getShopLocationZIP()
	{
		static $shopLocationZip = '';

		if(strlen($shopLocationZip) <= 0)
		{
			$locParams = self::getShopLocationParams();

			if(isset($locParams['ZIP']) && strlen($locParams['ZIP']) > 0)
				$shopLocationZip = strval($locParams['ZIP']);
		}

		return $shopLocationZip;
	}

	public static function getShopLocation($siteId = false)
	{
		static $shopLocation = array();

		if(empty($shopLocation))
		{
			$shopLocationId = self::getShopLocationId($siteId);

			if(intval($shopLocationId) > 0)
				$shopLocation = CSaleLocation::GetByID($shopLocationId);
		}

		return $shopLocation;
	}

	public static function getCsvObject($filePath)
	{
		$csvFile = new CCSVData();
		$csvFile->LoadFile($filePath);
		$csvFile->SetFieldsType("R");
		$csvFile->SetFirstHeader(false);
		$csvFile->SetDelimiter(",");

		return $csvFile;
	}

	/**
	* Returns HTML code to show file (image or download link)
	* Similar to CFile::ShowFile but shows name of the file in the download link
	*
	* @param int $fileId - file id
	* @param array $arSize - width and height for image thumbnail
	* @return string
	*/
	public static function getFileInfo($fileId, $arSize = array("WIDTH" => 90, "HEIGHT" => 90))
	{
		$resultHTML = "";
		$arFile = CFile::GetFileArray($fileId);
		if ($arFile)
		{
			$is_image = CFile::IsImage($arFile["FILE_NAME"], $arFile["CONTENT_TYPE"]);
			if ($is_image)
				$resultHTML = CFile::ShowImage($arFile["ID"], $arSize["WIDTH"], $arSize["HEIGHT"], "border=0", $arFile["SRC"], true);
			else
				$resultHTML = '<a href="'.$arFile["SRC"].'">'.$arFile["ORIGINAL_NAME"].'</a>';
		}
		return $resultHTML;
	}

	public static function getIblockPropInfo($value, $propData, $arSize = array("WIDTH" => 90, "HEIGHT" => 90))
	{
		$res = "";

		if ($propData["MULTIPLE"] == "Y")
		{
			$arVal = array();
			if (!is_array($value))
			{
				if (strpos($value, ",") !== false)
					$arVal = explode(",", $value);
				else
					$arVal[] = $value;
			}
			else
				$arVal = $value;

			if (count($arVal) > 0)
			{
				foreach ($arVal as $key => $val)
				{
					if ($propData["PROPERTY_TYPE"] == "F")
					{
						if (strlen($res) > 0)
							$res .= "<br/> ".CSaleHelper::getFileInfo(trim($val), $arSize);
						else
							$res = CSaleHelper::getFileInfo(trim($val), $arSize);
					}
					else
					{
						if (strlen($res) > 0)
							$res .= ", ".$val;
						else
							$res = $val;
					}
				}
			}
		}
		else
		{
			if ($propData["PROPERTY_TYPE"] == "F")
				$res = CSaleHelper::getFileInfo($value, $arSize);
			else
				$res = $value;
		}

		return $res;
	}

	public static function getLocationByIdHitCached($id)
	{
		static $result = array();

		if(!isset($result[$id]))
			$result[$id] = CSaleLocation::GetByIDForLegacyDelivery($id);

		return $result[$id];
	}
}
