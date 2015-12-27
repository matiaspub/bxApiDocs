<?php

namespace Bitrix\Sale\TradingPlatform\Ebay\Feed\Data\Processors;

abstract class DataProcessor
{
	abstract public function process($data);
}