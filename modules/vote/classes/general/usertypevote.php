<?
IncludeModuleLangFile(__FILE__);

class CUserTypeVote extends CUserTypeInteger
{
	public static function GetUserTypeDescription()
	{
		AddEventHandler("main", "OnBeforeUserTypeUpdate", array(__CLASS__, "CheckSettings"));
		AddEventHandler("main", "OnBeforeUserTypeAdd", array(__CLASS__, "CheckSettings"));
		if (IsModuleInstalled("blog"))
		{
			AddEventHandler("blog", "OnBeforePostUserFieldUpdate", array(__CLASS__, "OnBeforePostUserFieldUpdate"));
		}

		return array(
			"USER_TYPE_ID" => "vote",
			"CLASS_NAME" => __CLASS__,
			"DESCRIPTION" => GetMessage("V_USER_TYPE_DESCRIPTION"),
			"BASE_TYPE" => "int",
		);
	}

	static public function OnBeforePostUserFieldUpdate($ENTITY_ID, $ID, $arFields)
	{
		global $USER_FIELD_MANAGER;
		$arUserFields = $USER_FIELD_MANAGER->GetUserFields($ENTITY_ID, $ID, LANGUAGE_ID);
		if (is_array($arUserFields) && !empty($arUserFields))
		{
			$arUserFields = array_intersect_key($arUserFields, $arFields);
			$path = str_replace("#post_id#", $ID, $arFields["PATH"]);
			$arUserField = reset($arUserFields);
			do {
				if (is_array($arUserField["USER_TYPE"]) &&
					$arUserField["USER_TYPE"]["USER_TYPE_ID"] == "vote" &&
					$arUserField["USER_TYPE"]["CLASS_NAME"] == __CLASS__ &&
					isset($GLOBALS[__CLASS__.$arUserField["ENTITY_VALUE_ID"]]))
				{
					$GLOBALS[__CLASS__.$arUserField["ENTITY_VALUE_ID"]]["VOTE"]["URL"] = $path;
				}
			} while ($arUserField = next($arUserFields));
		}
	}

