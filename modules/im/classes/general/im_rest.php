<?
if(!CModule::IncludeModule('rest'))
	return;

class CIMRestService extends IRestService
{
	public static function OnRestServiceBuildDescription()
	{
		return array(
			'im' => array(
				'im.notify' => array('CIMRestService', 'notify'),
			),
		);
	}

	public static function notify($arParams, $n, $server)
	{
		global $USER;

		$arParams = array_change_key_case($arParams, CASE_UPPER);

		if (!isset($arParams['TYPE']) || !in_array($arParams['TYPE'], Array('USER', 'SYSTEM')))
		{
			$arParams['TYPE'] = 'USER';
		}

		if ($arParams['TYPE'] == 'SYSTEM')
		{
			$result = \CBitrix24App::getList(array(), array('APP_ID' => $server->getAppId()));
			$result = $result->fetch();
			$moduleName = isset($result['APP_NAME'])? $result['APP_NAME']: $result['CODE'];

			$fromUserId = 0;
			$notifyType = IM_NOTIFY_SYSTEM;
			$message = $moduleName."#BR#".$arParams['MESSAGE'];
		}
		else
		{
			$fromUserId = $USER->GetID();
			$notifyType = IM_NOTIFY_FROM;
			$message = $arParams['MESSAGE'];
		}

		$message = trim($message);
		if (strlen($message) <= 0)
		{
			return false;
		}

		$arMessageFields = array(
			"TO_USER_ID" => $arParams['TO'],
			"FROM_USER_ID" => $fromUserId,
			"NOTIFY_TYPE" => $notifyType,
			"NOTIFY_MODULE" => "rest",
			"NOTIFY_EVENT" => "rest_notify",
			"NOTIFY_MESSAGE" => $message,
		);

		return CIMNotify::Add($arMessageFields);
	}
}

?>