<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Sale\Location\DB;

use Bitrix\Main;

use Bitrix\Sale\Location\DB\Helper;

class BlockInserter
{
	protected $tableName = 		'';
	protected $tableMap = 		array();
	protected $fldVector = 		array();

	protected $insertHead = 	'';
	protected $insertTail = 	'';
	protected $index = 			0;
	protected $bufferSize = 	0;
	protected $buffer = 		'';
	protected $map = 			array();

	protected $autoIncFld = 	false;
	protected $dbType = 		false;
	protected $mtu = 			0;

	protected $dbConnection = 	null;
	protected $dbHelper = 		null;

	protected $callbacks = 		array();

	const RED_LINE = 			100;

	public function __construct($parameters = array())
	{
		$this->dbConnection = Main\HttpApplication::getConnection();
		$this->dbHelper = $this->dbConnection->getSqlHelper();

		$map = array();
		if(strlen($parameters['entityName']))
		{
			$table = $parameters['entityName'];

			$this->tableName = $table::getTableName();
			$this->tableMap = $table::getMap();

			// filter map throught $parameters['exactFields']
			if(is_array($parameters['exactFields']) && !empty($parameters['exactFields']))
			{
				foreach($parameters['exactFields'] as $fld)
				{
					if(!isset($this->tableMap[$fld]))
						throw new Main\SystemException('Field does not exist in ORM class, but present in "exactFields" parameter: '.$fld, 0, __FILE__, __LINE__);

					$map[] = $fld;
					$this->fldVector[$fld] = true;
				}
			}
			else
			{
				foreach($this->tableMap as $fld => $params)
				{
					$map[] = $fld;
					$this->fldVector[$fld] = true;
				}
			}
		}
		elseif(strlen($parameters['tableName']))
		{
			$this->tableName = $this->dbHelper->forSql($parameters['tableName']);
			$this->tableMap = $parameters['exactFields'];

			// $this->tableMap as $fld => $params - is the right way!
			/*
			required for

				$loc2site = new DB\BlockInserter(array(
					'tableName' => 'b_sale_loc_2site',
					'exactFields' => array(
						'LOCATION_ID' => array('data_type' => 'integer'),
						'SITE_ID' => array('data_type' => 'string')
					),
					'parameters' => array(
						'mtu' => 9999,
						'autoIncrementFld' => 'ID'
					)
				));
			*/
			foreach($this->tableMap as $fld => $params)
			{
				$map[] = $fld;
				$this->fldVector[$fld] = true;
			}
		}

		// automatically insert to this field an auto-increment value
		// beware of TransactSQL`s IDENTITY_INSERT when setting autoIncrementFld to a database-driven auto-increment field
		if(strlen($parameters['parameters']['autoIncrementFld']))
		{
			$this->autoIncFld = $this->dbHelper->forSql($this->autoIncFld);

			$this->autoIncFld = $parameters['parameters']['autoIncrementFld'];
			if(!isset($this->fldVector[$this->autoIncFld]))
			{
				$map[] = $this->autoIncFld;
				$this->fldVector[$this->autoIncFld] = true;
				$this->tableMap[$this->autoIncFld] = array('data_type' => 'integer');
			}

			$this->initIndexFromField();
		}

		$this->dbType = Main\HttpApplication::getConnection()->getType();

		if(!($this->mtu = intval($parameters['parameters']['mtu'])))
			$this->mtu = 9999;

		$dbMtu = Helper::getMaxTransferUnit();
		if($this->mtu > $dbMtu)
			$this->mtu = $dbMtu;

		$this->insertHead = Helper::getBatchInsertHead($this->tableName, $map);
		$this->insertTail = Helper::getBatchInsertTail();

		if(is_callable($parameters['parameters']['CALLBACKS']['ON_BEFORE_FLUSH']))
			$this->callbacks['ON_BEFORE_FLUSH'] = $parameters['parameters']['CALLBACKS']['ON_BEFORE_FLUSH'];

		$this->map = $map;
	}

	// this method is buggy when table is empty
	public function initIndexFromField($fld = 'ID')
	{
		if(!strlen($fld))
			throw new Main\SystemException('Field is not set');

		$fld = $this->dbHelper->forSql($fld);

		$sql = 'select MAX('.$fld.') as VAL from '.$this->tableName;

		$res = $this->dbConnection->query($sql)->fetch();
		$this->index = intval($res['VAL']);

		/*
		$sql = 'select '.$fld.' from '.$this->tableName.' order by '.$fld.' desc';
		$sql = $this->dbHelper->getTopSql($sql, 1);

		$res = $this->dbConnection->query($sql)->fetch();
		$this->index = intval($res[$this->autoIncFld]);
		*/

		return $this->index;
	}

	public function getIndex()
	{
		return $this->index;
	}

	public function dropAutoIncrementRestrictions()
	{
		if($this->autoIncFld === false)
			return false;

		return Helper::dropAutoIncrementRestrictions($this->tableName);
	}

	public function restoreAutoIncrementRestrictions()
	{
		if($this->autoIncFld === false)
			return false;

		return Helper::restoreAutoIncrementRestrictions($this->tableName);
	}

	public function resetAutoIncrementFromIndex()
	{
		Helper::resetAutoIncrement($this->tableName, $this->getIndex() + 1);
	}

	public function insert($row)
	{
		if(!is_array($row) || empty($row))
			return;

		$this->index++;
		$this->bufferSize++;

		if($this->autoIncFld !== false)
		{
			$row[$this->autoIncFld] = $this->index;
			Helper::incrementSequenceForTable($this->tableName); // if this is oracle and we insert auto increment key directly, we must provide sequence increment manually
		}

		$sql = Helper::getBatchInsertValues($row, $this->tableName, $this->fldVector, $this->map);

		/*
		MySQL & MsSQL: insert into b_test (F1,F2) values ('one','two'),('one1','two1'),('one2','two2')
		Oracle: insert all into b_test (F1,F2) values ('one','two') into b_test (F1,F2) values ('one1','two1') into b_test (F1,F2) values ('one2','two2')  select * from dual
		*/

		$nextBuffer = (empty($this->buffer) ? $this->insertHead : $this->buffer.Helper::getBatchInsertSeparator()).$sql;

		// here check length
		if(defined(SITE_CHARSET) && SITE_CHARSET == 'UTF-8')
			$len = mb_strlen($nextBuffer);
		else
			$len = strlen($nextBuffer);

		if(($this->mtu - (strlen($nextBuffer) + 100)) < self::RED_LINE)
		{
			$this->flush(); // flushing the previous buffer (now $this->buffer == '')
			$this->buffer = $this->insertHead.$sql;
		}
		else
			$this->buffer = $nextBuffer;

		return $this->index;
	}

	public function flush()
	{
		if(!strlen($this->buffer))
			return;

		if(isset($this->callbacks['ON_BEFORE_FLUSH']))
			call_user_func($this->callbacks['ON_BEFORE_FLUSH']);

		$this->buffer .= ' '.$this->insertTail;

		$restrDropped = $this->dropAutoIncrementRestrictions();

		Main\HttpApplication::getConnection()->query($this->buffer);

		if($restrDropped)
			$this->restoreAutoIncrementRestrictions();

		$this->buffer = '';
		$this->bufferSize = 0;
	}
}