<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage learning
 * @copyright 2001-2013 Bitrix
 */


IncludeModuleLangFile(__FILE__);

class CLearningEvent
{
	public static function MakeMainObject()
	{
		$obj = new CEventMain;
		return $obj;
	}


	static public function GetFilter()
	{
		$arFilter = array();

		return  $arFilter;
	}


	static public function GetAuditTypes()
	{
		return array(
			'LEARNING_REMOVE_ITEM' => '[LEARNING_REMOVE_ITEM] ' . GetMessage('LEARNING_LOG_REMOVE_ITEM')
		);
	}


	static public function GetEventInfo($row, $arParams)
	{
		$EventPrint = '???';
		switch($row['AUDIT_TYPE_ID'])
		{
			case 'LEARNING_REMOVE_ITEM':
				$EventPrint = GetMessage('LEARNING_LOG_REMOVE_ITEM');
				break;
		}

		return array(
			'eventType' => $EventPrint,
			'eventName' => $row['ITEM_ID'],
			'eventURL'  => null
		);
	}


	static public function GetFilterSQL($var)
	{
		$ar = array();

		$ar[] = array('AUDIT_TYPE_ID' => 'LEARNING_REMOVE_ITEM');

		return $ar;
	}
}
