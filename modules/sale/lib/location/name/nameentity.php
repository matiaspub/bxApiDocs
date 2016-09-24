<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale\Location\Name;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

use Bitrix\Sale\Location\Util\Assert;

Loc::loadMessages(__FILE__);

abstract class NameEntity extends Entity\DataManager
{
	public static function getLanguageFieldName()
	{
		return 'LANGUAGE_ID';
	}

	abstract public function getReferenceFieldName();

	/**
	 * Add translations for $primaryOwner
	 * @param mixed $primaryOwner Primary key of the item
	 * @param string[] $names A set of translations for the item
	 * 
	 * @return void
	 */
	public static function addMultipleForOwner($primaryOwner, $names = array())
	{
		$primaryOwner = Assert::expectIntegerPositive($primaryOwner, '$primaryOwner');

		// nothing to connect to, simply exit
		if(!is_array($names) || empty($names))
			return;

		$langField = static::getLanguageFieldName();
		$refField = static::getReferenceFieldName();

		foreach($names as $lid => $name)
		{
			$lid = Assert::castTrimLC($lid);

			$empty = true;
			foreach($name as $arg)
			{
				if(strlen($arg) > 0)
				{
					$empty = false;
					break;
				}
			}

			if(!$empty)
			{
				$res = static::add(array_merge(
					array(
						$langField => $lid,
						$refField => $primaryOwner
					),
					$name
				));
				if(!$res->isSuccess())
					throw new Main\SystemException(Loc::getMessage('SALE_LOCATION_NAME_NAME_ENTITY_CANNOT_ADD_NAMES_EXCEPTION'));
			}
		}

		$GLOBALS['CACHE_MANAGER']->ClearByTag('sale-location-data');
	}

	/**
	 * Update translations for $primaryOwner
	 * @param mixed $primaryOwner Primary key of the item
	 * @param string[] $names A set of translations for the item
	 * 
	 * @return void
	 */
	public static function updateMultipleForOwner($primaryOwner, $names)
	{
		$primaryOwner = Assert::expectIntegerPositive($primaryOwner, '$primaryOwner');

		if(!is_array($names))
			$names = array();

		$langField = static::getLanguageFieldName();
		$refField = static::getReferenceFieldName();

		// get already existed name records
		$res = static::getList(array(
			'filter' => array($refField => $primaryOwner),
			'select' => array('ID', $langField)
		));
		$existed = array();
		while($item = $res->Fetch())
			$existed[$item[$langField]] = $item['ID'];

		foreach($names as $lid => $name)
		{
			$lid = Assert::castTrimLC($lid);

			$empty = true;
			foreach($name as $arg)
			{
				if(strlen($arg) > 0)
				{
					$empty = false;
					break;
				}
			}

			if(!isset($existed[$lid]))
			{
				if(!$empty)
				{
					$res = static::add(array_merge(
						array(
							$langField => $lid,
							$refField => $primaryOwner
						),
						$name
					));
					if(!$res->isSuccess())
						throw new Main\SystemException(Loc::getMessage('SALE_LOCATION_NAME_NAME_ENTITY_CANNOT_ADD_NAMES_EXCEPTION'));
				}
			}
			else
			{
				if($empty)
				{
					$res = static::delete($existed[$lid]);
					if(!$res->isSuccess())
						throw new Main\SystemException(Loc::getMessage('SALE_LOCATION_NAME_NAME_ENTITY_CANNOT_DELETE_NAMES_EXCEPTION'));
				}
				else
				{
					$res = static::update($existed[$lid], $name);
					if(!$res->isSuccess())
						throw new Main\SystemException(Loc::getMessage('SALE_LOCATION_NAME_NAME_ENTITY_CANNOT_UPDATE_NAMES_EXCEPTION'));
				}
			}
		}
	}

	/**
	 * Delete translations for $primaryOwner
	 * @param mixed $primaryOwner Primary key of the item
	 * 
	 * @return void
	 */
	public static function deleteMultipleForOwner($primaryOwner)
	{
		$primaryOwner = Assert::expectIntegerPositive($primaryOwner, '$primaryOwner');

		// hunt existed
		$listRes = static::getList(array(
			'filter' => array(static::getReferenceFieldName() => $primaryOwner),
			'select' => array('ID')
		));

		// kill existed
		while($item = $listRes->fetch())
		{
			$res = static::delete($item['ID']);
			if(!$res->isSuccess())
				throw new Main\SystemException(Loc::getMessage('SALE_LOCATION_NAME_NAME_ENTITY_CANNOT_DELETE_NAMES_EXCEPTION'));
		}
	}

	/**
	 * Get existed translations for $primaryOwner and add only non-existed ones from $names
	 * @param mixed $primaryOwner Primary key of the item
	 * @param string[] $names A set of translations for the item
	 * 
	 * @return void
	 */
	public static function addAbsentForOwner($primaryOwner, $names, $behaviour = array('TREAT_EMPTY_AS_ABSENT' => true))
	{
		$primaryOwner = Assert::expectIntegerPositive($primaryOwner, '$primaryOwner');

		if(!is_array($names))
			$names = array();

		if(!is_array($behaviour))
			$behaviour = array();
		if(!isset($behaviour['TREAT_EMPTY_AS_ABSENT']))
			$behaviour['TREAT_EMPTY_AS_ABSENT'] = true;

		if(empty($names))
			return;

		$namesLC = array();
		foreach($names as $lid => $data)
		{
			$namesLC[Assert::castTrimLC($lid)] = $data;
		}
		$names = $namesLC;

		$langField = static::getLanguageFieldName();
		$refField = static::getReferenceFieldName();

		$names2Update = array();
		$res = static::getList(array('filter' => array('='.$refField => $primaryOwner)));
		while($item = $res->fetch())
		{
			$isEmpty = static::checkEmpty($item);

			if($isEmpty && $behaviour['TREAT_EMPTY_AS_ABSENT'])
			{
				$names2Update[$item['ID']] = $names[$item[$langField]];
			}

			unset($names[$item[$langField]]);
		}

		foreach($names as $lid => $data)
		{
			$data[$langField] = $lid;
			$data[$refField] = $primaryOwner;

			static::add($data);
		}

		foreach($names2Update as $id => $data)
		{
			static::update($id, $data);
		}
	}

	/**
	 * This method is for internal use only. It may be changed without any notification further, or even mystically disappear.
	 * 
	 * @access private
	 */
	public static function deleteMultipleByParentRangeSql($sql)
	{
		if(!strlen($sql))
			throw new Main\SystemException('Range sql is empty');

		$dbConnection = Main\HttpApplication::getConnection();

		$dbConnection->query('delete from '.static::getTableName().' where '.static::getReferenceFieldName().' in ('.$sql.')');
	}

	protected static function checkEmpty($item)
	{
		return !is_array($item) || (string) $item['NAME'] == '';
	}
}
