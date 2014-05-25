<?php

namespace Bitrix\Main\Entity;

use Bitrix\Main;

class Query
{
	protected
		$init_entity;

	protected
		$select = array(),
		$group = array(),
		$order = array(),
		$limit = null,
		$offset = null,
		$count_total = null;

	protected
		$filter = array(),
		$where = array(),
		$having = array();

	/**
	 * @var QueryChain[]
	 */
	protected					  // all chain storages keying by alias
		$select_chains = array(),
		$group_chains = array(),
		$order_chains = array();

	/**
	 * @var QueryChain[]
	 */
	protected
		$filter_chains = array(),
		$where_chains = array(),
		$having_chains = array();

	protected
		$select_expr_chains = array(), // from select expr "build_from"
		$having_expr_chains = array(), // from having expr "build_from"
		$hidden_chains = array(); // all expr "build_from" elements;

	protected
		$runtime_chains;

	protected
		$options;

	/**
	 * @var QueryChain[]
	 */
	protected $global_chains = array(); // keying by both def and alias

	protected $query_build_parts;

	/**
	 * Enable or Disable data doubling for 1:N relations in query filter
	 * If disabled, 1:N entity fields in filter will be trasnformed to exists() subquery
	 * @var bool
	 */
	protected $data_doubling = true;

	protected $table_alias_postfix = '';

	protected
		$join_map = array();

	protected
		$is_executing = false;

	protected
		$last_query;

	protected $replaced_aliases;

	/**
	 * @param Base|Query|string $source
	 * @throws Main\ArgumentException
	 */
	public function __construct($source)
	{
		if ($source instanceof $this)
		{
			$this->init_entity = Base::getInstanceByQuery($source);
		}
		elseif ($source instanceof Base)
		{
			$this->init_entity = $source;
		}
		elseif (is_string($source))
		{
			$this->init_entity = Base::getInstance($source);
		}
		else
		{
			throw new Main\ArgumentException(sprintf(
				'Unknown source type "%s" for new %s', gettype($source), __CLASS__
			));
		}
	}

	/**
	 * Returns an array of fields for SELECT clause
	 *
	 * @return array
	 */
	public function getSelect()
	{
		return $this->select;
	}

	/**
	 * Sets a list of fields for SELECT clause
	 *
	 * @param array $select
	 * @return Query
	 */
	public function setSelect(array $select)
	{
		$this->select = $select;
		return $this;
	}

	/**
	 * Adds a field for SELECT clause
	 *
	 * @param mixed $definition Field
	 * @param string $alias Field alias like SELECT field AS alias
	 * @return Query
	 */
	public function addSelect($definition, $alias = '')
	{
		if (strlen($alias))
		{
			$this->select[$alias] = $definition;
		}
		else
		{
			$this->select[] = $definition;
		}

		return $this;
	}

	/**
	 * Returns an array of filters for WHERE clause
	 *
	 * @return array
	 */
	public function getFilter()
	{
		return $this->filter;
	}

	/**
	 * Sets a list of filters for WHERE clause
	 *
	 * @param array $filter
	 * @return Query
	 */
	public function setFilter(array $filter)
	{
		$this->filter = $filter;
		return $this;
	}

	/**
	 * Adds a filter for WHERE clause
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return Query
	 */
	public function addFilter($key, $value)
	{
		if (is_null($key) && is_array($value))
		{
			$this->filter[] = $value;
		}
		else
		{
			$this->filter[$key] = $value;
		}

		return $this;
	}

	/**
	 * Returns an array of fields for GROUP BY clause
	 *
	 * @return array
	 */
	public function getGroup()
	{
		return $this->group;
	}

	/**
	 * Sets a list of fileds in GROUP BY clause
	 *
	 * @param mixed $group
	 * @return Query
	 */
	public function setGroup($group)
	{
		$group = !is_array($group) ? array($group) : $group;
		$this->group = $group;

		return $this;
	}

	/**
	 * Adds a field to the list of fields for GROUP BY clause
	 *
	 * @param $group
	 * @return Query
	 */
	public function addGroup($group)
	{
		$this->group[] = $group;
		return $this;
	}

	/**
	 * Returns an array of fields for ORDER BY clause
	 *
	 * @return array
	 */
	public function getOrder()
	{
		return $this->order;
	}

	/**
	 * Sets a list of fields for ORDER BY clause
	 *
	 * @param mixed $order
	 * @return Query
	 */
	public function setOrder($order)
	{
		$this->order = array();

		if (!is_array($order))
		{
			$order = array($order);
		}

		foreach ($order as $k => $v)
		{
			if (is_numeric($k))
			{
				$this->addOrder($v);
			}
			else
			{
				$this->addOrder($k, $v);
			}
		}

		return $this;
	}

