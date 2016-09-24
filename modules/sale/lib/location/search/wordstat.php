<?php
/**
 * Bitrix Framework
 * @package Bitrix\Sale\Location
 * @subpackage sale
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\Sale\Location\Search;

use Bitrix\Main;
use Bitrix\Main\DB;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Location;

Loc::loadMessages(__FILE__);

final class WordStatTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_loc_word_stat';
	}

	public static function cleanUp()
	{
		Main\HttpApplication::getConnection()->query('truncate table '.static::getTableName());
	}

	const STEP_SIZE = 100;

	// tmp
	protected static $blackList = array(
		'РАЙОН' => true,
		'ОБЛАСТЬ' => true,
		'УЛИЦА' => true,
		'ТУПИК' => true,
		'ГЕНЕРАЛА' => true,
		'ПЕРЕУЛОК' => true,
		'ПОСЁЛОК' => true,
		'СЕЛО' => true,
		'ГОРОДСКОГО' => true,
		'ТИПА' => true,
		'ГОРОДОК' => true,
		'СНТ' => true,
		'НАСЕЛЁННЫЙ' => true,
		'ПУНКТ' => true,
		'ДЕРЕВНЯ' => true,
		'ДАЧНЫЙ' => true,
		'ДНП' => true,
		'ДНТ' => true,
		'ПЛОЩАДЬ' => true,
		'ПРОЕЗД' => true,
		'АЛЛЕЯ' => true
	);

	public static function parseQuery($query)
	{
		$words = explode(' ', $query);

		$result = array();
		foreach($words as $k => &$word)
		{
			$word = ToUpper(trim($word));

			if(strlen($word) < 2 || isset(static::$blackList[$word]))
				continue;

			$result[] = $word;
		}

		$result = array_unique($result);

		//natsort($result);

		return $result;
	}

	public static function reInitData()
	{
		static::cleanUp();

		$totalCnt = 0;
		$offset = 0;
		$stat = array();

		while(true)
		{
			$res = Location\Name\LocationTable::getList(array(
				'select' => array(
					'NAME',
					'LOCATION_ID',
					'TID' => 'LOCATION.TYPE_ID'
				),
				'filter' => array(
					'=LOCATION.TYPE.CODE' => array('CITY', 'VILLAGE', 'STREET'),
					'=LANGUAGE_ID' => 'ru'
				),
				'limit' => self::STEP_SIZE,
				'offset' => $offset
			));

			$cnt = 0;
			while($item = $res->fetch())
			{
				if(strlen($item['NAME']))
				{
					$words = static::parseQuery($item['NAME']);

					foreach($words as $k => &$word)
					{
						try
						{

							static::add(array(
								'WORD' => $word,
								'TYPE_ID' => $item['TID'],
								'LOCATION_ID' => $item['LOCATION_ID']
							));

						}
						catch(\Bitrix\Main\DB\SqlQueryException $e)
						{
							// duplicate or smth
						}
					}

					$stat['W_'.count($words)] += 1;

					//_print_r($words);
				}

				$cnt++;
				$totalCnt++;
			}

			if(!$cnt)
				break;

			$offset += self::STEP_SIZE;
		}

		_print_r('Total: '.$totalCnt);
		_print_r($stat);
	}

	public static function getMap()
	{
		return array(

			'WORD' => array(
				'data_type' => 'string',
				'primary' => true,
			),
			'TYPE_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'LOCATION_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
		);
	}
}

