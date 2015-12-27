<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\PropertyIndex;

class QueryBuilder
{
	/** @var \Bitrix\Iblock\PropertyIndex\Facet */
	protected $facet = null;
	/** @var \Bitrix\Iblock\PropertyIndex\Dictionary */
	protected $dictionary = null;
	/** @var \Bitrix\Iblock\PropertyIndex\Storage  */
	protected $storage = null;

	protected $sectionFilter = null;
	protected $priceFilter = null;
	protected $distinct = false;
	protected $options = array();

	/**
	 * @param integer $iblockId Information block identifier.
	 */
	public function __construct($iblockId)
	{
		$this->facet = new Facet($iblockId);
		$this->dictionary = $this->facet->getDictionary();
		$this->storage = $this->facet->getStorage();
	}

	/**
	 * Returns true if filter rewrite is possible.
	 *
	 * @return boolean
	 */
	public function isValid()
	{
		return $this->facet->isValid();
	}

	/**
	 * Returns true if filter needs distinct sql clause.
	 *
	 * @return boolean
	 */
	public function getDistinct()
	{
		return $this->distinct;
	}

	/**
	 * Returns filter join with index tables.
	 *
	 * @param array &$filter Filter which may be rewritten.
	 * @param array &$sqlSearch Additional result of rewrite.
	 *
	 * @return string
	 */
	public function getFilterSql(&$filter, &$sqlSearch)
	{
		if (array_key_exists("FACET_OPTIONS", $filter))
		{
			if (is_array($filter["FACET_OPTIONS"]))
				$this->options = $filter["FACET_OPTIONS"];
			unset($filter["FACET_OPTIONS"]);
		}

		$this->distinct = false;
		$fcJoin = "";
		$toUnset = array();
		if (
			!is_array($filter["IBLOCK_ID"]) && $filter["IBLOCK_ID"] > 0
			&& (
				(!is_array($filter["SECTION_ID"]) && $filter["SECTION_ID"] > 0)
				|| ($this->options && !isset($filter["SECTION_ID"]))
			)
			&& isset($filter["ACTIVE"]) && $filter["ACTIVE"] === "Y"
		)
		{
			$where = array();
			if (isset($filter["SECTION_ID"]))
				$toUnset[] = array(&$filter, "SECTION_ID");

			if ($filter["INCLUDE_SUBSECTIONS"] === "Y")
			{
				$subsectionsCondition = "";
				$toUnset[] = array(&$filter, "INCLUDE_SUBSECTIONS");
			}
			else
			{
				$subsectionsCondition = "INCLUDE_SUBSECTIONS=1";
				if (array_key_exists("INCLUDE_SUBSECTIONS", $filter))
					$toUnset[] = array(&$filter, "INCLUDE_SUBSECTIONS");
			}

			$hasAdditionalFilters = false;
			$this->fillWhere($where, $hasAdditionalFilters, $toUnset, $filter);

			if (!$where)
			{
				$where[] = array(
					"TYPE" => Storage::DICTIONARY,
					"OP" => "=",
					"FACET_ID" => 1,
					"VALUES" => array(0),
				);
			}

			if (
				isset($filter["=ID"]) && is_object($filter["=ID"])
				&& $filter["=ID"]->arFilter["IBLOCK_ID"] == $this->facet->getSkuIblockId()
				&& $filter["=ID"]->strField === "PROPERTY_".$this->facet->getSkuPropertyId()
			)
			{
				$hasAdditionalFilters = false;
				$this->fillWhere($where, $hasAdditionalFilters, $toUnset, $filter["=ID"]->arFilter);
				if (!$hasAdditionalFilters)
				{
					$toUnset[] = array(&$filter, "=ID");
				}
			}

			if ($where)
			{
				$filter["SECTION_ID"] = (isset($filter["SECTION_ID"]) ? (int)$filter["SECTION_ID"] : 0);
				$this->facet->setSectionId($filter["SECTION_ID"]);
				if ($this->options)
				{
					if ($this->options["CURRENCY_CONVERSION"])
						$this->facet->enableCurrencyConversion($this->options["CURRENCY_CONVERSION"]["TO"], $this->options["CURRENCY_CONVERSION"]["FROM"]);
				}
				$distinctSelectCapable = (\Bitrix\Main\Application::getConnection()->getType() == "mysql");
				if (count($where) == 1 && $distinctSelectCapable)
				{
					$this->distinct = true;
					$fcJoin = "INNER JOIN ".$this->storage->getTableName()." FC on FC.ELEMENT_ID = BE.ID";
					foreach ($where as $facetFilter)
					{
						$sqlWhere = $this->facet->whereToSql($facetFilter, "FC", $subsectionsCondition);
						if ($sqlWhere)
							$sqlSearch[] = $sqlWhere;
					}
				}
				elseif (count($where) <= 5)
				{
					$subJoin = "";
					$subWhere = "";
					$i = 0;
					foreach ($where as $facetFilter)
					{
						if ($i == 0)
							$subJoin .= "FROM ".$this->storage->getTableName()." FC$i\n";
						else
							$subJoin .= "INNER JOIN ".$this->storage->getTableName()." FC$i ON FC$i.ELEMENT_ID = FC0.ELEMENT_ID\n";

						$sqlWhere = $this->facet->whereToSql($facetFilter, "FC$i", $subsectionsCondition);
						if ($sqlWhere)
						{
							if ($subWhere)
								$subWhere .= "\nAND ".$sqlWhere;
							else
								$subWhere .= $sqlWhere;
						}

						$i++;
					}

					$fcJoin = "
						INNER JOIN (
							SELECT ".($distinctSelectCapable? "": "DISTINCT")." FC0.ELEMENT_ID
							$subJoin
							WHERE
							$subWhere
						) FC on FC.ELEMENT_ID = BE.ID
					";
				}
				else
				{
					$condition = array();
					foreach ($where as $facetFilter)
					{
						$sqlWhere = $this->facet->whereToSql($facetFilter, "FC0", $subsectionsCondition);
						if ($sqlWhere)
							$condition[] = $sqlWhere;
					}
					$fcJoin = "
						INNER JOIN (
							SELECT FC0.ELEMENT_ID
							FROM ".$this->storage->getTableName()." FC0
							WHERE FC0.SECTION_ID = ".$filter["SECTION_ID"]."
							AND (
							(".implode(")OR(", $condition).")
							)
						GROUP BY FC0.ELEMENT_ID
						HAVING count(DISTINCT FC0.FACET_ID) = ".count($condition)."
						) FC on FC.ELEMENT_ID = BE.ID
					";
				}

				foreach ($toUnset as $command)
				{
					unset($command[0][$command[1]]);
				}
			}
			else
			{
				$fcJoin = "";
			}
		}
		return $fcJoin;
	}

