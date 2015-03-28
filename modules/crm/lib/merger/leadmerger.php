<?php
namespace Bitrix\Crm\Merger;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\Integrity;
use Bitrix\Crm\Recovery;
class LeadMerger extends EntityMerger
{
	private static $LANG_INCLUDED = false;
	private $entity = null;

	public function __construct($userID, $enablePermissionCheck = false)
	{
		parent::__construct(\CCrmOwnerType::Lead, $userID, $enablePermissionCheck);
	}
	protected function getEntity()
	{
		if($this->entity === null)
		{
			$this->entity = new \CCrmLead(false);
		}
		return $this->entity;
	}
	protected function getEntityFieldsInfo()
	{
		return \CCrmLead::GetFieldsInfo();
	}
	protected function getEntityUserFieldsInfo()
	{
		return \CCrmLead::GetUserFields();
	}
	protected function getEntityResponsibleID($entityID, $roleID)
	{
		$dbResult = \CCrmLead::GetListEx(
			array(),
			array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'ASSIGNED_BY_ID')
		);
		$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(!is_array($fields))
		{
			throw new EntityMergerException(\CCrmOwnerType::Lead, $entityID, $roleID, EntityMergerException::NOT_FOUND);
		}
		return isset($fields['ASSIGNED_BY_ID']) ? (int)$fields['ASSIGNED_BY_ID'] : 0;
	}
	protected function getEntityFields($entityID, $roleID)
	{
		$dbResult = \CCrmLead::GetListEx(
			array(),
			array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('*', 'UF_*')
		);
		$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(!is_array($fields))
		{
			throw new EntityMergerException(\CCrmOwnerType::Lead, $entityID, $roleID, EntityMergerException::NOT_FOUND);
		}
		return $fields;
	}
	protected function checkEntityReadPermission($entityID, $userPermissions)
	{
		return \CCrmLead::CheckReadPermission($entityID, $userPermissions);
	}
	protected function checkEntityUpdatePermission($entityID, $userPermissions)
	{
		return \CCrmLead::CheckUpdatePermission($entityID, $userPermissions);
	}
	protected function checkEntityDeletePermission($entityID, $userPermissions)
	{
		return \CCrmLead::CheckDeletePermission($entityID, $userPermissions);
	}
	protected function setupRecoveryData(Recovery\EntityRecoveryData $recoveryData, array &$fields)
	{
		if(isset($fields['TITLE']))
		{
			$recoveryData->setTitle($fields['TITLE']);
		}
		if(isset($fields['ASSIGNED_BY_ID']))
		{
			$recoveryData->setResponsibleID((int)$fields['ASSIGNED_BY_ID']);
		}
	}
	protected function updateEntity($entityID, array &$fields, $roleID)
	{
		$entity = $this->getEntity();
		if(!$entity->Update($entityID, $fields))
		{
			throw new EntityMergerException(
				\CCrmOwnerType::Lead,
				$entityID,
				$roleID,
				EntityMergerException::UPDATE_FAILED,
				'',
				0,
				new Main\SystemException($entity->LAST_ERROR)
			);
		}
	}
	protected function deleteEntity($entityID, $roleID, array $options = array())
	{
		$entity = $this->getEntity();
		if(!$entity->Delete($entityID, $options))
		{
			throw new EntityMergerException(
				\CCrmOwnerType::Lead,
				$entityID,
				$roleID,
				EntityMergerException::DELETE_FAILED,
				'',
				0,
				new Main\SystemException($entity->LAST_ERROR)
			);
		}
	}
	protected function rebind($seedID, $targID)
	{
		\CCrmActivity::Rebind(\CCrmOwnerType::Lead, $seedID, $targID);
		\CCrmLiveFeed::Rebind(\CCrmOwnerType::Lead, $seedID, $targID);
		\CCrmSonetRelation::RebindRelations(\CCrmOwnerType::Lead, $seedID, $targID);
		\CCrmEvent::Rebind(\CCrmOwnerType::Lead, $seedID, $targID);
	}
	protected function resolveMergeCollisions($seedID, $targID, array &$results)
	{
		$dbResult = \CCrmLead::GetListEx(array(), array('=ID' => $seedID), false, false, array('ORIGINATOR_ID', 'ORIGIN_ID'));
		$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(!is_array($fields))
		{
			return;
		}

		$originatorID = isset($fields['ORIGINATOR_ID']) ? $fields['ORIGINATOR_ID'] : '';
		$originID = isset($fields['ORIGIN_ID']) ? $fields['ORIGIN_ID'] : '';
		if($originatorID !== '' || $originID !== '')
		{
			$results[EntityMergeCollision::SEED_EXTERNAL_OWNERSHIP] = new EntityMergeCollision(\CCrmOwnerType::Lead, $seedID, $targID, EntityMergeCollision::SEED_EXTERNAL_OWNERSHIP);
		}
	}
	protected function prepareCollisionMessageFields(array &$collisions, array &$seed, array &$targ)
	{
		self::includeLangFile();

		$replacements = array(
			'#USER_NAME#' => $this->getUserName(),
			'#SEED_TITLE#' => isset($seed['TITLE']) ? $seed['TITLE'] : '',
			'#SEED_ID#' => isset($seed['ID']) ? $seed['ID'] : '',
			'#TARG_TITLE#' => isset($targ['TITLE']) ? $targ['TITLE'] : '',
			'#TARG_ID#' => isset($targ['ID']) ? $targ['ID'] : '',
		);

		$messages = array();
		if(isset($collisions[EntityMergeCollision::READ_PERMISSION_LACK])
			&& isset($collisions[EntityMergeCollision::UPDATE_PERMISSION_LACK]))
		{
			$messages[] = GetMessage('CRM_LEAD_MERGER_COLLISION_READ_UPDATE_PERMISSION', $replacements);
		}
		elseif(isset($collisions[EntityMergeCollision::READ_PERMISSION_LACK]))
		{
			$messages[] = GetMessage('CRM_LEAD_MERGER_COLLISION_READ_PERMISSION', $replacements);
		}
		elseif(isset($collisions[EntityMergeCollision::UPDATE_PERMISSION_LACK]))
		{
			$messages[] = GetMessage('CRM_LEAD_MERGER_COLLISION_UPDATE_PERMISSION', $replacements);
		}

		if(empty($messages))
		{
			return null;
		}

		$html = implode('<br/>', $messages);
		return array(
			'TO_USER_ID' => isset($seed['ASSIGNED_BY_ID']) ? (int)$seed['ASSIGNED_BY_ID'] : 0,
			'NOTIFY_MESSAGE' => $html,
			'NOTIFY_MESSAGE_OUT' => $html
		);
	}
	private static function includeLangFile()
	{
		if(!self::$LANG_INCLUDED)
		{
			self::$LANG_INCLUDED = IncludeModuleLangFile(__FILE__);
		}
	}
}