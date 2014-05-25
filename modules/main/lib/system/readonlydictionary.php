<?php
namespace Bitrix\Main\System;

class ReadonlyDictionary
	extends Dictionary
{
	/**
	 * Offset to set
	 */
	static public function offsetSet($offset, $value)
	{
		throw new \Bitrix\Main\NotSupportedException("Can not set readonly value");
	}

	/**
	 * Offset to unset
	 */
	static public function offsetUnset($offset)
	{
		throw new \Bitrix\Main\NotSupportedException("Can not unset readonly value");
	}
}