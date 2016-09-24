<?php

namespace Bitrix\Forum\Comments;

use Bitrix\Forum\Internals\Error\ErrorCollection;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Forum\Internals\Error\Error;
use \Bitrix\Forum\Comments\TaskEntity;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Event;
use \Bitrix\Main\EventResult;
use \Bitrix\Main\ArgumentTypeException;
use \Bitrix\Main\ArgumentException;

Loc::loadMessages(__FILE__);

class Comment extends BaseObject
{

	const ERROR_PARAMS_MESSAGE = 'params0006';
	const ERROR_PERMISSION = 'params0007';
	const ERROR_MESSAGE_IS_NULL = 'params0008';

	/* @var integer */
	private $id = 0;
	/* @var array */
	private $message = null;

	private function prepareFields(array &$params, ErrorCollection $errorCollectionParam)
	{
		$result = array(
			"FORUM_ID" => $this->topic["FORUM_ID"],
			"TOPIC_ID" => $this->topic["ID"],
			"POST_MESSAGE" => trim($params["POST_MESSAGE"]),
			"AUTHOR_ID" => $params["AUTHOR_ID"],
			"AUTHOR_NAME" => trim($params["AUTHOR_NAME"]),
			"AUTHOR_EMAIL" => trim($params["AUTHOR_EMAIL"]),
			"USE_SMILES" => ($params["USE_SMILES"] == "Y" ? "Y" : "N"),
			"APPROVED" => $this->topic["APPROVED"],
			"XML_ID" => $this->getEntity()->getXmlId(),
			"USER_ID" => $this->getUser()->getId()
		);
		$errorCollection = new ErrorCollection();
		if (strlen($result["POST_MESSAGE"]) <= 0)
			$errorCollection->addOne(new Error(Loc::getMessage("FORUM_CM_ERR_EMPTY_TEXT"), self::ERROR_PARAMS_MESSAGE));

		if (strlen($result["AUTHOR_NAME"]) <= 0 && $result["AUTHOR_ID"] > 0)
			$result["AUTHOR_NAME"] = self::getUserName($result["AUTHOR_ID"]);
		if (strlen($result["AUTHOR_NAME"]) <= 0)
			$errorCollection->addOne(new Error(Loc::getMessage("FORUM_CM_ERR_EMPTY_AUTHORS_NAME"), self::ERROR_PARAMS_MESSAGE));

		if (is_array($params["FILES"]) && in_array($this->forum["ALLOW_UPLOAD"], array("Y", "F", "A")))
		{
			$result["FILES"] = array();
			foreach ($params["FILES"] as $key => $val)
			{
				if (intval($val["FILE_ID"]) > 0)
				{
					$val["del"] = ($val["del"] == "Y" ? "Y" : "");
				}
				$result["FILES"][$key] = $val;
			}
			$res = array(
				"FORUM_ID" => $this->forum["ID"],
				"TOPIC_ID" => $this->topic["ID"],
				"MESSAGE_ID" => 0,
				"USER_ID" => $result["AUTHOR_ID"],
				"FORUM" => $this->forum
			);
			if (!\CForumFiles::CheckFields($result["FILES"], $res, "NOT_CHECK_DB"))
			{
				$text = "File upload error.";
				if (($ex = $this->getApplication()->getException()) && $ex)
					$text = $ex->getString();
				$errorCollection->addOne(new Error($text, self::ERROR_PARAMS_MESSAGE));
			}
		}
		if ($result["APPROVED"] != "N")
		{
			$result["APPROVED"] = ($this->forum["MODERATION"] != "Y" || $this->getEntity()->canModerate()) ? "Y" : "N";
		}

		if ($errorCollection->hasErrors())
		{
			$errorCollectionParam->add($errorCollection->toArray());
			return false;
		}
		else
		{
			global $USER_FIELD_MANAGER;
			$USER_FIELD_MANAGER->EditFormAddFields("FORUM_MESSAGE", $result);
			$params = $result;
			return true;
		}
	}

	private function updateStatisticModule($mid)
	{
		if (\CModule::IncludeModule("statistic"))
		{
			$F_EVENT1 = $this->forum["EVENT1"];
			$F_EVENT2 = $this->forum["EVENT2"];
			$F_EVENT3 = $this->forum["EVENT3"];
			if (empty($F_EVENT3))
			{
				$site = (array) \CForumNew::GetSites($this->forum["ID"]);
				$F_EVENT3 = \CForumNew::PreparePath2Message((array_key_exists(SITE_ID, $site) ? $site[SITE_ID] : reset($site)),
					array(
						"FORUM_ID" => $this->forum["ID"],
						"TOPIC_ID" => $this->topic["ID"],
						"MESSAGE_ID" => $mid
					)
				);
			}
			\CStatistics::Set_Event($F_EVENT1, $F_EVENT2, $F_EVENT3);
		}
	}

