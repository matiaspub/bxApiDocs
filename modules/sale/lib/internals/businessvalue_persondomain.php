<?php

namespace Bitrix\Sale\Internals;

use Bitrix\Main;

class BusinessValuePersonDomainTable extends Main\Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_bizval_persondomain';
	}

	public static function getMap()
	{
		return array(
			new Main\Entity\IntegerField('PERSON_TYPE_ID', array('primary' => true)),
			new Main\Entity\StringField ('DOMAIN'        , array('primary' => true, 'size' => 1)),

			new Main\Entity\ReferenceField('PERSON_TYPE_REFERENCE', 'Bitrix\Sale\Internals\PersonTypeTable',
				array('=this.PERSON_TYPE_ID' => 'ref.ID'),
				array('join_type' => 'INNER')
			),
		);
	}
}
