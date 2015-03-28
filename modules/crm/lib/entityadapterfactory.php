<?php
namespace Bitrix\Crm;
use Bitrix\Main;
class EntityAdapterFactory
{
	public static function create(array $fields = null, $sourceEntityTypeID = \CCrmOwnerType::Undefined, $destinationEntityTypeID = \CCrmOwnerType::Undefined)
	{
		if(!is_int($sourceEntityTypeID))
		{
			throw new Main\ArgumentTypeException('sourceEntityTypeID', 'integer');
		}

		if(!is_int($destinationEntityTypeID))
		{
			throw new Main\ArgumentTypeException('destinationEntityTypeID', 'integer');
		}

		if($sourceEntityTypeID !== \CCrmOwnerType::Undefined && !\CCrmOwnerType::IsDefined($sourceEntityTypeID))
		{
			$sourceEntityTypeID = \CCrmOwnerType::Undefined;
		}

		if($destinationEntityTypeID !== \CCrmOwnerType::Undefined && !\CCrmOwnerType::IsDefined($destinationEntityTypeID))
		{
			$destinationEntityTypeID = \CCrmOwnerType::Undefined;
		}

		if(\CCrmOwnerType::IsDefined($sourceEntityTypeID) && !\CCrmOwnerType::IsDefined($destinationEntityTypeID))
		{
			$destinationEntityTypeID = $sourceEntityTypeID;
		}

		$adapter = null;
		if($sourceEntityTypeID !== \CCrmOwnerType::Undefined && $destinationEntityTypeID !== \CCrmOwnerType::Undefined)
		{
			if($sourceEntityTypeID === \CCrmOwnerType::Lead)
			{
				if($destinationEntityTypeID === \CCrmOwnerType::Company)
				{
					$adapter = new EntityAdapter(
						\CCrmOwnerType::Lead,
						\CCrmOwnerType::Company,
						new Mapper(array('COMPANY_TITLE' => 'TITLE'))
					);
				}
			}
			elseif($sourceEntityTypeID === \CCrmOwnerType::Company)
			{
				if($destinationEntityTypeID === \CCrmOwnerType::Lead)
				{
					$adapter = new EntityAdapter(
						\CCrmOwnerType::Company,
						\CCrmOwnerType::Lead,
						new Mapper(array('TITLE' => 'COMPANY_TITLE'))
					);
				}
			}
		}

		if($adapter === null)
		{
			$adapter = new EntityAdapter($sourceEntityTypeID, $destinationEntityTypeID);
		}

		if($fields !== null)
		{
			$adapter->setFields($fields);
		}

		return $adapter;
	}
	public static function wrap(EntityAdapter $adapter, Mapper $mapper)
	{
		return new EntityAdapter(
			\CCrmOwnerType::Undefined,
			\CCrmOwnerType::Undefined,
			$mapper,
			$adapter
		);
	}
}