	/*
	 * Prepares data("SETTINGS") for serialization in functions CUserTypeEntity::Add & CUserTypeEntity::Update
	 */
	public static function PrepareSettings($arUserField)
	{
		$arUserField["SETTINGS"] = (is_array($arUserField["SETTINGS"]) ? $arUserField["SETTINGS"] : @unserialize($arUserField["SETTINGS"]));
		$arUserField["SETTINGS"] = (is_array($arUserField["SETTINGS"]) ? $arUserField["SETTINGS"] : array());
		$tmp = array("CHANNEL_ID" => intval($arUserField["SETTINGS"]["CHANNEL_ID"]));

		if ($arUserField["SETTINGS"]["CHANNEL_ID"] == "add")
		{
			$tmp["CHANNEL_TITLE"] = trim($arUserField["SETTINGS"]["CHANNEL_TITLE"]);
			$tmp["CHANNEL_SYMBOLIC_NAME"] = trim($arUserField["SETTINGS"]["CHANNEL_SYMBOLIC_NAME"]);
			$tmp["CHANNEL_USE_CAPTCHA"] = ($arUserField["SETTINGS"]["CHANNEL_USE_CAPTCHA"] == "Y" ? "Y" : "N");
		}

		$uniqType = $arUserField["SETTINGS"]["UNIQUE"];
		if (is_array($arUserField["SETTINGS"]["UNIQUE"]))
		{
			$uniqType = 0;
			foreach ($arUserField["SETTINGS"]["UNIQUE"] as $z)
				$uniqType |= $z;
			$uniqType += 5;
		}

		$tmp["UNIQUE"] = $uniqType;
		$tmp["UNIQUE_IP_DELAY"] = is_array($arUserField["SETTINGS"]["UNIQUE_IP_DELAY"]) ?
			$arUserField["SETTINGS"]["UNIQUE_IP_DELAY"] : array();
		$tmp["NOTIFY"] = (in_array($arUserField["SETTINGS"]["NOTIFY"], array("I", "Y", "N")) ?
			$arUserField["SETTINGS"]["NOTIFY"] : "N");

		return $tmp;
	}
	/*
	 * Checks CHANNEL or creates add vote group.
	 */
	public static function CheckSettings(&$arParams)
	{
		$arSettings = (is_array($arParams["SETTINGS"]) ? $arParams["SETTINGS"] : @unserialize($arParams["SETTINGS"]));
		$arSettings = is_array($arSettings) ? $arSettings : array($arSettings);
		if (array_key_exists("CHANNEL_ID", $arSettings))
		{
			$arSettings["CHANNEL_ID"] = intval($arSettings["CHANNEL_ID"]);
			if ($arSettings["CHANNEL_ID"] <= 0 && CModule::IncludeModule("vote"))
			{
				$db_res = CVoteChannel::GetList($by = "ID", $order = "ASC",
					array("SYMBOLIC_NAME" => $arSettings["CHANNEL_SYMBOLIC_NAME"], "SYMBOLIC_NAME_EXACT_MATCH" => "Y"), $is_filtered);
				if (!($db_res && ($arChannel = $db_res->Fetch()) && !!$arChannel))
				{
					$res = array(
						"TITLE" => $arSettings["CHANNEL_TITLE"],
						"SYMBOLIC_NAME" => $arSettings["CHANNEL_SYMBOLIC_NAME"],
						"ACTIVE" => "Y",
						"HIDDEN" => "Y",
						"C_SORT" => 100,
						"VOTE_SINGLE" => "N",
						"USE_CAPTCHA" => $arSettings["CHANNEL_USE_CAPTCHA"],
						"SITE" => array(),
						"GROUP_ID" => array()
					);
					$by = "sort"; $order = "asc";
					$db_res = CSite::GetList($by, $order);
					while ($site = $db_res->GetNext())
						$res["SITE"][] = $site["ID"];
					$db_res = CGroup::GetList($by = "sort", $order = "asc", Array("ADMIN" => "N"));
					while ($group = $db_res->GetNext())
						$res["GROUP_ID"][$group["ID"]] = ($group["ID"] == 2 ? 1 : 4);
					$res["GROUP_ID"] = (is_array($arSettings["GROUP_ID"]) ? array_intersect_key($arSettings["GROUP_ID"], $res["GROUP_ID"]) : $res["GROUP_ID"]);
					$channelId = CVoteChannel::Add($res);
				}
				else
				{
					$channelId = $arChannel["ID"];
				}

				$arSettings["CHANNEL_ID"] = $channelId;
				unset($arSettings["CHANNEL_TITLE"]);
				unset($arSettings["CHANNEL_SYMBOLIC_NAME"]);
				unset($arSettings["CHANNEL_USE_CAPTCHA"]);
				if (!$arSettings["CHANNEL_ID"])
					return false;
			}
			$uniqType = $arSettings["UNIQUE"];
			if (is_array($arSettings["UNIQUE"]))
			{
				foreach ( $arSettings["UNIQUE"] as $res)
					$uniqType |= $res;
				$uniqType += 5;
			}

			$arSettings["UNIQUE"] = $uniqType;
			$arSettings["UNIQUE_IP_DELAY"] = is_array($arSettings["UNIQUE_IP_DELAY"]) ?
				$arSettings["UNIQUE_IP_DELAY"] : array("DELAY" => "10", "DELAY_TYPE" => "D");
			$arParams["SETTINGS"] = serialize($arSettings);
			$arParams["MULTIPLE"] = "N";
			$arParams["MANDATORY"] = "N";
			$arParams["SHOW_FILTER"] = "N";
			$arParams["IS_SEARCHABLE"] = "N";
		}
		return true;
	}

