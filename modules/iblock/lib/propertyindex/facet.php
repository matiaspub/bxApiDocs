<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\PropertyIndex;
use Bitrix\Catalog;

class Facet
{
	protected $iblockId = 0;
	protected $valid = false;
	protected $skuIblockId = 0;
	protected $skuPropertyId = 0;
	protected $sectionFilter = array();
	protected $priceFilter = null;
	protected $toCurrencyId = 0;
	protected $convertCurrencyId = array();

	/** @var \Bitrix\Iblock\PropertyIndex\Dictionary */
	protected $dictionary = null;
	/** @var \Bitrix\Iblock\PropertyIndex\Storage  */
	protected $storage = null;
	protected static $catalog = null;

	protected $sectionId = 0;
	protected $where = array();

	/**
	 * @param integer $iblockId Information block identifier.
	 */
	public function __construct($iblockId)
	{
		$this->iblockId = intval($iblockId);
		$this->valid = \CIBlock::getArrayByID($this->iblockId, "PROPERTY_INDEX") === "Y";

		if (self::$catalog === null)
		{
			self::$catalog = \Bitrix\Main\Loader::includeModule("catalog");
		}

		if (self::$catalog)
		{
			$catalogInfo = \CCatalogSKU::getInfoByProductIBlock($this->iblockId);
			if (!empty($catalogInfo) && is_array($catalogInfo))
			{
				$this->skuIblockId = $catalogInfo["IBLOCK_ID"];
				$this->skuPropertyId = $catalogInfo["SKU_PROPERTY_ID"];
				$this->valid = $this->valid && \CIBlock::getArrayByID($this->skuIblockId, "PROPERTY_INDEX") === "Y";
			}
		}

		$this->dictionary = new Dictionary($this->iblockId);
		$this->storage = new Storage($this->iblockId);

		$this->valid = $this->valid && $this->dictionary->isExists();
	}

	/**
	 * Returns true if filter rewrite is possible.
	 *
	 * @return boolean
	 */
	public function isValid()
	{
		return $this->valid;
	}

	/**
	 * Returns iblock identifier.
	 *
	 * @return integer
	 */
	public function getIblockId()
	{
		return $this->iblockId;
	}

	/**
	 * Returns SKU iblock identifier.
	 *
	 * @return integer
	 */
	public function getSkuIblockId()
	{
		return $this->skuIblockId;
	}

	/**
	 * Returns SKU property identifier.
	 *
	 * @return integer
	 */
	public function getSkuPropertyId()
	{
		return $this->skuPropertyId;
	}

	/**
	 * Returns facet storage object.
	 *
	 * @return \Bitrix\Iblock\PropertyIndex\Storage
	 */
	public function getStorage()
	{
		return $this->storage;
	}

	/**
	 * Returns facet dictionary object.
	 *
	 * @return \Bitrix\Iblock\PropertyIndex\Dictionary
	 */
	public function getDictionary()
	{
		return $this->dictionary;
	}

	/**
	 * Returns string by its identifier in the dictionary.
	 *
	 * @param integer $valueId Value identifier for dictionary lookup.
	 *
	 * @return string
	 */
	public function lookupDictionaryValue($valueId)
	{
		return $this->dictionary->getStringById($valueId);
	}

	/**
	 * Returns filter join with index tables.
	 * <p>
	 * $filter parameters same as for CIBlockElement::getList
	 * <p>
	 * $facetTypes allows to get only "checkboxes" or "prices" and such.
	 *
	 * @param array $filter Filter to apply additionally to filter elements.
	 * @param array $facetTypes Which facet types will be used.
	 * @param integer $facetId Which facet category filter should not be applied.
	 *
	 * @return \Bitrix\Main\DB\Result
	 */
	public function query(array $filter, array $facetTypes = array(), $facetId = 0)
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$facetFilter = $this->getFacetFilter($facetTypes);
		if (!$facetFilter)
		{
			return false;
		}

		if ($filter)
		{
			$filter["IBLOCK_ID"] = $this->iblockId;

			$element = new \CIBlockElement;
			$element->strField = "ID";
			$element->getList(array(), $filter, false, false, array("ID"));
			$elementFrom = $element->sFrom;
			$elementWhere = $element->sWhere;
		}
		else
		{
			$elementFrom = "";
			$elementWhere = "";
		}

