<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;

class DuplicateManager
{
	/**
	* @return DuplicateCriterion
	*/
	public static function createCriterion($typeID, array $matches)
	{
		if($typeID === DuplicateIndexType::PERSON)
		{
			return DuplicatePersonCriterion::createFromMatches($matches);
		}
		elseif($typeID === DuplicateIndexType::ORGANIZATION)
		{
			return DuplicateOrganizationCriterion::createFromMatches($matches);
		}
		elseif($typeID === DuplicateIndexType::COMMUNICATION_PHONE
			|| $typeID === DuplicateIndexType::COMMUNICATION_EMAIL)
		{
			return DuplicateCommunicationCriterion::createFromMatches($matches);
		}
		else
		{
			throw new Main\NotSupportedException("Criterion type(s): '".DuplicateIndexType::resolveName($typeID)."' is not supported in current context");
		}
	}
	/**
	* @return Duplicate
	*/
	public static function createDuplicate($typeID, array $matches, $entityTypeID, $rootEntityID, $userID, $enablePermissionCheck, $enableRanking, $limit = 0)
	{
		return self::createCriterion($typeID, $matches)->createDuplicate($entityTypeID, $rootEntityID, $userID, $enablePermissionCheck, $enableRanking, $limit);
	}
	/**
	* @return DuplicateIndexBuilder
	*/
	public static function createIndexBuilder($typeID, $entityTypeID, $userID, $enablePermissionCheck = false)
	{
		return new DuplicateIndexBuilder($typeID, new DedupeParams($entityTypeID, $userID, $enablePermissionCheck));
	}
	public static function removeIndexes(array $typeIDs, $entityTypeID, $userID, $enablePermissionCheck = false)
	{
		$params = new DedupeParams($entityTypeID, $userID, $enablePermissionCheck);
		foreach($typeIDs as $typeID)
		{
			$builder = new DuplicateIndexBuilder($typeID, $params);
			$builder->remove();
		}
	}
	public static function getMatchHash($typeID, array $matches)
	{
		if($typeID === DuplicateIndexType::PERSON)
		{
			return DuplicatePersonCriterion::prepareMatchHash($matches);
		}
		elseif($typeID === DuplicateIndexType::ORGANIZATION)
		{
			return DuplicateOrganizationCriterion::prepareMatchHash($matches);
		}
		elseif($typeID === DuplicateIndexType::COMMUNICATION_EMAIL
			|| $typeID === DuplicateIndexType::COMMUNICATION_PHONE)
		{
			return DuplicateCommunicationCriterion::prepareMatchHash($matches);
		}

		throw new Main\NotSupportedException("Criterion type(s): '".DuplicateIndexType::resolveName($typeID)."' is not supported in current context");
	}
}