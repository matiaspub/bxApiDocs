<?
IncludeModuleLangFile(__FILE__);

class CCatalogDiscountConvert
{
	public static $intConvertPerStep = 0;
	public static $intNextConvertPerStep = 0;
	public static $intConverted = 0;
	public static $intLastConvertID = 0;
	public static $boolEmptyList = false;
	public static $intErrors = 0;
	public static $arErrors = array();
	public static $strSessID = '';

	static public function __construct()
	{

	}

	public static function InitStep()
	{
		if ('' == self::$strSessID)
			self::$strSessID = 'DC'.time();
		if (array_key_exists(self::$strSessID, $_SESSION) && is_array($_SESSION[self::$strSessID]))
		{
			if (isset($_SESSION[self::$strSessID]['ERRORS_COUNT']) && 0 < intval($_SESSION[self::$strSessID]['ERRORS_COUNT']))
				self::$intErrors = intval($_SESSION[self::$strSessID]['ERRORS_COUNT']);
			if (isset($_SESSION[self::$strSessID]['ERRORS']) && is_array($_SESSION[self::$strSessID]['ERRORS']))
				self::$arErrors = $_SESSION[self::$strSessID]['ERRORS'];
		}
	}

	public static function SaveStep()
	{
		if ('' == self::$strSessID)
			self::$strSessID = 'DC'.time();
		if (!array_key_exists(self::$strSessID, $_SESSION) || !is_array($_SESSION[self::$strSessID]))
			$_SESSION[self::$strSessID] = array();
		if (0 < self::$intErrors)
		{
			$_SESSION[self::$strSessID]['ERRORS_COUNT'] = self::$intErrors;
		}
		if (!empty(self::$arErrors))
		{
			$_SESSION[self::$strSessID]['ERRORS'] = self::$arErrors;
		}
	}

	public static function GetErrors()
	{
		return self::$arErrors;
	}