		$facets = array();
		if ($facetId)
		{
			$facets[] = array(
				"where" => $this->getWhere($facetId),
				"facet" => array($facetId),
			);
		}
		else
		{
			foreach ($facetFilter as $facetId)
			{
				$where = $this->getWhere($facetId);

				$found = false;
				foreach ($facets as $i => $facetWhereAndFacets)
				{
					if ($facetWhereAndFacets["where"] == $where)
					{
						$facets[$i]["facet"][] = $facetId;
						$found = true;
						break;
					}
				}

				if (!$found)
				{
					$facets[] = array(
						"where" => $where,
						"facet" => array($facetId),
					);
				}
			}
		}

		$sqlUnion = array();
		foreach ($facets as $facetWhereAndFacets)
		{
			$where = $facetWhereAndFacets["where"];
			$facetFilter = $facetWhereAndFacets["facet"];

			$sqlSearch = array("1=1");

			if (empty($where))
			{
				$sqlUnion[] = "
					SELECT
						F.FACET_ID
						,F.VALUE
						,MIN(F.VALUE_NUM) MIN_VALUE_NUM
						,MAX(F.VALUE_NUM) MAX_VALUE_NUM
						".($connection instanceof \Bitrix\Main\DB\MysqlCommonConnection
							?",MAX(case when LOCATE('.', F.VALUE_NUM) > 0 then LENGTH(SUBSTRING_INDEX(F.VALUE_NUM, '.', -1)) else 0 end)"
							:",MAX(".$sqlHelper->getLengthFunction("ABS(F.VALUE_NUM) - FLOOR(ABS(F.VALUE_NUM))")."+1-".$sqlHelper->getLengthFunction("0.1").")"
						)." VALUE_FRAC_LEN
						,COUNT(DISTINCT F.ELEMENT_ID) ELEMENT_COUNT
					FROM
						".($elementFrom
								?$elementFrom."
							INNER JOIN ".$this->storage->getTableName()." F ON BE.ID = F.ELEMENT_ID"
								:$this->storage->getTableName()." F"
							)."
					WHERE
						F.SECTION_ID = ".$this->sectionId."
						and F.FACET_ID in (".implode(",", $facetFilter).")
						".$elementWhere."
					GROUP BY
						F.FACET_ID, F.VALUE
				";
				continue;
			}
			elseif (count($where) == 1)
			{
				$fcJoin = "INNER JOIN ".$this->storage->getTableName()." FC on FC.ELEMENT_ID = BE.ID";
				foreach ($where as $facetWhere)
				{
					$sqlWhere = $this->whereToSql($facetWhere, "FC");
					if ($sqlWhere)
						$sqlSearch[] = $sqlWhere;
				}
			}
			elseif (count($where) <= 5)
			{
				$subJoin = "";
				$subWhere = "";
				$i = 0;
				foreach ($where as $facetWhere)
				{
					if ($i == 0)
						$subJoin .= "FROM ".$this->storage->getTableName()." FC$i\n";
					else
						$subJoin .= "INNER JOIN ".$this->storage->getTableName()." FC$i ON FC$i.ELEMENT_ID = FC0.ELEMENT_ID\n";

					$sqlWhere = $this->whereToSql($facetWhere, "FC$i");
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
						SELECT FC0.ELEMENT_ID
						$subJoin
						WHERE
						$subWhere
					) FC on FC.ELEMENT_ID = BE.ID
				";
			}
			else
			{
				$condition = array();
				foreach ($where as $facetWhere)
				{
					$sqlWhere = $this->whereToSql($facetWhere, "FC0");
					if ($sqlWhere)
						$condition[] = $sqlWhere;
				}
				$fcJoin = "
						INNER JOIN (
							SELECT FC0.ELEMENT_ID
							FROM ".$this->storage->getTableName()." FC0
							WHERE FC0.SECTION_ID = ".$this->sectionId."
							AND (
							(".implode(")OR(", $condition).")
							)
						GROUP BY FC0.ELEMENT_ID
						HAVING count(DISTINCT FC0.FACET_ID) = ".count($condition)."
						) FC on FC.ELEMENT_ID = BE.ID
					";
			}

			$sqlUnion[] = "
				SELECT
					F.FACET_ID
					,F.VALUE
					,MIN(F.VALUE_NUM) MIN_VALUE_NUM
					,MAX(F.VALUE_NUM) MAX_VALUE_NUM
					".($connection instanceof \Bitrix\Main\DB\MysqlCommonConnection
						?",MAX(case when LOCATE('.', F.VALUE_NUM) > 0 then LENGTH(SUBSTRING_INDEX(F.VALUE_NUM, '.', -1)) else 0 end)"
						:",MAX(".$sqlHelper->getLengthFunction("ABS(F.VALUE_NUM) - FLOOR(ABS(F.VALUE_NUM))")."+1-".$sqlHelper->getLengthFunction("0.1").")"
					)." VALUE_FRAC_LEN
					,COUNT(DISTINCT F.ELEMENT_ID) ELEMENT_COUNT
				FROM
					".$this->storage->getTableName()." F
					INNER JOIN (
						SELECT BE.ID
						FROM
							".($elementFrom
								?$elementFrom
								:"b_iblock_element BE"
							)."
							".$fcJoin."
						WHERE ".implode(" AND ", $sqlSearch)."
						".$elementWhere."
					) E ON E.ID = F.ELEMENT_ID
				WHERE
					F.SECTION_ID = ".$this->sectionId."
					and F.FACET_ID in (".implode(",", $facetFilter).")
				GROUP BY
					F.FACET_ID, F.VALUE
			";
		}

		$result = $connection->query(implode("\nUNION ALL\n", $sqlUnion));

		return $result;
	}

	/**
	 * Returns array of facets id filtered against $facetTypes.
	 *
	 * @param array $facetTypes Filter types.
	 *
	 * @return integer[]
	 */
	protected function getFacetFilter(array $facetTypes)
	{
		$facetFilter = array();
		foreach ($this->getSectionFilterProperty($this->sectionId) as $propertyId => $propertyType)
		{
			if (!$facetTypes || in_array($propertyType, $facetTypes))
			{
				$facetFilter[] = $propertyId * 2;
			}
		}
		if (!$facetTypes || in_array(Storage::PRICE, $facetTypes))
		{
			foreach ($this->getFilterPrices() as $priceId)
			{
				$facetFilter[] = 1 + $priceId * 2;
			}
		}
		return $facetFilter;
	}

	/**
	 * Returns where condition without facet given.
	 *
	 * @param integer $facetToExclude Facet id.
	 *
	 * @return array
	 */
	protected function getWhere($facetToExclude)
	{
		$where = array();
		foreach ($this->where as $facetWhere)
		{
			if ($facetWhere["FACET_ID"] != $facetToExclude)
				$where[] = $facetWhere;
		}
		return $where;
	}

	/**
	 * Converts structured $where array into sql condition or empty string.
	 *
	 * @param array $where Structured condition.
	 * @param string $tableAlias Table alias to use in sql.
	 * @param string $subsectionsCondition If not empty will be added.
	 *
	 * @return string
	 */
	public function whereToSql(array $where, $tableAlias, $subsectionsCondition = "")
	{
		$sqlWhere = "";
		$sectionCondition = "$tableAlias.SECTION_ID = ".$this->sectionId;
		$facetCondition   = "$tableAlias.FACET_ID = ".$where["FACET_ID"];
		switch ($where["TYPE"])
		{
		case Storage::DICTIONARY:
		case Storage::STRING:
			if ($where["OP"] == "=")
			{
				$sqlWhere = $sectionCondition
					." AND ".$facetCondition
					." AND $tableAlias.VALUE_NUM = 0"
					." AND $tableAlias.VALUE in (".implode(", ", $where["VALUES"]).")"
				;
			}
			break;
		case Storage::NUMERIC:
			if ($where["OP"] == ">=" || $where["OP"] == "<=")
			{
				$sqlWhere = $sectionCondition
					." AND ".$facetCondition
					." AND $tableAlias.VALUE_NUM ".$where["OP"]." ".$where["VALUES"][0]
					." AND $tableAlias.VALUE = 0"
				;
			}
			elseif ($where["OP"] == "><")
			{
				$sqlWhere = $sectionCondition
					." AND ".$facetCondition
					." AND $tableAlias.VALUE_NUM BETWEEN ".$where["VALUES"][0]." AND ".$where["VALUES"][1]
					." AND $tableAlias.VALUE = 0"
				;
			}
			break;
		case Storage::DATETIME:
			if ($where["OP"] == ">=" || $where["OP"] == "<=")
			{
				$sqlWhere = $sectionCondition
					." AND ".$facetCondition
					." AND $tableAlias.VALUE_NUM ".$where["OP"]." ".$where["VALUES"][0]
				;
			}
			elseif ($where["OP"] == "><")
			{
				$sqlWhere = $sectionCondition
					." AND ".$facetCondition
					." AND $tableAlias.VALUE_NUM BETWEEN ".$where["VALUES"][0]." AND ".$where["VALUES"][1]
				;
			}
			elseif ($where["OP"] == "=")
			{
				$sqlWhere = $sectionCondition
					." AND ".$facetCondition
					." AND $tableAlias.VALUE_NUM in (".implode(", ", $where["VALUES"]).")"
				;
			}
			break;
		case Storage::PRICE: //TODO AND FC.VALUE = 0
			if (($where["OP"] == ">=" || $where["OP"] == "<="))
			{
				$sqlWhere = $sectionCondition
					." AND ".$facetCondition
				;
				if ($this->toCurrencyId && $this->convertCurrencyId)
				{
					$sqlOr = array();
					foreach ($this->convertCurrencyId as $currency => $currencyDictionaryId)
					{
						$convertedPrice = \CCurrencyRates::convertCurrency($where["VALUES"][0], $this->toCurrencyId, $currency);
						$sqlOr[] = "($tableAlias.VALUE = $currencyDictionaryId AND $tableAlias.VALUE_NUM ".$where["OP"]." $convertedPrice)";
					}
					$sqlWhere .= " AND (".implode(" OR ", $sqlOr).")";
				}
				else
				{
					$sqlWhere .= " AND $tableAlias.VALUE_NUM ".$where["OP"]." ".$where["VALUES"][0];
				}
			}
			elseif ($where["OP"] == "><")
			{
				$sqlWhere = $sectionCondition
					." AND ".$facetCondition
				;
				if ($this->toCurrencyId && $this->convertCurrencyId)
				{
					$sqlOr = array();
					foreach ($this->convertCurrencyId as $currency => $currencyDictionaryId)
					{
						$convertedPrice1 = \CCurrencyRates::convertCurrency($where["VALUES"][0], $this->toCurrencyId, $currency);
						$convertedPrice2 = \CCurrencyRates::convertCurrency($where["VALUES"][1], $this->toCurrencyId, $currency);
						$sqlOr[] = "($tableAlias.VALUE = $currencyDictionaryId AND $tableAlias.VALUE_NUM BETWEEN $convertedPrice1 AND $convertedPrice2)";
					}
					$sqlWhere .= " AND (".implode(" OR ", $sqlOr).")";
				}
				else
				{
					$sqlWhere .= " AND $tableAlias.VALUE_NUM BETWEEN ".$where["VALUES"][0]." AND ".$where["VALUES"][1];
				}
			}
			break;
		}

		if ($sqlWhere && $subsectionsCondition !== '')
		{
			$sqlWhere .= " and ".$tableAlias.".".$subsectionsCondition;
		}

		return $sqlWhere;
	}

	/**
	 * Returns list of properties IDs linked to the section according their "TYPE".
	 * Property has to be not only linked to the section, but has to be marked as smart filter one.
	 * - N - maps to Indexer::NUMERIC
	 * - S - to Indexer::STRING
	 * - F, E, G, L - to Indexer::DICTIONARY
	 *
	 * @param integer $sectionId Section for with properties will be returned.
	 *
	 * @return integer[]
	 */
	protected function getSectionFilterProperty($sectionId)
	{
		if (!isset($this->sectionFilter[$sectionId]))
		{
			$properties = array();
			foreach(\CIBlockSectionPropertyLink::getArray($this->iblockId, $sectionId) as $PID => $link)
			{
				if($link["SMART_FILTER"] === "Y")
				{
					if ($link["PROPERTY_TYPE"] === "N")
						$properties[$link["PROPERTY_ID"]] = Storage::NUMERIC;
					elseif ($link["USER_TYPE"] === "DateTime")
						$properties[$link["PROPERTY_ID"]] = Storage::DATETIME;
					elseif ($link["PROPERTY_TYPE"] === "S")
						$properties[$link["PROPERTY_ID"]] = Storage::STRING;
					else
						$properties[$link["PROPERTY_ID"]] = Storage::DICTIONARY;
				}
			}
			if ($this->skuIblockId)
			{
				foreach(\CIBlockSectionPropertyLink::getArray($this->skuIblockId, $sectionId) as $PID => $link)
				{
					if($link["SMART_FILTER"] === "Y")
					{
						if ($link["PROPERTY_TYPE"] === "N")
							$properties[$link["PROPERTY_ID"]] = Storage::NUMERIC;
						elseif ($link["USER_TYPE"] === "DateTime")
							$properties[$link["PROPERTY_ID"]] = Storage::DATETIME;
						elseif ($link["PROPERTY_TYPE"] === "S")
							$properties[$link["PROPERTY_ID"]] = Storage::STRING;
					else
						$properties[$link["PROPERTY_ID"]] = Storage::DICTIONARY;
					}
				}
			}
			$this->sectionFilter[$sectionId] = $properties;
		}
		return $this->sectionFilter[$sectionId];
	}

	/**
	 * Sets section context for further filtering.
	 * <p>
	 * Returns this object instance for calls chaining.
	 *
	 * @param integer $sectionId Section identifier.
	 *
	 * @return \Bitrix\Iblock\PropertyIndex\Facet
	 */
	public function setSectionId($sectionId)
	{
		$this->sectionId = intval($sectionId);
		return $this;
	}

	/**
	 * Sets prices for further filtering.
	 * <p>
	 * Returns this object instance for calls chaining.
	 *
	 * @param array $prices Prices identifiers.
	 *
	 * @return \Bitrix\Iblock\PropertyIndex\Facet
	 */
	public function setPrices(array $prices)
	{
		$this->priceFilter = array();
		foreach ($prices as $priceInfo)
		{
			$this->priceFilter[] = (int)$priceInfo["ID"];
		}
		return $this;
	}
	/**
	 * Returns list of price IDs for storing in the index.
	 *
	 * @return integer[]
	 */
	protected function getFilterPrices()
	{
		if (!isset($this->priceFilter))
		{
			$this->priceFilter = array();
			if (self::$catalog)
			{
				$priceList = Catalog\GroupTable::getList(array(
					'select' => array('ID'),
					'order' => array('ID' => 'ASC')
				));
				while($price = $priceList->fetch())
				{
					$this->priceFilter[] = (int)$price['ID'];
				}
				unset($price, $priceList);
			}
		}
		return $this->priceFilter;
	}

	/**
	 * Adds a condition for further filtering.
	 *
	 * @param integer $propertyId Iblock property identifier.
	 * @param string $operator Comparing operator.
	 * @param float $value Value to compare.
	 *
	 * @return void
	 */
	public function addNumericPropertyFilter($propertyId, $operator, $value)
	{
		$facetId = $this->storage->propertyIdToFacetId($propertyId);
		if ($operator == "<=" || $operator == ">=")
		{
			$this->where[$operator.$facetId] = array(
				"TYPE" => Storage::NUMERIC,
				"OP" => $operator,
				"FACET_ID" => $facetId,
				"VALUES" => array(doubleval($value)),
			);
			if (isset($this->where[">=".$facetId]) && isset($this->where["<=".$facetId]))
			{
				$this->where["><".$facetId] = array(
					"TYPE" => Storage::NUMERIC,
					"OP" => "><",
					"FACET_ID" => $facetId,
					"VALUES" => array(
						$this->where[">=".$facetId]["VALUES"][0],
						$this->where["<=".$facetId]["VALUES"][0]
					),
				);
				unset($this->where[">=".$facetId]);
				unset($this->where["<=".$facetId]);
			}
		}
	}

	/**
	 * Adds a condition for further filtering.
	 *
	 * @param integer $priceId Catalog price identifier.
	 * @param string $operator Comparing operator.
	 * @param float $value Value to compare.
	 *
	 * @return void
	 */
	public function addPriceFilter($priceId, $operator, $value)
	{
		$facetId = $this->storage->priceIdToFacetId($priceId);
		if ($operator == "<=" || $operator == ">=")
		{
			$this->where[$operator.$facetId] = array(
				"TYPE" => Storage::PRICE,
				"OP" => $operator,
				"FACET_ID" => $facetId,
				"VALUES" => array(doubleval($value)),
			);
			if (isset($this->where[">=".$facetId]) && isset($this->where["<=".$facetId]))
			{
				$this->where["><".$facetId] = array(
					"TYPE" => Storage::PRICE,
					"OP" => "><",
					"FACET_ID" => $facetId,
					"VALUES" => array(
						$this->where[">=".$facetId]["VALUES"][0],
						$this->where["<=".$facetId]["VALUES"][0]
					),
				);
				unset($this->where[">=".$facetId]);
				unset($this->where["<=".$facetId]);
			}
		}
	}

	/**
	 * Adds a condition for further filtering.
	 *
	 * @param integer $propertyId Iblock property identifier.
	 * @param string $operator Comparing operator.
	 * @param float $value Value to compare.
	 *
	 * @return void
	 */
	public function addDictionaryPropertyFilter($propertyId, $operator, $value)
	{
		$facetId = $this->storage->propertyIdToFacetId($propertyId);
		if ($operator == "=")
		{
			if (isset($this->where[$facetId]))
			{
				$this->where[$facetId]["VALUES"][] = intval($value);
			}
			else
			{
				$this->where[$facetId] = array(
					"TYPE" => Storage::DICTIONARY,
					"OP" => $operator,
					"FACET_ID" => $facetId,
					"VALUES" => array(intval($value)),
				);
			}
		}
	}

	/**
	 * Adds a condition for further filtering.
	 *
	 * @param integer $propertyId Iblock property identifier.
	 * @param string $operator Comparing operator.
	 * @param float $value Timestamp to compare.
	 *
	 * @return void
	 */
	public function addDatetimePropertyFilter($propertyId, $operator, $value)
	{
		$facetId = $this->storage->propertyIdToFacetId($propertyId);
		if ($operator == "<=" || $operator == ">=")
		{
			$this->where[$operator.$facetId] = array(
				"TYPE" => Storage::DATETIME,
				"OP" => $operator,
				"FACET_ID" => $facetId,
				"VALUES" => array(intval($value)),
			);
			if (isset($this->where[">=".$facetId]) && isset($this->where["<=".$facetId]))
			{
				$this->where["><".$facetId] = array(
					"TYPE" => Storage::DATETIME,
					"OP" => "><",
					"FACET_ID" => $facetId,
					"VALUES" => array(
						$this->where[">=".$facetId]["VALUES"][0],
						$this->where["<=".$facetId]["VALUES"][0]
					),
				);
				unset($this->where[">=".$facetId]);
				unset($this->where["<=".$facetId]);
			}
		}
		elseif ($operator == "=")
		{
			if (isset($this->where[$operator.$facetId]))
			{
				$this->where[$operator.$facetId]["VALUES"][] = intval($value);
			}
			else
			{
				$this->where[$operator.$facetId] = array(
					"TYPE" => Storage::DATETIME,
					"OP" => $operator,
					"FACET_ID" => $facetId,
					"VALUES" => array(intval($value)),
				);
			}
		}
	}

	/**
	 * Returns true if not filters were applied.
	 *
	 * @return boolean
	 */
	public function isEmptyWhere()
	{
		return empty($this->where);
	}

	/**
	 * When called subsequent queries will use currency conversion for filtering.
	 * Works only with 'currency' module installed.
	 *
	 * @param string $toCurrencyId Valid currency id for price value passed into addPriceFilter method.
	 * @param array $convertCurrencyId Array of valid currencies ids.
	 *
	 * @return void
	 */
	public function enableCurrencyConversion($toCurrencyId, array $convertCurrencyId)
	{
		if (\Bitrix\Main\Loader::includeModule('currency'))
		{
			$this->toCurrencyId = $toCurrencyId;
			$this->convertCurrencyId = array();
			foreach ($convertCurrencyId as $currency)
			{
				$currencyDictionaryId = $this->dictionary->getStringId($currency, false);
				if ($currency)
					$this->convertCurrencyId[$currency] = $currencyDictionaryId;
			}
		}
	}
}