	/**
	 * Adds new comment
	 * @param array $params
	 * @return array|false
	 */
	public function add(array $params)
	{
		$params = array(
			"POST_MESSAGE" => trim($params["POST_MESSAGE"]),
			"AUTHOR_ID" => $this->getUser()->getId(),
			"AUTHOR_NAME" => trim($params["AUTHOR_NAME"]),
			"AUTHOR_EMAIL" => trim($params["AUTHOR_EMAIL"]),
			"USE_SMILES" => $params["USE_SMILES"],
			"FILES" => $params["FILES"]
		);

		if ($this->prepareFields($params, $this->errorCollection))
		{
			$AUTHOR_IP = $AUTHOR_IP_tmp = \ForumGetRealIP();
			$AUTHOR_REAL_IP = $_SERVER['REMOTE_ADDR'];
			if (\COption::GetOptionString("forum", "FORUM_GETHOSTBYADDR", "N") == "Y")
			{
				$AUTHOR_IP = @gethostbyaddr($AUTHOR_IP);
				$AUTHOR_REAL_IP = ($AUTHOR_IP_tmp == $AUTHOR_REAL_IP ? $AUTHOR_IP : @gethostbyaddr($AUTHOR_REAL_IP));
			}
			$params["AUTHOR_IP"] = ($AUTHOR_IP!==False) ? $AUTHOR_IP : "<no address>";
			$params["AUTHOR_REAL_IP"] = ($AUTHOR_REAL_IP!==False) ? $AUTHOR_REAL_IP : "<no address>";
			$params["GUEST_ID"] = $_SESSION["SESS_GUEST_ID"];

			if (!(($mid = \CForumMessage::Add($params, false)) > 0))
			{
				$text = Loc::getMessage("ADDMESS_ERROR_ADD_MESSAGE");
				if (($str = $this->getApplication()->getException()) && $str)
					$text = $str->getString();
				$this->errorCollection->addOne(new Error($text, self::ERROR_PARAMS_MESSAGE));
			}
			else
			{
				$this->updateStatisticModule($mid);
				\CForumMessage::SendMailMessage($mid, array(), false, "NEW_FORUM_MESSAGE");

				$this->setComment($mid);

				$event = new Event("forum", "OnAfterCommentAdd", array(
					$this->entity->getType(),
					$this->entity->getId(),
					array(
						"TOPIC_ID" => $this->topic["ID"],
						"MESSAGE_ID" => $mid,
						"PARAMS" => $params,
						"MESSAGE" => $this->getComment()
					))
				);
				$event->send();
				return $this->getComment();
			}
		}
		return false;
	}
	/**
	 * Edit new comment
	 * @param array $params
	 * @return array|false
	 */
	public function edit(array $params)
	{
		$paramsRaw = $params;
		if ($this->message === null)
		{
			$this->errorCollection->addOne(new Error(Loc::getMessage("FORUM_CM_ERR_COMMENT_IS_LOST1"), self::ERROR_MESSAGE_IS_NULL));
		}
		else
		{
			$run = true;
			$fields = array(
				$this->entity->getType(),
				$this->entity->getId(),
				array(
					"TOPIC_ID" => $this->topic["ID"],
					"MESSAGE_ID" => $this->message["ID"],
					"PARAMS" => &$paramsRaw,
					"ACTION" => "EDIT",
					"MESSAGE" => $this->getComment()
				)
			);
			/***************** Events OnBeforeCommentUpdate ******************/
			$event = new Event("forum", "OnBeforeCommentUpdate", $fields);
			$event->send($this);
			if($event->getResults())
			{
				foreach($event->getResults() as $eventResult)
				{
					if($eventResult->getType() != EventResult::SUCCESS)
					{
						$run = false;
						break;
					}
				}
			}
			/***************** /Events *****************************************/
			if (!$run)
			{
				$text = Loc::getMessage("ADDMESS_ERROR_EDIT_MESSAGE");
				if (($str = $this->getApplication()->getException()) && $str)
					$text = $str->getString();
				$this->errorCollection->addOne(new Error($text, self::ERROR_PARAMS_MESSAGE));
			}
			else if (($params = array(
				"POST_MESSAGE" => trim($params["POST_MESSAGE"]),
				"AUTHOR_ID" => $this->message["AUTHOR_ID"],
				"AUTHOR_NAME" => (array_key_exists("AUTHOR_NAME", $params) ? trim($params["AUTHOR_NAME"]) : $this->message["AUTHOR_NAME"]),
				"AUTHOR_EMAIL" => (array_key_exists("AUTHOR_EMAIL", $params) ? trim($params["AUTHOR_EMAIL"]) : $this->message["AUTHOR_EMAIL"]),
				"USE_SMILES" => $params["USE_SMILES"],
				"FILES" => $params["FILES"]
			)) && $this->prepareFields($params, $this->errorCollection))
			{
				if (array_key_exists("EDIT_REASON", $paramsRaw))
				{
					$params += array(
						"EDITOR_ID" => $this->getUser()->getId(),
						"EDITOR_NAME" => trim($paramsRaw["EDITOR_NAME"]),
						"EDITOR_EMAIL" => trim($paramsRaw["EDITOR_EMAIL"]),
						"EDIT_REASON" => trim($paramsRaw["EDIT_REASON"]),
						"EDIT_DATE" => ""
					);
					if (strlen($params["EDITOR_NAME"]) <= 0)
						$params["EDITOR_NAME"] = ($params["EDITOR_ID"] > 0 ? self::getUserName($params["EDITOR_ID"]) : Loc::getMessage("GUEST"));
				}
				if (!(($mid = \CForumMessage::Update($this->message["ID"], $params)) > 0))
				{
					$text = Loc::getMessage("ADDMESS_ERROR_EDIT_MESSAGE");
					if (($str = $this->getApplication()->getException()) && $str)
						$text = $str->getString();
					$this->errorCollection->addOne(new Error($text, self::ERROR_PARAMS_MESSAGE));
				}
				else
				{
					if ($params["AUTHOR_ID"] != $this->getUser()->getId() || \COption::GetOptionString("forum", "LOGS", "Q") < "U")
					{
						$res_log = array();
						foreach ($paramsRaw as $key => $val)
						{
							if ($val == $this->message[$key])
								continue;
							else if ($key == "FILES")
								$res_log["FILES"] = GetMessage("F_ATTACH_IS_MODIFIED");
							else
								$res_log[$key] = array(
									"before" => $this->message[$key],
									"after" => $val
								);
						}
						if (!empty($res_log))
						{
							$res_log["FORUM_ID"] = $this->forum["ID"];
							$res_log["TOPIC_ID"] = $this->topic["ID"];
							$res_log["TITLE"] = $this->topic["TITLE"];
							\CForumEventLog::Log("message", "edit", $this->message["ID"], serialize($res_log));
						}
					}
					$this->updateStatisticModule($mid);
					\CForumMessage::SendMailMessage($mid, array(), false, "EDIT_FORUM_MESSAGE");

					$this->setComment($mid);
					$fields["PARAMS"] = $params;
					/***************** Events OnCommentUpdate ************************/
					$event = new Event("forum", "OnCommentUpdate", $fields);
					$event->send();
					/***************** Events OnAfterCommentUpdate *******************/
					$event = new Event("forum", "OnAfterCommentUpdate", $fields);
					$event->send();
					/***************** /Events *****************************************/
					return $this->getComment();
				}
			}
		}
		return false;
	}