	/**
	 * Adds a filed to the list of fields for ORDER BY clause
	 *
	 * @param string $definition
	 * @param string $order
	 * @return Query
	 * @throws Main\ArgumentException
	 */
	public function addOrder($definition, $order = 'ASC')
	{
		$order = strtoupper($order);
		$definition = strtoupper($definition);

		if (!in_array($order, array('ASC', 'DESC'), true))
		{
			throw new Main\ArgumentException(sprintf('Invalid order "%s"', $order));
		}

		$connection = Main\Application::getConnection();
		$helper = $connection->getSqlHelper();

		if ($order == 'ASC')
		{
			$order = $helper->getAscendingOrder();
		}
		else
		{
			$order = $helper->getDescendingOrder();
		}

		$this->order[$definition] = $order;

		return $this;
	}

	/**
	 * Returns a limit
	 *
	 * @return null|int
	 */
	public function getLimit()
	{
		return $this->limit;
	}

	/**
	 * Sets a limit for LIMIT n clause
	 *
	 * @param int $limit
	 * @return Query
	 */
	public function setLimit($limit)
	{
		$this->limit = $limit;
		return $this;
	}

	/**
	 * Returns an offset
	 *
	 * @return null|int
	 */
	public function getOffset()
	{
		return $this->offset;
	}

	/**
	 * Sets an offset for LIMIT n, m clause

	 * @param int $offset
	 * @return Query
	 */
	public function setOffset($offset)
	{
		$this->offset = $offset;
		return $this;
	}

	public function countTotal($count = null)
	{
		if ($count === null)
		{
			return $this->count_total;
		}
		else
		{
			$this->count_total = (bool) $count;
			return $this;
		}
	}

	/**
	 * @deprecated
	 */
	public function enableDataDoubling()
	{
		$this->data_doubling = true;
	}

	/**
	 * @deprecated
	 */
	public function disableDataDoubling()
	{
		$this->data_doubling = false;
	}

	/**
	 * @deprecated
	 */
	public function getOptions()
	{
		return $this->options;
	}

	/**
	 * @deprecated
	 */
	public function setOptions($options)
	{
		$this->options = $options;
		return $this;
	}

	/**
	 * @deprecated
	 */
	public function addOption($option_name, $option_value)
	{
		$this->options[$option_name] = $option_value;
		return $this;
	}

	/**
	 * Adds a runtime field (created dinamycally, opposite to described statically in the entity map) for SELECT clause
	 *
	 * @param string $name
	 * @param array $fieldInfo
	 * @return Query
	 */
	public function registerRuntimeField($name, $fieldInfo)
	{
		$field = $this->init_entity->initializeField($name, $fieldInfo);

		$chain = new QueryChain;
		$chain->addElement(new QueryChainElement($this->init_entity));
		$chain->addElement(new QueryChainElement($field));

		// add
		$this->registerChain('runtime', $chain);
		return $this;
	}

	public function setTableAliasPostfix($postfix)
	{
		$this->table_alias_postfix = $postfix;
		return $this;
	}

	public function getTableAliasPostfix()
	{
		return $this->table_alias_postfix;
	}

	/**
	 * Builds and executes the query and returns the result
	 *
	 * @return \Bitrix\Main\DB\Result
	 */
	public function exec()
	{
		$this->is_executing = true;

		$query = $this->buildQuery();

		$result = $this->query($query);

		$this->is_executing = false;

		return $result;
	}

	protected function addToSelectChain($definition, $alias = '')
	{
		if (is_array($definition))
		{
			// it is runtime field
			$this->registerRuntimeField($alias, $definition);
			$chain = $this->getRegisteredChain($alias);

			// add
			$this->registerChain('select', $chain);

			// recursively collect all "build_from" fields
			if ($chain->getLastElement()->getValue() instanceof ExpressionField)
			{
				$this->collectExprChains($chain, array('hidden', 'select_expr'));
			}
		}
		else
		{
			// there is normal scalar field, or Reference, or Entity (all fields of)
			$chain = $this->getRegisteredChain($definition, true);

			if (!empty($alias))
			{
				// custom alias
				$chain = clone $chain;
				$chain->setCustomAlias($alias);
			}

			$last_elem = $chain->getLastElement();

			// fill if element is not scalar
			$expand_entity = null;

			if ($last_elem->getValue() instanceof ReferenceField)
			{
				$expand_entity = $last_elem->getValue()->getRefEntity();
			}
			elseif (is_array($last_elem->getValue()))
			{
				list($expand_entity, ) = $last_elem->getValue();
			}
			elseif ($last_elem->getValue() instanceof Base)
			{
				$expand_entity = $last_elem->getValue();
			}

			if ($expand_entity)
			{
				// add all fields of entity
				foreach ($expand_entity->getFields() as $exp_field)
				{
					if (!($exp_field instanceof ReferenceField))
					{
						if ($exp_field instanceof ExpressionField)
						{
							// we should have own copy of build_from_chains to set join aliases there
							// actually is copy&paste from getChainByDefinition
							// it would be correct to form DEFINITIONs here and call getChainByDefinition for each
							// better to skip expressions
							continue;
						}

						$exp_chain = clone $chain;
						$exp_chain->addElement(new QueryChainElement(
							$exp_field
						));

						// custom alias
						if (!empty($alias))
						{
							$exp_chain->setCustomAlias($alias.$exp_field->getName());
						}

						// add
						$this->registerChain('select', $exp_chain);

						if ($exp_field instanceof ExpressionField)
						{
							$this->collectExprChains($exp_chain, array('hidden', 'select_expr'));
						}
					}
				}
			}
			else
			{
				// scalar field that defined in entity
				$this->registerChain('select', $chain);

				// collect buildFrom fields (recursively)
				if ($chain->getLastElement()->getValue() instanceof ExpressionField)
				{
					$this->collectExprChains($chain, array('hidden', 'select_expr'));
				}
			}
		}

		return $this;
	}

