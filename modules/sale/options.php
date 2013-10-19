<?
$module_id = "sale";
$SALE_RIGHT = $APPLICATION->GetGroupRight($module_id);
if ($SALE_RIGHT>="R") :

global $MESS;
include(GetLangFileName($GLOBALS["DOCUMENT_ROOT"]."/bitrix/modules/main/lang/", "/options.php"));
include(GetLangFileName($GLOBALS["DOCUMENT_ROOT"]."/bitrix/modules/sale/lang/", "/options.php"));

function checkAccountNumberValue($templateType, $number_data, $number_prefix)
{
	$res = true;

	switch ($templateType)
	{
		case 'NUMBER':

			if (strlen($number_data) <= 0
				|| strlen($number_data) > 7
				|| !ctype_digit($number_data)
				|| intval($number_data) < intval(COption::GetOptionString("sale", "account_number_data", ""))
				)
				$res = false;

			break;

		case 'PREFIX':

			if (strlen($number_prefix) <= 0
				|| strlen($number_prefix) > 7
				|| preg_match('/[^a-zA-Z0-9_-]/', $number_prefix)
				)
				$res = false;

			break;
	}

	return $res;
}

include_once($GLOBALS["DOCUMENT_ROOT"]."/bitrix/modules/sale/include.php");

$siteList = array();
$rsSites = CSite::GetList($by="sort", $order="asc", Array());
while($arRes = $rsSites->GetNext())
{
	$siteList[] = Array("ID" => $arRes["ID"], "NAME" => $arRes["NAME"]);
}
$siteCount = count($siteList);

$bWasUpdated = false;

if ($REQUEST_METHOD=="GET" && strlen($RestoreDefaults)>0 && $SALE_RIGHT=="W" && check_bitrix_sessid())
{
	$bWasUpdated = true;

	COption::RemoveOption("sale");
	$z = CGroup::GetList($v1="id",$v2="asc", array("ACTIVE" => "Y", "ADMIN" => "N"));
	while($zr = $z->Fetch())
		$APPLICATION->DelGroupRight($module_id, array($zr["ID"]));
}

$arAllOptions =
	Array(
		Array("order_email", GetMessage("SALE_EMAIL_ORDER"), "order@".$SERVER_NAME, Array("text", 30)),
		//Array("default_email", GetMessage("SALE_EMAIL_REGISTER"), "admin@".$SERVER_NAME, Array("text", 30)),
		Array("delete_after", GetMessage("SALE_DELETE_AFTER"), "", Array("text", 10)),
		Array("order_list_date", GetMessage("SALE_ORDER_LIST_DATE"), 30, Array("text", 10)),
		Array("MAX_LOCK_TIME", GetMessage("SALE_MAX_LOCK_TIME"), 30, Array("text", 10)),
		Array("GRAPH_WEIGHT", GetMessage("SALE_GRAPH_WEIGHT"), 800, Array("text", 10)),
		Array("GRAPH_HEIGHT", GetMessage("SALE_GRAPH_HEIGHT"), 600, Array("text", 10)),
		Array("path2user_ps_files", GetMessage("SALE_PATH2UPSF"), BX_PERSONAL_ROOT."/php_interface/include/sale_payment/", Array("text", 40)),
		Array("path2custom_view_order", GetMessage("SMO_SALE_PATH2ORDER"), "", Array("text", 40)),
		Array("lock_catalog", GetMessage("SMO_LOCK_CATALOG"), "Y", Array("checkbox", 40)),
		(CBXFeatures::IsFeatureEnabled('SaleAffiliate')) ? Array("affiliate_param_name", GetMessage("SMOS_AFFILIATE_PARAM"), "partner", Array("text", 40)) : array(),
		(CBXFeatures::IsFeatureEnabled('SaleAffiliate')) ? Array("affiliate_life_time", GetMessage("SMO_AFFILIATE_LIFE_TIME"), "30", Array("text", 10)): array(),
		Array("show_order_sum", GetMessage("SMO_SHOW_ORDER_SUM"), "N", Array("checkbox", 40)),
		Array("show_order_product_xml_id", GetMessage("SMO_SHOW_ORDER_PRODUCT_XML_ID"), "N", Array("checkbox", 40)),
		Array("show_paysystem_action_id", GetMessage("SMO_SHOW_PAYSYSTEM_ACTION_ID"), "N", Array("checkbox", 40)),
		Array("measurement_path", GetMessage("SMO_MEASUREMENT_PATH"), "/bitrix/modules/sale/measurements.php", Array("text", 40)),
		//Array("use_delivery_handlers", GetMessage("SMO_USE_DELIVERY_HANDLERS"), "N", Array("checkbox", 40)),
		Array("delivery_handles_custom_path", GetMessage("SMO_DELIVERY_HANDLERS_CUSTOM_PATH"), BX_PERSONAL_ROOT."/php_interface/include/sale_delivery/", Array("text", 40)),
		Array("use_secure_cookies", GetMessage("SMO_USE_SECURE_COOKIES"), "N", Array("checkbox", 40)),
		Array("recalc_product_list", GetMessage("SALE_RECALC_PRODUCT_LIST"), "N", Array("checkbox", 40)),
		Array("recalc_product_list_period", GetMessage("SALE_RECALC_PRODUCT_LIST_PERIOD"), 7, Array("text", 10)),
		Array("COUNT_DISCOUNT_4_ALL_QUANTITY", GetMessage("SALE_OPT_COUNT_DISCOUNT_4_ALL_QUANTITY"), "N", Array("checkbox", 40)),
		Array("COUNT_DELIVERY_TAX", GetMessage("SALE_OPT_COUNT_DELIVERY_TAX"), "N", Array("checkbox", 40)),
		Array("QUANTITY_FACTORIAL", GetMessage("SALE_OPT_QUANTITY_FACTORIAL"), "N", Array("checkbox", 40)),
		Array("viewed_time", GetMessage("SALE_VIEWED_TIME"), 90, Array("text", 10)),
		Array("viewed_count", GetMessage("SALE_VIEWED_COUNT"), 100, Array("text", 10)),
		Array("SALE_ADMIN_NEW_PRODUCT", GetMessage("SALE_ADMIN_NEW_PRODUCT"), "N", Array("checkbox", 40)),
		Array("use_ccards", GetMessage("SALE_ADMIN_USE_CARDS"), "N", Array("checkbox", 40)),
		);

$arOrderFlags = array("P" => GetMessage("SMO_PAYMENT_FLAG"), "C" => GetMessage("SMO_CANCEL_FLAG"), "D" => GetMessage("SMO_DELIVERY_FLAG"));

$arAccountNumberDefaultTemplates = array(
	"" => GetMessage("SALE_ACCOUNT_NUMBER_TEMPLATE_0"),
	"NUMBER" => GetMessage("SALE_ACCOUNT_NUMBER_TEMPLATE_1"),
	"PREFIX" => GetMessage("SALE_ACCOUNT_NUMBER_TEMPLATE_2"),
	"RANDOM" => GetMessage("SALE_ACCOUNT_NUMBER_TEMPLATE_3"),
	"USER" => GetMessage("SALE_ACCOUNT_NUMBER_TEMPLATE_4"),
	"DATE" => GetMessage("SALE_ACCOUNT_NUMBER_TEMPLATE_5"),
);

