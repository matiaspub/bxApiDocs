<?

/*
 * get clean content for CRM
 */
function CRMModeOutput($text)
{
	while(@ob_end_clean());
	echo $text;
	die();
}

/*
 * get user name
 */
function fGetUserName($USER_ID)
{
	global $lang;
	$user = GetMessage('NEWO_BUYER_NAME_NULL');

	if (intval($USER_ID) > 0)
	{
		$rsUser = CUser::GetByID($USER_ID);
		$arUser = $rsUser->Fetch();

		if (count($arUser) > 1)
		{
			$user = "<a href='javascript:void(0);' onClick=\"window.open('/bitrix/admin/user_search.php?lang=".$lang."&FN=form_order_buyers_form&FC=user_id', '', 'scrollbars=yes,resizable=yes,width=840,height=500,top='+Math.floor((screen.height - 840)/2-14)+',left='+Math.floor((screen.width - 760)/2-5));\">";
			$user .= "(".htmlspecialcharsbx($arUser["LOGIN"]).")";

			if ($arUser["NAME"] != "")
				$user .= " ".htmlspecialcharsbx($arUser["NAME"]);
			if ($arUser["LAST_NAME"] != "")
				$user .= " ".htmlspecialcharsbx($arUser["LAST_NAME"]);

			$user .= "<span class='pencil'>&nbsp;</span></a>";
		}
	}

	return $user;
}

/*
 * get count name, mail, phones in profiles
 */
function fGetCountProfileProps($PERSON_TYPE_ID)
{
	$arResult = array();
	$dbProperties = CSaleOrderProps::GetList(
		array(),
		array("PERSON_TYPE_ID" => $PERSON_TYPE_ID, "ACTIVE" => "Y"),
		array("IS_PHONE", "COUNT" => "ID"),
		false,
		array("IS_PHONE")
	);
	while ($arProperties = $dbProperties->Fetch())
	{
		if ($arProperties["IS_PHONE"] == "Y")
			$arResult["IS_PHONE"] = $arProperties["CNT"];
	}

	$dbProperties = CSaleOrderProps::GetList(
		array(),
		array("PERSON_TYPE_ID" => $PERSON_TYPE_ID, "ACTIVE" => "Y"),
		array("IS_PAYER", "COUNT" => "ID"),
		false,
		array("IS_PAYER")
	);
	while ($arProperties = $dbProperties->Fetch())
	{
		if ($arProperties["IS_PAYER"] == "Y")
			$arResult["IS_PAYER"] = $arProperties["CNT"];
	}

	$dbProperties = CSaleOrderProps::GetList(
		array(),
		array("PERSON_TYPE_ID" => $PERSON_TYPE_ID, "ACTIVE" => "Y"),
		array("IS_EMAIL", "COUNT" => "ID"),
		false,
		array("IS_EMAIL")
	);
	while ($arProperties = $dbProperties->Fetch())
	{
		if ($arProperties["IS_EMAIL"] == "Y")
			$arResult["IS_EMAIL"] = $arProperties["CNT"];
	}

	return $arResult;
}

/*
 * user property (parameters order)
 */
