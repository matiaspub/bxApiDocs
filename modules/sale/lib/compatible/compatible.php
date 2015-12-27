<?php

namespace Bitrix\Sale\Compatible;

use	Bitrix\Main\Entity,
	Bitrix\Main\Entity\Query,
	Bitrix\Main\Entity\Field,
	Bitrix\Main\Entity\ReferenceField,
	Bitrix\Main\Entity\ExpressionField,
	Bitrix\Main\SystemException;

use Bitrix\Sale\Compatible;

class AliasedQuery extends Query
{
	private $aliases = array();

	public function __construct($source)
	{
		parent::__construct($source);

		$aliases = &$this->aliases;

		/** @var Field $field */
		foreach ($this->getEntity()->getFields() as $field)
		{
			if (! $field instanceof ReferenceField)
			{
				$name = $field->getName();
				$aliases[$name] = $name;
			}
		}
	}

	public function getAliases()
	{
		return $this->aliases;
	}

	public function addAliases(array $aliases)
	{
		foreach ($aliases as $alias => $field)
		{
			$this->addAlias($alias, $field);
		}

		return $this;
	}

	public function addAlias($alias, $field = null)
	{
		if ($this->aliases[$alias])
		{
			throw new SystemException("`$alias` already added", 0, __FILE__, __LINE__);
		}
		elseif (! $field)
		{
			$this->aliases[$alias] = $alias;
		}
		elseif (is_string($field) || (is_array($field) && $field['expression'])) // TODO Field support
		{
			$this->aliases[$alias] = $field;
		}
		else
		{
			throw new SystemException("invalid `$alias` type", 0, __FILE__, __LINE__);
		}

		return $this;
	}

	public function getAliasName($alias)
	{
		if ($field = $this->aliases[$alias])
		{
			if (is_string($field))
			{
				return $field; // name
			}
			elseif (is_array($field)) // TODO Field support
			{
				$name = '__'.$alias.'_ALIAS__';
				if (! $field['registered'])
				{
					$field['registered'] = true;
					$this->registerRuntimeField($name, $field);
				}
				return $name;
			}
			else
			{
				throw new SystemException("invalid alias '$alias' type", 0, __FILE__, __LINE__);
			}
		}
		else
		{
			return null;
		}
	}

	public function addAliasSelect($alias)
	{
		return ($name = $this->getAliasName($alias))
			? $this->addSelect($name, $alias)
			: $this;
	}

	public function addAliasGroup($alias)
	{
		return ($name = $this->getAliasName($alias))
			? $this->addGroup($name)
			: $this;
	}

	public function addAliasOrder($alias, $order)
	{
		return ($name = $this->getAliasName($alias))
			? $this->addOrder($name, $order)
			: $this;
	}

	public function addAliasFilter($key, $value)
	{
		preg_match('/^([!%@<=>]{0,3})(.*)$/', $key, $matches);

		return ($name = $this->getAliasName($matches[2]))
			? $this->addFilter($matches[1].$name, $value)
			: $this;

		// TODO recursive filters maybe?
//		if (is_null($key) && is_array($value))
//		{
//			return ($filter = self::getAliasFilterRecursive($value))
//				? $this->addFilter(null, $filter)
//				: $this;
//		}
//		else
//		{
//			preg_match('/^([!%@<=>]{0,3})(.*)$/', $key, $matches);
//
//			$alias = $matches[2];
//
//			if (! ($name = $this->getAliasName($alias)))
//			{
//				if ($this->getEntity()->hasField($alias))
//					$name = $alias;
//				else
//					return $this;
//			}
//
//			$key = $matches[1].$name;
//			return parent::addFilter($key, $value);
//		}
	}

//	private function getAliasFilterRecursive(array $filter)
//	{
//		$resolved = array();
//
//		foreach ($filter as $key => $value)
//		{
//			if ($key === 'LOGIC')
//			{
//				$resolved['LOGIC'] = $value;
//			}
//			elseif (is_array($value))
//			{
//				$resolved []= self::getAliasFilterRecursive($value);
//			}
//			else
//			{
//				preg_match('/^([!%@<=>]{0,3})(.*)$/', $key, $matches);
//
//				$alias = $matches[2];
//
//				if (! ($name = $this->getAliasName($alias)))
//				{
//					if ($this->getEntity()->hasField($alias))
//						$name = $alias;
//					else
//						continue;
//				}
//
//				$key = $matches[1].$name;
//				$resolved[$key] = $value;
//			}
//		}
//
//		return $resolved;
//	}
}

