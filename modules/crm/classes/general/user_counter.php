<?php
class CCrmUserCounter
{
	const Undefined = 0;
	const CurrentActivies = 1;
	const CurrentCompanyActivies = 2;
	const CurrentContactActivies = 3;
	const CurrentLeadActivies = 4;
	const CurrentDealActivies = 5;
	const CurrentQuoteActivies = 6; // refresh last number in LastType constant
	const LastType = 6;

	private $userID = 0;
	private $typeID = 0;
	private $code = '';
	private $optionName = '';
	private $lastCalculatedTime = null;
	private $curValue = null;

	private static $STATUSES = array();

	function __construct($userID, $typeID)
	{
		$this->userID = intval($userID);
		$this->typeID = intval($typeID);
		$this->code = self::ResolveCode($this->typeID);
		$this->optionName = $this->code !== '' ? $this->code.'_last_calc_'.SITE_ID : '';
	}

	public static function IsTypeDefined($typeID)
	{
		if(!is_numeric($typeID))
		{
			return false;
		}

		$typeID = intval($typeID);
		return $typeID > self::Undefined && $typeID <= self::LastType;
	}

	private static function GetStatusList($id)
	{
		if(!isset(self::$STATUSES[$id]))
		{
			self::$STATUSES[$id] = CCrmStatus::GetStatus($id);
		}

		return self::$STATUSES[$id];
	}

	private static function ResolveCode($typeID)
	{
		$code = '';
		if($typeID === self::CurrentActivies)
		{
			$code = 'crm_cur_act';
		}
		elseif($typeID === self::CurrentCompanyActivies)
		{
			$code = 'crm_cur_act_company';
		}
		elseif($typeID === self::CurrentContactActivies)
		{
			$code = 'crm_cur_act_contact';
		}
		elseif($typeID === self::CurrentLeadActivies)
		{
			$code = 'crm_cur_act_lead';
		}
		elseif($typeID === self::CurrentDealActivies)
		{
			$code = 'crm_cur_act_deal';
		}
		elseif($typeID === self::CurrentQuoteActivies)
		{
			$code = 'crm_cur_act_quote';
		}
		return $code;
	}

