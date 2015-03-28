<?php
namespace Bitrix\Crm\Recovery;
use Bitrix\Main;
class EntityRecoveryManager
{
	public static function prepareRecoveryData($entityTypeID, $entityID, array $options = null)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = intval($entityTypeID);
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentException('Is not defined', 'entityTypeID');
		}

		if(!is_int($entityID))
		{
			$entityID = intval($entityID);
		}

		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'entityID');
		}

		if(!is_array($options))
		{
			$options = array();
		}

		$item = new EntityRecoveryData();
		$item->setEntityTypeID($entityTypeID);
		$item->setEntityID($entityID);

		$userID = isset($options['USER_ID']) ? intval($options['USER_ID']) : 0;
		if($userID > 0)
		{
			$item->setUserID($userID);
		}

		if($entityTypeID === \CCrmOwnerType::Lead)
		{
			$result = \CCrmLead::GetListEx(array(), array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'), false, false, array('*', 'UF_*'));
			$fields = is_object($result) ? $result->Fetch() : null;
			if(!is_array($fields))
			{
				throw new Main\ObjectNotFoundException("The lead with ID '{$entityTypeID}' is not found");
			}
			$item->setDataItem('FIELDS', $fields);

			if(isset($fields['TITLE']))
			{
				$item->setTitle($fields['TITLE']);
			}
			if(isset($fields['ASSIGNED_BY_ID']))
			{
				$item->setResponsibleID(intval($fields['ASSIGNED_BY_ID']));
			}
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			$result = \CCrmContact::GetListEx(array(), array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'), false, false, array('*', 'UF_*'));
			$fields = is_object($result) ? $result->Fetch() : null;
			if(!is_array($fields))
			{
				throw new Main\ObjectNotFoundException("The contact with ID '{$entityTypeID}' is not found");
			}
			$item->setDataItem('FIELDS', $fields);

			$item->setTitle(\CCrmContact::GetFullName($fields, true));
			if(isset($fields['ASSIGNED_BY_ID']))
			{
				$item->setResponsibleID(intval($fields['ASSIGNED_BY_ID']));
			}
		}
		elseif($entityTypeID === \CCrmOwnerType::Company)
		{
			$result = \CCrmCompany::GetListEx(array(), array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'), false, false, array('*', 'UF_*'));
			$fields = is_object($result) ? $result->Fetch() : null;
			if(!is_array($fields))
			{
				throw new Main\ObjectNotFoundException("The company with ID '{$entityTypeID}' is not found");
			}
			$item->setDataItem('FIELDS', $fields);

			if(isset($fields['TITLE']))
			{
				$item->setTitle($fields['TITLE']);
			}
			if(isset($fields['ASSIGNED_BY_ID']))
			{
				$item->setResponsibleID(intval($fields['ASSIGNED_BY_ID']));
			}
		}
		else
		{
			throw new Main\NotSupportedException("The entity type '".\CCrmOwnerType::ResolveName($entityTypeID)."' is not supported in current context");
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
		//MULTI FIELDS -->
		$multiFieldData = array();
		$multiFieldTypes =  array(\CCrmFieldMulti::PHONE, \CCrmFieldMulti::EMAIL, \CCrmFieldMulti::WEB, \CCrmFieldMulti::IM);
		foreach($multiFieldTypes as $multiFieldType)
		{
			$result = \CCrmFieldMulti::GetListEx(
				array('ID' => 'ASC'),
				array(
					'TYPE_ID' => $multiFieldType,
					'ENTITY_ID' => $entityTypeName,
					'ELEMENT_ID' => $entityID,
					'CHECK_PERMISSIONS' => 'N'
				),
				false,
				array('nTopCount' => 50),
				array('VALUE_TYPE', 'VALUE')
			);

			if(!is_object($result))
			{
				continue;
			}

			while($multiFields = $result->Fetch())
			{
				$valueType = isset($multiFields['VALUE_TYPE']) ? $multiFields['VALUE_TYPE'] : '';
				$value = isset($multiFields['VALUE']) ? $multiFields['VALUE'] : '';
				if($value === '')
				{
					continue;
				}

				if(!isset($multiFieldData[$multiFieldType]))
				{
					$multiFieldData[$multiFieldType] = array();
				}
				$multiFieldData[$multiFieldType][] = array('VALUE_TYPE' => $valueType, 'VALUE' => $value);
			}
		}
		if(!empty($multiFieldData))
		{
			$item->setDataItem('MULTI_FIELDS', $multiFieldData);
		}
		//<-- MULTI FIELDS

		//ACTIVITIES -->
		$activityIDs = \CCrmActivity::GetBoundIDs($entityTypeID, $entityID);
		if(!empty($activityIDs))
		{
			$item->setDataItem('ACTIVITY_IDS', $activityIDs);
		}
		//<-- ACTIVITIES

		//EVENTS -->
		$eventIDs = array();
		$result = \CCrmEvent::GetListEx(
			array('EVENT_REL_ID' => 'ASC'),
			array(
				'ENTITY_TYPE' => $entityTypeName,
				'ENTITY_ID' => $entityID,
				'EVENT_TYPE' => 0,
				'CHECK_PERMISSIONS' => 'N'
			),
			false,
			false,
			array('EVENT_REL_ID')
		);

		if(is_object($result))
		{
			while($eventFields = $result->Fetch())
			{
				$eventIDs[] = intval($eventFields['EVENT_REL_ID']);
			}
		}
		if(!empty($eventIDs))
		{
			$item->setDataItem('EVENT_IDS', $eventIDs);
		}

		//<-- EVENTS
		return $item;
	}
}