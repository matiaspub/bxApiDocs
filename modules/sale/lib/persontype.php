<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale;

use Bitrix\Main\ArgumentException;
use Bitrix\Sale\Internals\CollectionBase;
use Bitrix\Sale\Internals\PersonTypeTable;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class PersonType
{
	/** @var int */
	protected $siteId;

	/** @var array  */
	private $personTypeList = array();


	protected function __construct()
	{

	}

	/**
	 * @param null $siteId
	 * @param null $id
	 * @return mixed
	 * @throws ArgumentException
	 */
	public static function load($siteId = null, $id = null)
	{
		if (strval($siteId) == "" && intval($id) <= 0)
		{
			throw new ArgumentException();
		}

		$personType = new static();
		$personType->siteId = $siteId;

		$filter = array("=ACTIVE" => "Y");

		if (strval($siteId) != "")
		{
			$filter['=PERSON_TYPE_SITE.SITE_ID'] = $siteId;
		}

		if ($id > 0)
		{
			$filter['ID'] = $id;
		}

		if ($personTypeList = static::loadFromDb(array(
			                          'order' => array("SORT" => "ASC", "NAME" => "ASC"),
			                          'filter' => $filter,
		                          )))
		{
			foreach($personTypeList as $personTypeData)
			{
				$personType->personTypeList[$personTypeData['ID']] = $personTypeData;
			}
		}


		return $personType->personTypeList;
	}

	/**
	 * @param array $filter
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected static function loadFromDb(array $filter)
	{
		$res = PersonTypeTable::getList($filter)->fetchAll();
		return $res;
	}

	/**
	 * @param $personTypeId
	 * @param $siteId
	 * @return bool
	 */
	public static function checkCorrect($personTypeId, $siteId)
	{
		if (static::getList(array(
			'filter' => array(
				"ID" => $personTypeId,
				"LID" => $siteId,
				"ACTIVE" => "Y"
			)))->fetch())
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * @param OrderBase $order
	 * @return Entity\Result
	 */
	public static function doCalculate(OrderBase $order)
	{
		$result = new Result();

		if ($order->getPersonTypeId() !== null)
		{
			if (!($personTypeList = static::load($order->getSiteId(), $order->getPersonTypeId())))
			{
				$result->addError(new Entity\EntityError(GetMessage('SKGP_PERSON_TYPE_NOT_FOUND'), 'PERSON_TYPE_ID'));
			}

			return $result;
		}

		if (($personTypeList = static::load($order->getSiteId())) && !empty($personTypeList) && is_array($personTypeList))
		{
			$firstPersonType = reset($personTypeList);
			$order->setPersonTypeId($firstPersonType["ID"]);
		}
		else
		{
			$result->addError(new Entity\EntityError(GetMessage('SKGP_PERSON_TYPE_EMPTY'), 'PERSON_TYPE_ID'));
		}

		return $result;
	}


	// TODO: checkFields, update, delete, onBeforeLangDelete, selectBox

}