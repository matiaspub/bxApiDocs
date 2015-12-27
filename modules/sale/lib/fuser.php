<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale;

use Bitrix\Main;
use Bitrix\Sale\Internals\FuserTable;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Fuser
{
	public static function __construct()
	{

	}

	/**
	 * Return fuserId.
	 *
	 * @param bool $skipCreate		Create, if not exist.
	 * @return int
	 */
	public static function getId($skipCreate = false)
	{
		$id = \CSaleUser::getID($skipCreate);
		static::updateSession($id);
		return $id;
	}

	/**
	 * Update session data
	 *
	 * @param int $id				FuserId.
	 * @return void
	 */
	protected static function updateSession($id)
	{
		\CSaleUser::updateSessionSaleUserID();
		if ((string)Main\Config\Option::get('sale', 'encode_fuser_id') != 'Y' && isset($_SESSION['SALE_USER_ID']))
			$_SESSION['SALE_USER_ID'] = (int)$_SESSION['SALE_USER_ID'];

		if (!isset($_SESSION['SALE_USER_ID']) || (string)$_SESSION['SALE_USER_ID'] == '' || $_SESSION['SALE_USER_ID'] === 0)
			$_SESSION['SALE_USER_ID'] = $id;
	}

	/**
	 * Return fuser code.
	 *
	 * @return int
	 */
	protected static function getCode()
	{
		return \CSaleUser::getFUserCode();
	}

	public static function getIdByUserId($userId)
	{
		$res = FuserTable::getList(array(
			'filter' => array(
				'USER_ID' => $userId
			),
			'select' => array(
				'ID'
			)
		));
		if ($fuserData = $res->fetch())
		{
			return intval($fuserData['ID']);
		}
		else
		{
			/** @var Result $r */
			$r = static::createForUserId($userId);
			if ($r->isSuccess())
			{
				return $r->getId();
			}
		}

		return false;
	}

	/**
	 * @param $userId
	 * @return Main\Entity\AddResult
	 * @throws \Exception
	 */
	protected static function createForUserId($userId)
	{
		$fields = array(
			'DATE_INSERT' => new Main\Type\DateTime(),
			'DATE_UPDATE' => new Main\Type\DateTime(),
			'USER_ID' => $userId,
			'CODE' => md5(time().randString(10))
		);

		/** @var Result $r */
		return FuserTable::add($fields);
	}

	/**
	 * @param $days
	 */
	public static function deleteOld($days)
	{
		$connection = Main\Application::getConnection();

		$expired = new Main\Type\DateTime();
		$expired->add('-'.$days.'days');
		$expiredValue = $expired->format('Y-m-d H:i:s');

		if ($connection instanceof Main\DB\MysqlConnection)
		{
			$query = "DELETE FROM b_sale_fuser WHERE
										b_sale_fuser.DATE_UPDATE < '".$expiredValue."'
										AND b_sale_fuser.USER_ID IS NULL
										AND b_sale_fuser.id NOT IN (select FUSER_ID from b_sale_basket)";
			$connection->query($query);
		}
		elseif ($connection instanceof Main\DB\MssqlConnection)
		{
			$query = "DELETE FROM b_sale_fuser WHERE
										b_sale_fuser.DATE_UPDATE < CONVERT(varchar(20),'".$expiredValue."', 20)
										AND b_sale_fuser.USER_ID IS NULL
										AND b_sale_fuser.id NOT IN (select FUSER_ID from b_sale_basket)";
			$connection->query($query);
		}
		elseif($connection instanceof Main\DB\OracleConnection)
		{
			$query = "DELETE FROM b_sale_fuser WHERE
										b_sale_fuser.DATE_UPDATE < TO_DATE('".$expiredValue."', 'YYYY-MM-DD HH24:MI:SS')
										AND b_sale_fuser.USER_ID IS NULL
										AND b_sale_fuser.id NOT IN (select FUSER_ID from b_sale_basket)";
			$connection->query($query);
		}
	}
}