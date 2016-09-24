<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale\Internals;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class PaySystemServiceTable extends Main\Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_sale_pay_system';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('PAY_SYSTEM_ENTITY_ID_FIELD'),
			),
			'LID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateLid'),
				'title' => Loc::getMessage('PAY_SYSTEM_ENTITY_LID_FIELD'),
			),
			'CURRENCY' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateCurrency'),
				'title' => Loc::getMessage('PAY_SYSTEM_ENTITY_CURRENCY_FIELD'),
			),
			'NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateName'),
				'title' => Loc::getMessage('PAY_SYSTEM_ENTITY_NAME_FIELD'),
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('PAY_SYSTEM_ENTITY_ACTIVE_FIELD'),
			),
			'ALLOW_EDIT_PAYMENT' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('PAY_SYSTEM_ENTITY_ALLOW_EDIT_PAYMENT_FIELD'),
			),
			'SORT' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('PAY_SYSTEM_ENTITY_SORT_FIELD'),
			),
			'DESCRIPTION' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateDescription'),
				'title' => Loc::getMessage('PAY_SYSTEM_ENTITY_DESCRIPTION_FIELD'),
			),
			'ACTION' => array(
				'data_type' => 'Bitrix\Sale\Internals\PaySystemActionTable',
				'reference' => array('=this.ID' => 'ref.PAY_SYSTEM_ID')
			),
		);
	}

	/**
	 * Returns validators for LID field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>LID</code> (идентификатор сайта). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/paysystemservicetable/validatelid.php
	* @author Bitrix
	*/
	public static function validateLid()
	{
		return array(
			new Entity\Validator\Length(null, 2),
		);
	}
	/**
	 * Returns validators for CURRENCY field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>CURRENCY</code> (код валюты). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/paysystemservicetable/validatecurrency.php
	* @author Bitrix
	*/
	public static function validateCurrency()
	{
		return array(
			new Entity\Validator\Length(null, 3),
		);
	}
	/**
	 * Returns validators for NAME field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>NAME</code> (название платежной системы). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/paysystemservicetable/validatename.php
	* @author Bitrix
	*/
	public static function validateName()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for DESCRIPTION field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>DESCRIPTION</code> (описание платежной системы). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/paysystemservicetable/validatedescription.php
	* @author Bitrix
	*/
	public static function validateDescription()
	{
		return array(
			new Entity\Validator\Length(null, 2000),
		);
	}

	public static function getListWithInner(array $parameters = array())
	{
		if(isset($parameters['filter']))
		{
			$parameters['filter'] = array(
				'LOGIC' => 'OR',
				$parameters['filter'],
				array(
					'ID' => \Bitrix\Sale\PaySystem\Manager::getInnerPaySystemId()
				)
			);
		}

		return parent::getList($parameters);
	}
}