	/**
	 * Shows data form in admin part when you edit or add usertype.
	 * @param bool $arUserField
	 * @param string $arHtmlControl
	 * @param bool $bVarsFromForm
	 * @return string
	 */
	public static function GetSettingsHTML($arUserField = false, $arHtmlControl, $bVarsFromForm)
	{
		if (!CModule::IncludeModule("vote"))
			return '';
		$value = "";
		if($bVarsFromForm)
			$value = $GLOBALS[$arHtmlControl["NAME"]]["CHANNEL_ID"];
		elseif(is_array($arUserField))
		{
			$value = $arUserField["SETTINGS"]["CHANNEL_ID"];
			$GLOBALS[$arHtmlControl["NAME"]]["NOTIFY"] = $arUserField["SETTINGS"]["NOTIFY"];
		}
		$value = (!empty($value) ? intval($value) : "add");
		$db_res = CVoteChannel::GetList($by = "", $order = "", array("ACTIVE" => "Y"), $is_filtered);
		$arVoteChannels = array("reference" => array(GetMessage("V_NEW_CHANNEL")), "reference_id" => array("add"));
		if ($db_res && $res = $db_res->Fetch())
		{
			do
			{
				$arVoteChannels["reference"][] = $res["TITLE"];
				$arVoteChannels["reference_id"][] = $res["ID"];
			} while ($res = $db_res->Fetch());
		}

		ob_start();
?>
	<tr>
		<td><?=GetMessage("V_CHANNEL_ID_COLON")?></td>
		<td><?=str_replace(
			"<select",
			"<select onchange='if(this.value!=\"add\"){BX.hide(BX(\"channel_create\"));BX.show(this.nextSibling);}".
				"else{BX(\"channel_create\").style.display=\"\";BX.hide(this.nextSibling);}' ",
			SelectBoxFromArray(
				$arHtmlControl["NAME"]."[CHANNEL_ID]",
				$arVoteChannels,
				$value)
			)?><a style="margin-left: 1em;" href="" rel="/bitrix/admin/vote_channel_edit.php?ID=#id#" <?
			?>onmousedown="this.href=this.rel.replace('#id#',this.previousSibling.value);"><?=GetMessage("V_CHANNEL_ID_EDIT")?></a></td>
	</tr>
	<tbody id="channel_create" style="<?if ($value != "add"): ?>display:none;<? endif; ?>">
	<tr class="adm-detail-required-field">
		<td class="adm-detail-content-cell-l" width="40%"><?=GetMessage("V_CHANNEL_ID_TITLE")?></td>
		<td class="adm-detail-content-cell-r" width="60%"><input type="text" name="<?=$arHtmlControl["NAME"]?>[CHANNEL_TITLE]" <?
			?>value="<?=htmlspecialcharsbx($GLOBALS[$arHtmlControl["NAME"]]["CHANNEL_TITLE"]);?>" /></td>
	</tr>
	<tr class="adm-detail-required-field">
		<td class="adm-detail-content-cell-l"><?=GetMessage("V_CHANNEL_ID_SYMBOLIC_NAME")?></td>
		<td class="adm-detail-content-cell-r"><input type="text" name="<?=$arHtmlControl["NAME"]?>[CHANNEL_SYMBOLIC_NAME]" <?
			?>value="<?=htmlspecialcharsbx($GLOBALS[$arHtmlControl["NAME"]]["CHANNEL_SYMBOLIC_NAME"]);?>" /></td>
	</tr>
	<tr>
		<td class="adm-detail-content-cell-l">&nbsp;</td>
		<td class="adm-detail-content-cell-r"><input type="checkbox" name="<?=$arHtmlControl["NAME"]?>[CHANNEL_USE_CAPTCHA]" <?
			?>id="CHANNEL_USE_CAPTCHA" <?if ($GLOBALS[$arHtmlControl["NAME"]]["CHANNEL_USE_CAPTCHA"] == "Y"): ?> checked <? endif;
			?>value="Y" /> <label for="CHANNEL_USE_CAPTCHA"><?=GetMessage("V_CHANNEL_ID_USE_CAPTCHA")?></label></td>
	</tr><?
	$db_res = CGroup::GetList($by = "sort", $order = "asc", Array("ADMIN" => "N"));
	while ($group = $db_res->GetNext())
	{
		if($bVarsFromForm)
			$value = $GLOBALS[$arHtmlControl["NAME"]]["GROUP_ID"][$group["ID"]];
		else
			$value = ($group["ID"] == 2 ? 1 : ($group["ID"] == 1 ? 4 : 2));
?>
	<tr>
		<td class="adm-detail-content-cell-l"><?=$group["NAME"].":"?></td>
		<td class="adm-detail-content-cell-r"><?=SelectBoxFromArray("GROUP_ID[".$group["ID"]."]", $GLOBALS["aVotePermissions"], $value);?></td>
	</tr><?
	}

?>
	</tbody>
<?
		if($bVarsFromForm)
		{
			$GLOBALS[$arHtmlControl["NAME"]]['UNIQUE'] = is_array($GLOBALS[$arHtmlControl["NAME"]]['UNIQUE']) ?
				$GLOBALS[$arHtmlControl["NAME"]]['UNIQUE'] : array();
			$uniqType = 0;
			foreach ($GLOBALS[$arHtmlControl["NAME"]]['UNIQUE'] as $res)
				$uniqType |= $res;
		}
		else
		{
			$uniqType = ($arUserField["SETTINGS"]["UNIQUE"] ? $arUserField["SETTINGS"]["UNIQUE"] : 13);
			if (is_array($arUserField["SETTINGS"]["UNIQUE"]))
			{
				foreach ( $arUserField["SETTINGS"]["UNIQUE"] as $res)
					$uniqType |= $res;
				$uniqType += 5;
			}
			$uniqType -=5;
		}
?>
<script language="javascript">
function __utch(show)
{
	if (BX("UNIQUE_TYPE_IP").checked)
		BX.show(BX("DELAY_TYPE"), "");
	else
		BX.hide(BX("DELAY_TYPE"));

	var
		show = BX("UNIQUE_TYPE_USER_ID").checked,
		res = BX("UNIQUE_TYPE_USER_ID_NEW");
	res.disabled = !show;
	if (!!show)
		BX.show(res.parentNode.parentNode, "");
	else
		BX.hide(res.parentNode.parentNode);
}
</script>
	<tr>
		<td class="adm-detail-content-cell-l adm-detail-valign-top" width="40%"><?=GetMessage("VOTE_NOTIFY")?></td>
		<td class="adm-detail-content-cell-r" width="60%"><?
			$GLOBALS[$arHtmlControl["NAME"]]["NOTIFY"] = (
				$GLOBALS[$arHtmlControl["NAME"]]["NOTIFY"] != "I" && $GLOBALS[$arHtmlControl["NAME"]]["NOTIFY"] != "Y" ?
					"N" : $GLOBALS[$arHtmlControl["NAME"]]["NOTIFY"]);
			if (IsModuleInstalled("im")): ?>
				<?=InputType("radio", $arHtmlControl["NAME"]."[NOTIFY]", "I", $GLOBALS[$arHtmlControl["NAME"]]["NOTIFY"], false, GetMessage("VOTE_NOTIFY_IM"))?><br /><?
			else:
				$GLOBALS[$arHtmlControl["NAME"]]["NOTIFY"] = ($GLOBALS[$arHtmlControl["NAME"]]["NOTIFY"] == "I" ?
					"N" : $GLOBALS[$arHtmlControl["NAME"]]["NOTIFY"]);
			endif; ?>
			<?=InputType("radio", $arHtmlControl["NAME"]."[NOTIFY]", "Y", $GLOBALS[$arHtmlControl["NAME"]]["NOTIFY"], false, GetMessage("VOTE_NOTIFY_EMAIL"))?><br />
			<?=InputType("radio", $arHtmlControl["NAME"]."[NOTIFY]", "N", $GLOBALS[$arHtmlControl["NAME"]]["NOTIFY"], false, GetMessage("VOTE_NOTIFY_N"))?><?
			?></td>
	</tr>
	<tr>
		<td class="adm-detail-content-cell-l adm-detail-valign-top"><?=GetMessage("V_UNIQUE")?></td>
		<td class="adm-detail-content-cell-r">
			<? if (IsModuleInstalled('statistic')): ?>
			<input type="checkbox" name="<?=$arHtmlControl["NAME"]?>[UNIQUE][]" id="UNIQUE_TYPE_SESSION" value="1" <?=($uniqType & 1)?" checked":""?> />
			<label for="UNIQUE_TYPE_SESSION"><?=GetMessage("V_UNIQUE_SESSION")?></label><br />
			<? endif; ?>
			<input type="checkbox" name="<?=$arHtmlControl["NAME"]?>[UNIQUE][]" id="UNIQUE_TYPE_COOKIE" value="2" <?=($uniqType & 2)?" checked":""?> />
			<label for="UNIQUE_TYPE_COOKIE"><?=GetMessage("V_UNIQUE_COOKIE_ONLY")?></label><br />
			<input type="checkbox" name="<?=$arHtmlControl["NAME"]?>[UNIQUE][]" id="UNIQUE_TYPE_IP" onclick="__utch()" value="4" <?
				?><?=($uniqType & 4) ? " checked":""?> />
			<label for="UNIQUE_TYPE_IP"><?=GetMessage("V_UNIQUE_IP_ONLY")?></label><br />
			<input type="checkbox" name="<?=$arHtmlControl["NAME"]?>[UNIQUE][]" id="UNIQUE_TYPE_USER_ID" onclick="__utch();" value="8" <?
				?><?=($uniqType & 8)?" checked":""?> />
			<label for="UNIQUE_TYPE_USER_ID"><?=GetMessage("V_UNIQUE_USER_ID_ONLY")?></label><br />
		</td>
	</tr>
	<tr>
		<td class="adm-detail-content-cell-l" width="40%">&nbsp;</td>
		<td class="adm-detail-content-cell-r" width="60%"><input type="checkbox" name="<?=$arHtmlControl["NAME"]?>[UNIQUE][]" id="UNIQUE_TYPE_USER_ID_NEW" value="16" <?
			?><?=($uniqType & 16)?" checked ":""?><?
			?><?=($uniqType & 8)?"": " disabled"?> /> <label for="UNIQUE_TYPE_USER_ID_NEW"><?=GetMessage("V_UNIQUE_USER_ID_NEW")?></label>
		</td>
	</tr>
	<?
		if($bVarsFromForm)
			$value = $GLOBALS[$arHtmlControl["NAME"]]["UNIQUE_IP_DELAY"];
		else
			$value = (is_array($arUserField) ?
				$arUserField["SETTINGS"]["UNIQUE_IP_DELAY"] :
					array("DELAY" => "10", "DELAY_TYPE" => "D"));
		?>
	<tr id="DELAY_TYPE">
		<td class="adm-detail-content-cell-l" width="40%"><?=GetMessage("V_UNIQUE_IP_DELAY")?></td>
		<td class="adm-detail-content-cell-r" width="60%">
			<input type="text" name="<?=$arHtmlControl["NAME"]?>[UNIQUE_IP_DELAY][DELAY]" value="<?=htmlspecialcharsbx($value["DELAY"]);?>" />
			<?=SelectBoxFromArray(
				$arHtmlControl["NAME"]."[UNIQUE_IP_DELAY][DELAY_TYPE]",
				array(
					"reference_id" => array("S", "M", "H", "D"),
					"reference" => array(
						GetMessage("V_SECONDS"), GetMessage("V_MINUTES"),
						GetMessage("V_HOURS"), GetMessage("V_DAYS"))
				),
				$value["DELAY_TYPE"]);?>
<script type="text/javascript">
BX.ready(function(){
	if (!!document.forms.post_form.MULTIPLE)
		BX.hide(document.forms.post_form.MULTIPLE.parentNode.parentNode);
	__utch();
});
</script>

		</td>
	</tr>
	<?
	return ob_get_clean();
	}

