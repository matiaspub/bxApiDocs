<?php

namespace Bitrix\Main\Entity;

use Bitrix\Main\SystemException;

class QueryChainElement
{
	protected $value;

	protected $parameters;

	protected $type;

	protected $definition_fragment;

	protected $alias_fragment;

	/**
	 * Value format:
	 * 1. Field - normal scalar field
	 * 2. ReferenceField - pointer to another entity
	 * 3. array(Base, ReferenceField) - pointer from another entity to this
	 * 4. Base - all fields of entity
	 * @param Field|array|Base $element
	 * @param array $parameters
	 * @throws \Exception
	 */
	public function __construct($element, $parameters = array())
	{
		if ($element instanceof ReferenceField)
		{
			$this->type = 2;
		}
		elseif (is_array($element)
			&& $element[0] instanceof Base
			&& $element[1] instanceof ReferenceField
		)
		{
			$this->type = 3;
		}
		elseif ($element instanceof Base)
		{
			$this->type = 4;
		}
		elseif ($element instanceof Field)
		{
			$this->type = 1;
		}
		else
		{
			throw new SystemException(sprintf('Invalid value for QueryChainElement: %s.', $element));
		}

		$this->value = $element;
		$this->parameters = $parameters;
	}

	/**
	 * @return array|Base|ExpressionField|ReferenceField|FileField|ScalarField
	 */
	public function getValue()
	{
		return $this->value;
	}

	public function getParameter($name)
	{
		return $this->parameters[$name];
	}

	public function setParameter($name, $value)
	{
		$this->parameters[$name] = $value;
	}

	public function getDefinitionFragment()
	{
		if (is_null($this->definition_fragment))
		{
			if ($this->type == 2)
			{
				// skip uts entity
				if ($this->value->getRefEntity()->isUts())
				{
					$this->definition_fragment = '';
				}
				else
				{
					$this->definition_fragment = $this->value->getName();
				}
			}
			elseif ($this->type == 3)
			{
				// skip utm entity
				if ($this->value[0]->isUtm())
				{
					$this->definition_fragment = '';
				}
				else
				{
					$this->definition_fragment = $this->value[0]->getFullName()	. ':' . $this->value[1]->getName();
				}
			}
			elseif ($this->type == 4)
			{
				$this->definition_fragment = '*';
			}
			else
			{
				if (!empty($this->parameters['uField']))
				{
					$this->definition_fragment = $this->parameters['uField']->getName();
				}
				else
				{
					$this->definition_fragment = $this->value->getName();
				}
			}
		}

		return $this->definition_fragment;
	}

	public function getAliasFragment()
	{
		if (is_null($this->alias_fragment))
		{
			if ($this->type == 2)
			{
				// skip uts entity
				if ($this->value->getRefEntity()->isUts())
				{
					$this->alias_fragment = '';
				}
				else
				{
					$this->alias_fragment = $this->value->getName();
				}
			}
			elseif ($this->type == 3)
			{
				// skip utm entity
				if ($this->value[0]->isUtm())
				{
					$this->alias_fragment = '';
				}
				else
				{
					$this->alias_fragment = $this->value[0]->getCode() . '_' . $this->value[1]->getName();
				}
			}
			elseif ($this->type == 4)
			{
				$this->alias_fragment = $this->value->getCode();
			}
			else
			{
				if (!empty($this->parameters['ufield']))
				{
					$this->alias_fragment = $this->parameters['ufield']->getName();
				}
				else
				{
					$this->alias_fragment = $this->value->getName();
				}
			}
		}

		return $this->alias_fragment;
	}

	public function getSqlDefinition()
	{
		if (is_array($this->value) || $this->value instanceof ReferenceField || $this->value instanceof Base)
		{
			throw new SystemException('Unknown value');
		}

		if ($this->value instanceof ExpressionField)
		{
			$SQLBuildFrom = array();

			foreach ($this->value->getBuildFromChains() as $chain)
			{
				$SQLBuildFrom[] = $chain->getSQLDefinition();
			}

			// join
			$sql = call_user_func_array('sprintf', array_merge(array($this->value->getExpression()), $SQLBuildFrom));
		}
		else
		{
			$helper = $this->value->getEntity()->getConnection()->getSqlHelper();

			$sql = $helper->quote($this->getParameter('talias')) . '.';
			$sql .= $helper->quote($this->value->getColumnName());
		}

		return $sql;
	}

	public function isBackReference()
	{
		if ($this->type === 3)
		{
			return true;
		}

		if ($this->value instanceof ExpressionField)
		{
			foreach ($this->value->getBuildFromChains() as $bfChain)
			{
				if ($bfChain->hasBackReference())
				{
					return true;
				}
			}
		}

		return false;
	}

	public function dump()
	{
		echo gettype($this->value).' ';

		if ($this->value instanceof Field)
		{
			echo get_class($this->value).' '.$this->value->getName();
		}
		elseif ($this->value instanceof Base)
		{
			echo get_class($this->value);
		}
		elseif (is_array($this->value))
		{
			echo '('.get_class($this->value[0]).', '.get_class($this->value[1]).' '.$this->value[1]->getName().')';
		}

		echo ' '.json_encode($this->parameters);
	}
}