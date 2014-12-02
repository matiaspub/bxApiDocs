<?
namespace Bitrix\Socialservices;

use \Bitrix\Main\Entity\DataManager;

class UserTable extends DataManager
{
	const ALLOW = 'Y';
	const DISALLOW = 'N';

	const INITIALIZED = 'Y';
	const NOT_INITIALIZED = 'N';

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
	}}