final class CDBResult extends \CDBResult
{
	public function compatibleNavQuery(Query $query, array $arNavStartParams) //, $bIgnoreErrors = false)
	{
		$cnt = $query->exec()->getSelectedRowsCount(); // TODO check groups

		global $DB;

		if(isset($arNavStartParams["SubstitutionFunction"]))
		{
			$arNavStartParams["SubstitutionFunction"]($this, $query->getLastQuery(), $cnt, $arNavStartParams);
			return null;
		}

		if(isset($arNavStartParams["bDescPageNumbering"]))
			$bDescPageNumbering = $arNavStartParams["bDescPageNumbering"];
		else
			$bDescPageNumbering = false;

		$this->InitNavStartVars($arNavStartParams);
		$this->NavRecordCount = $cnt;

		if($this->NavShowAll)
			$this->NavPageSize = $this->NavRecordCount;

		//calculate total pages depend on rows count. start with 1
		$this->NavPageCount = ($this->NavPageSize>0 ? floor($this->NavRecordCount/$this->NavPageSize) : 0);
		if($bDescPageNumbering)
		{
			$makeweight = 0;
			if($this->NavPageSize > 0)
				$makeweight = ($this->NavRecordCount % $this->NavPageSize);
			if($this->NavPageCount == 0 && $makeweight > 0)
				$this->NavPageCount = 1;

			//page number to display
			$this->NavPageNomer =
				(
				$this->PAGEN < 1 || $this->PAGEN > $this->NavPageCount
					?
					($_SESSION[$this->SESS_PAGEN] < 1 || $_SESSION[$this->SESS_PAGEN] > $this->NavPageCount
						?
						$this->NavPageCount
						:
						$_SESSION[$this->SESS_PAGEN]
					)
					:
					$this->PAGEN
				);

			//rows to skip
			$NavFirstRecordShow = 0;
			if($this->NavPageNomer != $this->NavPageCount)
				$NavFirstRecordShow += $makeweight;

			$NavFirstRecordShow += ($this->NavPageCount - $this->NavPageNomer) * $this->NavPageSize;
			$NavLastRecordShow = $makeweight + ($this->NavPageCount - $this->NavPageNomer + 1) * $this->NavPageSize;
		}
		else
		{
			if($this->NavPageSize > 0 && ($this->NavRecordCount % $this->NavPageSize > 0))
				$this->NavPageCount++;

			//calculate total pages depend on rows count. start with 1
			if($this->PAGEN >= 1 && $this->PAGEN <= $this->NavPageCount)
				$this->NavPageNomer = $this->PAGEN;
			elseif($_SESSION[$this->SESS_PAGEN] >= 1 && $_SESSION[$this->SESS_PAGEN] <= $this->NavPageCount)
				$this->NavPageNomer = $_SESSION[$this->SESS_PAGEN];
			elseif($arNavStartParams["checkOutOfRange"] !== true)
				$this->NavPageNomer = 1;
			else
				return null;

			//rows to skip
			$NavFirstRecordShow = $this->NavPageSize*($this->NavPageNomer-1);
			$NavLastRecordShow = $this->NavPageSize*$this->NavPageNomer;
		}

		$NavAdditionalRecords = 0;
		if(is_set($arNavStartParams, "iNavAddRecords"))
			$NavAdditionalRecords = $arNavStartParams["iNavAddRecords"];

		if(!$this->NavShowAll)
		{
			$query->setOffset($NavFirstRecordShow);
			$query->setLimit($NavLastRecordShow - $NavFirstRecordShow + $NavAdditionalRecords);
		}

		$res_tmp = $query->exec(); //, $bIgnoreErrors);

//		// Return false on sql errors (if $bIgnoreErrors == true)
//		if ($bIgnoreErrors && ($res_tmp === false))
//			return false;

//		$this->result = $res_tmp->result;
		$this->DB = DB;

		if($this->SqlTraceIndex)
			$start_time = microtime(true);

		$temp_arrray = array();
		$temp_arrray_add = array();
		$tmp_cnt = 0;

		while($ar = $res_tmp->fetch())
		{
			$tmp_cnt++;
			if (intval($NavLastRecordShow - $NavFirstRecordShow) > 0 && $tmp_cnt > ($NavLastRecordShow - $NavFirstRecordShow))
				$temp_arrray_add[] = $ar;
			else
				$temp_arrray[] = $ar;
		}

		if($this->SqlTraceIndex)
		{
			/** @noinspection PhpUndefinedVariableInspection */
			$exec_time = round(microtime(true) - $start_time, 10);
			$DB->addDebugTime($this->SqlTraceIndex, $exec_time);
			$DB->timeQuery += $exec_time;
		}

		$this->arResult = (!empty($temp_arrray)? $temp_arrray : false);
		$this->arResultAdd = (!empty($temp_arrray_add)? $temp_arrray_add : false);
		$this->nSelectedCount = $cnt;
		$this->bDescPageNumbering = $bDescPageNumbering;
		$this->bFromLimited = true;

		return null;
	}

