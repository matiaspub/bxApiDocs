<?php

namespace Bitrix\Sale\TradingPlatform\Ebay\Feed\Data\Sources;

class SimpleArray extends DataSource implements \Iterator
{
	protected $data = array();
	protected $currentPos = 0;

	public function current()
	{
		return $this->data[$this->currentPos];
	}

	public function key()
	{
		return $this->currentPos;
	}

	public function next()
	{
		$this->currentPos++;
	}

	public function rewind()
	{
		$this->currentPos = 0;
	}

	public function valid()
	{
		return isset($this->data[$this->currentPos]) && !empty($this->data[$this->currentPos]);
	}

	public function setData(array $data)
	{
		$this->data = $data;
	}
} 