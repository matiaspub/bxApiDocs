<?
class CControllerAgent
{
	public static function CleanUp()
	{
		global $DB;
		$DB->Query("DELETE FROM b_controller_log WHERE TIMESTAMP_X < DATE_ADD(now(), INTERVAL -14 DAY)");
		$DB->Query("DELETE FROM b_controller_task WHERE STATUS<>'N' AND DATE_EXECUTE IS NOT NULL AND DATE_EXECUTE < DATE_ADD(now(), INTERVAL -14 DAY)");
		$DB->Query("DELETE FROM b_controller_command WHERE DATE_INSERT < DATE_ADD(now(), INTERVAL -14 DAY)");
		return "CControllerAgent::CleanUp();";
	}

	function _OrderBy($arOrder, $arFields, $obUserFieldsSql = null)
	{
		$arOrderBy = array();
		if(is_array($arOrder))
		{
			foreach($arOrder as $by=>$order)
			{
				$by = strtoupper($by);
				$order = (strtolower($order)=='desc'? 'desc': 'asc');

				if(
					isset($arFields[$by])
					&& isset($arFields[$by]["FIELD_TYPE"])
				)
					$arOrderBy[$by] = $arFields[$by]["FIELD_NAME"].' '.$order;
				elseif(
					isset($obUserFieldsSql)
					&& ($s = $obUserFieldsSql->GetOrder($by))
				)
					$arOrderBy[$by] = $s.' '.$order;
			}
		}

		if(count($arOrderBy))
			return "ORDER BY ".implode(", ", $arOrderBy);
		else
			return "";
	}

	function _Lock($uniq)
	{
		global $DB;

		$db_lock = $DB->Query("SELECT GET_LOCK('".$DB->ForSQL($uniq)."', 0) as L", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$ar_lock = $db_lock->Fetch();

		if($ar_lock["L"]=="1")
			return true;
		else
			return false;
	}

	function _UnLock($uniq)
	{
		global $DB;

		$db_lock = $DB->Query("SELECT RELEASE_LOCK('".$DB->ForSQL($uniq)."') as L", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$ar_lock = $db_lock->Fetch();

		if($ar_lock["L"]=="0")
			return false;
		else
			return true;
	}
}
?>
