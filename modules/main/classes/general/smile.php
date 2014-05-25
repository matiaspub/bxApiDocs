<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

IncludeModuleLangFile(__FILE__);

class CSmile
{
	const TYPE_ALL = '';
	const TYPE_SMILE = 'S';
	const TYPE_ICON = 'I';
	const PATH_TO_SMILE = "/bitrix/images/main/smiles/";
	const PATH_TO_ICON = "/bitrix/images/main/icons/";
	const CHECK_TYPE_ADD = 1;
	const CHECK_TYPE_UPDATE = 2;
	const GET_ALL_LANGUAGE = false;

	private static function checkFields(&$arFields, $actionType = self::CHECK_TYPE_ADD)
	{
		global $APPLICATION;

		$aMsg = array();

		if(isset($arFields['TYPE']) && (!in_array($arFields['TYPE'], array(self::TYPE_SMILE, self::TYPE_ICON))))
			$aMsg[] = array("id"=>"TYPE", "text"=> GetMessage("MAIN_SMILE_TYPE_ERROR"));
		else if($actionType == self::CHECK_TYPE_ADD && !isset($arFields['TYPE']))
			$arFields['TYPE'] = self::TYPE_SMILE;

		if($actionType == self::CHECK_TYPE_ADD && (!isset($arFields['SET_ID']) || intval($arFields['SET_ID']) <= 0))
			$aMsg[] = array("id"=>"SET_ID", "text"=> GetMessage("MAIN_SMILE_SET_ID_ERROR"));

		if($actionType == self::CHECK_TYPE_ADD && (!isset($arFields['SORT']) || intval($arFields['SORT']) <= 0))
			$arFields['SORT'] = 300;

		if($actionType == self::CHECK_TYPE_ADD && $arFields['TYPE'] == self::TYPE_SMILE && (!isset($arFields['TYPING']) || strlen($arFields['TYPING']) <= 0))
			$aMsg[] = array("id"=>"TYPING", "text"=> GetMessage("MAIN_SMILE_TYPING_ERROR"));

		if($actionType == self::CHECK_TYPE_UPDATE && $arFields['TYPE'] == self::TYPE_SMILE && (isset($arFields['TYPING']) && strlen($arFields['TYPING']) <= 0))
			$aMsg[] = array("id"=>"TYPING", "text"=> GetMessage("MAIN_SMILE_TYPING_ERROR"));

		if(isset($arFields['CLICKABLE']) && $arFields['CLICKABLE'] != 'N')
			$arFields['CLICKABLE'] = 'Y';

		if(isset($arFields['IMAGE_HR']) && $arFields['IMAGE_HR'] != 'Y')
			$arFields['IMAGE_HR'] = 'N';

		if($actionType == self::CHECK_TYPE_ADD && (!isset($arFields['IMAGE']) || strlen($arFields['IMAGE']) <= 0))
			$aMsg[] = array("id"=>"IMAGE", "text"=> GetMessage("MAIN_SMILE_IMAGE_ERROR"));

		if (isset($arFields['IMAGE']) && (!in_array(strtolower(GetFileExtension($arFields['IMAGE'])), Array('png', 'jpg', 'gif')) || !CBXVirtualIo::GetInstance()->ValidateFilenameString($arFields['IMAGE'])))
			$aMsg[] = array("id"=>"IMAGE", "text"=> GetMessage("MAIN_SMILE_IMAGE_ERROR"));

		if(isset($arFields['IMAGE']) && (!isset($arFields['IMAGE_WIDTH']) || intval($arFields['IMAGE_WIDTH']) <= 0))
			$aMsg["IMAGE_XY"] = array("id"=>"IMAGE_XY", "text"=> GetMessage("MAIN_SMILE_IMAGE_XY_ERROR"));

		if(isset($arFields['IMAGE']) && (!isset($arFields['IMAGE_HEIGHT']) || intval($arFields['IMAGE_HEIGHT']) <= 0))
			$aMsg["IMAGE_XY"] = array("id"=>"IMAGE_XY", "text"=> GetMessage("MAIN_SMILE_IMAGE_XY_ERROR"));

		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}

