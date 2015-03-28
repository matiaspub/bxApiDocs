<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Crm;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class IBlockElementProxyTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_iblock_element';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'IBLOCK_ID' => array(
				'data_type' => 'integer'
			),
			'NAME' => array(
				'data_type' => 'string'
			)
		);
	}
}

// Greated only for groupping deal products in report (please see CCrmReportHelper::getGrcColumns)
class IBlockElementGrcProxyTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_iblock_element';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'NAME' => array(
				'data_type' => 'string'
			)
		);
	}
}