	public static function CheckFields($arUserField, $value)
	{
		if (!($arUserField && is_array($arUserField["USER_TYPE"]) &&
			$arUserField["USER_TYPE"]["CLASS_NAME"] == __CLASS__))
			return true;
		$arData = (isset($GLOBALS[$arUserField["FIELD_NAME"]."_DATA"]) ? $GLOBALS[$arUserField["FIELD_NAME"]."_DATA"] : false);
		$aMsg = array();

		if (!empty($arData) && CModule::IncludeModule("vote"))
		{
			$arVote = array(
				"ID" => $value,
				"CHANNEL_ID" => $arUserField["SETTINGS"]["CHANNEL_ID"],
				"TITLE" => $arData["TITLE"],
				"URL" => $arData["URL"],
				"NOTIFY" => $arUserField["SETTINGS"]["NOTIFY"],
				"DATE_END" => GetTime((isset($arData["DATE_END"]) ? MakeTimeStamp($arData["DATE_END"]) : 1924984799), "FULL"),
				"QUESTIONS" => array());

			$arVoteQuestions = array();
			$arQuestions = is_array($arData["QUESTIONS"]) ? $arData["QUESTIONS"] : array();

			if (!$arVote["ID"])
			{
				$arVote["DATE_START"] = GetTime(CVote::GetNowTime(), "FULL");
			}
			else
			{
				$db_res = CVoteQuestion::GetListEx(array("ID" => "ASC"),
					array("CHANNEL_ID" => $arVote["CHANNEL_ID"], "VOTE_ID" => $arVote["ID"]));
				if ($db_res && $res = $db_res->Fetch())
				{
					do {
						$arVoteQuestions[$res["ID"]] = $res + array("ANSWERS" => array());
					} while ($res = $db_res->Fetch());
				}
				if (!empty($arVoteQuestions))
				{
					$db_res = CVoteAnswer::GetListEx(array("ID" => "ASC"),
						array("CHANNEL_ID" => $arVote["CHANNEL_ID"], "VOTE_ID" => $arVote["ID"]));
					if ($db_res && $res = $db_res->Fetch())
					{
						do {
							if (is_set($arVoteQuestions, $res["QUESTION_ID"]))
								$arVoteQuestions[$res["QUESTION_ID"]]["ANSWERS"][$res["ID"]] = $res;
						} while ($res = $db_res->Fetch());
					}
				}
			}

			foreach ($arQuestions as $key => $arQuestion)
			{
				$res = array(
					"ID" => (array_key_exists($arQuestion["ID"], $arVoteQuestions) ? $arQuestion["ID"] : false),
					"QUESTION" => trim($arQuestion["QUESTION"]),
					"QUESTION_TYPE" => trim($arQuestion["QUESTION_TYPE"]),
					"MULTI" => ($arQuestion["MULTI"] == "Y" ? "Y" : "N"),
					"ANSWERS" => array());

				$arQuestion["ANSWERS"] = (is_array($arQuestion["ANSWERS"]) ?
					$arQuestion["ANSWERS"] : array());

				$arVoteAnswers = ($res["ID"] > 0 ? $arVoteQuestions[$res["ID"]]["ANSWERS"] : array());
				foreach ($arQuestion["ANSWERS"] as $arAnswer)
				{
					$resa = array(
						"ID" => (array_key_exists($arAnswer["ID"], $arVoteAnswers) ? $arAnswer["ID"] : false),
						"MESSAGE" => trim($arAnswer["MESSAGE"]),
						"MESSAGE_TYPE" => trim($arAnswer["MESSAGE_TYPE"]),
						"FIELD_TYPE" => ($res["MULTI"] == "Y" ? 1 : 0));

					if (empty($resa["MESSAGE"]))
						continue;
					if (!!$resa["ID"])
						unset($arVoteAnswers[$resa["ID"]]);

					$res["ANSWERS"][] = $resa;
				}
				foreach ($arVoteAnswers as $arAnswer)
					$res["ANSWERS"][] = array_merge($arAnswer, array("DEL" => "Y"));

				if (empty($res["ANSWERS"]) && empty($res["QUESTION"]) && !$res["ID"])
					continue;
				if (!!$res["ID"])
					unset($arVoteQuestions[$res["ID"]]);

				$arVote["QUESTIONS"][] = $res;
			}

			$arVoteParams = array();
			if (!empty($arVote["QUESTIONS"]))
			{
				$arVoteParams = array(
					"UNIQUE_TYPE" => $arUserField["SETTINGS"]['UNIQUE'],
					"DELAY" => intval($arUserField["SETTINGS"]['UNIQUE_IP_DELAY']["DELAY"]),
					"DELAY_TYPE" => $arUserField["SETTINGS"]['UNIQUE_IP_DELAY']["DELAY_TYPE"]);
			}

			if (!VoteVoteEditFromArray($arUserField["SETTINGS"]["CHANNEL_ID"], $arVote["ID"],
				$arVote, ($res = ($arVoteParams + array("bOnlyCheck" => "Y")))))
			{
				$aMsg[] = array(
					"id" => $arUserField["FIELD_NAME"],
					"text" => (($e = $GLOBALS['APPLICATION']->GetException()) && $e ? preg_replace("/\<br(.*?)\>/", " ", $e->GetString()) : GetMessage("VT_UNKNOWN_ERROR_ADD_VOTE"))
				);
			}
			else
			{
				$GLOBALS[__CLASS__.$arUserField["ENTITY_VALUE_ID"]] = array("VOTE" => $arVote, "PARAMS" => $arVoteParams);
			}
		}
		return $aMsg;
	}

