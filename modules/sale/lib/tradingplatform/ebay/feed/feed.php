<?php

namespace Bitrix\Sale\TradingPlatform\Ebay\Feed;

use Bitrix\Main\ArgumentException;
use \Bitrix\Sale\TradingPlatform\Timer;
use \Bitrix\Sale\TradingPlatform\TimeIsOverException;

class Feed
{
	/** @var  Data\Converters\DataConverter $dataConvertor */
	protected $dataConvertor;
	/** @var  Data\Sources\DataSource $sourceDataIterator */
	protected $sourceDataIterator;
	/** @var  Data\Processors\DataProcessor $dataProcessor */
	protected $dataProcessor;

	/** @var \Bitrix\Sale\TradingPlatform\Timer|null $timer */
	protected $timer = null;

	public function __construct($params)
	{
		if(isset($params["TIMER"]) && $params["DATA_SOURCE"] instanceof Timer)
			$this->timer = $params["TIMER"];

		if(!isset($params["DATA_SOURCE"]) || (!($params["DATA_SOURCE"] instanceof Data\Sources\DataSource)))
			throw new ArgumentException("DATA_SOURCE must be instanceof DataSource!", "DATA_SOURCE");

		if(!isset($params["DATA_CONVERTER"]) || (!($params["DATA_CONVERTER"] instanceof Data\Converters\DataConverter)))
			throw new ArgumentException("DATA_CONVERTER must be instanceof DataConverter!", "DATA_CONVERTER");

		if(!isset($params["DATA_PROCESSOR"]) || (!($params["DATA_PROCESSOR"] instanceof Data\Processors\DataProcessor)))
			throw new ArgumentException("DATA_PROCESSOR must be instanceof DataProcessor!", "DATA_PROCESSOR");

		$this->sourceDataIterator = $params["DATA_SOURCE"];
		$this->dataConvertor = $params["DATA_CONVERTER"];
		$this->dataProcessor = $params["DATA_PROCESSOR"];
	}

	public function processData($startPosition = "")
	{
		$this->sourceDataIterator->setStartPosition($startPosition);

		foreach($this->sourceDataIterator as $position => $data)
		{
			$convertedData = $this->dataConvertor->convert($data);

			$this->dataProcessor->process($convertedData);

			if ($this->timer !== null && !$this->timer->check())
				throw new TimeIsOverException("Timelimit is over", $position);
		}
	}

	public function setSourceData($data)
	{
		$this->sourceDataIterator->setData($data);
	}
}