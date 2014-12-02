<?
namespace Bitrix\Scale;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
* Class Logger
* Makes records to bitrix-system site log
* @package Bitrix\Scale
*/
class Logger
{
	const LOG_LEVEL_DISABLE = 0;
	const LOG_LEVEL_ERROR = 10;
	const LOG_LEVEL_INFO = 20;
	const LOG_LEVEL_DEBUG = 30;

	/**
	 * @param $level
	 * @param $auditType
	 * @param $itemId
	 * @param $description
	 * @return bool
	 */
	public static function addRecord($level, $auditType, $itemId, $description)
	{
		if($level == self::LOG_LEVEL_ERROR)
			$severity = "ERROR";
		elseif($level == self::LOG_LEVEL_INFO)
			$severity = "INFO";
		elseif($level == self::LOG_LEVEL_DEBUG)
			$severity = "DEBUG";
		else
			$severity = "UNKNOWN";

		\CEventLog::Add(array(
			"SEVERITY" => $severity,
			"AUDIT_TYPE_ID" => $auditType,
			"MODULE_ID" => "scale",
			"ITEM_ID" => $itemId,
			"DESCRIPTION" => $description,
		));

		return true;
	}

	/**
	 * @return array
	 */
	public static function onEventLogGetAuditTypes()
	{
		return array(
			"SCALE_ACTION_STARTED" => "[SCALE_ACTION_STARTED] ".Loc::getMessage("SCALE_ACTION_EVENT_LOG_TYPE_ACTION_STARTED"),
			"SCALE_ACTION_RESULT" => "[SCALE_ACTION_RESULT] ".Loc::getMessage("SCALE_ACTION_EVENT_LOG_TYPE_ACTION_RESULT"),
			"SCALE_ACTION_ERROR" => "[SCALE_ACTION_ERROR] ".Loc::getMessage("SCALE_ACTION_EVENT_LOG_TYPE_ACTION_ERROR"),
			"SCALE_ACTION_OUTPUT" => "[SCALE_ACTION_OUTPUT] ".Loc::getMessage("SCALE_ACTION_EVENT_LOG_TYPE_ACTION_OUTPUT"),
			"SCALE_ACTION_CHECK_STATE" => "[SCALE_ACTION_CHECK_STATE] ".Loc::getMessage("SCALE_ACTION_EVENT_LOG_TYPE_ACTION_CHECK_STATE"),
			"SCALE_PROVIDER_SEND_ORDER" => "[SCALE_PROVIDER_SEND_ORDER] ".Loc::getMessage("SCALE_ACTION_EVENT_LOG_TYPE_PROVIDER_SEND_ORDER")
		);
	}
}