	public function delete()
	{
		if ($this->message === null)
		{
			$this->errorCollection->addOne(new Error(Loc::getMessage("FORUM_CM_ERR_COMMENT_IS_LOST2"), self::ERROR_MESSAGE_IS_NULL));
		}
		else
		{
			$run = true;
			$fields = array(
				$this->entity->getType(),
				$this->entity->getId(),
				array(
					"TOPIC_ID" => $this->topic["ID"],
					"MESSAGE_ID" => $this->message["ID"],
					"MESSAGE" => $this->getComment(),
					"ACTION" => "DEL"
				));
			/***************** Events OnBeforeCommentDelete ******************/
			$event = new Event("forum", "OnBeforeCommentDelete", $fields);
			$event->send($this);
			if($event->getResults())
			{
				foreach($event->getResults() as $eventResult)
				{
					if($eventResult->getType() != EventResult::SUCCESS)
					{
						$run = false;
						break;
					}
				}
			}
			/***************** /Events *****************************************/
			if ($run && \CForumMessage::Delete($this->message["ID"]))
			{
				\CForumEventLog::Log("message", "delete", $this->message["ID"], serialize($this->message + array("TITLE" => $this->topic["TITLE"])));
				/***************** Events OnCommentDelete ************************/
				$event = new Event("forum", "OnCommentDelete", $fields);
				$event->send();
				/***************** Events OnAfterCommentUpdate *********************/
				$event = new Event("forum", "OnAfterCommentUpdate", $fields); // It is not a mistake
				$event->send();
				/***************** /Events *****************************************/
			}
			else
			{
				$text = Loc::getMessage("FORUM_CM_ERR_DELETE");
				if (($ex = $this->getApplication()->getException()) && $ex)
					$text = $ex->getString();
				$this->errorCollection->addOne(new Error($text, self::ERROR_PARAMS_MESSAGE));
			}
		}
		return true;
	}