	public function setFilterChains(array $filter, $section = 'filter')
	{
		foreach ($filter as $filter_def => $filter_match)
		{
			if ($filter_def === 'LOGIC')
			{
				continue;
			}

			if (!is_numeric($filter_def))
			{
				$csw_result = \CSQLWhere::makeOperation($filter_def);
				list($definition, ) = array_values($csw_result);

				$chain = $this->getRegisteredChain($definition, true);

				$this->registerChain($section, $chain, $definition);

				// fill hidden select
				if ($chain->getLastElement()->getValue() instanceof ExpressionField)
				{
					$this->collectExprChains($chain);
				}
			}

			if (is_array($filter_match))
			{
				$this->setFilterChains($filter_match, $section);
			}
		}
	}

	protected function divideFilter()
	{
		// divide filter to where and having

		$logic = isset($this->filter['LOGIC']) ? $this->filter['LOGIC'] : 'AND';

		if ($logic == 'OR')
		{
			// if has aggr then move all to having
			if ($this->checkFilterAggregation($this->filter))
			{
				$this->where = array();
				$this->where_chains = array();

				$this->having = $this->filter;
				$this->having_chains = $this->filter_chains;
			}
			else
			{
				$this->where = $this->filter;
				$this->where_chains = $this->filter_chains;

				$this->having = array();
				$this->having_chains = array();
			}
		}
		elseif ($logic == 'AND')
		{
			// we can separate root filters
			foreach ($this->filter as $k => $sub_filter)
			{
				if ($k === 'LOGIC')
				{
					$this->where[$k] = $sub_filter;
					$this->having[$k] = $sub_filter;

					continue;
				}

				$tmp_filter = array($k => $sub_filter);

				if ($this->checkFilterAggregation($tmp_filter))
				{
					$this->having[$k] = $sub_filter;
					$this->setFilterChains($tmp_filter, 'having');
				}
				else
				{
					$this->where[$k] = $sub_filter;
					$this->setFilterChains($tmp_filter, 'where');
				}
			}
		}

		// collect "build_from" fields from having
		foreach ($this->having_chains as $chain)
		{
			if ($chain->getLastElement()->getValue() instanceof ExpressionField)
			{
				$this->collectExprChains($chain, array('hidden', 'having_expr'));
			}
		}
	}

	protected function checkFilterAggregation($filter)
	{
		foreach ($filter as $filter_def => $filter_match)
		{
			if ($filter_def === 'LOGIC')
			{
				continue;
			}

			$is_having = false;
			if (!is_numeric($filter_def))
			{
				$csw_result = \CSQLWhere::makeOperation($filter_def);
				list($definition, ) = array_values($csw_result);

				$chain = $this->filter_chains[$definition];
				$last = $chain->getLastElement();

				$is_having = $last->getValue() instanceof ExpressionField && $last->getValue()->isAggregated();
			}
			elseif (is_array($filter_match))
			{
				$is_having = $this->checkFilterAggregation($filter_match);
			}

			if ($is_having)
			{
				return true;
			}
		}

		return false;
	}

	protected function addToGroupChain($definition)
	{
		$chain = $this->getRegisteredChain($definition, true);
		$this->registerChain('group', $chain);

		if ($chain->getLastElement()->getValue() instanceof ExpressionField)
		{
			$this->collectExprChains($chain);
		}
	}

	protected function addToOrderChain($definition)
	{
		$chain = $this->getRegisteredChain($definition, true);
		$this->registerChain('order', $chain);

		if ($chain->getLastElement()->getValue() instanceof ExpressionField)
		{
			$this->collectExprChains($chain);
		}
	}

	protected function buildJoinMap()
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();

		$aliasLength = $helper->getAliasLength();

		// list of used joins
		$done = array();

		$talias_count = 0;

