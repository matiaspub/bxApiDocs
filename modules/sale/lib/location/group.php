<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale\Location;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Config;
use Bitrix\Main\Localization\Loc;

use Bitrix\Sale\Location\Name;
use Bitrix\Sale\Location\Util\Assert;

Loc::loadMessages(__FILE__);

class GroupTable extends Entity\DataManager
{
	const PROJECT_USES_GROUPS_OPT = 'project_uses_groups';

	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_location_group';
	}

	public static function add(array $data)
	{
		if(isset($data['NAME']))
		{
			$name = $data['NAME'];
			unset($data['NAME']);
		}

		$addResult = parent::add($data);

		// add connected data
		if($addResult->isSuccess())
		{
			$primary = $addResult->getId();

			// names
			if(isset($name))
				Name\GroupTable::addMultipleForOwner($primary, $name);

			// set flag that indicates whether project still uses groups or not
			self::setGroupUsage();
		}

		return $addResult;
	}
	
	public static function update($primary, array $data)
	{
		$primary = Assert::expectIntegerPositive($primary, '$primary');

		// first update parent, and if it succeed, do updates of the connected data

		if(isset($data['NAME']))
		{
			$name = $data['NAME'];
			unset($data['NAME']);
		}

		$updResult = parent::update($primary, $data);

		// update connected data
		if($updResult->isSuccess())
		{
			// names
			if(isset($name))
				Name\GroupTable::updateMultipleForOwner($primary, $name);
		}

		return $updResult;
	}

	public static function delete($primary)
	{
		$primary = Assert::expectIntegerPositive($primary, '$primary');

		$delResult = parent::delete($primary);

		// delete connected data
		if($delResult->isSuccess())
		{
			Name\GroupTable::deleteMultipleForOwner($primary);

			// set flag that indicates whether project still uses groups or not
			self::checkGroupUsage();
		}

		return $delResult;
	}

	public static function checkGroupUsage()
	{
		$optValue = Config\Option::get("sale", self::PROJECT_USES_GROUPS_OPT, '', '');
		if(!$optValue) // option is undefined, we are not sure if there are groups or not
			return self::getGroupUsage();

		return $optValue == 'Y';
	}

	public static function getGroupUsage()
	{
		$isUsing = !!GroupTable::getList(array('limit' => 1, 'select' => array('ID')))->fetch();
		Config\Option::set("sale", self::PROJECT_USES_GROUPS_OPT, $isUsing ? 'Y' : 'N', '');

		return $isUsing;
	}

	public static function setGroupUsage()
	{
		Config\Option::set("sale", self::PROJECT_USES_GROUPS_OPT, 'Y');
	}

	public static function getCodeValidators()
	{
		return array(
			new Entity\Validator\Unique(),
		);
	}

	public static function getMap()
	{
		return array(

			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'CODE' => array(
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage('SALE_LOCATION_GROUP_ENTITY_CODE_FIELD'),
				'validation' => array(__CLASS__, 'getCodeValidators')
			),
			'SORT' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('SALE_LOCATION_GROUP_ENTITY_SORT_FIELD'),
				'default' => '100'
			),

			// virtual
			'NAME' => array(
				'data_type' => '\Bitrix\Sale\Location\Name\Group',
				'reference' => array(
					'=this.ID' => 'ref.LOCATION_GROUP_ID'
				)
			),
			'LOCATION' => array(
				'data_type' => '\Bitrix\Sale\Location\Location',
				'reference' => array(
					'=this.ID' => 'ref.LOCATION_GROUP_ID'
				)
			)
		);
	}
}
