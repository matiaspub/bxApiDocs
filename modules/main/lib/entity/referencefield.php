<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Main\Entity;

/**
 * Reference field describes relation 1-to-1 or 1-to-many between two entities
 * @package bitrix
 * @subpackage main
 */
class ReferenceField extends Field
{
	protected $refEntity;

	protected $refEntityName;

	protected $reference;

	protected $join_type = 'LEFT';


	public function __construct($name, Base $entity, $refEntityName, $reference, $parameters = array())
	{
		parent::__construct($name, $parameters['data_type'], $entity);

		$this->refEntityName = $refEntityName;

		$this->reference = $reference;

		if (isset($parameters['join_type']))
		{
			$join_type = strtoupper($parameters['join_type']);

			if (in_array($join_type, array('LEFT', 'INNER', 'RIGHT'), true))
			{
				$this->join_type = $join_type;
			}
		}
	}

	static public function validateValue($value, $row, Result $result)
	{
		throw new Exception('Reference field doesn\'t support value set up and validation.');
	}

	public function getRefEntity()
	{
		if ($this->refEntity === null)
		{
			$this->refEntity = Base::getInstance($this->refEntityName);
		}

		return $this->refEntity;
	}

	public function getReference()
	{
		return $this->reference;
	}

	public function getJoinType()
	{
		return $this->join_type;
	}
}


