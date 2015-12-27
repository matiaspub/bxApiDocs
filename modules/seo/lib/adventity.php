<?php
namespace Bitrix\Seo;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Seo\Engine;

Loc::loadMessages(__FILE__);

/**
 * Class AdvEntity
 *
 * Interface for Yandex.Direct and Google AdWords local data mirrors. Contains common fields defeinitions.
 *
 * Implemented fields:
 * <ul>
 * <li> ID int mandatory
 * <li> ENGINE_ID int mandatory
 * <li> XML_ID string(255) mandatory
 * <li> LAST_UPDATE datetime optional
 * <li> SETTINGS string optional
 * </ul>
 *
 * @package Bitrix\Seo
 **/

class AdvEntity extends Entity\DataManager
{
	const ACTIVE = 'Y';
	const INACTIVE = 'N';

	protected static $skipRemoteUpdate = false;

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('ADV_CAMPAIGN_ENTITY_ID_FIELD'),
			),
			'ENGINE_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('ADV_CAMPAIGN_ENTITY_ENGINE_ID_FIELD'),
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array(static::INACTIVE, static::ACTIVE),
			),
			'OWNER_ID' => array(
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage('ADV_CAMPAIGN_ENTITY_OWNER_ID_FIELD'),
			),
			'OWNER_NAME' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('ADV_CAMPAIGN_ENTITY_OWNER_NAME_FIELD'),
			),
			'XML_ID' => array(
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage('ADV_CAMPAIGN_ENTITY_XML_ID_FIELD'),
			),
			'NAME' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('ADV_CAMPAIGN_ENTITY_NAME_FIELD'),
			),
			'LAST_UPDATE' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('ADV_CAMPAIGN_ENTITY_LAST_UPDATE_FIELD'),
			),
			'SETTINGS' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('ADV_CAMPAIGN_ENTITY_SETTINGS_FIELD'),
				'serialized' => true,
			),
			'ENGINE' => array(
				'data_type' => 'Bitrix\Seo\SearchEngineTable',
				'reference' => array('=this.ENGINE_ID' => 'ref.ID'),
			)
		);
	}

	public static function setSkipRemoteUpdate($value)
	{
		static::$skipRemoteUpdate = $value;
	}

	public static function onBeforeAdd(Entity\Event $event)
	{
		$result = new Entity\EventResult();

		$result->modifyFields(array(
			'LAST_UPDATE' => new DateTime(),
			'ACTIVE' => static::ACTIVE)
		);

		return $result;
	}

	public static function onBeforeUpdate(Entity\Event $event)
	{
		$result = new Entity\EventResult();

		$result->modifyFields(array('LAST_UPDATE' => new DateTime()));

		return $result;
	}

}