	/**
	 * Goes through the $filter and creates unified conditions in $where array.
	 *
	 * @param array &$where Output result.
	 * @param boolean &$hasAdditionalFilters Whenever has some filters left or not.
	 * @param array &$toUnset Output $filter keys which may be excluded.
	 * @param array &$filter Filter to go through.
	 *
	 * @return void
	 */
	private function fillWhere(&$where, &$hasAdditionalFilters, &$toUnset, &$filter)
	{
		$properties = $this->getFilterProperty();
		foreach ($filter as $filterKey => $filterValue)
		{
			if (preg_match("/^(=)PROPERTY\$/i", $filterKey, $keyDetails) && is_array($filterValue))
			{
				foreach ($filterValue as $propertyId => $value)
				{
					$facetId = $this->storage->propertyIdToFacetId($propertyId);
					if ($properties[$propertyId] == Storage::DICTIONARY || $properties[$propertyId] == Storage::STRING)
					{
						$sqlValues = $this->getInSql($value, $properties[$propertyId] == Storage::STRING);
						if ($sqlValues)
						{
							$where[] = array(
								"TYPE" => $properties[$propertyId],
								"OP" => $keyDetails[1],
								"FACET_ID" => $facetId,
								"VALUES" => $sqlValues,
							);
							$toUnset[] = array(&$filter[$filterKey], $propertyId);
						}
					}
				}
			}
			elseif (preg_match("/^(=)PROPERTY_(\\d+)\$/i", $filterKey, $keyDetails))
			{
				$propertyId = $keyDetails[2];
				$value = $filterValue;
				$facetId = $this->storage->propertyIdToFacetId($propertyId);
				if ($properties[$propertyId] == Storage::DICTIONARY || $properties[$propertyId] == Storage::STRING)
				{
					$sqlValues = $this->getInSql($value, $properties[$propertyId] == Storage::STRING);
					if ($sqlValues)
					{
						$where[] = array(
							"TYPE" => $properties[$propertyId],
							"OP" => $keyDetails[1],
							"FACET_ID" => $facetId,
							"VALUES" => $sqlValues,
						);
						$toUnset[] = array(&$filter, $filterKey);
					}
				}
			}
			elseif (preg_match("/^(>=|<=)PROPERTY\$/i", $filterKey, $keyDetails) && is_array($filterValue))
			{
				foreach ($filterValue as $propertyId => $value)
				{
					$facetId = $this->storage->propertyIdToFacetId($propertyId);
					if ($properties[$propertyId] == Storage::NUMERIC)
					{
						if (is_array($value))
							$doubleValue = doubleval(current($value));
						else
							$doubleValue = doubleval($value);
						$where[] = array(
							"TYPE" => Storage::NUMERIC,
							"OP" => $keyDetails[1],
							"FACET_ID" => $facetId,
							"VALUES" => array($doubleValue),
						);
						$toUnset[] = array(&$filter[$filterKey], $propertyId);
					}
					elseif ($properties[$propertyId] == Storage::DATETIME)
					{
						if (is_array($value))
							$timestamp = MakeTimeStamp(current($value), "YYYY-MM-DD HH:MI:SS");
						else
							$timestamp = MakeTimeStamp($value, "YYYY-MM-DD HH:MI:SS");
						$where[] = array(
							"TYPE" => Storage::DATETIME,
							"OP" => $keyDetails[1],
							"FACET_ID" => $facetId,
							"VALUES" => array($timestamp),
						);
						$toUnset[] = array(&$filter[$filterKey], $propertyId);
					}
				}
			}
			elseif (preg_match("/^(><)PROPERTY\$/i", $filterKey, $keyDetails) && is_array($filterValue))
			{
				foreach ($filterValue as $propertyId => $value)
				{
					$facetId = $this->storage->propertyIdToFacetId($propertyId);
					if ($properties[$propertyId] == Storage::NUMERIC)
					{
						if (is_array($value) && count($value) == 2)
						{
							$doubleMinValue = doubleval(current($value));
							$doubleMaxValue = doubleval(end($value));
							$where[] = array(
								"TYPE" => Storage::NUMERIC,
								"OP" => $keyDetails[1],
								"FACET_ID" => $facetId,
								"VALUES" => array($doubleMinValue, $doubleMaxValue),
							);
							$toUnset[] = array(&$filter[$filterKey], $propertyId);
						}
					}
					elseif ($properties[$propertyId] == Storage::DATETIME)
					{
						if (is_array($value) && count($value) == 2)
						{
							$timestamp1 = MakeTimeStamp(current($value), "YYYY-MM-DD HH:MI:SS");
							$timestamp2 = MakeTimeStamp(end($value), "YYYY-MM-DD HH:MI:SS");
							$where[] = array(
								"TYPE" => Storage::DATETIME,
								"OP" => $keyDetails[1],
								"FACET_ID" => $facetId,
								"VALUES" => array($timestamp1, $timestamp2),
							);
							$toUnset[] = array(&$filter[$filterKey], $propertyId);
						}
					}
				}
			}
			elseif (
				$this->options["PRICE_FILTER"]
				&& preg_match("/^(>=|<=)CATALOG_PRICE_(\\d+)\$/i", $filterKey, $keyDetails)
				&& !is_array($filterValue)
			)
			{
				$priceId = $keyDetails[2];
				$value = $filterValue;
				$facetId = $this->storage->priceIdToFacetId($priceId);
				$doubleValue = doubleval($value);
				$where[] = array(
					"TYPE" => Storage::PRICE,
					"OP" => $keyDetails[1],
					"FACET_ID" => $facetId,
					"VALUES" => array($doubleValue),
				);
				$toUnset[] = array(&$filter, $filterKey);
			}
			elseif (
				$this->options["PRICE_FILTER"]
				&& preg_match("/^(><)CATALOG_PRICE_(\\d+)\$/i", $filterKey, $keyDetails)
				&& is_array($filterValue)
			)
			{
				$priceId = $keyDetails[2];
				$value = $filterValue;
				$facetId = $this->storage->priceIdToFacetId($priceId);
				$doubleValueMin = doubleval($value[0]);
				$doubleValueMax = doubleval($value[1]);
				$where[] = array(
					"TYPE" => Storage::PRICE,
					"OP" => $keyDetails[1],
					"FACET_ID" => $facetId,
					"VALUES" => array($doubleValueMin, $doubleValueMax),
				);
				$toUnset[] = array(&$filter, $filterKey);
			}
			elseif (
				$this->options["PRICE_FILTER"]
				&& is_numeric($filterKey)
				&& is_array($filterValue) && count($filterValue) === 3
				&& isset($filterValue["LOGIC"]) && $filterValue["LOGIC"] === "OR"
				&& isset($filterValue["=ID"]) && is_object($filterValue["=ID"])
				&& preg_match("/^(>=|<=)CATALOG_PRICE_(\\d+)\$/i", key($filterValue[0][0]), $keyDetails)
				&& !is_array(current($filterValue[0][0]))
			)
			{
				$priceId = $keyDetails[2];
				$value = current($filterValue[0][0]);
				$facetId = $this->storage->priceIdToFacetId($priceId);
				$doubleValue = doubleval($value);
				$where[] = array(
					"TYPE" => Storage::PRICE,
					"OP" => $keyDetails[1],
					"FACET_ID" => $facetId,
					"VALUES" => array($doubleValue),
				);
				$toUnset[] = array(&$filter, $filterKey);
				$toUnset[] = array(&$filter, "CATALOG_SHOP_QUANTITY_1");
			}
			elseif (
				$this->options["PRICE_FILTER"]
				&& is_numeric($filterKey)
				&& is_array($filterValue) && count($filterValue) === 3
				&& isset($filterValue["LOGIC"]) && $filterValue["LOGIC"] === "OR"
				&& isset($filterValue["=ID"]) && is_object($filterValue["=ID"])
				&& preg_match("/^(><)CATALOG_PRICE_(\\d+)\$/i", key($filterValue[0][0]), $keyDetails)
				&& is_array(current($filterValue[0][0]))
			)
			{
				$priceId = $keyDetails[2];
				$value = current($filterValue[0][0]);
				$facetId = $this->storage->priceIdToFacetId($priceId);
				$doubleValueMin = doubleval($value[0]);
				$doubleValueMax = doubleval($value[1]);
				$where[] = array(
					"TYPE" => Storage::PRICE,
					"OP" => $keyDetails[1],
					"FACET_ID" => $facetId,
					"VALUES" => array($doubleValueMin, $doubleValueMax),
				);
				$toUnset[] = array(&$filter, $filterKey);
				$toUnset[] = array(&$filter, "CATALOG_SHOP_QUANTITY_1");
			}
			elseif (
				$filterKey !== "IBLOCK_ID"
				&& $filterKey !== "ACTIVE"
				&& $filterKey !== "ACTIVE_DATE"
			)
			{
				$hasAdditionalFilters = true;
			}
		}
	}