	public function GetValue($forceSync = false)
	{
		if($this->curValue !== null)
		{
			return $this->curValue;
		}

		if($this->code === '')
		{
			return 0;
		}

		$this->curValue = CUserCounter::GetValue($this->userID, $this->code, SITE_ID);

		if(!$this->CheckLastCalculatedTime() || $forceSync)
		{
			$this->Synchronize();
		}

		return $this->curValue;
	}
	public function Synchronize()
	{
		$currentDay = time() + CTimeZone::GetOffset();
		$currentDayEnd = ConvertTimeStamp(mktime(23, 59, 59, date('n', $currentDay), date('j', $currentDay), date('Y', $currentDay)), 'FULL', SITE_ID);

		$count = 0;
		if($this->typeID === self::CurrentActivies)
		{
			//Count of open user activities (start time: before tomorrow)
			$filter = array(
				'RESPONSIBLE_ID' => $this->userID,
				'COMPLETED' => 'N',
				'<=START_TIME' => $currentDayEnd
			);

			$count = CCrmActivity::GetCount($filter);
		}
		elseif($this->typeID === self::CurrentCompanyActivies)
		{
			$count = CCrmActivity::GetCurrentQuantity($this->userID, CCrmOwnerType::Company);
		}
		elseif($this->typeID === self::CurrentContactActivies)
		{
			$count = CCrmActivity::GetCurrentQuantity($this->userID, CCrmOwnerType::Contact);
		}
		elseif($this->typeID === self::CurrentLeadActivies)
		{
			$count = CCrmActivity::GetCurrentQuantity($this->userID, CCrmOwnerType::Lead);
			if(CCrmUserCounterSettings::GetValue(CCrmUserCounterSettings::ReckonActivitylessItems, true))
			{
				$leadTable = CCrmLead::TABLE_NAME;
				$activityTable = CCrmActivity::USER_ACTIVITY_TABLE_NAME;

				$statusStr = "'CONVERTED'";
				$statusCount = 1;
				$statuses = self::GetStatusList('STATUS');
				$isFound = false;
				foreach($statuses as &$status)
				{
					if(!$isFound)
					{
						$isFound = $status['STATUS_ID'] === 'CONVERTED';
					}
					else
					{
						$statusStr .= ",'{$status['STATUS_ID']}'";
						$statusCount++;
						// Foolproof
						if($statusCount === 10)
						{
							break;
						}
					}

				}
				unset($status);

				global $DBType;
				$sqlData = array(
					'FROM' => '',
					'WHERE' => "l.ASSIGNED_BY_ID = {$this->userID} AND l.STATUS_ID NOT IN({$statusStr}) AND l.ID NOT IN(SELECT a.OWNER_ID FROM {$activityTable} a WHERE a.USER_ID = 0 AND a.OWNER_TYPE_ID = 1)",
					'GROUPBY' => ''
				);

				$count += CSqlUtil::GetRowCount($sqlData, $leadTable, 'l', $DBType);
			}
		}
		elseif($this->typeID === self::CurrentDealActivies)
		{
			$count = CCrmActivity::GetCurrentQuantity($this->userID, CCrmOwnerType::Deal);
			if(CCrmUserCounterSettings::GetValue(CCrmUserCounterSettings::ReckonActivitylessItems, true))
			{
				$dealTable = CCrmDeal::TABLE_NAME;
				$activityTable = CCrmActivity::USER_ACTIVITY_TABLE_NAME;

				$stageStr = "'WON'";
				$stageCount = 1;
				$stages = self::GetStatusList('DEAL_STAGE');
				$isFound = false;
				foreach($stages as &$stage)
				{
					if(!$isFound)
					{
						$isFound = $stage['STATUS_ID'] === 'WON';
					}
					else
					{
						$stageStr .= ",'{$stage['STATUS_ID']}'";
						$stageCount++;
						// Foolproof
						if($stageCount === 10)
						{
							break;
						}
					}

				}
				unset($stage);

				global $DB;

				$dbResult = $DB->Query(
					"SELECT COUNT(d.ID) AS CNT FROM {$dealTable} d WHERE d.ASSIGNED_BY_ID = {$this->userID} AND d.STAGE_ID NOT IN({$stageStr}) AND d.ID NOT IN(SELECT a.OWNER_ID FROM {$activityTable} a WHERE a.USER_ID = 0 AND a.OWNER_TYPE_ID = 2)",
					false,
					'File: '.__FILE__.'<br/>Line: '.__LINE__
				);
				$result = $dbResult->Fetch();
				$count += is_array($result) ? intval($result['CNT']) : 0;
			}
		}
		elseif($this->typeID === self::CurrentQuoteActivies)
		{
			$count = 0;
			if(CCrmUserCounterSettings::GetValue(CCrmUserCounterSettings::ReckonActivitylessItems, true))
			{
				$quoteTable = CCrmQuote::TABLE_NAME;

				$statusStr = "'APPROVED'";
				$statusCount = 1;
				$statuses = self::GetStatusList('QUOTE_STATUS');
				$isFound = false;
				foreach($statuses as &$status)
				{
					if(!$isFound)
					{
						$isFound = $status['STATUS_ID'] === 'APPROVED';
					}
					else
					{
						$statusStr .= ",'{$status['STATUS_ID']}'";
						$statusCount++;
						// Foolproof
						if($statusCount === 10)
						{
							break;
						}
					}

				}
				unset($status);

				global $DB;

				$currentDay = time() + CTimeZone::GetOffset();
				$currentDayEnd = ConvertTimeStamp(mktime(23, 59, 59, date('n', $currentDay), date('j', $currentDay), date('Y', $currentDay)), 'FULL', SITE_ID);
				$currentDayEnd = $DB->CharToDateFunction($DB->ForSql($currentDayEnd), 'FULL');

				$dbResult = $DB->Query(
					"SELECT COUNT(q.ID) AS CNT FROM {$quoteTable} q WHERE q.ASSIGNED_BY_ID = {$this->userID} AND q.CLOSEDATE IS NOT NULL AND q.CLOSEDATE <= {$currentDayEnd} AND q.STATUS_ID NOT IN ({$statusStr})",
					false,
					'File: '.__FILE__.'<br/>Line: '.__LINE__
				);
				$result = $dbResult->Fetch();
				$count += is_array($result) ? intval($result['CNT']) : 0;
			}
		}

		if($this->curValue !== $count)
		{
			$this->curValue = $count;
			if($this->code !== '')
			{
				CUserCounter::Set($this->userID, $this->code, $this->curValue, SITE_ID);
			}
		}
		$this->RefreshLastCalculatedTime();
		return $this->curValue;
	}