		foreach ($this->global_chains as $chain)
		{
			if ($chain->getLastElement()->getParameter('talias'))
			{
				// already been here
				continue;
			}

			// in NO_DOUBLING mode skip 1:N relations that presented in filter only
			if (!$this->data_doubling && $chain->hasBackReference())
			{
				$alias = $chain->getAlias();

				if (isset($this->filter_chains[$alias])
					&& !isset($this->select_chains[$alias]) && !isset($this->select_expr_chains[$alias])
					&& !isset($this->group_chains[$alias]) && !isset($this->order_chains[$alias])
				)
				{
					continue;
				}
			}

			$prev_entity = $this->init_entity;
			$prev_alias = $this->getInitAlias(false);

			$map_key = '';

			/**
			 * elemenets after init entity
			 * @var $elements QueryChainElement[]
			 * */
			$elements = array_slice($chain->getAllElements(), 1);

			foreach ($elements as $element)
			{
				$table_alias = null;

				/**
				 * define main objects
				 * @var $src_entity Base
				 * @var $ref_field ReferenceField
				 * @var $dst_entity Base
				 */
				if ($element->getValue() instanceof ReferenceField)
				{
					// ref to another entity
					$src_entity = $prev_entity;
					$ref_field = $element->getValue();
					$dst_entity = $ref_field->getRefEntity();
				}
				elseif (is_array($element->getValue())
				)
				{
					// link from another entity to this
					$src_entity = $prev_entity;
					list($dst_entity, $ref_field) = $element->getValue();
				}
				else
				{
					// scalar field
					$element->setParameter('talias', $prev_alias.$this->table_alias_postfix);
					continue;
				}

				// mapping
				if (empty($map_key))
				{
					$map_key = $src_entity->getName();
				}

				$map_key .= '/' . $ref_field->getName() . '/' . $dst_entity->getName();

				if (isset($done[$map_key]))
				{
					// already connected
					$table_alias = $done[$map_key];
				}
				else
				{
					// prepare reference
					$reference = $ref_field->getReference();

					if ($element->getValue() instanceof ReferenceField)
					{
						// ref to another entity
						if (is_null($table_alias))
						{
							$table_alias = $prev_alias.'_'.strtolower($ref_field->getName());

							if (strlen($table_alias.$this->table_alias_postfix) > $aliasLength)
							{
								$table_alias = 'TALIAS'.$this->table_alias_postfix.'_' . (++$talias_count);
							}
						}

						$alias_this = $prev_alias;
						$alias_ref = $table_alias;
					}
					elseif (is_array($element->getValue()))
					{
						if (is_null($table_alias))
						{
							$table_alias = Base::camel2snake($dst_entity->getName()) . '_' . strtolower($ref_field->getName());
							$table_alias = $prev_alias.'_'.$table_alias;

							if (strlen($table_alias.$this->table_alias_postfix) > $aliasLength)
							{
								$table_alias = 'TALIAS'.$this->table_alias_postfix.'_' . (++$talias_count);
							}
						}

						$alias_this = $table_alias;
						$alias_ref = $prev_alias;

						if ($dst_entity->isUtm())
						{
							// add to $reference
							$reference = array(
								$reference,
								'=this.FIELD_ID' => array('?i', $element->getParameter('ufield')->getFieldId())
							);
						}
					}

					// replace this. and ref. to real definition -- not supported yet
					// instead it we set $alias_this and $alias_ref

					$csw_reference = $this->prepareJoinReference(
						$reference, $alias_this.$this->table_alias_postfix, $alias_ref.$this->table_alias_postfix
					);

					$join = array(
						'type' => $ref_field->getJoinType(),
						'table' => $dst_entity->getDBTableName(),
						'alias' => $table_alias.$this->table_alias_postfix,
						'reference' => $csw_reference
					);

					$this->join_map[] = $join;

					$done[$map_key] = $table_alias;
				}

				// set alias for each element
				$element->setParameter('talias', $table_alias.$this->table_alias_postfix);

				$prev_entity = $dst_entity;
				$prev_alias = $table_alias;
			}
		}
	}

	protected function buildSelect()
	{
		$sql = array();

		foreach ($this->select_chains as $chain)
		{
			$sql[] = $chain->getSqlDefinition(true);
		}

		if (empty($sql))
		{
			$sql[] = 1;
		}

		return "\t".join(",\n\t", $sql);
	}

	protected function buildJoin()
	{
		$sql = array();
		$csw = new \CSQLWhere;

		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();

		foreach ($this->join_map as $join)
		{
			// prepare csw fields
			$csw_fields = $this->getJoinCswFields($join['reference']);
			$csw->setFields($csw_fields);

			// sql
			$sql[] = sprintf('%s JOIN %s %s ON %s',
				$join['type'],
				$helper->quote($join['table']),
				$helper->quote($join['alias']),
				trim($csw->getQuery($join['reference']))
			);
		}

		return join("\n", $sql);
	}

	protected function buildWhere()
	{
		$csw = new \CSQLWhere;

		$csw_fields = $this->getFilterCswFields($this->where);
		$csw->setFields($csw_fields);

		$sql = trim($csw->getQuery($this->where));

		return $sql;
	}

	protected function buildGroup()
	{
		$sql = array();

		if (!empty($this->group_chains) || !empty($this->having_chains)
			|| $this->checkChainsAggregation($this->select_chains)
			|| $this->checkChainsAggregation($this->order_chains)
		)
		{
			// add non-aggr fields to group
			foreach ($this->global_chains as $chain)
			{
				$alias = $chain->getAlias();

				// skip constants
				if ($chain->isConstant())
				{
					continue;
				}

//				if (isset($this->select_chains[$alias]) || isset($this->select_expr_chains[$alias]) || isset($this->order_chains[$alias])
//					|| isset($this->having_chains[$alias]) || isset($this->having_expr_chains[$alias]))
				if (isset($this->select_chains[$alias]) || isset($this->order_chains[$alias]) || isset($this->having_chains[$alias]))
				{
					//if (!($chain->getLastElement()->getValue() instanceof ExpressionField) && !$chain->hasAggregation())
					// skip subqueries
					if (!$chain->hasAggregation() && !$chain->hasSubquery())
					{
						$this->registerChain('group', $chain);
					}
					// but include build_from of subqueries
					elseif (!$chain->hasAggregation() && $chain->hasSubquery() && $chain->getLastElement()->getValue() instanceof ExpressionField)
					{
						$sub_chains = $chain->getLastElement()->getValue()->getBuildFromChains();

						foreach ($sub_chains as $sub_chain)
						{
							// build real subchain starting from init entity
							$real_sub_chain = clone $chain;

							foreach (array_slice($sub_chain->getAllElements(), 1) as $sub_chain_elem)
							{
								$real_sub_chain->addElement($sub_chain_elem);
							}

							// add to query
							$this->registerChain('group', $this->global_chains[$real_sub_chain->getAlias()]);
						}
					}
				}
				elseif (isset($this->having_expr_chains[$alias]))
				{
					if (!$chain->hasAggregation() && $chain->hasSubquery())
					{
						$this->registerChain('group', $chain);
					}
				}
			}
		}

		foreach ($this->group_chains as $chain)
		{
			$sql[] = $chain->getSqlDefinition();
		}

		return join(', ', $sql);
	}

	protected function buildHaving()
	{
		$csw = new \CSQLWhere;

		$csw_fields = $this->getFilterCswFields($this->having);
		$csw->setFields($csw_fields);

		$sql = trim($csw->getQuery($this->having));

		return $sql;
	}

	protected function buildOrder()
	{
		$sql = array();

		foreach ($this->order_chains as $chain)
		{
			$sort = isset($this->order[$chain->getDefinition()])
				? $this->order[$chain->getDefinition()]
				: $this->order[$chain->getAlias()];

			$sql[] = $chain->getSqlDefinition() . ' ' . $sort;
		}

		return join(', ', $sql);
	}

	protected function buildQuery()
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();

		if ($this->query_build_parts === null)
		{

			foreach ($this->select as $key => $value)
			{
				$this->addToSelectChain($value, is_numeric($key) ? '' : $key);
			}

			$this->setFilterChains($this->filter);
			$this->divideFilter($this->filter);

			foreach ($this->group as $value)
			{
				$this->addToGroupChain($value);
			}

			foreach ($this->order as $key => $value)
			{
				$this->addToOrderChain($key);
			}

			$this->buildJoinMap();

			$sqlSelect = $this->buildSelect();
			$sqlJoin = $this->buildJoin();
			$sqlWhere = $this->buildWhere();
			$sqlGroup = $this->buildGroup();
			$sqlHaving = $this->buildHaving();
			$sqlOrder = $this->buildOrder();

			$sqlFrom = $this->init_entity->getDBTableName();

			if (strpos($sqlFrom, '(') === false)
			{
				// is not subquery
				$sqlFrom = $helper->quote($sqlFrom);
			}

			$sqlFrom .= ' '.$helper->quote($this->getInitAlias());
			$sqlFrom .= ' '.$sqlJoin;

			$this->query_build_parts = array_filter(array(
				'SELECT' => $sqlSelect,
				'FROM' => $sqlFrom,
				'WHERE' => $sqlWhere,
				'GROUP BY' => $sqlGroup,
				'HAVING' => $sqlHaving,
				'ORDER BY' => $sqlOrder
			));
		}

		$build_parts = $this->query_build_parts;

		foreach ($build_parts as $k => &$v)
		{
			$v = $k . ' ' . $v;
		}

		$query = join("\n", $build_parts);

		list($query, $replaced) = $this->replaceSelectAliases($query);
		$this->replaced_aliases = $replaced;

		if (!empty($this->options))
		{
			foreach ($this->options as $opt => $value)
			{
				$query = str_replace('%'.$opt.'%', $value, $query);
			}
		}

		if ($this->limit > 0)
		{
			$query = $helper->getTopSql($query, $this->limit, $this->offset);
		}

		return $query;
	}

	protected function getFilterCswFields(&$filter)
	{
		$fields = array();

		foreach ($filter as $filter_def => &$filter_match)
		{
			if ($filter_def === 'LOGIC')
			{
				continue;
			}

			if (!is_numeric($filter_def))
			{
				$csw_result = \CSQLWhere::makeOperation($filter_def);
				list($definition, ) = array_values($csw_result);

				$chain = $this->filter_chains[$definition];
				$last = $chain->getLastElement();

				$field_type = $last->getValue()->getDataType();

				// rewrite type & value for CSQLWhere
				if ($field_type == 'integer')
				{
					$field_type = 'int';
				}
				elseif ($field_type == 'boolean')
				{
					$field_type = 'string';

					/** @var BooleanField $field */
					$field = $last->getValue();
					$values = $field->getValues();

					if (is_numeric($values[0]) && is_numeric($values[1]))
					{
						$field_type = 'int';
					}

					if (is_scalar($filter_match))
					{
						$filter_match = $field->normalizeValue($filter_match);
					}
				}
				elseif ($field_type == 'float')
				{
					$field_type = 'double';
				}
				elseif ($field_type == 'enum' || $field_type == 'text')
				{
					$field_type = 'string';
				}

				//$is_having = $last->getValue() instanceof ExpressionField && $last->getValue()->isAggregated();

				// if back-reference found (Entity:REF)
				// if NO_DOUBLING mode enabled, then change getSQLDefinition to subquery exists(...)
				// and those chains should not be in joins if it is possible

				$callback = null;

				/*if (!$this->data_doubling && $chain->hasBackReference())
				{
					$field_type = 'callback';
					$init_query = $this;

					$callback = function ($field, $operation, $value) use ($init_query, $chain)
					{
						$init_entity = $init_query->getEntity();
						$init_table_alias = CBaseEntity::camel2snake($init_entity->getName()).$init_query->getTableAliasPostfix();

						$filter = array();

						// add primary linking with main query
						foreach ($init_entity->getPrimaryArray() as $primary)
						{
							$filter['='.$primary] = new CSQLWhereExpression('?#', $init_table_alias.'.'.$primary);
						}

						// add value filter
						$filter[CSQLWhere::getOperationByCode($operation).$chain->getDefinition()] = $value;

						// build subquery
						$query_class = __CLASS__;
						$sub_query = new $query_class($init_entity);
						$sub_query->setFilter($filter);
						$sub_query->setTableAliasPostfix('_sub');

						return 'EXISTS(' . $sub_query->getQuery() . ')';
					};
				}*/

				$fields[$definition] = array(
					'TABLE_ALIAS' => 'table',
					'FIELD_NAME' => $chain->getSqlDefinition(),
					'FIELD_TYPE' => $field_type,
					'MULTIPLE' => '',
					'JOIN' => '',
					'CALLBACK' => $callback
				);
			}

			if (is_array($filter_match))
			{
				$fields = array_merge($fields, $this->getFilterCswFields($filter_match));
			}
		}

		return $fields;
	}

	protected function prepareJoinReference($reference, $alias_this, $alias_ref)
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();
		$leftQuote = $helper->getLeftQuote();
		$rightQuote = $helper->getRightQuote();

		$new = array();

		foreach ($reference as $k => $v)
		{
			if ($k === 'LOGIC')
			{
				$new[$k] = $v;
				continue;
			}

			if (is_numeric($k))
			{
				// subfilter, recursive call
				$new[$k] = $this->prepareJoinReference($v, $alias_this, $alias_ref);
			}
			else
			{
				// key
				$csw_result = \CSQLWhere::makeOperation($k);
				list($field, ) = array_values($csw_result);

				if (strpos($field, 'this.') === 0)
				{
					$k = str_replace('this.', $helper->quote($alias_this).'.'.$leftQuote, $k);
					$k .= $rightQuote;
				}
				elseif (strpos($field, 'ref.') === 0)
				{
					$k = str_replace('ref.', $helper->quote($alias_ref).'.'.$leftQuote, $k);
					$k .= $rightQuote;
				}
				else
				{
					throw new Main\SystemException();
				}

				// value
				if (is_array($v))
				{
					// field = expression
					$v = new \CSQLWhereExpression($v[0], array_slice($v, 1));
				}
				else
				{
					// field = field
					if (strpos($v, 'this.') === 0)
					{
						$field_def = str_replace('this.', $alias_this.'.', $v);
					}
					elseif (strpos($v, 'ref.') === 0)
					{
						$field_def = str_replace('ref.', $alias_ref.'.', $v);
					}

					$v = new \CSQLWhereExpression('?#', $field_def);
				}

				$new[$k] = $v;
			}
		}

		return $new;
	}

	protected function getJoinCswFields($reference)
	{
		$fields = array();

		foreach ($reference as $k => $v)
		{
			if ($k === 'LOGIC')
			{
				continue;
			}

			if (is_numeric($k))
			{
				$fields = array_merge($fields, $this->getJoinCswFields($v));
			}
			else
			{
				// key
				$csw_result = \CSQLWhere::makeOperation($k);
				list($field, ) = array_values($csw_result);

				$fields[$field] = array(
					'TABLE_ALIAS' => 'alias',
					'FIELD_NAME' => $field,
					'FIELD_TYPE' => 'string',
					'MULTIPLE' => '',
					'JOIN' => ''
				);

				// no need to add values as csw fields
			}
		}

		return $fields;
	}

	protected function checkChainsAggregation($chain)
	{
		/** @var QueryChain[] $chains */
		$chains = is_array($chain) ? $chain : array($chain);

		foreach ($chains as $chain)
		{
			$last = $chain->getLastElement();
			$is_aggr = $last->getValue() instanceof ExpressionField && $last->getValue()->isAggregated();

			if ($is_aggr)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * The most magic method. Do not edit without strong need, and for sure run tests after.
	 *
	 * @param QueryChain $chain
	 * @param array      $storages
	 */
	protected function collectExprChains(QueryChain $chain, $storages = array('hidden'))
	{
		$last_elem = $chain->getLastElement();
		$bf_chains = $last_elem->getValue()->getBuildFromChains();

		$pre_chain = clone $chain;
		//$pre_chain->removeLastElement();

		foreach ($bf_chains as $bf_chain)
		{
			// collect hidden chain
			$tmp_chain = clone $pre_chain;

			// exclude init entity
			$bf_elements = array_slice($bf_chain->getAllElements(), 1);

			// add elements
			foreach ($bf_elements as $bf_element)
			{
				$tmp_chain->addElement($bf_element);
			}

			//if (!($bf_chain->getLastElement()->getValue() instanceof ExpressionField))
			{
				foreach ($storages as $storage)
				{
					$reg_chain = $this->registerChain($storage, $tmp_chain);
				}

				// replace "build_from" chain end by registered chain end
				// actually it's better and more correctly to replace the whole chain
				$bf_chain->removeLastElement();
				$bf_chain->addElement($reg_chain->getLastElement());
			}

			// check elements to recursive collect hidden chains
			foreach ($bf_elements as $bf_element)
			{
				if ($bf_element->getValue() instanceof ExpressionField)
				{
					$this->collectExprChains($tmp_chain);
				}
			}
		}
	}

	public function registerChain($section, QueryChain $chain, $opt_key = null)
	{
		$alias = $chain->getAlias();

		if (isset($this->global_chains[$alias]))
		{
			$reg_chain = $this->global_chains[$alias];
		}
		else
		{
			$reg_chain = $chain;
			$def = $reg_chain->getDefinition();

			$this->global_chains[$alias] = $chain;
			$this->global_chains[$def] = $chain;
		}

		$storage_name = $section . '_chains';
		$this->{$storage_name}[$alias] = $reg_chain;

		if (!is_null($opt_key))
		{
			$this->{$storage_name}[$opt_key] = $reg_chain;
		}

		// get default options from expressions
		if ($chain->getLastElement()->getValue() instanceof ExpressionField)
		{
			$options = $chain->getLastElement()->getValue()->getOptions();

			if (!empty($options))
			{
				foreach ($options as $option => $value)
				{
					if (!isset($this->options[$option]))
					{
						$this->options[$option] = $value;
					}
				}
			}
		}

		return $reg_chain;
	}

	public function getRegisteredChain($key, $force_create = false)
	{
		if (isset($this->global_chains[$key]))
		{
			return $this->global_chains[$key];
		}

		if ($force_create)
		{
			$chain = QueryChain::getChainByDefinition($this->init_entity, $key);
			$this->registerChain('global', $chain);

			return $chain;
		}

		return false;
	}

	protected function query($query)
	{
		// check nosql configuration
		$configuration = $this->init_entity->getConnection()->getConfiguration();

		if (isset($configuration['handlersocket']['read']))
		{
			$nosqlConnectionName = $configuration['handlersocket']['read'];

			$nosqlConnection = \Bitrix\Main\Application::getInstance()->getConnectionPool()->getConnection($nosqlConnectionName);
			$isNosqlCapable = NosqlPrimarySelector::checkQuery($nosqlConnection, $this);

			if ($isNosqlCapable)
			{
				$nosqlResult = NosqlPrimarySelector::relayQuery($nosqlConnection, $this);

				return new \Bitrix\Main\DB\ArrayResult($nosqlResult);
			}
		}

/*
Vadim: this is for paging but currently is not used
		if ($this->count_total || !is_null($this->offset))
		{
			$cnt_body_elements = $build_parts;

			// remove order
			unset($cnt_body_elements['ORDER BY']);

			$cnt_query = join("\n", $cnt_body_elements);

			// remove long aliases
			list($cnt_query, ) = $this->replaceSelectAliases($cnt_query);

			// select count
			$cnt_query = 'SELECT COUNT(1) AS TMP_ROWS_CNT FROM ('.$cnt_query.') xxx';
			$cnt = $connection->queryScalar($cnt_query);
		}
*/
		$connection = \Bitrix\Main\Application::getConnection();

		$result = $connection->query($query);
		$result->setReplacedAliases($this->replaced_aliases);

		$this->last_query = $query;

		return $result;
	}

	protected function replaceSelectAliases($query)
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();

		$length = (int) $helper->getAliasLength();
		$leftQuote = $helper->getLeftQuote();
		$rightQuote = $helper->getRightQuote();

		$replaced = array();

		preg_match_all(
			'/ AS '.preg_quote($leftQuote).'([a-z0-9_]{'.($length+1).',})'.preg_quote($rightQuote).'/i',
			$query, $matches
		);

		if (!empty($matches[1]))
		{
			foreach ($matches[1] as $alias)
			{
				$newAlias = 'FALIAS_'.count($replaced);
				$replaced[$newAlias] = $alias;

				$query = str_replace(
					' AS ' . $helper->quote($alias),
					' AS ' . $helper->quote($newAlias) . '/* '.$alias.' */',
					$query
				);
			}
		}

		return array($query, $replaced);
	}

	/**
	 * @return array|QueryChain[]
	 */
	public function getChains()
	{
		return $this->global_chains;
	}

	/**
	 * @return array|QueryChain[]
	 */
	public function getGroupChains()
	{
		return $this->group_chains;
	}

	/**
	 * @return array
	 */
	public function getHiddenChains()
	{
		return $this->hidden_chains;
	}

	/**
	 * @return array|QueryChain[]
	 */
	public function getHavingChains()
	{
		return $this->having_chains;
	}

	/**
	 * @return array|QueryChain[]
	 */
	public function getFilterChains()
	{
		return $this->filter_chains;
	}

	/**
	 * @return array|QueryChain[]
	 */
	public function getOrderChains()
	{
		return $this->order_chains;
	}

	/**
	 * @return array|QueryChain[]
	 */
	public function getSelectChains()
	{
		return $this->select_chains;
	}

	/**
	 * @return array|QueryChain[]
	 */
	public function getWhereChains()
	{
		return $this->where_chains;
	}

	public function getJoinMap()
	{
		return $this->join_map;
	}

	/**
	 * Builds and returns SQL query string
	 *
	 * @return string
	 */
	public function getQuery()
	{
		return $this->buildQuery();
	}

	/**
	 * Returns last executed query string
	 *
	 * @return string
	 */
	public function getLastQuery()
	{
		return $this->last_query;
	}

	public function getEntity()
	{
		return $this->init_entity;
	}

	/**
	 * @param bool $withPrefix
	 * @return string
	 */
	public function getInitAlias($withPrefix = true)
	{
		$init_alias = strtolower($this->init_entity->getCode());

		if ($withPrefix)
		{
			$init_alias .= $this->table_alias_postfix;
		}

		$connection = \Bitrix\Main\Application::getConnection();
		$aliasLength = $connection->getSqlHelper()->getAliasLength();

		if (strlen($init_alias) > $aliasLength)
		{
			$init_alias = 'base';
		}

		return $init_alias;
	}

	public function getReplacedAliases()
	{
		return $this->replaced_aliases;
	}

	public function dump()
	{
		echo '<pre>';

		echo 'last query: ';
		var_dump($this->last_query);
		echo PHP_EOL;

		echo 'size of select_chains: '.count($this->select_chains);
		echo PHP_EOL;
		foreach ($this->select_chains as $num => $chain)
		{
			echo '  chain ['.$num.'] has '.$chain->getSize().' elements: '.PHP_EOL;
			$chain->dump();
			echo PHP_EOL;
		}

		echo PHP_EOL.PHP_EOL;

		echo 'size of where_chains: '.count($this->where_chains);
		echo PHP_EOL;
		foreach ($this->where_chains as $num => $chain)
		{
			echo '  chain ['.$num.'] has '.$chain->getSize().' elements: '.PHP_EOL;
			$chain->dump();
			echo PHP_EOL;
		}

		echo PHP_EOL.PHP_EOL;

		echo 'size of group_chains: '.count($this->group_chains);
		echo PHP_EOL;
		foreach ($this->group_chains as $num => $chain)
		{
			echo '  chain ['.$num.'] has '.$chain->getSize().' elements: '.PHP_EOL;
			$chain->dump();
			echo PHP_EOL;
		}

		echo PHP_EOL.PHP_EOL;

		echo 'size of having_chains: '.count($this->having_chains);
		echo PHP_EOL;
		foreach ($this->having_chains as $num => $chain)
		{
			echo '  chain ['.$num.'] has '.$chain->getSize().' elements: '.PHP_EOL;
			$chain->dump();
			echo PHP_EOL;
		}

		echo PHP_EOL.PHP_EOL;

		echo 'size of filter_chains: '.count($this->filter_chains);
		echo PHP_EOL;
		foreach ($this->filter_chains as $num => $chain)
		{
			echo '  chain ['.$num.'] has '.$chain->getSize().' elements: '.PHP_EOL;
			$chain->dump();
			echo PHP_EOL;
		}

		echo PHP_EOL.PHP_EOL;

		echo 'size of select_expr_chains: '.count($this->select_expr_chains);
		echo PHP_EOL;
		foreach ($this->select_expr_chains as $num => $chain)
		{
			echo '  chain ['.$num.'] has '.$chain->getSize().' elements: '.PHP_EOL;
			$chain->dump();
			echo PHP_EOL;
		}

		echo PHP_EOL.PHP_EOL;

		echo 'size of hidden_chains: '.count($this->hidden_chains);
		echo PHP_EOL;
		foreach ($this->hidden_chains as $num => $chain)
		{
			echo '  chain ['.$num.'] has '.$chain->getSize().' elements: '.PHP_EOL;
			$chain->dump();
			echo PHP_EOL;
		}

		echo PHP_EOL.PHP_EOL;

		echo 'size of global_chains: '.count($this->global_chains);
		echo PHP_EOL;
		foreach ($this->global_chains as $num => $chain)
		{
			echo '  chain ['.$num.'] has '.$chain->getSize().' elements: '.PHP_EOL;
			$chain->dump();
			echo PHP_EOL;
		}

		echo PHP_EOL.PHP_EOL;

		var_dump($this->join_map);

		echo '</pre>';
	}
}