function fGetBuyerType($PERSON_TYPE_ID, $LID, $USER_ID = '', $ORDER_ID = 0, $formVarsSubmit = false)
{
	global $locationZipID, $locationID, $DELIVERY_LOCATION, $DELIVERY_LOCATION_ZIP;
	$resultHtml = "<script>locationZipID = 0;locationID = 0;</script><table width=\"100%\" id=\"order_type_props\" class=\"edit-table\">";

	//select person type
	$arPersonTypeList = array();
	$personTypeSelect = "<select name='buyer_type_id' id='buyer_type_id' OnChange='fBuyerChangeType(this);' >";
	$dbPersonType = CSalePersonType::GetList(array("SORT" => "ASC", "NAME" => "ASC"), array("ACTIVE" => "Y"));
	while ($arPersonType = $dbPersonType->GetNext())
	{
		if (!in_array($LID, $arPersonType["LIDS"]))
			continue;

		if (!isset($PERSON_TYPE_ID) OR $PERSON_TYPE_ID == "")
			$PERSON_TYPE_ID = $arPersonType["ID"];

		$class = "";
		if (IntVal($arPersonType["ID"]) == IntVal($PERSON_TYPE_ID))
			$class = " selected";

		$personTypeSelect .= "<option value=\"".$arPersonType["ID"]."\" ".$class.">".$arPersonType["NAME"]." [".$arPersonType["ID"]."]</option>";
	}
	$personTypeSelect .= "</select>";

	$userComment = "";
	$userDisplay = "none";
	if (IntVal($ORDER_ID) > 0)
	{
		$dbOrder = CSaleOrder::GetList(
			array(),
			array("ID" => $ORDER_ID, "ACTIVE" => "Y"),
			false,
			false,
			array()
		);
		$arOrder = $dbOrder->Fetch();
		$userComment = $arOrder["USER_DESCRIPTION"];
		$userDisplay = "table-row";
	}

	if ($formVarsSubmit && $_REQUEST["btnTypeBuyer"] == "btnBuyerNew")
		$userDisplay = "none";
	elseif ($formVarsSubmit && $_REQUEST["btnTypeBuyer"] == "btnBuyerExist")
		$userDisplay = "table-row";

	$resultHtml .= "<tr id=\"btnBuyerExistField\" style=\"display:".$userDisplay."\">
			<td class=\"adm-detail-content-cell-l\" width=\"40%\">".GetMessage("NEWO_BUYER").":</td>
			<td class=\"adm-detail-content-cell-r\" width=\"60%\"><div id=\"user_name\">".fGetUserName($USER_ID)."</div></td></tr>";

	$resultHtml .= "<tr class=\"adm-detail-required-field\">
		<td class=\"adm-detail-content-cell-l\" width=\"40%\">".GetMessage("SOE_PERSON_TYPE").":</td>
		<td class=\"adm-detail-content-cell-r\" width=\"60%\">".$personTypeSelect."</td>
	</tr>";

	$bShowTrProfile = "none";
	if ($formVarsSubmit && $_POST["btnTypeBuyer"] == "btnBuyerExist")
		$bShowTrProfile = "table-row";

	$resultHtml .= "<tr id=\"buyer_profile_display\" style=\"display:".$bShowTrProfile."\" class=\"adm-detail-required-field\">
		<td class=\"adm-detail-content-cell-l\">".GetMessage("NEWO_BUYER_PROFILE").":</td>
		<td class=\"adm-detail-content-cell-r\">
			<div id=\"buyer_profile_select\">";

			if ($formVarsSubmit && $_POST["btnTypeBuyer"] == "btnBuyerExist")
			{
				$resultHtml .= fUserProfile(IntVal($_POST["user_id"]), IntVal($_POST["buyer_type_id"]), $default = '');
			}

	$resultHtml .= "</div></td>
	</tr>";

	if ($ORDER_ID <= 0)
	{
		$arCountProps = fGetCountProfileProps($PERSON_TYPE_ID);
		$resultHtml .= "<tr id=\"btnBuyerNewField\">";
		if (count($arCountProps) < 3)
		{
			$resultHtml .= "<td colspan=2>
					<table width=\"100%\" class=\"edit-table\" >";
					if (IntVal($arCountProps["IS_EMAIL"]) <= 0)
						$resultHtml .= "<tr class=\"adm-detail-required-field\">
							<td class=\"adm-detail-content-cell-l\" width=\"40%\">".GetMessage("NEWO_BUYER_REG_MAIL")."</td>
							<td class=\"adm-detail-content-cell-r\"><input type=\"text\" name=\"NEW_BUYER_EMAIL\" size=\"30\" value=\"".htmlspecialcharsbx(trim($_REQUEST["NEW_BUYER_EMAIL"]))."\" tabindex=\"1\" /></td>
						</tr>";
					if (IntVal($arCountProps["IS_PAYER"]) <= 0)
						$resultHtml .= "<tr class=\"adm-detail-required-field\">
							<td class=\"adm-detail-content-cell-l\">".GetMessage("NEWO_BUYER_REG_LASTNAME")."</td>
							<td class=\"adm-detail-content-cell-r\"><input type=\"text\" name=\"NEW_BUYER_LAST_NAME\" size=\"30\" value=\"".htmlspecialcharsbx(trim($_REQUEST["NEW_BUYER_LAST_NAME"]))."\" tabindex=\"3\" /></td>
						</tr>
						<tr class=\"adm-detail-required-field\">
							<td class=\"adm-detail-content-cell-l\">".GetMessage("NEWO_BUYER_REG_NAME")."</td>
							<td class=\"adm-detail-content-cell-r\"><input type=\"text\" name=\"NEW_BUYER_NAME\" size=\"30\" value=\"".htmlspecialcharsbx(trim($_REQUEST["NEW_BUYER_NAME"]))."\" tabindex=\"2\" /></td>
						</tr>";
					$resultHtml .= "</table>
				</td>";
		}
		$resultHtml .= "</tr>";
	}

	$arPropValues = array();
	if ($formVarsSubmit)
	{
		$locationIndexForm = "";
		foreach ($_POST as $key => $value)
		{
			if (substr($key, 0, strlen("CITY_ORDER_PROP_")) == "CITY_ORDER_PROP_")
			{
				$arPropValues[IntVal(substr($key, strlen("CITY_ORDER_PROP_")))] = htmlspecialcharsbx($value);
				$locationIndexForm = IntVal(substr($key, strlen("CITY_ORDER_PROP_")));
			}
			if (substr($key, 0, strlen("ORDER_PROP_")) == "ORDER_PROP_")
			{
				if ($locationIndexForm != IntVal(substr($key, strlen("ORDER_PROP_"))))
					$arPropValues[IntVal(substr($key, strlen("ORDER_PROP_")))] = htmlspecialcharsbx($value);
			}
		}
		$userComment = $_POST["USER_DESCRIPTION"];
	}
	elseif ($ORDER_ID == "" AND $USER_ID != "")
	{
		//profile
		$userProfile = array();
		$userProfile = CSaleOrderUserProps::DoLoadProfiles($USER_ID, $PERSON_TYPE_ID);
		$arPropValues = $userProfile[$PERSON_TYPE_ID]["VALUES"];
	}
	elseif ($ORDER_ID != "")
	{
		$dbPropValuesList = CSaleOrderPropsValue::GetList(
			array(),
			array("ORDER_ID" => $ORDER_ID, "ACTIVE" => "Y"),
			false,
			false,
			array("ID", "ORDER_PROPS_ID", "NAME", "VALUE", "CODE")
		);
		while ($arPropValuesList = $dbPropValuesList->Fetch())
		{
			$arPropValues[IntVal($arPropValuesList["ORDER_PROPS_ID"])] = htmlspecialcharsbx($arPropValuesList["VALUE"]);
		}
	}

	//select field (town) for disable
	$arDisableFieldForLocation = array();
	$dbProperties = CSaleOrderProps::GetList(
		array(),
		array("PERSON_TYPE_ID" => $PERSON_TYPE_ID, "ACTIVE" => "Y", ">INPUT_FIELD_LOCATION" => 0),
		false,
		false,
		array("INPUT_FIELD_LOCATION")
	);
	while ($arProperties = $dbProperties->Fetch())
		$arDisableFieldForLocation[$arProperties["INPUT_FIELD_LOCATION"]] = $arProperties["INPUT_FIELD_LOCATION"];

	//show town if location is another
	$arEnableTownProps = array();
	$dbOrderProps = CSaleOrderPropsValue::GetOrderProps($ORDER_ID);
	while ($arOrderProps = $dbOrderProps->Fetch())
	{
		if ($arOrderProps["TYPE"] == "LOCATION" && $arOrderProps["ACTIVE"] == "Y" && $arOrderProps["IS_LOCATION"] == "Y" && in_array($arOrderProps["INPUT_FIELD_LOCATION"], $arDisableFieldForLocation))
		{
			$arLocation = CSaleLocation::GetByID($arPropValues[$arOrderProps["ORDER_PROPS_ID"]]);
			if (IntVal($arLocation["CITY_ID"]) <= 0)
				unset($arDisableFieldForLocation[$arOrderProps["INPUT_FIELD_LOCATION"]]);
		}
	}

	$dbProperties = CSaleOrderProps::GetList(
		array("GROUP_SORT" => "ASC", "PROPS_GROUP_ID" => "ASC", "SORT" => "ASC", "NAME" => "ASC"),
		array("PERSON_TYPE_ID" => $PERSON_TYPE_ID, "ACTIVE" => "Y"),
		false,
		false,
		array("*")
	);
	$propertyGroupID = -1;

	while ($arProperties = $dbProperties->Fetch())
	{
		if (IntVal($arProperties["PROPS_GROUP_ID"]) != $propertyGroupID)
		{
			$resultHtml .= "<tr><td colspan=\"2\" style=\"text-align:center;font-weight:bold;font-size:14px;color:rgb(75, 98, 103);\" >".htmlspecialcharsEx($arProperties["GROUP_NAME"])."\n</td>\n</tr>";
			$propertyGroupID = IntVal($arProperties["PROPS_GROUP_ID"]);
		}

		if (IntVal($arProperties["PROPS_GROUP_ID"]) != $propertyGroupID)
			$propertyGroupID = IntVal($arProperties["PROPS_GROUP_ID"]);

		$adit = "";
		$requiredField = "";
		if ($arProperties["REQUIED"] == "Y" || $arProperties["IS_PROFILE_NAME"] == "Y" || $arProperties["IS_LOCATION"] == "Y" || $arProperties["IS_LOCATION4TAX"] == "Y" || $arProperties["IS_PAYER"] == "Y" || $arProperties["IS_ZIP"] == "Y")
		{
			$adit = " class=\"adm-detail-required-field\"";
			$requiredField = " class=\"adm-detail-content-cell-l\"";
		}
		//delete town from location
		if (in_array($arProperties["ID"], $arDisableFieldForLocation))
			$resultHtml .= "<tr style=\"display:none;\" id=\"town_location_".$arProperties["ID"]."\"".$adit.">\n";
		else
			$resultHtml .= "<tr id=\"town_location_".$arProperties["ID"]."\"".$adit.">\n";

		if(($arProperties["TYPE"] == "MULTISELECT" || $arProperties["TYPE"] == "TEXTAREA") || ($ORDER_ID <= 0 && $arProperties["IS_PROFILE_NAME"] == "Y") )
			$resultHtml .= "<td valign=\"top\" class=\"adm-detail-content-cell-l\" width=\"40%\">\n";
		else
			$resultHtml .= "<td align=\"right\" width=\"40%\" ".$requiredField.">\n";

		$resultHtml .= htmlspecialcharsEx($arProperties["NAME"]).":</td>";

		$curVal = $arPropValues[IntVal($arProperties["ID"])];

		if($arProperties["IS_EMAIL"] == "Y" || $arProperties["IS_PAYER"] == "Y")
		{
			if(strlen($arProperties["DEFAULT_VALUE"]) <= 0 && IntVal($USER_ID) > 0)
			{
				$rsUser = CUser::GetByID($USER_ID);
				if ($arUser = $rsUser->Fetch())
				{
					if($arProperties["IS_EMAIL"] == "Y")
						$arProperties["DEFAULT_VALUE"] = $arUser["EMAIL"];
					else
					{
						if (strlen($arUser["LAST_NAME"]) > 0)
							$arProperties["DEFAULT_VALUE"] .= $arUser["LAST_NAME"];
						if (strlen($arUser["NAME"]) > 0)
							$arProperties["DEFAULT_VALUE"] .= " ".$arUser["NAME"];
						if (strlen($arUser["SECOND_NAME"]) > 0 AND strlen($arUser["NAME"]) > 0)
							$arProperties["DEFAULT_VALUE"] .= " ".$arUser["SECOND_NAME"];
					}
				}
			}
		}

		$resultHtml .= "<td class=\"adm-detail-content-cell-r\" width=\"60%\">";

		if ($arProperties["TYPE"] == "CHECKBOX")
		{
			$resultHtml .= '<input type="checkbox" class="inputcheckbox" ';
			$resultHtml .= 'name="ORDER_PROP_'.$arProperties["ID"].'" value="Y"';
			$resultHtml .= 'id="ORDER_PROP_'.$arProperties["ID"].'" ';
			if ($curVal=="Y" || !isset($curVal) && $arProperties["DEFAULT_VALUE"]=="Y")
				$resultHtml .= " checked";
			$resultHtml .= '>';
		}
		elseif ($arProperties["TYPE"] == "TEXT")
		{
			$change = "";
			if ($arProperties["IS_ZIP"] == "Y")
			{
				$DELIVERY_LOCATION_ZIP = $curVal;
				$resultHtml .= '<script> locationZipID = \''.$arProperties["ID"].'\';</script>';
				$locationZipID = ((isset($curVal)) ? htmlspecialcharsEx($curVal) : htmlspecialcharsex($arProperties["DEFAULT_VALUE"]));
			}

			if ($arProperties["IS_PAYER"] == "Y" && IntVal($USER_ID) <= 0)
			{
				$resultHtml .= '<div id="BREAK_NAME"';
				if ($ORDER_ID > 0 || ($formVarsSubmit && $_REQUEST["btnTypeBuyer"] == "btnBuyerExist"))
					$resultHtml .= ' style="display:none"';
				$resultHtml .= '>';

				$BREAK_LAST_NAME_TMP = GetMessage('NEWO_BREAK_LAST_NAME');
				if (isset($_REQUEST["BREAK_LAST_NAME"]) && strlen($_REQUEST["BREAK_LAST_NAME"]) > 0)
					$BREAK_LAST_NAME_TMP = htmlspecialcharsbx(trim($_REQUEST["BREAK_LAST_NAME"]));

				$NEWO_BREAK_NAME_TMP = GetMessage('NEWO_BREAK_NAME');
				if (isset($_REQUEST["BREAK_NAME"]) && strlen($_REQUEST["BREAK_NAME"]) > 0)
					$NEWO_BREAK_NAME_TMP = htmlspecialcharsbx(trim($_REQUEST["BREAK_NAME"]));

				$BREAK_SECOND_NAME_TMP = GetMessage('NEWO_BREAK_SECOND_NAME');
				if (isset($_REQUEST["BREAK_SECOND_NAME"]) && strlen($_REQUEST["BREAK_SECOND_NAME"]) > 0)
					$BREAK_SECOND_NAME_TMP = htmlspecialcharsbx(trim($_REQUEST["BREAK_SECOND_NAME"]));

				$resultHtml .= "<div class=\"fio newo_break_active\"><input onblur=\"if (this.value==''){this.value='".GetMessage('NEWO_BREAK_LAST_NAME')."';BX.addClass(this.parentNode,'newo_break_active');}\" onfocus=\"if (this.value=='".GetMessage('NEWO_BREAK_LAST_NAME')."') {this.value='';BX.removeClass(this.parentNode,'newo_break_active');}\" type=\"text\" name=\"BREAK_LAST_NAME\" id=\"BREAK_LAST_NAME\" size=\"30\" value=\"".$BREAK_LAST_NAME_TMP."\" /></div>";
				$resultHtml .= "<div class=\"fio newo_break_active\"><input onblur=\"if (this.value==''){this.value='".GetMessage('NEWO_BREAK_NAME')."';BX.addClass(this.parentNode,'newo_break_active');}\" onfocus=\"if (this.value=='".GetMessage('NEWO_BREAK_NAME')."') {this.value='';BX.removeClass(this.parentNode,'newo_break_active');}\" type=\"text\" name=\"BREAK_NAME\" id=\"BREAK_NAME_BUYER\" size=\"30\" value=\"".$NEWO_BREAK_NAME_TMP."\" /></div>";
				$resultHtml .= "<div class=\"fio newo_break_active\"><input onblur=\"if (this.value==''){this.value='".GetMessage('NEWO_BREAK_SECOND_NAME')."';BX.addClass(this.parentNode,'newo_break_active');}\" onfocus=\"if (this.value=='".GetMessage('NEWO_BREAK_SECOND_NAME')."') {this.value='';BX.removeClass(this.parentNode,'newo_break_active');}\" type=\"text\" name=\"BREAK_SECOND_NAME\" id=\"BREAK_SECOND_NAME\" size=\"30\" value=\"".$BREAK_SECOND_NAME_TMP."\" /></div>";
				$resultHtml .= '</div>';

				$resultHtml .= '<div id="NO_BREAK_NAME"';
				if ($ORDER_ID <= 0)
					$tmpNone = ' style="display:none"';
				if ($formVarsSubmit && $_REQUEST["btnTypeBuyer"] == "btnBuyerExist")
					$tmpNone = ' style="display:block"';
				$resultHtml .= $tmpNone.'>';
			}

			$resultHtml .= '<input type="text" maxlength="250" ';
			$resultHtml .= 'size="30" ';
			$resultHtml .= 'value="'.((isset($curVal)) ? $curVal : $arProperties["DEFAULT_VALUE"]).'" ';
			$resultHtml .= 'name="ORDER_PROP_'.$arProperties["ID"].'" ';
			$resultHtml .= 'id="ORDER_PROP_'.$arProperties["ID"].'" '.$change.'>';

			if ($arProperties["IS_PAYER"] == "Y" && IntVal($USER_ID) <= 0)
				$resultHtml .= '</div>';
		}
		elseif ($arProperties["TYPE"] == "SELECT")
		{
			$resultHtml .= '<select name="ORDER_PROP_'.$arProperties["ID"].'" ';
			$resultHtml .= 'id="ORDER_PROP_'.$arProperties["ID"].'" ';
			$resultHtml .= 'size="5" ';
			$resultHtml .= 'class="typeselect">';
			$dbVariants = CSaleOrderPropsVariant::GetList(
				array("SORT" => "ASC"),
				array("ORDER_PROPS_ID" => $arProperties["ID"]),
				false,
				false,
				array("*")
			);
			while ($arVariants = $dbVariants->Fetch())
			{
				$resultHtml .= '<option value="'.htmlspecialcharsex($arVariants["VALUE"]).'"';
				if ($arVariants["VALUE"] == $curVal || !isset($curVal) && $arVariants["VALUE"] == $arProperties["DEFAULT_VALUE"])
					$resultHtml .= " selected";
				$resultHtml .= '>'.htmlspecialcharsEx($arVariants["NAME"]).'</option>';
			}
			$resultHtml .= '</select>';
		}
		elseif ($arProperties["TYPE"] == "MULTISELECT")
		{
			$resultHtml .= '<select multiple name="ORDER_PROP_'.$arProperties["ID"].'[]" ';
			$resultHtml .= 'id="ORDER_PROP_'.$arProperties["ID"].'" ';
			$resultHtml .= 'size="5" ';
			$resultHtml .= 'class="typeselect" type="multyselect">';

			if (!is_array($curVal))
			{
				if (strlen($curVal) > 0 OR $ORDER_ID != "")
					$curVal = explode(",", $curVal);
				else
					$curVal = explode(",", $arProperties["DEFAULT_VALUE"]);

				$arCurVal = array();
				$countCurVal = count($curVal);
				for ($i = 0; $i < $countCurVal; $i++)
					$arCurVal[$i] = Trim($curVal[$i]);
			}
			else
				$arCurVal = $curVal;

			$dbVariants = CSaleOrderPropsVariant::GetList(
				array("SORT" => "ASC"),
				array("ORDER_PROPS_ID" => $arProperties["ID"]),
				false,
				false,
				array("*")
			);
			while ($arVariants = $dbVariants->Fetch())
			{
				$resultHtml .= '<option value="'.htmlspecialcharsex($arVariants["VALUE"]).'"';
				if (in_array($arVariants["VALUE"], $arCurVal))
					$resultHtml .= " selected";
				$resultHtml .= '>'.htmlspecialcharsEx($arVariants["NAME"]).'</option>';
			}
			$resultHtml .= '</select>';
		}
		elseif ($arProperties["TYPE"] == "TEXTAREA")
		{
			$resultHtml .= '<textarea ';
			$resultHtml .= 'rows="4" ';
			$resultHtml .= 'cols="40" ';
			$resultHtml .= 'name="ORDER_PROP_'.$arProperties["ID"].'" ';
			$resultHtml .= 'id="ORDER_PROP_'.$arProperties["ID"].'" type="textarea">';
			$resultHtml .= ((isset($curVal)) ? $curVal : $arProperties["DEFAULT_VALUE"]);
			$resultHtml .= '</textarea>';
		}
		elseif ($arProperties["TYPE"] == "LOCATION")
		{
			$countryID = "";
			$cityID = "";
			$cityList = "";
			$DELIVERY_LOCATION = $arPropValues[IntVal($arProperties["ID"])];
			$locationID = $curVal;
			$tmpLocation = '';

			ob_start();
			$tmpLocation = $GLOBALS["APPLICATION"]->IncludeComponent(
						'bitrix:sale.ajax.locations',
						'',
						array(
							"SITE_ID" => $LID,
							"AJAX_CALL" => "N",
							"COUNTRY_INPUT_NAME" => "ORDER_PROP_".$arProperties["ID"],
							"REGION_INPUT_NAME" => "REGION_ORDER_PROP_".$arProperties["ID"],
							"CITY_INPUT_NAME" => "CITY_ORDER_PROP_".$arProperties["ID"],
							"CITY_OUT_LOCATION" => "Y",
							"ALLOW_EMPTY_CITY" => "Y",
							"LOCATION_VALUE" => $curVal,
							"COUNTRY" => "",
							"ONCITYCHANGE" => "fRecalProduct('', '', 'N');",
							"PUBLIC" => "N",
						),
						null,
						array('HIDE_ICONS' => 'Y')
			);
			$tmpLocation = ob_get_contents();
			ob_end_clean();

			$resultHtml .= '<script>var locationID = \''.$arProperties["ID"].'\';</script>';
			$resultHtml .= $tmpLocation;
		}
		elseif ($arProperties["TYPE"] == "RADIO")
		{
			$dbVariants = CSaleOrderPropsVariant::GetList(
				array("SORT" => "ASC"),
				array("ORDER_PROPS_ID" => $arProperties["ID"]),
				false,
				false,
				array("*")
			);
			$resultHtml .= '<div id="ORDER_PROP_'.$arProperties["ID"].'">';// type="radio"
			while ($arVariants = $dbVariants->Fetch())
			{
				$resultHtml .= '<input type="radio" class="inputradio" ';
				$resultHtml .= 'name="ORDER_PROP_'.$arProperties["ID"].'" ';
				$resultHtml .= 'value="'.htmlspecialcharsex($arVariants["VALUE"]).'"';
				if ($arVariants["VALUE"] == $curVal || !isset($curVal) && $arVariants["VALUE"] == $arProperties["DEFAULT_VALUE"])
					$resultHtml .= " checked";
				$resultHtml .= '>'.htmlspecialcharsEx($arVariants["NAME"]).'<br>';
			}
			$resultHtml .= '</div>';
		}

		if (strlen($arProperties["DESCRIPTION"]) > 0)
		{
			$resultHtml .= "<br><small>".htmlspecialcharsEx($arProperties["DESCRIPTION"])."</small>";
		}
		$resultHtml .= "\n</td>\n</tr>";

	}//end while

	$resultHtml .= "<tr>\n<td valign=\"top\" class=\"adm-detail-content-cell-l\">".GetMessage("SOE_BUYER_COMMENT").":
			</td>
			<td class=\"adm-detail-content-cell-r\">
				<textarea name=\"USER_DESCRIPTION\" rows=\"4\" cols=\"40\">".htmlspecialcharsbx($userComment)."</textarea>
			</td>
		</tr>";

	$resultHtml .= "</table>";
	return $resultHtml;
}

