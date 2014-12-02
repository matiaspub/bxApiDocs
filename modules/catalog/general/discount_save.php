<?
IncludeModuleLangFile(__FILE__);

class CAllCatalogDiscountSave
{
	const ENTITY_ID = 1;

	const TYPE_PERCENT = 'P';
	const TYPE_FIX = 'F';

	const COUNT_TIME_ALL = 'U';
	const COUNT_TIME_INTERVAL = 'D';
	const COUNT_TIME_PERIOD = 'P';

	const ACTION_TIME_ALL = 'U';
	const ACTION_TIME_INTERVAL = 'D';
	const ACTION_TIME_PERIOD = 'P';

	static protected $intDisable = 0;
	static protected $intDiscountUserID = 0;

	public static function Enable()
	{
		self::$intDisable++;
	}

	public static function Disable()
	{
		self::$intDisable--;
	}

	public static function IsEnabled()
	{
		return (0 <= self::$intDisable);
	}

	public static function SetDiscountUserID($intUserID)
	{
		$intUserID = (int)$intUserID;
		if ($intUserID > 0)
			self::$intDiscountUserID = $intUserID;
	}

	public static function ClearDiscountUserID()
	{
		self::$intDiscountUserID = 0;
	}

	public static function GetDiscountUserID()
	{
		return self::$intDiscountUserID;
	}

	public static function GetDiscountSaveTypes($boolFull = false)
	{
		$boolFull = ($boolFull === true);
		if ($boolFull)
		{
			return array(
				self::TYPE_PERCENT => GetMessage('BT_CAT_CAT_DSC_SV_TYPE_PERCENT'),
				self::TYPE_FIX => GetMessage('BT_CAT_CAT_DSC_SV_TYPE_FIX')
			);
		}
		return array(
			self::TYPE_PERCENT,
			self::TYPE_FIX
		);
	}