$arAccountNumberCustomHandlers = array();
foreach(GetModuleEvents("sale", "OnBuildAccountNumberTemplateList", true) as $arEvent)
{
	$arRes = ExecuteModuleEventEx($arEvent, array());
	if (isset($arRes["CODE"]) && isset($arRes["NAME"]))
		$arAccountNumberCustomHandlers[$arRes["CODE"]] = $arRes["NAME"];
}

$arAccountNumberTemplates = array_merge($arAccountNumberDefaultTemplates, $arAccountNumberCustomHandlers);

$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("MAIN_TAB_SET"), "ICON" => "sale_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_SET")),
	array("DIV" => "edit7", "TAB" => GetMessage("SALE_TAB_WEIGHT"), "ICON" => "sale_settings", "TITLE" => GetMessage("SALE_TAB_WEIGHT_TITLE")),
	array("DIV" => "edit5", "TAB" => GetMessage("SALE_TAB_ADDRESS"), "ICON" => "sale_settings", "TITLE" => GetMessage("SALE_TAB_ADDRESS_TITLE"))
	);

if (CBXFeatures::IsFeatureEnabled('SaleCCards') && COption::GetOptionString($module_id, "use_ccards", "N") == "Y")
	$aTabs[] = array("DIV" => "edit2", "TAB" => GetMessage("SALE_TAB_2"), "ICON" => "sale_settings", "TITLE" => GetMessage("SMO_CRYPT_TITLE"));

$aTabs[] = array("DIV" => "edit3", "TAB" => GetMessage("SALE_TAB_3"), "ICON" => "sale_settings", "TITLE" => GetMessage("SALE_TAB_3_TITLE"));
$aTabs[] = array("DIV" => "edit4", "TAB" => GetMessage("MAIN_TAB_RIGHTS"), "ICON" => "sale_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_RIGHTS"));
$tabControl = new CAdminTabControl("tabControl", $aTabs);

