<?php

namespace Bitrix\Sale\Internals;

use Bitrix\Main;

class BusinessValueCodeTable extends Main\Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_bizval_code';
	}

	public static function getMap()
	{
		return array(
			new Main\Entity\IntegerField('ID'       , array('primary' => true, 'autocomplete' => true)),
			new Main\Entity\StringField ('NAME'     , array('required' => true)),
			new Main\Entity\StringField ('DOMAIN'  ),
			new Main\Entity\IntegerField('GROUP_ID'),
			new Main\Entity\IntegerField('SORT'     , array('default_value' => 100)),

			new Main\Entity\ReferenceField('GROUP', 'Bitrix\Sale\Internals\BusinessValueGroupTable',
				array('=this.GROUP_ID' => 'ref.ID'),
				array('join_type' => 'LEFT')
			),
		);
	}
}
