<?php
namespace Bitrix\Im;

class Alias
{
	const ENTITY_TYPE_USER = 'USER';
	const ENTITY_TYPE_CHAT = 'CHAT';
	const ENTITY_TYPE_OPEN_LINE = 'LINES';
	const ENTITY_TYPE_OTHER = 'OTHER';

	const CACHE_TTL = 31536000;
	const CACHE_PATH = '/bx/im/alias/';

	const FILTER_BY_ALIAS = 'alias';
	const FILTER_BY_ID = 'id';

	public static function add(array $fields)
	{
		$alias = self::prepareAlias($fields['ALIAS']);
		$entityType = $fields['ENTITY_TYPE'];
		$entityId = $fields['ENTITY_ID'];

		if (empty($entityId) || empty($entityType) || empty($alias))
		{
			return false;
		}

		$aliasData = self::get($alias);
		if ($aliasData)
			return false;

		$result = \Bitrix\Im\Model\AliasTable::add(Array(
			'ALIAS' => $alias,
			'ENTITY_TYPE' => $entityType,
			'ENTITY_ID' => $entityId,
		));
		if (!$result->isSuccess())
		{
			return false;
		}

		return $result->getId();
	}

	public static function addUnique(array $fields)
	{
		$alias = \Bitrix\Im\Alias::prepareAlias(substr(uniqid(),-6));
		$fields['ALIAS'] = $alias;

		$id = self::add($fields);
		if (!$id)
		{
			return self::addUnique($fields);
		}

		return Array(
			'ID' => $id,
			'ALIAS' => $alias
		);
	}

	public static function update($id, $fields)
	{
		$id = intval($id);
		if ($id <= 0)
			return false;

		$update = Array();
		if (isset($fields['ALIAS']))
		{
			$update['ALIAS'] = self::prepareAlias($fields['ALIAS']);
			$result = self::get($update['ALIAS']);
			if ($result)
			{
				return false;
			}
		}

		if (isset($fields['ENTITY_TYPE']))
		{
			$update['ENTITY_TYPE'] = $fields['ENTITY_TYPE'];
		}
		if (isset($fields['ENTITY_ID']))
		{
			$update['ENTITY_ID'] = $fields['ENTITY_ID'];
		}

		if (empty($update))
			return false;

		\Bitrix\Im\Model\AliasTable::update($id, $update);

		return true;
	}


	public static function delete($id, $filter = self::FILTER_BY_ID)
	{
		if ($filter == self::FILTER_BY_ALIAS)
		{
			$aliasData = self::get($id);
			if (!$aliasData)
				return false;
		}
		else
		{
			$aliasData['ID'] = intval($id);
		}

		\Bitrix\Im\Model\AliasTable::delete($aliasData['ID']);

		return true;
	}

	public static function get($alias)
	{
		$alias = self::prepareAlias($alias);
		if (empty($alias))
		{
			return false;
		}

		$orm = \Bitrix\Im\Model\AliasTable::getList(Array(
			'filter' => Array('=ALIAS' => $alias)
		));

		return $orm->fetch();
	}

	public static function prepareAlias($alias)
	{
		$alias = preg_replace("/[^\.\-0-9a-zA-Z]+/", "", $alias);
		$alias = substr($alias, 0, 255);

		return $alias;
	}
}