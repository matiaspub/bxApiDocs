<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\Sale\Internals;

use Bitrix\Main;

class StatusGroupTaskTable extends Main\Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_status_group_task';
	}

	public static function getMap()
	{
		return array(

			new Main\Entity\StringField('STATUS_ID', array(
				'primary' => true,
				'format'  => '/^[A-Za-z?0-9]{1,2}$/',
			)),

			new Main\Entity\IntegerField('GROUP_ID', array(
				'primary' => true,
				'format' => '/^[0-9]{1,18}$/',
			)),

			new Main\Entity\IntegerField('TASK_ID', array(
				'primary' => true,
				'format' => '/^[0-9]{1,18}$/',
			)),

			new Main\Entity\ReferenceField('STATUS', 'Bitrix\Sale\Internals\StatusTable',
				array('=this.STATUS_ID' => 'ref.ID'),
				array('join_type' => 'LEFT')
			),

			new Main\Entity\ReferenceField('GROUP', 'Bitrix\Main\GroupTable',
				array('=this.GROUP_ID' => 'ref.ID'),
				array('join_type' => 'INNER')
			),

			new Main\Entity\ReferenceField('TASK', 'Bitrix\Main\TaskTable',
				array('=this.TASK_ID' => 'ref.ID'),
				array('join_type' => 'INNER')
			),

		);
	}

	public static function deleteByStatus($statusId)
	{
		$result = self::getList(array(
			'select' => array('STATUS_ID', 'GROUP_ID', 'TASK_ID'),
			'filter' => array('=STATUS_ID' => $statusId)
		));
		while ($primary = $result->fetch())
			self::delete($primary);
	}
}