/*
 * paysystem
 */
function fBuyerDelivery($PERSON_TYPE_ID, $PAY_SYSTEM_ID)
{
	$resultHtml = "<table width=\"100%\">";
	$resultHtml .= "<tr class=\"adm-detail-required-field\">\n<td class=\"adm-detail-content-cell-l\" width=\"40%\">".GetMessage("SOE_PAY_SYSTEM").":</td><td class=\"adm-detail-content-cell-r\" width=\"60%\">";

	$arPaySystem = CSalePaySystem::DoLoadPaySystems($PERSON_TYPE_ID);

	$resultHtml .= "<select name=\"PAY_SYSTEM_ID\" id=\"PAY_SYSTEM_ID\">\n";
	$resultHtml .= "<option value=\"\">(".GetMessage("SOE_SELECT").")</option>";
	foreach ($arPaySystem as $key => $val)
	{
		$resultHtml .= "<option value=\"".$key."\"";
		if ($key == IntVal($PAY_SYSTEM_ID))
			$resultHtml .= " selected";
		$resultHtml .= ">".$val["NAME"]." [".$key."]</option>";
	}
	$resultHtml .= "</select>";
	$resultHtml .= "</td>\n</tr>";
	$resultHtml .= "</table>";

	return $resultHtml;
}

