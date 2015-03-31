<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;
class Duplicate
{
	private static $LANG_INCLUDED = false;
	//protected $created = '';
	protected $rootEntityID = 0;
	protected $rootPersonName = '';
	protected $rootOrganizationTitle = '';

	/** @var DuplicateCriterion $criterion **/
	protected $criterion = null;
	/** @var DuplicateEntity[] $entities **/
	//protected $entities = null;
	/** @var array $entities **/
	protected $entityMap = null;
	protected $totalEntityCount = 0;
	protected $summary = '';
	protected $isJunk = false;
	protected $options = null;

	public function __construct(DuplicateCriterion $criterion, array $entities)
	{
		$this->setCriterion($criterion);
		$this->setEntities($entities);
	}

	public function getRootEntityID()
	{
		return $this->rootEntityID;
	}
	public function setRootEntityID($entityID)
	{
		$this->rootEntityID = $entityID;
	}
	public function getRootPersonName()
	{
		return $this->rootPersonName;
	}
	public function setRootPersonName($name)
	{
		$this->rootPersonName = $name;
	}
	public function getRootOrganizationTitle()
	{
		return $this->rootOrganizationTitle;
	}
	public function setRootOrganizationTitle($title)
	{
		$this->rootOrganizationTitle = $title;
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
	/** @return DuplicateEntity[] **/
	public function getEntitiesByType($entityTypeID)
	{
		return isset($this->entityMap[$entityTypeID])
			? array_values($this->entityMap[$entityTypeID]) : array();
	}
	public function getEntities()
	{
		$result = array();
		foreach($this->entityMap as $entities)
		{
			foreach($entities as $entity)
			{
				$result[] = $entity;
			}
		}
		return $result;
	}
	public function setEntities(array $entities)
	{
		$this->entityMap = array();
		foreach($entities as $entity)
		{
			/** @var DuplicateEntity $entity **/
			$entityTypeID = $entity->getEntityTypeID();
			if(!isset($this->entityMap[$entityTypeID]))
			{
				$this->entityMap[$entityTypeID] = array();
			}
			$this->entityMap[$entityTypeID][$entity->getEntityID()] = $entity;
		}
	}
	public static function entityCountToText($count)
	{
		self::includeLangFile();
		$count = intval($count);
		return $count.' '.\Bitrix\Crm\MessageHelper::getNumberDeclension(
			$count,
			GetMessage('CRM_DUP_ENTITY_COUNT_NOMINATIVE'),
			GetMessage('CRM_DUP_ENTITY_COUNT_GENITIVE_SINGULAR'),
			GetMessage('CRM_DUP_ENTITY_COUNT_GENITIVE_PLURAL')
		);
	}
	public function getEntityArray()
	{
		$result = array();
		foreach($this->entityMap as $entities)
		{
			/** @var DuplicateEntity $entity **/
			foreach($entities as $entity)
			{
				$result[] = $entity->toArray();
			}
		}
		return $result;
	}
	public function getEntityIDsByType($entityTypeID)
	{
		return isset($this->entityMap[$entityTypeID]) ? array_keys($this->entityMap[$entityTypeID]) : array();
	}
	public function getEntityIDs()
	{
		$result = array();
		foreach($this->entityMap as $entities)
		{
			$result = $result + array_keys($entities);
		}
		return $result;
	}
	public function addEntity(DuplicateEntity $entity)
	{
		$entityTypeID = $entity->getEntityTypeID();
		if(!isset($this->entityMap[$entityTypeID]))
		{
			$this->entityMap[$entityTypeID] = array();
		}
		$this->entityMap[$entityTypeID][$entity->getEntityID()] = $entity;
	}
	public function removeEntity(DuplicateEntity $entity)
	{
		$entityTypeID = $entity->getEntityTypeID();
		$entityID = $entity->getEntityID();
		if(isset($this->entityMap[$entityTypeID]) && $this->entityMap[$entityTypeID][$entityID])
		{
			unset($this->entityMap[$entityTypeID][$entityID]);
			if(empty($this->entityMap[$entityTypeID]))
			{
				unset($this->entityMap[$entityTypeID]);
			}
		}
	}
	/**
	 * @return DuplicateEntity
	 */
	public function findEntity($entityTypeID, $entityID)
	{
		return isset($this->entityMap[$entityTypeID]) && isset($this->entityMap[$entityTypeID][$entityID])
			? $this->entityMap[$entityTypeID][$entityID] : null;
	}
	public function getTotalEntityCount()
	{
		return $this->totalEntityCount;
	}
	public function setTotalEntityCount($value)
	{
		$this->totalEntityCount = $value;
	}
	/**
	 * @return DuplicateEntityRanking[]
	 */
	public function getAllRankings()
	{
		$result = array();
		foreach($this->entityMap as $entities)
		{
			/** @var DuplicateEntity $entity **/
			foreach($entities as $entity)
			{
				$result[] = $entity->getRanking();
			}
		}
		return $result;
	}
	public function getSummary()
	{
		return $this->summary;
	}
	public function setSummary($str)
	{
		$this->summary = $str;
	}
	public function isJunk()
	{
		return $this->isJunk;
	}
	public function markAsJunk($junk)
	{
		if(!is_bool($junk))
		{
			$junk = (bool)$junk;
		}

		$this->isJunk = $junk;
	}
	public function hasOption($name)
	{
		return is_array($this->options) && isset($this->options[$name]);
	}
	public function setOption($name, $value)
	{
		if(!is_array($this->options))
		{
			$this->options = array();
		}
		$this->options[$name] = $value;
	}
	public function getOption($name, $default = null)
	{
		return is_array($this->options) && isset($this->options[$name]) ? $this->options[$name] : $default;
	}
	private static function includeLangFile()
	{
		if(!self::$LANG_INCLUDED)
		{
			self::$LANG_INCLUDED = IncludeModuleLangFile(__FILE__);
		}
	}
}