	public function moderate($show)
	{
		if ($this->message === null)
		{
			$this->errorCollection->addOne(new Error(Loc::getMessage("FORUM_CM_ERR_COMMENT_IS_LOST3"), self::ERROR_MESSAGE_IS_NULL));
		}
		else
		{
			$run = true;
			$fields = array(
				$this->entity->getType(),
				$this->entity->getId(),
				array(
					"TOPIC_ID" => $this->topic["ID"],
					"MESSAGE_ID" => $this->message["ID"],
					"MESSAGE" => $this->getComment(),
					"ACTION" => $show ? "SHOW" : "HIDE",
					"PARAMS" => array("APPROVED" => ($show ? "Y" : "N"))
				));
			/***************** Events OnBeforeCommentModerate ****************/
			$event = new Event("forum", "OnBeforeCommentModerate", $fields);
			$event->send($this);
			if($event->getResults())
			{
				foreach($event->getResults() as $eventResult)
				{
					if($eventResult->getType() != EventResult::SUCCESS)
					{
						$run = false;
						break;
					}
				}
			}
			/***************** /Events *****************************************/
			if ($run && $this->message["APPROVED"] == $fields["PARAMS"]["APPROVED"] || ($mid = \CForumMessage::Update($this->message["ID"], $fields["PARAMS"])) > 0)
			{
				$this->setComment($this->message["ID"]);
				/***************** Event onMessageModerate ***********************/
				$event = new Event("forum", "onMessageModerate", array($this->message["ID"], ($show ? "SHOW" : "HIDE"), $this->message, $this->topic));
				$event->send();
				/***************** Events OnCommentModerate ************************/
				$event = new Event("forum", "OnCommentModerate", $fields);
				$event->send();
				/***************** Events OnAfterCommentUpdate *********************/
				$event = new Event("forum", "OnAfterCommentUpdate", $fields); // It is not a mistake
				$event->send();
				/***************** /Events *****************************************/
				$res = serialize(array(
					"ID" => $this->message["ID"],
					"AUTHOR_NAME" => $this->message["AUTHOR_NAME"],
					"POST_MESSAGE" => $this->message["POST_MESSAGE"],
					"TITLE" => $this->topic["TITLE"],
					"TOPIC_ID" => $this->topic["ID"],
					"FORUM_ID" => $this->topic["FORUM_ID"]));
				\CForumMessage::SendMailMessage($this->message["ID"], array(), false, ($show ? "NEW_FORUM_MESSAGE" : "EDIT_FORUM_MESSAGE"));
				\CForumEventLog::Log("message", ($show ? "approve" : "unapprove"), $this->message["ID"], $res);
				return $this->getComment();
			}
			else
			{
				$text = Loc::getMessage("FORUM_CM_ERR_MODERATE");
				if (($ex = $this->getApplication()->getException()) && $ex)
					$text = $ex->getString();
				$this->errorCollection->addOne(new Error($text, self::ERROR_PARAMS_MESSAGE));
			}
		}
		return false;
	}

	public function canEdit()
	{
		$result = false;
		if ($this->message === null)
		{
			$this->errorCollection->addOne(new Error(Loc::getMessage("FORUM_CM_ERR_COMMENT_IS_LOST4"), self::ERROR_MESSAGE_IS_NULL));
		}
		else
		{
			$result = ($this->entity->canEdit() || (
					((int) $this->message["AUTHOR_ID"] > 0) &&
					((int) $this->message["AUTHOR_ID"] == (int) $this->getUser()->getId()) &&
					$this->entity->canEditOwn()
				));
		}
		return $result;
	}

	public function canDelete()
	{
		return $this->canEdit();
	}

	public function setComment($id)
	{
		$id = intval($id);
		$message = ($id > 0 ? \CForumMessage::getById($id) : null);
		if (!empty($message))
		{
			if ($message["TOPIC_ID"] != $this->topic["ID"])
			{
				throw new ArgumentException(Loc::getMessage("ACCESS_DENIED"), self::ERROR_PERMISSION);
			}
			$this->id = $id;
			$this->message = $message;
		}
	}

	public function getComment()
	{
		return $this->message;
	}

	/**
	 * Creates new
	 * @param Feed $feed
	 * @param $id
	 * @return Comment
	 */
	public static function createFromId(Feed $feed, $id)
	{
		$forum = $feed->getForum();
		$comment = new Comment($forum["ID"], $feed->getEntity()->getFullId(), $feed->getUser()->getId());
		$comment->getEntity()->setPermission($feed->getEntity()->getPermission());
		$comment->setComment($id);
		return $comment;
	}
	/**
	 * Creates new
	 * @param Feed $feed
	 * @param $id
	 * @return Comment
	 */
	public static function create(Feed $feed)
	{
		$forum = $feed->getForum();
		$comment = new Comment($forum["ID"], $feed->getEntity()->getFullId(), $feed->getUser()->getId());
		$comment->getEntity()->setPermission($feed->getEntity()->getPermission());
		return $comment;
	}
}