/*
 * user profile
 */
function fUserProfile($USER_ID, $BUYER_TYPE = '', $default = '')
{
	$userProfileSelect = "<select name=\"user_profile\" id=\"user_profile\" onChange=\"fChangeProfile(this);\">";
	$userProfileSelect .= "<option value=\"0\">".GetMessage("NEWO_BUYER_PROFILE_NEW")."</option>";
	$userProfile = CSaleOrderUserProps::DoLoadProfiles($USER_ID, $BUYER_TYPE);
	$i = "";
	foreach($userProfile as $key => $val)
	{
		if ($default == "" AND $i == "")
		{
			$userProfileSelect .= "<option selected value=\"".$key."\">".$val["NAME"]."</option>";
			$i = $key;
		}
		elseif ($default == $key)
			$userProfileSelect .= "<option selected value=\"".$key."\">".$val["NAME"]."</option>";
		else
			$userProfileSelect .= "<option value=\"".$key."\">".$val["NAME"]."</option>";
	}
	$userProfileSelect .= "</select>";

	return $userProfileSelect;
}

/*
 * user balance
 */
function fGetPayFromAccount($USER_ID, $CURRENCY)
{
	$arResult = array("PAY_MESSAGE" => GetMessage("NEWO_PAY_FROM_ACCOUNT_NO"));
	$dbUserAccount = CSaleUserAccount::GetList(
	array(),
	array(
		"USER_ID" => $USER_ID,
		"CURRENCY" => $CURRENCY,
		)
	);
	if ($arUserAccount = $dbUserAccount->GetNext())
	{
		if (DoubleVal($arUserAccount["CURRENT_BUDGET"]) > 0)
		{
			$arResult["PAY_BUDGET"] = SaleFormatCurrency($arUserAccount["CURRENT_BUDGET"], $CURRENCY);
			$arResult["PAY_MESSAGE"] = str_replace("#MONEY#", $arResult["PAY_BUDGET"], GetMessage("NEWO_PAY_FROM_ACCOUNT_YES"));
			$arResult["CURRENT_BUDGET"] = $arUserAccount["CURRENT_BUDGET"];
		}
	}

	return $arResult;
}

