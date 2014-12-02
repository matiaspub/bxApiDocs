<?php

namespace Bitrix\Main;

use Bitrix\Main\Entity;

class TaskOperationTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_task_operation';
	}

	public static function getMap()
	{
		return array(
			'TASK_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'OPERATION_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'OPERATION' => array(
				'data_type' => 'Bitrix\Main\OperationTable',
				'reference' => array('=this.OPERATION_ID' => 'ref.ID'),
			),
			'TASK' => array(
				'data_type' => 'Bitrix\Main\TaskTable',
				'reference' => array('=this.TASK_ID' => 'ref.ID'),
			),
		);
	}
}