$strWarning = "";
if ($REQUEST_METHOD == "POST" && strlen($Update) > 0 && $SALE_RIGHT == "W" && check_bitrix_sessid())
{
	if (!checkAccountNumberValue($_POST["account_number_template"], $_POST["account_number_number"], $_POST["account_number_prefix"]))
	{
		if ($_POST["account_number_template"] == "PREFIX")
			$strWarning = GetMessage("SALE_ACCOUNT_NUMBER_PREFIX_WARNING", array("#PREFIX#" => $_POST["account_number_prefix"]));
		elseif ($_POST["account_number_template"] == "NUMBER")
			$strWarning = GetMessage("SALE_ACCOUNT_NUMBER_NUMBER_WARNING", array("#NUMBER#" => $_POST["account_number_number"]));
	}
	else
	{
		$bWasUpdated = true;

		COption::RemoveOption($module_id, "weight_unit");
		COption::RemoveOption($module_id, "weight_koef");

		if (!empty($_REQUEST["WEIGHT_dif_settings"]))
		{
			for ($i = 0; $i < $siteCount; $i++)
			{
				COption::SetOptionString($module_id, "weight_unit", trim($_REQUEST["weight_unit"][$siteList[$i]["ID"]]), false, $siteList[$i]["ID"]);
				COption::SetOptionString($module_id, "weight_koef", floatval($_REQUEST["weight_koef"][$siteList[$i]["ID"]]), false, $siteList[$i]["ID"]);
			}
			COption::SetOptionString($module_id, "WEIGHT_different_set", "Y");
		}
		else
		{
			$site_id = trim($_REQUEST["WEIGHT_current_site"]);
			COption::SetOptionString($module_id, "weight_unit", trim($_REQUEST["weight_unit"][$site_id]));
			COption::SetOptionString($module_id, "weight_koef", floatval($_REQUEST["weight_koef"][$site_id]));
			COption::SetOptionString($module_id, "WEIGHT_different_set", "N");
		}

		COption::RemoveOption($module_id, "location_zip");
		COption::RemoveOption($module_id, "location");

		if (!empty($_REQUEST["ADDRESS_dif_settings"]))
		{
			for ($i = 0; $i < $siteCount; $i++)
			{
				COption::SetOptionInt($module_id, "location_zip", intval($_REQUEST["location_zip"][$siteList[$i]["ID"]]), false, $siteList[$i]["ID"]);
				COption::SetOptionInt($module_id, "location", intval($_REQUEST["location"][$siteList[$i]["ID"]]), false, $siteList[$i]["ID"]);
			}
			COption::SetOptionString($module_id, "ADDRESS_different_set", "Y");
		}
		else
		{
			$site_id = trim($_REQUEST["ADDRESS_current_site"]);
			COption::SetOptionInt($module_id, "location_zip", intval($_REQUEST["location_zip"][$site_id]));
			COption::SetOptionInt($module_id, "location", intval($_REQUEST["location"][$site_id]));
			COption::SetOptionString($module_id, "ADDRESS_different_set", "N");
		}

		CAgent::RemoveAgent("CSaleUser::DeleteOldAgent(".COption::GetOptionString("sale", "delete_after", "365").");", "sale");
		if(COption::GetOptionString("sale", "recalc_product_list", "N") != $recalc_product_list || COption::GetOptionInt("sale", "recalc_product_list_period", 7) != $recalc_product_list_period)
			CAgent::RemoveAgent("CSaleProduct::RefreshProductList();", "sale");
		if($recalc_product_list == "Y" && IntVal($recalc_product_list_period) > 0 && COption::GetOptionInt("sale", "recalc_product_list_period", 7) != $recalc_product_list_period || COption::GetOptionString("sale", "recalc_product_list", "N") == "N")
			CAgent::AddAgent("CSaleProduct::RefreshProductList();", "sale", "N", 60*60*24*IntVal($recalc_product_list_period), "", "Y");

		for ($i = 0, $intCount = count($arAllOptions); $i < $intCount; $i++)
		{
			$name = $arAllOptions[$i][0];
			$val = ${$name};
			if ($arAllOptions[$i][3][0]=="checkbox" && $val!="Y")
				$val = "N";

			if ($name == "path2user_ps_files" && substr($val, strlen($val)-1, 1) != "/")
			{
				$val .= "/";
			}
			COption::SetOptionString("sale", $name, $val, $arAllOptions[$i][1]);
		}

		if(CBXFeatures::IsFeatureEnabled('SaleAffiliate'))
		{
			COption::SetOptionString("sale", "affiliate_plan_type", $affiliate_plan_type);
		}
		$arAmountSer = Array();
		foreach($amount_val as $key =>$val)
		{
			if(DoubleVal($val) > 0)
				$arAmountSer[$key] = Array("AMOUNT" => DoubleVal($val), "CURRENCY" => $amount_currency[$key]);
		}
		if(!empty($arAmountSer))
			COption::SetOptionString("sale", "pay_amount", serialize($arAmountSer));

		if(!empty($reminder))
			COption::SetOptionString("sale", "pay_reminder", serialize($reminder));

		//subscribe product
		if(!empty($subscribProd))
		{
			foreach ($subscribProd as $lid => $val)
			{
				if ($val["use"] == "Y")
				{
					if (IntVal($val["del_after"]) <= 0)
						$subscribProd[$lid]["del_after"] = 30;

					CAgent::AddAgent("CSaleBasket::ClearProductSubscribe(\"".$lid."\");", "sale", "N", IntVal($subscribProd[$lid]["del_after"])*24*60*60, "", "Y");
				}
				else
				{
					CAgent::RemoveAgent("CSaleBasket::ClearProductSubscribe(\"".$lid."\");", "sale");
				}
			}
			COption::SetOptionString("sale", "subscribe_prod", serialize($subscribProd));
		}

		//viewed product
		if(!empty($viewed))
		{
			foreach ($viewed as $lid => $val)
			{
				if (IntVal($val["time"]) <= 0)
					$viewed[$lid]["time"] = 90;
				if (IntVal($val["count"]) <= 0)
					$viewed[$lid]["count"] = 1000;
			}
			COption::SetOptionString("sale", "viewed_product", serialize($viewed));
		}

		COption::SetOptionString("sale", "default_currency", $CURRENCY_DEFAULT);
		COption::SetOptionString("sale", "crypt_algorithm", $crypt_algorithm);
		COption::SetOptionString("sale", "sale_data_file", $sale_data_file);
		COption::SetOptionString("sale", "status_on_paid", $PAID_STATUS);
		COption::SetOptionString("sale", "status_on_allow_delivery", $ALLOW_DELIVERY_STATUS);
		COption::SetOptionString("sale", "status_on_payed_2_allow_delivery", $PAYED_2_ALLOW_DELIVERY);
		COption::SetOptionString("sale", "allow_deduction_on_delivery", $ALLOW_DEDUCTION_ON_DELIVERY);
		COption::SetOptionString("sale", "product_reserve_condition", $PRODUCT_RESERVE_CONDITION);

		if (!empty($SELECTED_FIELDS) && is_array($SELECTED_FIELDS))
		{
			for ($i = 0, $intCount = count($SELECTED_FIELDS); $i < $intCount; $i++)
			{
				if (strlen($saveValue) > 0)
					$saveValue .= ",";

				$saveValue .= $SELECTED_FIELDS[$i];
			}
		}
		else
		{
			$saveValue = "ID,USER,PAY_SYSTEM,PRICE,STATUS,PAYED,PS_STATUS,CANCELED,BASKET";
		}
		COption::SetOptionString("sale", "order_list_fields", $saveValue);

		// account number generation algorithm
		if (isset($_POST["account_number_template"]))
		{
			if (array_key_exists($_POST["account_number_template"], $arAccountNumberDefaultTemplates))
			{
				switch ($_POST["account_number_template"])
				{
					case 'NUMBER':
						COption::SetOptionString("sale", "account_number_template", "NUMBER");
						COption::SetOptionString("sale", "account_number_data", intval($_POST["account_number_number"]));
						break;

					case 'PREFIX':
						COption::SetOptionString("sale", "account_number_template", "PREFIX");
						COption::SetOptionString("sale", "account_number_data", $_POST["account_number_prefix"]);
						break;

					case 'RANDOM':
						COption::SetOptionString("sale", "account_number_template", "RANDOM");
						COption::SetOptionString("sale", "account_number_data", intval($_POST["account_number_random_length"]));
						break;

					case 'USER':
						COption::SetOptionString("sale", "account_number_template", "USER");
						COption::SetOptionString("sale", "account_number_data", "");
						break;

					case 'DATE':
						COption::SetOptionString("sale", "account_number_template", "DATE");
						COption::SetOptionString("sale", "account_number_data", $_POST["account_number_date_period"]);
						break;

					default:
						COption::SetOptionString("sale", "account_number_template", "");
						COption::SetOptionString("sale", "account_number_data", "");
						break;
				}
			}
			else // custom account number generation template
			{
				COption::SetOptionString("sale", "account_number_template", $_POST["account_number_template"]);
			}
		}

		//SAVE SHOP LIST SITE
		foreach($siteList as $val)
		{
			COption::RemoveOption("sale", "SHOP_SITE_".$val["ID"]);
		}
		if (isset(${"SHOP_SITE"}) AND is_array(${"SHOP_SITE"}))
		{
			foreach (${"SHOP_SITE"} as $key => $val)
			{
				COption::SetOptionString("sale", "SHOP_SITE_".$val, $val);
			}
		}

		$db_result_lang = CLang::GetList(($by1="sort"), ($order1="asc"));
		while ($db_result_lang_array = $db_result_lang->Fetch())
		{
			$valCurrency = Trim(${"CURRENCY_".$db_result_lang_array["LID"]});
			UnSet($arFields);
			$arFields["LID"] = $db_result_lang_array["LID"];
			if (strlen($valCurrency)<=0) $valCurrency = false;
			$arFields["CURRENCY"] = $valCurrency;
			if ($arRes = CSaleLang::GetByID($db_result_lang_array["LID"]))
			{
				if ($valCurrency!==false)
				{
					CSaleLang::Update($db_result_lang_array["LID"], $arFields);
				}
				else
				{
					CSaleLang::Delete($db_result_lang_array["LID"]);
				}
			}
			else
			{
				if ($valCurrency!==false)
				{
					CSaleLang::Add($arFields);
				}
			}

			CSaleGroupAccessToSite::DeleteBySite($db_result_lang_array["LID"]);
			if (isset(${"SITE_USER_GROUPS_".$db_result_lang_array["LID"]})
				&& is_array(${"SITE_USER_GROUPS_".$db_result_lang_array["LID"]}))
			{
				for ($i = 0, $intCount = count(${"SITE_USER_GROUPS_".$db_result_lang_array["LID"]}); $i < $intCount; $i++)
				{
					if (IntVal(${"SITE_USER_GROUPS_".$db_result_lang_array["LID"]}[$i]) > 0)
					{
						CSaleGroupAccessToSite::Add(
								array(
										"SITE_ID" => $db_result_lang_array["LID"],
										"GROUP_ID" => IntVal(${"SITE_USER_GROUPS_".$db_result_lang_array["LID"]}[$i])
									)
							);
					}
				}
			}
		}

		if (IntVal($delete_after) > 0)
			CAgent::AddAgent("CSaleUser::DeleteOldAgent(".IntVal($delete_after).");", "sale", "N", 8*60*60, "", "Y");

		ob_start();
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");
		ob_end_clean();
	}
}

$arStatuses = Array("" => GetMessage("SMO_STATUS"));
$dbStatus = CSaleStatus::GetList(Array("SORT" => "ASC"), Array("LID" => LANGUAGE_ID), false, false, Array("ID", "NAME", "SORT"));
while ($arStatus = $dbStatus->GetNext())
{
	$arStatuses[$arStatus["ID"]] = "[".$arStatus["ID"]."] ".$arStatus["NAME"];
}

