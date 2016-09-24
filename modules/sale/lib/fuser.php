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
	
	/**
	* <p>Метод возвращает идентификатор покупателя. Метод статический.</p>
	*
	*
	* @param boolean $skipCreate = false Если параметр принимает <i>false</i>, то покупатель будет создан, если
	* его не существует.
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/fuser/getid.php
	* @author Bitrix
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

	/**
	 * Return fuserId for user.
	 *
	 * @param int $userId			User Id.
	 * @return false|int
	 * @throws Main\ArgumentException
	 */
	public static function getIdByUserId($userId)
	{
		$res = FuserTable::getList(array(
			'filter' => array(
				'USER_ID' => $userId
			),
			'select' => array(
				'ID'
			),
			'order' => array('ID' => "DESC")
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
	 * Return user by fuserId.
	 *
	 * @param int $fuserId		Fuser Id.
	 * @return int
	 * @throws Main\ArgumentException
	 */
	public static function getUserIdById($fuserId)
	{
		$result = 0;

		$fuserId = (int)$fuserId;
		if ($fuserId <= 0)
			return $result;
		$row = FuserTable::getList(array(
			'select' => array('USER_ID'),
			'filter' => array('=ID' => $fuserId),
			'order' => array('ID' => "DESC")
		))->fetch();
		if (!empty($row))
			$result = (int)$row['USER_ID'];

		return $result;
	}

	/**
	 * Delete fuserId over several days.
	 *
	 * @param int $days			Interval.
	 * @return void
	 */
	public static function deleteOld($days)
	{
		$expired = new Main\Type\DateTime();
		$expired->add('-'.$days.'days');
		$expiredValue = $expired->format('Y-m-d H:i:s');

		/** @var Main\DB\Connection $connection */
		$connection = Main\Application::getConnection();
		/** @var Main\DB\SqlHelper $sqlHelper */
		$sqlHelper = $connection->getSqlHelper();

		$query = "DELETE FROM b_sale_fuser WHERE
									b_sale_fuser.DATE_UPDATE < ".$sqlHelper->getDateToCharFunction("'".$expiredValue."'")."
									AND b_sale_fuser.USER_ID IS NULL
									AND b_sale_fuser.id NOT IN (select FUSER_ID from b_sale_basket)";
		$connection->queryExecute($query);
	}

	/**
	 * Create new fuserId for user.
	 *
	 * @param int $userId				User id.
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
}