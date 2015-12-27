<?php

namespace Bitrix\Forum\Comments;

use \Bitrix\Forum\Internals\Error\ErrorCollection;
use \Bitrix\Forum\Internals\Error\Error;
use Bitrix\Main\Localization\Loc;
use \Bitrix\Main\ArgumentTypeException;
use \Bitrix\Main\ArgumentException;
use \Bitrix\Main\Event;

Loc::loadMessages(__FILE__);

abstract class BaseObject
{
	const ERROR_PARAMS_FORUM_ID = 'params0001';
	const ERROR_PARAMS_TOPIC_ID = 'params0002';
	const ERROR_PARAMS_ENTITY_ID = 'params0003';
	private static $topics = array();
	private static $users = array();
	/* @var \Bitrix\Forum\Comments\Entity */
	protected $entity;
	/** @var array */
	protected $forum;
	/** @var array */
	protected $topic;
	/** @var  ErrorCollection */
	protected $errorCollection;

	public function __construct($forumId, $entity)
	{
		$this->errorCollection = new ErrorCollection();
		$this->setForum($forumId);
		$this->setEntity($entity);
		$this->setTopic();
	}

	protected function setEntity(array $id)
	{
		if (is_array($id) &&
			($id = array_change_key_case($id, CASE_LOWER)) &&
			$id["id"] > 0
		)
		{
			switch(strtolower($id["type"]))
			{
				case 'tk':
					$result = array(TaskEntity::className(), 'tasks');
					if (!array_key_exists('xml_id', $id))
						$id['xml_id'] = 'TASK_'.$id['id'];
					break;
				default:
					$result = array(Entity::className(), 'forum');
				break;
			}
			$this->entity = new $result[0]($id, $this);
		}
		else if (
			$id instanceof TaskEntity ||
			$id instanceof Entity)
			$this->entity = $id;
		else
			throw new ArgumentException(Loc::getMessage("FORUM_CM_WRONG_ENTITY"), self::ERROR_PARAMS_ENTITY_ID);

		return $this;
	}

	/**
	 * Returns entity which manage all rights. For example forum topic or task
	 * @return Entity
	 */
	public function getEntity()
	{
		return $this->entity;
	}

	protected function setTopic()
	{
		if (!array_key_exists($this->entity->getXmlId(), self::$topics))
		{
			$dbRes = \CForumTopic::getList(null, array(
				"FORUM_ID" => $this->forum["ID"],
				"XML_ID" => $this->entity->getXmlId()
			));
			self::$topics[$this->entity->getXmlId()] = (($res = $dbRes->fetch()) && $res ? $res : null);
		}
		$this->topic = self::$topics[$this->entity->getXmlId()];
		return $this;
	}

	protected function createTopic()
	{
		$topic = array(
			'TITLE' => $this->entity->getXmlId(),
			'TAGS' => '',
			'MESSAGE' => $this->entity->getXmlId(),
			'AUTHOR_ID' => 0
		);
		/** @var $request \Bitrix\Main\HttpRequest */
		$request = \Bitrix\Main\Context::getCurrent()->getRequest();
		$post = array_merge($request->getQueryList()->toArray(), $request->getPostList()->toArray());

		$event = new Event("forum", "OnCommentTopicAdd", array($this->entity->getType(), $this->entity->getId(), $post, &$topic));
		$event->send();

		if (strlen($topic["AUTHOR_NAME"]) <= 0)
			$topic["AUTHOR_NAME"] = ($topic["AUTHOR_ID"] <= 0 ? Loc::getMessage("FORUM_USER_SYSTEM") : self::getUserName($topic["AUTHOR_ID"]));

		$topic = array_merge($topic, array(
			"FORUM_ID" => $this->forum["ID"],
			'TITLE' => $topic["TITLE"],
			'TAGS' => $topic["TAGS"],
			'MESSAGE' => $topic["MESSAGE"],
			"USER_START_ID" => $topic["AUTHOR_ID"],
			"USER_START_NAME" => $topic["AUTHOR_NAME"],
			"LAST_POSTER_NAME" => $topic["AUTHOR_NAME"],
			"XML_ID" => $this->entity->getXmlId(),
			"APPROVED" => "Y"
		));
		if (($tid = \CForumTopic::add($topic)) > 0)
		{
			if ($this->forum["ALLOW_HTML"] != "Y")
				$topic['MESSAGE'] = strip_tags($topic['MESSAGE']);

			$fields = Array(
				"POST_MESSAGE" => $topic['MESSAGE'],
				"AUTHOR_ID" => $topic["AUTHOR_ID"],
				"AUTHOR_NAME" => $topic["AUTHOR_NAME"],
				"FORUM_ID" => $topic["FORUM_ID"],
				"TOPIC_ID" => $tid,
				"APPROVED" => $topic["APPROVED"],
				"NEW_TOPIC" => "Y",
				"PARAM1" => $this->entity->getType(),
				"PARAM2" => $this->entity->getId()
			);
			if ((\CForumMessage::add($fields, false, array("SKIP_INDEXING" => "Y", "SKIP_STATISTIC" => "N"))) > 0)
			{
				$event = new Event("forum", "OnAfterCommentTopicAdd", array($this->entity->getType(), $this->entity->getId(), $tid));
				$event->send();

				self::$topics[$this->entity->getXmlId()] = $topic + array("ID" => $tid);
				return self::$topics[$this->entity->getXmlId()];
			}
			\CForumTopic::delete($tid);
		}
		$this->errorCollection->add(array(new Error(Loc::getMessage("FORUM_CM_TOPIC_IS_NOT_CREATED"), self::ERROR_PARAMS_TOPIC_ID)));
		return null;
	}

