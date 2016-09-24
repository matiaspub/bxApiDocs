<?php
namespace Bitrix\Main\Web\DOM;

class DomException extends \Bitrix\Main\SystemException
{
	const INDEX_SIZE_ERR = 1;
	const DOMSTRING_SIZE_ERR = 2;
	const HIERARCHY_REQUEST_ERR = 3;
	const WRONG_DOCUMENT_ERR = 4;
	const INVALID_CHARACTER_ERR = 5;
	const NO_DATA_ALLOWED_ERR = 6;
	const NO_MODIFICATION_ALLOWED_ERR = 7;
	const NOT_FOUND_ERR = 8;
	const NOT_SUPPORTED_ERR = 9;
	const INUSE_ATTRIBUTE_ERR = 10;
	// Introduced in DOM Level 2:
	const INVALID_STATE_ERR = 11;
	// Introduced in DOM Level 2:
	const SYNTAX_ERR = 12;
	// Introduced in DOM Level 2:
	const INVALID_MODIFICATION_ERR = 13;
	// Introduced in DOM Level 2:
	const NAMESPACE_ERR = 14;
	// Introduced in DOM Level 2:
	const INVALID_ACCESS_ERR = 15;
	// Introduced in DOM Level 3:
	const VALIDATION_ERR = 16;

	static public function __construct($message = "", $code = 0, \Exception $previous = null)
	{
		parent::__construct($message, $code, '', '', $previous);
	}
}