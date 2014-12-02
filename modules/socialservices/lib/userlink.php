<?
namespace Bitrix\Socialservices;

use \Bitrix\Main\Entity\DataManager;
use \Bitrix\Socialservices\UserTable as SocservUserTable;

class UserLinkTable extends DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_socialservices_user_link';
	}

	public static function getMap()
	{
		$fieldsMap = array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'SOCSERV_USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'LINK_USER_ID' => array(
				'data_type' => 'integer',
			),
			'LINK_UID' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'LINK_NAME' => array(
				'data_type' => 'string',
			),
			'LINK_LAST_NAME' => array(
				'data_type' => 'string',
			),
			'LINK_PICTURE' => array(
				'data_type' => 'string',
			),
			'USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array('=this.USER_ID' => 'ref.ID'),
			),
			'SOCSERV_USER' => array(
				'data_type' => 'Bitrix\Socialservices\UserTable',
				'reference' => array('=this.SOCSERV_USER_ID' => 'ref.ID'),
			),
			'LINK_USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array('=this.LINK_USER_ID' => 'ref.ID'),
			),
		);

		return $fieldsMap;
	}

	public static function deleteBySocserv($userId, $socservProfileId)
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$query = $connection->query("
DELETE
FROM ".self::getTableName()."
WHERE USER_ID='".intval($userId)."' AND SOCSERV_USER_ID='".intval($socservProfileId)."'
");
	}

	public static function compareUserLinks($userId, $socservUserId, $arLinks)
	{
		$dbRes = self::getList(array(
			'filter' => array(
				//'USER_ID' => $userId, // link USER_ID doesn't update with socserv_user
				'SOCSERV_USER_ID' => $socservUserId,
			)
		));

		$arCurrentList = array();
		while($arRes = $dbRes->fetch())
		{
			$arCurrentList[$arRes['LINK_UID']] = $arRes['ID'];
		}

		foreach($arLinks as $key => $link)
		{
			if(array_key_exists($link['uid'], $arCurrentList))
			{
				unset($arCurrentList[$link['uid']]);
				unset($arLinks[$key]);
			}
		}

		foreach($arCurrentList as $linkId)
		{
			self::delete($linkId);
		}

		foreach($arLinks as $link)
		{
			self::add(array(
				'USER_ID' => $userId,
				'SOCSERV_USER_ID' => $socservUserId,
				'LINK_USER_ID' => null,
				'LINK_UID' => $link['uid'],
				'LINK_NAME' => $link['first_name'],
				'LINK_LAST_NAME' => $link['last_name'],
				'LINK_PICTURE' => $link['picture'],
			));
		}
	}

	public static function checkUserLinks($socservUserId)
	{
		$dbRes = UserTable::getByPrimary($socservUserId);
		$arUser = $dbRes->fetch();
		if($arUser)
		{
			$sql = "
SELECT sul.ID, su_link.USER_ID
FROM ".self::getTableName()." sul
LEFT JOIN ".SocservUserTable::getTableName()." su_link ON sul.LINK_UID=su_link.XML_ID
WHERE (1=1)
AND sul.SOCSERV_USER_ID='".$arUser['ID']."'
AND su_link.EXTERNAL_AUTH_ID='".$arUser['EXTERNAL_AUTH_ID']."'
AND sul.LINK_USER_ID IS NULL
";
			$connection = \Bitrix\Main\Application::getConnection();
			return $connection->query($sql);
		}
		else
		{
			return false;
		}
	}
}