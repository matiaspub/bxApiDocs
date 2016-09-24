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

final class WordTable extends Entity\DataManager implements \Serializable
{
	protected $procData = 		array();
	protected $word2LocationInserter = 	null;
	protected $dictionaryInserter = 	null;

	protected $dictionaryIndex = 		array();

	const STEP_SIZE = 					10000;
	const MTU = 						9999;

	public function serialize()
	{
		return serialize($this->procData);
	}
	public function unserialize($data)
	{
		$this->procData = unserialize($data);
		$this->initInsertHandles();
	}

	public static function getFilePath()
	{
		return __FILE__;
	}

	// this for keeping word dictionary
	public static function getTableName()
	{
		return 'b_sale_loc_search_word';
	}

	// this for keeping links between location-and-word-id
	public static function getTableNameWord2Location()
	{
		return 'b_sale_loc_search_w2l';
	}

	// this for merge, temporal table
	public static function getTableNamePositionTemporal()
	{
		return 'b_sale_loc_search_wt';
	}

	public function __construct($parameters)
	{
		$this->resetProcess();
		$this->initInsertHandles();

		if(is_array($parameters['TYPES']) && !empty($parameters['TYPES']))
		{
			$this->procData['ALLOWED_TYPES'] = array_unique($parameters['TYPES']);
		}
		if(is_array($parameters['LANGS']) && !empty($parameters['LANGS']))
		{
			$this->procData['ALLOWED_LANGS'] = array_unique($parameters['LANGS']);
		}

		$this->procData['CURRENT_LOCATION'] = false;
		$this->procData['CURRENT_LOCATION_WORDS'] = array();
	}

	public function initInsertHandles()
	{
		$this->word2LocationInserter = new BlockInserter(array(
			'tableName' => static::getTableNameWord2Location(),
			'exactFields' => array(
				'LOCATION_ID' => array('data_type' => 'integer'),
				'WORD_ID' => array('data_type' => 'integer')
			),
			'parameters' => array(
				'mtu' => static::MTU
			)
		));

		$this->dictionaryInserter = new BlockInserter(array(
			'entityName' => '\Bitrix\Sale\Location\Search\WordTable',
			'exactFields' => array(
				'WORD'
			),
			'parameters' => array(
				'mtu' => static::MTU,
				'autoIncrementFld' => 'ID',
				'CALLBACKS' => array(
					'ON_BEFORE_FLUSH' => array($this, 'onBeforeDictionaryFlush')
				)
			)
		));

		$this->dictionaryResorter = new BlockInserter(array(
			'tableName' => static::getTableNamePositionTemporal(),
			'exactFields' => array(
				'WORD_ID' => array('data_type' => 'integer'),
				'POSITION' => array('data_type' => 'integer')
			),
			'parameters' => array(
				'mtu' => static::MTU
			)
		));
	}

	public function resetProcess()
	{
		$this->procData = array(
			'OFFSET' => 					0,
			'POSITION' => 					0,
			'CURRENT_LOCATION' => 			false,
			'CURRENT_LOCATION_WORDS' => 	array()
		);
	}

	public static function cleanUpData()
	{
		$dbConnection = Main\HttpApplication::getConnection();

		Helper::dropTable(static::getTableName());

		$binary = ToLower($dbConnection->getType()) == 'mysql' ? 'binary' : ''; // http://bugs.mysql.com/bug.php?id=34096

		// ORACE: OK, MSSQL: OK
		Main\HttpApplication::getConnection()->query("create table ".static::getTableName()." (

			ID ".Helper::getSqlForDataType('int')." not null ".Helper::getSqlForAutoIncrement()." primary key,
			WORD ".Helper::getSqlForDataType('varchar', 50)." ".$binary." not null,
			POSITION ".Helper::getSqlForDataType('int')." default '0'
		)");

		Helper::addAutoIncrement(static::getTableName()); // only for ORACLE

		Helper::createIndex(static::getTableName(), 'TMP', array('WORD'), true);
		Helper::dropTable(static::getTableNameWord2Location());

		// ORACLE: OK, MSSQL: OK
		Main\HttpApplication::getConnection()->query("create table ".static::getTableNameWord2Location()." (

			LOCATION_ID ".Helper::getSqlForDataType('int')." not null,
			WORD_ID ".Helper::getSqlForDataType('int')." not null,

			primary key (LOCATION_ID, WORD_ID)
		)");

		Helper::dropTable(static::getTableNamePositionTemporal());

		$dbConnection->query("create table ".static::getTableNamePositionTemporal()." (
			WORD_ID ".Helper::getSqlForDataType('int')." not null,
			POSITION ".Helper::getSqlForDataType('int')." default '0'
		)");
	}

