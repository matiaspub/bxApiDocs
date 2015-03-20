<?
/**
 * This class allow to manage the sale order's statuses from crm module
 */

IncludeModuleLangFile(__FILE__);

class CCrmStatusInvoice extends CCrmStatus
{
	protected static $languageID = '';

	protected static function ensureLanguageDefined()
	{
		if (empty(self::$languageID))
		{
			$arFilter = array('=LID' => SITE_ID, '=ACTIVE' => 'Y');
			if (defined("ADMIN_SECTION"))
				$arFilter = array('=DEF' => 'Y', '=ACTIVE' => 'Y');

			self::$languageID = LANGUAGE_ID;
			$arLang = \Bitrix\Main\SiteTable::getRow(
				array('filter' => $arFilter, 'select' => array('LANGUAGE_ID'), 'limit' => 1)
			);
			if (is_array($arLang) && !empty($arLang['LANGUAGE_ID']))
				self::$languageID = $arLang['LANGUAGE_ID'];

			if (empty(self::$languageID))
				self::$languageID = 'en';
		}
	}

	/**
	 * Adds new sale order status
	 * @param array $arFields Array with status properties.
	 * @return int|bool Returns the new status id or false if addition failed.
	 */
	public function Add($arFields)
	{
		if (!CModule::IncludeModule('sale'))
		{
			return false;
		}

		if(!isset($arFields['NAME']))
			return false;

		$arStatus = array(
					'ID' => self::getNewId(),
					'LANG' => self::getStatusLang($arFields['NAME'])
			);

		if(isset($arFields['SORT']))
			$arStatus['SORT'] = $arFields['SORT'];

		return CSaleStatus::Add($arStatus);
	}

	/**
	 * Updates sale order status
	 * @param int $statusId Updated status ID
	 * @param array $arFields Array with status properties.
	 * @return int|bool Returns the updated status id or false if updating failed.
	 */
	public function Update($statusId, $arFields)
	{
		if (!CModule::IncludeModule('sale'))
		{
			return false;
		}

		if(!isset($arFields['NAME']))
			return false;

		$statusId = intval($statusId);
		if ($statusId === ($statusId & 0xFF) && $statusId >= 65 && $statusId <= 90)
			$statusId = chr($statusId);
		else
			return false;

		$arStatusFields = array(
					'LANG' => self::getStatusLang($arFields['NAME'], $statusId)
			);

		if(isset($arFields['SORT']))
			$arStatusFields['SORT'] = $arFields['SORT'];

		return CSaleStatus::Update($statusId, $arStatusFields);
	}

	/**
	 * Deletes sale order status
	 * @param int $statusId Status ID
	 * @return bool Deletion success.s
	 */
	public function Delete($statusId)
	{
		if (!CModule::IncludeModule('sale'))
		{
			return false;
		}

		$statusId = intval($statusId);
		if ($statusId === ($statusId & 0xFF) && $statusId >= 65 && $statusId <= 90)
			$statusId = chr($statusId);
		else
			return false;

		$result =  CSaleStatus::Delete($statusId);

		return $result;
	}

	/**
	 * Returns statuses
	 * @param string $entityId Wich statuses we interested for?
	 * @return array Statuses list
	 */
	public static function GetStatus($entityId)
	{
		return self::getStatusList();
	}

	public static function getStatusIds($statusType)
	{
		$result = array();

		if (!in_array($statusType, array('success', 'failed', 'neutral'), true))
			return $result;

		$statuses = self::getStatusList();
		if ($statusType === 'success')
		{
			$result[] = 'P';
		}
		else if ($statusType === 'failed')
		{
			$check = false;
			foreach($statuses as $statusId => $statusInfo)
			{
				if ($check)
					$result[] = $statusId;
				if ($statusId === 'P')
					$check = true;
			}
			unset($check);
		}
		else if ($statusType === 'neutral')
		{
			foreach($statuses as $statusId => $statusInfo)
			{
				if ($statusId === 'P')
					break;
				$result[] = $statusId;
			}
		}

		return $result;
	}

	public static function isStatusFailed($statusId)
	{
		$arStatuses = self::getStatusList();
		if ($arStatuses[$statusId]['SORT'] >= $arStatuses['D']['SORT'])
			return true;
		return false;
	}

	public static function isStatusNeutral($statusId)
	{
		$arStatuses = self::getStatusList();
		if ($arStatuses[$statusId]['SORT'] < $arStatuses['P']['SORT'])
			return true;
		return false;
	}

	public static function isStatusSuccess($statusId)
	{
		return ($statusId === 'P') ? true : false;
	}

