<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;

class DedupeDataSourceResult
{
	protected $processedItemCount = 0;
	/** @var Duplicate[]*/
	protected $items = array();

	public function addItem($key, Duplicate $item)
	{
		$this->items[$key] = $item;
	}
	/**
	 * @return Boolean
	 */
	public function hasItem($key)
	{
		return isset($this->items[$key]);
	}
	/**
	 * @return Duplicate
	 */
	public function getItem($key)
	{
		return isset($this->items[$key]) ? $this->items[$key] : null;
	}
	public function removeItem($key)
	{
		unset($this->items[$key]);
	}
	/**
	 * @return Duplicate[]
	 */
	public function getItems()
	{
		return $this->items;
	}
	public function getProcessedItemCount()
	{
		return $this->processedItemCount;
	}
	public function setProcessedItemCount($count)
	{
		$this->processedItemCount = $count;
	}
	/**
	 * @return DuplicateEntityRanking[]
	 */
	public function getAllRankings()
	{
		$result = array();
		foreach($this->items as $item)
		{
			$result = array_merge($result, $item->getAllRankings());
		}
		return $result;
	}
}