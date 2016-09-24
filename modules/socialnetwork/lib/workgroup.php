<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage socialnetwork
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Socialnetwork;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;

Loc::loadMessages(__FILE__);

class WorkgroupTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_sonet_group';
	}

	public static function getMap()
	{
		$fieldsMap = array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y')
			),
			'SITE_ID' => array(
				'data_type' => 'string',
			),
			'SUBJECT_ID' => array(
				'data_type' => 'integer',
			),
			'WORKGROUP_SUBJECT' => array(
				'data_type' => '\Bitrix\Socialnetwork\WorkgroupSubject',
				'reference' => array('=this.SUBJECT_ID' => 'ref.ID')
			),
			'NAME' => array(
				'data_type' => 'string'
			),
			'DESCRIPTION' => array(
				'data_type' => 'text'
			),
			'CLOSED' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y')
			),
			'VISIBLE' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y')
			),
			'OPENED' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y')
			),
			'DATE_ACTIVITY' => array(
				'data_type' => 'datetime'
			),
			'IMAGE_ID' => array(
				'data_type' => 'integer',
			),
			'OWNER_ID' => array(
				'data_type' => 'integer',
			),
			'WORKGROUP_OWNER' => array(
				'data_type' => '\Bitrix\Main\User',
				'reference' => array('=this.OWNER_ID' => 'ref.ID')
			),
			'INITIATE_PERMS' => array(
				'data_type' => 'string'
			),
		);

		return $fieldsMap;
	}

	public static function add(array $data)
	{
		throw new NotImplementedException("Use CSocNetGroup class.");
	}

	public static function update($primary, array $data)
	{
		throw new NotImplementedException("Use CSocNetGroup class.");
	}

	public static function delete($primary)
	{
		throw new NotImplementedException("Use CSocNetGroup class.");
	}
}
