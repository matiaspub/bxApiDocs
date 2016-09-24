<?php
namespace Bitrix\Sale\Internals;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class PaySystemExtraServiceTable extends Main\Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_pay_system_es';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('PAY_SYSTEM_EXTRA_SERVICES_ENTITY_ID_FIELD'),
			),
			'CODE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateCode'),
				'title' => Loc::getMessage('PAY_SYSTEM_EXTRA_SERVICES_ENTITY_CODE_FIELD'),
			),
			'NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateName'),
				'title' => Loc::getMessage('PAY_SYSTEM_EXTRA_SERVICES_ENTITY_NAME_FIELD'),
			),
			'DESCRIPTION' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateDescription'),
				'title' => Loc::getMessage('PAY_SYSTEM_EXTRA_SERVICES_ENTITY_DESCRIPTION_FIELD'),
			),
			'CLASS_NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateClassName'),
				'title' => Loc::getMessage('PAY_SYSTEM_EXTRA_SERVICES_ENTITY_CLASS_NAME_FIELD'),
			),
			'PARAMS' => array(
				'data_type' => 'text',
				'serialized' => true,
				'title' => Loc::getMessage('PAY_SYSTEM_EXTRA_SERVICES_ENTITY_PARAMS_FIELD'),
			),
			'SHOW_MODE' => array(
				'data_type' => 'string',
				'required' => false,
				'validation' => array(__CLASS__, 'validateShowMode'),
				'title' => Loc::getMessage('PAY_SYSTEM_EXTRA_SERVICES_ENTITY_RIGHTS_FIELD'),
			),
			'PAY_SYSTEM_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('PAY_SYSTEM_EXTRA_SERVICES_ENTITY_PAY_SYSTEM_ID_FIELD'),
			),
			'DEFAULT_VALUE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateDefaultValue'),
				'title' => Loc::getMessage('PAY_SYSTEM_EXTRA_SERVICES_ENTITY_INITIAL_FIELD'),
			),
			'ACTIVE' => array(
				'data_type' => 'string',
				'default_value'=> 'Y',
				'validation' => array(__CLASS__, 'validateActive'),
				'title' => Loc::getMessage('PAY_SYSTEM_EXTRA_SERVICES_ENTITY_ACTIVE_FIELD'),
			),
			'SORT' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('PAY_SYSTEM_EXTRA_SERVICES_ENTITY_SORT_FIELD'),
			),
			'PAYMENT' => array(
				'data_type' => '\Bitrix\Sale\Internal\PaymentExtraServiceTable',
				'reference' => array('this.ID' => 'ref.EXTRA_SERVICE_ID')
			)
		);
	}
	public static function validateCode()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
	public static function validateName()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	public static function validateDescription()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	public static function validateClassName()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	public static function validateShowMode()
	{
		return array(
			new Main\Entity\Validator\Length(null, 1),
		);
	}
	public static function validateDefaultValue()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	public static function validateActive()
	{
		return array(
			new Main\Entity\Validator\Length(null, 1),
		);
	}

	public static function onBeforeDelete(Main\Entity\Event $event)
	{
		$result = new Main\Entity\EventResult;
		$primary = $event->getParameter("primary");

		if ((int)$primary['ID'] > 0)
		{
			$dbRes = \Bitrix\Sale\Internals\PaymentExtraServiceTable::getList(array(
				'filter' => array(
					'=EXTRA_SERVICE_ID' => $primary['ID']
				)
			));

			if ($row = $dbRes->fetch())
			{
				$result->addError(new Main\Entity\EntityError(
					str_replace('#ID#', $primary['ID'], Loc::getMessage('PAY_SYSTEM_EXTRA_SERVICES_ENTITY_ERROR_DELETE'))
				));
			}
		}

		return $result;
	}
}