	static public function CheckFields($strAction, &$arFields, $intID = 0)
	{
		global $APPLICATION;
		global $DB;
		global $USER;

		$strAction = strtoupper($strAction);
		if ('UPDATE' != $strAction && 'ADD' != $strAction)
			return false;
		$intID = (int)$intID;

		$arCurrencyList = array();
		$by = 'sort';
		$order = 'asc';
		$rsCurrencies = CCurrency::GetList($by, $order);
		while ($arCurrency = $rsCurrencies->Fetch())
		{
			$arCurrencyList[] = $arCurrency['CURRENCY'];
		}

		$boolResult = true;
		$arMsg = array();

		if (array_key_exists('ID',$arFields))
			unset($arFields['ID']);
		if ((is_set($arFields, "SITE_ID") || $strAction=="ADD") && empty($arFields["SITE_ID"]))
		{
			$arMsg[] = array('id' => 'SITE_ID', 'text' => GetMessage('BT_MOD_CAT_DSC_SV_ERR_EMPTY_SITE'));
			$boolResult = false;
		}
		else
		{
			$rsSites = CSite::GetByID($arFields['SITE_ID']);
			if (!$arSite = $rsSites->Fetch())
			{
				$arMsg[] = array('id' => 'SITE_ID','text' => GetMessage('BT_MOD_CAT_DSC_SV_ERR_BAD_SITE'));
				$boolResult = false;
			}
		}

		if ((is_set($arFields, "NAME") || $strAction=="ADD") && (strlen(trim($arFields["NAME"])) <= 0))
		{
			$arMsg[] = array('id' => 'NAME', 'text' => GetMessage('BT_MOD_CAT_DSC_SV_ERR_EMPTY_NAME'));
			$boolResult = false;
		}
		if ((is_set($arFields, "ACTIVE") || $strAction=="ADD") && $arFields["ACTIVE"] != "N")
			$arFields["ACTIVE"] = "Y";
		if ((is_set($arFields,'SORT') || $strAction == 'ADD') && intval($arFields['SORT']) <= 0)
			$arFields['SORT'] = 500;
		if ((is_set($arFields, "CURRENCY") || $strAction=="ADD") && empty($arFields["CURRENCY"]))
		{
			$arMsg[] = array('id' => 'CURRENCY', 'text' => GetMessage('BT_MOD_CAT_DSC_SV_ERR_EMPTY_CURRENCY'));
			$boolResult = false;
		}
		elseif (!in_array($arFields['CURRENCY'],$arCurrencyList))
		{
			$arMsg[] = array('id' => 'CURRENCY', 'text' => GetMessage('BT_MOD_CAT_DSC_SV_ERR_BAD_CURRENCY'));
			$boolResult = false;
		}
		if ((is_set($arFields, "ACTIVE_FROM") || $strAction=="ADD") && (!$DB->IsDate($arFields["ACTIVE_FROM"], false, LANGUAGE_ID, "FULL")))
			$arFields["ACTIVE_FROM"] = false;
		if ((is_set($arFields, "ACTIVE_TO") || $strAction=="ADD") && (!$DB->IsDate($arFields["ACTIVE_TO"], false, LANGUAGE_ID, "FULL")))
			$arFields["ACTIVE_TO"] = false;

		if ((is_set($arFields,'COUNT_SIZE') || $strAction == 'ADD') && intval($arFields['COUNT_SIZE']) < 0)
			$arFields['COUNT_SIZE'] = 0;
		if ((is_set($arFields,'COUNT_TYPE') || $strAction == 'ADD') && !in_array($arFields['COUNT_TYPE'],array('D','M','Y')))
			$arFields['COUNT_TYPE'] = 'Y';
		if ((is_set($arFields, "COUNT_FROM") || $strAction=="ADD") && (!$DB->IsDate($arFields["COUNT_FROM"], false, LANGUAGE_ID, "FULL")))
			$arFields["COUNT_FROM"] = false;
		if ((is_set($arFields, "COUNT_TO") || $strAction=="ADD") && (!$DB->IsDate($arFields["COUNT_TO"], false, LANGUAGE_ID, "FULL")))
			$arFields["COUNT_TO"] = false;

		if (is_set($arFields,'COUNT_PERIOD'))
			unset($arFields['COUNT_PERIOD']);
		$strCountPeriod = self::COUNT_TIME_ALL;
		if (is_set($arFields,'COUNT_SIZE') && intval($arFields['COUNT_SIZE']) > 0)
			$strCountPeriod = self::COUNT_TIME_PERIOD;
		if (!empty($arFields["COUNT_FROM"]) || !empty($arFields["COUNT_TO"]))
			$strCountPeriod = self::COUNT_TIME_INTERVAL;
		$arFields['COUNT_PERIOD'] = $strCountPeriod;

		if ((is_set($arFields,'ACTION_SIZE') || $strAction == 'ADD') && intval($arFields['ACTION_SIZE']) < 0)
			$arFields['ACTION_SIZE'] = 0;
		if ((is_set($arFields,'ACTION_TYPE') || $strAction == 'ADD') && !in_array($arFields['ACTION_TYPE'],array('D','M','Y')))
			$arFields['ACTION_TYPE'] = 'Y';

		$arFields['TYPE'] = self::ENTITY_ID;
		$arFields["RENEWAL"] = 'N';
		$arFields['PRIORITY'] = 1;
		$arFields['LAST_DISCOUNT'] = 'Y';

		$intUserID = 0;
		$boolUserExist = CCatalog::IsUserExists();
		if ($boolUserExist)
			$intUserID = (int)$USER->GetID();
		$strDateFunction = $DB->GetNowFunction();
		if (array_key_exists('TIMESTAMP_X', $arFields))
			unset($arFields['TIMESTAMP_X']);
		if (array_key_exists('DATE_CREATE', $arFields))
			unset($arFields['DATE_CREATE']);
		$arFields['~TIMESTAMP_X'] = $strDateFunction;
		if ($boolUserExist)
		{
			if (!array_key_exists('MODIFIED_BY', $arFields) || intval($arFields["MODIFIED_BY"]) <= 0)
				$arFields["MODIFIED_BY"] = $intUserID;
		}
		if ('ADD' == $strAction)
		{
			$arFields['~DATE_CREATE'] = $strDateFunction;
			if ($boolUserExist)
			{
				if (!array_key_exists('CREATED_BY', $arFields) || intval($arFields["CREATED_BY"]) <= 0)
					$arFields["CREATED_BY"] = $intUserID;
			}
		}
		if ('UPDATE' == $strAction)
		{
			if (array_key_exists('CREATED_BY', $arFields))
				unset($arFields['CREATED_BY']);
		}

		if (is_set($arFields,'RANGES') || $strAction == 'ADD')
		{
			if (!is_array($arFields['RANGES']) || empty($arFields['RANGES']))
			{
				$arMsg[] = array('id' => 'RANGES', 'text' => GetMessage('BT_MOD_CAT_DSC_SV_ERR_EMPTY_RANGES'));
				$boolResult = false;
			}
			else
			{
				$boolRepeat = false;
				$arRangeList = array();
				foreach ($arFields['RANGES'] as &$arRange)
				{
					if (!is_array($arRange) || empty($arRange))
					{
						$arMsg[] = array('id' => 'RANGES','text' => GetMessage('BT_MOD_CAT_DSC_SV_ERR_BAD_RANGE'));
						$boolResult = false;
					}
					else
					{
						if (empty($arRange['TYPE']) || $arRange['TYPE'] != self::TYPE_FIX)
							$arRange['TYPE'] = self::TYPE_PERCENT;
						if (isset($arRange['VALUE']))
						{
							$arRange["VALUE"] = str_replace(",", ".", $arRange["VALUE"]);
							$arRange["VALUE"] = doubleval($arRange["VALUE"]);
							if (!(0 < $arRange["VALUE"]))
							{
								$arMsg[] = array('id' => 'RANGES','text' => GetMessage('BT_MOD_CAT_DSC_SV_ERR_BAD_RANGE_VALUE'));
								$boolResult = false;
							}
							elseif (self::TYPE_PERCENT == $arRange['TYPE'] && 100 < $arRange["VALUE"])
							{
								$arMsg[] = array('id' => 'RANGES','text' => GetMessage('BT_MOD_CAT_DSC_SV_ERR_BAD_RANGE_VALUE'));
								$boolResult = false;
							}
						}
						else
						{
							$arMsg[] = array('id' => 'RANGES','text' => GetMessage('BT_MOD_CAT_DSC_SV_ERR_BAD_RANGE_VALUE'));
							$boolResult = false;
						}

						if (isset($arRange['RANGE_FROM']))
						{
							$arRange["RANGE_FROM"] = str_replace(",", ".", $arRange["RANGE_FROM"]);
							$arRange["RANGE_FROM"] = doubleval($arRange["RANGE_FROM"]);
							if (0 > $arRange["RANGE_FROM"])
							{
								$arMsg[] = array('id' => 'RANGES','text' => GetMessage('BT_MOD_CAT_DSC_SV_ERR_BAD_RANGE_FROM'));
								$boolResult = false;
							}
							else
							{
								if (in_array($arRange["RANGE_FROM"], $arRangeList))
								{
									$boolRepeat = true;
								}
								else
								{
									$arRangeList[] = $arRange["RANGE_FROM"];
								}
							}
						}
						else
						{
							$arMsg[] = array('id' => 'RANGES','text' => GetMessage('BT_MOD_CAT_DSC_SV_ERR_BAD_RANGE_FROM'));
							$boolResult = false;
						}
					}
				}
				if (isset($arRange))
					unset($arRange);
				if ($boolRepeat)
				{
					$arMsg[] = array('id' => 'RANGES','text' => GetMessage('BT_MOD_CAT_DSC_SV_ERR_DUP_RANGE_FROM'));
					$boolResult = false;
				}
			}
		}
		if (isset($arFields['GROUP_IDS']) || $strAction == 'ADD')
		{
			if (!empty($arFields['GROUP_IDS']))
			{
				if (!is_array($arFields['GROUP_IDS']))
					$arFields['GROUP_IDS'] = array($arFields['GROUP_IDS']);
				$arValid = array();
				foreach ($arFields['GROUP_IDS'] as &$intGroupID)
				{
					$intGroupID = (int)$intGroupID;
					if (0 < $intGroupID && 2 != $intGroupID)
						$arValid[] = $intGroupID;
				}
				if (isset($intGroupID))
					unset($intGroupID);
				$arFields['GROUP_IDS'] = array_unique($arValid);
			}
			if (empty($arFields['GROUP_IDS']))
			{
				$arMsg[] = array('id' => 'GROUP_IDS','text' => GetMessage('BT_MOD_CAT_DSC_SV_ERR_EMPTY_GROUP_IDS'));
				$boolResult = false;
			}
		}
		if (!$boolResult)
		{
			$obError = new CAdminException($arMsg);
			$APPLICATION->ResetException();
			$APPLICATION->ThrowException($obError);
		}
		return $boolResult;
	}