	public static function IsReckoned($typeID, &$data)
	{
		$userID = isset($data['CURRENT_USER_ID']) ? intval($data['CURRENT_USER_ID']) : 0;

		$typeID = intval($typeID);
		if($typeID === self::CurrentDealActivies)
		{
			$activity = isset($data['ACTIVITY']) ? $data['ACTIVITY'] : null;
			$entity = isset($data['ENTITY']) ? $data['ENTITY'] : null;

			if(!is_array($entity))
			{
				return false;
			}

			$assignedByID = isset($entity['ASSIGNED_BY_ID']) ? $entity['ASSIGNED_BY_ID'] : 0;
			$stageID = isset($entity['STAGE_ID']) ? $entity['STAGE_ID'] : '';
			$stages = self::GetStatusList('DEAL_STAGE');

			$stageSort = 0;
			if($stageID !== '' && isset($stages[$stageID]))
			{
				$stageSort = intval($stages[$stageID]['SORT']);
			}
			$finalSort = isset($stages['WON']) ? intval($stages['WON']['SORT']) : 0;
			$isCompleted = $stageSort > 0 && $finalSort > 0 && $stageSort >= $finalSort;
			if(!is_array($activity))
			{
				return !$isCompleted && $userID === $assignedByID
					&& CCrmUserCounterSettings::GetValue(CCrmUserCounterSettings::ReckonActivitylessItems, true);
			}


			$activityResponsibleID = isset($activity['RESPONSIBLE_ID']) ? intval($activity['RESPONSIBLE_ID']) : 0;
			if($userID !== $activityResponsibleID)
			{
				return false;
			}

			$isActivityCompleted = isset($activity['IS_COMPLETED']) ? $activity['IS_COMPLETED'] : false;
			$isActivityForCurrentDay = isset($activity['IS_CURRENT_DAY']) ? $activity['IS_CURRENT_DAY'] : false;
			return !$isActivityCompleted &&  $isActivityForCurrentDay;
		}
		elseif($typeID === self::CurrentQuoteActivies)
		{
			$entity = isset($data['ENTITY']) ? $data['ENTITY'] : null;

			if(!is_array($entity) || empty($entity['CLOSEDATE']))
			{
				return false;
			}

			$assignedByID = isset($entity['ASSIGNED_BY_ID']) ? $entity['ASSIGNED_BY_ID'] : 0;
			$statusID = isset($entity['STATUS_ID']) ? $entity['STATUS_ID'] : '';
			$statuses = self::GetStatusList('QUOTE_STATUS');

			$statusSort = 0;
			if($statusID !== '' && isset($statuses[$statusID]))
			{
				$statusSort = intval($statuses[$statusID]['SORT']);
			}
			$finalSort = isset($statuses['APPROVED']) ? intval($statuses['APPROVED']['SORT']) : 0;
			$isCompleted = $statusSort > 0 && $finalSort > 0 && $statusSort >= $finalSort;

			$tsCloseDate = MakeTimeStamp($entity['CLOSEDATE']);
			$tsNow = time() + CTimeZone::GetOffset();
			$tsMax = mktime(23, 59, 59, date('m',$tsNow), date('d',$tsNow), date('Y',$tsNow));

			return (!$isCompleted && $userID === $assignedByID && $tsCloseDate <= $tsMax
				&& CCrmUserCounterSettings::GetValue(CCrmUserCounterSettings::ReckonActivitylessItems, true));
		}
		elseif($typeID === self::CurrentLeadActivies)
		{
			$activity = isset($data['ACTIVITY']) ? $data['ACTIVITY'] : null;
			$entity = isset($data['ENTITY']) ? $data['ENTITY'] : null;

			if(!is_array($entity))
			{
				return false;
			}

			$assignedByID = isset($entity['ASSIGNED_BY_ID']) ? $entity['ASSIGNED_BY_ID'] : 0;
			$statusID = isset($entity['STATUS_ID']) ? $entity['STATUS_ID'] : '';
			$statuses = self::GetStatusList('STATUS');

			$statusSort = 0;
			if($statusID !== '' && isset($statuses[$statusID]))
			{
				$statusSort = intval($statuses[$statusID]['SORT']);
			}
			$finalSort = isset($statuses['CONVERTED']) ? intval($statuses['CONVERTED']['SORT']) : 0;
			$isCompleted = $statusSort > 0 && $finalSort > 0 && $statusSort >= $finalSort;
			if(!is_array($activity))
			{
				return !$isCompleted && $userID === $assignedByID
					&& CCrmUserCounterSettings::GetValue(CCrmUserCounterSettings::ReckonActivitylessItems, true);
			}

			$activityResponsibleID = isset($activity['RESPONSIBLE_ID']) ? intval($activity['RESPONSIBLE_ID']) : 0;
			if($userID !== $activityResponsibleID)
			{
				return false;
			}

			$isActivityCompleted = isset($activity['IS_COMPLETED']) ? $activity['IS_COMPLETED'] : false;
			$isActivityForCurrentDay = isset($activity['IS_CURRENT_DAY']) ? $activity['IS_CURRENT_DAY'] : false;
			return !$isActivityCompleted &&  $isActivityForCurrentDay;
		}
		elseif($typeID === self::CurrentContactActivies || $typeID === self::CurrentCompanyActivies)
		{
			$activity = isset($data['ACTIVITY']) ? $data['ACTIVITY'] : null;
			$entity = isset($data['ENTITY']) ? $data['ENTITY'] : null;

			if(!is_array($entity) || !is_array($activity))
			{
				return false;
			}

			$activityResponsibleID = isset($activity['RESPONSIBLE_ID']) ? intval($activity['RESPONSIBLE_ID']) : 0;
			if($userID !== $activityResponsibleID)
			{
				return false;
			}

			$isActivityCompleted = isset($activity['IS_COMPLETED']) ? $activity['IS_COMPLETED'] : false;
			$isActivityForCurrentDay = isset($activity['IS_CURRENT_DAY']) ? $activity['IS_CURRENT_DAY'] : false;
			return !$isActivityCompleted &&  $isActivityForCurrentDay;
		}
		return false;
	}

