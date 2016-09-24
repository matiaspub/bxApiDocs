<?php

namespace Bitrix\Main\Entity;

use Bitrix\Main;
use Bitrix\Main\SystemException;

class QueryChain
{
	/**
	 * @var QueryChainElement[]
	 */
	protected $chain;

	protected $size = 0;

	protected $definition;

	protected $alias;

	protected $custom_alias;

	/** @var boolean */
	protected $forcesDataDoulingOff = false;

	/**
	 * @var QueryChainElement
	 */
	protected $last_element;

	public function __construct()
	{
		$this->chain = array();
	}

	public function addElement(QueryChainElement $element)
	{
		if (empty($this->chain) && !($element->getValue() instanceof Base))
		{
			throw new SystemException('The first element of chain should be Entity only.');
		}

		$this->chain[] = $element;
		$this->definition = null;
		$this->alias = null;

		$this->last_element = $element;
		$this->size++;
	}

	public function getFirstElement()
	{
		return $this->chain[0];
	}

	/**
	 * @return QueryChainElement
	 */
	public function getLastElement()
	{
		return $this->last_element;
	}

	/**
	 * @return array|QueryChainElement[]
	 */
	public function getAllElements()
	{
		return $this->chain;
	}

	public function removeLastElement()
	{
		$this->chain = array_slice($this->chain, 0, -1);
		$this->definition = null;
		$this->alias = null;

		$this->last_element = end($this->chain);
		$this->size--;
	}

	public function hasBackReference()
	{
		foreach ($this->chain as $element)
		{
			if ($element->isBackReference())
			{
				return true;
			}
		}

		return false;
	}

	public function getSize()
	{
		return $this->size;
	}

	public function getDefinition()
	{
		if (is_null($this->definition))
		{
			$this->definition = self::getDefinitionByChain($this);
		}

		return $this->definition;
	}

	public function getAlias()
	{
		if ($this->custom_alias !== null)
		{
			return $this->custom_alias;
		}

		if ($this->alias === null)
		{
			$this->alias = self::getAliasByChain($this);
		}

		return $this->alias;
	}

	public function setCustomAlias($alias)
	{
		$this->custom_alias = $alias;
	}

	public static function getChainByDefinition(Base $init_entity, $definition)
	{
		$chain = new QueryChain;
		$chain->addElement(new QueryChainElement($init_entity));

		$def_elements = explode('.', $definition);
		$def_elements_size = count($def_elements);

		$prev_entity  = $init_entity;

		$i = 0;

		foreach ($def_elements as &$def_element)
		{
			$is_last_elem  = (++$i == $def_elements_size);

			$not_found = false;

			// all elements should be a Reference field or Entity
			// normal (scalar) field can only be the last element

			if ($prev_entity->hasField($def_element))
			{
				// field has been found at current entity
				$field = $prev_entity->getField($def_element);

				if ($field instanceof ReferenceField)
				{
					$prev_entity = $field->getRefEntity();
				}
				elseif ($field instanceof ExpressionField)
				{
					// expr can be in the middle too
				}
				elseif (!$is_last_elem)
				{
					throw new SystemException(sprintf(
						'Normal fields can be only the last in chain, `%s` %s is not the last.',
						$field->getName(), get_class($field)
					));
				}

				if ($is_last_elem && $field instanceof ExpressionField)
				{
					// we should have own copy of build_from_chains to set join aliases there
					$field = clone $field;
				}

				$chain->addElement(new QueryChainElement($field));
			}
			elseif ($prev_entity->hasUField($def_element) && false)
			{
				/** @deprecated */
				// extend chain with utm/uts entity
				$ufield = $prev_entity->getUField($def_element);

				if ($ufield->isMultiple())
				{
					// add utm entity  user.utm:source_object (1:N)
					$utm_entity = Base::getInstance($prev_entity->getNamespace().'Utm'.$prev_entity->getName());

					$chain->addElement(new QueryChainElement(
						array($utm_entity, $utm_entity->getField('SOURCE_OBJECT')),
						array('ufield' => $ufield)
					));

					if ($ufield->getTypeId() == 'iblock_section'
						&& substr($ufield->getName(), -3) == '_BY'
						&& $prev_entity->hasUField(substr($ufield->getName(), 0, -3))
					)
					{
						// connect next entity
						$utm_fname = $ufield->getName();
						$prev_entity = Base::getInstance('Bitrix\Iblock\Section');
					}
					else
					{
						$utm_fname = $ufield->getValueFieldName();
					}

					$chain->addElement(new QueryChainElement(
						$utm_entity->getField($utm_fname),
						array('ufield' => $ufield)
					));
				}
				else
				{
					// uts table - single value
					// add uts entity user.uts (1:1)
					$uts_entity = Base::getInstance($prev_entity->getNamespace().'Uts'.$prev_entity->getName());

					$chain->addElement(new QueryChainElement(
						$prev_entity->getField('UTS_OBJECT')
					));

					// add `value` field
					$chain->addElement(new QueryChainElement(
						$uts_entity->getField($def_element)
					));
				}
			}
			elseif (Base::isExists($def_element)
				&& Base::getInstance($def_element)->getReferencesCountTo($prev_entity->getName()) == 1
			)
			{
				// def_element is another entity with only 1 reference to current entity
				// need to identify Reference field
				$ref_entity = Base::getInstance($def_element);
				$field = end($ref_entity->getReferencesTo($prev_entity->getName()));

				$prev_entity = $ref_entity;

				$chain->addElement(new QueryChainElement(
					array($ref_entity, $field)
				));
			}
			elseif ( ($pos_wh = strpos($def_element, ':')) > 0 )
			{
				$ref_entity_name = substr($def_element, 0, $pos_wh);

				if (strpos($ref_entity_name, '\\') === false)
				{
					// if reference has no namespace, then it'is in the namespace of previous entity
					$ref_entity_name = $prev_entity->getNamespace().$ref_entity_name;
				}

				if (
					Base::isExists($ref_entity_name)
					&& Base::getInstance($ref_entity_name)->hasField($ref_field_name = substr($def_element, $pos_wh+1))
					&& Base::getInstance($ref_entity_name)->getField($ref_field_name) instanceof ReferenceField
				)
				{
					/** @var ReferenceField $reference */
					$reference = Base::getInstance($ref_entity_name)->getField($ref_field_name);

					if ($reference->getRefEntity()->getFullName() == $prev_entity->getFullName())
					{
						// chain element is another entity with >1 references to current entity
						// def like NewsArticle:AUTHOR, NewsArticle:LAST_COMMENTER
						// NewsArticle - entity, AUTHOR and LAST_COMMENTER - Reference fields
						$chain->addElement(new QueryChainElement(array(
							Base::getInstance($ref_entity_name),
							Base::getInstance($ref_entity_name)->getField($ref_field_name)
						)));

						$prev_entity = Base::getInstance($ref_entity_name);
					}
					else
					{
						$not_found = true;
					}
				}
				else
				{
					$not_found = true;
				}

			}
			elseif ($def_element == '*' && $is_last_elem)
			{
				continue;
			}
			else
			{
				// unknown chain
				$not_found = true;
			}

			if ($not_found)
			{
				throw new SystemException(sprintf(
					'Unknown field definition `%s` (%s) for %s Entity.',
					$def_element, $definition, $prev_entity->getName()
				), 100);
			}
		}

		return $chain;
	}