/*
 * delivery
 */
function fGetDelivery($location, $locationZip, $weight, $price, $currency, $siteId, $defaultDelivery)
{
	$arResult = array();
	$delivery = "<select name=\"DELIVERY_ID\" id=\"DELIVERY_ID\" OnChange=\"fChangeDelivery();\">";
	$delivery .= "<option value=\"\">".GetMessage('NEWO_DELIVERY_NO')."</option>";

	$arDelivery = CSaleDelivery::DoLoadDelivery($location, $locationZip, $weight, $price, $currency, $siteId);
	$price = 0;
	$description = "";
	$error = "";
	if (count($arDelivery) > 0)
	{
		foreach($arDelivery as $val)
		{
			if (isset($val["PROFILES"]))
			{
				foreach($val["PROFILES"] as $k => $v)
				{
					$currency = $v["CURRENCY"];
					$selected = "";
					if ($v["ID"] == $defaultDelivery)
					{
						$selected = " selected=\"selected\"";

						if (floatval($v["DELIVERY_PRICE"]) <= 0)
						{
							$error = "<div class='error'>".GetMessage('NEWO_DELIVERY_ERR')."</div>";
							$v["DELIVERY_PRICE"] = 0;
							$val["DESCRIPTION"] = "";
						}
						$price = $v["DELIVERY_PRICE"];
						$description = $val["DESCRIPTION"];
					}

					$delivery .= "<option".$selected." value=\"".$v["ID"]."\">".$val["TITLE"]." (".$v["TITLE"].") [".$v["ID"]."]</option>";
				}
			}
			else
			{
				$currency = $val["CURRENCY"];
				$selected = "";
				if ($val["ID"] == $defaultDelivery)
				{
					$selected = " selected=\"selected\"";
					$price = $val["PRICE"];
					$description = $val["DESCRIPTION"];
				}

				$delivery .= "<option".$selected." value=\"".$val["ID"]."\">".$val["NAME"]." [".$val["ID"]."]</option>";
			}
		}
	}
	$delivery .= "</select>";

	$arResult["DELIVERY"] = $delivery;
	$arResult["DELIVERY_DEFAULT"] = $defaultDelivery;
	$arResult["DELIVERY_DEFAULT_PRICE"] = $price;
	$arResult["DELIVERY_DEFAULT_DESCRIPTION"] = $description;
	$arResult["DELIVERY_DEFAULT_ERR"] = $error;
	$arResult["CURRENCY"] = $currency;

	return $arResult;
}

