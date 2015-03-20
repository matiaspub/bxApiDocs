<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;
class DuplicateEntity
{
	protected $entityTypeID = 0;
	protected $entityID = 0;
	protected $criterion = null;
	protected $ranking = null;
	protected $info = null;
	protected $sort = '';

	public function __construct($entityTypeID, $entityID)
	{
		$this->entityTypeID = $entityTypeID;
		$this->entityID = $entityID;
	}
	public static function compare(DuplicateEntity $a, DuplicateEntity $b)
	{
		$result = $a->entityTypeID - $b->entityTypeID;
		if($result === 0)
		{
			$result = $a->entityID - $b->entityID;
		}
		return $result;
	}
	public static function compareByRanking(DuplicateEntity $a, DuplicateEntity $b)
	{
		return DuplicateEntityRanking::compare($a->getRanking(), $b->getRanking());
	}
	public static function compareByRankingReversed(DuplicateEntity $a, DuplicateEntity $b)
	{
		return DuplicateEntityRanking::compareReversed($a->getRanking(), $b->getRanking());
	}
	public static function compareBySortString(DuplicateEntity $a, DuplicateEntity $b)
	{
		return strcasecmp($a->sort, $b->sort);
	}
	public static function compareBySortStringReversed(DuplicateEntity $a, DuplicateEntity $b)
	{
		return (-1 * strcasecmp($a->sort, $b->sort));
	}
	public function getEntityTypeID()
	{
		return $this->entityTypeID;
	}
	public function getEntityID()
	{
		return $this->entityID;
	}
	/** @return DuplicateCriterion */
	public function getCriterion()
	{
		return $this->criterion;
	}
	public function setCriterion(DuplicateCriterion $criterion)
	{
		$this->criterion = $criterion;
	}
	public function getRanking()
	{
		if($this->ranking === null)
		{
			$this->ranking = new DuplicateEntityRanking($this);
		}
		return $this->ranking;
	}
	public function getSort()
	{
		return $this->sort;
	}
	public function setSort($sort)
	{
		$this->sort = $sort;
	}
	public function equals(DuplicateEntity $item)
	{
		return $this->entityTypeID === $item->entityTypeID
			&& $this->entityID === $item->entityID;
	}
	public function isUpdatable($userID = 0, $userPermissions = null)
	{
		if(!is_int($userID))
		{
			throw new Main\ArgumentTypeException('userID', 'integer');
		}

		if($userPermissions === null)
		{
			$userPermissions = \CCrmPerms::GetUserPermissions($userID);
		}

		return \CCrmAuthorizationHelper::CheckUpdatePermission(\CCrmOwnerType::ResolveName($this->entityTypeID), $this->entityID, $userPermissions);
	}
	public function isDeletable($userID = 0, $userPermissions = null)
	{
		if(!is_int($userID))
		{
			throw new Main\ArgumentTypeException('userID', 'integer');
		}

		if($userPermissions === null)
		{
			$userPermissions = \CCrmPerms::GetUserPermissions($userID);
		}

		return \CCrmAuthorizationHelper::CheckDeletePermission(\CCrmOwnerType::ResolveName($this->entityTypeID), $this->entityID, $userPermissions);
	}
	protected function prepareEntityInfo($reset = false)
	{
		if($reset || $this->info === null)
		{
			\CCrmOwnerType::TryGetEntityInfo($this->entityTypeID, $this->entityID, $this->info, false);
		}
	}
	public function getTitle()
	{
		if($this->info === null)
		{
			$this->prepareEntityInfo();
		}
		return $this->info['TITLE'];
	}
	public function getLegend()
	{
		if($this->info === null)
		{
			$this->prepareEntityInfo();
		}
		return $this->info['LEGEND'];
	}
	public function getShowUrl()
	{
		if($this->info === null)
		{
			$this->prepareEntityInfo();
		}
		return $this->info['SHOW_URL'];
	}
	public function getResponsibleID()
	{
		if($this->info === null)
		{
			$this->prepareEntityInfo();
		}
		return $this->info['RESPONSIBLE_ID'];
	}
	public function toArray()
	{
		return array(
			'ENTITY_TYPE_ID' => $this->entityTypeID,
			'ENTITY_ID' => $this->entityID
		);
	}
	public function __toString()
	{
		$entityTypeName = \CCrmOwnerType::ResolveName($this->entityTypeID);
		$entityCaption = \CCrmOwnerType::IsDefined($this->entityTypeID) && $this->entityID > 0
			? \CCrmOwnerType::GetCaption($this->entityTypeID, $this->entityID, false) : '';
		return "{ type: \"{$entityTypeName}\", id: {$this->entityID}, caption: \"{$entityCaption}\" }";
	}
}