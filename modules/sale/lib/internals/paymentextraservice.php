<?php
namespace Bitrix\Sale\Internals;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class PaymentExtraServiceTable extends Main\Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_order_payment_es';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('ORDER_PAYMENT_EXTRA_SERVICES_ENTITY_ID_FIELD'),
			),
			'PAYMENT_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('ORDER_PAYMENT_EXTRA_SERVICES_ENTITY_PAYMENT_ID_FIELD'),
			),
			'EXTRA_SERVICE_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('ORDER_PAYMENT_EXTRA_SERVICES_ENTITY_EXTRA_SERVICE_ID_FIELD'),
			),
			'VALUE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateValue'),
				'title' => Loc::getMessage('ORDER_PAYMENT_EXTRA_SERVICES_ENTITY_VALUE_FIELD'),
			),
		);
	}
	public static function validateValue()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}

	public static function deleteByPaymentId($paymentId)
	{
		if((int)$paymentId > 0)
		{
			$con = Main\Application::getConnection();
			$sqlHelper = $con->getSqlHelper();
			$strSql = "DELETE FROM ".self::getTableName()." WHERE PAYMENT_ID=".$sqlHelper->forSql($paymentId);
			$con->queryExecute($strSql);
		}
	}
}