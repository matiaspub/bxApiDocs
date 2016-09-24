<?php

namespace Bitrix\Bizproc;

use Bitrix\Main;
use Bitrix\Main\Entity;

class WorkflowStateTable extends Entity\DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_bp_workflow_state';
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
			'MODULE_ID' => array(
				'data_type' => 'string'
			),
			'ENTITY' => array(
				'data_type' => 'string'
			),
			'DOCUMENT_ID' => array(
				'data_type' => 'string'
			),
			'DOCUMENT_ID_INT' => array(
				'data_type' => 'integer'
			),
			'WORKFLOW_TEMPLATE_ID' => array(
				'data_type' => 'integer'
			),
			'STATE' => array(
				'data_type' => 'string'
			),
			'STATE_PARAMETERS' => array(
				'data_type' => 'string'
			),
			'MODIFIED' => array(
				'data_type' => 'datetime'
			),
			'STARTED' => array(
				'data_type' => 'datetime'
			),
			'STARTED_BY' => array(
				'data_type' => 'integer'
			),
			'STARTED_USER' => array(
				'data_type' => '\Bitrix\Main\UserTable',
				'reference' => array(
					'=this.STARTED_BY' => 'ref.ID'
				),
				'join_type' => 'LEFT',
			),
			'INSTANCE' => array(
				'data_type' => '\Bitrix\Bizproc\WorkflowInstanceTable',
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

	/**
	 * @param mixed $primary Primary key.
	 * @throws Main\NotImplementedException
	 * @return void
	 */
	public static function delete($primary)
	{
		throw new Main\NotImplementedException("Use CBPStateService class.");
	}
}