/*
 * cupons
 */
function fGetCupon($CUPON)
{
	$arCupon = array();
	if (isset($CUPON) AND $CUPON != "")
	{
		$cupons = explode(",", $CUPON);
		foreach($cupons as $val)
		{
			if (strlen(trim($val)) > 0)
				$arCupon[] = trim($val);
		}
	}

	return $arCupon;
}

/*
 * get ID, ZIP location
 */
function fGetLocationID($PERSON_TYPE_ID)
{
	$dbProperties = CSaleOrderProps::GetList(
		array("SORT" => "ASC"),
		array("PERSON_TYPE_ID" => $PERSON_TYPE_ID),
		false,
		false,
		array("TYPE", "IS_ZIP", "ID", "SORT")
	);

	$arResult = array();
	while ($arProperties = $dbProperties->Fetch())
	{
		if ($arProperties["TYPE"] == "TEXT")
		{
			if ($arProperties["IS_ZIP"] == "Y")
			{
				$arResult["LOCATION_ZIP_ID"] = $arProperties["ID"];
			}
		}
		elseif ($arProperties["TYPE"] == "LOCATION")
		{
			$arResult["LOCATION_ID"] = $arProperties["ID"];
		}
	}//end while

	return $arResult;
}

/*
 * array product busket
 */
function fGetUserShoppingCart($arProduct, $LID, $recalcOrder)
{
	$arOrderProductPrice = array();
	$i = 0;

	foreach($arProduct as $key => $val)
	{
		$arSortNum[] = $val['PRICE_DEFAULT'];
		$arProduct[$key]["PRODUCT_ID"] = IntVal($val["PRODUCT_ID"]);
		$arProduct[$key]["TABLE_ROW_ID"] = $key;
	}
	if (count($arProduct) > 0 && count($arSortNum) > 0)
		array_multisort($arSortNum, SORT_DESC, $arProduct);

	foreach($arProduct as $key => $val)
	{
		$val["QUANTITY"] = abs(str_replace(",", ".", $val["QUANTITY"]));
		$val["QUANTITY_DEFAULT"] = $val["QUANTITY"];
		$val["PRICE"] = str_replace(",", ".", $val["PRICE"]);

		//Y is used when custom price was set in the admin form
		if ($val["CALLBACK_FUNC"] == "Y")
		{
			$val["CALLBACK_FUNC"] = false;
			$val["CUSTOM_PRICE"] = "Y";

			if (isset($val["BUSKET_ID"]) || intval($val["BUSKET_ID"]) > 0)
			{
				CSaleBasket::Update($val["BUSKET_ID"], array("CUSTOM_PRICE" => "Y"));
			}

			//$val["DISCOUNT_PRICE"] = $val["PRICE_DEFAULT"] - $val["PRICE"];
		}

		$arOrderProductPrice[$i] = $val;
		$arOrderProductPrice[$i]["TABLE_ROW_ID"] = $val["TABLE_ROW_ID"];
		$arOrderProductPrice[$i]["PRODUCT_ID"] = IntVal($val["PRODUCT_ID"]);
		$arOrderProductPrice[$i]["NAME"] = htmlspecialcharsback($val["NAME"]);
		$arOrderProductPrice[$i]["LID"] = $LID;
		$arOrderProductPrice[$i]["CAN_BUY"] = "Y";

		if (!isset($val["BUSKET_ID"]) || $val["BUSKET_ID"] == "")
		{
			/*if ($val["CALLBACK_FUNC"] == "Y")
			{
				$arOrderProductPrice[$i]["CALLBACK_FUNC"] = '';
				$arOrderProductPrice[$i]["DISCOUNT_PRICE"] = 0;
			}*/
		}
		else
		{
			$arOrderProductPrice[$i]["ID"] = IntVal($val["BUSKET_ID"]);

			if ($recalcOrder != "Y" && $arOrderProductPrice[$i]["CALLBACK_FUNC"] != false)
				unset($arOrderProductPrice[$i]["CALLBACK_FUNC"]);

			$arNewProps = array();
			if (is_array($val["PROPS"]))
			{
				foreach($val["PROPS"] as $k => $v)
				{
					if ($v["NAME"] != "" AND $v["VALUE"] != "")
						$arNewProps[$k] = $v;
				}
			}
			else
				$arNewProps = array("NAME" => "", "VALUE" => "", "CODE" => "", "SORT" => "");

			$arOrderProductPrice[$i]["PROPS"] = $arNewProps;
		}
		$i++;
	}//endforeach $arProduct

	return $arOrderProductPrice;
}

/*
 * get template recomendet & busket product
 */
