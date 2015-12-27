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

final class WordChainTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_loc_wordchain';
	}

	public static function cleanUp()
	{
		$dbConnection = Main\HttpApplication::getConnection();

		try
		{
			$dbConnection->query('drop table '.static::getTableName());

			$allowedTypes = array('REGION', 'SUBREGION', 'CITY', 'VILLAGE', 'STREET');

			foreach($allowedTypes as $type)
			{
				//$dbConnection->query('drop table b_sale_loc_wc_'.ToLower($type)); // here replace according to map: TYPE_CODE => TABLE_ID
				$dbConnection->query('truncate table b_sale_loc_wc_'.ToLower($type));
			}
		}
		catch(\Bitrix\Main\DB\SqlQueryException $e)
		{
		}
	}

	public static function createTables()
	{
		static::cleanUp();

		$sql = "create table b_sale_loc_wordchain 
			(
				LOCATION_ID int primary key,
				TYPE_ID int,

				REGION_ID int,
				SUBREGION_ID int,
				CITY_ID int,
				VILLAGE_ID int,
				STREET_ID int,

				RELEVANCY int default '0'
			)";

		Main\HttpApplication::getConnection()->query($sql);

		$allowedTypes = array('REGION', 'SUBREGION', 'CITY', 'VILLAGE', 'STREET');

		foreach($allowedTypes as $type)
		{
		}

		/*
			create table b_sale_loc_wc_region 
			(
				LOCATION_ID int primary key,
				LANGUAGE_ID char(2) default 'ru',

				W_1 varchar(50),
				WORD_COUNT tinyint default '0'
			)

			create table b_sale_loc_wc_subregion 
			(
				LOCATION_ID int primary key,
				LANGUAGE_ID char(2) default 'ru',

				W_1 varchar(50),
				WORD_COUNT tinyint default '0'
			)

			create table b_sale_loc_wc_city 
			(
				LOCATION_ID int primary key,
				LANGUAGE_ID char(2) default 'ru',

				W_1 varchar(50),
				W_2 varchar(50),
				W_3 varchar(50),
				WORD_COUNT tinyint default '0'
			)

			create table b_sale_loc_wc_village 
			(
				LOCATION_ID int primary key,
				LANGUAGE_ID char(2) default 'ru',

				W_1 varchar(50),
				W_2 varchar(50),
				W_3 varchar(50),
				WORD_COUNT tinyint default '0'
			)

			create table b_sale_loc_wc_street 
			(
				LOCATION_ID int primary key,
				LANGUAGE_ID char(2) default 'ru',

				W_1 varchar(50),
				W_2 varchar(50),
				W_3 varchar(50),
				WORD_COUNT tinyint default '0'
			)
		*/

		
	}

	public static function reCreateIndex()
	{
		/*
		$sql = 'create index IX_SALE_WC on b_sale_loc_wordchain (

				SORT,
				TYPE_SORT,

				W_1,
				W_2,
				W_3,
				W_4,
				W_5,
				W_6,
				W_7,
				W_8,
				W_9,
				W_10
		)';

		Main\HttpApplication::getConnection()->query($sql);
		*/
	}

	const STEP_SIZE = 100;

	public static function reInitData($parameters = array())
	{
		static::createTables();

		$offset = 0;
		$stat = array();

		$types = array();
		$typeSort = array();
		$res = Location\TypeTable::getList(array('select' => array('ID', 'CODE', 'SORT')));

		$allowedTypes = array('REGION', 'SUBREGION', 'CITY', 'VILLAGE', 'STREET');

		while($item = $res->fetch())
		{
			if(in_array($item['CODE'], $allowedTypes))
				$types[$item['CODE']] = $item['ID'];

			$typeSort[$item['ID']] = $item['SORT'];
		}

		$typesBack = array_flip($types);

		//print_r($types);
		//_print_r($typeSort);

		$wordChain = array();
		$pathChain = array();

		//_dump_r('GO!');

		$prevDepth = 0;
		while(true)
		{
			$res = Location\LocationTable::getList(array(
				'select' => array(
					'ID', 
					'TYPE_ID',
					'LNAME' => 'NAME.NAME',
					'DEPTH_LEVEL',
					'SORT'
				),
				'filter' => array(
					'=TYPE_ID' => array_values($types),
					'=NAME.LANGUAGE_ID' => LANGUAGE_ID
				),
				'order' => array(
					'LEFT_MARGIN' => 'asc'
				),
				'limit' => self::STEP_SIZE,
				'offset' => $offset
			));

			$cnt = 0;
			while($item = $res->fetch())
			{
				if($item['DEPTH_LEVEL'] < $prevDepth)
				{
					//print('DROP to '.$item['DEPTH_LEVEL'].'<br />');

					// drop chain to DEPTH_LEVEL inclusively
					$newWC = array();
					$newPC = array();
					foreach($wordChain as $dl => $name)
					{
						if($dl >= $item['DEPTH_LEVEL'])
							break;

						$newWC[$dl] = $name;
					}

					$wordChain = $newWC;

					foreach($pathChain as $dl => $id)
					{
						if($dl >= $item['DEPTH_LEVEL'])
							break;

						$newPC[$dl] = $id;
					}

					$pathChain = $newPC;
				}

				$wordChain[$item['DEPTH_LEVEL']] = $item['LNAME'];
				$pathChain[$item['DEPTH_LEVEL']] = array('TYPE' => $item['TYPE_ID'], 'ID' => $item['ID']);

				$prevDepth = $item['DEPTH_LEVEL'];

				//print($item['DEPTH_LEVEL'].' - '.implode(' ', WordStatTable::parseQuery(implode(' ', $wordChain))).'<br />');

				$parsed = WordStatTable::parseQuery(implode(' ', $wordChain));
				$wordMap = array();
				$i = 1;
				foreach($parsed as $word)
				{
					$wordMap['W_'.$i] = $word;
					$i++;
				}
				$pathMap = array();
				foreach($pathChain as $elem)
					$pathMap[$typesBack[$elem['TYPE']].'_ID'] = $elem['ID'];

				$data = array_merge($wordMap, $pathMap, array(
					'LOCATION_ID' => $item['ID'],
					'TYPE_ID' => $item['TYPE_ID'],
					'TYPE_SORT' => $typeSort[$item['TYPE_ID']],
					'SORT' => $item['SORT'],
					'WORD_COUNT' => count($wordMap)
				));

				//print('<pre>');
				//print('</pre>');

				try
				{
					static::add($data);
				}
				catch(\Exception $e)
				{
					_dump_r('Cant add '.implode(' ', $wordChain).' ('.count($wordMap).')<br />');
					// duplicate or smth
				}

				$cnt++;
			}

			if(!$cnt)
				break;

			$offset += self::STEP_SIZE;
		}
	}

	public static function search($words, $offset) // very temporal prototype
	{
		$dbConnection = Main\HttpApplication::getConnection();
		$dbHelper = Main\HttpApplication::getConnection()->getSqlHelper();

		$where = array();

		foreach($words as $word)
		{
			$whereWord = array();
			for($k = 1; $k <= 10; $k++)
			{
				$whereWord[] = "W_".$k." like '".$dbHelper->forSql($word)."%'";
			}

			$where[] = '('.implode(' or ', $whereWord).')';
		}

		$sql = "
			select SQL_NO_CACHE IX.LOCATION_ID as ID, L.CODE, IX.TYPE_ID, L.LEFT_MARGIN, L.RIGHT_MARGIN, N.NAME as NAME from ".static::getTableName()." IX

				inner join b_sale_location L on IX.LOCATION_ID = L.ID
				inner join b_sale_loc_name N on IX.LOCATION_ID = N.LOCATION_ID and N.LANGUAGE_ID = 'ru'

				where ".implode(' and ', $where)."

			order by 
				IX.SORT asc,
				IX.TYPE_SORT asc
			limit 10
			".(intval($offset) ? 'offset '.intval($offset) : '')."
		";

		_dump_r($sql);

		/*
		print('<pre>');
		print_r($sql);
		print('</pre>');
		*/

		return $dbConnection->query($sql);
	}

	public static function getMap()
	{
		return array(

			'LOCATION_ID' => array(
				'data_type' => 'integer',
				'primary' => true // tmp
			),
			'TYPE_ID' => array(
				'data_type' => 'integer',
			),


			'REGION_ID' => array(
				'data_type' => 'integer',
			),

			'SUBREGION_ID' => array(
				'data_type' => 'integer',
			),
			'CITY_ID' => array(
				'data_type' => 'integer',
			),
			'VILLAGE_ID' => array(
				'data_type' => 'integer',
			),
			'STREET_ID' => array(
				'data_type' => 'integer',
			),

			'W_1' => array(
				'data_type' => 'string',
			),
			'W_2' => array(
				'data_type' => 'string',
			),
			'W_3' => array(
				'data_type' => 'string',
			),
			'W_4' => array(
				'data_type' => 'string',
			),
			'W_5' => array(
				'data_type' => 'string',
			),
			'W_6' => array(
				'data_type' => 'string',
			),
			'W_7' => array(
				'data_type' => 'string',
			),
			'W_8' => array(
				'data_type' => 'string',
			),
			'W_9' => array(
				'data_type' => 'string',
			),
			'W_10' => array(
				'data_type' => 'string',
			),

			'WORD_COUNT' => array(
				'data_type' => 'integer',
			),

			'TYPE_SORT' => array(
				'data_type' => 'integer',
			),
			'SORT' => array(
				'data_type' => 'integer',
			),
		);
	}
}

