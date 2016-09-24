<?php
namespace Bitrix\Catalog\Helpers\Admin;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc,
	Bitrix\Catalog,
	Bitrix\Iblock;

Loc::loadMessages(__FILE__);

/**
 * Class ProductEdit
 * Provides methods for admin product page.
 *
 * @package Bitrix\Catalog\Helpers\Admin
 */
class ProductEdit
{
	/**
	 * Return default values for product fields.
	 *
	 * @param bool $subscribe		Is iblock subscribe.
	 * @return array
	 * @throws Main\ArgumentNullException
	 */
	public static function getDefaultValues($subscribe = false)
	{
		$vatInclude = ((string)Main\Config\Option::get('catalog', 'default_product_vat_included') == 'Y' ? 'Y' : 'N');
		$subscribe = ($subscribe === true);
		if ($subscribe)
		{
			return array(
				'QUANTITY' => '',
				'QUANTITY_RESERVED' => '',
				'VAT_ID' => 0,
				'VAT_INCLUDED' => $vatInclude,
				'QUANTITY_TRACE_ORIG' => Catalog\ProductTable::STATUS_DEFAULT,
				'CAN_BUY_ZERO_ORIG' => Catalog\ProductTable::STATUS_DEFAULT,
				'SUBSCRIBE_ORIG' => Catalog\ProductTable::STATUS_DEFAULT,
				'PURCHASING_PRICE' => '',
				'PURCHASING_CURRENCY' => '',
				'BARCODE_MULTI' => '',
				'PRICE_TYPE' => '',
				'RECUR_SCHEME_TYPE' => '',
				'RECUR_SCHEME_LENGTH' => '',
				'TRIAL_PRICE_ID' => '',
				'WITHOUT_ORDER' => '',
			);
		}
		return array(
			'QUANTITY' => '',
			'QUANTITY_RESERVED' => '',
			'VAT_ID' => 0,
			'VAT_INCLUDED' => $vatInclude,
			'QUANTITY_TRACE_ORIG' => Catalog\ProductTable::STATUS_DEFAULT,
			'CAN_BUY_ZERO_ORIG' => Catalog\ProductTable::STATUS_DEFAULT,
			'SUBSCRIBE_ORIG' => Catalog\ProductTable::STATUS_DEFAULT,
			'PURCHASING_PRICE' => '',
			'PURCHASING_CURRENCY' => '',
			'WEIGHT' => '',
			'WIDTH' => '',
			'LENGTH' => '',
			'HEIGHT' => '',
			'MEASURE' => '',
			'BARCODE_MULTI' => ''
		);
	}
}