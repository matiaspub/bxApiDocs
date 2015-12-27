<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\PropertyIndex;
use Bitrix\Catalog;

class Indexer
{
	protected $iblockId = 0;
	protected static $catalog = null;
	protected $skuIblockId = 0;
	protected $skuPropertyId = 0;
	protected $sectionParents = array();
	protected $propertyFilter = null;
	protected $priceFilter = null;

	/** @var Dictionary */
	protected $dictionary = null;
	/** @var Storage */
	protected $storage = null;

	/**
	 * @param integer $iblockId Information block identifier.
	 */
	public function __construct($iblockId)
	{
		$this->iblockId = intval($iblockId);
	}

	/**
	 * Initializes internal object state. Must be called before usage.
	 *
	 * @throws \Bitrix\Main\LoaderException
	 * @return void
	 */
	public function init()
	{
		$this->dictionary = new Dictionary($this->iblockId);
		$this->storage = new Storage($this->iblockId);
		if (self::$catalog === null)
		{
			self::$catalog = \Bitrix\Main\Loader::includeModule("catalog");
		}

		if (self::$catalog)
		{
			$catalog = \CCatalogSKU::getInfoByProductIBlock($this->iblockId);
			if (!empty($catalog) && is_array($catalog))
			{
				$this->skuIblockId = $catalog["IBLOCK_ID"];
				$this->skuPropertyId = $catalog["SKU_PROPERTY_ID"];
			}
		}
	}

	/**
	 * Checks if storage and dictionary exists in the database.
	 * Returns true on success.
	 *
	 * @return boolean
	 */
	public function isExists()
	{
		return $this->storage->isExists() && $this->dictionary->isExists();
	}

	/**
	 * Drops and recreates the index. So one can start indexing.
	 *
	 * @return boolean
	 */
	public function startIndex()
	{
		if ($this->storage->isExists())
			$this->storage->drop();
		if ($this->dictionary->isExists())
			$this->dictionary->drop();

		$this->dictionary->create();
		$this->storage->create();

		return true;
	}

	/**
	 * End of index creation. Marks iblock as indexed.
	 *
	 * @return boolean
	 */
	public function endIndex()
	{
		\Bitrix\Iblock\IblockTable::update($this->iblockId, array(
			"PROPERTY_INDEX" => "Y",
		));
		if ($this->skuIblockId)
		{
			\Bitrix\Iblock\IblockTable::update($this->skuIblockId, array(
				"PROPERTY_INDEX" => "Y",
			));
		}

		return true;
	}

	/**
	 * Does index step. Returns number of indexed elements.
	 *
	 * @param integer $interval Time limit for execution.
	 * @return integer
	 */
	public function continueIndex($interval = 0)
	{
		if ($interval > 0)
			$endTime = microtime(true) + $interval;
		else
			$endTime = 0;

		$indexedCount = 0;
		$lastElementID = $this->storage->getLastStoredElementId();
		$elementList = $this->getElementsCursor($lastElementID);
		while ($element = $elementList->fetch())
		{
			$this->indexElement($element["ID"]);
			$indexedCount++;
			if ($endTime > 0 && $endTime < microtime(true))
				break;
		}
		return $indexedCount;
	}

	/**
	 * Returns number of elements to be indexed.
	 *
	 * @return integer
	 */
	public function estimateElementCount()
	{
		$filter = array(
			"IBLOCK_ID" => $this->iblockId,
			"ACTIVE" => "Y",
			"CHECK_PERMISSIONS" => "N",
		);

		return \CIBlockElement::getList(array(), $filter, array());
	}

	/**
	 * Indexes one element.
	 *
	 * @param integer $elementId Element identifier.
	 *
	 * @return void
	 */
	public function indexElement($elementId)
	{
		$element = new Element($this->iblockId, $elementId);
		$element->loadFromDatabase();

		$elementSections = $element->getSections();
		$elementIndexValues = $this->getSectionIndexEntries($element);
		
		foreach ($element->getParentSections() as $sectionId)
		{
			foreach ($elementIndexValues as $facetId => $values)
			{
				foreach ($values as $value)
				{
					$this->storage->addIndexEntry(
						$sectionId,
						$elementId,
						$facetId,
						$value["VALUE"],
						$value["VALUE_NUM"],
						in_array($sectionId, $elementSections)
					);
				}
			}
		}

		foreach ($elementIndexValues as $facetId => $values)
		{
			foreach ($values as $value)
			{
				$this->storage->addIndexEntry(
					0,
					$elementId,
					$facetId,
					$value["VALUE"],
					$value["VALUE_NUM"],
					empty($elementSections)
				);
			}
		}
	}

	/**
	 * Removes element from the index.
	 *
	 * @param integer $elementId Element identifier.
	 *
	 * @return void
	 */
	public function deleteElement($elementId)
	{
		$this->storage->deleteIndexElement($elementId);
	}

	/**
	 * Returns elements list database cursor for indexing.
	 * This list contains only active elements,
	 * starts with $lastElementID and ID in ascending order.
	 *
	 * @param integer $lastElementID Element identifier.
	 * @return \CIBlockResult
	 */
	protected function getElementsCursor($lastElementID = 0)
	{
		$filter = array(
			"IBLOCK_ID" => $this->iblockId,
			"ACTIVE" => "Y",
			"CHECK_PERMISSIONS" => "N",
		);

		if ($lastElementID > 0)
		{
			$filter[">ID"] = $lastElementID;
		}

		return \CIBlockElement::getList(array("ID" => "ASC"), $filter, false, false, array("ID"));
	}