	/**
	 * Returns array with status name on all site languages
	 * @param string $name Status name
	 * @param int $statusId (optional) Status ID needed if we updating status.
	 * @return array Array of status names.
	 */
	private function getStatusLang($name, $statusId = false)
	{
		if (!CModule::IncludeModule('sale'))
		{
			return false;
		}

		$arStatusLang = array();
		$by = "sort";
		$order = "asc";
		$dbLang = CLangAdmin::GetList($by, $order, array("ACTIVE" => "Y"));

		self::ensureLanguageDefined();

		while ($arLang = $dbLang->Fetch())
		{
			$statusName = '';

			if($statusId && $arLang["LID"] != self::$languageID)
			{
				$arLangStatus = CSaleStatus::GetLangByID($statusId, $arLang["LID"]);

				if($arLangStatus && isset($arLangStatus['NAME']))
					$statusName = $arLangStatus['NAME'];
			}

			if(strlen($statusName) <=0)
				$statusName = $name;

			$arStatusLang[] = array(
									'LID' => $arLang["LID"],
									'NAME' => $statusName
								);
		}

		return $arStatusLang;
	}

	/**
	 * Returns new unique ID for sale status.
	 * @return string(1)
	 */
	private function getNewId()
	{
		do
		{
			$newId = chr(rand(65, 90)); //A-Z
		}
		while(self::isIdExist($newId));

		return $newId;
	}

	/**
	 * Checks if status with ID alredy exist
	 */
	private function isIdExist($statusId)
	{
		$statusList = self::getStatusList();

		return isset($statusList[$statusId]);
	}

	/**
	 * Returns object of CCrmStatusInvoice type.
	 * This method must be called by event: OnBeforeCrmStatusCreate from crm.config.status/component.php
	 * RegisterModuleDependences('crm', 'OnBeforeCrmStatusCreate', 'crm', 'CCrmStatusInvoice', 'createCrmStatus');
	 * @param string $entityId Wich entity created object
	 * @return CCrmStatusInvoice Status object
	 */
	public function createCrmStatus($entityId)
	{
		if($entityId != "INVOICE_STATUS")
			return false;

		return new CCrmStatusInvoice($entityId);
	}

	/**
	 * Returns status list
	 * This method must be called by event OnCrmStatusGetList from crm.config.status/component.php
	 * RegisterModuleDependences('crm', 'OnCrmStatusGetList', 'crm', 'CCrmStatusInvoice', 'getStatusList');
	 * @return array Status list
	 */
	public static function getStatusList($entityId = 'INVOICE_STATUS')
	{
		if (!CModule::IncludeModule('sale'))
		{
			return array();
		}

		if($entityId != 'INVOICE_STATUS')
			return array();

		$arStatus = array();

		self::ensureLanguageDefined();

		$res = CSaleStatus::GetList(
				array('SORT' => 'ASC'),
				array('LID' => self::$languageID), false, false, array('ID', 'SORT', 'NAME')
		);

		while ($row = $res->Fetch())
		{
			if ($row['ID'] === 'F') continue;

			$arStatus[$row['ID']] = array(
				'ID' => ord($row['ID']),
				'ENTITY_ID' => 'INVOICE_STATUS',
				'STATUS_ID' => $row['ID'],
				'NAME' => $row['NAME'],
				'NAME_INIT' => '',
				'SORT' => $row['SORT'],
				'SYSTEM' => 'N'
			);

			if (in_array($row['ID'], array('N', 'P', 'F', 'D')))
			{
				if ($row['ID'] === 'F')
					$arStatus[$row['ID']]['NAME_INIT'] = GetMessage('CRM_INVOICE_STATUS_F');
				elseif ($row['ID'] === 'D')
					$arStatus[$row['ID']]['NAME_INIT'] = GetMessage('CRM_INVOICE_STATUS_D');
				elseif ($row['ID'] === 'N')
					$arStatus[$row['ID']]['NAME_INIT'] = GetMessage('CRM_INVOICE_STATUS_N');
				elseif ($row['ID'] === 'P')
					$arStatus[$row['ID']]['NAME_INIT'] = GetMessage('CRM_INVOICE_STATUS_P');

				$arStatus[$row['ID']]['SYSTEM'] = 'Y';
			}
		}

		return $arStatus;
	}

	public static function getByID($statusID)
	{
		self::ensureLanguageDefined();
		return CSaleStatus::GetByID($statusID, self::$languageID);
	}

	/**
	 * Adds Entity type to the list
	 * Method must be called by event OnGetEntityTypes from CCrmStatus::GetEntityTypes()
	 * RegisterModuleDependences('crm', 'OnGetEntityTypes', 'crm', 'CCrmStatusInvoice', 'onGetEntityTypes');
	 * @param array $arEntityType List of the entities types
	 * @return array List of the entities types
	 */
	public function onGetEntityTypes($arEntityType)
	{
		$arEntityType['INVOICE_STATUS'] = array(
						'ID' =>'INVOICE_STATUS',
						'NAME' => GetMessage('CRM_STATUS_TYPE_INVOICE_STATUS')
		);

		return $arEntityType;
	}
}
?>