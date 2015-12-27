<?php

namespace Bitrix\Sale\Internals;

use Bitrix\Main;

class BusinessValueTable extends Main\Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_bizval';
	}

	public static function getMap()
	{
		return array(
			new Main\Entity\IntegerField('ID'             , array('primary' => true, 'autocomplete' => true)),
			new Main\Entity\IntegerField('CODE_ID'        , array('required' => true)),
			new Main\Entity\IntegerField('PERSON_TYPE_ID'),
			new Main\Entity\StringField ('ENTITY'        ),
			new Main\Entity\StringField ('ITEM'          ),

			new Main\Entity\ReferenceField('CODE', 'Bitrix\Sale\Internals\BusinessValueCodeTable',
				array('=this.CODE_ID' => 'ref.ID'),
				array('join_type' => 'INNER')
			),

			new Main\Entity\ReferenceField('lCODE', 'Bitrix\Sale\Internals\BusinessValueCodeTable',
				array('=this.CODE_ID' => 'ref.ID'),
				array('join_type' => 'LEFT')
			),
		);
	}
}
