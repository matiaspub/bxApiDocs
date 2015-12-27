<?php
namespace Bitrix\Security\Mfa;


use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Entity;
use Bitrix\Main\Type;
use Bitrix\Main\Security\Random;

/*
CREATE TABLE `b_sec_recovery_codes` (
	`ID` int NOT NULL AUTO_INCREMENT,
	`USER_ID` int NOT NULL,
	`CODE` varchar(255) NOT NULL,
	`USED` varchar(1) NOT NULL,
	`USING_DATE` DATETIME NULL,
	`USING_IP` VARCHAR(255) NULL,
	PRIMARY KEY(`ID`),
	INDEX ix_b_sec_recovery_codes_user_id (USER_ID)
)
 */

class RecoveryCodesTable
	extends Entity\DataManager
{
	const CODES_PER_USER = 10;
	const CODE_PATTERN = '#^[a-z0-9]{4}-[a-z0-9]{4}$#D';

	/**
	 * {@inheritdoc}
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sec_recovery_codes';
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true
			)),
			new Entity\IntegerField('USER_ID', array(
				'required' => true
			)),
			new Entity\StringField('CODE', array(
				'required' => true,
				'format' => static::CODE_PATTERN
			)),
			new Entity\BooleanField('USED', array(
				'values' => array('Y', 'N'),
				'default' => 'N'
			)),
			new Entity\DatetimeField('USING_DATE'),
			new Entity\StringField('USING_IP'),
			new Entity\ReferenceField(
				'USER',
				'Bitrix\Main\User',
				array('=this.USER_ID' => 'ref.ID'),
				array('join_type' => 'INNER')
			),
		);
	}

	/**
	 * Clear all saved recovery codes for provided user
	 *
	 * @param int $userId Needed user id.
	 * @return bool Returns true if successful
	 * @throws ArgumentTypeException
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function clearByUser($userId)
	{
		$userId = (int) $userId;
		if ($userId <= 0)
			throw new ArgumentTypeException('userId', 'positive integer');

		$codes = static::getList(array(
			'select' => array('ID'),
			'filter' => array('=USER_ID' => $userId)
		));

		while (($code = $codes->fetch()))
		{
			static::delete($code['ID']);
		}

		return true;
	}

	/**
	 * Generate new recovery codes for provided user
	 * Previously generated codes will be removed
	 *
	 * @param int $userId Needed user id.
	 * @return bool Returns true if successful
	 * @throws ArgumentTypeException
	 */
	public static function regenerateCodes($userId)
	{
		$userId = (int) $userId;
		if ($userId <= 0)
			throw new ArgumentTypeException('userId', 'positive integer');

		static::clearByUser($userId);

		$randomVector = Random::getString(static::CODES_PER_USER * 8);
		$randomVector = str_split($randomVector, 4);
		for ($i = 0; $i < static::CODES_PER_USER; $i++)
		{
			$code = array(
				'USER_ID' => $userId,
				'USED' => 'N',
				'CODE' => sprintf('%s-%s', $randomVector[$i * 2], $randomVector[($i * 2) + 1])
			);

			static::add($code);
		}

		return true;
	}

	/**
	 * Use recovery code for user
	 *
	 * @param int $userId Needed user id.
	 * @param string $searchCode Recovery code in accepted format (see RecoveryCodesTable::CODE_PATTERN).
	 * @return bool Returns true if successful
	 * @throws ArgumentTypeException
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function useCode($userId, $searchCode)
	{
		$userId = (int) $userId;
		if ($userId <= 0)
			throw new ArgumentTypeException('userId', 'positive integer');

		if (!preg_match(static::CODE_PATTERN, $searchCode))
			throw new ArgumentTypeException('searchCode', sprintf('string, check pattern "%s"', static::CODE_PATTERN));

		$codes = static::getList(array(
			'select' => array('ID', 'CODE'),
			'filter' => array('=USER_ID' => $userId, '=USED' => 'N'),
		));

		$found = false;
		while (($code = $codes->fetch()))
		{
			if($code['CODE'] === $searchCode)
			{
				static::update($code['ID'], array(
					'USED' => 'Y',
					'USING_DATE' => new Type\DateTime,
					'USING_IP' => \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getRemoteAddress()
				));
				$found = true;
				break;
			}
		}

		return $found;
	}
}
