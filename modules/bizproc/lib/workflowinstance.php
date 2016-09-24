<?php

namespace Bitrix\Bizproc;

use Bitrix\Main;
use Bitrix\Main\Entity;

class WorkflowInstanceTable extends Entity\DataManager
{
	const LOCKED_TIME_INTERVAL = 300;

	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_bp_workflow_instance';
	}

	/**
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'string',
				'primary' => true,
			),
			'WORKFLOW' => array(
				'data_type' => 'string'
			),
			'STATUS' => array(
				'data_type' => 'integer'
			),
			'MODIFIED' => array(
				'data_type' => 'datetime'
			),
			'OWNER_ID' => array(
				'data_type' => 'string'
			),
			'OWNED_UNTIL' => array(
				'data_type' => 'datetime'
			),
			'STATE' => array(
				'data_type' => '\Bitrix\Bizproc\WorkflowStateTable',
				'reference' => array(
					'=this.ID' => 'ref.ID'
				),
				'join_type' => 'LEFT',
			),
		);
	}

	/**
	 * @param array $data Entity data.
	 * @throws Main\NotImplementedException
	 * @return void
	 */
	public static function add(array $data)
	{
		throw new Main\NotImplementedException("Use CBPStateService class.");
	}

	/**
	 * @param mixed $primary Primary key.
	 * @param array $data Entity data.
	 * @throws Main\NotImplementedException
	 * @return void
	 */
	public static function update($primary, array $data)
	{
		throw new Main\NotImplementedException("Use CBPStateService class.");
	}
}
