<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\Main\Authentication;

use Bitrix\Main;
use Bitrix\Main\Entity;

class ApplicationPasswordTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return "b_app_password";
	}

	public static function getMap()
	{
		return array(
			new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true
			)),
			new Entity\IntegerField('USER_ID', array(
				'required' => true,
				'validation' => '\Bitrix\Main\Authentication\ApplicationPasswordTable::getUserValidators',
			)),
			new Entity\StringField('APPLICATION_ID', array(
				'required' => true,
			)),
			new Entity\StringField('PASSWORD', array(
				'required' => true,
			)),
			new Entity\StringField('DIGEST_PASSWORD'),
			new Entity\DatetimeField('DATE_CREATE'),
			new Entity\DatetimeField('DATE_LOGIN'),
			new Entity\StringField('LAST_IP'),
			new Entity\StringField('COMMENT'),
			new Entity\StringField('SYSCOMMENT'),
			new Entity\StringField('CODE'),
			new Entity\ReferenceField(
				'USER',
				'Bitrix\Main\User',
				array('=this.USER_ID' => 'ref.ID'),
				array('join_type' => 'INNER')
			),
		);
	}

	public static function getUserValidators()
	{
		return array(
			new Entity\Validator\Foreign(Main\UserTable::getEntity()->getField('ID')),
		);
	}

	public static function onBeforeAdd(Entity\Event $event)
	{
		$result = new Entity\EventResult;
		$data = $event->getParameter("fields");

		if(isset($data["USER_ID"]) && isset($data['PASSWORD']))
		{
			$salt = md5(\CMain::GetServerUniqID().uniqid());
			$password = $salt.md5($salt.$data['PASSWORD']);

			$modified = array(
				'PASSWORD' => $password,
			);

			$user = Main\UserTable::getRowById($data["USER_ID"]);
			if($user !== null)
			{
				$realm = (defined('BX_HTTP_AUTH_REALM')? BX_HTTP_AUTH_REALM : "Bitrix Site Manager");
				$digest = md5($user["LOGIN"].':'.$realm.':'.$data['PASSWORD']);
				$modified['DIGEST_PASSWORD'] = $digest;
			}

			$result->modifyFields($modified);
		}
		return $result;
	}

	/**
	 * Generates a random password.
	 * @return string
	 */
	public static function generatePassword()
	{
		return \randString(16, "qwertyuiopasdfghjklzxcvbnm");
	}

	/**
	 * Finds the application by the user's password.
	 *
	 * @param int $userId
	 * @param string $password
	 * @param bool $passwordOriginal
	 * @return array|false
	 */
	public static function findPassword($userId, $password, $passwordOriginal = true)
	{
		$encodedPassword = substr($password, 32);
		$noSpacePassword = str_replace(' ', '', $password);

		$appPasswords = static::getList(array(
			'select' => array('ID', 'PASSWORD', 'APPLICATION_ID'),
			'filter' => array('=USER_ID' => $userId),
		));
		while(($appPassword = $appPasswords->fetch()))
		{
			$dbPassword = substr($appPassword["PASSWORD"], 32);

			if($passwordOriginal)
			{
				$appSalt = substr($appPassword["PASSWORD"], 0, 32);
				$userPassword =  md5($appSalt.$noSpacePassword);
			}
			else
			{
				$userPassword = $encodedPassword;
			}

			if($dbPassword === $userPassword)
			{
				//bingo, application password
				return $appPassword;
			}
		}
		return false;
	}

	/**
	 * Finds the application by the user's digest authentication.
	 *
	 * @param int $userId
	 * @param array $digest See CHTTP::ParseDigest() for the array structure.
	 * @return array|false
	 */
	public static function findDigestPassword($userId, array $digest)
	{
		$appPasswords = static::getList(array(
			'select' => array('PASSWORD', 'DIGEST_PASSWORD', 'APPLICATION_ID'),
			'filter' => array('=USER_ID' => $userId),
		));

		$server = Main\Context::getCurrent()->getServer();
		$method = ($server['REDIRECT_REQUEST_METHOD'] !== null? $server['REDIRECT_REQUEST_METHOD'] : $server['REQUEST_METHOD']);
		$HA2 = md5($method.':'.$digest['uri']);

		while(($appPassword = $appPasswords->fetch()))
		{
			$HA1 = $appPassword["DIGEST_PASSWORD"];
			$valid_response = md5($HA1.':'.$digest['nonce'].':'.$HA2);

			if($digest["response"] === $valid_response)
			{
				//application password
				return $appPassword;
			}
		}
		return false;
	}
}
