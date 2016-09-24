<?php
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Sale\Location\Import\Compiler\Db;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Sale\Location;

class ExportTreeTable extends Entity\DataManager
{
	const CODE_LENGTH = 			10;
	const RECURSION_MAX_DEPTH = 	30;

	protected $inserter = 	null;
	protected $codeIndex = 	array();
	protected $exportOffset = 1;
	protected $exportPath = 	array();

	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_tmp_export_tree';
	}

	public static function getMap()
	{
		return array(

			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),

			'CODE' => array(
				'data_type' => 'string',
			),
			'PARENT_CODE' => array(
				'data_type' => 'string',
			),
			'SYS_CODE' => array(
				'data_type' => 'string',
			),

			'TYPE_CODE' => array(
				'data_type' => 'string',
			),
			'FIAS_TYPE' => array(
				'data_type' => 'string',
			),
			'NAME' => array(
				'data_type' => 'string',
				'primary' => true,
			),
			'LANGNAMES' => array(
				'data_type' => 'string',
			),
			'EXTERNALS' => array(
				'data_type' => 'string',
			),
			'LATITUDE' => array(
				'data_type' => 'string',
			),
			'LONGITUDE' => array(
				'data_type' => 'string',
			),
			'SOURCE' => array(
				'data_type' => 'string',
			),

			'ALTERNATE_COORDS' => array(
				'data_type' => 'string',
			),
			'BOUNDED_WITH' => array(
				'data_type' => 'string',
			),
		);
	}

	public function __construct()
	{
		$this->create();

		$this->inserter = new Location\DBBlockInserter(array(
			'entityName' => get_called_class(),
			'exactFields' => array(
				'ID', 'CODE', 'PARENT_CODE', 'SYS_CODE', 'TYPE_CODE', 'FIAS_TYPE', 'NAME', 'LANGNAMES', 'EXTERNALS', 'LATITUDE', 'LONGITUDE', 'SOURCE'
			),
			'parameters' => array(
				'mtu' => 999999,
				'autoIncrementFld' => 'ID'
			)
		));
	}

	public function restoreExportOffset()
	{
		$this->exportOffset = intval($this->getNextFreeCode());
	}

	public function setExportOffset($offset)
	{
		$this->exportOffset = $offset;
	}

	public function dropCodeIndex()
	{
		$this->codeIndex = array();
	}

	public function insert($data)
	{
		if(isset($this->codeIndex[$data['SYS_CODE']])) // already in there
			return;

		$this->codeIndex[$data['SYS_CODE']] = $this->formatCode($this->exportOffset);

		$data['CODE'] = $this->codeIndex[$data['SYS_CODE']];
		$data['PARENT_CODE'] = strlen($data['PARENT_SYS_CODE']) ? $this->codeIndex[$data['PARENT_SYS_CODE']] : '';

		unset($data['PARENT_SYS_CODE']);

		if(is_array($data['LANGNAMES']))
			$data['LANGNAMES'] = serialize($data['LANGNAMES']);

		if(is_array($data['EXTERNALS']))
			$data['EXTERNALS'] = serialize($data['EXTERNALS']);

		$this->exportOffset++;

		$this->inserter->insert($data);
	}

	public function doneInsert()
	{
		$this->inserter->flush();
	}

	public function deleteAll()
	{
		$this->cleanup();
	}

	static public function getLastOccupiedCode()
	{
		$res = static::getList(array('order' => array('ID' => 'desc'), 'limit' => 1, 'select' => array('CODE')))->fetch();

		return $res['CODE'];
	}

	static public function getNextFreeCode()
	{
		return self::formatCode(intval(static::getLastOccupiedCode()) + 1);
	}

	public static function formatCode($value, $length = self::CODE_LENGTH)
	{
		if(strlen($value) >= $length)
			return $value;

		$diff = abs($length - strlen($value));

		for($i = 0; $i < $diff; $i++)
			$value = '0'.$value;

		return $value;
	}

	static public function getByCode($code)
	{
		return static::getList(array('filter' => array(
			'=CODE' => $code
		)));
	}

	public function getPathTo($code)
	{
		$nextCode = $code;
		$result = array();
		while($nextCode)
		{
			$node = $this->getByCode($nextCode)->fetch();

			$result[] = $node;
			$nextCode = $node['PARENT_CODE'];
		}

		return $result;
	}

	public function getWalkPath()
	{
		return $this->exportPath;
	}

	public function getWalkPathString()
	{
		$res = array();
		foreach ($this->exportPath as $item)
		{
			$res[] = $item['NAME'].' ('.$item['TYPE_CODE'].')';
		}

		return implode(', ', $res);
	}

	public function walkInDeep($callbacks, $ignoreThisAndDeeper = array(), $startFrom = '')
	{
		if(!is_callable($callbacks['ITEM']))
			throw new Main\SystemException('Invalid callback passed');

		$this->exportPath = array();
		$this->waklInDeepBundle($callbacks, $ignoreThisAndDeeper, $startFrom);
	}

	private function waklInDeepBundle($callbacks, $ignoreThisAndDeeper = array(), $parentCode = '', $depth = 1)
	{
		if($depth > static::RECURSION_MAX_DEPTH)
			throw new Main\SystemException('Too deep recursion');

		$res = $this->getList(array('filter' => array('PARENT_CODE' => $parentCode)));
		while($item = $res->fetch())
		{
			array_push($this->exportPath, $item);

			$goDeeper = true;
			if(call_user_func($callbacks['ITEM'], $item, $this) === false)
				$goDeeper = false;

			if(isset($ignoreThisAndDeeper[$item['TYPE_CODE']]))
				$goDeeper = false;

			if($goDeeper)
				$this->waklInDeepBundle($callbacks, $ignoreThisAndDeeper, $item['CODE'], $depth + 1);

			array_pop($this->exportPath);
		}
	}

	public function create()
	{
		$dbConnection = Main\HttpApplication::getConnection();

		$table = static::getTableName();

		global $DB;

		if(!$DB->query('select * from '.$table.' where 1=0', true))
		{
			$dbConnection->query('create table '.$table.' (

				ID int auto_increment not null primary key,

				CODE varchar(100) not null,
				PARENT_CODE varchar(100),
				SYS_CODE varchar(100),

				TYPE_CODE varchar(20),
				FIAS_TYPE varchar(10),

				NAME varchar(100) not null,

				LANGNAMES varchar(300),
				EXTERNALS varchar(200),

				LATITUDE varchar(30),
				LONGITUDE varchar(30),

				ALTERNATE_COORDS varchar(100),
				BOUNDED_WITH varchar(100),

				SOURCE varchar(2)
			)');

			$this->restoreIndexes();
		}
	}

	static public function dropIndexes()
	{
		$dbConnection = Main\HttpApplication::getConnection();
		$table = static::getTableName();

		try
		{
			$dbConnection->query('DROP INDEX IX_SALE_LOCATION_EXPORT_TREE_CODE ON '.$table);
		}
		catch(\Exception $e)
		{
		}

		try
		{
			$dbConnection->query('DROP INDEX IX_SALE_LOCATION_EXPORT_TREE_PARENT_CODE ON '.$table);
		}
		catch(\Exception $e)
		{
		}

		try
		{
			$dbConnection->query('DROP INDEX IX_SALE_LOCATION_EXPORT_TREE_TYPE_CODE ON '.$table);
		}
		catch(\Exception $e)
		{
		}
	}

	static public function restoreIndexes()
	{
		$dbConnection = Main\HttpApplication::getConnection();
		$table = static::getTableName();

		try
		{
			$dbConnection->query('CREATE INDEX IX_SALE_LOCATION_EXPORT_TREE_CODE ON '.$table.' (CODE)');
		}
		catch(\Exception $e)
		{
		}
		
		try
		{
			$dbConnection->query('CREATE INDEX IX_SALE_LOCATION_EXPORT_TREE_PARENT_CODE ON '.$table.' (PARENT_CODE)');
		}
		catch(\Exception $e)
		{
		}

		try
		{
			$dbConnection->query('CREATE INDEX IX_SALE_LOCATION_EXPORT_TREE_TYPE_CODE ON '.$table.' (TYPE_CODE)');
		}
		catch(\Exception $e)
		{
		}
	}

	static public function cleanup()
	{
		Main\HttpApplication::getConnection()->query('truncate table '.static::getTableName());
	}

	public static function switchIndexes($way = true)
	{
		Main\HttpApplication::getConnection()->query('alter table '.static::getTableName().' '.($way ? 'enable' : 'disable').' keys');
	}

	static public function output($data, $important = true)
	{
		if(!$important)
			return false;

		ob_start();
		print_r($data);
		$data = ob_get_contents();
		ob_end_clean();

		file_put_contents($_SERVER['DOCUMENT_ROOT'].'/output.txt', $data.PHP_EOL, FILE_APPEND);
	}
}
