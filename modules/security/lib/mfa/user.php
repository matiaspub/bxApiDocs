<?php
namespace Bitrix\Security\Mfa;


use Bitrix\Main\Entity;
use Bitrix\Main\Type;

/*
CREATE TABLE b_sec_user
(
	USER_ID INT(11) NOT NULL REFERENCES b_user(ID),
	ACTIVE CHAR(1) NOT NULL DEFAULT 'N',
	SECRET VARCHAR(64) NOT NULL,
	PARAMS text,
	TYPE VARCHAR(16) NOT NULL,
	ATTEMPTS int(18),
	INITIAL_DATE date,
	SKIP_MANDATORY CHAR(1) DEFAULT 'N',
	DEACTIVATE_UNTIL datetime,
	PRIMARY KEY (USER_ID)
);
 */

class UserTable
	extends Entity\DataManager
{
	/**
	 * {@inheritdoc}
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sec_user';
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'USER_ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'USER' => array(
				'data_type' => '\Bitrix\Main\User',
				'reference' => array('=this.USER_ID' => 'ref.ID')
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array('Y', 'N'),
				'default' => 'N'
			),
			'SECRET' => array(
				'data_type' => 'string',
				'format' => '#^[a-z0-9]{0,64}$#iD'
			),
			'PARAMS' => array(
				'data_type' => 'text'
			),
			'TYPE' => array(
				'data_type' => 'string',
				'values' => array(Otp::TYPE_TOTP, Otp::TYPE_HOTP),
				'default' => Otp::TYPE_DEFAULT
			),
			'ATTEMPTS' => array(
				'data_type' => 'integer',
				'default' => 0
			),
			'INITIAL_DATE' => array(
				'data_type' => 'datetime',
				'default' => new Type\DateTime
			),
			'SKIP_MANDATORY' => array(
				'data_type' => 'boolean',
				'values' => array('Y', 'N'),
				'default' => 'N'
			),
			'DEACTIVATE_UNTIL' => array(
				'data_type' => 'datetime'
			),
		);
	}

	/**
	 * Clear recovery codes after delete user
	 *
	 * @param Entity\Event $event Our event.
	 * @return void
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public static function onAfterDelete(Entity\Event $event)
	{
		$primary = $event->getParameter('primary');
		RecoveryCodesTable::clearByUser($primary['USER_ID']);
	}
}