	public static function createIndex()
	{
		Helper::dropIndexByName('IX_SALE_LOC_SEARCH_WORD_TMP', static::getTableName());
		Helper::createIndex(static::getTableName(), 'WP', array('WORD', 'POSITION'));
	}

	public static function parseWords($words)
	{
		$result = array();
		foreach($words as $k => &$word)
		{
			$word = ToUpper(trim($word));
			$word = str_replace('%', '', $word);

			if(!strlen($word))
				continue;

			$result[] = $word;
		}

		//natsort($result);

		return array_unique($result);
	}

	public static function parseString($query)
	{
		$query = ToUpper(Trim($query));

		//$query = str_replace(array_keys(static::$blackList), static::$blackList, ' '.$query.' ');
		$query = str_replace(array(')', '(', '%', '_'), array('', '', '', ''), $query);

		$words = explode(' ', $query);

		return self::parseWords($words);
	}

	public function setOffset($offset)
	{
		$this->procData['OFFSET'] = $offset;
	}

	public function getOffset()
	{
		return $this->procData['OFFSET'];
	}

	public function setPosition($position)
	{
		$this->procData['POSITION'] = $position;
	}

	public function getPosition()
	{
		return $this->procData['POSITION'];
	}

	public function onBeforeDictionaryFlush()
	{
		$this->dictionaryIndex = array();
	}

	public static function getFilterForInitData($parameters = array())
	{
		$filter = array();

		if(!is_array($parameters))
			$parameters = array();

		if(is_array($parameters['TYPES']) && !empty($parameters['TYPES']))
			$filter['=LOCATION.TYPE_ID'] = array_unique($parameters['TYPES']);

		if(is_array($parameters['LANGS']) && !empty($parameters['LANGS']))
			$filter['=LANGUAGE_ID'] = array_unique($parameters['LANGS']);

		return $filter;
	}

	public function initializeData()
	{
		$res = Location\Name\LocationTable::getList(array(
			'select' => array(
				'NAME',
				'LOCATION_ID'
			),
			'filter' => static::getFilterForInitData(array(
				'TYPES' => $this->procData['ALLOWED_TYPES'], 
				'LANGS' => $this->procData['ALLOWED_LANGS']
			)),
			'order' => array('LOCATION_ID' => 'asc'), // need to make same location ids stay together
			'limit' => static::STEP_SIZE,
			'offset' => $this->procData['OFFSET']
		));

		$cnt = 0;
		while($item = $res->fetch())
		{
			if(strlen($item['NAME']))
			{
				if($this->procData['CURRENT_LOCATION'] != $item['LOCATION_ID'])
					$this->procData['CURRENT_LOCATION_WORDS'] = array();

				$this->procData['CURRENT_LOCATION'] = $item['LOCATION_ID'];

				$words = static::parseString($item['NAME']);

				foreach($words as $k => &$word)
				{
					$wordHash = md5($word);

					$wordId = false;
					if(isset($this->dictionaryIndex[$wordHash])) // word is already added and in a hot index
					{
						$wordId = $this->dictionaryIndex[$wordHash];
					}
					else
					{
						$wordId = static::getIdByWord($word); // check if the word was added previously
					}

					if($wordId === false)
					{
						$wordId = $this->dictionaryInserter->insert(array(
							'WORD' => $word
						));
						$this->dictionaryIndex[$wordHash] = $wordId;
					}

					if($wordId !== false && !isset($this->procData['CURRENT_LOCATION_WORDS'][$wordId]))
					{
						$this->procData['CURRENT_LOCATION_WORDS'][$wordId] = true;

						$this->word2LocationInserter->insert(array(
							'LOCATION_ID' => intval($item['LOCATION_ID']),
							'WORD_ID' => intval($wordId)
						));
					}
				}
			}

			$cnt++;
		}

		$this->procData['OFFSET'] += static::STEP_SIZE;

		$this->dictionaryInserter->flush();
		$this->word2LocationInserter->flush();

		return !$cnt;
	}