	static public function GetByID($intID)
	{
		$intID = (int)$intID;
		if ($intID <= 0)
			return false;

		return CCatalogDiscountSave::GetList(array(), array('ID' => $intID), false, false, array());
	}

	static public function GetArrayByID($intID)
	{
		$intID = (int)$intID;
		if ($intID <= 0)
			return false;

		$rsDiscounts = CCatalogDiscountSave::GetList(array(), array('ID' => $intID), false, false, array());
		if ($arDiscount = $rsDiscounts->Fetch())
		{
			return $arDiscount;
		}
		return false;
	}

	static public function Update($intID, $arFields, $boolCalc = false)
	{
		global $DB;

		$intID = (int)$intID;
		if ($intID <= 0)
			return false;

		if (!CCatalogDiscountSave::CheckFields('UPDATE',$arFields,$intID))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_catalog_discount", $arFields);
		if (!empty($strUpdate))
		{
			$strSql = "UPDATE b_catalog_discount SET ".$strUpdate." WHERE ID = ".$intID." AND TYPE = ".self::ENTITY_ID;
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		if (!empty($arFields['RANGES']))
		{
			$DB->Query("delete from b_catalog_disc_save_range where DISCOUNT_ID = ".$intID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			foreach ($arFields['RANGES'] as &$arRange)
			{
				$arRange['DISCOUNT_ID'] = $intID;
				$arInsert = $DB->PrepareInsert("b_catalog_disc_save_range", $arRange);
				$strSql =
					"INSERT INTO b_catalog_disc_save_range(".$arInsert[0].") ".
					"VALUES(".$arInsert[1].")";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
			if (isset($arRange))
				unset($arRange);
		}

		if (!empty($arFields['GROUP_IDS']))
		{
			$DB->Query("delete from b_catalog_disc_save_group where DISCOUNT_ID = ".$intID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			foreach ($arFields['GROUP_IDS'] as &$intGroupID)
			{
				$strSql =
					"INSERT INTO b_catalog_disc_save_group(DISCOUNT_ID,GROUP_ID) VALUES(".$intID.",".$intGroupID.")";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
			if (isset($intGroupID))
				unset($intGroupID);
		}

		$boolCalc = ($boolCalc === true ? true : false);
		if ($boolCalc)
			CCatalogDiscountSave::UserDiscountCalc($intID, $arFields, false);

		return $intID;
	}

	static public function Delete($intID)
	{
		global $DB;

		$intID = (int)$intID;
		if ($intID <= 0)
			return false;

		$DB->Query("delete from b_catalog_disc_save_range where DISCOUNT_ID = ".$intID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$DB->Query("delete from b_catalog_disc_save_group where DISCOUNT_ID = ".$intID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$DB->Query("delete from b_catalog_disc_save_user where DISCOUNT_ID = ".$intID, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $DB->Query("delete from b_catalog_discount where ID = ".$intID." and TYPE = ".self::ENTITY_ID, true);
	}

/*
* @deprecated deprecated since catalog 14.5.3
*/
	protected function __ClearGroupsCache($intID = 0)
	{
		return true;
	}

/*
* @deprecated deprecated since catalog 14.5.3
*/
	protected function __AddGroupsCache($intID, $arGroups = array())
	{
		return true;
	}

/*
* @deprecated deprecated since catalog 14.5.3
*/
	protected function __UpdateGroupsCache($intID, $arGroups = array())
	{
		return true;
	}

	static public function ChangeActive($intID, $boolActive = true)
	{
		$intID = (int)$intID;
		if ($intID <= 0)
			return false;

		return CCatalogDiscountSave::Update($intID, array('ACTIVE' => ($boolActive === true ? 'Y' : 'N')), false);
	}

	static public function UserDiscountCalc($intID,$arFields = array(),$boolNew = false)
	{

	}

/*
* @deprecated deprecated since catalog 14.5.3
*/
	protected function __GetDiscountGroups($arUserGroups)
	{
		return array();
	}

	static public function GetDiscount($arParams = array(), $getAll = false)
	{
		global $DB, $USER;

		$arResult = array();

		if (!CCatalog::IsUserExists() || !$USER->IsAuthorized() || !self::IsEnabled())
			return $arResult;

		foreach (GetModuleEvents("catalog", "OnGetDiscountSave", true) as $arEvent)
		{
			$mxResult = ExecuteModuleEventEx($arEvent, $arParams);
			if ($mxResult !== true)
				return $mxResult;
		}

		if (empty($arParams) || !is_array($arParams))
			return $arResult;

		$intUserID = 0;
		$arUserGroups = array();
		$strSiteID = false;
		if (isset($arParams['USER_ID']))
			$intUserID = $arParams['USER_ID'];
		if (isset($arParams['USER_GROUPS']))
			$arUserGroups = $arParams['USER_GROUPS'];
		if (isset($arParams['SITE_ID']))
			$strSiteID = $arParams['SITE_ID'];

		if (self::GetDiscountUserID() > 0)
		{
			$intUserID = self::GetDiscountUserID();
			$arUserGroups = $USER->GetUserGroup($intUserID);
		}
		else
		{
			$intUserID = (int)$intUserID;
			if ($intUserID <= 0)
			{
				$intUserID = $USER->GetID();
				$arUserGroups = $USER->GetUserGroupArray();
			}
			else
			{
				if (empty($arUserGroups))
					$arUserGroups = $USER->GetUserGroup($intUserID);
			}
		}
		if (empty($arUserGroups) || !is_array($arUserGroups) || $intUserID <= 0)
			return $arResult;
		$key = array_search(2,$arUserGroups);
		if ($key !== false)
			unset($arUserGroups[$key]);
		if (empty($arUserGroups))
			return $arResult;
		if ($strSiteID === false)
			$strSiteID = SITE_ID;

		$arCurrentDiscountID = CCatalogDiscountSave::__GetDiscountIDByGroup($arUserGroups);
		if (isset($arParams['ID']))
		{
			CatalogClearArray($arParams['ID'], false);
			if (!empty($arParams['ID']))
			{
				$arCurrentDiscountID = array_intersect($arCurrentDiscountID, $arParams['ID']);
			}
		}

		if (!empty($arCurrentDiscountID))
		{
			$getAll = ($getAll === true);
			$intCurrentTime = getmicrotime();
			$strDate = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")), $intCurrentTime);
			$arFilter = array(
				'ID' => $arCurrentDiscountID,
				'SITE_ID' => $strSiteID,
				'TYPE' => self::ENTITY_ID,
				'ACTIVE' => 'Y',
				'+<=ACTIVE_FROM' => $strDate,
				'+>=ACTIVE_TO' => $strDate
			);
			CTimeZone::Disable();
			$rsDiscSaves = CCatalogDiscountSave::GetList(array(),$arFilter);
			CTimeZone::Enable();
			while ($arDiscSave = $rsDiscSaves->Fetch())
			{
				$arDiscSave['ACTION_SIZE'] = (int)$arDiscSave['ACTION_SIZE'];
				$arDiscSave['COUNT_SIZE'] = (int)$arDiscSave['COUNT_SIZE'];
				$arDiscSave['ACTIVE_FROM_UT'] = false;
				$arDiscSave['ACTIVE_TO_UT'] = false;
				$arDiscSave['COUNT_FROM_UT'] = false;
				$arDiscSave['COUNT_TO_UT'] = false;

				$strCountPeriod = self::COUNT_TIME_ALL;
				$strActionPeriod = self::ACTION_TIME_ALL;
				$arCountPeriodBack = array();
				$arActionPeriodBack = array();
				$arActionPeriod = array();

				$arStartDate = false;
				$arOldOrderSumm = false;
				$arOrderSumm = false;
				$boolPeriodInsert = true;

				$intCountTime = $intCurrentTime;
				$arOrderFilter = array(
					'USER_ID' => $intUserID,
					'LID' => $arDiscSave['SITE_ID'],
					'PAYED' => 'Y',
					'CANCELED' => 'N',
				);
				$arOldOrderFilter = $arOrderFilter;

				if (!empty($arDiscSave['ACTIVE_FROM']) || !empty($arDiscSave['ACTIVE_TO']))
				{
					$strActionPeriod = self::ACTION_TIME_INTERVAL;
					if (!empty($arDiscSave['ACTIVE_FROM']))
						$arDiscSave['ACTIVE_FROM_UT'] = MakeTimeStamp($arDiscSave['ACTIVE_FROM']);
					if (!empty($arDiscSave['ACTIVE_TO']))
						$arDiscSave['ACTIVE_TO_UT'] = MakeTimeStamp($arDiscSave['ACTIVE_TO']);
				}
				elseif ($arDiscSave['ACTION_SIZE'] > 0 && in_array($arDiscSave['ACTION_TYPE'], array('D','M','Y')))
				{
					$strActionPeriod = self::ACTION_TIME_PERIOD;
					$arActionPeriodBack = CCatalogDiscountSave::__GetTimeStampArray($arDiscSave['ACTION_SIZE'], $arDiscSave['ACTION_TYPE']);
					$arActionPeriod = CCatalogDiscountSave::__GetTimeStampArray($arDiscSave['ACTION_SIZE'], $arDiscSave['ACTION_TYPE'], true);
				}
				if (!empty($arDiscSave['COUNT_FROM']) || !empty($arDiscSave['COUNT_TO']))
				{
					$strCountPeriod = self::COUNT_TIME_INTERVAL;
					if (!empty($arDiscSave['COUNT_FROM']))
						$arDiscSave['COUNT_FROM_UT'] = MakeTimeStamp($arDiscSave['COUNT_FROM']);
					if (!empty($arDiscSave['COUNT_TO']))
					{
						$arDiscSave['COUNT_TO_UT'] = MakeTimeStamp($arDiscSave['COUNT_TO']);
						if ($arDiscSave['COUNT_TO_UT'] > $intCountTime)
						{
							$arDiscSave['COUNT_TO_UT'] = $intCountTime;
							$arDiscSave['COUNT_TO'] = ConvertTimeStamp($intCountTime, 'FULL');
						}
					}
				}
				elseif ($arDiscSave['COUNT_SIZE'] > 0 && in_array($arDiscSave['COUNT_TYPE'],array('D','M','Y')))
				{
					$strCountPeriod = self::COUNT_TIME_PERIOD;
					$arCountPeriodBack = CCatalogDiscountSave::__GetTimeStampArray($arDiscSave['COUNT_SIZE'], $arDiscSave['COUNT_TYPE']);
				}

				if ($strCountPeriod == self::COUNT_TIME_INTERVAL)
				{
					if (false !== $arDiscSave['COUNT_FROM_UT'])
					{
						if ($arDiscSave['COUNT_FROM_UT'] > $intCountTime)
							continue;
						if (false !== $arDiscSave['COUNT_TO_UT'] && $arDiscSave['COUNT_TO_UT'] <= $arDiscSave['COUNT_FROM_UT'])
							continue;
						if (false !== $arDiscSave['ACTIVE_TO_UT'] && $arDiscSave['COUNT_FROM_UT'] >= $arDiscSave['ACTIVE_TO_UT'])
							continue;
					}
					if (false !== $arDiscSave['COUNT_TO_UT'])
					{
						if ($strActionPeriod == self::ACTION_TIME_PERIOD && ($arDiscSave['COUNT_TO_UT'] < AddToTimeStamp($arActionPeriodBack, $intCountTime)))
							continue;
					}
				}

				if ($strActionPeriod == self::ACTION_TIME_PERIOD)
				{
					if ($strCountPeriod == self::COUNT_TIME_PERIOD)
					{
						$arStartDate = CCatalogDiscountSave::__GetUserInfoByDiscount(array(
							'DISCOUNT_ID' => $arDiscSave['ID'],
							'USER_ID' => $intUserID,
							'ACTIVE_FROM' => date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")),AddToTimeStamp($arActionPeriodBack, $intCountTime)),
						));
						if (is_array($arStartDate) && !empty($arStartDate))
						{
							$arOldOrderFilter['<DATE_INSERT'] = $arStartDate['ACTIVE_FROM_FORMAT'];
							$arOldOrderFilter['>=DATE_INSERT'] = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")), AddToTimeStamp($arCountPeriodBack, MakeTimeStamp($arStartDate['ACTIVE_FROM_FORMAT'])));

							$arOldOrderSumm = CCatalogDiscountSave::__SaleOrderSumm($arOldOrderFilter, $arDiscSave['CURRENCY']);
						}
					}
					else
					{
						$arStartDate = CCatalogDiscountSave::__GetUserInfoByDiscount(
							array(
								'DISCOUNT_ID' => $arDiscSave['ID'],
								'USER_ID' => $intUserID,
							),
							array(
								'ACTIVE_FROM' => false,
								'DELETE' => false,
							)
						);
						if (is_array($arStartDate) && !empty($arStartDate))
						{
							$intTimeStart = MakeTimeStamp($arStartDate['ACTIVE_FROM_FORMAT']);
							$intTimeFinish = MakeTimeStamp($arStartDate['ACTIVE_TO_FORMAT']);
							if (!($intTimeStart <= $intCountTime && $intTimeFinish >= $intCountTime))
							{
								continue;
							}
							else
							{
								$boolPeriodInsert = false;
							}
						}
					}
				}

				$intTimeStart = false;
				$intTimeFinish = false;

				if ($strCountPeriod == self::COUNT_TIME_INTERVAL)
				{
					$intTimeStart = (!empty($arDiscSave['COUNT_FROM']) ? $arDiscSave['COUNT_FROM'] : false);
					$intTimeFinish = (!empty($arDiscSave['COUNT_TO']) ? $arDiscSave['COUNT_TO'] : false);
				}
				elseif ($strCountPeriod == self::COUNT_TIME_PERIOD)
				{
					$intTimeStart = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")),AddToTimeStamp($arCountPeriodBack, $intCountTime));
				}
				if ($intTimeStart)
					$arOrderFilter['>=DATE_INSERT'] = $intTimeStart;
				if ($intTimeFinish)
					$arOrderFilter['<DATE_INSERT'] = $intTimeFinish;

				$arOrderSumm = CCatalogDiscountSave::__SaleOrderSumm($arOrderFilter, $arDiscSave['CURRENCY']);

				if (is_array($arOldOrderSumm) && 0 < $arOldOrderSumm['RANGE_SUMM'])
				{
					if ($arOrderSumm['RANGE_SUMM'] <= $arOldOrderSumm['RANGE_SUMM'])
					{
						$arOrderSumm = $arOldOrderSumm;
					}
					else
					{
						$arOldOrderSumm = false;
					}
				}

				$rsRanges = CCatalogDiscountSave::GetRangeByDiscount(
					array('RANGE_FROM' => 'desc'),
					array('DISCOUNT_ID' => $arDiscSave['ID'], '<=RANGE_FROM' => $arOrderSumm['RANGE_SUMM']),
					false,
					array('nTopCount' => 1)
				);
				$arRange = $rsRanges->Fetch();
				if (!empty($arRange) || $getAll)
				{
					if (!empty($arRange))
					{
						if ($strActionPeriod == self::ACTION_TIME_PERIOD)
						{
							if ($strCountPeriod == self::COUNT_TIME_PERIOD)
							{
								if (!is_array($arOldOrderSumm))
								{
									CCatalogDiscountSave::__UpdateUserInfoByDiscount(array(
										'DISCOUNT_ID' => $arDiscSave['ID'],
										'USER_ID' => $intUserID,
										'ACTIVE_FROM' => date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")),$intCountTime),
										'ACTIVE_TO' => date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")),AddToTimeStamp($arActionPeriod,$intCountTime)),
										'RANGE_FROM' => -1,
									));
								}
							}
							else
							{
								if ($boolPeriodInsert)
								{
									CCatalogDiscountSave::__UpdateUserInfoByDiscount(
										array(
											'DISCOUNT_ID' => $arDiscSave['ID'],
											'USER_ID' => $intUserID,
											'ACTIVE_FROM' => date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")),$intCountTime),
											'ACTIVE_TO' => date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")),AddToTimeStamp($arActionPeriod,$intCountTime)),
											'RANGE_FROM' => -1,
										),
										array(
											'SEARCH' => true,
											'DELETE' => false,
										)
									);
								}
							}
						}
					}

					unset($arDiscSave['ACTIVE_FROM_UT'], $arDiscSave['ACTIVE_TO_UT'], $arDiscSave['COUNT_FROM_UT'], $arDiscSave['COUNT_TO_UT']);

					$arOneResult = $arDiscSave;
					if (!empty($arRange))
					{
						$arOneResult['VALUE'] = $arRange['VALUE'];
						$arOneResult['VALUE_TYPE'] = $arRange['TYPE'];
						$arOneResult['RANGE_FROM'] = $arRange['RANGE_FROM'];
					}
					else
					{
						$arOneResult['VALUE'] = 0;
						$arOneResult['VALUE_TYPE'] = self::TYPE_PERCENT;
						$rsRanges = CCatalogDiscountSave::GetRangeByDiscount(
							array('RANGE_FROM' => 'asc'),
							array('DISCOUNT_ID' => $arDiscSave['ID']),
							false,
							array('nTopCount' => 1)
						);
						$arRange = $rsRanges->Fetch();
						$arOneResult['NEXT_RANGE_FROM'] = $arRange['RANGE_FROM'];
						$arOneResult['NEXT_VALUE'] = $arRange['VALUE'];
						$arOneResult['NEXT_VALUE_TYPE'] = $arRange['TYPE'];
					}
					$arOneResult['SUMM'] = $arOrderSumm['SUMM'];
					$arOneResult['SUMM_CURRENCY'] = $arOrderSumm['CURRENCY'];
					$arOneResult['RANGE_SUMM'] = $arOrderSumm['RANGE_SUMM'];
					$arOneResult['LAST_ORDER_DATE'] = $arOrderSumm['LAST_ORDER_DATE'];
					$arResult[] = $arOneResult;
				}
			}
		}
		return $arResult;
	}

	static public function GetPeriodTypeList($boolFull = true)
	{
		$boolFull = ($boolFull === true);
		if ($boolFull)
		{
			$arResult = array(
				'D' => GetMessage('BT_MOD_CAT_DSC_SV_MESS_PERIOD_DAY'),
				'M' => GetMessage('BT_MOD_CAT_DSC_SV_MESS_PERIOD_MONTH'),
				'Y' => GetMessage('BT_MOD_CAT_DSC_SV_MESS_PERIOD_YEAR'),
			);
		}
		else
		{
			$arResult = array('D','M','Y');
		}
		return $arResult;
	}

	protected function __SaleOrderSumm($arOrderFilter, $strCurrency)
	{
		$arOrderSumm = array(
			'SUMM' => 0,
			'CURRENCY' => '',
			'LAST_ORDER_DATE' => '',
			'TIMESTAMP' => 0,
			'RANGE_SUMM' => 0,
		);
		foreach (GetModuleEvents("catalog", "OnSaleOrderSumm", true) as $arEvent)
		{
			$mxOrderCount = ExecuteModuleEventEx($arEvent, array($arOrderFilter));
			if (!empty($mxOrderCount) && is_array($mxOrderCount))
			{
				if ($mxOrderCount['CURRENCY'] != $strCurrency)
				{
					$dblSumm = doubleval(CCurrencyRates::ConvertCurrency($mxOrderCount['PRICE'], $mxOrderCount['CURRENCY'], $strCurrency));
				}
				else
				{
					$dblSumm = doubleval($mxOrderCount['PRICE']);
				}
				$arOrderSumm['LAST_ORDER_DATE'] = $mxOrderCount['LAST_ORDER_DATE'];
				$arOrderSumm['SUMM'] = $mxOrderCount['PRICE'];
				$arOrderSumm['CURRENCY'] = $mxOrderCount['CURRENCY'];
				$arOrderSumm['TIMESTAMP'] = $mxOrderCount['TIMESTAMP'];
				$arOrderSumm['RANGE_SUMM'] = $dblSumm;
			}
			break;
		}
		return $arOrderSumm;
	}

	protected function __GetTimeStampArray($intSize, $strType, $boolDir = false)
	{
		if ('D' == $strType)
			$arTimeStamp = array('DD' => ($boolDir ? $intSize : -$intSize));
		elseif('M' == $strType)
			$arTimeStamp = array('MM' => ($boolDir ? $intSize : -$intSize));
		elseif ('Y' == $strType)
			$arTimeStamp = array('YYYY' => ($boolDir ? $intSize : -$intSize));
		else
			$arTimeStamp = array();
		return $arTimeStamp;
	}
}
?>