	private function RefreshLastCalculatedTime()
	{
		if($this->optionName === '')
		{
			return 0;
		}

		$current = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
		if($this->lastCalculatedTime !== $current)
		{
			$this->lastCalculatedTime = $current;
			CUserOptions::SetOption('crm', $this->optionName, $this->lastCalculatedTime, false, $this->userID);
		}
		return $this->lastCalculatedTime;
	}
	private function CheckLastCalculatedTime()
	{
		if($this->optionName === '')
		{
			return false;
		}

		$current = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
		if($this->lastCalculatedTime === null)
		{
			$this->lastCalculatedTime = CUserOptions::GetOption('crm', $this->optionName, 0, $this->userID);
		}
		return $this->lastCalculatedTime >= $current;
	}
}

class CCrmUserCounterSettings
{
	const Undefined = 0;
	const ReckonActivitylessItems = 1;

	public static function IsDefined($typeID)
	{
		$typeID = intval($typeID);
		return $typeID > self::Undefined && $typeID <= self::ReckonActivitylessItems;
	}

	private static function GetBooleanValue($settingName, $default = false)
	{
		return strtoupper(COption::GetOptionString('crm', $settingName, $default ? 'Y' : 'N')) !== 'N';
	}

	private static function SetBooleanValue($settingName, $value)
	{
		return COption::SetOptionString('crm', $settingName, $value ? 'Y' : 'N');
	}

	public static function GetValue($setting, $default)
	{
		$setting = intval($setting);
		if(!self::IsDefined($setting))
		{
			return $default;
		}

		if($setting === self::ReckonActivitylessItems)
		{
			return self::GetBooleanValue('usr_counter_reckon_items', $default);
		}

		return $default;
	}

	public static function SetValue($setting, $value)
	{
		$setting = intval($setting);
		if(!self::IsDefined($setting))
		{
			return;
		}

		if($setting === self::ReckonActivitylessItems)
		{
			self::SetBooleanValue('usr_counter_reckon_items', $value);
		}
	}
}