	/**
	 * Returns array on integers representing values for sql query.
	 *
	 * @param mixed $value Value to be intvaled.
	 * @param boolean $lookup Whenever to convert the value from string to dictionary or not.
	 *
	 * @return integer[]
	 */
	protected function getInSql($value, $lookup)
	{
		$result = array();

		if (is_array($value))
		{
			foreach ($value as $val)
			{
				if (strlen($val) > 0)
				{
					if ($lookup)
					{
						$result[] = $this->dictionary->getStringId($val, false);
					}
					else
					{
						$result[] = intval($val);
					}
				}
			}
		}
		elseif (strlen($value) > 0)
		{
			if ($lookup)
			{
				$result[] = $this->dictionary->getStringId($value, false);
			}
			else
			{
				$result[] = intval($value);
			}
		}

		return $result;
	}

	/**
	 * Returns map of properties to their types.
	 * Properties of iblock and its sku returned
	 * which marked as for smart filter.
	 *
	 * @return integer[]
	 */
	private function getFilterProperty()
	{
		if (!isset($this->propertyFilter))
		{
			$this->propertyFilter = array();
			$propertyList = \Bitrix\Iblock\SectionPropertyTable::getList(array(
				"select" => array("PROPERTY_ID", "PROPERTY.PROPERTY_TYPE", "PROPERTY.USER_TYPE"),
				"filter" => array(
					"=IBLOCK_ID" => array($this->facet->getIblockId(), $this->facet->getSkuIblockId()),
					"=SMART_FILTER" => "Y",
				),
			));
			while ($link = $propertyList->fetch())
			{
				if ($link["IBLOCK_SECTION_PROPERTY_PROPERTY_PROPERTY_TYPE"] === "N")
					$this->propertyFilter[$link["PROPERTY_ID"]] = Storage::NUMERIC;
				elseif ($link["IBLOCK_SECTION_PROPERTY_PROPERTY_USER_TYPE"] === "DateTime")
					$this->propertyFilter[$link["PROPERTY_ID"]] = Storage::DATETIME;
				elseif ($link["IBLOCK_SECTION_PROPERTY_PROPERTY_PROPERTY_TYPE"] === "S")
					$this->propertyFilter[$link["PROPERTY_ID"]] = Storage::STRING;
				else
					$this->propertyFilter[$link["PROPERTY_ID"]] = Storage::DICTIONARY;
			}
		}
		return $this->propertyFilter;
	}
}
