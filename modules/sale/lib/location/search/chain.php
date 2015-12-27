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
use Bitrix\Sale\Location\DB\BlockInserter;
use Bitrix\Sale\Location\DB\Helper;

Loc::loadMessages(__FILE__);

final class ChainTable extends Entity\DataManager implements \Serializable
{
	const STEP_SIZE = 	10000;
	const MTU = 		9999;

	protected $procData = 		array();
	protected $indexInserter = 	null;

	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_loc_search_chain';
	}

	public function serialize()
	{
		return serialize($this->procData);
	}
	public function unserialize($data)
	{
		$this->procData = unserialize($data);
		$this->initInsertHandles();
	}

	public function __construct($parameters = array())
	{
		$this->resetProcess();

		if(is_array($parameters['TYPES']) && !empty($parameters['TYPES']))
		{
			$this->procData['ALLOWED_TYPES'] = array_unique($parameters['TYPES']);
		}

		$typeSort = array();
		$res = Location\TypeTable::getList(array('select' => array('ID', 'CODE', 'DISPLAY_SORT')));

		$this->procData['TYPES'] = array();
		$this->procData['TYPE_SORT'] = array();

		while($item = $res->fetch())
		{
			if(!is_array($this->procData['ALLOWED_TYPES']) || (is_array($this->procData['ALLOWED_TYPES']) && in_array($item['ID'], $this->procData['ALLOWED_TYPES'])))
				$this->procData['TYPES'][$item['CODE']] = $item['ID'];

			$this->procData['TYPE_SORT'][$item['ID']] = $item['DISPLAY_SORT'];
		}

		$this->procData['TYPES_BACK'] = array_flip($this->procData['TYPES']);

		$this->initInsertHandles();
	}

	public function initInsertHandles()
	{
		$this->indexInserter = new BlockInserter(array(
			'entityName' => '\Bitrix\Sale\Location\Search\ChainTable',
			'exactFields' => array(
				'LOCATION_ID', 'RELEVANCY', 'POSITION'
			),
			'parameters' => array(
				'mtu' => static::MTU
			)
		));
	}

	public function resetProcess()
	{
		$this->procData = array(
			'OFFSET' => 	0,
			'DEPTH' => 	0,
			'PATH' => 			array(),
		);
	}

	public function getOffset()
	{
		return $this->procData['OFFSET'];
	}

	public static function cleanUpData()
	{
		Helper::dropTable(static::getTableName());

		Main\HttpApplication::getConnection()->query("create table ".static::getTableName()." (

			LOCATION_ID ".Helper::getSqlForDataType('int').",
			RELEVANCY ".Helper::getSqlForDataType('int')." default '0',
			POSITION ".Helper::getSqlForDataType('int')." default '0',

			primary key (POSITION, LOCATION_ID)

		)");
	}

	public static function getFilterForInitData($parameters = array())
	{
		$filter = array();

		if(!is_array($parameters))
			$parameters = array();

		if(is_array($parameters['TYPES']) && !empty($parameters['TYPES']))
			$filter['=TYPE_ID'] = array_unique($parameters['TYPES']);

		return $filter;
	}

	protected static function rarefact($sorts, $window = 10000)
	{
		if(!intval($window))
			$window = 10000;

		$rSorts = array();
		$w = $window;
		if(is_array($sorts))
		{
			asort($sorts);

			foreach($sorts as $id => $sort)
			{
				$rSorts[$id] = $w;
				$w += $window;
			}
		}

		return $rSorts;
	}

	public function initializeData()
	{
		$dbConnection = Main\HttpApplication::getConnection();

		$res = Location\LocationTable::getList(array(
			'select' => array(
				'ID', 
				'TYPE_ID',
				'DEPTH_LEVEL',
				'SORT'
			),
			//'filter' => static::getFilterForInitData(array('TYPES' => $this->procData['ALLOWED_TYPES'])),
			'order' => array(
				'LEFT_MARGIN' => 'asc'
			),
			'limit' => self::STEP_SIZE,
			'offset' => $this->procData['OFFSET']
		));

		$this->procData['TYPE_SORT'] = $this->rarefact($this->procData['TYPE_SORT']);

		$cnt = 0;
		while($item = $res->fetch())
		{
			// tmp!!!!
			//$name = Location\Name\LocationTable::getList(array('select' => array('NAME'), 'filter' => array('=LOCATION_ID' => $item['ID'], '=LANGUAGE_ID' => 'ru')))->fetch();

			if($item['DEPTH_LEVEL'] < $this->procData['DEPTH'])
			{
				$newPC = array();

				foreach($this->procData['PATH'] as $dl => $id)
				{
					if($dl >= $item['DEPTH_LEVEL'])
						break;

					$newPC[$dl] = $id;
				}

				$this->procData['PATH'] = $newPC;
			}

			$this->procData['PATH'][$item['DEPTH_LEVEL']] = array(
				'TYPE' => $item['TYPE_ID'],
				'ID' => $item['ID']
			);

			if(is_array($this->procData['ALLOWED_TYPES']) && in_array($item['TYPE_ID'], $this->procData['ALLOWED_TYPES']))
			{
				$data = array(
					'LOCATION_ID' => $item['ID'],
					'RELEVANCY' => $this->procData['TYPE_SORT'][$item['TYPE_ID']] + $item['SORT'],// * $item['DEPTH_LEVEL'], // tmp, will be more complicated calc here later
				);
				$wordsAdded = array();

				/*
				_dump_r('############################');
				_dump_r('LOCATION: '.$name['NAME']);
				_dump_r('TYPE RELEVANCY: '.$data['RELEVANCY']);
				_dump_r('PATH:');
				_dump_r($this->procData['PATH']);
				*/

				$this->procData['DEPTH'] = $item['DEPTH_LEVEL'];

				// pre-load missing words
				$wordCount = 0;
				foreach($this->procData['PATH'] as &$pathItem)
				{
					if(!isset($pathItem['WORDS'])) // words were not loaded previously for this part of the path
					{
						$sql = "
							select WS.POSITION from ".WordTable::getTableNameWord2Location()." WL
								inner join ".WordTable::getTableName()." WS on WL.WORD_ID = WS.ID
							where
								WL.LOCATION_ID = '".intval($pathItem['ID'])."'
						";

						$wordRes = $dbConnection->query($sql);

						$pathItem['WORDS'] = array();
						while($wordItem = $wordRes->fetch())
						{
							$pathItem['WORDS'][] = $wordItem['POSITION'];
						}
						$pathItem['WORDS'] = array_unique($pathItem['WORDS']);
					}

					$wordCount += count($pathItem['WORDS']);
				}

				// count words
				//_dump_r('Words total: '.$wordCount);

				$wOffset = 0;
				foreach($this->procData['PATH'] as &$pathItem)
				{
					foreach($pathItem['WORDS'] as $i => $position)
					{
						$wordWeight = $wordCount - $wOffset;

						$tmpData = $data;
						$tmpData['RELEVANCY'] += $wordWeight;

						//_dump_r('	Word relevancy: '.$data['RELEVANCY'].' ==>> '.$tmpData['RELEVANCY']);

						if(!isset($wordsAdded[$position]))
						{
							$this->indexInserter->insert(array_merge(array('POSITION' => $position), $tmpData));
							$wordsAdded[$position] = true;
						}

						$wOffset++;
					}
				}
				unset($pathItem);

			}

			$cnt++;
		}

		$this->indexInserter->flush();

		$this->procData['OFFSET'] += self::STEP_SIZE;

		return !$cnt;
	}

	public static function createIndex()
	{
		Helper::createIndex(static::getTableName(), 'LPR', array('LOCATION_ID', 'POSITION', 'RELEVANCY'));
	}

	public static function getMap()
	{
		return array(

			'LOCATION_ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'POSITION' => array(
				'data_type' => 'integer',
				'primary' => true
			),

			'RELEVANCY' => array(
				'data_type' => 'integer',
			)
		);
	}
}

