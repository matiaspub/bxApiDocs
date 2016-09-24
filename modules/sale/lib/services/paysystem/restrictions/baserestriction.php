<?php

namespace Bitrix\Sale\PaySystem\Restrictions;

use Bitrix\Main\NotImplementedException;
use Bitrix\Sale\Internals\CollectableEntity;

abstract class BaseRestriction
{
	/**
	 * @param CollectableEntity $entity
	 * @param $restriction
	 * @throws NotImplementedException
	 * @return bool
	 */
	static public function checkAptitude(CollectableEntity $entity, $restriction)
	{
		throw new NotImplementedException();
	}

	/**
	 * @param $restrictionId
	 * @return mixed
	 */
	public abstract function delete($restrictionId);

	/**
	 * @return mixed
	 */
	public abstract function getName();

	/**
	 * @return mixed
	 */
	public abstract function getDescription();

}