	public static function getDefinitionByChain(QueryChain $chain)
	{
		$def = array();

		// add members of chain except of init entity
		/** @var $elements QueryChainElement[] */
		$elements = array_slice($chain->getAllElements(), 1);

		foreach ($elements  as $element)
		{
			if ($element->getValue() instanceof ExpressionField && $element !== end($elements))
			{
				// skip non-last expressions
				//continue;
			}

			$def[] = $element->getDefinitionFragment();
		}

		return join('.', $def);
	}

	public static function getAliasByChain(QueryChain $chain)
	{
		$alias = array();

		$elements = $chain->getAllElements();

		// add prefix of init entity
		if (count($elements) > 2)
		{
			$alias[] = $chain->getFirstElement()->getAliasFragment();
		}

		// add other members of chain
		/** @var QueryChainElement[] $elements */
		$elements = array_slice($elements, 1);

		foreach ($elements  as $element)
		{
			$fragment = $element->getAliasFragment();

			if (strlen($fragment))
			{
				$alias[] = $fragment;
			}
		}

		return join('_', $alias);
	}

	public static function getAliasByDefinition(Base $entity, $definition)
	{
		return self::getChainByDefinition($entity, $definition)->getAlias();
	}

	public function hasAggregation()
	{
		$elements = array_reverse($this->chain);

		foreach ($elements as $element)
		{
			/**
			 * @var $element QueryChainElement
			 */
			if ($element->getValue() instanceof ExpressionField && $element->getValue()->isAggregated())
			{
				return true;
			}
		}

		return false;
	}

	public function hasSubquery()
	{
		$elements = array_reverse($this->chain);

		foreach ($elements as $element)
		{
			/**
			 * @var $element QueryChainElement
			 */
			if ($element->getValue() instanceof ExpressionField && $element->getValue()->hasSubquery())
			{
				return true;
			}
		}

		return false;
	}

	public function isConstant()
	{
		return ($this->getLastElement()->getValue() instanceof ExpressionField
			&& $this->getLastElement()->getValue()->isConstant());
	}

	public function forceDataDoublingOff()
	{
		$this->forcesDataDoulingOff = true;
	}

	public function forcesDataDoublingOff()
	{
		return $this->forcesDataDoulingOff;
	}

	public function getSqlDefinition($with_alias = false)
	{
		$sql_def = $this->getLastElement()->getSqlDefinition();

		if ($with_alias)
		{
			$helper = $this->getLastElement()->getValue()->getEntity()->getConnection()->getSqlHelper();
			$sql_def .= ' AS ' . $helper->quote($this->getAlias());
		}

		return $sql_def;
	}

	public function __clone()
	{
		$this->custom_alias = null;
	}

	public function dump()
	{
		echo '  '.'   forcesDataDoublingOff: '.($this->forcesDataDoublingOff()?'true':'false');
		echo PHP_EOL;

		$i = 0;
		foreach ($this->chain as $elem)
		{
			echo '  '.++$i.'. ';
			$elem->dump();
			echo PHP_EOL;
		}
	}
}