	public static function ConvertDiscount($intStep = 100, $intMaxExecutionTime = 15)
	{
		global $DBType;
		global $DB;
		global $APPLICATION;

		self::InitStep();

		$intStep = intval($intStep);
		if (0 >= $intStep)
			$intStep = 100;
		$startConvertTime = getmicrotime();

		$obDiscount = new CCatalogDiscount();

		$strQueryPriceTypes = '';
		$strQueryUserGroups = '';
		$strTableName = '';
		switch (ToUpper($DBType))
		{
			case 'MYSQL':
				$strQueryPriceTypes = 'select CATALOG_GROUP_ID from b_catalog_discount2cat where DISCOUNT_ID = #ID#';
				$strQueryUserGroups = 'select GROUP_ID from b_catalog_discount2group where DISCOUNT_ID = #ID#';
				$strTableName = 'b_catalog_discount';
				break;
			case 'MSSQL':
				$strQueryPriceTypes = 'select CATALOG_GROUP_ID from B_CATALOG_DISCOUNT2CAT where DISCOUNT_ID = #ID#';
				$strQueryUserGroups = 'select GROUP_ID from B_CATALOG_DISCOUNT2GROUP where DISCOUNT_ID = #ID#';
				$strTableName = 'B_CATALOG_DISCOUNT';
				break;
			case 'ORACLE':
				$strQueryPriceTypes = 'select CATALOG_GROUP_ID from B_CATALOG_DISCOUNT2CAT where DISCOUNT_ID = #ID#';
				$strQueryUserGroups = 'select GROUP_ID from B_CATALOG_DISCOUNT2GROUP where DISCOUNT_ID = #ID#';
				$strTableName = 'B_CATALOG_DISCOUNT';
				break;
		}

		CTimeZone::Disable();

		$rsDiscounts = CCatalogDiscount::GetList(
			array('ID' => 'ASC'),
			array(
				'TYPE' => DISCOUNT_TYPE_STANDART,
				'VERSION' => CATALOG_DISCOUNT_OLD_VERSION
			),
			false,
			array('nTopCount' => $intStep),
			array('ID', 'MODIFIED_BY', 'TIMESTAMP_X', 'NAME')
		);
		while ($arDiscount = $rsDiscounts->Fetch())
		{
			$boolActive = true;
			$arSrcEntity = array();

			$arFields = array();
			$arFields['MODIFIED_BY'] = $arDiscount['MODIFIED_BY'];
			$arPriceTypes = array();
			$arUserGroups = array();

			$rsPriceTypes = $DB->Query(str_replace('#ID#', $arDiscount['ID'], $strQueryPriceTypes), false, "File: ".__FILE__."<br>Line: ".__LINE__);
			while ($arPrice = $rsPriceTypes->Fetch())
			{
				$arPrice['CATALOG_GROUP_ID'] = intval($arPrice['CATALOG_GROUP_ID']);
				if (0 < $arPrice['CATALOG_GROUP_ID'])
					$arPriceTypes[] = $arPrice['CATALOG_GROUP_ID'];
			}
			if (!empty($arPriceTypes))
			{
				$arPriceTypes = array_values(array_unique($arPriceTypes));
			}
			else
			{
				$arPriceTypes = array(-1);
			}

			$rsUserGroups = $DB->Query(str_replace('#ID#', $arDiscount['ID'], $strQueryUserGroups), false, "File: ".__FILE__."<br>Line: ".__LINE__);
			while ($arGroup = $rsUserGroups->Fetch())
			{
				$arGroup['GROUP_ID'] = intval($arGroup['GROUP_ID']);
				if (0 < $arGroup['GROUP_ID'])
					$arUserGroups[] = $arGroup['GROUP_ID'];
			}
			if (!empty($arUserGroups))
			{
				$arUserGroups = array_values(array_unique($arUserGroups));
			}
			else
			{
				$arUserGroups = array(-1);
			}

			$arFields['CATALOG_GROUP_IDS'] = $arPriceTypes;
			$arFields['GROUP_IDS'] = $arUserGroups;

			$arIBlockList = array();
			$arSectionList = array();
			$arElementList = array();
			$arConditions = array(
				'CLASS_ID' => 'CondGroup',
				'DATA' => array(
					'All' => 'AND',
					'True' => 'True',
				),
				'CHILDREN' => array(),
			);
			$intEntityCount = 0;

			$boolEmpty = true;
			$arSrcList = array();
			$rsIBlocks = CCatalogDiscount::GetDiscountIBlocksList(array(), array('DISCOUNT_ID' => $arDiscount['ID']), false, false, array('IBLOCK_ID'));
			while ($arIBlock = $rsIBlocks->Fetch())
			{
				$boolEmpty = false;
				$arSrcList[] = $arIBlock['IBLOCK_ID'];
				$arIBlock['IBLOCK_ID'] = intval($arIBlock['IBLOCK_ID']);
				if (0 < $arIBlock['IBLOCK_ID'])
				{
					$strName = CIBlock::GetArrayByID($arIBlock['IBLOCK_ID'], 'NAME');
					if (false !== $strName && !is_null($strName))
					{
						$arIBlockList[] = $arIBlock['IBLOCK_ID'];
					}
				}
			}
			if (!empty($arIBlockList))
			{
				$arIBlockList = array_values(array_unique($arIBlockList));
				$intEntityCount++;
			}
			else
			{
				if (!$boolEmpty)
				{
					$boolActive = false;
					$arSrcEntity[] = str_replace('#IDS#', implode(', ', $arSrcList), GetMessage('BT_MOD_CAT_DSC_CONV_ENTITY_IBLOCK_ERR'));
				}
			}

			$boolEmpty = true;
			$arSrcList = array();
			$rsSections = CCatalogDiscount::GetDiscountSectionsList(array(), array('DISCOUNT_ID' => $arDiscount['ID']), false, false, array('SECTION_ID'));
			while ($arSection = $rsSections->Fetch())
			{
				$boolEmpty = false;
				$arSrcList[] = $arSection['SECTION_ID'];
				$arSection['SECTION_ID'] = intval($arSection['SECTION_ID']);
				if (0 < $arSection['SECTION_ID'])
					$arSectionList[] = $arSection['SECTION_ID'];
			}
			if (!empty($arSectionList))
			{
				$arSectionList = array_values(array_unique($arSectionList));
				$rsSections = CIBlockSection::GetList(array(), array('ID' => $arSectionList), false, array('ID'));
				$arCheckResult = array();
				while ($arSection = $rsSections->Fetch())
				{
					$arCheckResult[] = intval($arSection['ID']);
				}
				if (!empty($arCheckResult))
				{
					$arSectionList = $arCheckResult;
					$intEntityCount++;
				}
				else
				{
					$arSectionList = array();
				}
			}

			if (empty($arSectionList))
			{
				if (!$boolEmpty)
				{
					$boolActive = false;
					$arSrcEntity[] = str_replace('#IDS#', implode(', ', $arSrcList), GetMessage('BT_MOD_CAT_DSC_CONV_ENTITY_SECTION_ERR'));
				}
			}

			$boolEmpty = true;
			$arSrcList = array();
			$rsElements = CCatalogDiscount::GetDiscountProductsList(array(), array('DISCOUNT_ID' => $arDiscount['ID']), false, false, array('PRODUCT_ID'));
			while ($arElement = $rsElements->Fetch())
			{
				$boolEmpty = false;
				$arSrcList[] = $arElement['PRODUCT_ID'];
				$arElement['PRODUCT_ID'] = intval($arElement['PRODUCT_ID']);
				if (0 < $arElement['PRODUCT_ID'])
					$arElementList[] = $arElement['PRODUCT_ID'];
			}
			if (!empty($arElementList))
			{
				$arElementList = array_values(array_unique($arElementList));
				$rsItems = CIBlockElement::GetList(array(), array('ID' => $arElementList), false, false, array('ID'));
				$arCheckResult = array();
				while ($arItem = $rsItems->Fetch())
				{
					$arCheckResult[] = intval($arItem['ID']);
				}
				if (!empty($arCheckResult))
				{
					$arElementList = $arCheckResult;
					$intEntityCount++;
				}
				else
				{
					$arElementList = array();
				}
			}

			if (empty($arElementList))
			{
				if (!$boolEmpty)
				{
					$boolActive = false;
					$arSrcEntity[] = str_replace('#IDS#', implode(', ', $arSrcList), GetMessage('BT_MOD_CAT_DSC_CONV_ENTITY_ELEMENT_ERR'));
				}
			}

			if (!empty($arIBlockList))
			{
				if (1 < count($arIBlockList))
				{
					$arList = array();
					foreach ($arIBlockList as &$intItemID)
					{
						$arList[] = array(
							'CLASS_ID' => 'CondIBIBlock',
							'DATA' => array(
								'logic' => 'Equal',
								'value' => $intItemID
							),
						);
					}
					if (isset($intItemID))
						unset($intItemID);
					if (1 == $intEntityCount)
					{
						$arConditions = array(
							'CLASS_ID' => 'CondGroup',
							'DATA' => array(
								'All' => 'OR',
								'True' => 'True',
							),
							'CHILDREN' => $arList,
						);
					}
					else
					{
						$arConditions['CHILDREN'][] = array(
							'CLASS_ID' => 'CondGroup',
							'DATA' => array(
								'All' => 'OR',
								'True' => 'True',
							),
							'CHILDREN' => $arList,
						);
					}
				}
				else
				{
					$arConditions['CHILDREN'][] = array(
						'CLASS_ID' => 'CondIBIBlock',
						'DATA' => array(
							'logic' => 'Equal',
							'value' => current($arIBlockList)
						),
					);
				}
			}

			if (!empty($arSectionList))
			{
				if (1 < count($arSectionList))
				{
					$arList = array();
					foreach ($arSectionList as &$intItemID)
					{
						$arList[] = array(
							'CLASS_ID' => 'CondIBSection',
							'DATA' => array(
								'logic' => 'Equal',
								'value' => $intItemID
							),
						);
					}
					if (isset($intItemID))
						unset($intItemID);
					if (1 == $intEntityCount)
					{
						$arConditions = array(
							'CLASS_ID' => 'CondGroup',
							'DATA' => array(
								'All' => 'OR',
								'True' => 'True',
							),
							'CHILDREN' => $arList,
						);
					}
					else
					{
						$arConditions['CHILDREN'][] = array(
							'CLASS_ID' => 'CondGroup',
							'DATA' => array(
								'All' => 'OR',
								'True' => 'True',
							),
							'CHILDREN' => $arList,
						);
					}
				}
				else
				{
					$arConditions['CHILDREN'][] = array(
						'CLASS_ID' => 'CondIBSection',
						'DATA' => array(
							'logic' => 'Equal',
							'value' => current($arSectionList)
						),
					);
				}
			}

			if (!empty($arElementList))
			{
				if (1 < count($arElementList))
				{
					$arList = array();
					foreach ($arElementList as &$intItemID)
					{
						$arList[] = array(
							'CLASS_ID' => 'CondIBElement',
							'DATA' => array(
								'logic' => 'Equal',
								'value' => $intItemID
							),
						);
					}
					if (isset($intItemID))
						unset($intItemID);
					if (1 == $intEntityCount)
					{
						$arConditions = array(
							'CLASS_ID' => 'CondGroup',
							'DATA' => array(
								'All' => 'OR',
								'True' => 'True',
							),
							'CHILDREN' => $arList,
						);
					}
					else
					{
						$arConditions['CHILDREN'][] = array(
							'CLASS_ID' => 'CondGroup',
							'DATA' => array(
								'All' => 'OR',
								'True' => 'True',
							),
							'CHILDREN' => $arList,
						);
					}
				}
				else
				{
					$arConditions['CHILDREN'][] = array(
						'CLASS_ID' => 'CondIBElement',
						'DATA' => array(
							'logic' => 'Equal',
							'value' => current($arElementList)
						),
					);
				}
			}

			$arFields['CONDITIONS'] = $arConditions;

			if (!$boolActive)
			{
				$arFields['ACTIVE'] = 'N';
				self::$intErrors++;
				self::$arErrors[] = array(
					'ID' => $arDiscount['ID'],
					'NAME' => $arDiscount['NAME'],
					'ERROR' => GetMessage('BT_MOD_CAT_DSC_CONV_INACTIVE').' '.implode('; ', $arSrcEntity),
				);
			}

			$mxRes = $obDiscount->Update($arDiscount['ID'], $arFields);
			if (!$mxRes)
			{
				self::$intErrors++;
				$strError = '';
				if ($ex = $APPLICATION->GetException())
				{
					$strError = $ex->GetString();
				}
				if (empty($strError))
					$strError = GetMessage('BT_MOD_CAT_DSC_FORMAT_ERR');
				self::$arErrors[] = array(
					'ID' => $arDiscount['ID'],
					'NAME' => $arDiscount['NAME'],
					'ERROR' => $strError,
				);
			}
			else
			{
				$arTimeFields = array('~TIMESTAMP_X' => $DB->CharToDateFunction($arDiscount['TIMESTAMP_X'], "FULL"));
				$strUpdate = $DB->PrepareUpdate($strTableName, $arTimeFields);
				if (!empty($strUpdate))
				{
					$strQuery = "UPDATE ".$strTableName." SET ".$strUpdate." WHERE ID = ".$arDiscount['ID']." AND TYPE = ".DISCOUNT_TYPE_STANDART;
					$DB->Query($strQuery, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				}

				self::$intConverted++;
				self::$intConvertPerStep++;
			}

			if ($intMaxExecutionTime > 0 && (getmicrotime() - $startConvertTime > $intMaxExecutionTime))
				break;
		}

		CTimeZone::Enable();

		if ($intMaxExecutionTime > (2*(getmicrotime() - $startConvertTime)))
			self::$intNextConvertPerStep = $intStep*2;
		else
			self::$intNextConvertPerStep = $intStep;

		self::SaveStep();
	}

	public static function ConvertFormatDiscount($intStep = 20, $intMaxExecutionTime = 15)
	{
		global $DBType;
		global $DB;
		global $APPLICATION;

		self::InitStep();

		$intStep = intval($intStep);
		if (0 >= $intStep)
			$intStep = 20;
		$startConvertTime = getmicrotime();

		$obDiscount = new CCatalogDiscount();

		$strTableName = '';
		switch (ToUpper($DBType))
		{
			case 'MYSQL':
				$strTableName = 'b_catalog_discount';
				break;
			case 'MSSQL':
				$strTableName = 'B_CATALOG_DISCOUNT';
				break;
			case 'ORACLE':
				$strTableName = 'B_CATALOG_DISCOUNT';
				break;
		}

		if (!CCatalogDiscountConvertTmp::CreateTable())
		{
			return false;
		}

		if (0 >= self::$intLastConvertID)
			self::$intLastConvertID = CCatalogDiscountConvertTmp::GetLastID();

		CTimeZone::Disable();

		self::$boolEmptyList = true;

		$rsDiscounts = CCatalogDiscount::GetList(
			array('ID' => 'ASC'),
			array(
				'>ID' => self::$intLastConvertID,
				'TYPE' => DISCOUNT_TYPE_STANDART,
				'VERSION' => CATALOG_DISCOUNT_NEW_VERSION
			),
			false,
			array('nTopCount' => $intStep),
			array('ID', 'MODIFIED_BY', 'TIMESTAMP_X', 'CONDITIONS', 'NAME')
		);
		while ($arDiscount = $rsDiscounts->Fetch())
		{
			$mxExist = CCatalogDiscountConvertTmp::IsExistID($arDiscount['ID']);
			if (false === $mxExist)
			{
				self::$intErrors++;
				return false;
			}
			self::$boolEmptyList = false;
			if (0 < $mxExist)
			{
				self::$intConverted++;
				self::$intConvertPerStep++;
				self::$intLastConvertID = $arDiscount['ID'];
				continue;
			}
			$arFields = array();
			$arFields['MODIFIED_BY'] = $arDiscount['MODIFIED_BY'];

			$arFields['CONDITIONS'] = $arDiscount['CONDITIONS'];

			$mxRes = $obDiscount->Update($arDiscount['ID'], $arFields);
			if (!$mxRes)
			{
				self::$intErrors++;
				$strError = '';
				if ($ex = $APPLICATION->GetException())
				{
					$strError = $ex->GetString();
				}
				if (empty($strError))
					$strError = GetMessage('BT_MOD_CAT_DSC_FORMAT_ERR');
				self::$arErrors[] = array(
					'ID' => $arDiscount['ID'],
					'NAME' => $arDiscount['NAME'],
					'ERROR' => $strError,
				);
				if (!CCatalogDiscountConvertTmp::SetID($arDiscount['ID']))
				{
					return false;
				}

				self::$intConverted++;
				self::$intConvertPerStep++;
				self::$intLastConvertID = $arDiscount['ID'];
			}
			else
			{
				$arTimeFields = array('~TIMESTAMP_X' => $DB->CharToDateFunction($arDiscount['TIMESTAMP_X'], "FULL"));
				$strUpdate = $DB->PrepareUpdate($strTableName, $arTimeFields);
				if (!empty($strUpdate))
				{
					$strQuery = "UPDATE ".$strTableName." SET ".$strUpdate." WHERE ID = ".$arDiscount['ID']." AND TYPE = ".DISCOUNT_TYPE_STANDART;
					$DB->Query($strQuery, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				}
				if (!CCatalogDiscountConvertTmp::SetID($arDiscount['ID']))
				{
					return false;
				}

				self::$intConverted++;
				self::$intConvertPerStep++;
				self::$intLastConvertID = $arDiscount['ID'];
			}

			if ($intMaxExecutionTime > 0 && (getmicrotime() - $startConvertTime > $intMaxExecutionTime))
				break;

		}
		CTimeZone::Enable();

		if ($intMaxExecutionTime > (2*(getmicrotime() - $startConvertTime)))
			self::$intNextConvertPerStep = $intStep*2;
		else
			self::$intNextConvertPerStep = $intStep;

		self::SaveStep();

		return true;
	}

	public static function GetCountOld()
	{
		global $DBType;
		global $DB;

		$strSql = '';
		switch(ToUpper($DBType))
		{
			case 'MYSQL':
				$strSql = "SELECT COUNT(*) CNT FROM b_catalog_discount WHERE TYPE=".DISCOUNT_TYPE_STANDART." AND VERSION=".CATALOG_DISCOUNT_OLD_VERSION;
				break;
			case 'MSSQL':
				$strSql = "SELECT COUNT(*) CNT FROM B_CATALOG_DISCOUNT WHERE TYPE=".DISCOUNT_TYPE_STANDART." AND VERSION=".CATALOG_DISCOUNT_OLD_VERSION;
				break;
			case 'ORACLE':
				$strSql = "SELECT COUNT(*) CNT FROM B_CATALOG_DISCOUNT WHERE TYPE=".DISCOUNT_TYPE_STANDART." AND VERSION=".CATALOG_DISCOUNT_OLD_VERSION;
				break;
		}
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if (!$res)
			return 0;

		if ($row = $res->Fetch())
			return intval($row['CNT']);
	}

	public static function GetCountFormat()
	{
		if (!CCatalogDiscountConvertTmp::CreateTable())
			return false;
		return CCatalogDiscountConvertTmp::GetNeedConvert(self::$intLastConvertID);
	}

	public static function FormatComplete()
	{
		return CCatalogDiscountConvertTmp::DropTable();
	}
}
?>