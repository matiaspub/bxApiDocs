<?php

namespace Bitrix\Sale\TradingPlatform\Ebay\Feed\Data\Sources;

abstract class DataSource implements \Iterator
{
	static public function setStartPosition($startPosition) { return true; }
	static public function setData(array $data) { return true; }
} 