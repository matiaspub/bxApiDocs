<?php
namespace Bitrix\Main;

/**
 * Exception is thrown when object property is not valid.
 */
class ObjectPropertyException extends ArgumentException
{
	static public function __construct($parameter = "", \Exception $previous = null)
	{
		parent::__construct("Object property \"".$parameter."\" not found.", $parameter, $previous);
	}
}
