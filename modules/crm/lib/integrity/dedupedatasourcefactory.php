<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;

class DedupeDataSourceFactory
{
	static public function create($typeID, DedupeParams $params)
	{
		if($typeID === DuplicateIndexType::PERSON)
		{
			return new PersonDedupeDataSource($params);
		}
		elseif($typeID === DuplicateIndexType::ORGANIZATION)
		{
			return new OrganizationDedupeDataSource($params);
		}
		elseif($typeID === DuplicateIndexType::COMMUNICATION_PHONE
			|| $typeID === DuplicateIndexType::COMMUNICATION_EMAIL)
		{
			return new CommunicationDedupeDataSource($typeID, $params);
		}
		else
		{
			throw new Main\NotSupportedException("Type: '".DuplicateIndexType::resolveName($typeID)."' is not supported in current context");
		}
	}
}