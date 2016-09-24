<?php
namespace Bitrix\Socialservices;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;


/**
 * Class ContactConnectTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TIMESTAMP_X datetime optional default 'CURRENT_TIMESTAMP'
 * <li> CONTACT_ID int mandatory
 * <li> CONTACT_PROFILE_ID int mandatory
 * <li> CONTACT_PORTAL string(255) mandatory
 * <li> CONNECT_TYPE string(1) optional default 'P'
 * </ul>
 *
 * @package Bitrix\Socialservices
 **/

class ContactConnectTable extends Main\Entity\DataManager
{
	const TYPE_PORTAL = 'P'; // bitrix24 portal
	const TYPE_EXTERNAL = 'E'; // external site (box)

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_socialservices_contact_connect';
	}

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
			),
			'TIMESTAMP_X' => array(
				'data_type' => 'datetime',
			),
			'CONTACT_ID' => array(
				'data_type' => 'integer',
			),
			'LINK_ID' => array(
				'data_type' => 'integer',
			),
			'CONTACT_PROFILE_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'CONTACT_PORTAL' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'LAST_AUTHORIZE' => array(
				'data_type' => 'datetime',
			),
			'CONNECT_TYPE' => array(
				'data_type' => 'enum',
				'values' => array(static::TYPE_PORTAL, static::TYPE_EXTERNAL),
			),
			'CONTACT' => array(
					'data_type' => 'Bitrix\Socialservices\ContactTable',
					'reference' => array('=this.CONTACT_ID' => 'ref.ID'),
			),
			'LINK' => array(
					'data_type' => 'Bitrix\Socialservices\UserLinkTable',
					'reference' => array('=this.LINK_ID' => 'ref.ID'),
			),
		);
	}

	public static function onBeforeUpdate(Entity\Event $event)
	{
		$result = new Entity\EventResult();
		$data = $event->getParameter("fields");

		if(!isset($data['TIMESTAMP_X']))
		{
			$data['TIMESTAMP_X'] = new DateTime();
			$result->modifyFields($data);
		}
	}

	public static function deleteByLink($linkId)
	{
		$connection = \Bitrix\Main\Application::getConnection();
		return $connection->query("
DELETE
FROM ".self::getTableName()."
WHERE LINK_ID='".intval($linkId)."'
");
	}

	public static function deleteByContact($contactId)
	{
		$connection = \Bitrix\Main\Application::getConnection();
		return $connection->query("
DELETE
FROM ".self::getTableName()."
WHERE CONTACT_ID='".intval($contactId)."'
");
	}

}
