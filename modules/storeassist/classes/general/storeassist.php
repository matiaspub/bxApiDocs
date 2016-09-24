<?
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class CStoreAssist
{
	static $arAllPageId = array(
		"currencies",
		"cat_group_admin",
		"cat_measure_list",
		"sale_report_edit",
		"sale_person_type",
		"locations",
		"sale_buyers",
		"sale_status",
		"cat_store_list",
		"storeassist_social",
		"cat_product_list",
		"quantity",
		"cat_store_document_list",
		"order_setting",
		"reserve_setting",
		"storeassist_1c_catalog_fill",
		"1c_integration",
		"storeassist_1c_unloading",
		"1c_exchange",
		"storeassist_1c_exchange_realtime",
		"storeassist_1c_small_firm",
		"sale_pay_system",
		"sale_delivery",
		"sale_delivery_service_list",
		"storeassist_seo_settings",
		"seo_robots",
		"seo_sitemap",
		"seo_search_yandex",
		"seo_search_google",
		"search_reindex",
		"storeassist_adaptive",
		"opening",
		"checklist",
		"storeassist_context_adv",
		"cat_discount_admin",
		"storeassist_marketing",
		"posting_admin",
		"cat_export_setup",
		"sale_ymarket",
		"ebay",
		"sale_order",
		"sale_report",
		"storeassist_print",
		"client",
		"sale_account_admin",
		"sale_basket",
		"sale_personalization",
		"sale_crm",
		"storeassist_crm_client",
		"storeassist_crm_calls",
		"site_speed",
		"composite",
		"bitrixcloud_cdn",
		"perfmon_panel",
		"security_filter",
		"dump_auto",
		"bitrixcloud_monitoring_admin",
		"security_scanner",
		"security_otp",
		"scale_graph",
		"cluster_index",
		"storeassist_virtual",
		"blog_comment",
		"ticket_desktop",
		"site_checker",
		"info_vk",
		"info_blog",
		"info_forum_guest",
		"info_forum_client",
		"info_idea",
		"info_user_doc",
		"info_api_doc",
		"info_courses",
		"support_developer",
		"support_bitrix"
	);

	public static function setSettingOption($pageId, $isDone)
	{
		if (!in_array($pageId, self::$arAllPageId))
			return false;

		if (!in_array($isDone, array("Y", "N")))
			return false;

		$option = Bitrix\Main\Config\Option::get('storeassist', 'storeassist_settings', '');

		if (!$option)
			$option = array();
		else
			$option = explode(",", $option);

		if ($isDone == "Y" && !in_array($pageId, $option))
		{
			$option[] = $pageId;
		}
		elseif($isDone == "N" && in_array($pageId, $option))
		{
			$key = array_search($pageId, $option);
			unset($option[$key]);
		}

		$option = implode(",", $option);

		Bitrix\Main\Config\Option::set('storeassist', 'storeassist_settings', $option, '');

		return $option;
	}

	public static function getSettingOption()
	{
		$option = Bitrix\Main\Config\Option::get('storeassist', 'storeassist_settings', '');
		if (!$option)
			$option = array();
		else
			$option = explode(",", $option);

		return $option;
	}

	public static function getDocumentationLink($pageId)
	{
		if (!$pageId)
			return false;

		if (!in_array($pageId, self::$arAllPageId))
			return false;

		return Loc::getMessage("STOREAS_DOCURL_".$pageId);
	}

	public static function onPrologAdminTitle($pageUrl, $pageId = "")
	{
		if (!in_array(LANGUAGE_ID, array("ru", "ua")))
			return;

		global $APPLICATION, $bxProductConfig;

		$ST_RIGHT = $APPLICATION->GetGroupRight("storeassist");
		if (!($ST_RIGHT >= "R"))
		{
			return false;
		}

		$partnerUrl = Bitrix\Main\Config\Option::get("storeassist", "partner_url", '');
		if ($partnerUrl)
			$bxProductConfig["admin"]["links"] = '<a href="http://www.1c-bitrix.ru/">www.1c-bitrix.ru</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="'.htmlspecialcharsbx($partnerUrl).'" class="adm-main-support-link">'.Loc::getMessage("STOREAS_SUPPORT").'</a>';

		if (!$pageId)
		{
			preg_match("/([^\/]*?)\.php/i", $pageUrl, $matches);
			if (isset($matches[1]))
				$pageId = $matches[1];
			else
				return false;
		}

		if (!in_array($pageId, self::$arAllPageId))
			return false;

		$arSettingOption = self::getSettingOption();
		$isItemDone = (in_array($pageId, $arSettingOption)) ? true : false;

		CJSCore::Init(array('storeassist', 'fx'));
		$APPLICATION->SetAdditionalCSS('/bitrix/panel/storeassist/storeassist.css');
		//Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/panel/storeassist/storeassist.css');

		$hash = (isset($_GET["back"])) ? "#".htmlspecialcharsbx($_GET["back"]) : "";
		echo '<div class="adm-s-toolbar" data-role="adm-task-toolbar" onmouseout="BX.removeClass(this, \'hover\')">
			<div class="adm-s-toolbar-shadow">
				<div class="adm-detail-toolbar">
					<a href="/bitrix/admin/storeassist.php?lang='.LANGUAGE_ID.$hash.'" class="adm-detail-toolbar-btn"><span class="adm-detail-toolbar-btn-l"></span><span class="adm-detail-toolbar-btn-text">'.Loc::getMessage("STOREAS_BACK_BUTTON").'</span><span class="adm-detail-toolbar-btn-r"></span></a>
					<span class="adm-s-toolbar-task '.($isItemDone ? 'checked' : '').'">'.Loc::getMessage(($isItemDone ? 'STOREAS_DONE' : 'STOREAS_NOT_DONE')).'</span>
					<div class="adm-detail-toolbar-right">
						<span class="adm-btn-wrap adm-btn-delete">
							<span class="adm-btn" onclick="BX.Storeassist.Admin.showDocumentation(\''.CUtil::JSEscape(Loc::getMessage("STOREAS_DOCURL_".$pageId)).'\')">'.Loc::getMessage("STOREAS_DOCUMENTATION").'</span>
						</span>
					</div>
				</div>
				<div class="adm-s-toolbar-hidden-block">
					<strong>'.Loc::getMessage("STOREAS_TASK").':</strong>
					'.Loc::getMessage("STOREAS_TASK_".$pageId).
					($ST_RIGHT >= "W" ?
					'<div class="adm-s-toolbar-hidden-block-btn">'.($isItemDone ?
						'<a href="javascript:void(0)" onclick="BX.Storeassist.Admin.setOption(\''.$pageId.'\', \'N\')" class="adm-btn">'.Loc::getMessage("STOREAS_NOT_DONE_BUTTON").'</a>' :
						'<a href="javascript:void(0)" onclick="BX.Storeassist.Admin.setOption(\''.$pageId.'\', \'Y\')" class="adm-btn adm-btn-green">'.Loc::getMessage("STOREAS_DONE_BUTTON").'</a>').
					'</div>' : '')
				.'</div>
			</div>
		</div>';

		if (!$isItemDone)
		{
			echo '<script>
				BX.ready(function(){
					var hash = window.location.hash;
					if (hash.indexOf("showtask") != -1)
					{
						var toolbar = document.querySelector(\'[data-role="adm-task-toolbar"]\');

						if (toolbar)
						{
							new BX.easing({
								duration : 600,
								start : { opacity : 0 },
								finish : { opacity : 1 },
								transition : BX.easing.transitions.linear,
								step : function(state){
									toolbar.style.opacity = state.opacity;
								},
								complete : function() {
									BX.addClass(toolbar, "hover");
								}
							}).animate();
						}
					}
				});
			</script>';
		}
	}

	public static function onBuildGlobalMenu(&$arGlobalMenu, &$arModuleMenu)
	{
		if (!in_array(LANGUAGE_ID, array("ru", "ua")))
			return;

		global $APPLICATION;

		if ($APPLICATION->GetGroupRight("storeassist") >= "R")
		{
			$storeAssistItem = array(
				"text" => Loc::getMessage("STOREAS_MENU_TEXT"),
				"url" => "storeassist.php?lang=".LANGUAGE_ID,
				"title" => Loc::getMessage("STOREAS_MENU_TITLE"),
			);

			foreach ($arModuleMenu as &$arMenuItem)
			{
				if (!isset($arMenuItem["items_id"]) || $arMenuItem["items_id"] !== "menu_sale_settings")
				{
					continue;
				}

				if (isset($arMenuItem["items"]) && is_array($arMenuItem["items"]))
				{
					array_unshift($arMenuItem["items"], $storeAssistItem);
				}
				else
				{
					$arMenuItem["items"] = array($storeAssistItem);
				}
				break;
			}
		}
	}

	public static function getProgressPercent()
	{
		$progress =	Bitrix\Main\Config\Option::get("storeassist", "progress_percent", 0);

		if ($progress < 0)
			$progress = 0;
		elseif ($progress > 10)
			$progress = 10;

		return $progress;
	}

	public static function AgentCountDayOrders()
	{
		global $DB;

		if (Bitrix\Main\Loader::includeModule("sale"))
		{
			//count orders
			$arFilter = array(
				"STATUS_ID" => array("F", "P"),
				"PAYED" => "Y",
			);

			$dateFromTs = MakeTimeStamp(date("d", time()-3600*24*2).".".date("m", time()-3600*24*2).".".date("Y", time()-3600*24*2)." 23:59:59", "DD.MM.YYYY HH:MI:SS");
			$dateToTs = MakeTimeStamp(date("d").".".date("m").".".date("Y")." 00:00:00", "DD.MM.YYYY HH:MI:SS");
			
			$arFilter[">DATE_STATUS"] = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL", SITE_ID)), $dateFromTs);
			$arFilter["<DATE_STATUS"] = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL", SITE_ID)), $dateToTs);

			$dbOrders = CSaleOrder::GetList(array(), $arFilter);
			$numOrders = $dbOrders->SelectedRowsCount();

			$newNumOrders = array(
				"newDay" => $numOrders
			);

			$curNumOrders = Bitrix\Main\Config\Option::get("storeassist", "num_orders", "");
			if ($curNumOrders)
			{
				$curNumOrders = unserialize($curNumOrders);
			}
			$newNumOrders["prevDay"] = (isset($curNumOrders["newDay"])) ? $curNumOrders["newDay"] : 0;

			Bitrix\Main\Config\Option::set("storeassist", "num_orders", serialize($newNumOrders), "");

			//count progress
			$progress = Bitrix\Main\Config\Option::get("storeassist", "progress_percent", 0);

			$newProgress = 0;
			if ($newNumOrders["newDay"] >= $newNumOrders["prevDay"] && $newNumOrders["newDay"] > 0)
			{
				$newProgress = (++$progress > 10) ? 10 : $progress;
			}
			else
			{
				$newProgress = (--$progress > 0) ? $progress : 0;
			}

			Bitrix\Main\Config\Option::set("storeassist", "progress_percent", $newProgress, "");
		}

		return "CStoreAssist::AgentCountDayOrders();";
	}
}
?>
