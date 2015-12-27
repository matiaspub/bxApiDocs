<?php

namespace Bitrix\Sale\Internals;

use Bitrix\Main;

class BusinessValueCodeParentTable extends Main\Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_bizval_codeparent';
	}

	public static function getMap()
	{
		return array(
			new Main\Entity\IntegerField('CODE_ID'  , array('primary' => true)),
			new Main\Entity\IntegerField('PARENT_ID', array('primary' => true)),

			new Main\Entity\ReferenceField('CODE', 'Bitrix\Sale\Internals\BusinessValueCodeTable',
				array('=this.CODE_ID' => 'ref.ID'),
				array('join_type' => 'INNER')
			),

			new Main\Entity\ReferenceField('PARENT', 'Bitrix\Sale\Internals\BusinessValueParentTable',
				array('=this.PARENT_ID' => 'ref.ID'),
				array('join_type' => 'INNER')
			),
		);
	}
}
