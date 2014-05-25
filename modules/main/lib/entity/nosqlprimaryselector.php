<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage main
 * @copyright  2001-2012 Bitrix
 */

namespace Bitrix\Main\Entity;

/**
 * Class description
 * @package    bitrix
 * @subpackage main
 */
class NosqlPrimarySelector
{
	/**
	 * @param \Bitrix\Main\Data\Connection $connection
	 * @param Query                        $query
	 *
	 * @return bool
	 */
	public static function checkQuery(\Bitrix\Main\Data\Connection $connection, Query $query)
	{
		// check interface
		if (!($connection instanceof INosqlPrimarySelector))
		{
			return false;
		}

		// no expressions in select
		foreach ($query->getSelectChains() as $selectChain)
		{
			if ($selectChain->getLastElement()->getValue() instanceof ExpressionField)
			{
				return false;
			}
		}

		// skip empty select, just for not handle this useless case in nosql api
		if (!count($query->getSelect()))
		{
			return false;
		}

		// if empty joinmap, group, order and simple filter
		if (!count($query->getJoinMap()) && !count($query->getGroupChains()) && !count($query->getOrderChains()) && !count($query->getHavingChains()))
		{
			$entityPrimary = $query->getEntity()->getPrimary();

			// check for primary singularity
			if (!is_array($entityPrimary))
			{
				// check if only primary is in filter
				if (count($query->getFilterChains()) == 1 && key($query->getFilterChains()) === $entityPrimary)
				{
					$passFilter = true;

					// check if only equality operations & 1-level filter
					foreach ($query->getFilter() as $filterElement => $filterValue)
					{
						if (is_numeric($filterElement) && is_array($filterValue))
						{
							// filter has subfilters. not ok
							$passFilter = false;
							break;
						}

						if ($filterElement === 'LOGIC')
						{
							continue;
						}

						$operation = substr($filterElement, 0, 1);

						if ($operation !== '=')
						{
							// only equal operation allowed. not ok
							$passFilter = false;
							break;
						}
					}

					// fine!
					if ($passFilter)
					{
						return true;
					}
				}
			}
		}

		return false;
	}

	public static function relayQuery(\Bitrix\Main\Data\Connection $connection, Query $query)
	{
		// prepare select
		$select = array();

		foreach ($query->getSelectChains() as $selectChain)
		{
			$select[] = $selectChain->getLastElement()->getValue()->getName();
		}

		// prepare filter
		$filter = array();

		foreach ($query->getFilter() as $filterElem)
		{
			if (is_array($filterElem))
			{
				$filter = array_merge($filter, $filterElem);
			}
			else
			{
				$filter[] = $filterElem;
			}
		}

		$result = $connection->getEntityByPrimary($query->getEntity(), $filter, $select);

		return $result;
	}
}
