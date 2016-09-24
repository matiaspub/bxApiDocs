<?
namespace Bitrix\Sale\Location\Util;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

class SaleTreeNodeNotFoundException extends Main\SystemException {

	static public function __construct($message = "", $code = 0)
	{
		parent::_construct(strlen($message) ? $message : Loc::getMessage('SALE_TREE_ENTITY_NODE_NOT_FOUND_EXCEPTION'), $code);
	}

}

class SaleTreeSystemException extends Main\SystemException {

	static public function __construct($message = "", $code = 0)
	{
		parent::_construct(strlen($message) ? $message : Loc::getMessage('SALE_TREE_ENTITY_INTERNAL_EXCEPTION'), $code);
	}

}
