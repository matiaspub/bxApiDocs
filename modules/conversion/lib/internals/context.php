<?php

namespace Bitrix\Conversion\Internals;

use Bitrix\Main\Entity;

/** @internal */
class ContextTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_conv_context';
	}

	public static function getMap()
	{
		return array(
			new Entity\IntegerField('ID'      , array('primary'  => true, 'autocomplete' => true)),
			new Entity\StringField ('SNAPSHOT', array('required' => true, 'size' => 64)),
		);
	}

	public static function getFilePath()
	{
		return __FILE__;
	}
}