function fGetFormatedProduct($USER_ID, $LID, $arData, $currency, $type = '')
{
	global $crmMode;
	$result = "";

	if (!is_array($arData["ITEMS"]) || count($arData["ITEMS"]) <= 0)
		return $result;

	$result = "<table width=\"100%\">";
	if (CModule::IncludeModule('catalog') && CModule::IncludeModule('iblock'))
	{
		$arProductId = array();
		$arDataTab = array();

		$arSkuParentChildren = array();
		$arSkuParentId = array();
		$arSkuParent = array();

		foreach ($arData["ITEMS"] as $items)
		{
			if (!empty($items["CURRENCY"]) && $items["CURRENCY"] != $currency)
			{
				if (floatval($items["PRICE"]) > 0)
					$items["PRICE"] = CCurrencyRates::ConvertCurrency($items["PRICE"], $items["CURRENCY"], $currency);

				if (floatval($items["DISCOUNT_PRICE"]) > 0)
					$items["DISCOUNT_PRICE"] = CCurrencyRates::ConvertCurrency($items["DISCOUNT_PRICE"], $items["CURRENCY"], $currency);

				$items["CURRENCY"] = $currency;
			}

			if ($items["MODULE"] == "catalog")
			{
				$arProductId[$items["PRODUCT_ID"]] = $items["PRODUCT_ID"];
				$arDataTab[$items["PRODUCT_ID"]] = $items;

				$arParent = CCatalogSku::GetProductInfo($items["PRODUCT_ID"]);
				if ($arParent)
				{
					$arSkuParentChildren[$items["PRODUCT_ID"]] = $arParent["ID"];
					$arSkuParentId[$arParent["ID"]] = $arParent["ID"];
				}
			}
		}

		if(!empty($arSkuParentId))
		{
			$res = CIBlockElement::GetList(array(), array("ID" => $arSkuParentId), false, false, array("ID", "IBLOCK_ID", "IBLOCK_SECTION_ID", "PREVIEW_PICTURE", "DETAIL_PICTURE", "NAME", "DETAIL_PAGE_URL"));
			while ($arItems = $res->GetNext())
				$arSkuParent[$arItems["ID"]] = $arItems;
		}

		if(!empty($arProductId))
		{
			$dbProduct = CIBlockElement::GetList(array(), array("ID" => $arProductId), false, false, array('ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID', 'DETAIL_PICTURE', 'PREVIEW_PICTURE', 'IBLOCK_TYPE_ID'));
			while($arProduct = $dbProduct->Fetch())
			{
				$imgCode = 0;
				$arImgProduct = false;
				$arFile = false;
				$imgUrl = '';
				$imgProduct = '';
				$arDataTab[$arProduct['ID']]['IBLOCK_ID'] = $arProduct['IBLOCK_ID'];
				$arDataTab[$arProduct['ID']]['IBLOCK_SECTION_ID'] = $arProduct['IBLOCK_SECTION_ID'];
				$arDataTab[$arProduct['ID']]['DETAIL_PICTURE'] = $arProduct['DETAIL_PICTURE'];
				$arDataTab[$arProduct['ID']]['PREVIEW_PICTURE'] = $arProduct['PREVIEW_PICTURE'];
				$arDataTab[$arProduct['ID']]['IBLOCK_TYPE_ID'] = $arProduct['IBLOCK_TYPE_ID'];
				$items = $arDataTab[$arProduct['ID']];

				if ($items["PREVIEW_PICTURE"] == "" && $items["DETAIL_PICTURE"] == "" && is_set($arSkuParentChildren[$items["PRODUCT_ID"]]))
				{
					$idTmp = $arSkuParentChildren[$items["PRODUCT_ID"]];
					$items["DETAIL_PICTURE"] = $arSkuParent[$idTmp]["DETAIL_PICTURE"];
					$items["PREVIEW_PICTURE"] = $arSkuParent[$idTmp]["PREVIEW_PICTURE"];
				}

				if ($items["DETAIL_PICTURE"] > 0)
					$imgCode = $items["DETAIL_PICTURE"];
				elseif ($items["PREVIEW_PICTURE"] > 0)
					$imgCode = $items["PREVIEW_PICTURE"];

				$arSkuProperty = CSaleProduct::GetProductSkuProps($items["PRODUCT_ID"]);

				$items["NAME"] = htmlspecialcharsex($items["NAME"]);
				$items["EDIT_PAGE_URL"] = htmlspecialcharsex($items["EDIT_PAGE_URL"]);
				$items["CURRENCY"] = htmlspecialcharsex($items["CURRENCY"]);

				if ($imgCode > 0)
				{
					$arFile = CFile::GetFileArray($imgCode);
					$arImgProduct = CFile::ResizeImageGet($arFile, array('width'=>80, 'height'=>80), BX_RESIZE_IMAGE_PROPORTIONAL, false, false);
				}

				if (is_array($arImgProduct))
				{
					$imgUrl = $arImgProduct["src"];
					$imgProduct = "<a href=\"".$items["EDIT_PAGE_URL"]."\" target=\"_blank\"><img src=\"".$imgUrl."\" alt=\"\" title=\"".$items["NAME"]."\" ></a>";
				}
				else
					$imgProduct = "<div class='no_foto'>".GetMessage('NO_FOTO')."</div>";

				$arCurFormat = CCurrencyLang::GetCurrencyFormat($items["CURRENCY"]);
				$priceValutaFormat = str_replace("#", '', $arCurFormat["FORMAT_STRING"]);

				$currentTotalPrice = ($items["PRICE"] + $items["DISCOUNT_PRICE"]);

				$discountPercent = 0;
				if ($items["DISCOUNT_PRICE"] > 0)
					$discountPercent = IntVal(($items["DISCOUNT_PRICE"] * 100) / $currentTotalPrice);

				$ar_res = CCatalogProduct::GetByID($items["PRODUCT_ID"]);
				$balance = FloatVal($ar_res["QUANTITY"]);

				$arParams = array();
				$arParams["id"] = $items["PRODUCT_ID"];
				$arParams["name"] = $items["NAME"];
				$arParams["url"] = $items["DETAIL_PAGE_URL"];
				$arParams["urlEdit"] = $items["EDIT_PAGE_URL"];
				$arParams["urlImg"] = $imgUrl;
				$arParams["price"] = FloatVal($items["PRICE"]);
				$arParams["priceBase"] = FloatVal($currentTotalPrice);
				$arParams["priceBaseFormat"] = CurrencyFormatNumber(FloatVal($currentTotalPrice), $items["CURRENCY"]);
				$arParams["priceFormated"] = CurrencyFormatNumber(FloatVal($items["PRICE"]), $items["CURRENCY"]);
				$arParams["valutaFormat"] = $priceValutaFormat;
				$arParams["priceDiscount"] = FloatVal($items["DISCOUNT_PRICE"]);
				$arParams["priceTotalFormated"] = SaleFormatCurrency($currentTotalPrice, $items["CURRENCY"]);
				$arParams["discountPercent"] = $discountPercent;
				$arParams["summaFormated"] = CurrencyFormatNumber($items["PRICE"], $items["CURRENCY"]);
				$arParams["quantity"] = 1;
				$arParams["module"] = $items["MODULE"];
				$arParams["currency"] = $items["CURRENCY"];
				$arParams["weight"] = 0;
				$arParams["vatRate"] = 0;
				$arParams["priceType"] = "";
				$arParams["balance"] = $balance;
				$arParams['skuProps'] = CUtil::PhpToJSObject($arSkuProperty);
				$arParams["catalogXmlID"] = "";
				$arParams["productXmlID"] = "";
				$arParams["callback"] = "";
				$arParams["orderCallback"] = "";
				$arParams["cancelCallback"] = "";
				$arParams["payCallback"] = "";
				$arParams["productProviderClass"] = "CCatalogProductProvider";

				$result .= "<tr id='more_".$type."_".$items["ID"]."'>
								<td class=\"tab_img\" >".$imgProduct."</td>
								<td class=\"tab_text\">
									<div class=\"order_name\"><a href=\"".$items["EDIT_PAGE_URL"]."\" target=\"_blank\" title=\"".$items["NAME"]."\">".$items["NAME"]."</a></div>
									<div class=\"order_price\">
										".GetMessage('NEWO_SUBTAB_PRICE').": <b>".SaleFormatCurrency($items["PRICE"], $currency)."</b>
									</div>";

				$arResult = CSaleProduct::GetProductSku($USER_ID, $LID, $items["PRODUCT_ID"], $items["NAME"], $currency, $arProduct);

				if (count($arResult["SKU_ELEMENTS"]) > 0)
				{
					foreach ($arResult["SKU_ELEMENTS"] as $key => $val)
					{
						$arTmp = array();
						foreach ($val as $k => $v)
						{
							if (is_numeric($k))
							{
								$arTmp[$arResult["SKU_PROPERTIES"][$k]["NAME"]] = $v;
							}
						}
						$arResult["SKU_ELEMENTS"][$key]["SKU_PROPS"] = CUtil::PhpToJSObject($arTmp);
					}
				}

				$arResult["POPUP_MESSAGE"] = array(
					"PRODUCT_ADD" => GetMEssage('NEWO_POPUP_TO_BUSKET'),
					"PRODUCT_ORDER" => GetMEssage('NEWO_POPUP_TO_ORDER'),
					"PRODUCT_NOT_ADD" => GetMEssage('NEWO_POPUP_DONT_CAN_BUY'),
					"PRODUCT_PRICE_FROM" => GetMessage('NEWO_POPUP_FROM')
				);

				if (count($arResult["SKU_ELEMENTS"]) <= 0)
					$result .= "<a href=\"javascript:void(0);\" class=\"get_new_order\" onClick=\"fAddToBusketMoreProduct('".$type."', ".CUtil::PhpToJSObject($arParams).");return false;\"><span></span>".GetMessage('NEWO_SUBTAB_ADD_BUSKET')."</a><br>";
				else
					$result .= "<a href=\"javascript:void(0);\" class=\"get_new_order\" onClick=\"fAddToBusketMoreProductSku(".CUtil::PhpToJsObject($arResult['SKU_ELEMENTS']).", ".CUtil::PhpToJsObject($arResult['SKU_PROPERTIES']).", 'busket', ".CUtil::PhpToJsObject($arResult["POPUP_MESSAGE"]).");\"><span></span>".GetMessage('NEWO_SUBTAB_ADD_BUSKET')."</a><br>";

				if (!$crmMode)
				{
					if (count($arResult["SKU_ELEMENTS"]) > 0)
					{
						$result .= "<a href=\"javascript:void(0);\" class=\"get_new_order\" onClick=\"fAddToBusketMoreProductSku(".CUtil::PhpToJsObject($arResult['SKU_ELEMENTS']).", ".CUtil::PhpToJsObject($arResult['SKU_PROPERTIES']).", 'neworder', ".CUtil::PhpToJsObject($arResult["POPUP_MESSAGE"]).");\"><span></span>".GetMessage('NEWO_SUBTAB_ADD_ORDER')."</a>";
					}
					else
					{
						$cntProd = (floatval($items["QUANTITY"]) > 0) ? floatval($items["QUANTITY"]) : 1;
						$url = "/bitrix/admin/sale_order_new.php?lang=".LANGUAGE_ID."&user_id=".$USER_ID."&LID=".$LID."&product[".$items["PRODUCT_ID"]."]=".$cntProd;
						$result .= "<a href=\"".$url."\" target=\"_blank\" class=\"get_new_order\"><span></span>".GetMessage('NEWO_SUBTAB_ADD_ORDER')."</a>";
					}
				}

				$result .= "</td></tr>";
			}//end foreach
		}
	}//end if

	if ($arData["CNT"] > 2 && $arData["CNT"] != count($arData["ITEMS"]))
	{
		$result .= "<tr><td colspan='2' align='right' class=\"more_product\">";
		if ($type == "busket")
			$result .= "<a href='javascript:void(0);' onClick='fGetMoreBusket(\"Y\");' class=\"get_more\">".GetMessage('NEWO_SUBTAB_MORE')."<span></span></a>";
		elseif ($type == "viewed")
			$result .= "<a href='javascript:void(0);' onClick='fGetMoreViewed(\"Y\");' class=\"get_more\">".GetMessage('NEWO_SUBTAB_MORE')."<span></span></a>";
		else
			$result .= "<a href='javascript:void(0);' onClick='fGetMoreRecom();' class=\"get_more\">".GetMessage('NEWO_SUBTAB_MORE')."<span></span></a>";
		$result .= "</td></tr>";
	}

	$result .= "</table>";

	return $result;
}