if(strlen($strWarning)>0)
	CAdminMessage::ShowMessage($strWarning);
elseif ($bWasUpdated)
{
	if(strlen($Update)>0 && strlen($_REQUEST["back_url_settings"])>0)
		LocalRedirect($_REQUEST["back_url_settings"]);
	else
		LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($mid)."&lang=".urlencode(LANGUAGE_ID)."&back_url_settings=".urlencode($_REQUEST["back_url_settings"])."&".$tabControl->ActiveTabParam());
}

$tabControl->Begin();
?><form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&lang=<?=LANGUAGE_ID?>" name="opt_form">
<?=bitrix_sessid_post()?>
<?
$tabControl->BeginNextTab();
?>
<tr class="heading">
	<td colspan="2"><?=GetMessage("SALE_SERVICE_AREA")?></td>
</tr>
<?
	for ($i = 0, $intCount = count($arAllOptions); $i < $intCount; $i++):
		$Option = $arAllOptions[$i];
		$val = COption::GetOptionString("sale", $Option[0], $Option[2]);
		$type = $Option[3];

		if ($Option[0]=="assist_LOGIN" || $Option[0]=="assist_PASSWORD")
		{
			if ($SALE_RIGHT!="W") $val = "........";
		}
		?>
		<tr>
			<td width="40%"><?	if($type[0]=="checkbox")
							echo "<label for=\"".htmlspecialcharsbx($Option[0])."\">".$Option[1]."</label>";
						else
							echo $Option[1];?></td>
			<td width="60%">

					<?if($type[0]=="checkbox"):?>
						<input type="checkbox" name="<?echo htmlspecialcharsbx($Option[0])?>" id="<?echo htmlspecialcharsbx($Option[0])?>" value="Y"<?if($val=="Y")echo" checked";?>>
					<?elseif($type[0]=="text"):?>
						<input type="text" size="<?echo $type[1]?>" value="<?echo htmlspecialcharsbx($val)?>" name="<?echo htmlspecialcharsbx($Option[0])?>">
					<?elseif($type[0]=="textarea"):?>
						<textarea rows="<?echo $type[1]?>" cols="<?echo $type[2]?>" name="<?echo htmlspecialcharsbx($Option[0])?>"><?echo htmlspecialcharsbx($val)?></textarea>
					<?endif?>

			</td>
		</tr>
	<?endfor;?>
	<tr>
		<td>
			<?echo GetMessage("SALE_DEF_CURR")?>
		</td>
		<td>
			<?
			$val = COption::GetOptionString("sale", "default_currency", "RUB");
			echo CCurrency::SelectBox("CURRENCY_DEFAULT", $val, "", True, "");
			?>
		</td>
	</tr>

	<?
	if(CBXFeatures::IsFeatureEnabled('SaleAffiliate'))
	{
		?>
	<tr>
		<td>
			<?echo GetMessage("SMO_AFFILIATE_PLAN_TYPE")?>:
		</td>
		<td>
			<?
			$val = COption::GetOptionString("sale", "affiliate_plan_type", "N");
			?>
			<select name="affiliate_plan_type">
				<option value="N"<?if ($val == "N") echo " selected";?>><?= GetMessage("SMO_AFFILIATE_PLAN_TYPE_N") ?></option>
				<option value="S"<?if ($val == "S") echo " selected";?>><?= GetMessage("SMO_AFFILIATE_PLAN_TYPE_S") ?></option>
			</select>
		</td>
	</tr>
		<?
	}
	?>
	<tr>
		<td>
			<?echo GetMessage("SALE_PAY_TO_STATUS")?>
		</td>
		<td>
			<?
			$val = COption::GetOptionString("sale", "status_on_paid", "");
			?>
			<select name="PAID_STATUS">
				<?
				foreach($arStatuses as $statusID => $statusName)
				{
					?><option value="<?=$statusID?>"<?if ($val == $statusID) echo " selected";?>><?=$statusName?></option><?
				}
				?>
			</select>
		</td>
	</tr>
	<tr>
		<td>
			<?echo GetMessage("SALE_ALLOW_DELIVERY_TO_STATUS")?>
		</td>
		<td>
			<?
			$val = COption::GetOptionString("sale", "status_on_allow_delivery", "");
			?>
			<select name="ALLOW_DELIVERY_STATUS">
				<?
				foreach($arStatuses as $statusID => $statusName)
				{
					?><option value="<?=$statusID?>"<?if ($val == $statusID) echo " selected";?>><?=$statusName?></option><?
				}
				?>
			</select>
		</td>
	</tr>
	<tr>
		<td>
			<label for="PAYED_2_ALLOW_DELIVERY"><?echo GetMessage("SALE_PAYED_2_ALLOW_DELIVERY")?></label>
		</td>
		<td>
			<?
			$val = COption::GetOptionString("sale", "status_on_payed_2_allow_delivery", "");
			?>
			<input type="checkbox" name="PAYED_2_ALLOW_DELIVERY" id="PAYED_2_ALLOW_DELIVERY" value="Y"<?if($val=="Y")echo" checked";?>>
		</td>
	</tr>
	<tr>
		<td>
			<label for="ALLOW_DEDUCTION_ON_DELIVERY"><?echo GetMessage("SALE_ALLOW_DEDUCTION_ON_DELIVERY")?></label>
		</td>
		<td>
			<?
			$val = COption::GetOptionString("sale", "allow_deduction_on_delivery", "");
			?>
			<input type="checkbox" name="ALLOW_DEDUCTION_ON_DELIVERY" id="ALLOW_DEDUCTION_ON_DELIVERY" value="Y"<?if($val=="Y")echo" checked";?>>
		</td>
	</tr>

	<tr>
		<td valign="top">
			<?echo GetMessage("SALE_IS_SHOP")?>
		</td>
		<td>
			<select name="SHOP_SITE[]" multiple size="5">
			<?
			foreach($siteList as $key => $val)
			{
				$site = COption::GetOptionString("sale", "SHOP_SITE_".$val["ID"], "");
				?><option value="<?=$val["ID"]?>" <? if ($site == $val["ID"]) echo "selected";  ?>    ><? echo htmlspecialcharsEx($val["NAME"])." (".htmlspecialcharsEx($val["ID"]).")";?></option><?
			}
			?>
			</select>
		</td>
	</tr>

	<!-- account number template settings -->
	<tr>
		<td>
			<?echo GetMessage("SALE_ACCOUNT_NUMBER_TEMPLATE")?>
		</td>
		<td>
			<?
			$val = COption::GetOptionString("sale", "account_number_template", "");
			?>
			<select name="account_number_template" onChange="showAccountNumberAdditionalFields(this.selectedIndex)">
				<?
				$templateNumber = 0;
				$ind = 0;
				foreach($arAccountNumberTemplates as $template => $templateName)
				{
					?>
					<option value="<?=$template?>"<?if ($val == $template) { echo " selected"; $templateNumber = $ind; } ?>><?=$templateName?></option>
					<?
					$ind++;
				}
				?>
			</select>
		</td>
	</tr>
	<tr id="account_template_1" <?=($templateNumber == "1") ? "" : "style=\"display:none\""?>>
		<td>&nbsp;</td>
		<td>
			<?
			if (strlen($account_number_number) <= 0 || strlen($strWarning) <= 0)
				$account_number_number = ($templateNumber == 1) ? COption::GetOptionString("sale", "account_number_data", "") : "";
			?>
			<?=GetMessage("SALE_ACCOUNT_NUMBER_NUMBER")?>&nbsp;<input type="text" name="account_number_number" size="7" maxlength="7" value="<?=$account_number_number?>" /><br/><br/><?=GetMessage("SALE_ACCOUNT_NUMBER_NUMBER_DESC")?>
		</td>
	</tr>
	<tr id="account_template_2" <?=($templateNumber == "2") ? "" : "style=\"display:none\""?>>
		<td>&nbsp;</td>
		<td>
			<?
			if (strlen($account_number_prefix) <= 0 || strlen($strWarning) <= 0)
				$account_number_prefix = ($templateNumber == 2) ? COption::GetOptionString("sale", "account_number_data", "") : "";
			?>
			<?=GetMessage("SALE_ACCOUNT_NUMBER_PREFIX")?>&nbsp;<input type="text" name="account_number_prefix" size="10" maxlength="7" value="<?=$account_number_prefix?>" /><br/><br/>
			<?=GetMessage("SALE_ACCOUNT_NUMBER_PREFIX_DESC")?>
		</td>
	</tr>
	<tr id="account_template_3" <?=($templateNumber == "3") ? "" : "style=\"display:none\""?>>
		<td>&nbsp;</td>
		<td>
			<?
			$value = ($templateNumber == 3) ? COption::GetOptionString("sale", "account_number_data", "") : "";
			?>
			<?=GetMessage("SALE_ACCOUNT_NUMBER_RANDOM")?>
			<select name="account_number_random_length">
				<option value="5" <?=($value == "5") ? "selected" : "" ?>>5</option>
				<option value="6" <?=($value == "6") ? "selected" : "" ?>>6</option>
				<option value="7" <?=($value == "7") ? "selected" : "" ?>>7</option>
				<option value="8" <?=($value == "8") ? "selected" : "" ?>>8</option>
				<option value="9" <?=($value == "9") ? "selected" : "" ?>>9</option>
				<option value="10" <?=($value == "10") ? "selected" : "" ?>>10</option>
			</select>
			<br/><br/>
			<?=GetMessage("SALE_ACCOUNT_NUMBER_TEMPLATE_EXAMPLE")?>&nbsp;6B7R1, 8CB2A59X8X
		</td>
	</tr>
	<tr id="account_template_4" <?=($templateNumber == "4") ? "" : "style=\"display:none\""?>>
		<td>&nbsp;</td>
		<td>
			<?=GetMessage("SALE_ACCOUNT_NUMBER_TEMPLATE_EXAMPLE")?>&nbsp;1_12, 16749_2
		</td>
	</tr>
	<tr id="account_template_5" <?=($templateNumber == "5") ? "" : "style=\"display:none\""?>>
		<td>&nbsp;</td>
		<td>
			<?
			$value = ($templateNumber == 5) ? COption::GetOptionString("sale", "account_number_data", "") : "";
			?>
			<?=GetMessage("SALE_ACCOUNT_NUMBER_DATE")?>
			<select name="account_number_date_period" onChange="showDateExample(this.selectedIndex)">
				<option value="day" <?=($value == "day") ? "selected" : "" ?>><?=GetMessage("SALE_ACCOUNT_NUMBER_DATE_1")?></option>
				<option value="month" <?=($value == "month") ? "selected" : "" ?>><?=GetMessage("SALE_ACCOUNT_NUMBER_DATE_2")?></option>
				<option value="year" <?=($value == "year") ? "selected" : "" ?>><?=GetMessage("SALE_ACCOUNT_NUMBER_DATE_3")?></option>
			</select>
			<br/><br/>
			<?
			if (!function_exists("showAccountNumberDateExample"))
			{
				function showAccountNumberDateExample($period)
				{
					switch ($period)
					{
						case 'day':
							return "23042013&nbsp;/&nbsp;5";
							break;
						case 'month':
							return "042013&nbsp;/&nbsp;4";
							break;
						case 'year':
							return "2013&nbsp;/&nbsp;176";
							break;
						default:
							return "23042013&nbsp;/&nbsp;5";
							break;
					}
				}
			}
			?>
			<?=GetMessage("SALE_ACCOUNT_NUMBER_TEMPLATE_EXAMPLE")?>&nbsp;<span id="account_number_date_example"><?=showAccountNumberDateExample($value)?></span>
		</td>
	</tr>
	<!-- end of account number template settings -->

	<tr class="heading">
		<td colspan="2"><?=GetMessage("SALE_STORES_AREA")?></td>
	</tr>
	<tr>
		<td>
			<?echo GetMessage("SALE_PRODUCT_RESERVE_CONDITION")?>
		</td>
		<td>
			<?
			$val = COption::GetOptionString("sale", "product_reserve_condition", "O");

			$arConditions = array(
				"O" => GetMessage("SALE_PRODUCT_RESERVE_1_ORDER"),
				"P" => GetMessage("SALE_PRODUCT_RESERVE_2_PAYMENT"),
				"D" => GetMessage("SALE_PRODUCT_RESERVE_3_DELIVERY"),
				"S" => GetMessage("SALE_PRODUCT_RESERVE_4_DEDUCTION")
			);
			?>
			<select name="PRODUCT_RESERVE_CONDITION" id="sl_reserve">
				<?
				foreach($arConditions as $conditionID => $conditionName)
				{
					?><option value="<?=$conditionID?>"<?if ($val == $conditionID) echo " selected";?>><?=$conditionName?></option><?
				}
				?>
			</select>
		</td>
	</tr>
	<?
	if (CBXFeatures::IsFeatureEnabled('SaleAccounts'))
	{
		?>
	<tr class="heading">
		<td colspan="2"><?=GetMessage("SALE_AMOUNT_NAME")?></td>
	</tr>
	<tr>
		<td colspan="2" align="center">
			<table cellspacing="0" cellpadding="0" border="0" class="internal">
				<tr class="heading">
					<td valign="top">
						<?echo GetMessage("SALE_AMOUNT_VAL")?>
					</td>
					<td valign="top">
						<?echo GetMessage("SALE_AMOUNT_CURRENCY")?>
					</td>
				</tr>
				<?
				$val = COption::GetOptionString("sale", "pay_amount", 'a:4:{i:1;a:2:{s:6:"AMOUNT";s:2:"10";s:8:"CURRENCY";s:3:"EUR";}i:2;a:2:{s:6:"AMOUNT";s:2:"20";s:8:"CURRENCY";s:3:"EUR";}i:3;a:2:{s:6:"AMOUNT";s:2:"30";s:8:"CURRENCY";s:3:"EUR";}i:4;a:2:{s:6:"AMOUNT";s:2:"40";s:8:"CURRENCY";s:3:"EUR";}}');
				if(strlen($val) > 0)
				{
					$arAmount = unserialize($val);
					foreach($arAmount as $key => $val)
					{
						?>
						<tr>
							<td><input type="text" name="amount_val[<?=$key?>]" value="<?=$val["AMOUNT"]?>"></td>
							<td><?=CCurrency::SelectBox("amount_currency[".$key."]", $val["CURRENCY"], "", True, "")?></td>
						</tr>
						<?
					}
				}
				if(IntVal($key) <= 0)
					$key = 0;
				?>
				<tr>
					<td><input type="text" name="amount_val[<?=++$key?>]" value=""></td>
					<td><?=CCurrency::SelectBox("amount_currency[".$key."]", $val["CURRENCY"], "", True, "")?></td>
				</tr>
				<tr>
					<td><input type="text" name="amount_val[<?=++$key?>]" value=""></td>
					<td><?=CCurrency::SelectBox("amount_currency[".$key."]", $val["CURRENCY"], "", True, "")?></td>
				</tr>
				<tr>
					<td><input type="text" name="amount_val[<?=++$key?>]" value=""></td>
					<td><?=CCurrency::SelectBox("amount_currency[".$key."]", $val["CURRENCY"], "", True, "")?></td>
				</tr>

			</table>
		</td>
	</tr>
		<?
	}
	?>
	<tr class="heading">
		<td colspan="2"><?=GetMessage("SMO_ORDER_OPTIONS")?></td>
	</tr>
	<tr>
		<td colspan="2">
			<?
			$reminder = COption::GetOptionString("sale", "pay_reminder", "");
			$arReminder = unserialize($reminder);

			$arSubscribeProd = array();
			$subscribeProd = COption::GetOptionString("sale", "subscribe_prod", "");
			if (strlen($subscribeProd) > 0)
				$arSubscribeProd = unserialize($subscribeProd);

			$aTabs2 = Array();
			foreach($siteList as $val)
			{
				$aTabs2[] = Array("DIV"=>"reminder".$val["ID"], "TAB" => "[".$val["ID"]."] ".($val["NAME"]), "TITLE" => "[".$val["ID"]."] ".($val["NAME"]));
			}
			$tabControl2 = new CAdminViewTabControl("tabControl2", $aTabs2);
			$tabControl2->Begin();
			foreach($siteList as $val)
			{
				$tabControl2->BeginNextTab();
				?>
				<table cellspacing="5" cellpadding="0" border="0" width="100%" align="center">
				<tr class="heading">
					<td colspan="2"><?=GetMessage("SMO_PRODUCT_SUBSCRIBE")?></td>
				</tr>
				<tr>
					<td align="right" width="40%"><label for="notify-<?=$val["ID"]?>"><?=GetMessage("SALE_NOTIFY_PRODUCT_USE")?></label></td>
					<td width="60%"><input type="checkbox" name="subscribProd[<?=$val["ID"]?>][use]" value="Y" id="notify-<?=$val["ID"]?>"<?if($arSubscribeProd[$val["ID"]]["use"] == "Y") echo " checked";?>></td>
				</tr>
				<tr>
					<td align="right"><?=GetMessage("SALE_NOTIFY_PRODUCT")?></td>
					<td><input type="text" name="subscribProd[<?=$val["ID"]?>][del_after]" value="<?=intval($arSubscribeProd[$val["ID"]]["del_after"])?>" size="5" id="del-after-<?=$val["ID"]?>"></td>
				</tr>
				<tr class="heading">
					<td colspan="2"><?=GetMessage("SMO_ORDER_PAY_REMINDER")?></td>
				</tr>
				<tr>
					<td align="right" width="40%"><label for="use-<?=$val["ID"]?>"><?=GetMessage("SMO_ORDER_PAY_REMINDER_USE")?>:</label></td>
					<td width="60%"><input type="checkbox" name="reminder[<?=$val["ID"]?>][use]" value="Y" id="use-<?=$val["ID"]?>"<?if($arReminder[$val["ID"]]["use"] == "Y") echo " checked";?>></td>
				</tr>
				<tr>
					<td align="right"><label for="after-<?=$val["ID"]?>"><?=GetMessage("SMO_ORDER_PAY_REMINDER_AFTER")?>:</label></td>
					<td><input type="text" name="reminder[<?=$val["ID"]?>][after]" value="<?=intval($arReminder[$val["ID"]]["after"])?>" size="5" id="after-<?=$val["ID"]?>"></td>
				</tr>
				<tr>
					<td align="right"><label for="frequency-<?=$val["ID"]?>"><?=GetMessage("SMO_ORDER_PAY_REMINDER_FREQUENCY")?>:</label></td>
					<td><input type="text" name="reminder[<?=$val["ID"]?>][frequency]" value="<?=intval($arReminder[$val["ID"]]["frequency"])?>" size="5" id="frequency-<?=$val["ID"]?>"></td>
				</tr>
				<tr>
					<td align="right"><label for="period-<?=$val["ID"]?>"><?=GetMessage("SMO_ORDER_PAY_REMINDER_PERIOD")?>:</label></td>
					<td><input type="text" name="reminder[<?=$val["ID"]?>][period]" value="<?=intval($arReminder[$val["ID"]]["period"])?>" size="5" id="period-<?=$val["ID"]?>"></td>
				</tr>
				</table>
				<?
			}
			$tabControl2->End();
			?>
		</td>
	</tr>

	<?$tabControl->BeginNextTab();?>
