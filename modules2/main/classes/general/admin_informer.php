<?
IncludeModuleLangFile(__FILE__);

class CAdminInformer
{
	private static $items=array();
	public static $alertCounter = 0;

	/**
	 * Adds items to admin informer
	 * @param array (
	 *			string TITLE  - item title (mandatory),
	 *			string HTML - item's body html (mandatory),
	 *			string FOOTER - item footer,
	 *			string LINK - were to go after click ,
	 *			bool ALERT - true || false - increment or not notification counter in the admin panel,
	 *			string COLOR green || blue || gray || custom... For custom you must define right css styles
	 *						 for css class adm-informer-item-custom.
	 *			int SORT - less value higher message. default value 20. if ALERT then SORT = 10
	 *			)
	 * @return mix items count after adding item or false
	 */
	public static function AddItem($arParams)
	{
		if(!isset($arParams["TITLE"]) || empty($arParams["TITLE"]))
			return false;

		if(!isset($arParams["HTML"]) || empty($arParams["HTML"]))
			return false;

		$item = array(
			"TITLE" => $arParams["TITLE"],
			"HTML" => $arParams["HTML"],
			"FOOTER" => isset($arParams["FOOTER"]) ? $arParams["FOOTER"] : false,
			"LINK" => isset($arParams["LINK"]) ? $arParams["LINK"] : false,
			"ALERT" => isset($arParams["ALERT"]) ? $arParams["ALERT"] : false,
			"COLOR" => isset($arParams["COLOR"]) ? $arParams["COLOR"] : "green",
		);
		if($arParams["ALERT"])
		{
			$item["SORT"] = 10;
			self::$alertCounter++;
		}
		else
			$item["SORT"] = isset($arParams["SORT"]) ? $arParams["SORT"] : 20;

		self::$items[] = $item;

		return count(self::$items)-1;
	}

	private static function PrintItemHtml($itemIdx, $bVisible)
	{
		$itemHtml = '
		<div class="adm-informer-item'.(self::$items[$itemIdx]["COLOR"] ? " adm-informer-item-".self::$items[$itemIdx]["COLOR"] : '').'" style="display:'.($bVisible ? 'block' : 'none').'"'.(self::$items[$itemIdx]["LINK"] ? ' onclick="location.href=\''.self::$items[$itemIdx]["LINK"].'\';"' : '' ).'>
			<div class="adm-informer-item-title">
				'.self::$items[$itemIdx]["TITLE"].'
			</div>
			<div class="adm-informer-item-body">
				<div class="adm-informer-item-html" id="adm-informer-item-html-'.$itemIdx.'">
					'.self::$items[$itemIdx]["HTML"].'
					<span class="adm-informer-icon"></span>
				</div>';

		if(self::$items[$itemIdx]["FOOTER"]!=false && !empty(self::$items[$itemIdx]["FOOTER"]))
		{
			$itemHtml .= '
				<div class="adm-informer-item-footer" id="adm-informer-item-footer-'.$itemIdx.'">
				'.self::$items[$itemIdx]["FOOTER"].'
				</div>';
		}

		$itemHtml .= '
			</div>
		</div>';

		return $itemHtml;
	}

	public static function PrintHtmlPublic($visCountParam = 3)
	{
		if(!$GLOBALS["APPLICATION"]->PanelShowed)
			return "";

		return self::PrintHtml($visCountParam);
	}

	public static function PrintHtml($visCountParam = 3)
	{
		sortByColumn(self::$items, "SORT");

		$itemsCount = 0;
		$visibleCount = 0;

		$div = '
<div class="adm-informer" id="admin-informer" style="display: none; top:48px; left:316px;" onclick="return BX.adminInformer.OnInnerClick(event);">
	<div class="adm-informer-header">'.GetMessage("MAIN_AI_NEW_NOTIF").'</div>';

		foreach (self::$items as $key => $item)
		{
			if( $itemsCount < $visCountParam || $item["ALERT"])
			{
				$bVisible = true;
				$visibleCount++;
			}
			else
				$bVisible = false;

			$div .= self::PrintItemHtml($key, $bVisible);

			$itemsCount++;
		}

		$div .= '
	<a href="javascript:void(0);" class="adm-informer-footer adm-informer-footer-collapsed" hidefocus="true" id="adm-informer-footer" onclick="return BX.adminInformer.ToggleExtra();" '.( $itemsCount <= $visibleCount ? ' style="display:none;"' : '' ).'>'.GetMessage('MAIN_AI_ALL_NOTIF').' ('.$itemsCount.') </a>
	<span class="adm-informer-arrow"></span>
</div>

<script type="text/javascript">
	BX.ready( function(){BX.adminInformer.Init('.$visibleCount.'); } );
</script>';

	return $div;
	}

