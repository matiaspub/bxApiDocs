<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\Sale\Internals;

use	Bitrix\Main,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class StatusLangTable extends Main\Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_status_lang';
	}

	public static function getMap()
	{
		return array(

			new Main\Entity\StringField('STATUS_ID', array(
				'primary' => true,
				'format'  => '/^[A-Za-z]{1,2}$/',
				'title'   => Loc::getMessage('B_SALE_STATUS_LANG_STATUS_ID'),
			)),

			new Main\Entity\StringField('LID', array(
				'primary' => true,
				'format' => '/^[a-z]{2}$/',
				'title'   => Loc::getMessage('B_SALE_STATUS_LANG_LID'),
			)),

			new Main\Entity\StringField('NAME', array(
				'required' => true,
				'title'   => Loc::getMessage('B_SALE_STATUS_LANG_NAME'),
			)),

			new Main\Entity\StringField('DESCRIPTION', array(
				'title'   => Loc::getMessage('B_SALE_STATUS_LANG_DESCRIPTION'),
			)),

			new Main\Entity\ReferenceField('STATUS', 'Bitrix\Sale\Internals\StatusTable',
				array('=this.STATUS_ID' => 'ref.ID'),
                array('join_type' => 'INNER')
			),

			// field for filter operation on entity
			//'ID' => array(
			//	'data_type' => 'string',
			//	'expression' => array(
			//		'%s', 'STATUS_ID'
			//	)
			//),

		);
	}

	public static function deleteByStatus($statusId)
	{
		$result = self::getList(array(
			'select' => array('STATUS_ID', 'LID'),
			'filter' => array('=STATUS_ID' => $statusId)
		));

		while ($primary = $result->fetch())
		{
			self::delete($primary);
		}
	}
}