<script language="javascript">
var cur_site = {WEIGHT:'<?=CUtil::JSEscape($siteList[0]["ID"])?>',ADDRESS:'<?=CUtil::JSEscape($siteList[0]["ID"])?>'};
function changeSiteList(value, add_id)
{
	var SLHandler = document.getElementById(add_id + '_site_id');
	SLHandler.disabled = value;
}

function changeStoreDeductCondition(value, control_id)
{
	var SLDeductCondition = document.getElementById(control_id);
	SLDeductCondition.disabled = value;
}

function selectSite(current, add_id)
{
	if (current == cur_site[add_id]) return;

	var last_handler = document.getElementById('par_' + add_id + '_' +cur_site[add_id]);
	var current_handler = document.getElementById('par_' + add_id + '_' + current);
	var CSHandler = document.getElementById(add_id + '_current_site');

	last_handler.style.display = 'none';
	current_handler.style.display = 'inline';

	cur_site[add_id] = current;
	CSHandler.value = current;

	return;
}

function setWeightValue(obj)
{
	if (!obj.value) return;

	var selectorUnit = document.forms.opt_form['weight_unit[' + cur_site['WEIGHT'] + ']'];
	var selectorKoef = document.forms.opt_form['weight_koef[' + cur_site['WEIGHT'] + ']'];

	if (selectorKoef && selectorUnit)
	{
		selectorKoef.value = obj.value;
		selectorUnit.value = obj.options[obj.selectedIndex].text;
	}
}

