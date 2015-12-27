<?php

namespace Bitrix\Forum\Comments;

use Bitrix\Forum\Internals\Error\ErrorCollection;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Forum\Internals\Error\Error;
use \Bitrix\Forum\Comments\ForumEntity;
use \Bitrix\Forum\Comments\TaskEntity;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Event;
use \Bitrix\Main\ArgumentTypeException;
use \Bitrix\Main\ArgumentException;

Loc::loadMessages(__FILE__);

class Feed extends BaseObject
{
	const ERROR_PARAMS_MESSAGE = 'params0004';
	const ERROR_PERMISSION = 'params0005';

	private function checkTopic()
	{
		if (empty($this->topic))
		{
			$this->topic = $this->createTopic();
		}
		return ($this->topic !== null);
	}

	/**
	 * Returns true if entity allows adding
	 * @return bool
	 */
	public function canAdd()
	{
		global $USER;
		return $this->entity->canAdd();
	}

	/**
	 * Returns true if entity allows reading
	 * @return bool
	 */
	public function canRead()
	{
		return $this->entity->canRead();
	}

	/**
	 * Returns true if entity allows editing
	 * @return bool
	 */
	public function canEdit()
	{
		return $this->entity->canEdit();
	}

	/**
	 * @param integer $commentId Message ID in b_forum_message to edit.
	 * @return bool
	 */
	static public function canEditComment($commentId)
	{
		return Comment::createFromId($this, $commentId)->canEdit();
	}

	/**
	 * Returns true if entity allows deleting.
	 * @return bool
	 */
	public function canDelete()
	{
		return $this->entity->canEdit();
	}

	/**
	 * @param  integer $commentId Message ID in b_forum_message to delete.
	 * @return bool
	 */
	static public function canDeleteComment($commentId)
	{
		return Comment::createFromId($this, $commentId)->canEdit();
	}

	/**
	 * @return bool
	 */
	public function canModerate()
	{
		global $USER;
		return $this->entity->canModerate();
	}

	/**
	 * Add a comment
	 * @param array $params Fields for new message to add in table b_forum_message.
	 * @return array|bool
	 */
	public function add(array $params)
	{
		if (!$this->canAdd())
			$this->errorCollection->addOne(new Error(Loc::getMessage("FORUM_CM_RIGHTS1"), self::ERROR_PERMISSION));
		else if ($this->checkTopic())
		{
			$comment = Comment::create($this);
			$comment->add($params);
			if ($comment->hasErrors())
				$this->errorCollection->add($comment->getErrors());
			else
				return $comment->getComment();
		}
		return false;
	}

	/**
	 * Edit a comment
	 * @param integer $id Message id.
	 * @param array $params Fields to edit message.
	 * @return array|bool
	 */
	public function edit($id, array $params)
	{
		$comment = Comment::createFromId($this, $id);
		if (!$this->canEdit() && !$comment->canEdit())
			$this->errorCollection->addOne(new Error(Loc::getMessage("FORUM_CM_RIGHTS2"), self::ERROR_PERMISSION));
		else
		{
			$comment->edit($params);
			if ($comment->hasErrors())
				$this->errorCollection->add($comment->getErrors());
			else
				return $comment->getComment();
		}
		return false;
	}

	/**
	 * Delete a comment
	 * @param integer $id Message id.
	 * @return array|bool
	 */
	public function delete($id)
	{
		$comment = Comment::createFromId($this, $id);
		if (!$this->canDelete() && !$comment->canDelete())
			$this->errorCollection->addOne(new Error(Loc::getMessage("FORUM_CM_RIGHTS3"), self::ERROR_PERMISSION));
		else
		{
			$comment->delete();
			if ($comment->hasErrors())
				$this->errorCollection->add($comment->getErrors());
			else
				return $comment->getComment();
		}
		return false;
	}

	/**
	 * Moderate comment with id
	 * @param integer $id Message id.
	 * @param boolean $show State for moderating: true - show, false - hide.
	 * @return array|bool
	 */
	public function moderate($id, $show)
	{
		$comment = Comment::createFromId($this, $id);
		if (!$this->canModerate())
			$this->errorCollection->addOne(new Error(Loc::getMessage("FORUM_CM_RIGHTS4"), self::ERROR_PERMISSION));
		else
		{
			$comment->moderate($show);
			if ($comment->hasErrors())
				$this->errorCollection->add($comment->getErrors());
			else
				return $comment->getComment();
		}
		return false;
	}

	/**
	 * Mainly this function for forum entity. In this case params have to from the list: A < E < I < M < Q < U < Y
	 * A - NO ACCESS		E - READ			I - ANSWER
	 * M - NEW TOPIC		Q - MODERATE	U - EDIT			Y - FULL_ACCESS
	 * @param string $permission A,E,I,M,Q,U,Y.
	 * @return $this
	 */
	public function setPermission($permission)
	{
		return $this->entity->setPermission($permission);
	}

	/**
	 * @param boolean $allow True or false.
	 * @return $this
	 */
	public function setEditOwn($allow)
	{
		return $this->entity->setEditOwn($allow);
	}

	/**
	 * Returns permission From list: A < E < I < M < Q < U < Y.
	 * @return string
	 */
	public function getPermission()
	{
		return $this->entity->getPermission();
	}
}