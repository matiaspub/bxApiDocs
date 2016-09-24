<?php

namespace Bitrix\Conversion;

final class CounterManager extends Internals\TypeManager
{
	static protected $event = 'OnGetCounterTypes';
	static protected $types = array();
	static protected $ready = false;
	static protected $checkModule = true;
}