function showAccountNumberAdditionalFields(templateID)
{
	for (var i = 1; i < 6; i++)
	{
		BX("account_template_" + i).style.display = 'none';
	};

	if (templateID != 0)
	{
		BX("account_template_" + templateID).style.display = 'table-row';
	}
}

function showDateExample(period)
{
	if (period == 0)
		BX("account_number_date_example").innerHTML = "23042013&nbsp;/&nbsp;5";
	if (period == 1)
		BX("account_number_date_example").innerHTML = "042013&nbsp;/&nbsp;4";
	if (period == 2)
		BX("account_number_date_example").innerHTML = "2013&nbsp;/&nbsp;176";
}

function allowAutoDelivery(value)
{
	var allowDeliveryCheckbox = document.getElementById('PAYED_2_ALLOW_DELIVERY');

	if (value === false) {
		allowDeliveryCheckbox.disabled = true;
		allowDeliveryCheckbox.checked = false;
	} else {
		allowDeliveryCheckbox.disabled = false;
	}
}
</script>
	<tr>
		<td valign="top" width="40%"><?=GetMessage("SMO_PAR_DIF_SETTINGS")?></td>
		<td valign="top" width="60%"><input type="checkbox" name="WEIGHT_dif_settings" id="dif_settings" <? if(COption::GetOptionString($module_id, "WEIGHT_different_set", "N") == "Y") echo " checked=\"checked\"";?> OnClick="changeSiteList(!this.checked, 'WEIGHT')" /></td>
	</tr>
	<tr>
		<td><?=GetMessage("SMO_PAR_SITE_LIST")?></td>
		<td><select name="site" id="WEIGHT_site_id"<? if(COption::GetOptionString($module_id, "WEIGHT_different_set", "N") != "Y") echo " disabled=\"disabled\""; ?> OnChange="selectSite(this.value, 'WEIGHT')">
			<?
				for($i = 0; $i < $siteCount; $i++)
					echo "<option value=\"".($siteList[$i]["ID"])."\">".($siteList[$i]["NAME"])."</option>";
			?></select><input type="hidden" name="WEIGHT_current_site" id="WEIGHT_current_site" value="<?=($siteList[0]["ID"]);?>" /></td>
	</tr>
	<tr>
		<td valign="top" colspan="2">
	<?for ($i = 0; $i < $siteCount; $i++):?>
			<div id="par_WEIGHT_<?=($siteList[$i]["ID"])?>" style="display: <?=($i == 0 ? "inline" : "none");?>">
			<table cellpadding="0" cellspacing="2" class="adm-detail-content-table edit-table">
			<tr class="heading">
				<td align="center" colspan="2"><?echo GetMessage("SMO_PAR_SITE_PARAMETERS")?></td>
			</tr>
			<tr>
				<td width="40%" class="adm-detail-content-cell-l"><?echo GetMessage("SMO_PAR_SITE_WEIGHT_UNIT_SALE")?></td>
				<td width="60%" class="adm-detail-content-cell-r"><select name="weight_unit_tmp[<?=$siteList[$i]["ID"]?>]" OnChange="setWeightValue(this)">
						<option selected="selected"></option><?
					$arUnitList = CSalemeasure::GetList("W");
					foreach ($arUnitList as $key => $arM)
					{
						?>
						<option value="<?=floatval($arM["KOEF"])?>"><?=htmlspecialcharsbx($arM["NAME"])?></option>
						<?
					}

				?></select></td>
			</tr>
			<tr>
				<td class="adm-detail-content-cell-l"><?=GetMessage('SMO_PAR_WEIGHT_UNIT')?></td>
				<td class="adm-detail-content-cell-r"><input type="text" name="weight_unit[<?=$siteList[$i]["ID"]?>]" size="5" value="<?=htmlspecialcharsbx(COption::GetOptionString($module_id, "weight_unit", GetMessage('SMO_PAR_WEIGHT_UNIT_GRAMM'), $siteList[$i]["ID"]))?>" /></td>
			</tr>
			<tr>
				<td class="adm-detail-content-cell-l"><?=GetMessage('SMO_PAR_WEIGHT_KOEF')?></td>
				<td class="adm-detail-content-cell-r"><input type="text" name="weight_koef[<?=$siteList[$i]["ID"]?>]" size="5" value="<?=htmlspecialcharsbx(COption::GetOptionString($module_id, "weight_koef", "1", $siteList[$i]["ID"]))?>" /></td>
			</tr>
			</table>
			</div>
	<?endfor;?>
		</td>
	</tr>

