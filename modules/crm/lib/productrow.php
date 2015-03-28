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

class ProductRowTable extends Entity\DataManager
{
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'OWNER_ID' => array(
				'data_type' => 'integer'
			),
			'OWNER_TYPE' => array(
				'data_type' => 'string'
			),
			'OWNER' => array(
				'data_type' => 'Deal',
				'reference' => array('=this.OWNER_ID' => 'ref.ID')
			),
			'DEAL_OWNER' => array(
				'data_type' => 'Deal',
				'reference' => array(
					'=this.OWNER_ID' => 'ref.ID',
					'=this.OWNER_TYPE' => array('?', 'D')
				)
			),
			'LEAD_OWNER' => array(
				'data_type' => 'Lead',
				'reference' => array(
					'=this.OWNER_ID' => 'ref.ID',
					'=this.OWNER_TYPE' => array('?', 'L')
				)
			),
			'PRODUCT_ID' => array(
				'data_type' => 'integer'
			),
			/*'PRODUCT' => array(
				'data_type' => 'Product',
				'reference' => array('=this.PRODUCT_ID' => 'ref.ID')
			),*/
			'IBLOCK_ELEMENT' => array(
				'data_type' => 'IBlockElementProxy',
				'reference' => array('=this.PRODUCT_ID' => 'ref.ID')
			),
			'IBLOCK_ELEMENT_GRC' => array(
				'data_type' => 'IBlockElementGrcProxy',
				'reference' => array('=this.PRODUCT_ID' => 'ref.ID')
			),
			'PRICE' => array(
				'data_type' => 'integer'
			),
			'PRICE_ACCOUNT' => array(
				'data_type' => 'integer'
			),
			'QUANTITY' => array(
				'data_type' => 'integer'
			),
			'SUM_ACCOUNT' => array(
				'data_type' => 'integer',
				'expression' => array(
					'%s * %s',
					'PRICE_ACCOUNT', 'QUANTITY'
				)
			)
		);
	}
}
