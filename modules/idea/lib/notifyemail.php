<?php
namespace Bitrix\Idea;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class NotifyEmailTable extends Entity\DataManager
{
	const SUBSCRIBE_TYPE_ALL = 'ALL';
	const SUBSCRIBE_TYPE_NEW_IDEAS = 'NEW IDEAS';
	const ENTITY_TYPE_IDEA = 'IDEA';
	const ENTITY_TYPE_CATEGORY = 'CATEGORY';
	/**
	 * Returns path to the file which contains definition of the class.
	 *
	 * @return string
	 */
	public static function getFilePath()
	{
		return __FILE__;
	}

	/**
	 * Returns DB table name for entity
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_idea_email_subscribe';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'USER_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'title' => Loc::getMessage('IDEA_NOTIFY_EMAIL_USER_ID'),
			),
			'SUBSCRIBE_TYPE' => array(
				'data_type' => 'enum',
				'required' => true,
				'values' => array(self::SUBSCRIBE_TYPE_ALL, self::SUBSCRIBE_TYPE_NEW_IDEAS),
				'title' => Loc::getMessage('IDEA_NOTIFY_EMAIL_SUBSCRIBE_TYPE')
			),
			'ENTITY_TYPE' => array(
				'data_type' => 'enum',
				'primary' => true,
				'values' => array(self::ENTITY_TYPE_IDEA, self::ENTITY_TYPE_CATEGORY),
				'title' => Loc::getMessage('IDEA_NOTIFY_EMAIL_ENTITY_TYPE')
			),
			'ENTITY_CODE' => array(
				'data_type' => 'string',
				'primary' => true,

				'title' => Loc::getMessage('IDEA_NOTIFY_EMAIL_ENTITY_CODE')
			),
			'USER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array(
					'=this.USER_ID' =>  'ref.ID'
				),
			),
			'ASCENDED_CATEGORIES' => array(
				'data_type' => 'Bitrix\Iblock\Section',
				'reference' => array(
					'=this.ENTITY_TYPE' =>  array('?', 'CATEGORY'),
					'=this.ENTITY_CODE' => 'ref.CODE'
				),
			)
		);
	}
}

class NotifyEmail
{
	protected static $cache = array();
	protected $IblockID = null;
	protected $userID = null;

	public function __construct($IblockID = null)
	{
		if ($IblockID > 0)
		{
			$this->IblockID = $IblockID;
			\CIdeaManagment::getInstance()->idea()->setCategoryListId($IblockID);
		}
		else
			$this->IblockID = \CIdeaManagment::getInstance()->idea()->getCategoryListID();
		global $USER;
		$this->userID = $USER->getID();
	}

	protected function getCacheId($params = array())
	{
		if (array_key_exists("IDEA", $params))
		{
			$id = "IDEA_".$params["IDEA"];
		}
		else
		{
			$id = "CATEGORY_".$this->IblockID.(empty($params["CATEGORY"]) ? "" : "_".$params["CATEGORY"]);
		}

		return $id;
	}

	public function addCategory($category, $subscribeType = NotifyEmailTable::SUBSCRIBE_TYPE_NEW_IDEAS)
	{
		if ($this->IblockID > 0 && $this->userID > 0)
		{
			$db_res = NotifyEmailTable::getList(array(
				"filter" => array(
					"USER_ID" => $this->userID,
					"ENTITY_TYPE" => NotifyEmailTable::ENTITY_TYPE_CATEGORY,
					"ENTITY_CODE" => (empty($category) ? "" : $category)
				)));
			if ($db_res->getSelectedRowsCount() <= 0)
			{
				$db_res = NotifyEmailTable::add(array(
					"USER_ID" => $this->userID,
					"SUBSCRIBE_TYPE" => ($subscribeType == NotifyEmailTable::SUBSCRIBE_TYPE_NEW_IDEAS ? NotifyEmailTable::SUBSCRIBE_TYPE_NEW_IDEAS : NotifyEmailTable::SUBSCRIBE_TYPE_ALL),
					"ENTITY_TYPE" => NotifyEmailTable::ENTITY_TYPE_CATEGORY,
					"ENTITY_CODE" => (empty($category) ? "" : $category)
				));
			}
			return $db_res;
		}
		return false;
	}

	public function deleteCategory($category)
	{
		if ($this->userID > 0)
		{
			return NotifyEmailTable::delete(array(
				"USER_ID" => $this->userID,
				"ENTITY_TYPE" => NotifyEmailTable::ENTITY_TYPE_CATEGORY,
				"ENTITY_CODE" => (empty($category) ? "" : $category)
			));
		}
		return false;
	}

	public function addIdea($id)
	{
		if ($this->userID > 0)
		{
			$db_res = NotifyEmailTable::getList(array(
				"filter" => array(
					"USER_ID" => $this->userID,
					"ENTITY_TYPE" => NotifyEmailTable::ENTITY_TYPE_IDEA,
					"ENTITY_CODE" => $id.""
			)));
			if (!(!!$db_res && ($res = $db_res->fetch()) && !empty($res)))
			{
				$db_res = NotifyEmailTable::add(array(
					"USER_ID" => $this->userID,
					"SUBSCRIBE_TYPE" => NotifyEmailTable::SUBSCRIBE_TYPE_ALL,
					"ENTITY_TYPE" => NotifyEmailTable::ENTITY_TYPE_IDEA,
					"ENTITY_CODE" => $id.""
				));
				return $db_res;
			}
			return $res;
		}
		return false;
	}

	public function deleteIdea($id)
	{

		if ($this->userID > 0)
		{
			return NotifyEmailTable::delete(array(
				"USER_ID" => $this->userID,
				"ENTITY_TYPE" => NotifyEmailTable::ENTITY_TYPE_IDEA,
				"ENTITY_CODE" => $id
			));
		}
		return false;
	}

	protected function checkCache($userId, $params = array())
	{
		if (!array_key_exists($userId, self::$cache))
			self::$cache[$userId] = array();
		$id = $this->getCacheId($params);
		return (array_key_exists($id, self::$cache[$userId]) ? self::$cache[$userId][$id] : false);
	}

	protected function setCache($userId, $params = array(), $data = array())
	{
		if (!array_key_exists($userId, self::$cache))
			self::$cache[$userId] = array();
		$id = $this->getCacheId($params);
		self::$cache[$userId][$id] = $data;
		return true;
	}

	public function getAscendedCategories($category = null, $userId = null)
	{
		$return = false;
		$userId = ($userId === null ? $this->userID : $userId);
		if ($this->IblockID > 0 && $userId > 0)
		{
			$cache = $this->checkCache($userId, array("CATEGORY" => $category));
			if (!!$cache)
			{
				$return = $cache;
			}
			else if (empty($category))
			{
				$return = array();
				$db_res = NotifyEmailTable::getList(array(
					"filter" => array(
						"USER_ID" => $userId,
						"=ENTITY_TYPE" => NotifyEmailTable::ENTITY_TYPE_CATEGORY,
						"=ENTITY_CODE" => NULL
					)
				));
				while ($res = $db_res->fetch())
					array_push($return, $res);
			}
			else if (is_string($category) && ($categories = \CIdeaManagment::getInstance()->idea()->getCategoryList()) && !empty($categories))
			{
				$category = ToUpper($category);
				if (array_key_exists($category, $categories))
				{
					$return = array();
					$category = $categories[$category];
					$db_res = NotifyEmailTable::getList(array(
						"filter" => array(
							"=USER_ID" => $userId,
							"=ENTITY_TYPE" => NotifyEmailTable::ENTITY_TYPE_CATEGORY,
							"=ASCENDED_CATEGORIES.IBLOCK_ID" => \CIdeaManagment::getInstance()->idea()->getCategoryListID(),
							"<=ASCENDED_CATEGORIES.DEPTH_LEVEL" => $category["DEPTH_LEVEL"],
							"<=ASCENDED_CATEGORIES.LEFT_MARGIN" => $category["LEFT_MARGIN"],
							">=ASCENDED_CATEGORIES.RIGHT_MARGIN" => $category["RIGHT_MARGIN"]
						)
					));
					while ($res = $db_res->fetch())
						array_push($return, $res);
				}
			}
			$this->setCache($userId, array("CATEGORY" => $category), $return);
		}
		return $return;
	}

}
?>