	/**
	 * Returns all relevant information for the element in section context.
	 *
	 * @param Element $element Loaded from the database element information.
	 *
	 * @return array
	 */
	protected function getSectionIndexEntries(Element $element)
	{
		$result = array(
			1 => array( //Section binding
				array("VALUE" => 0, "VALUE_NUM" => 0.0)
			)
		);

		foreach ($this->getFilterProperty(Storage::DICTIONARY) as $propertyId)
		{
			$facetId = $this->storage->propertyIdToFacetId($propertyId);
			$result[$facetId] = array();
			$propertyValues = $element->getPropertyValues($propertyId);
			foreach ($propertyValues as $value)
			{
				$value = intval($value);
				$result[$facetId][$value] = array(
					"VALUE" => $value,
					"VALUE_NUM" => 0.0,
				);
			}
		}

		foreach ($this->getFilterProperty(Storage::STRING) as $propertyId)
		{
			$facetId = $this->storage->propertyIdToFacetId($propertyId);
			$result[$facetId] = array();
			$propertyValues = $element->getPropertyValues($propertyId);
			foreach ($propertyValues as $value)
			{
				$valueId = $this->dictionary->getStringId($value);
				$result[$facetId][$valueId] = array(
					"VALUE" => $valueId,
					"VALUE_NUM" => 0.0,
				);
			}
		}

		foreach ($this->getFilterProperty(Storage::NUMERIC) as $propertyId)
		{
			$facetId = $this->storage->propertyIdToFacetId($propertyId);
			$result[$facetId] = array();
			$propertyValues = $element->getPropertyValues($propertyId);
			foreach ($propertyValues as $value)
			{
				$value = doubleval($value);
				$result[$facetId][md5($value)] = array(
					"VALUE" => 0,
					"VALUE_NUM" => $value,
				);
			}
		}

		foreach ($this->getFilterProperty(Storage::DATETIME) as $propertyId)
		{
			$facetId = $this->storage->propertyIdToFacetId($propertyId);
			$result[$facetId] = array();
			$propertyValues = $element->getPropertyValues($propertyId);
			foreach ($propertyValues as $value)
			{
				//Save date only based on server time.
				$timestamp = MakeTimeStamp($value, "YYYY-MM-DD HH:MI:SS");
				$value = date('Y-m-d', $timestamp);
				$timestamp = MakeTimeStamp($value, "YYYY-MM-DD");
				$valueId = $this->dictionary->getStringId($value);
				$result[$facetId][$valueId] = array(
					"VALUE" => $valueId,
					"VALUE_NUM" => $timestamp,
				);
			}
		}

		foreach ($this->getFilterPrices() as $priceId)
		{
			$facetId = $this->storage->priceIdToFacetId($priceId);
			$result[$facetId] = array();
			$elementPrices = $element->getPriceValues($priceId);
			if ($elementPrices)
			{
				foreach ($elementPrices as $currency => $priceValues)
				{
					$currencyId = $this->dictionary->getStringId($currency);
					foreach ($priceValues as $price)
					{
						$result[$facetId][$currencyId.":".$price] = array(
							"VALUE" => $currencyId,
							"VALUE_NUM" => $price,
						);
					}
				}
			}
		}

		return array_filter($result, "count");
	}

	/**
	 * Returns list of properties IDs marked as indexed to the section according their "TYPE".
	 * - N - maps to Indexer::NUMERIC
	 * - S - to Indexer::STRING
	 * - F, E, G, L - to Indexer::DICTIONARY
	 *
	 * @param integer $propertyType Property classification for the index.
	 *
	 * @return integer[]
	 */
	protected function getFilterProperty($propertyType)
	{
		if (!isset($this->propertyFilter))
		{
			$this->propertyFilter = array(
				Storage::DICTIONARY => array(),
				Storage::STRING => array(),
				Storage::NUMERIC => array(),
				Storage::DATETIME => array(),
			);
			$propertyList = \Bitrix\Iblock\SectionPropertyTable::getList(array(
				"select" => array("PROPERTY_ID", "PROPERTY.PROPERTY_TYPE", "PROPERTY.USER_TYPE"),
				"filter" => array(
					"=IBLOCK_ID" => array($this->iblockId, $this->skuIblockId),
					"=SMART_FILTER" => "Y",
				),
			));
			while ($link = $propertyList->fetch())
			{
				if ($link["IBLOCK_SECTION_PROPERTY_PROPERTY_PROPERTY_TYPE"] === "N")
					$this->propertyFilter[Storage::NUMERIC][] = $link["PROPERTY_ID"];
				elseif ($link["IBLOCK_SECTION_PROPERTY_PROPERTY_USER_TYPE"] === "DateTime")
					$this->propertyFilter[Storage::DATETIME][] = $link["PROPERTY_ID"];
				elseif ($link["IBLOCK_SECTION_PROPERTY_PROPERTY_PROPERTY_TYPE"] === "S")
					$this->propertyFilter[Storage::STRING][] = $link["PROPERTY_ID"];
				else
					$this->propertyFilter[Storage::DICTIONARY][] = $link["PROPERTY_ID"];
			}
		}
		return $this->propertyFilter[$propertyType];
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
}
