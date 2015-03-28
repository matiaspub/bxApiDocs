<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;
class DuplicateEntityMatchHash
{
	public static function register($entityTypeID, $entityID, $typeID, $matchHash, $isPrimary = true)
	{
		Entity\DuplicateEntityMatchHashTable::upsert(
			array(
				'ENTITY_ID' => $entityID,
				'ENTITY_TYPE_ID' => $entityTypeID,
				'TYPE_ID' => $typeID,
				'MATCH_HASH' => $matchHash,
				'IS_PRIMARY' => $isPrimary
			)
		);

	}
	public static function unregister($entityTypeID, $entityID, $typeID, $matchHash)
	{
		Entity\DuplicateEntityMatchHashTable::delete(
			array(
				'ENTITY_ID'=> $entityID,
				'ENTITY_TYPE_ID' => $entityTypeID,
				'TYPE_ID' => $typeID,
				'MATCH_HASH' => $matchHash
			)
		);
	}
	public static function unregisterEntity($entityTypeID, $entityID, $typeID = 0)
	{
		Entity\DuplicateEntityMatchHashTable::deleteByFilter(
			array('ENTITY_ID' => $entityID, 'ENTITY_TYPE_ID' => $entityTypeID, 'TYPE_ID' => $typeID)
		);
	}
}