	/**
	 * Returns forum topic
	 * @return array
	 */
	public function getTopic()
	{
		return $this->topic;
	}

	/**
	 * @return bool
	 */
	public function hasErrors()
	{
		return $this->errorCollection->hasErrors();
	}

	/**
	 * @return array
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	protected static function checkForumId(&$forumId)
	{
		$res = (is_integer($forumId) || is_string($forumId) ? intval($forumId) : 0);
		if ($res > 0)
		{
			$forumId = $res;
			// TODO Complete forum verifying
			return true;
		}
		return false;
	}

	protected function setForum($id)
	{
		if (!$this->checkForumId($id))
			throw new ArgumentTypeException(Loc::getMessage("FORUM_CM_FORUM_IS_WRONG"), self::ERROR_PARAMS_FORUM_ID);

		$this->forum = \CForumNew::getByIDEx($id, SITE_ID);

		if (!$this->forum)
			throw new ArgumentException(Loc::getMessage("FORUM_CM_FORUM_IS_LOST"), self::ERROR_PARAMS_FORUM_ID);

		return $this;
	}

	/**
	 * Returns forum
	 * @return array
	 */
	public function getForum()
	{
		return $this->forum;
	}

	/**
	 * Redefines forum params
	 *
	 * @param array $params Array(key=>value, key2=>value2) of fields to redefine forum fields.
	 * @return $this
	 */
	public function setForumFields(array $params)
	{
		foreach ($params as $key => $val)
		{
			if (array_key_exists($key, $this->forum))
				$this->forum[$key] = $val;
		}
		return $this;
	}

	/**
	 * @return \CUser
	 */
	static public function getUser()
	{
		global $USER;
		return $USER;
	}

	/**
	 * @return \CMain
	 */
	static public function getApplication()
	{
		global $APPLICATION;
		return $APPLICATION;
	}

	private static function getUserFromForum($userId)
	{
		if ($userId > 0 && !array_key_exists($userId, self::$users))
		{
			self::$users[$userId] = \CForumUser::getListEx(array(), array("USER_ID" => $userId))->fetch();
			if(!self::$users[$userId])
			{
				self::$users[$userId] = \CUser::getById($userId)->fetch();
				self::$users[$userId]["SHOW_NAME"] = \COption::getOptionString("forum", "USER_SHOW_NAME", "Y");
			}
		}
		return self::$users[$userId];
	}

	protected function getUserName($userId)
	{
		$user = self::getUserFromForum($userId);
		$name = "";
		if (is_array($user))
		{
			$name = ($user["SHOW_NAME"] == "Y" ? trim($user["NAME"]." ".$user["LAST_NAME"]) : "");
			$name = (empty($name) ? $user["LOGIN"] : $name);
		}
		return $name;
	}
}

