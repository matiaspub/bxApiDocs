<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main;
use Bitrix\Currency;
use Bitrix\Catalog;

Loc::loadMessages(__FILE__);

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

	const APPLY_MODE_REPLACE = 'R';
	const APPLY_MODE_ADD = 'A';
	const APPLY_MODE_DISABLE = 'D';

	static protected $intDisable = 0;
	static protected $intDiscountUserID = 0;
	static protected $userGroups = array();
	static protected $discountFilterCache = array();
	static protected $discountResultCache = array();

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
				self::TYPE_PERCENT => Loc::getMessage('BT_CAT_CAT_DSC_SV_TYPE_PERCENT'),
				self::TYPE_FIX => Loc::getMessage('BT_CAT_CAT_DSC_SV_TYPE_FIX')
			);
		}
		return array(
			self::TYPE_PERCENT,
			self::TYPE_FIX
		);
	}

	public static function GetApplyModeList($extendedMode = false)
	{
		$extendedMode = ($extendedMode === true);
		if ($extendedMode)
		{
			return array(
				self::APPLY_MODE_REPLACE => Loc::getMessage('BX_CAT_DISCSAVE_APPLY_MODE_R'),
				self::APPLY_MODE_ADD => Loc::getMessage('BX_CAT_DISCSAVE_APPLY_MODE_A'),
				self::APPLY_MODE_DISABLE => Loc::getMessage('BX_CAT_DISCSAVE_APPLY_MODE_D')
			);
		}
		return array(
			self::APPLY_MODE_REPLACE,
			self::APPLY_MODE_ADD,
			self::APPLY_MODE_DISABLE
		);
	}

	public static function CheckFields($strAction, &$arFields, $intID = 0)
	{
		global $APPLICATION;
		global $DB;
		global $USER;

		$strAction = strtoupper($strAction);
		if ('UPDATE' != $strAction && 'ADD' != $strAction)
			return false;
		$intID = (int)$intID;

		$arCurrencyList = Currency\CurrencyManager::getCurrencyList();

		$boolResult = true;
		$arMsg = array();

		$clearFields = array(
			'ID',
			'~ID',
			'UNPACK',
			'~UNPACK',
			'~CONDITIONS',
			'CONDITIONS',
			'USE_COUPONS',
			'~USE_COUPONS',
			'HANDLERS',
			'~HANDLERS',
			'~TYPE',
			'~RENEWAL',
			'~PRIORITY',
			'~LAST_DISCOUNT',
			'~VERSION',
			'TIMESTAMP_X',
			'DATE_CREATE',
			'~DATE_CREATE',
			'~MODIFIED_BY',
			'~CREATED_BY'
		);
		if ($strAction == 'UPDATE')
			$clearFields[] = 'CREATED_BY';
		$arFields = array_filter($arFields, 'CCatalogDiscountSave::clearFields');
		foreach ($clearFields as &$fieldName)
		{
			if (isset($arFields[$fieldName]))
				unset($arFields[$fieldName]);
		}
		unset($fieldName, $clearFields);

		$arFields['TYPE'] = self::ENTITY_ID;
		$arFields["RENEWAL"] = 'N';
		$arFields['PRIORITY'] = 1;
		$arFields['LAST_DISCOUNT'] = 'N';
		$arFields['VERSION'] = Catalog\DiscountTable::ACTUAL_VERSION;

		if ((is_set($arFields, "SITE_ID") || $strAction=="ADD") && empty($arFields["SITE_ID"]))
		{
			$arMsg[] = array('id' => 'SITE_ID', 'text' => Loc::getMessage('BT_MOD_CAT_DSC_SV_ERR_EMPTY_SITE'));
			$boolResult = false;
		}
		else
		{
			$rsSites = CSite::GetByID($arFields['SITE_ID']);
			if (!$arSite = $rsSites->Fetch())
			{
				$arMsg[] = array('id' => 'SITE_ID','text' => Loc::getMessage('BT_MOD_CAT_DSC_SV_ERR_BAD_SITE'));
				$boolResult = false;
			}
		}

		if ((is_set($arFields, "NAME") || $strAction=="ADD") && (strlen(trim($arFields["NAME"])) <= 0))
		{
			$arMsg[] = array('id' => 'NAME', 'text' => Loc::getMessage('BT_MOD_CAT_DSC_SV_ERR_EMPTY_NAME'));
			$boolResult = false;
		}
		if ((is_set($arFields, "ACTIVE") || $strAction=="ADD") && $arFields["ACTIVE"] != "N")
			$arFields["ACTIVE"] = "Y";
		if ((is_set($arFields,'SORT') || $strAction == 'ADD') && intval($arFields['SORT']) <= 0)
			$arFields['SORT'] = 500;
		if ((is_set($arFields, "CURRENCY") || $strAction=="ADD") && empty($arFields["CURRENCY"]))
		{
			$arMsg[] = array('id' => 'CURRENCY', 'text' => Loc::getMessage('BT_MOD_CAT_DSC_SV_ERR_EMPTY_CURRENCY'));
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

		$intUserID = 0;
		$boolUserExist = CCatalog::IsUserExists();
		if ($boolUserExist)
			$intUserID = (int)$USER->GetID();
		$strDateFunction = $DB->GetNowFunction();
		$arFields['~TIMESTAMP_X'] = $strDateFunction;
		if ($boolUserExist)
		{
			if (!array_key_exists('MODIFIED_BY', $arFields) || (int)$arFields["MODIFIED_BY"] <= 0)
				$arFields["MODIFIED_BY"] = $intUserID;
		}
		if ('ADD' == $strAction)
		{
			$arFields['~DATE_CREATE'] = $strDateFunction;
			if ($boolUserExist)
			{
				if (!array_key_exists('CREATED_BY', $arFields) || (int)$arFields["CREATED_BY"] <= 0)
					$arFields["CREATED_BY"] = $intUserID;
			}
		}

		if (is_set($arFields,'RANGES') || $strAction == 'ADD')
		{
			if (!is_array($arFields['RANGES']) || empty($arFields['RANGES']))
			{
				$arMsg[] = array('id' => 'RANGES', 'text' => Loc::getMessage('BT_MOD_CAT_DSC_SV_ERR_EMPTY_RANGES'));
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
						$arMsg[] = array('id' => 'RANGES','text' => Loc::getMessage('BT_MOD_CAT_DSC_SV_ERR_BAD_RANGE'));
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
								$arMsg[] = array('id' => 'RANGES','text' => Loc::getMessage('BT_MOD_CAT_DSC_SV_ERR_BAD_RANGE_VALUE'));
								$boolResult = false;
							}
							elseif (self::TYPE_PERCENT == $arRange['TYPE'] && 100 < $arRange["VALUE"])
							{
								$arMsg[] = array('id' => 'RANGES','text' => Loc::getMessage('BT_MOD_CAT_DSC_SV_ERR_BAD_RANGE_VALUE'));
								$boolResult = false;
							}
						}
						else
						{
							$arMsg[] = array('id' => 'RANGES','text' => Loc::getMessage('BT_MOD_CAT_DSC_SV_ERR_BAD_RANGE_VALUE'));
							$boolResult = false;
						}

						if (isset($arRange['RANGE_FROM']))
						{
							$arRange["RANGE_FROM"] = str_replace(",", ".", $arRange["RANGE_FROM"]);
							$arRange["RANGE_FROM"] = doubleval($arRange["RANGE_FROM"]);
							if (0 > $arRange["RANGE_FROM"])
							{
								$arMsg[] = array('id' => 'RANGES','text' => Loc::getMessage('BT_MOD_CAT_DSC_SV_ERR_BAD_RANGE_FROM'));
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
							$arMsg[] = array('id' => 'RANGES','text' => Loc::getMessage('BT_MOD_CAT_DSC_SV_ERR_BAD_RANGE_FROM'));
							$boolResult = false;
						}
					}
				}
				if (isset($arRange))
					unset($arRange);
				if ($boolRepeat)
				{
					$arMsg[] = array('id' => 'RANGES','text' => Loc::getMessage('BT_MOD_CAT_DSC_SV_ERR_DUP_RANGE_FROM'));
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
				$arMsg[] = array('id' => 'GROUP_IDS','text' => Loc::getMessage('BT_MOD_CAT_DSC_SV_ERR_EMPTY_GROUP_IDS'));
				$boolResult = false;
			}
		}
		if ($boolResult)
		{
			$cond = new CCatalogCondTree();
			$boolCond = $cond->Init(BT_COND_MODE_GENERATE, BT_COND_BUILD_CATALOG, array());
			if (!$boolCond)
			{
				$boolResult = false;
			}
			else
			{
				$arFields['CONDITIONS'] = $cond->GetDefaultConditions();
				$arFields['UNPACK'] = $cond->Generate($arFields['CONDITIONS'], array());
				$arFields['CONDITIONS'] = serialize($arFields['CONDITIONS']);
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

	public static function GetByID($intID)
	{
		$intID = (int)$intID;
		if ($intID <= 0)
			return false;

		return CCatalogDiscountSave::GetList(array(), array('ID' => $intID), false, false, array());
	}

	public static function GetArrayByID($intID)
	{
		$intID = (int)$intID;
		if ($intID <= 0)
			return false;

		$rsDiscounts = CCatalogDiscountSave::GetList(array(), array('ID' => $intID), false, false, array());
		if ($arDiscount = $rsDiscounts->Fetch())
			return $arDiscount;

		return false;
	}

	public static function Delete($intID)
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
	protected static function __ClearGroupsCache($intID = 0)
	{
		return true;
	}

/*
* @deprecated deprecated since catalog 14.5.3
*/
	protected static function __AddGroupsCache($intID, $arGroups = array())
	{
		return true;
	}

/*
* @deprecated deprecated since catalog 14.5.3
*/
	protected static function __UpdateGroupsCache($intID, $arGroups = array())
	{
		return true;
	}

	public static function ChangeActive($intID, $boolActive = true)
	{
		$intID = (int)$intID;
		if ($intID <= 0)
			return false;

		return CCatalogDiscountSave::Update($intID, array('ACTIVE' => ($boolActive === true ? 'Y' : 'N')), false);
	}

	public static function UserDiscountCalc($intID,$arFields = array(),$boolNew = false)
	{

	}

/*
* @deprecated deprecated since catalog 14.5.3
*/
	protected static function __GetDiscountGroups($arUserGroups)
	{
		return array();
	}

	public static function GetDiscount($arParams = array(), $getAll = false)
	{
		global $DB, $USER;

		$adminSection = (defined('ADMIN_SECTION') && ADMIN_SECTION === true);

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
			$intUserID = (int)$arParams['USER_ID'];
		if (isset($arParams['USER_GROUPS']))
			$arUserGroups = $arParams['USER_GROUPS'];
		if (isset($arParams['SITE_ID']))
			$strSiteID = $arParams['SITE_ID'];

		if (self::GetDiscountUserID() > 0)
		{
			$intUserID = (int)self::GetDiscountUserID();
			$arUserGroups = array();
		}
		if ($intUserID <= 0 && !$adminSection)
		{
			$intUserID = (int)$USER->GetID();
			$arUserGroups = array();
		}
		if (empty($arUserGroups))
		{
			if (!isset(self::$userGroups[$intUserID]))
				self::$userGroups[$intUserID] = $USER->GetUserGroup($intUserID);
			$arUserGroups = self::$userGroups[$intUserID];
		}
		if (empty($arUserGroups) || !is_array($arUserGroups) || $intUserID <= 0)
			return $arResult;
		$key = array_search(2, $arUserGroups);
		if ($key !== false)
			unset($arUserGroups[$key]);
		if (empty($arUserGroups))
			return $arResult;
		Main\Type\Collection::normalizeArrayValuesByInt($arUserGroups, true);
		if (empty($arUserGroups))
			return $arResult;
		if ($strSiteID === false)
			$strSiteID = SITE_ID;
		$cacheKey = md5('U'.implode('_', $arUserGroups));
		if (!isset(self::$discountFilterCache[$cacheKey]))
			self::$discountFilterCache[$cacheKey] = CCatalogDiscountSave::__GetDiscountIDByGroup($arUserGroups);
		if (empty(self::$discountFilterCache[$cacheKey]))
			return $arResult;

		$arCurrentDiscountID = self::$discountFilterCache[$cacheKey];
		unset($cacheKey);
		if (isset($arParams['ID']))
		{
			Main\Type\Collection::normalizeArrayValuesByInt($arUserGroups, true);
			if (!empty($arParams['ID']))
				$arCurrentDiscountID = array_intersect($arCurrentDiscountID, $arParams['ID']);
		}

		if (!empty($arCurrentDiscountID))
		{
			$getAll = ($getAll === true);
			$cacheKey = 'DS'.implode('_', $arCurrentDiscountID).'|'.$strSiteID;
			if (!isset(self::$discountResultCache[$cacheKey]))
			{
				self::$discountResultCache[$cacheKey] = array();

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
				$rsDiscSaves = CCatalogDiscountSave::GetList(array(), $arFilter);
				CTimeZone::Enable();
				while ($arDiscSave = $rsDiscSaves->Fetch())
				{
					$arDiscSave['ACTION_SIZE'] = (int)$arDiscSave['ACTION_SIZE'];
					$arDiscSave['COUNT_SIZE'] = (int)$arDiscSave['COUNT_SIZE'];
					$arDiscSave['ACTIVE_FROM_UT'] = false;
					$arDiscSave['ACTIVE_TO_UT'] = false;
					$arDiscSave['COUNT_FROM_UT'] = false;
					$arDiscSave['COUNT_TO_UT'] = false;
					$arDiscSave['TYPE'] = (int)$arDiscSave['TYPE'];
					$arDiscSave['MODULE_ID'] = 'catalog';
					$arDiscSave['MICROTIME'] = $intCurrentTime;
					$arDiscSave['LAST_DISCOUNT'] = 'N';
					$arDiscSave['PRIORITY'] = 1;

					self::$discountResultCache[$cacheKey][] = $arDiscSave;
				}
				unset($arDiscSave, $rsDiscSaves);
				unset($intCurrentTime);
			}
			$discountList = self::$discountResultCache[$cacheKey];
			unset($cacheKey);
			foreach ($discountList as $arDiscSave)
			{
				$strCountPeriod = self::COUNT_TIME_ALL;
				$strActionPeriod = self::ACTION_TIME_ALL;
				$arCountPeriodBack = array();
				$arActionPeriodBack = array();
				$arActionPeriod = array();

				$arStartDate = false;
				$arOldOrderSumm = false;
				$arOrderSumm = false;
				$boolPeriodInsert = true;

				$intCountTime = $arDiscSave['MICROTIME'];
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
					array('RANGE_FROM' => 'DESC'),
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
					unset($arDiscSave['MICROTIME']);

					$arOneResult = $arDiscSave;
					if (!empty($arRange))
					{
						$arOneResult['VALUE'] = $arRange['VALUE'];
						$arOneResult['VALUE_TYPE'] = $arRange['TYPE'];
						$arOneResult['RANGE_FROM'] = $arRange['RANGE_FROM'];
						$arOneResult['MAX_DISCOUNT'] = 0;
					}
					else
					{
						$arOneResult['VALUE'] = 0;
						$arOneResult['VALUE_TYPE'] = self::TYPE_PERCENT;
						$arOneResult['MAX_DISCOUNT'] = 0;
						$rsRanges = CCatalogDiscountSave::GetRangeByDiscount(
							array('RANGE_FROM' => 'ASC'),
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

	public static function GetPeriodTypeList($boolFull = true)
	{
		$boolFull = ($boolFull === true);
		if ($boolFull)
		{
			$arResult = array(
				'D' => Loc::getMessage('BT_MOD_CAT_DSC_SV_MESS_PERIOD_DAY'),
				'M' => Loc::getMessage('BT_MOD_CAT_DSC_SV_MESS_PERIOD_MONTH'),
				'Y' => Loc::getMessage('BT_MOD_CAT_DSC_SV_MESS_PERIOD_YEAR'),
			);
		}
		else
		{
			$arResult = array('D','M','Y');
		}
		return $arResult;
	}

	protected static function __SaleOrderSumm($arOrderFilter, $strCurrency)
	{
		$arOrderSumm = array(
			'ORDER_FILTER' => $arOrderFilter,
			'SUMM' => 0,
			'CURRENCY' => '',
			'LAST_ORDER_DATE' => '',
			'TIMESTAMP' => 0,
			'RANGE_SUMM' => 0,
			'RANGE_SUMM_CURRENCY' => $strCurrency
		);
		foreach (GetModuleEvents('catalog', 'OnSaleOrderSumm', true) as $arEvent)
		{
			$mxOrderCount = ExecuteModuleEventEx($arEvent, array($arOrderFilter));
			if (!empty($mxOrderCount) && is_array($mxOrderCount))
			{
				$mxOrderCount['PRICE'] = (float)$mxOrderCount['PRICE'];

				$arOrderSumm['LAST_ORDER_DATE'] = $mxOrderCount['LAST_ORDER_DATE'];
				$arOrderSumm['SUMM'] = $mxOrderCount['PRICE'];
				$arOrderSumm['CURRENCY'] = $mxOrderCount['CURRENCY'];
				$arOrderSumm['TIMESTAMP'] = $mxOrderCount['TIMESTAMP'];
				$arOrderSumm['RANGE_SUMM'] = (
					$mxOrderCount['CURRENCY'] != $strCurrency
					? CCurrencyRates::ConvertCurrency($mxOrderCount['PRICE'], $mxOrderCount['CURRENCY'], $strCurrency)
					: $mxOrderCount['PRICE']
				);
				break;
			}
		}
		foreach (GetModuleEvents('catalog', 'OnSaleOrderSummResult', true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array(&$arOrderSumm));
		}
		return $arOrderSumm;
	}

	protected static function __GetTimeStampArray($intSize, $strType, $boolDir = false)
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

	protected static function clearFields($value)
	{
		return ($value !== null);
	}
}