<?$tabControl->BeginNextTab();?>
	<tr>
		<td width="40%"><?=GetMessage("SMO_DIF_SETTINGS")?></td>
		<td width="60%"><input type="checkbox" name="ADDRESS_dif_settings" id="ADDRESS_dif_settings"<? if(COption::GetOptionString($module_id, "ADDRESS_different_set", "N") != "N") echo " checked=\"checked\"";?> OnClick="changeSiteList(!this.checked, 'ADDRESS')" /></td>
	</tr>
	<tr>
		<td><?=GetMessage("SMO_SITE_LIST")?></td>
		<td><select name="site" id="ADDRESS_site_id"<? if(COption::GetOptionString($module_id, "ADDRESS_different_set", "N") != "Y") echo " disabled=\"disabled\""; ?> onChange="selectSite(this.value, 'ADDRESS')">
			<?
				for($i = 0; $i < $siteCount; $i++)
					echo "<option value=\"".($siteList[$i]["ID"])."\">".($siteList[$i]["NAME"])."</option>";
			?></select><input type="hidden" name="ADDRESS_current_site" id="ADDRESS_current_site" value="<?=($siteList[0]["ID"]);?>" /></td>
	</tr>
	<tr>
		<td colspan="2" valign="top">
<?
for ($i = 0; $i < $siteCount; $i++):
	$location_zip = intval(COption::GetOptionString('sale', 'location_zip', '', $siteList[$i]["ID"]));
	$location = intval(COption::GetOptionString('sale', 'location', '', $siteList[$i]["ID"]));

	if ($location_zip == 0) $location_zip = '';
?>
		<div  id="par_ADDRESS_<?=($siteList[$i]["ID"])?>" style="display: <?=($i == 0 ? "inline" : "none");?>">
		<table cellpadding="0" cellspacing="2" border="0" width="60%" align="center">
			<tr class="heading">
				<td align="center" colspan="2"><?echo GetMessage("SMO_PAR_SITE_ADRES")?></td>
			</tr>
			<tr>
				<td width="40%" class="adm-detail-content-cell-l"><?echo GetMessage("SMO_LOCATION_ZIP");?></td>
				<td width="60%" class="adm-detail-content-cell-r"><input type="text" name="location_zip[<?=$siteList[$i]["ID"]?>]" value="<?=$location_zip?>" size="5" /></td>
			</tr>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("SMO_LOCATION_CITY");?></td>
				<td class="adm-detail-content-cell-r">
					<select name="location[<?=$siteList[$i]["ID"]?>]">
						<option value=''></option>
						<?$dbLocationList = CSaleLocation::GetList(
							Array(
								"COUNTRY_NAME_LANG"=>"ASC",
								"REGION_NAME_LANG"=>"ASC",
								"CITY_NAME_LANG"=>"ASC"
							),
							array(),
							LANG);
						?>
						<?while ($arLocation = $dbLocationList->GetNext()):
							$locationName = $arLocation["COUNTRY_NAME"];

							if (strlen($arLocation["REGION_NAME"]) > 0)
							{
								if (strlen($locationName) > 0)
									$locationName .= " - ";
								$locationName .= $arLocation["REGION_NAME"];
							}
							if (strlen($arLocation["CITY_NAME"]) > 0)
							{
								if (strlen($locationName) > 0)
									$locationName .= " - ";
								$locationName .= $arLocation["CITY_NAME"];
							}
						?>
							<option value="<?=$arLocation["ID"]?>"<?=($location == $arLocation["ID"] ? " selected=\"selected\"" : "")?>> <?echo htmlspecialcharsbx($locationName)?> </option>
						<?endwhile;?>
					</select>
				</td>
			</tr>
		</table>
		</div>