	// FetchAdapter

	private $fetchAdapters = array();

	public function addFetchAdapter(FetchAdapter $adapter)
	{
		$this->fetchAdapters[] = $adapter;
	}

	public function Fetch()
	{
		if ($row = parent::Fetch())
			foreach ($this->fetchAdapters as $adapter)
				$row = $adapter->adapt($row);

		return $row;
	}
}

interface FetchAdapter
{
	static public function adapt(array $row);
}

class AggregateAdapter implements FetchAdapter
{
	private $aggregated = array();

	public function __construct(array $aggregated)
	{
		$this->aggregated = $aggregated;
	}

	public function adapt(array $row)
	{
		foreach ($this->aggregated as $alias => $name)
		{
			$row[$name] = $row[$alias];
			unset ($row[$alias]);
		}

		return $row;
	}
}

class OrderQuery extends AliasedQuery
{
	private $counted, $grouped, $allSelected, $aggregated = array();

	public function counted()
	{
		return $this->counted;
	}

	public function grouped()
	{
		return $this->grouped;
	}

	public function allSelected()
	{
		return $this->allSelected;
	}

	public function aggregated()
	{
		return $this->aggregated ? true : false;
	}

	private function addAggregatedSelect($alias, $aggregate, $name = null)
	{
		$aggregateAlias = '__'.$aggregate.'_'.$alias.'_ALIAS__';
		$this->aggregated[$aggregateAlias] = $alias;

		return $this->addSelect(
			$name
				? new ExpressionField($aggregateAlias, $aggregate.'(%s)', $name)
				: new ExpressionField($aggregateAlias, $aggregate)
		);
	}

	public static function explodeFilterKey($key)
	{
		preg_match('/^([!+]{0,1})([<=>@%~]{0,2})(.*)$/', $key, $matches);

		return array(
			'modifier' => $matches[1], // can be ""
			'operator' => $matches[2], // can be ""
			'alias'    => $matches[3], // can be ""
		);
	}

	public function compatibleAddFilter($key, $value)
	{
		$keyMatch = static::explodeFilterKey($key);
		$modifier = $keyMatch['modifier'];
		$operator = $keyMatch['operator'];
		$alias    = $keyMatch['alias'   ];

		if (! $name = $this->getAliasName($alias))
			return $this;

		switch ($operator)
		{
			case  '':
			case '@': $operator = '='; break;

			case '~': $operator =  ''; break;
			// default: with no changes
		}

		switch ($modifier)
		{
			case '' : return $this->addFilter($modifier.$operator.$name, $value);

			case '!': return $operator == '=' && $value
				? $this->addFilter(null, array('LOGIC' => 'OR', array('!='.$name => $value), array('='.$name => '')))
				: $this->addFilter($modifier.$operator.$name, $value);

			case '+': return $this->addFilter(null, array('LOGIC' => 'OR', array($operator.$name => $value), array('='.$name => '')));

			default : throw new SystemException("invalid modifier '$modifier'", 0, __FILE__, __LINE__);
		}
	}

	protected function mapLocationRuntimeField($field, $asFilter = false)
	{
		return $field;
	}

	public function prepare(array $order, array $filter, $group, array $select)
	{
		// Do not remove!!!
//		file_put_contents('/var/www/log'
//			, spl_object_hash($this)."\n"
//			. 'Order: '.print_r($order,true)
//			. 'Filter: '.print_r($filter,true)
//			. 'Group: '.print_r($group,true)
//			. 'Select: '.print_r($select,true)
//			."\n\n\n"
//			, FILE_APPEND);

		static $aggregates = array('COUNT'=>1, 'AVG'=>1, 'MIN'=>1, 'MAX'=>1, 'SUM'=>1);

		foreach ($filter as $key => $value)
		{
			$key = $this->mapLocationRuntimeField($key, true);

			$this->compatibleAddFilter($key, $value);
		}

		if (is_array($group))
		{
			if (empty($group))
			{
				$this->counted = true;
				return;
			}
			else
			{
				foreach ($group as $key => $alias)
				{
					if ($name = $this->getAliasName($alias))
					{
						if (is_string($key) && ($aggregate = ToUpper($key)) && $aggregates[$aggregate])
						{
							$this->addAggregatedSelect($alias, $aggregate, $name);
						}
						else
						{
							$this->grouped = true;
							$this->addGroup($name);
							$this->addSelect($name, $alias);
						}
					}
				}

				if ($this->grouped)
				{
					$this->addAggregatedSelect('CNT', 'COUNT(*)');
					// TODO Maybe? "COUNT(%%_DISTINCT_%% ".$arFields[$arFieldsKeys[0]]["FIELD"].") as CNT ";
				}
			}
		}
		else
		{
			if (empty($select) || $select == array('*'))
			{
				$this->allSelected = true;

				foreach ($this->getAliases() as $alias => $field)
				{
					$field = $this->mapLocationRuntimeField($field);

					$this->addAliasSelect($alias);
				}
			}
			else
			{
				foreach ($select as $key => $alias)
				{
					$alias = $this->mapLocationRuntimeField($alias);

					if ($name = $this->getAliasName($alias))
					{
						if (is_string($key) && ($aggregate = ToUpper($key)) && $aggregates[$aggregate])
						{
							$this->addAggregatedSelect($alias, $aggregate, $name);
						}
						else
						{
							$this->addSelect($name, $alias);
						}
					}
				}
			}
		}

		foreach ($order as $alias => $value)
		{
			$alias = $this->mapLocationRuntimeField($alias);

			$this->addAliasOrder($alias, $value);
		}
	}

