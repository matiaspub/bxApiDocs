<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Main\Mail\Internal;

use Bitrix\Main\Entity;
use Bitrix\Main\Mail as Mail;
use Bitrix\Main\Config as Config;
use Bitrix\Main\Type as Type;

class EventTable extends Entity\DataManager
{

	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_event';
	}

	/**
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'EVENT_NAME' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'MESSAGE_ID' => array(
				'data_type' => 'integer',
			),
			'LID' => array(
				'data_type' => 'string',
				'required' => true,
			),


			'C_FIELDS' => array(
				'data_type' => 'string',
				//'column_name' => 'C_FIELDS',
				'save_data_modification' => array(__CLASS__, "getSaveModificatorsForFieldsField"),
				'fetch_data_modification' => array(__CLASS__, "getFetchModificatorsForFieldsField"),
			),


			'DATE_INSERT' => array(
				'data_type' => 'datetime',
				'default_value' => new Type\DateTime(),
			),
			'DATE_EXEC' => array(
				'data_type' => 'datetime',
			),
			'SUCCESS_EXEC' => array(
				'data_type' => 'string',
			),
			'DUPLICATE' => array(
				'data_type' => 'string',
			),
		);
	}


	/**
	 * @return array
	 */
	public static function getSaveModificatorsForFieldsField()
	{
		return array(
			array(__CLASS__, "serialize")
		);
	}


	/**
	 * @return array
	 */
	public static function getFetchModificatorsForFieldsField()
	{
		return array(
			array(__CLASS__, "getFetchModificationForFieldsField"),
			array(new Entity\StringField('FIELDS', array()), "unserialize")
		);
	}

	public static function serialize($fields)
	{
		if(!is_array($fields)) $fields = array();
		array_walk_recursive($fields, array(__CLASS__, 'replaceValuesBeforeSerialize'));
		if(!is_array($fields)) $fields = array();

		return serialize($fields);
	}

	protected static function replaceValuesBeforeSerialize(&$item, &$key)
	{
		if(is_object($item))
		{
			if(method_exists($item, '__toString'))
				$item = (string) $item;
			else
				$item = '';
		}
	}
	/**
	 * @param $str
	 * @return bool
	 */
	protected  static function isFieldSerialized($str)
	{
		$str = trim($str);

		if ($str == 'N;')
			return true;

		if ( !preg_match( '/^([abdiOs]):/', $str, $matches ) )
			return false;

		switch($matches[1])
		{
			case 'b':
			case 'i':
			case 'd':
				if(preg_match( "/^".$matches[1].":[0-9.E-]+;\$/", $str))
					return true;
				break;

			case 'a':
			case 'O':
			case 's':
				if(preg_match( "/^".$matches[1].":[0-9]+:.*[;}]\$/s", $str))
					return true;
				break;
		}

		return false;
	}

	/**
	 * @param $str
	 * @return string
	 */
	public static function getFetchModificationForFieldsField($str)
	{
		if(static::isFieldSerialized($str))
			return $str;

		$ar = explode("&", $str);
		$newar = array();
		while (list (, $val) = each ($ar))
		{
			$val = str_replace("%1", "&", $val);
			$tar = explode("=", $val);
			$key = $tar[0];
			$val = $tar[1];
			$key = str_replace("%3", "=", $key);
			$val = str_replace("%3", "=", $val);
			$key = str_replace("%2", "%", $key);
			$val = str_replace("%2", "%", $val);
			if($key != "")
				$newar[$key] = $val;
		}

		$field = new Entity\StringField('FIELDS', array());
		return $field->serialize($newar);
	}
}
