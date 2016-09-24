<?php

namespace Bitrix\Sale\Internals;

use Bitrix\Main;

class BusinessValueCode1CTable extends Main\Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_bizval_code_1C';
	}

	public static function getMap()
	{
		return array(
			new Main\Entity\IntegerField('PERSON_TYPE_ID', array('primary' => true)),
			new Main\Entity\IntegerField('CODE_INDEX'    , array('primary' => true)),
			new Main\Entity\StringField ('NAME'          , array('required' => true, 'size' => 255)),
		);
	}
}