	public function resort()
	{
		$res = Main\HttpApplication::getConnection()->query(
			Main\HttpApplication::getConnection()->getSqlHelper()->getTopSql("select ID, WORD from ".static::getTableName()." order by WORD asc, ID asc", self::STEP_SIZE, intval($this->procData['OFFSET']))
		);

		$cnt = 0;
		while($item = $res->fetch())
		{
			$this->procData['POSITION']++;

			$this->dictionaryResorter->insert(array(
				'WORD_ID' => $item['ID'],
				'POSITION' => $this->procData['POSITION']
			));

			$cnt++;
		}

		$this->procData['OFFSET'] += static::STEP_SIZE;

		$this->dictionaryResorter->flush();

		return !$cnt;
	}

	public static function mergeResort()
	{
		Helper::mergeTables(
			static::getTableName(),
			static::getTableNamePositionTemporal(),
			array('POSITION' => 'POSITION'),
			array('ID' => 'WORD_ID')
		);

		Main\HttpApplication::getConnection()->query("drop table ".static::getTableNamePositionTemporal());
	}

	public static function getIdByWord($word)
	{
		if(!strlen($word))
			return false;

		$dbConnection = Main\HttpApplication::getConnection();

		$item = $dbConnection->query("select ID from ".static::getTableName()." where WORD = '".$dbConnection->getSqlHelper()->forSql($word)."'")->fetch();

		return intval($item['ID']) ? intval($item['ID']) : false;
	}

	public static function getBoundsByWord($word)
	{
		$word = trim($word);

		$dbConnection = Main\HttpApplication::getConnection();
		$sql = "select MIN(POSITION) as INF, MAX(POSITION) as SUP from ".static::getTableName()." where WORD like '".ToUpper($dbConnection->getSqlHelper()->forSql($word))."%'";

		return $dbConnection->query($sql)->fetch();
	}

	public static function getWordsByBounds($inf, $sup)
	{
		return static::getList(array('filter' => array(
			'>=POSITION' => intval($inf),
			'<=POSITION' => intval($sup)
		), 'order' => array(
			'POSITION' => 'asc'
		)));
	}

	public static function getBoundsForPhrase($phrase)
	{
		if(is_string($phrase))
			$words = self::parseString($phrase);
		elseif(is_array($phrase))
			$words = self::parseWords($phrase);
		else
			return array();

		// check for empty request

		$bounds = array();
		$sizes = array();
		$i = 0;
		foreach($words as $word)
		{
			$bound = self::getBoundsByWord($word);
			if(!intval($bound['INF']) && !intval($bound['SUP'])) // no such word
				return array();

			$bounds[$i] = $bound;

			$sizes[] = $bound['SUP'] - $bound['INF'];

			$i++;
		}

		// resort bounds to have sorted smallest to largest
		//asort($sizes, SORT_NUMERIC);

		$boundsSorted = array();
		foreach($sizes as $j => $size)
			$boundsSorted[] = $bounds[$j];

		// todo: here drop nested intervals, if any

		return $boundsSorted;
	}

	public static function getMap()
	{
		return array(

			'ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'WORD' => array(
				'data_type' => 'string',
			),
			'POSITION' => array(
				'data_type' => 'integer'
			),

			'CNT' => array(
				'data_type' => 'integer',
				'expression' => array(
					'count(*)'
				)
			),
		);
	}
}

