<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale\Location;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

use Bitrix\Sale\Location\Util\Assert;

Loc::loadMessages(__FILE__);

class DefaultSiteTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_loc_def2site';
	}

	/**
	* $locationCodeList format: array(array('LOCATION_CODE' => int, 'SORT' => int))
	*/
	public static function addMultipleForOwner($siteId, $locationCodeList = array())
	{
		$siteId = static::checkSiteId($siteId);

		$existed = array();
		$res = self::getList(array('filter' => array('SITE_ID' => $siteId)));
		while($item = $res->fetch())
			$existed[$item['LOCATION_CODE']] = true;

		if(is_array($locationCodeList))
		{
			foreach($locationCodeList as $location)
			{
				if(!isset($existed[$location]))
				{
					$opRes = self::add(array(
						'SITE_ID' => $siteId,
						'LOCATION_CODE' => $location['LOCATION_CODE'],
						'SORT' => $location['SORT'],
					));
					if(!$opRes->isSuccess())
						throw new Main\SystemException(Loc::getMessage('SALE_LOCATION_DEFAULTSITE_ENTITY_CANNOT_ADD_EXCEPTION'));
				}
			}
		}
		else
			throw new Main\SystemException('Code list is not a valid array');

		$GLOBALS['CACHE_MANAGER']->ClearByTag('sale-location-data');
	}

	/**
	* $locationCodeList format
	*/
	public static function updateMultipleForOwner($siteId, $locationCodeList = array(), $behaviour = array('REMOVE_ABSENT' => true))
	{
		$siteId = static::checkSiteId($siteId);

		if(!is_array($locationCodeList))
			throw new Main\SystemException('Code list is not a valid array');

		// throw away duplicates and make index array
		$index = array();
		$locationCodeListTemp = array();
		foreach($locationCodeList as $location)
		{
			$index[$location['LOCATION_CODE']] = true;
			$locationCodeListTemp[$location['LOCATION_CODE']] = $location;
		}
		$locationCodeList = $locationCodeListTemp;

		$res = self::getList(array('filter' => array('SITE_ID' => $siteId)));
		$update = array();
		$delete = array();
		while($item = $res->Fetch())
		{
			if(!isset($index[$item['LOCATION_CODE']]))
				$delete[$item['LOCATION_CODE']] = true;
			else
			{
				unset($index[$item['LOCATION_CODE']]);
				$update[$item['LOCATION_CODE']] = true;
			}
		}

		if($behaviour['REMOVE_ABSENT'])
		{
			foreach($delete as $code => $void)
			{
				$res = self::delete(array('SITE_ID' => $siteId, 'LOCATION_CODE' => $code));
				if(!$res->isSuccess())
					throw new Main\SystemException(Loc::getMessage('SALE_LOCATION_DEFAULTSITE_ENTITY_CANNOT_DELETE_EXCEPTION'));
			}
		}

		foreach($update as $code => $void)
		{
			$res = self::update(array('SITE_ID' => $siteId, 'LOCATION_CODE' => $code), array('SORT' => $locationCodeList[$code]['SORT']));
			if(!$res->isSuccess())
				throw new Main\SystemException(Loc::getMessage('SALE_LOCATION_DEFAULTSITE_ENTITY_CANNOT_UPDATE_EXCEPTION'));
		}

		foreach($index as $code => $void)
		{
			$res = self::add(array(
				'SORT' => $locationCodeList[$code]['SORT'],
				'SITE_ID' => $siteId,
				'LOCATION_CODE' => $code
			));
			if(!$res->isSuccess())
				throw new Main\SystemException(Loc::getMessage('SALE_LOCATION_DEFAULTSITE_ENTITY_CANNOT_ADD_EXCEPTION'));
		}

		$GLOBALS['CACHE_MANAGER']->ClearByTag('sale-location-data');
	}

	private static function checkSiteId($siteId)
	{
		$siteId = Assert::expectStringNotNull($siteId, '$siteId');

		$res = Main\SiteTable::getList(array('filter' => array('LID' => $siteId)))->fetch();
		if(!$res)
			throw new Main\ArgumentOutOfRangeException(Loc::getMessage('SALE_LOCATION_DEFAULTSITE_ENTITY_SITE_ID_UNKNOWN_EXCEPTION'));

		return $siteId;
	}

	public static function getMap()
	{
		return array(

			'SORT' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('SALE_LOCATION_DEFAULTSITE_ENTITY_SORT_FIELD'),
				'default' => '100'
			),
			'LOCATION_CODE' => array(
				'data_type' => 'string',
				'required' => true,
				'primary' => true,
				'title' => Loc::getMessage('SALE_LOCATION_DEFAULTSITE_ENTITY_LOCATION_ID_FIELD')
			),
			'SITE_ID' => array(
				'data_type' => 'string',
				'required' => true,
				'primary' => true,
				'title' => Loc::getMessage('SALE_LOCATION_DEFAULTSITE_ENTITY_SITE_ID_FIELD')
			),

			// virtual
			'LOCATION' => array(
				'data_type' => '\Bitrix\Sale\Location\Location',
				'reference' => array(
					'=this.LOCATION_CODE' => 'ref.CODE'
				)
			),

			'SITE' => array(
				'data_type' => '\Bitrix\Main\Site',
				'reference' => array(
					'=this.SITE_ID' => 'ref.LID'
				)
			)
		);
	}
}
