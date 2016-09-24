<?
namespace Bitrix\Socialservices;

use \Bitrix\Main\Entity;


class UserTable extends Entity\DataManager
{
	const ALLOW = 'Y';
	const DISALLOW = 'N';

	const INITIALIZED = 'Y';
	const NOT_INITIALIZED = 'N';

	private static $deletedList = array();

	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_socialservices_user';
	}

	public static function getMap()
	{
		$fieldsMap = array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'LOGIN' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'NAME' => array(
				'data_type' => 'string',
			),
			'LAST_NAME' => array(
				'data_type' => 'string',
			),
			'EMAIL' => array(
				'data_type' => 'string',
			),
			'PERSONAL_PHOTO' => array(
				'data_type' => 'string',
			),
			'EXTERNAL_AUTH_ID' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'XML_ID' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'CAN_DELETE' => array(
				'data_type' => 'boolean',
				'values' => array(self::DISALLOW, self::ALLOW)
			),
			'PERSONAL_WWW' => array(
				'data_type' => 'string',
			),
			'PERMISSIONS' => array(
				'data_type' => 'string',
				'serizalized' => true,
			),
			'OATOKEN' => array(
				'data_type' => 'string',
			),
			'OATOKEN_EXPIRES' => array(
				'data_type' => 'integer',
			),
			'OASECRET' => array(
				'data_type' => 'string',
			),
			'REFRESH_TOKEN' => array(
				'data_type' => 'string',
			),
			'SEND_ACTIVITY' => array(
				'data_type' => 'boolean',
				'values' => array(self::DISALLOW, self::ALLOW)
			),
			'SITE_ID' => array(
				'data_type' => 'string',
			),
			'INITIALIZED' => array(
				'data_type' => 'boolean',
				'values' => array(self::NOT_INITIALIZED, self::INITIALIZED)
			),
			'USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array('=this.USER_ID' => 'ref.ID'),
			),
		);

		return $fieldsMap;
	}

	public static function filterFields($fields)
	{
		$map = static::getMap();
		foreach($fields as $key => $value)
		{
			if(!array_key_exists($key, $map))
			{
				unset($fields[$key]);
			}
			elseif($map[$key]['required'] && empty($fields[$key]))
			{
				unset($fields[$key]);
			}
		}

		return $fields;
	}

	public static function onBeforeDelete(Entity\Event $event)
	{
		$primary = $event->getParameter("primary");
		$ID = $primary["ID"];
		$dbRes = static::getByPrimary($ID);
		self::$deletedList[$ID] = $dbRes->fetch();
	}

	public static function onAfterDelete(Entity\Event $event)
	{
		$primary = $event->getParameter("primary");
		$ID = $primary["ID"];
		$userInfo = self::$deletedList[$ID];
		if($userInfo)
		{
			UserLinkTable::deleteBySocserv($userInfo["USER_ID"], $userInfo["ID"]);

			if($userInfo["EXTERNAL_AUTH_ID"] === \CSocServBitrix24Net::ID)
			{
				$interface = new \CBitrix24NetOAuthInterface();
				$interface->setToken($userInfo["OATOKEN"]);
				$interface->setAccessTokenExpires($userInfo["OATOKEN_EXPIRES"]);
				$interface->setRefreshToken($userInfo["REFRESH_TOKEN"]);

				if($interface->checkAccessToken() || $interface->getNewAccessToken())
				{
					$interface->RevokeAuth();
				}
			}
		}
	}
}