	private static function CutErrorId($sError)
	{
		return preg_replace('/\[.*\]/', '', $sError);
	}

	private static function IsUpdateSystemNeedUpdate($sError)
	{
		return strpos($sError, 'NEW_UPDATE_SYSTEM');
	}

	public static function InsertMainItems()
	{
		if(defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1)
			return false;

		global $USER;

		if(!$USER->IsAuthorized())
			return false;

		//Updates
		if($USER->IsAdmin() || $USER->CanDoOperation('install_updates'))
		{
			$update_res = UpdateTools::GetUpdateResult();

			$updAIParams = array(
				"TITLE" => GetMessage("top_panel_ai_updates"),
				"COLOR" => 'gray',
				"SORT" => 12
			);

			//update_autocheck == true and we have something to show
			if ($update_res['result'] === true && $update_res['tooltip'])
			{
				$updAIParams["HTML"] = $update_res['tooltip'];
				$updAIParams["FOOTER"] = '<a href="/bitrix/admin/update_system.php?lang='.LANGUAGE_ID.'">'.GetMessage("top_panel_ai_upd_instl").'</a>';
				$updAIParams["ALERT"] = true;
			}
			else if ($update_res['error'] <> '') // update error
			{
				$updAIParams["TITLE"] .= " - ".GetMessage("top_panel_ai_title_err");
				$updAIParams["HTML"] = trim(self::CutErrorId($update_res['error']));
				$updAIParams["FOOTER"] = '<a href="/bitrix/admin/update_system.php?refresh=Y&lang='.LANGUAGE_ID.'">'.GetMessage("top_panel_ai_upd_chk").'</a>';
				$updAIParams["ALERT"] = true;
			}
			else // update_autocheck == false
			{
				//last update date time
				$updateDate = COption::GetOptionString("main", "update_system_update", false);
				// remove seconds
				$updateDate = $updateDate ? CDatabase::FormatDate($updateDate, "DD.MM.YYYY HH:MI:SS", "DD.MM.YYYY HH:MI") : false;

				$updAIParams["HTML"] = '<span class="adm-informer-strong-text">'.GetMessage("top_panel_ai_sys_ver").' '.SM_VERSION."</span><br>";
				$updAIParams["HTML"] .= $updateDate ? GetMessage("top_panel_ai_upd_last").'<br>'.$updateDate : GetMessage("top_panel_ai_upd_never");
				$updAIParams["FOOTER"] = '<a href="/bitrix/admin/update_system.php?refresh=Y&lang='.LANGUAGE_ID.'">'.GetMessage("top_panel_ai_upd_chk").'</a>';
				$updAIParams["ALERT"] = false;
			}

			self::AddItem($updAIParams);
		}

		//Disk space (quota)
		$maxQuota = COption::GetOptionInt("main", "disk_space", 0)*1048576;
		if ($maxQuota > 0)
		{
			$quota = new CDiskQuota();
			$free = $quota->GetDiskQuota();
			$freeMB = CFile::FormatSize($free,1);
			$free = round($free/($maxQuota)*100);

			$qAIParams = array(
				"TITLE" => GetMessage("top_panel_ai_used_space"),
				"COLOR" => "green",
				"ALERT" => ($free < 10 ? true : false)
				);

			$qAIParams["HTML"] = '
	<div class="adm-informer-item-section">
		<span class="adm-informer-item-l"><span class="adm-informer-strong-text">'.GetMessage("top_panel_ai_in_all").'</span> '.CFile::FormatSize($maxQuota,1).'</span>
		<span class="adm-informer-item-r"><span class="adm-informer-strong-text">'.GetMessage("top_panel_ai_in_aviable").'</span> '.$freeMB.'</span>
	</div>
	<div class="adm-informer-status-bar-block" >
		<div class="adm-informer-status-bar-indicator" style="width:'.(100-$free).'%; "></div>
		<div class="adm-informer-status-bar-text">'.(100-$free).'%</div>
	</div>
	<div class="adm-informer-item-section"><span class="adm-informer-strong-text">'.GetMessage("top_panel_ai_in_recomend").'</span> '.GetMessage("top_panel_ai_in_no").'</div>';

			self::AddItem($qAIParams);
		}

		foreach(GetModuleEvents("main", "OnAdminInformerInsertItems", true) as $arHandler)
			ExecuteModuleEventEx($arHandler);

		return count(self::$items);
	}
}
?>
