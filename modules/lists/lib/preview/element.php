<?

namespace Bitrix\Lists\Preview;

class Element
{
	public static function buildPreview($parameters)
	{
		global $APPLICATION;

		ob_start();
		$APPLICATION->IncludeComponent(
			'bitrix:lists.element.preview',
			'',
			$parameters
		);
		return ob_get_clean();
	}

	public static function checkUserReadAccess($parameters)
	{
		global $USER;

		$parameters['listId'] = (int)$parameters['listId'];
		$parameters['elementId'] = (int)$parameters['elementId'];

		if($parameters['listId'] == 0 || $parameters['elementId'] == 0)
			return false;

		$userPermission = \CListPermissions::CheckAccess(
			$USER,
			$parameters["IBLOCK_TYPE_ID"],
			$parameters['listId']
		);
		if($userPermission < 0)
		{
			return false;
		}
		else if(   $userPermission < \CListPermissions::CAN_READ
				&& !\CIBlockElementRights::UserHasRightTo($parameters['listId'], $parameters['elementId'], "element_read"))
		{
			return false;
		}

		return true;
	}

}