<?php

namespace Bitrix\Sale\Internals;

use	Bitrix\Main\Entity\DataManager,
	Bitrix\Main\Entity\StringField,
	Bitrix\Main\Entity\IntegerField;

class OrderPropsVariantTable extends DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_order_props_variant';
	}

	public static function getMap()
	{
		return array(
			new IntegerField('ID'            , array('primary' => true, 'autocomplete' => true)),
			new IntegerField('ORDER_PROPS_ID', array('required' => true)),
			new StringField ('NAME'          , array('required' => true)),
			new StringField ('VALUE'        ),
			new IntegerField('SORT'          , array('default_value' => 100)),
			new StringField ('DESCRIPTION'  ),
		);
	}
}
