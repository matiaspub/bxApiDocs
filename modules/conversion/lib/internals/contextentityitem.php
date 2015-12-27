<?php

namespace Bitrix\Conversion\Internals;

use Bitrix\Main\Entity;

/** @internal */
class ContextEntityItemTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_conv_context_entity_item';
	}

	public static function getMap()
	{
		return array(
			new Entity\IntegerField('CONTEXT_ID', array('primary' => true)),
			new Entity\StringField ('ENTITY'    , array('primary' => true, 'size' => 30)),
			new Entity\StringField ('ITEM'      , array('primary' => true, 'size' => 30)),

			new Entity\ReferenceField('CONTEXT', 'ContextTable',
				array('=this.CONTEXT_ID' => 'ref.ID'),
				array('join_type' => 'INNER')
			),
		);
	}

	public static function getFilePath()
	{
		return __FILE__;
	}
}
