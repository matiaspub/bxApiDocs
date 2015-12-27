<?php

namespace Bitrix\Sale\Internals;

use Bitrix\Main;

class BusinessValueParentTable extends Main\Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_bizval_parent';
	}

	public static function getMap()
	{
		return array(
			new Main\Entity\IntegerField('ID'       , array('primary' => true, 'autocomplete' => true)),
			new Main\Entity\StringField ('NAME'     , array('required' => true)),
			new Main\Entity\StringField ('LANG_SRC'),
		);
	}
}