	public static function OnBeforeSave($arUserField, $value)
	{
		$arVote = $GLOBALS[__CLASS__.$arUserField["ENTITY_VALUE_ID"]]["VOTE"];
		$arVoteParams = $GLOBALS[__CLASS__.$arUserField["ENTITY_VALUE_ID"]]["PARAMS"];
		unset($GLOBALS[__CLASS__.$arUserField["ENTITY_VALUE_ID"]]);
		$res = VoteVoteEditFromArray($arUserField["SETTINGS"]["CHANNEL_ID"], $value, $arVote, $arVoteParams);
		if ($res === true)
			return 0;
		return $res;
	}

	public static function GetEditFormHTML($arUserField, $arHtmlControl)
	{
		if (!empty($arHtmlControl))
		{
			if (array_key_exists("VALUE", $arHtmlControl))
				$arUserField["VALUE"] = $arHtmlControl["VALUE"];
			if (array_key_exists("NAME", $arHtmlControl))
				$arUserField["FIELD_NAME"] = $arHtmlControl["NAME"];
		}
		ob_start();
		$GLOBALS["APPLICATION"]->IncludeComponent(
			"bitrix:system.field.edit",
			"vote",
			array(
				"bVarsFromForm" => false,
				"arUserField" => $arUserField,
				"form_name" => "wd_upload_form"
			), null, array("HIDE_ICONS" => "Y")
		);
		return ob_get_clean();
	}

