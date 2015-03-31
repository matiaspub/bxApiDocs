<?php
class CCrmActivity extends CAllCrmActivity
{
	const TABLE_NAME = 'b_crm_act';
	const BINDING_TABLE_NAME = 'b_crm_act_bind';
	const COMMUNICATION_TABLE_NAME = 'b_crm_act_comm';
	const ELEMENT_TABLE_NAME = 'b_crm_act_elem';
	const USER_ACTIVITY_TABLE_NAME = 'b_crm_usr_act';
	const FIELD_MULTI_TABLE_NAME = 'b_crm_field_multi';
	const DB_TYPE = 'MYSQL';

	public static function DoSaveBindings($ID, &$arBindings)
	{
		global $DB;

		$ID = intval($ID);
		if($ID <= 0 || !is_array($arBindings))
		{
			self::RegisterError(array('text' => 'Invalid arguments are supplied.'));
			return false;
		}

		if(!is_array($arPresentComms = self::GetBindings($ID)))
		{
			self::RegisterError(array('text' => self::GetLastErrorMessage()));
			return false;
		}

		$ar2Add = array();
		$ar2Delete = array();
		self::PrepareAssociationsSave($arBindings, $arPresentComms, $ar2Add, $ar2Delete);

		if($ID > 0)
		{
			self::DeleteBindings($ID);
		}

		if(count($arBindings) == 0)
		{
			return true;
		}

		$bulkColumns = '';
		$bulkValues = array();

		foreach($arBindings as &$arBinding)
		{
			if(isset($arBinding['ID']))
			{
				unset($arBinding['ID']);
			}

			$data = $DB->PrepareInsert(self::BINDING_TABLE_NAME, $arBinding);
			if(strlen($bulkColumns) == 0)
			{
				$bulkColumns = $data[0];
			}

			$bulkValues[] = $data[1];
		}
		unset($arComm);

		if(count($bulkValues) == 0)
		{
			self::RegisterError(array('text' => 'There are no values for insert.'));
			return false;
		}

		$query = '';
		foreach($bulkValues as &$value)
		{
			if($query !== '')
			{
				$query .= ',';
			}

			$query .= "($value)";
		}

		if(strlen($query) == 0)
		{
			self::RegisterError(array('text' => 'Could not build query.'));
			return false;
		}

		$DB->Query(
			'INSERT INTO '.self::BINDING_TABLE_NAME.'('.$bulkColumns.') VALUES'.$query,
			false,
			'File: '.__FILE__.'<br/>Line: '.__LINE__
		);
		return true;
	}
	public static function PrepareBindingsFilterSql(&$arBindings, $tableAlias = '')
	{
		if(!is_array($arBindings))
		{
			return '';
		}

		$qty = count($arBindings);
		if($qty === 0)
		{
			return '';
		}

		$tableAlias = strval($tableAlias);
		if($tableAlias === '')
		{
			$tableAlias = CAllCrmActivity::TABLE_ALIAS;
		}

		$bindingTableName = self::BINDING_TABLE_NAME;
		$sql = '';

		if($qty === 1)
		{
			$binding = $arBindings[0];
			$ownerTypeID = isset($binding['OWNER_TYPE_ID']) ? intval($binding['OWNER_TYPE_ID']) : 0;
			if($ownerTypeID > 0)
			{
				$sql = "B.OWNER_TYPE_ID = {$ownerTypeID}";
				$ownerID = isset($binding['OWNER_ID']) ? intval($binding['OWNER_ID']) : 0;
				if($ownerID > 0)
				{
					$sql .= " AND B.OWNER_ID = {$ownerID}";
				}
			}
			return $sql !== '' ? "INNER JOIN {$bindingTableName} B ON B.ACTIVITY_ID = {$tableAlias}.ID AND {$sql}" : '';
		}
		else
		{
			foreach($arBindings as &$binding)
			{
				$ownerTypeID = isset($binding['OWNER_TYPE_ID']) ? intval($binding['OWNER_TYPE_ID']) : 0;
				if($ownerTypeID <= 0)
				{
					continue;
				}

				$s = "B.OWNER_TYPE_ID = {$ownerTypeID}";
				$ownerID = isset($binding['OWNER_ID']) ? intval($binding['OWNER_ID']) : 0;
				if($ownerID > 0)
				{
					$s .= " AND B.OWNER_ID = {$ownerID}";
				}

				if($sql !== '')
				{
					$sql .= ' OR ';
				}

				$sql .= "({$s})";
			}
			unset($binding);
			return $sql !== '' ? "INNER JOIN {$bindingTableName} B ON B.ACTIVITY_ID = {$tableAlias}.ID AND ({$sql})" : '';
		}
	}
	public static function DoSaveCommunications($ID, &$arComms, $arFields = array(), $registerEvents = true, $checkPerms = true)
	{
		global $DB;

		$ID = intval($ID);
		if($ID <= 0 || !is_array($arComms))
		{
			self::RegisterError(array('text' => 'Invalid arguments are supplied.'));
			return false;
		}

		if(!is_array($arPresentComms = self::GetCommunications($ID)))
		{
			self::RegisterError(array('text' => self::GetLastErrorMessage()));
			return false;
		}

		$ar2Delete = array();
		$ar2Add = array();
		foreach($arComms as $arComm)
		{
			$commID = isset($arComm['ID']) ? intval($arComm['ID']) : 0;
			if($commID <= 0)
			{
				$ar2Add[] = $arComm;
				continue;
			}
		}

		foreach($arPresentComms as $arPresentComm)
		{
			$presentCommID = intval($arPresentComm['ID']);
			$found = false;
			foreach($arComms as $arComm)
			{
				$commID = isset($arComm['ID']) ? intval($arComm['ID']) : 0;
				if($commID === $presentCommID)
				{
					$found = true;
					break;
				}
			}

			if(!$found)
			{
				$ar2Delete[] = $arPresentComm;
			}
		}


		if($ID > 0)
		{
			self::DeleteCommunications($ID);
		}

		if($registerEvents)
		{
			foreach($ar2Delete as $arComm)
			{
				self::RegisterCommunicationEvent(
					$ID,
					$arFields,
					$arComm,
					'REMOVE',
					$checkPerms
				);
			}
		}

		if(count($arComms) == 0)
		{
			return true;
		}

		$bulkColumns = '';
		$bulkValues = array();

		foreach($arComms as &$arComm)
		{
			if(isset($arComm['ID']))
			{
				unset($arComm['ID']);
			}

			$data = $DB->PrepareInsert(self::COMMUNICATION_TABLE_NAME, $arComm);
			if(strlen($bulkColumns) == 0)
			{
				$bulkColumns = $data[0];
			}

			$bulkValues[] = $data[1];
		}
		unset($arComm);

		if(count($bulkValues) == 0)
		{
			self::RegisterError(array('text' => 'There are no values for insert.'));
			return false;
		}

		$query = '';
		foreach($bulkValues as &$value)
		{
			if($query !== '')
			{
				$query .= ',';
			}

			$query .= "($value)";
		}

		if(strlen($query) == 0)
		{
			self::RegisterError(array('text' => 'Could not build query.'));
			return false;
		}

		$DB->Query(
			'INSERT INTO '.self::COMMUNICATION_TABLE_NAME.'('.$bulkColumns.') VALUES'.$query,
			false,
			'File: '.__FILE__.'<br/>Line: '.__LINE__
		);

		if($registerEvents)
		{
			foreach($ar2Add as $arComm)
			{
				self::RegisterCommunicationEvent(
					$ID,
					$arFields,
					$arComm,
					'ADD',
					$checkPerms
				);
			}
		}
		return true;
	}
	public static function DoSaveElementIDs($ID, $storageTypeID, $arElementIDs)
	{
		global $DB;

		$ID = intval($ID);
		$storageTypeID = intval($storageTypeID);
		if($ID <= 0 || !CCrmActivityStorageType::IsDefined($storageTypeID) || !is_array($arElementIDs))
		{
			self::RegisterError(array('text' => 'Invalid arguments are supplied.'));
			return false;
		}

		$DB->Query(
			'DELETE FROM '.self::ELEMENT_TABLE_NAME.' WHERE ACTIVITY_ID = '.$ID,
			false,
			'File: '.__FILE__.'<br/>Line: '.__LINE__
		);

		if(empty($arElementIDs))
		{
			return true;
		}

		$arRows = array();
		foreach($arElementIDs as $elementID)
		{
			$arRows[] = array(
				'ACTIVITY_ID'=> $ID,
				'STORAGE_TYPE_ID' => $storageTypeID,
				'ELEMENT_ID' => $elementID
			);
		}

		$bulkColumns = '';
		$bulkValues = array();


		foreach($arRows as &$row)
		{
			$data = $DB->PrepareInsert(self::ELEMENT_TABLE_NAME, $row);
			if($bulkColumns === '')
			{
				$bulkColumns = $data[0];
			}

			$bulkValues[] = $data[1];
		}
		unset($row);

		$query = '';
		foreach($bulkValues as &$value)
		{
			$query .= ($query !== '' ? ',' : '').'('.$value.')';
		}

		if($query !== '')
		{
			$sql = 'INSERT INTO '.self::ELEMENT_TABLE_NAME.'('.$bulkColumns.') VALUES '.$query.' ON DUPLICATE KEY UPDATE ELEMENT_ID = ELEMENT_ID, STORAGE_TYPE_ID = STORAGE_TYPE_ID, ACTIVITY_ID = ACTIVITY_ID';
			$DB->Query($sql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
		}

		return true;
	}
	public static function DoSaveNearestUserActivity($arFields)
	{
		global $DB;
		$userID = isset($arFields['USER_ID']) ? intval($arFields['USER_ID']) : 0;
		$ownerID = isset($arFields['OWNER_ID']) ? intval($arFields['OWNER_ID']) : 0;
		$ownerTypeID = isset($arFields['OWNER_TYPE_ID']) ? intval($arFields['OWNER_TYPE_ID']) : 0;
		$activityID = isset($arFields['ACTIVITY_ID']) ? intval($arFields['ACTIVITY_ID']) : 0;
		$activityTime = isset($arFields['ACTIVITY_TIME']) ? $arFields['ACTIVITY_TIME'] : '';
		if($activityTime !== '')
		{
			$activityTime = $DB->CharToDateFunction($DB->ForSql($activityTime), 'FULL');
		}
		$sort = isset($arFields['SORT']) ? $arFields['SORT'] : '';

		$sql = "INSERT INTO b_crm_usr_act(USER_ID, OWNER_ID, OWNER_TYPE_ID, ACTIVITY_TIME, ACTIVITY_ID, SORT, DEPARTMENT_ID)
			VALUES({$userID}, {$ownerID}, {$ownerTypeID}, {$activityTime}, {$activityID}, '{$sort}', 0)
			ON DUPLICATE KEY UPDATE ACTIVITY_TIME = {$activityTime}, ACTIVITY_ID = {$activityID}, SORT = '{$sort}'";

		$DB->Query($sql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
	}
}