<?
endfor;
?>
		</td>
	</tr>
<?if (CBXFeatures::IsFeatureEnabled('SaleCCards') && COption::GetOptionString($module_id, "use_ccards", "N") == "Y")
{
	?>
	<?$tabControl->BeginNextTab();?>

	<?
	if (!CSaleUserCards::CheckPassword())
	{
		?><tr>
			<td colspan="2"><?CAdminMessage::ShowMessage(str_replace("#ROOT#", $_SERVER["DOCUMENT_ROOT"], GetMessage("SMO_NO_VALID_PASSWORD")))?></td>
		</tr><?
	}
	?>
	<tr>
		<td valign="top" width="50%">

				<?= GetMessage("SMO_PATH2CRYPT_FILE") ?>

		</td>
		<td valign="middle" width="50%">

				<input type="text" size="40" value="<?= htmlspecialcharsbx(COption::GetOptionString("sale", "sale_data_file", "")) ?>" name="sale_data_file">

		</td>
	</tr>
	<tr>
		<td valign="top">

				<?= GetMessage("SMO_CRYPT_ALGORITHM") ?>

		</td>
		<td valign="middle">

				<?
				$val = COption::GetOptionString("sale", "crypt_algorithm", "RC4");
				?>
				<select name="crypt_algorithm">
					<option value="RC4"<?if ($val=="RC4") echo " selected";?>>RC4</option>
					<option value="AES"<?if ($val=="AES") echo " selected";?>>AES (Rijndael) - <?= GetMessage("SMO_NEED_MCRYPT") ?></option>
					<option value="3DES"<?if ($val=="3DES") echo " selected";?>>3DES (Triple-DES) - <?= GetMessage("SMO_NEED_MCRYPT") ?></option>
				</select>

		</td>
	</tr>
	<?
}
?>
<?$tabControl->BeginNextTab();?>
	<tr class="heading">
		<td colspan="2"><?=GetMessage("SMO_ADDITIONAL_SITE_PARAMS")?></td>
	</tr>
	<tr>
		<td colspan="2" align="center">
		<table cellspacing="0" cellpadding="0" border="0" class="internal">
		<tr class="heading">
			<td valign="top">
				<?echo GetMessage("SALE_LANG")?>
			</td>
			<td valign="top">
				<?echo GetMessage("SALE_CURRENCY")?>
			</td>
			<td valign="top">
				<?= GetMessage("SMO_GROUPS2SITE") ?>
			</td>
		</tr>
		<?
		foreach($siteList as $val)
		{
			?>
			<tr>
				<td valign="top">
					[<a href="site_edit.php?LID=<?=$val["ID"]?>&lang=<?=LANGUAGE_ID?>" title="<?=GetMessage("SALE_SITE_ALT")?>"><?echo $val["ID"] ?></a>] <?echo ($val["NAME"]) ?>
				</td>
				<td valign="top">

					<?
					$arCurr = CSaleLang::GetByID($val["ID"]);
					echo CCurrency::SelectBox("CURRENCY_".$val["ID"], $arCurr["CURRENCY"], GetMessage("SALE_NOT_SET"), True, "");
					?>

				</td>
				<td valign="top">

					<?
					$arCurrentGroups = array();
					$dbSiteGroupsList = CSaleGroupAccessToSite::GetList(
							array(),
							array("SITE_ID" => $val["ID"])
						);
					while ($arSiteGroup = $dbSiteGroupsList->Fetch())
					{
						$arCurrentGroups[] = IntVal($arSiteGroup["GROUP_ID"]);
					}

					if (!isset($LOCAL_USER_GROUPS_CACHE) || !is_array($LOCAL_USER_GROUPS_CACHE))
					{
						$LOCAL_USER_GROUPS_CACHE = array();

						$dbGroups = CGroup::GetList(
								($b = "c_sort"),
								($o = "asc"),
								array("ANONYMOUS" => "N")
							);
						while ($arGroup = $dbGroups->Fetch())
						{
							$arGroup["ID"] = IntVal($arGroup["ID"]);

							if ($arGroup["ID"] == 1 || $arGroup["ID"] == 2)
								continue;

							$LOCAL_USER_GROUPS_CACHE[] = $arGroup;
						}
					}
					?>
					<select name="SITE_USER_GROUPS_<?= $val["ID"] ?>[]" multiple size="5">
						<?
						for ($i = 0, $intCount = count($LOCAL_USER_GROUPS_CACHE); $i < $intCount; $i++)
						{
							?><option value="<?= $LOCAL_USER_GROUPS_CACHE[$i]["ID"] ?>"<?if (in_array($LOCAL_USER_GROUPS_CACHE[$i]["ID"], $arCurrentGroups)) echo " selected";?>><?= htmlspecialcharsEx($LOCAL_USER_GROUPS_CACHE[$i]["NAME"]) ?></option><?
						}
						?>
					</select>

				</td>
			</tr>
			<?
		}
		?>
		</table>
		</td>
	</tr>
<?$tabControl->BeginNextTab();?>
<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");?>
<?$tabControl->Buttons();?>
<script language="JavaScript">
function RestoreDefaults()
{
	if (confirm('<?echo AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>'))
		window.location = "<?echo $APPLICATION->GetCurPage()?>?RestoreDefaults=Y&lang=<?echo LANG?>&mid=<?echo urlencode($mid)?>";
}
</script>

<input type="submit" <?if ($SALE_RIGHT<"W") echo "disabled" ?> name="Update" value="<?echo GetMessage("MAIN_SAVE")?>" class="adm-btn-save">
<input type="hidden" name="Update" value="Y">
<?if(strlen($_REQUEST["back_url_settings"])>0):?>
	<input type="button" name="Cancel" value="<?=GetMessage("MAIN_OPT_CANCEL")?>" onclick="window.location='<?echo htmlspecialcharsbx(CUtil::addslashes($_REQUEST["back_url_settings"]))?>'">
	<input type="hidden" name="back_url_settings" value="<?=htmlspecialcharsbx($_REQUEST["back_url_settings"])?>">
<?endif;?>
<input type="button" <?if ($SALE_RIGHT<"W") echo "disabled" ?> title="<?echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" OnClick="RestoreDefaults();" value="<?echo GetMessage("MAIN_RESTORE_DEFAULTS")?>">
<?$tabControl->End();?>
</form>
<?endif;?>