function fDeleteDoubleProduct($arShoppingCart = array(), $arDelete = array(), $showAll = 'N')
{
	global $COUNT_RECOM_BASKET_PROD;
	$arResult = array(
		"CNT" => 0,
		"ITEMS" => array(),
	);

	$arShoppingCartTmp = array();
	$arProductId = array();
	if (empty($arDelete) ||!is_array($arDelete))
		$arDelete = array();

	if (!empty($arShoppingCart) && is_array($arShoppingCart))
	{
		foreach($arShoppingCart as $key => $val)
		{
			if (!in_array($val["PRODUCT_ID"], $arDelete))
			{
				$arShoppingCartTmp[] = $val;
				$arProductId[] = $val["PRODUCT_ID"];
			}
		}
	}

	if (!empty($arShoppingCartTmp))
	{
		if (CModule::IncludeModule('catalog'))
		{
			$i = 0;

			$res = CIBlockElement::GetList(array(), array("ID" => $arProductId), false, false, array('ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID', 'IBLOCK_TYPE_ID'));
			while ($arSectionTmp = $res->Fetch())
				$arSection[$arSectionTmp["ID"]] = $arSectionTmp;

			foreach($arShoppingCartTmp as $key => $val)
			{
				if (!isset($val["PRODUCT_ID"]))
					$val["PRODUCT_ID"] = $val["ID"];

				if ((!isset($val["EDIT_PAGE_URL"]) || $val["EDIT_PAGE_URL"] == "") && $arSection[$val["PRODUCT_ID"]]["IBLOCK_ID"] > 0)
					$val["EDIT_PAGE_URL"] = "/bitrix/admin/iblock_element_edit.php?ID=".$val["PRODUCT_ID"]."&type=".$arSection[$val["PRODUCT_ID"]]["IBLOCK_TYPE_ID"]."&lang=".LANG."&IBLOCK_ID=".$arSection[$val["PRODUCT_ID"]]["IBLOCK_ID"]."&find_section_section=".IntVal($arSection[$val["PRODUCT_ID"]]["IBLOCK_SECTION_ID"]);

				$arResult["ITEMS"][] = $val;
				$i++;
				if ($i >= $COUNT_RECOM_BASKET_PROD && $showAll == "N")
					break;
			}
		}
	}

	if ($showAll == "Y")
		$arResult["CNT"] = count($arResult["ITEMS"]);
	else
		$arResult["CNT"] = count($arShoppingCartTmp);

	return $arResult;
}

?>