	public static function GetPublicViewHTML($arProperty, $value, $strHTMLControlName)
	{
		ob_start();
		$GLOBALS["APPLICATION"]->IncludeComponent(
			"bitrix:system.field.view",
			"vote",
			array(
				"bVarsFromForm" => false,
				"form_name" => "wd_upload_form"
			), null, array("HIDE_ICONS" => "Y")
		);
		return ob_get_clean();
	}

	public static function GetFilterHTML($arUserField, $arHtmlControl)
	{
		return '';
	}

	public static function GetAdminListViewHTML($arUserField, $arHtmlControl)
	{
		$return = '&nbsp;';
		$return_url = $GLOBALS["APPLICATION"]->GetCurPageParam("", array("admin_history", "mode", "table_id"));

		if($arHtmlControl["VALUE"] > 0)
		{
			$db_res = CVote::GetByIDEx($arHtmlControl["VALUE"]);
			if ($db_res && ($arVote = $db_res->GetNext()))
			{
				if ($arVote["LAMP"] == "yellow")
					$arVote["LAMP"] = ($arVote["ID"] == CVote::GetActiveVoteId($arVote["CHANNEL_ID"]) ? "green" : "red");
				$return = "<div class=\"lamp-red\" title=\"".($arVote["ACTIVE"] != 'Y' ? GetMessage("VOTE_NOT_ACTIVE") : GetMessage("VOTE_ACTIVE_RED_LAMP"))."\"  style=\"display:inline-block;\"></div>";
				if ($arVote["LAMP"]=="green")
					$return = "<div class=\"lamp-green\" title=\"".GetMessage("VOTE_LAMP_ACTIVE")."\" style=\"display:inline-block;\"></div>";
				$return .= " [<a href='vote_edit.php?lang=".LANGUAGE_ID."&ID=".$arVote["ID"]."&return_url=".urlencode($return_url)."' title='".GetMessage("VOTE_EDIT_TITLE")."'>".$arVote["ID"]."</a>] ";
				$return .= $arVote["TITLE"].(!empty($arVote["DESCRIPTION"]) ? " <i>(".$arVote["DESCRIPTION"].")</i>" : "");
				if ($arVote["COUNTER"] > 0)
					$return .= GetMessage("VOTE_VOTES")." <a href=\"vote_user_votes.php?lang=".LANGUAGE_ID."&find_vote_id=".$arVote["ID"]."&find_valid=Y&set_filter=Y\">".$arVote["COUNTER"]."</a>";
			}

		}
		return $return;
	}

	public static function GetAdminListEditHTML($arUserField, $arHtmlControl)
	{
		return '<input type="text" '.
			'name="'.$arHtmlControl["NAME"].'" '.
			'size="'.$arUserField["SETTINGS"]["SIZE"].'" '.
			'value="'.$arHtmlControl["VALUE"].'" '.
			'>';
	}

	public static function OnSearchIndex($arUserField)
	{
		if(is_array($arUserField["VALUE"]))
			return implode("\r\n", $arUserField["VALUE"]);
		else
			return $arUserField["VALUE"];
	}
}
?>