	public function getSelectNamesAssoc()
	{
		$names = array();

		foreach ($this->getSelect() as $k => $v)
		{
			if (is_numeric($k))
			{
				if ($v instanceof Field)
					$names[$v->getName()] = true;
				else
					throw new SystemException("invalid", 0, __FILE__, __LINE__);
			}
			else
			{
				$names[$k] = true;
			}
		}

		return $names;
	}

	public function compatibleExec(CDBResult $result, $navStart)
	{
		if ($this->aggregated)
		{
			$result->addFetchAdapter(new AggregateAdapter($this->aggregated));
		}

		if (is_array($navStart) && isset($navStart['nTopCount']))
		{
			if ($navStart['nTopCount'] > 0)
			{
				$this->setLimit($navStart['nTopCount']);
			}
			else
			{
				$result->compatibleNavQuery($this, $navStart);
				return $result;
			}
		}

		// Do not remove!!!
//		file_put_contents('/var/www/log', "\n\n\n\n".$this->getQuery()."\n", FILE_APPEND); // $this->dump()

		$rows = $this->exec()->fetchAll();

		// Do not remove!!!
//		foreach ($rows as $row)
//		{
//			file_put_contents('/var/www/log', "\n".print_r($row, true), FILE_APPEND);
//		}

		$result->InitFromArray($rows);

		// Do not remove!!!
//		while ($row = $result->Fetch())
//		{
//			file_put_contents('/var/www/log', "\n".print_r($row, true), FILE_APPEND);
//		}

		return $result;
	}
}

class OrderQueryLocation extends OrderQuery
{
	protected $locationFieldMap = array();

	public function addLocationRuntimeField($fieldName, $ref = false)
	{
		if((string) $fieldName == '')
			return false;

		$this->registerRuntimeField(
			'LOCATION',
			array(
				'data_type' => '\Bitrix\Sale\Location\LocationTable',
				'reference' => array(
					'=this.'.$fieldName => 'ref.CODE'
				),
				'join_type' => 'left'
			)
		);

		$this->registerRuntimeField(
			'PROXY_'.$fieldName.'_LINK',
			array(
				'data_type' => 'string',
				'expression' => array(
					"CASE WHEN %s = 'LOCATION' THEN CAST(%s AS CHAR) ELSE %s END",
					($ref !== false ? $ref.'.' : '').'TYPE',
					'LOCATION.ID',
					$fieldName
				)
			)
		);

		$this->addAliases(array(
			$fieldName.'_ORIG'   => $fieldName,
			'PROXY_'.$fieldName  => 'PROXY_'.$fieldName.'_LINK'
		));

		$this->locationFieldMap[$fieldName] = true;
	}

	protected function mapLocationRuntimeField($field, $asFilter = false)
	{
		if($asFilter)
		{
			$parsed = static::explodeFilterKey($field);

			if($this->locationFieldMap[$parsed['alias']])
				return $parsed['modifier'].$parsed['operator'].'PROXY_'.$parsed['alias'];
			else
				return $field;
		}
		else
		{
			if($this->locationFieldMap[$field])
				return 'PROXY_'.$field;
			else
				return $field;
		}
	}
}

final class Test
{
	static function assertLastQuery($name, $query)
	{
		$lastQuery = Query::getLastQuery();
		return $query == $lastQuery ? "ok\n" : "\n$name - Assert Last Query Failed!\nExpected:\n($query)\nGiven:\n($lastQuery)\n\n";
	}

	static function run()
	{
		foreach (glob($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/sale/lib/compatible/tests/*.test.php') as $filename)
		{
			include $filename;
		}
	}
}
