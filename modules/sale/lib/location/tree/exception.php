<?
namespace Bitrix\Sale\Location\Tree;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Exception extends \Bitrix\Sale\Location\Exception
{
	static public function getDefaultMessage()
	{
		return Loc::getMessage('SALE_TREE_ENTITY_EXCEPTION');
	}

	protected function fillMessageAdditions()
	{
		$message = '';

		$aInfo = $this->getAdditionalInfo();

		if(isset($aInfo['ID']))
		{
			$message .= ' (ID = '.intval($aInfo['ID']).')';
		}
		if(isset($aInfo['CODE']))
		{
			$message .= ' (CODE = '.intval($aInfo['CODE']).')';
		}

		return $message;
	}
}

class NodeNotFoundException extends \Bitrix\Sale\Location\Tree\Exception
{
	static public function getDefaultMessage()
	{
		return Loc::getMessage('SALE_TREE_ENTITY_NODE_NOT_FOUND_EXCEPTION').static::fillMessageAdditions();
	}
}

class NodeIncorrectException extends \Bitrix\Sale\Location\Tree\Exception
{
	static public function getDefaultMessage()
	{
		return 'Incorrect LEFT_MARGIN or RIGHT_MARGIN (wrong data given or tree structure integrity seems to be compromised)'.static::fillMessageAdditions();
	}
}