		return true;
	}

	public static function add($arFields)
	{
		global $DB, $CACHE_MANAGER;

		if (!self::checkFields($arFields, self::CHECK_TYPE_ADD))
			return false;

		$arInsert = array(
			'TYPE' => $arFields['TYPE'],
			'SET_ID' => intval($arFields['SET_ID']),
			'SORT' => intval($arFields['SORT']),
			'IMAGE' => $arFields['IMAGE'],
			'IMAGE_WIDTH' => intval($arFields['IMAGE_WIDTH']),
			'IMAGE_HEIGHT' => intval($arFields['IMAGE_HEIGHT']),
		);

		if (isset($arFields['IMAGE_HR']) && $arFields['IMAGE_HR'] == 'Y')
		{
			$arInsert['IMAGE_HR'] = $arFields['IMAGE_HR'];
			$arInsert['IMAGE_WIDTH'] = $arInsert['IMAGE_WIDTH']/2;
			$arInsert['IMAGE_HEIGHT'] = $arInsert['IMAGE_HEIGHT']/2;
		}

		if (isset($arFields['TYPING']))
			$arInsert['TYPING'] = $arFields['TYPING'];

		if (isset($arFields['CLICKABLE']))
			$arInsert['CLICKABLE'] = $arFields['CLICKABLE'];

		$setId = IntVal($DB->Add("b_smile", $arInsert));

		if ($setId && isset($arFields['LANG']))
		{
			$arLang = Array();
			if (is_array($arFields['LANG']))
				$arLang = $arFields['LANG'];
			else
				$arLang[LANG] = $arFields['LANG'];

			foreach ($arLang as $lang => $name)
			{
				if (strlen(trim($name)) > 0)
				{
					$arInsert = array(
						'TYPE' => self::TYPE_SMILE,
						'SID' => $setId,
						'LID' => htmlspecialcharsbx($lang),
						'NAME' => trim($name),
					);
					$DB->Add("b_smile_lang", $arInsert);
				}
			}
		}

		$CACHE_MANAGER->CleanDir("b_smile");

		return $setId;
	}

	public static function update($id, $arFields)
	{
		// TODO
		global $DB, $CACHE_MANAGER;

		$id = intVal($id);
		if (!self::checkFields($arFields, self::CHECK_TYPE_UPDATE))
			return false;

		$arUpdate = Array();

		if (isset($arFields['TYPE']))
			$arUpdate['TYPE'] = "'".$arFields['TYPE']."'";

		if (isset($arFields['SET_ID']))
			$arUpdate['SET_ID'] = intval($arFields['SET_ID']);

		if (isset($arFields['SORT']))
			$arUpdate['SORT'] = intval($arFields['SORT']);

		if (isset($arFields['IMAGE']))
		{
			$arUpdate['IMAGE'] = "'".$DB->ForSql($arFields['IMAGE'])."'";
			$arUpdate['IMAGE_WIDTH'] = intval($arFields['IMAGE_WIDTH']);
			$arUpdate['IMAGE_HEIGHT'] = intval($arFields['IMAGE_HEIGHT']);

			if (isset($arFields['IMAGE_HR']) && $arFields['IMAGE_HR'] == 'Y')
			{
				$arUpdate['IMAGE_HR'] = "'".$arFields['IMAGE_HR']."'";
				$arUpdate['IMAGE_WIDTH'] = $arUpdate['IMAGE_WIDTH']/2;
				$arUpdate['IMAGE_HEIGHT'] = $arUpdate['IMAGE_HEIGHT']/2;
			}
		}

		if (isset($arFields['TYPING']))
			$arUpdate['TYPING'] = "'".$DB->ForSql($arFields['TYPING'])."'";

		if (isset($arFields['CLICKABLE']))
			$arUpdate['CLICKABLE'] = "'".$arFields['CLICKABLE']."'";

		if (!empty($arUpdate))
			$DB->Update("b_smile", $arUpdate, "WHERE ID = ".intval($id));

		if (isset($arFields['LANG']))
		{
			$arLang = Array();
			if (is_array($arFields['LANG']))
				$arLang = $arFields['LANG'];
			else
				$arLang[LANG] = $arFields['LANG'];

			foreach ($arLang as $lang => $name)
			{
				if (strlen(trim($name)) > 0)
				{
					$DB->Query("DELETE FROM b_smile_lang WHERE TYPE = '".self::TYPE_SMILE."' AND SID = ".$id." AND LID = '".$DB->ForSql(htmlspecialcharsbx($lang))."'", true);
					$arInsert = array(
						'TYPE' => self::TYPE_SMILE,
						'SID' => $id,
						'LID' => htmlspecialcharsbx($lang),
						'NAME' => trim($name),
					);
					$DB->Add("b_smile_lang", $arInsert);
				}
			}
		}

		$CACHE_MANAGER->CleanDir("b_smile");

		return true;
	}

	public static function delete($id)
	{
		global $DB, $CACHE_MANAGER;

		$id = intval($id);
		if ($id <= 0)
			return false;

		$DB->Query("DELETE FROM b_smile WHERE ID = ".$id, true);
		$DB->Query("DELETE FROM b_smile_lang WHERE TYPE = '".self::TYPE_SMILE."' AND SID = ".$id, true);

		$CACHE_MANAGER->CleanDir("b_smile");

		return true;
	}

	public static function deleteBySet($id)
	{
		global $DB, $CACHE_MANAGER;

		$id = intval($id);
		if ($id <= 0)
			return false;

		$arDelete = Array();
		$arSmiles = self::getList(Array(
			'SELECT' => Array('ID'),
			'FILTER' => Array('SET_ID' => $id),
		));
		foreach ($arSmiles as $key => $value)
			$arDelete[] = intval($key);

		if (!empty($arDelete))
		{
			$DB->Query("DELETE FROM b_smile WHERE ID IN (".implode(',', $arDelete).")", true);
			$DB->Query("DELETE FROM b_smile_lang WHERE TYPE = '".self::TYPE_SMILE."' AND SID IN (".implode(',', $arDelete).")", true);

			$CACHE_MANAGER->CleanDir("b_smile");
		}

		return true;
	}

	public static function getById($id, $lang = LANGUAGE_ID)
	{
		global $DB;

		$id = intVal($id);
		$arResult = Array();

		$strSql = "
			SELECT s.ID, s.SET_ID, s.TYPE, s.SORT, s.TYPING, s.CLICKABLE, s.IMAGE, s.IMAGE_HR, s.IMAGE_WIDTH, s.IMAGE_HEIGHT, sl.NAME, sl.LID
			FROM b_smile s
			LEFT JOIN b_smile_lang sl ON sl.TYPE = '".self::TYPE_SMILE."' AND sl.SID = s.ID".($lang !== false? " AND sl.LID = '".$DB->ForSql(htmlspecialcharsbx($lang))."'": "")."
			WHERE s.ID = ".$id."";
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($lang !== self::GET_ALL_LANGUAGE)
		{
			$arResult = $res->GetNext(true, false);
			unset($arResult['LID']);
		}
		else
		{
			while ($row = $res->GetNext(true, false))
			{
				if (empty($arResult))
				{
					$arResult = $row;
					$arResult['NAME'] = Array();
					unset($arResult['LID']);
				}
				$arResult['NAME'][$row['LID']] = $row['NAME'];
			}
		}
		return $arResult;
	}

	public static function getList($arParams = Array(), $lang = LANGUAGE_ID)
	{
		global $DB;

		$arResult = $arSelect = $arOrder = $arFilter = $arJoin = Array();
		if (!isset($arParams['SELECT']) || !is_array($arParams['SELECT']))
			$arParams['SELECT'] = Array('ID', 'SET_ID',  'TYPE', 'NAME', 'SORT', 'TYPING', 'CLICKABLE', 'IMAGE', 'IMAGE_HR', 'IMAGE_WIDTH', 'IMAGE_HEIGHT');

		// select block
		foreach ($arParams['SELECT'] as $fieldName)
		{
			if ($fieldName == 'ID' || $fieldName == 'TYPE' || $fieldName == 'SET_ID' || $fieldName == 'SORT' || $fieldName == 'TYPING' || $fieldName == 'CLICKABLE'
			|| $fieldName == 'IMAGE' || $fieldName == 'IMAGE_HR' || $fieldName == 'IMAGE_WIDTH' || $fieldName == 'IMAGE_HEIGHT')
			{
				$arSelect[$fieldName] = 's.'.$fieldName;
			}
			elseif ($fieldName == 'NAME')
			{
				$arSelect['NAME'] = 'sl.'.$fieldName;
				$arJoin['LANG'] = "LEFT JOIN b_smile_lang sl ON sl.TYPE = '".self::TYPE_SMILE."' AND sl.SID = s.ID AND sl.LID = '".$DB->ForSql(htmlspecialcharsbx($lang))."'";
			}
			elseif ($fieldName == 'SET_NAME')
			{
				$arSelect['SET_ID'] = 's.SET_ID';
				$arSelect['SET_NAME'] = 'sl2.NAME as SET_NAME';
				$arJoin['LANG2'] = "LEFT JOIN b_smile_lang sl2 ON sl2.TYPE = '".CSmileSet::TYPE_SET."' AND sl2.SID = s.SET_ID AND sl2.LID = '".$DB->ForSql(htmlspecialcharsbx($lang))."'";
			}
		}
		$arSelect['ID'] = 's.ID';

		// filter block
		if (isset($arParams['FILTER']['ID']))
		{
			if (is_array($arParams['FILTER']['ID']))
			{
				$ID = Array();
				foreach ($arParams['FILTER']['ID'] as $key => $value)
					$ID[$key] = intval($value);

				if (!empty($ID))
					$arFilter[] = "s.ID IN (".implode(',', $ID).')';
			}
			else
			{
				$arFilter[] = "s.ID = ".intval($arParams['FILTER']['ID']);
			}
		}
		if (isset($arParams['FILTER']['SET_ID']))
		{
			if (is_array($arParams['FILTER']['SET_ID']))
			{
				$ID = Array();
				foreach ($arParams['FILTER']['SET_ID'] as $key => $value)
					$ID[$key] = intval($value);

				if (!empty($ID))
					$arFilter[] = "s.SET_ID IN ('".implode("','", $ID)."')";
			}
			else
			{
				$arFilter[] = "s.SET_ID = ".intval($arParams['FILTER']['SET_ID']);
			}
		}
		if (isset($arParams['FILTER']['TYPE']) && in_array($arParams['FILTER']['TYPE'], Array(self::TYPE_SMILE, self::TYPE_ICON)))
		{
			$arFilter[] = "s.TYPE = '".$arParams['FILTER']['TYPE']."'";
		}

		// order block
		if (isset($arParams['ORDER']) && is_array($arParams['ORDER']))
		{
			foreach ($arParams['ORDER'] as $by => $order)
			{
				$order = strtoupper($order) == 'ASC'? 'ASC': 'DESC';
				$by = strtoupper($by);
				if (in_array($by, Array('ID', 'SET_ID', 'SORT', 'IMAGE_HR')))
				{
					$arOrder[$by] = 's.'.$by.' '.$order;
				}
			}
		}
		else
		{
			$arOrder['ID'] = 's.ID DESC';
		}

		$strSelect = "SELECT ".implode(', ', $arSelect);
		$strSql = "
			FROM b_smile s
			".(!empty($arJoin)? implode(' ', $arJoin): "")."
			".(!empty($arFilter)? "WHERE ".implode(' AND ', $arFilter): "")."
			".(!empty($arOrder)? "ORDER BY ".implode(', ', $arOrder): "")."
		";

		if (isset($arParams['RETURN_SQL']) && $arParams['RETURN_SQL'] == 'Y')
		{
			return $strSelect.$strSql;
		}

		if(array_key_exists("NAV_PARAMS", $arParams) && is_array($arParams["NAV_PARAMS"]))
		{
			$nTopCount = intval($arParams['NAV_PARAMS']['nTopCount']);
			if($nTopCount > 0)
			{
				$strSql = $DB->TopSql($strSelect.$strSql, $nTopCount);
				$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
			else
			{
				$res_cnt = $DB->Query("
					SELECT COUNT(s.ID) as CNT
					FROM b_smile s
					".(!empty($arFilter)? "WHERE ".implode(' AND ', $arFilter): "")
				);
				$arCount = $res_cnt->Fetch();
				$res = new CDBResult();
				$res->NavQuery($strSelect.$strSql, $arCount["CNT"], $arParams["NAV_PARAMS"]);
			}
		}
		else
		{
			$res = $DB->Query($strSelect.$strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		if (isset($arParams['RETURN_RES']) && $arParams['RETURN_RES'] == 'Y')
		{
			return $res;
		}
		else
		{
			while ($row = $res->GetNext(true, false))
				$arResult[$row['ID']] = $row;

			return $arResult;
		}
	}

	public static function getByType($type = self::TYPE_ALL, $setId = CSmileSet::SET_ID_BY_CONFIG, $lang = LANGUAGE_ID)
	{
		$arFilter = array();
		if (in_array($type, array(self::TYPE_SMILE, self::TYPE_ICON)))
			$arFilter["TYPE"] = $type;

		$setId = intval($setId);
		if ($setId == CSmileSet::SET_ID_BY_CONFIG)
			$setId = CSmileSet::getConfigSetId();

		if ($lang <> '')
			$arFilter["LID"] = htmlspecialcharsbx($lang);

		global $CACHE_MANAGER;
		$cache_id = "b_smile_".$arFilter["TYPE"]."_".$setId."_".$arFilter["LID"];

		if (CACHED_b_smile !== false && $CACHE_MANAGER->Read(CACHED_b_smile, $cache_id, "b_smile"))
		{
			$arResult = $CACHE_MANAGER->Get($cache_id);
		}
		else
		{
			if ($setId != CSmileSet::SET_ID_ALL)
				$arFilter['SET_ID'] = $setId;

			$arResult = self::getList(Array(
				'ORDER' => Array('SORT' => 'ASC'),
				'FILTER' => $arFilter,
			));

			if (CACHED_b_smile !== false)
				$CACHE_MANAGER->Set($cache_id, $arResult);
		}

		return $arResult;
	}

	public static function import($arParams)
	{
		global $APPLICATION;

		// check fields
		$aMsg = array();
		$arParams['SET_ID'] = intval($arParams['SET_ID']);
		$arParams['IMPORT_IF_FILE_EXISTS'] = isset($arParams['IMPORT_IF_FILE_EXISTS']) && $arParams['IMPORT_IF_FILE_EXISTS'] == 'Y'? true: false;
		if(isset($arParams['FILE']) && GetFileExtension($arParams['FILE']) != 'zip')
		{
			$aMsg["FILE_EXT"] = array("id"=>"FILE_EXT", "text"=> GetMessage("MAIN_SMILE_IMPORT_FILE_EXT_ERROR"));
		}
		else if (!isset($arParams['FILE']) || !file_exists($arParams['FILE']))
		{
			$aMsg["FILE"] = array("id"=>"FILE", "text"=> GetMessage("MAIN_SMILE_IMPORT_FILE_ERROR"));
		}
		else if($arParams['SET_ID'] <= 0)
		{
			$aMsg["SET_ID"] = array("id"=>"SET_ID", "text"=> GetMessage("MAIN_SMILE_IMPORT_SET_ID_ERROR"));
		}
		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}
		$sUnpackDir = CTempFile::GetDirectoryName(1);
		CheckDirPath($sUnpackDir);

		/** @var IBXArchive $oArchiver */
		$oArchiver = CBXArchive::GetArchive($arParams['FILE'], "ZIP");
		$oArchiver->SetOptions(array("STEP_TIME" => 300));

		if (!$oArchiver->Unpack($sUnpackDir))
		{
			$aMsg["UNPACK"] = array("id"=>"UNPACK", "text"=> GetMessage("MAIN_SMILE_IMPORT_UNPACK_ERROR"));
			$e = new CAdminException($aMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}

		$arSmiles = Array();
		if (file_exists($sUnpackDir.'install.csv'))
		{
			$arLang = Array();
			$db_res = CLanguage::GetList($b="sort", $o="asc");
			while ($res = $db_res->Fetch())
			{
				if (file_exists($sUnpackDir.'install_lang_'. $res["LID"].'.csv'))
				{
					$arSmiles = Array();
					$csvFile = new CCSVData();
					$csvFile->LoadFile($sUnpackDir.'install_lang_'.$res["LID"].'.csv');
					$csvFile->SetFieldsType("R");
					$csvFile->SetFirstHeader(false);
					while($smile = $csvFile->Fetch())
					{
						if (defined('BX_UTF') && BX_UTF && $res["LID"] == 'ru')
							$smile[1] = $APPLICATION->ConvertCharset($smile[1], 'windows-1251', 'utf-8');

						$arLang[$smile[0]][$res["LID"]] = $smile[1];
					}
				}
			}

			$csvFile = new CCSVData();
			$csvFile->LoadFile($sUnpackDir.'install.csv');
			$csvFile->SetFieldsType("R");
			$csvFile->SetFirstHeader(false);
			while($smile = $csvFile->Fetch())
			{
				if (!in_array($smile[0], Array(CSmile::TYPE_SMILE, CSmile::TYPE_ICON)))
					continue;

				$smile[3] = GetFileName($smile[3]);

				$imgArray = CFile::GetImageSize($sUnpackDir.$smile[3]);
				if (!is_array($imgArray))
					continue;

				$arInsert = Array(
					'TYPE' => $smile[0],
					'SET_ID' => $arParams['SET_ID'],
					'CLICKABLE' => $smile[1] == 'Y'? 'Y': 'N',
					'SORT' => intval($smile[2]),
					'IMAGE' => $smile[3],
					'IMAGE_WIDTH' => intval($smile[4]),
					'IMAGE_HEIGHT' => intval($smile[5]),
					'IMAGE_HR' => $smile[6] == 'Y'? 'Y': 'N',
					'TYPING' => $smile[8],
				);

				if (isset($smile[7]) && isset($arLang[$smile[7]]))
					$arInsert['LANG'] = $arLang[$smile[7]];

				$arSmiles[] = $arInsert;
			}
		}
		else
		{
			$smileSet = CSmileSet::getById($arParams['SET_ID']);
			if ($handle = @opendir($sUnpackDir))
			{
				$sort = 300;
				while (($file = readdir($handle)) !== false)
				{
					if ($file == "." || $file == "..")
						continue;

					if (is_file($sUnpackDir.$file))
					{
						$imgArray = CFile::GetImageSize($sUnpackDir.$file);
						if (is_array($imgArray))
						{
							$smileHR = 'N';
							$smileType = CSmile::TYPE_SMILE;
							$smileCode = GetFileNameWithoutExtension($file);
							if (strpos($file, 'smile_') !== false && strpos($file, 'smile_') == 0)
							{
								$smileCode = substr($smileCode, 6);
							}
							else if (strpos($file, 'smile') !== false && strpos($file, 'smile') == 0)
							{
								$smileCode = substr($smileCode, 5);
							}
							elseif (strpos($file, 'icon_') !== false && strpos($file, 'icon_') == 0)
							{
								$smileType = CSmile::TYPE_ICON;
								$smileCode = substr($smileCode, 5);
							}
							else if (strpos($file, 'icon') !== false && strpos($file, 'icon') == 0)
							{
								$smileType = CSmile::TYPE_ICON;
								$smileCode = substr($smileCode, 4);
							}
							if (strrpos($smileCode, '_hr') !== false && strrpos($smileCode, '_hr') == strlen($smileCode)-3)
							{
								$smileHR = 'Y';
								$smileCode = substr($smileCode, 0, strrpos($smileCode, '_hr'));
							}
							if (($pos = strpos($smileCode, '_hr_')))
							{
								echo substr($smileCode, 0, $pos);
								$smileHR = 'Y';
								$smileCode = substr($smileCode, 0, $pos).'_'.substr($smileCode, $pos+4);
							}

							$arSmiles[] = Array(
								'TYPE' => $smileType,
								'SET_ID' => $arParams['SET_ID'],
								'CLICKABLE' => 'Y',
								'SORT' => $sort,
								'IMAGE' => $file,
								'IMAGE_WIDTH' => intval($imgArray[0]),
								'IMAGE_HEIGHT' => intval($imgArray[1]),
								'IMAGE_HR' => $smileHR,
								'TYPING' => ':'.(isset($smileSet['STRING_ID'])? $smileSet['STRING_ID']: $smileSet['ID']).'/'.$smileCode.':',
							);
							$sort = $sort+5;
						}
					}

				}
				@closedir($handle);
			}
		}
		$importSmile = 0;
		foreach ($arSmiles as $smile)
		{
			$sUploadDir = ($smile['TYPE'] == CSmile::TYPE_ICON? CSmile::PATH_TO_ICON: CSmile::PATH_TO_SMILE).intval($smile["SET_ID"]).'/';
			if (file_exists($sUnpackDir.$smile['IMAGE']) && ($arParams['IMPORT_IF_FILE_EXISTS'] || !file_exists($_SERVER["DOCUMENT_ROOT"].$sUploadDir.$smile['IMAGE'])))
			{
				if (CheckDirPath($_SERVER["DOCUMENT_ROOT"].$sUploadDir))
				{
					$insertId = CSmile::add($smile);
					if ($insertId)
					{
						if ($arParams['IMPORT_IF_FILE_EXISTS'] && file_exists($_SERVER["DOCUMENT_ROOT"].$sUploadDir.$smile['IMAGE']))
						{
							$importSmile++;
						}
						else if (copy($sUnpackDir.$smile['IMAGE'], $_SERVER["DOCUMENT_ROOT"].$sUploadDir.$smile['IMAGE']))
						{
							@chmod($_SERVER["DOCUMENT_ROOT"].$sUploadDir.$smile['IMAGE'], BX_FILE_PERMISSIONS);
							$importSmile++;
						}
						else
						{
							CSmile::delete($insertId);
						}
					}

					$APPLICATION->ResetException();
				}
			}
		}

		return $importSmile;
	}
}

class CSmileSet
{
	const TYPE_SET = 'G';
	const SET_ID_ALL = 0;
	const SET_ID_BY_CONFIG = -1;
	const GET_ALL_LANGUAGE = false;

	public static function add($arFields)
	{
		global $DB, $CACHE_MANAGER;

		$arInsert = array();

		if (isset($arFields['STRING_ID']))
			$arInsert['STRING_ID'] = $arFields['STRING_ID'];

		if (isset($arFields['SORT']))
			$arInsert['SORT'] = intval($arFields['SORT']);

		$setId = IntVal($DB->Add("b_smile_set", $arInsert));

		if ($setId && isset($arFields['LANG']))
		{
			$arLang = Array();
			if (is_array($arFields['LANG']))
				$arLang = $arFields['LANG'];
			else
				$arLang[LANG] = $arFields['LANG'];

			foreach ($arLang as $lang => $name)
			{
				$arInsert = array(
					'TYPE' => self::TYPE_SET,
					'SID' => $setId,
					'LID' => htmlspecialcharsbx($lang),
					'NAME' => $name,
				);
				$DB->Add("b_smile_lang", $arInsert);
			}
		}

		$CACHE_MANAGER->CleanDir("b_smile_set");

		return $setId;
	}

	public static function update($id, $arFields)
	{
		global $DB, $CACHE_MANAGER;

		$id = intVal($id);

		$arUpdate = Array();

		if (isset($arFields['STRING_ID']))
			$arUpdate['STRING_ID'] = "'".$DB->ForSql($arFields['STRING_ID'])."'";

		if (isset($arFields['SORT']))
			$arUpdate['SORT'] = intval($arFields['SORT']);

		if (!empty($arUpdate))
			$DB->Update("b_smile_set", $arUpdate, "WHERE ID = ".intval($id));

		if (isset($arFields['LANG']))
		{
			$arLang = Array();
			if (is_array($arFields['LANG']))
				$arLang = $arFields['LANG'];
			else
				$arLang[LANG] = $arFields['LANG'];

			foreach ($arLang as $lang => $name)
			{
				$DB->Query("DELETE FROM b_smile_lang WHERE TYPE = '".self::TYPE_SET."' AND SID = ".$id." AND LID = '".$DB->ForSql(htmlspecialcharsbx($lang))."'", true);
				$arInsert = array(
					'TYPE' => self::TYPE_SET,
					'SID' => $id,
					'LID' => htmlspecialcharsbx($lang),
					'NAME' => $name,
				);
				$DB->Add("b_smile_lang", $arInsert);
			}
		}

		$CACHE_MANAGER->CleanDir("b_smile_set");

		return true;
	}

	public static function delete($id)
	{
		global $DB, $CACHE_MANAGER;

		$id = intval($id);

		$DB->Query("DELETE FROM b_smile_set WHERE ID = ".$id, true);
		$DB->Query("DELETE FROM b_smile_lang WHERE TYPE = '".self::TYPE_SET."' AND SID = ".$id, true);

		CSmile::deleteBySet($id);

		$CACHE_MANAGER->CleanDir("b_smile_set");

		return true;
	}

	public static function getById($id, $lang = LANGUAGE_ID)
	{
		global $DB;

		$id = intVal($id);
		$arResult = Array();

		$strSql = "
			SELECT ss.ID, ss.STRING_ID, ss.SORT, sl.NAME, sl.LID
			FROM b_smile_set ss
			LEFT JOIN b_smile_lang sl ON sl.TYPE = '".self::TYPE_SET."' AND sl.SID = ss.ID".($lang !== false? " AND sl.LID = '".$DB->ForSql(htmlspecialcharsbx($lang))."'": "")."
			WHERE ss.ID = ".$id."";
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($lang !== self::GET_ALL_LANGUAGE)
		{
			$arResult = $res->GetNext(true, false);
			unset($arResult['LID']);
		}
		else
		{
			while ($row = $res->GetNext(true, false))
			{
				if (empty($arResult))
				{
					$arResult = $row;
					$arResult['NAME'] = Array();
					unset($arResult['LID']);
				}
				$arResult['NAME'][$row['LID']] = $row['NAME'];
			}
		}
		return $arResult;
	}

	public static function getByStringId($id, $lang = LANGUAGE_ID)
	{
		global $DB;

		$arResult = Array();

		$strSql = "
			SELECT ss.ID, ss.STRING_ID, ss.SORT, sl.NAME, sl.LID
			FROM b_smile_set ss
			LEFT JOIN b_smile_lang sl ON sl.TYPE = '".self::TYPE_SET."' AND sl.SID = ss.ID".($lang !== false? " AND sl.LID = '".$DB->ForSql(htmlspecialcharsbx($lang))."'": "")."
			WHERE ss.STRING_ID = '".$DB->ForSql($id)."'";
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($lang !== false)
		{
			$arResult = $res->GetNext(true, false);
			unset($arResult['LID']);
		}
		else
		{
			while ($row = $res->GetNext(true, false))
			{
				if (empty($arResult))
				{
					$arResult = $row;
					$arResult['NAME'] = Array();
					unset($arResult['LID']);
				}
				$arResult['NAME'][$row['LID']] = $row['NAME'];
			}
		}
		return $arResult;
	}

	public static function getBySmiles($arSmiles)
	{
		$arResult = Array();
		$arSets = self::getListCache();

		foreach ($arSmiles as $smile)
		{
			if (isset($arSets[$smile['SET_ID']]))
				$arResult[$smile['SET_ID']] = $arSets[$smile['SET_ID']];
		}

		return $arResult;
	}

	public static function getList($arParams = Array(), $lang = LANGUAGE_ID)
	{
		global $DB;

		$arResult = $arSelect = $arOrder = $arFilter = $arJoin = Array();
		if (!isset($arParams['SELECT']) || !is_array($arParams['SELECT']))
			$arParams['SELECT'] = Array('ID', 'STRING_ID', 'SORT', 'NAME');

		if (isset($arParams['ORDER']['SMILE_COUNT']))
			$arParams['SELECT'][] = 'SMILE_COUNT';

		// select block
		foreach ($arParams['SELECT'] as $fieldName)
		{
			if ($fieldName == 'ID' || $fieldName == 'STRING_ID' || $fieldName == 'SORT')
			{
				$arSelect[$fieldName] = 'ss.'.$fieldName;
			}
			elseif ($fieldName == 'NAME')
			{
				$arSelect['NAME'] = 'sl.'.$fieldName;
				$arJoin['LANG'] = "LEFT JOIN b_smile_lang sl ON sl.TYPE = '".self::TYPE_SET."' AND sl.SID = ss.ID AND sl.LID = '".$DB->ForSql(htmlspecialcharsbx($lang))."'";
			}
			elseif ($fieldName == 'SMILE_COUNT')
			{
				$arSelect['SMILE_COUNT'] = '(SELECT COUNT(s.ID) FROM b_smile s WHERE s.SET_ID = ss.ID) as SMILE_COUNT';
			}
		}
		$arSelect['ID'] = 'ss.ID';

		// filter block
		if (isset($arParams['FILTER']['ID']))
		{
			if (is_array($arParams['FILTER']['ID']))
			{
				$ID = Array();
				foreach ($arParams['FILTER']['ID'] as $key => $value)
					$ID[$key] = intval($value);

				if (!empty($ID))
					$arFilter[] = "ss.ID IN (".implode(',', $ID).')';
			}
			else
			{
				$arFilter[] = "ss.ID = ".intval($arParams['FILTER']['ID']);
			}
		}
		if (isset($arParams['FILTER']['STRING_ID']))
		{
			if (is_array($arParams['FILTER']['STRING_ID']))
			{
				$ID = Array();
				foreach ($arParams['FILTER']['STRING_ID'] as $key => $value)
					$ID[$key] = intval($value);

				if (!empty($ID))
					$arFilter[] = "ss.STRING_ID IN ('".implode("','", $ID)."')";
			}
			else
			{
				$arFilter[] = "ss.STRING_ID = ".intval($arParams['FILTER']['STRING_ID']);
			}
		}

		// order block
		if (isset($arParams['ORDER']) && is_array($arParams['ORDER']))
		{
			foreach ($arParams['ORDER'] as $by => $order)
			{
				$order = strtoupper($order) == 'ASC'? 'ASC': 'DESC';
				$by = strtoupper($by);
				if (in_array($by, Array('ID', 'SORT')))
				{
					$arOrder[$by] = 'ss.'.$by.' '.$order;
				}
				else if ($by == 'SMILE_COUNT')
					$arOrder[$by] = $by.' '.$order;
			}
		}
		else
		{
			$arOrder['ID'] = 'ss.ID DESC';
		}

		$strSelect = "SELECT ".implode(', ', $arSelect);
		$strSql = "
			FROM b_smile_set ss
			".(!empty($arJoin)? implode(' ', $arJoin): "")."
			".(!empty($arFilter)? "WHERE ".implode(' AND ', $arFilter): "")."
			".(!empty($arOrder)? "ORDER BY ".implode(', ', $arOrder): "")."
		";

		if (isset($arParams['RETURN_SQL']) && $arParams['RETURN_SQL'] == 'Y')
		{
			return $strSelect.$strSql;
		}

		if(array_key_exists("NAV_PARAMS", $arParams) && is_array($arParams["NAV_PARAMS"]))
		{
			$nTopCount = intval($arParams['NAV_PARAMS']['nTopCount']);
			if($nTopCount > 0)
			{
				$strSql = $DB->TopSql($strSelect.$strSql, $nTopCount);
				$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
			else
			{
				$res_cnt = $DB->Query("
					SELECT COUNT(ss.ID) as CNT
					FROM b_smile_set ss
					".(!empty($arFilter)? "WHERE ".implode(' AND ', $arFilter): "")
				);
				$arCount = $res_cnt->Fetch();
				$res = new CDBResult();
				$res->NavQuery($strSelect.$strSql, $arCount["CNT"], $arParams["NAV_PARAMS"]);
			}
		}
		else
		{
			$res = $DB->Query($strSelect.$strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		if (isset($arParams['RETURN_RES']) && $arParams['RETURN_RES'] == 'Y')
		{
			return $res;
		}
		else
		{
			while ($row = $res->GetNext(true, false))
				$arResult[$row['ID']] = $row;

			return $arResult;
		}
	}

	public static function getListCache($lang = LANGUAGE_ID)
	{
		if (strlen($lang) > 0)
			$lang = htmlspecialcharsbx($lang);

		global $CACHE_MANAGER;
		$cache_id = "b_smile_set_".$lang;

		if (CACHED_b_smile !== false && $CACHE_MANAGER->Read(CACHED_b_smile, $cache_id, "b_smile_set"))
		{
			$arResult = $CACHE_MANAGER->Get($cache_id);
		}
		else
		{
			$arResult = self::getList(Array('ORDER' => Array('SORT' => 'ASC')), $lang);
			if (CACHED_b_smile !== false)
				$CACHE_MANAGER->Set($cache_id, $arResult);
		}

		return $arResult;
	}

	public static function getFormList($bWithOptionAll = false, $lang = LANGUAGE_ID)
	{
		$arSetList = Array();
		if ($bWithOptionAll)
			$arSetList[0] = GetMessage('MAIN_SMILE_ALL_SET');

		foreach (CSmileSet::getListCache($lang) as $key => $value)
			$arSetList[$key] = !empty($value['NAME'])? $value['NAME']: GetMessage('MAIN_SMILE_SET_NAME', Array('#ID#' => $key));

		return $arSetList;
	}

	public static function getConfigSetId()
	{
		$setId = COption::GetOptionString("main", "smile_set_id", self::SET_ID_ALL);
		$eventSetId = -1;
		foreach(GetModuleEvents("main", "OnBeforeSmileGetConfigSetId", true) as $arEvent)
			$eventSetId = intval(ExecuteModuleEventEx($arEvent, array($setId)));

		return $eventSetId >= self::SET_ID_ALL? $eventSetId: $setId;
	}
}

