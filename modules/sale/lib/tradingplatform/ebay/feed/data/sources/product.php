<?php

namespace Bitrix\Sale\TradingPlatform\Ebay\Feed\Data\Sources;

use Bitrix\Main\SystemException;
use Bitrix\Main\ArgumentNullException;

class Product
	extends DataSource
	implements \Iterator
{
	protected $productFeeds = array();
	protected $currentFeed;

	protected $ebay;
	protected $siteId;

	protected $startPos = 0;
	protected $startProductFeed = 0;

	public function __construct($params)
	{
		$this->ebay = \Bitrix\Sale\TradingPlatform\Ebay\Ebay::getInstance();

		if(!$this->ebay->isActive())
			throw new SystemException("Ebay is not active!".__METHOD__);

		if(!isset($params["SITE_ID"]) || strlen($params["SITE_ID"]) <= 0)
			throw new ArgumentNullException("SITE_ID");

		$this->siteId = $params["SITE_ID"];

		if(!\Bitrix\Main\Loader::includeModule('catalog'))
			throw new SystemException("Can't include module \"Catalog\"! ".__METHOD__);

		$iBlockIds = $this->getIblockIds();

		if(empty($iBlockIds))
			throw new SystemException("Can't find iblocks ids! ".__METHOD__);


		foreach($iBlockIds as $iblockId)
		{
			$this->productFeeds[] = \Bitrix\Catalog\ExportOfferCreator::getOfferObject(
				array(
					"IBLOCK_ID" => $iblockId,
					"PRODUCT_GROUPS" => $this->getMappedGroups($iblockId),
					"XML_DATA" => $this->getXmlData(),
					"SETUP_SERVER_NAME" => $this->getDomainName(),
				)
			);
		}
	}

	protected  function  getIblockIds()
	{
		$result = array();
		$settings = $this->ebay->getSettings();

		if(isset($settings[$this->siteId]["IBLOCK_ID"]) && is_array($settings[$this->siteId]["IBLOCK_ID"]))
			$result = $settings[$this->siteId]["IBLOCK_ID"];

		return $result;
	}

	protected  function  getMappedGroups($iblockId)
	{
		$result = array();
		$catMapEntId = \Bitrix\Sale\TradingPlatform\Ebay\MapHelper::getCategoryEntityId($iblockId);

		$catRes = \Bitrix\Sale\TradingPlatform\MapTable::getList(array(
			'select' => array('VALUE_INTERNAL'),
			'filter' => array('=ENTITY_ID' => $catMapEntId),
			'group' => array('VALUE_INTERNAL')
		));

		while($category = $catRes->fetch())
			$result[] = $category["VALUE_INTERNAL"];

		return $result;
	}

	protected  function getXmlData()
	{
		return array();
	}

	protected  function getDomainName()
	{
		$result = "";
		$settings = $this->ebay->getSettings();

		if(isset($settings[$this->siteId]["DOMAIN_NAME"]) && is_array($settings[$this->siteId]["DOMAIN_NAME"]))
			$result = $settings[$this->siteId]["DOMAIN_NAME"];

		return $result;
	}

	public function setStartPosition($startPos = "")
	{
		if(strlen($startPos) > 3) // format: iBlockId_RecordNumber
		{
			$positions = explode("_", $startPos);

			if(isset($positions[0]) && isset($positions[1]))
			{
				$this->startProductFeed = $positions[0];
				$this->startPos = $positions[1];
			}
		}
	}

	//Proxy offers iterator methods
	public function current()
	{
		return $this->productFeeds[$this->currentFeed]->current();
	}

	public function key()
	{
		return $this->currentFeed."_".$this->productFeeds[$this->currentFeed]->key();
	}

	public function next()
	{
		$this->productFeeds[$this->currentFeed]->next();

		if(!$this->valid() && $this->currentFeed < count($this->productFeeds)-1)
		{
			$this->currentFeed++;
			$this->next();
		}
	}

	public function rewind()
	{
		$this->currentFeed = $this->startProductFeed;

		foreach($this->productFeeds as $feed)
			$feed->rewind();

		for($i = 0; $i < $this->startPos; $i++)
			$this->productFeeds[$this->currentFeed]->next();
	}

	public function valid()
	{
		return $this->productFeeds[$this->currentFeed]->valid();
	}
} 