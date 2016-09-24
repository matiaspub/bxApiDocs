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

	/**
	 * @var QueryChain[]
	 */
	protected
		$select_expr_chains = array(), // from select expr "build_from"
		$having_expr_chains = array(), // from having expr "build_from"
		$hidden_chains = array(); // all expr "build_from" elements;

	protected
		$runtime_chains;

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
	protected $data_doubling_off = false;

	protected $table_alias_postfix = '';

	protected
		$join_map = array();

	/** @var array list of used joins */
	protected $join_registry;

	protected
		$is_executing = false;

	/** @var string Last executed SQL query */
	protected static $last_query;

	/** @var array Replaced field aliases */
	protected $replaced_aliases;

	/** @var array Replaced table aliases */
	protected $replaced_taliases;

	/** @var callable[] */
	protected $selectFetchModifiers = array();

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
			$this->init_entity = clone $source;
		}
		elseif (is_string($source))
		{
			$this->init_entity = clone Base::getInstance($source);
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
	
	/**
	* <p>Нестатический метод возвращает массив полей для оператора SELECT.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/query/getselect.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод составляет список полей для оператора SELECT.</p>
	*
	*
	* @param array $select  
	*
	* @return \Bitrix\Main\Entity\Query 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/query/setselect.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод создаёт поле для оператора SELECT.</p>
	*
	*
	* @param mixed $definition  Поле
	*
	* @param string $alias = '' Алиас поля по аналогии как задается в SQL.
	*
	* @return \Bitrix\Main\Entity\Query 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/query/addselect.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает массив полей оператора WHERE.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/query/getfilter.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод устанавливает список фильтров для оператора WHERE.</p>
	*
	*
	* @param array $filter  
	*
	* @return \Bitrix\Main\Entity\Query 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/query/setfilter.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод добавляет фильтр оператору WHERE.</p>
	*
	*
	* @param string $key  
	*
	* @param mixed $value  
	*
	* @return \Bitrix\Main\Entity\Query 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/query/addfilter.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает массив полей оператора GROUP BY.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/query/getgroup.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод устанавливает список полей для оператора GROUP BY.</p>
	*
	*
	* @param mixed $group  
	*
	* @return \Bitrix\Main\Entity\Query 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/query/setgroup.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод добавляет поле в список полей оператора GROUP BY.</p>
	*
	*
	* @param mixed $group  
	*
	* @return \Bitrix\Main\Entity\Query 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/query/addgroup.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает массив полей оператора ORDER BY.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/query/getorder.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод устанавливает список полей для оператора ORDER BY.</p>
	*
	*
	* @param mixed $order  
	*
	* @return \Bitrix\Main\Entity\Query 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/query/setorder.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод добавляет поле в список полей оператора ORDER BY.</p>
	*
	*
	* @param string $definition  
	*
	* @param string $order = 'ASC' 
	*
	* @return \Bitrix\Main\Entity\Query 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/query/addorder.php
	* @author Bitrix
	*/
	public function addOrder($definition, $order = 'ASC')
	{
		$order = strtoupper($order);

		if (!in_array($order, array('ASC', 'DESC'), true))
		{
			throw new Main\ArgumentException(sprintf('Invalid order "%s"', $order));
		}

		$connection = $this->init_entity->getConnection();
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
	
	/**
	* <p>Нестатический метод возвращает лимит.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/query/getlimit.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод устанавливает лимит для оператора LIMIT n.</p>
	*
	*
	* @param integer $limit  
	*
	* @return \Bitrix\Main\Entity\Query 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/query/setlimit.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает смещение.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/query/getoffset.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод устанавливает смещение оператора LIMIT n, m.</p>
	*
	*
	* @param integer $offset  
	*
	* @return \Bitrix\Main\Entity\Query 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/query/setoffset.php
	* @author Bitrix
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
	 * @see disableDataDoubling
	 *
	 * @return void
	 */
	public function enableDataDoubling()
	{
		$this->data_doubling_off = false;
	}

	/**
	 * Replaces all 1:N relations in filter to ID IN (subquery SELECT ID FROM <1:N relation>)
	 * Available for Entities with 1 primary field only
	 *
	 * @return bool
	 */
	
	/**
	* <p>Нестатический метод заменяет все связи 1:N в фильтре к ID IN (подзапрос SELECT ID FROM ).  Доступно для объектов только с 1 первичным полем.</p> <p class="note">MSSQL <a href="http://connect.microsoft.com/SQLServer/feedback/details/299231/add-support-for-ansi-standard-row-value-constructors" >не поддерживает</a> конструкции <code>WHERE (col1, col2) IN (SELECT col1, col2 FROM SomeOtherTable)</code>.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/query/disabledatadoubling.php
	* @author Bitrix
	*/
	public function disableDataDoubling()
	{
		if (count($this->init_entity->getPrimaryArray()) !== 1)
		{
			// mssql doesn't support constructions WHERE (col1, col2) IN (SELECT col1, col2 FROM SomeOtherTable)
			/* @see http://connect.microsoft.com/SQLServer/feedback/details/299231/add-support-for-ansi-standard-row-value-constructors */
			trigger_error(sprintf(
				'Disabling data doubling available for Entities with 1 primary field only. Number of primaries of your entity `%s` is %d.',
				$this->init_entity->getFullName(), count($this->init_entity->getPrimaryArray())
			), E_USER_WARNING);

			return false;
		}

		$this->data_doubling_off = true;

		return true;
	}

	/**
	 * Adds a runtime field (being created dinamycally, opposite to being described statically in the entity map)
	 *
	 * @param string|null $name
	 * @param array|Field $fieldInfo
	 *
	 * @return Query
	 */
	
	/**
	* <p>Нестатический метод добавляет поле во время выполнения запроса. Создаётся динамически, в противном случае описывается статически в карте.</p>
	*
	*
	* @param mixed $string  
	*
	* @param null $name  
	*
	* @param null $array  
	*
	* @param arra $Bitrix  
	*
	* @param Bitri $Main  
	*
	* @param Mai $Entity  
	*
	* @param Field $fieldInfo  
	*
	* @return \Bitrix\Main\Entity\Query 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/query/registerruntimefield.php
	* @author Bitrix
	*/
	public function registerRuntimeField($name, $fieldInfo)
	{
		if ((empty($name) || is_numeric($name)) && $fieldInfo instanceof Field)
		{
			$name = $fieldInfo->getName();
		}

		$this->init_entity->addField($fieldInfo, $name);

		// force chain creation for further needs
		$chain = $this->getRegisteredChain($name, true);
		$this->registerChain('runtime', $chain);

		if ($chain->getLastElement()->getValue() instanceof ExpressionField)
		{
			$this->collectExprChains($chain, array('hidden'));
		}

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
	
	/**
	* <p>Нестатический метод выстраивает и выполняет запрос и возвращает результат.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Main\DB\Result 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/query/exec.php
	* @author Bitrix
	*/
	public function exec()
	{
		$this->is_executing = true;

		$query = $this->buildQuery();

		$result = $this->query($query);

		$this->is_executing = false;

		return $result;
	}

	protected function addToSelectChain($definition, $alias = null)
	{
		if ($definition instanceof ExpressionField)
		{
			if (empty($alias))
			{
				$alias = $definition->getName();
			}

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
		elseif (is_array($definition))
		{
			// it is runtime field
			// now they are @deprecated in here
			throw new Main\ArgumentException(
				'Expression as an array in `select` section is no more supported due to security reason.'
				.' Please use `runtime` parameter, or Query->registerRuntimeField method, or pass ExpressionField object instead of array.'
			);
		}
		else
		{
			// there is normal scalar field, or Reference, or Entity (all fields of)
			$chain = $this->getRegisteredChain($definition, true);

			if ($alias !== null)
			{
				// custom alias
				$chain = clone $chain;
				$chain->setCustomAlias($alias);
			}

			$last_elem = $chain->getLastElement();

			// fill if element is not scalar
			/** @var null|Base $expand_entity */
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

			if (!$expand_entity && $alias !== null)
			{
				// we have a single field, lets cheks its custom alias
				if (
					$this->init_entity->hasField($alias)
					&& (
						// if it's not the same field
						$this->init_entity->getFullName() !== $last_elem->getValue()->getEntity()->getFullName()
						||
						$last_elem->getValue()->getName() !== $alias
					)
				)
				{
					// deny aliases eq. existing fields
					throw new Main\ArgumentException(sprintf(
						'Alias "%s" matches already existing field "%s" of initial entity "%s". '.
						'Please choose another name for alias.',
						$alias, $alias, $this->init_entity->getFullName()
					));
				}
			}

			if ($expand_entity)
			{
				// add all fields of entity
				foreach ($expand_entity->getFields() as $exp_field)
				{
					// except for references and expressions
					if ($exp_field instanceof ScalarField)
					{
						$exp_chain = clone $chain;
						$exp_chain->addElement(new QueryChainElement(
							$exp_field
						));

						// custom alias
						if ($alias !== null)
						{
							$fieldAlias = $alias . $exp_field->getName();

							// deny aliases eq. existing fields
							if ($this->init_entity->hasField($fieldAlias))
							{
								throw new Main\ArgumentException(sprintf(
									'Alias "%s" + field "%s" match already existing field "%s" of initial entity "%s". '.
									'Please choose another name for alias.',
									$alias, $exp_field->getName(), $fieldAlias, $this->init_entity->getFullName()
								));
							}

							$exp_chain->setCustomAlias($fieldAlias);
						}

						// add
						$this->registerChain('select', $exp_chain);
					}
				}
			}
			else
			{
				// scalar field that defined in entity
				$this->registerChain('select', $chain);

				// it would be nice here to register field as a runtime when it has custom alias
				// it will make possible to use aliased fields as a native init entity fields
				// e.g. in expressions or in data_doubling=off filter

				// collect buildFrom fields (recursively)
				if ($chain->getLastElement()->getValue() instanceof ExpressionField)
				{
					$this->collectExprChains($chain, array('hidden', 'select_expr'));
				}
			}
		}

		return $this;
	}

	public function setFilterChains(&$filter, $section = 'filter')
	{
		foreach ($filter as $filter_def => &$filter_match)
		{
			if ($filter_def === 'LOGIC')
			{
				continue;
			}

			if (!is_numeric($filter_def))
			{
				$sqlWhere = new \CSQLWhere();
				$csw_result = $sqlWhere->makeOperation($filter_def);
				list($definition, ) = array_values($csw_result);

				// do not register it in global chain registry - get it in a smuggled way
				// - we will do the registration later after UF rewriting and data doubling checking
				$chain = $this->getRegisteredChain($definition);

				if (!$chain)
				{
					// try to find it in filter chains if it is 2nd call of method (when dividing filter for where/having)
					// and chain is still not registered in global (e.g. when forcesDataDoublingOff)
					if (isset($this->filter_chains[$definition]))
					{
						$chain = $this->filter_chains[$definition];
					}
					else
					{
						$chain = QueryChain::getChainByDefinition($this->init_entity, $definition);
					}
				}

				// dirty hack for UF multiple fields: replace text UF_SMTH by UF_SMTH_SINGLE
				$dstField = $chain->getLastElement()->getValue();
				$dstEntity = $dstField->getEntity();

				if ($dstField instanceof ExpressionField && count($dstField->getBuildFromChains()) == 1)
				{
					// hold entity, but get real closing field
					$dstBuildFromChains = $dstField->getBuildFromChains();

					/** @var QueryChain $firstChain */
					$firstChain = $dstBuildFromChains[0];
					$dstField = $firstChain->getLastElement()->getValue();
				}

				// check for base linking
				if ($dstField instanceof TextField && $dstEntity->hasField($dstField->getName().'_SINGLE'))
				{
					$utmLinkField = $dstEntity->getField($dstField->getName().'_SINGLE');

					if ($utmLinkField instanceof ExpressionField)
					{
						$buildFromChains = $utmLinkField->getBuildFromChains();

						// check for back-reference
						if (count($buildFromChains) == 1 && $buildFromChains[0]->hasBackReference())
						{
							$endField = $buildFromChains[0]->getLastElement()->getValue();

							// and final check for entity name
							if (strpos($endField->getEntity()->getName(), 'Utm'))
							{
								$expressionChain = clone $chain;
								$expressionChain->removeLastElement();
								$expressionChain->addElement(new QueryChainElement(clone $utmLinkField));
								$expressionChain->forceDataDoublingOff();

								$chain = $expressionChain;

								// rewrite filter definition
								unset($filter[$filter_def]);
								$filter[$filter_def.'_SINGLE'] = $filter_match;
								$definition .= '_SINGLE';
							}
						}
					}
				}

				// continue
				$registerChain = true;

				// if data doubling disabled and it is back-reference - do not register, it will be overwritten
				if ($chain->forcesDataDoublingOff() || ($this->data_doubling_off && $chain->hasBackReference()))
				{
					$registerChain = false;
				}

				if ($registerChain)
				{
					$this->registerChain($section, $chain, $definition);

					// fill hidden select
					if ($chain->getLastElement()->getValue() instanceof ExpressionField)
					{
						$this->collectExprChains($chain);
					}
				}
				else
				{
					// hide from global registry to avoid "join table"
					// but we still need it in filter chains
					$this->filter_chains[$chain->getAlias()] = $chain;
					$this->filter_chains[$definition] = $chain;

					// and we will need primary chain in filter later when overwriting data-doubling
					$this->getRegisteredChain($this->init_entity->getPrimary(), true);
				}
			}
			elseif (is_array($filter_match))
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
				$sqlWhere = new \CSQLWhere();
				$csw_result = $sqlWhere->makeOperation($filter_def);
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

	protected function buildJoinMap($chains = null)
	{
		$connection = $this->init_entity->getConnection();
		$helper = $connection->getSqlHelper();

		$aliasLength = $helper->getAliasLength();

		if (empty($chains))
		{
			$chains = $this->global_chains;
		}

		foreach ($chains as $chain)
		{
			if ($chain->getLastElement()->getParameter('talias'))
			{
				// already been here
				continue;
			}

			// in NO_DOUBLING mode skip 1:N relations that presented in filter only
			if ($chain->forcesDataDoublingOff() || ($this->data_doubling_off && $chain->hasBackReference()))
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

			$prev_alias = $this->getInitAlias(false);

			$map_key = '';

			/**
			 * elemenets after init entity
			 * @var $elements QueryChainElement[]
			 * */
			$elements = array_slice($chain->getAllElements(), 1);

			$currentDedinition = array();

			foreach ($elements as $element)
			{
				$table_alias = null;

				/**
				 * define main objects
				 * @var $ref_field ReferenceField
				 * @var $dst_entity Base
				 */
				if ($element->getValue() instanceof ReferenceField)
				{
					// ref to another entity
					$ref_field = $element->getValue();
					$dst_entity = $ref_field->getRefEntity();
				}
				elseif (is_array($element->getValue())
				)
				{
					// link from another entity to this
					list($dst_entity, $ref_field) = $element->getValue();
				}
				else
				{
					// scalar field
					// if it's a field of the init entity, use getInitAlias to use 'base' alias
					if ($prev_alias === $this->getInitAlias(false))
					{
						$element->setParameter('talias', $this->getInitAlias());
					}
					else
					{
						$element->setParameter('talias', $prev_alias.$this->table_alias_postfix);
					}

					continue;
				}

				// mapping
				if (empty($map_key))
				{
					$map_key = join('.', $currentDedinition);
				}

				$map_key .= '/' . $ref_field->getName() . '/' . $dst_entity->getName();

				$currentDedinition[] = $element->getDefinitionFragment();

				if (isset($this->join_registry[$map_key]))
				{
					// already connected
					$table_alias = $this->join_registry[$map_key];
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
								$old_table_alias = $table_alias;
								$table_alias = 'TALIAS_' . (count($this->replaced_taliases) + 1);
								$this->replaced_taliases[$table_alias] = $old_table_alias;
							}
						}

						$alias_this = $prev_alias;
						$alias_ref = $table_alias;

						$isBackReference = false;

						$definition_this = join('.', array_slice($currentDedinition, 0, -1));
						$definition_ref = join('.', $currentDedinition);
					}
					elseif (is_array($element->getValue()))
					{
						if (is_null($table_alias))
						{
							$table_alias = Base::camel2snake($dst_entity->getName()) . '_' . strtolower($ref_field->getName());
							$table_alias = $prev_alias.'_'.$table_alias;

							if (strlen($table_alias.$this->table_alias_postfix) > $aliasLength)
							{
								$old_table_alias = $table_alias;
								$table_alias = 'TALIAS_' . (count($this->replaced_taliases) + 1);
								$this->replaced_taliases[$table_alias] = $old_table_alias;
							}
						}

						$alias_this = $table_alias;
						$alias_ref = $prev_alias;

						$isBackReference = true;

						$definition_this = join('.', $currentDedinition);
						$definition_ref = join('.', array_slice($currentDedinition, 0, -1));
					}
					else
					{
						throw new Main\SystemException(sprintf('Unknown reference element `%s`', $element->getValue()));
					}

					// replace this. and ref. to real definition
					$csw_reference = $this->prepareJoinReference(
						$reference,
						$alias_this.$this->table_alias_postfix,
						$alias_ref.$this->table_alias_postfix,
						$definition_this,
						$definition_ref,
						$isBackReference
					);

					// double check after recursive call in prepareJoinReference
					if (!isset($this->join_registry[$map_key]))
					{
						$join = array(
							'type' => $ref_field->getJoinType(),
							'table' => $dst_entity->getDBTableName(),
							'alias' => $table_alias.$this->table_alias_postfix,
							'reference' => $csw_reference,
							'map_key' => $map_key
						);

						$this->join_map[] = $join;
						$this->join_registry[$map_key] = $table_alias;
					}
				}

				// set alias for each element
				$element->setParameter('talias', $table_alias.$this->table_alias_postfix);

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

		return "\n\t".join(",\n\t", $sql);
	}

	protected function buildJoin()
	{
		$sql = array();
		$csw = new \CSQLWhere;

		$connection = $this->init_entity->getConnection();
		$helper = $connection->getSqlHelper();

		foreach ($this->join_map as $join)
		{
			// prepare csw fields
			$csw_fields = $this->getJoinCswFields($join['reference']);
			$csw->setFields($csw_fields);

			// sql
			$sql[] = sprintf('%s JOIN %s %s ON %s',
				$join['type'],
				$this->quoteTableSource($join['table']),
				$helper->quote($join['alias']),
				trim($csw->getQuery($join['reference']))
			);
		}

		return "\n".join("\n", $sql);
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

				if (isset($this->select_chains[$alias]) || isset($this->order_chains[$alias]) || isset($this->having_chains[$alias]))
				{
					if (isset($this->group_chains[$alias]))
					{
						// skip already groupped
						continue;
					}
					elseif (!$chain->hasAggregation() && !$chain->hasSubquery())
					{
						// skip subqueries and already aggregated
						$this->registerChain('group', $chain);
					}
					elseif (!$chain->hasAggregation() && $chain->hasSubquery() && $chain->getLastElement()->getValue() instanceof ExpressionField)
					{
						// but include build_from of subqueries
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
			$connection = $this->init_entity->getConnection();
			$sqlDefinition = $chain->getSqlDefinition();
			$valueField = $chain->getLastElement()->getValue();

			if ($valueField instanceof ExpressionField)
			{
				$valueField = $valueField->getValueField();
			}

			if (($connection instanceof Main\DB\OracleConnection || $connection instanceof Main\DB\MssqlConnection)
				&& $valueField instanceof TextField)
			{
				// softTextCast
				$sqlDefinition = $connection->getSqlHelper()->softCastTextToChar($sqlDefinition);
			}

			$sql[] = $sqlDefinition;
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

			$connection = $this->init_entity->getConnection();
			$sqlDefinition = $chain->getSqlDefinition();
			$valueField = $chain->getLastElement()->getValue();

			if ($valueField instanceof ExpressionField)
			{
				$valueField = $valueField->getValueField();
			}

			if (($connection instanceof Main\DB\OracleConnection || $connection instanceof Main\DB\MssqlConnection)
				&& $valueField instanceof TextField)
			{
				// softTextCast
				$sqlDefinition = $connection->getSqlHelper()->softCastTextToChar($sqlDefinition);
			}

			$sql[] = $sqlDefinition. ' ' . $sort;
		}

		return join(', ', $sql);
	}

	protected function buildQuery()
	{
		$connection = $this->init_entity->getConnection();
		$helper = $connection->getSqlHelper();

		if ($this->query_build_parts === null)
		{

			foreach ($this->select as $key => $value)
			{
				$this->addToSelectChain($value, is_numeric($key) ? null : $key);
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

			$sqlFrom = $this->quoteTableSource($this->init_entity->getDBTableName());

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
				$sqlWhere = new \CSQLWhere();
				$csw_result = $sqlWhere->makeOperation($filter_def);
				list($definition, $operation) = array_values($csw_result);

				$chain = $this->filter_chains[$definition];
				$last = $chain->getLastElement();

				// need to create an alternative of CSQLWhere in D7.Entity
				$field_type = $last->getValue()->getDataType();
				$callback = null;

				// rewrite type & value for CSQLWhere
				if ($field_type == 'integer')
				{
					$field_type = 'int';
				}
				elseif ($field_type == 'boolean')
				{

					if ($operation == 'SE')
					{
						// just boolean expression, without operator
						// e.g. WHERE EXISTS(...)
						$field_type = 'callback';
						$callback = array($this, 'booleanStrongEqualityCallback');
					}
					else
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
				}
				elseif ($field_type == 'float')
				{
					$field_type = 'double';
				}
				elseif ($field_type == 'enum' || $field_type == 'text')
				{
					$field_type = 'string';
				}

				$sqlDefinition = $chain->getSqlDefinition();

				// data-doubling-off mode
				/** @see disableDataDoubling */
				if ($chain->forcesDataDoublingOff() || ($this->data_doubling_off && $chain->hasBackReference()))
				{
					$primaryName = $this->init_entity->getPrimary();
					$uniquePostfix = '_TMP'.rand();

					// build subquery
					$subQuery = new Query($this->init_entity);
					$subQuery->addSelect($primaryName);
					$subQuery->addFilter($filter_def, $filter_match);
					$subQuery->setTableAliasPostfix(strtolower($uniquePostfix));
					$subQuerySql = $subQuery->getQuery();

					// proxying subquery as value to callback
					$filter_match = $subQuerySql;
					$callback = array($this, 'dataDoublingCallback');

					$field_type = 'callback';

					// change sql definition
					$idChain = $this->getRegisteredChain($primaryName);
					$sqlDefinition = $idChain->getSqlDefinition();
				}

				// set entity connection to the sql expressions
				if ($filter_match instanceof Main\DB\SqlExpression)
				{
					$filter_match->setConnection($this->init_entity->getConnection());
				}

				//$is_having = $last->getValue() instanceof ExpressionField && $last->getValue()->isAggregated();

				// if back-reference found (Entity:REF)
				// if NO_DOUBLING mode enabled, then change getSQLDefinition to subquery exists(...)
				// and those chains should not be in joins if it is possible

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
					'FIELD_NAME' => $sqlDefinition,
					'FIELD_TYPE' => $field_type,
					'MULTIPLE' => '',
					'JOIN' => '',
					'CALLBACK' => $callback
				);
			}
			elseif (is_array($filter_match))
			{
				$fields = array_merge($fields, $this->getFilterCswFields($filter_match));
			}
		}

		return $fields;
	}

	protected function prepareJoinReference($reference, $alias_this, $alias_ref, $baseDefinition, $refDefenition, $isBackReference)
	{
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
				$new[$k] = $this->prepareJoinReference($v, $alias_this, $alias_ref, $baseDefinition, $refDefenition, $isBackReference);
			}
			else
			{
				// key
				$sqlWhere = new \CSQLWhere();
				$csw_result = $sqlWhere->makeOperation($k);
				list($field, $operation) = array_values($csw_result);

				if (strpos($field, 'this.') === 0)
				{
					// parse the chain
					$definition = str_replace(\CSQLWhere::getOperationByCode($operation).'this.', '', $k);
					$absDefinition = strlen($baseDefinition) ? $baseDefinition . '.' . $definition : $definition;

					$chain = $this->getRegisteredChain($absDefinition, true);

					if (!$isBackReference)
					{
						// make sure these fields will be joined bebore the main join
						$this->buildJoinMap(array($chain));
					}
					else
					{
						$chain->getLastElement()->setParameter('talias', $alias_this);
					}

					// recursively collect all "build_from" fields
					if ($chain->getLastElement()->getValue() instanceof ExpressionField)
					{
						$this->collectExprChains($chain);
						$this->buildJoinMap($chain->getLastElement()->getValue()->getBuildFromChains());
					}

					$k = \CSQLWhere::getOperationByCode($operation).$chain->getSqlDefinition();
				}
				elseif (strpos($field, 'ref.') === 0)
				{
					$definition = str_replace(\CSQLWhere::getOperationByCode($operation).'ref.', '', $k);
					$absDefinition = strlen($refDefenition) ? $refDefenition . '.' . $definition : $definition;

					$chain = $this->getRegisteredChain($absDefinition, true);

					if ($isBackReference)
					{
						// make sure these fields will be joined bebore the main join
						$this->buildJoinMap(array($chain));
					}
					else
					{
						$chain->getLastElement()->setParameter('talias', $alias_ref);
					}

					// recursively collect all "build_from" fields
					if ($chain->getLastElement()->getValue() instanceof ExpressionField)
					{
						$this->collectExprChains($chain);
						$this->buildJoinMap($chain->getLastElement()->getValue()->getBuildFromChains());
					}

					$k = \CSQLWhere::getOperationByCode($operation).$chain->getSqlDefinition();
				}
				else
				{
					throw new Main\SystemException(sprintf('Unknown reference key `%s`', $k));
				}

				// value
				if (is_array($v))
				{
					// field = expression
					$v = new \CSQLWhereExpression($v[0], array_slice($v, 1));
				}
				elseif ($v instanceof Main\DB\SqlExpression)
				{
					// set entity connection
					$v->setConnection($this->init_entity->getConnection());
				}
				elseif (!is_object($v))
				{
					if (strpos($v, 'this.') === 0)
					{
						$definition = str_replace('this.', '', $v);
						$absDefinition = strlen($baseDefinition) ? $baseDefinition . '.' . $definition : $definition;

						$chain = $this->getRegisteredChain($absDefinition, true);

						if (!$isBackReference)
						{
							// make sure these fields will be joined bebore the main join
							$this->buildJoinMap(array($chain));
						}
						else
						{
							$chain->getLastElement()->setParameter('talias', $alias_this);
						}

						// recursively collect all "build_from" fields
						if ($chain->getLastElement()->getValue() instanceof ExpressionField)
						{
							$this->collectExprChains($chain);
							$this->buildJoinMap($chain->getLastElement()->getValue()->getBuildFromChains());
						}

						$field_def = $chain->getSqlDefinition();
					}
					elseif (strpos($v, 'ref.') === 0)
					{
						$definition = str_replace('ref.', '', $v);
						$absDefinition = strlen($refDefenition) ? $refDefenition . '.' . $definition : $definition;

						$chain = $this->getRegisteredChain($absDefinition, true);

						if ($isBackReference)
						{
							// make sure these fields will be joined bebore the main join
							$this->buildJoinMap(array($chain));
						}
						else
						{
							$chain->getLastElement()->setParameter('talias', $alias_ref);
						}

						$this->buildJoinMap(array($chain));

						// recursively collect all "build_from" fields
						if ($chain->getLastElement()->getValue() instanceof ExpressionField)
						{
							$this->collectExprChains($chain);
							$this->buildJoinMap($chain->getLastElement()->getValue()->getBuildFromChains());
						}

						$field_def = $chain->getSqlDefinition();
					}
					else
					{
						throw new Main\SystemException(sprintf('Unknown reference value `%s`', $v));
					}

					$v = new \CSQLWhereExpression('?#', $field_def);
				}
				else
				{
					throw new Main\SystemException(sprintf('Unknown reference value `%s`', $v));
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
				$sqlWhere = new \CSQLWhere();
				$csw_result = $sqlWhere->makeOperation($k);
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
			/** @var QueryChainElement[] $bf_elements */
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
				/** @var QueryChain $reg_chain */
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

	static public function booleanStrongEqualityCallback($field, $operation, $value)
	{
		return ($value ? '' : 'NOT ') . $field;
	}

	static public function dataDoublingCallback($field, $operation, $value)
	{
		return $field.' IN ('.$value.')';
	}

	protected function query($query)
	{
		// check nosql configuration
		$connection = $this->init_entity->getConnection();
		$configuration = $connection->getConfiguration();

		/** @var Main\DB\Result $result */
		$result = null;

		if (isset($configuration['handlersocket']['read']))
		{
			// optimize through nosql
			$nosqlConnectionName = $configuration['handlersocket']['read'];

			$nosqlConnection = Main\Application::getInstance()->getConnectionPool()->getConnection($nosqlConnectionName);
			$isNosqlCapable = NosqlPrimarySelector::checkQuery($nosqlConnection, $this);

			if ($isNosqlCapable)
			{
				$nosqlResult = NosqlPrimarySelector::relayQuery($nosqlConnection, $this);
				$result = new Main\DB\ArrayResult($nosqlResult);
			}
		}

		if ($result === null)
		{
			// regular SQL query
			$cnt = null;

			if ($this->count_total)
			{
				$buildParts = $this->query_build_parts;

				//remove order
				unset($buildParts['ORDER BY']);

				//remove select
				$buildParts['SELECT'] = "1 cntholder";

				foreach ($buildParts as $k => &$v)
				{
					$v = $k . ' ' . $v;
				}

				$cntQuery = join("\n", $buildParts);

				// select count
				$cntQuery = 'SELECT COUNT(cntholder) AS TMP_ROWS_CNT FROM ('.$cntQuery.') xxx';
				$cnt = $connection->queryScalar($cntQuery);
			}

			$result = $connection->query($query);
			$result->setReplacedAliases($this->replaced_aliases);

			if($this->count_total)
			{
				$result->setCount($cnt);
			}

			static::$last_query = $query;
		}

		if ($this->isFetchModificationRequired())
		{
			$result->addFetchDataModifier(array($this, 'fetchDataModificationCallback'));
		}

		return $result;
	}

	/**
	 * Being called in Db\Result as a data fetch modifier
	 * @param $data
	 */
	
	/**
	* <p>Нестатический метод вызывается в <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/result/index.php">DB\Result</a> как модификатор данных выборки.</p>
	*
	*
	* @param mixed $data  
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/query/fetchdatamodificationcallback.php
	* @author Bitrix
	*/
	public function fetchDataModificationCallback(&$data)
	{
		// entity-defined callbacks
		foreach ($this->selectFetchModifiers as $alias => $modifiers)
		{
			foreach ($modifiers as $modifier)
			{
				$data[$alias] = call_user_func_array($modifier, array($data[$alias], $this, $data, $alias));
			}
		}
	}

	/**
	 * Check if fetch data modification reqired, also caches modifier-callbacks
	 * @return bool
	 */
	
	/**
	* <p>Нестатический метод проверяет требуется ли модификация данных, также кеширует модификатор.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/query/isfetchmodificationrequired.php
	* @author Bitrix
	*/
	public function isFetchModificationRequired()
	{
		$this->selectFetchModifiers = array();

		foreach ($this->select_chains as $chain)
		{
			if ($chain->getLastElement()->getValue()->getFetchDataModifiers())
			{
				$this->selectFetchModifiers[$chain->getAlias()] = $chain->getLastElement()->getValue()->getFetchDataModifiers();
			}
		}

		return !empty($this->selectFetchModifiers) || !empty($this->files);
	}

	protected function replaceSelectAliases($query)
	{
		$connection = $this->init_entity->getConnection();
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

	public function quoteTableSource($source)
	{
		// don't quote subqueries
		if (!preg_match('/\s*\(\s*SELECT.*\)\s*/is', $source))
		{
			$source =  $this->init_entity->getConnection()->getSqlHelper()->quote($source);
		}

		return $source;
	}

	public function __clone()
	{
		$this->init_entity = clone $this->init_entity;

		foreach ($this->select as $k => $v)
		{
			if ($v instanceof ExpressionField)
			{
				$this->select[$k] = clone $v;
			}
		}
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
	
	/**
	* <p>Нестатический метод выстраивает и возвращает строки SQL запроса.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/query/getquery.php
	* @author Bitrix
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
	
	/**
	* <p>Статический метод возвращает строку последнего выполненного запроса.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/query/getlastquery.php
	* @author Bitrix
	*/
	public static function getLastQuery()
	{
		return static::$last_query;
	}

	public function getEntity()
	{
		return $this->init_entity;
	}

	/**
	 * @param bool $withPostfix
	 * @return string
	 */
	public function getInitAlias($withPostfix = true)
	{
		$init_alias = strtolower($this->init_entity->getCode());

		// add postfix
		if ($withPostfix)
		{
			$init_alias .= $this->table_alias_postfix;
		}

		// check length
		$connection = $this->init_entity->getConnection();
		$aliasLength = $connection->getSqlHelper()->getAliasLength();

		if (strlen($init_alias) > $aliasLength)
		{
			$init_alias = 'base';

			// add postfix
			if ($withPostfix)
			{
				$init_alias .= $this->table_alias_postfix;
			}
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
		var_dump(static::$last_query);
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
