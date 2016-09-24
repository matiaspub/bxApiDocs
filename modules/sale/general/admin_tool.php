<?
use Bitrix\Main\Loader;
use Bitrix\Iblock;
use Bitrix\Catalog;
// some of this functions should probably migrate to CSaleHelper

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
	$user = GetMessage('NEWO_BUYER_NAME_NULL');

	if (intval($USER_ID) > 0)
	{
		$rsUser = CUser::GetByID($USER_ID);
		$arUser = $rsUser->Fetch();

		if (count($arUser) > 1)
		{
			$user = "<a href='javascript:void(0);' onClick=\"window.open('/bitrix/admin/user_search.php?lang=".LANGUAGE_ID."&FN=order_edit_info_form&FC=user_id', '', 'scrollbars=yes,resizable=yes,width=840,height=500,top='+Math.floor((screen.height - 840)/2-14)+',left='+Math.floor((screen.width - 760)/2-5));\">";
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
 * get template recomendet & basket product
 */
function fGetFormatedProductData($USER_ID, $LID, $arData, $CNT, $currency, $type, $crmMode = false)
{
	$result = "";
	$arSet = array();

	if (!is_array($arData) || count($arData) <= 0)
		return $result;

	$result = '<table width="100%">';
	if (CModule::IncludeModule('catalog'))
	{
		$arProductId = array();
		$arDataTab = array();

		$arSkuParentChildren = array();
		$arSkuParentId = array();
		$arSkuParent = array();
		$arSet = array();

		foreach ($arData as $item)
		{
			if (!empty($item["CURRENCY"]) && $item["CURRENCY"] != $currency)
			{
				if (doubleval($item["PRICE"]) > 0)
					$item["PRICE"] = CCurrencyRates::ConvertCurrency($item["PRICE"], $item["CURRENCY"], $currency);

				if (doubleval($item["DISCOUNT_PRICE"]) > 0)
					$item["DISCOUNT_PRICE"] = CCurrencyRates::ConvertCurrency($item["DISCOUNT_PRICE"], $item["CURRENCY"], $currency);

				$item["CURRENCY"] = $currency;
			}

			// get set items
			/** @var $productProvider IBXSaleProductProvider */
			if ($productProvider = CSaleBasket::GetProductProvider($item))
			{
				if (method_exists($productProvider, "GetSetItems"))
				{
					$itemInfo = (isset($item['ID']) ? array('BASKET_ID' => $item['ID']) : array());
					$arSets = $productProvider::GetSetItems($item["PRODUCT_ID"], CSaleBasket::TYPE_SET, $itemInfo);
					unset($itemInfo);

					if (is_array($arSets))
					{
						foreach ($arSets as $arSetData)
						{
							foreach ($arSetData["ITEMS"] as $setItem)
							{
								$setItem["FUSER_ID"] = $item["FUSER_ID"];
								$setItem["LID"] = $item["LID"];
								$setItem["MODULE"] = $item["MODULE"];
								$setItem["PRODUCT_PROVIDER_CLASS"] = $productProvider;
								$setItem["SET_PARENT_ID"] = $item["ID"];

								$arSet[$item["PRODUCT_ID"]][] = $setItem;
							}
						}
					}
				}
			}

			if ($item["MODULE"] == "catalog")
			{
				$arProductId[] = $item["PRODUCT_ID"];
				$arDataTab[$item["PRODUCT_ID"]] = $item;

				$arParent = CCatalogSku::GetProductInfo($item["PRODUCT_ID"]);
				if ($arParent)
				{
					$arSkuParentChildren[$item["PRODUCT_ID"]] = $arParent["ID"];
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
			$arProducts = array();
			$dbProduct = CIBlockElement::GetList(array(), array("ID" => $arProductId), false, false, array('ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID', 'DETAIL_PICTURE', 'PREVIEW_PICTURE', 'IBLOCK_TYPE_ID'));
			while($arProduct = $dbProduct->GetNext())
				$arProducts[] = $arProduct;

			foreach ($arProducts as $arProduct)
			{
				$imgCode = "";
				$arDataTab[$arProduct['ID']]['IBLOCK_ID'] = $arProduct['IBLOCK_ID'];
				$arDataTab[$arProduct['ID']]['IBLOCK_SECTION_ID'] = $arProduct['IBLOCK_SECTION_ID'];
				$arDataTab[$arProduct['ID']]['DETAIL_PICTURE'] = $arProduct['DETAIL_PICTURE'];
				$arDataTab[$arProduct['ID']]['PREVIEW_PICTURE'] = $arProduct['PREVIEW_PICTURE'];
				$arDataTab[$arProduct['ID']]['IBLOCK_TYPE_ID'] = $arProduct['IBLOCK_TYPE_ID'];
				$arProduct = $arDataTab[$arProduct['ID']];

				if ($arProduct["PREVIEW_PICTURE"] == "" && $arProduct["DETAIL_PICTURE"] == "" && is_set($arSkuParentChildren[$arProduct["PRODUCT_ID"]]))
				{
					$idTmp = $arSkuParentChildren[$arProduct["PRODUCT_ID"]];
					$arProduct["DETAIL_PICTURE"] = $arSkuParent[$idTmp]["DETAIL_PICTURE"];
					$arProduct["PREVIEW_PICTURE"] = $arSkuParent[$idTmp]["PREVIEW_PICTURE"];
				}

				if ($arProduct["IBLOCK_ID"] > 0)
				{
					$arProduct["EDIT_PAGE_URL"] = CIBlock::GetAdminElementEditLink($arProduct["IBLOCK_ID"], $arProduct["PRODUCT_ID"], array(
						"find_section_section" => $arProduct["IBLOCK_SECTION_ID"],
						'WF' => 'Y',
					));
				}

				$arProduct["NAME"] = htmlspecialcharsex($arProduct["NAME"]);
				$arProduct["DETAIL_PAGE_URL"] = htmlspecialcharsex($arProduct["DETAIL_PAGE_URL"]);
				$arProduct["CURRENCY"] = htmlspecialcharsex($arProduct["CURRENCY"]);

				if ($arProduct["PREVIEW_PICTURE"] > 0)
					$imgCode = $arProduct["PREVIEW_PICTURE"];
				elseif ($arProduct["DETAIL_PICTURE"] > 0)
					$imgCode = $arProduct["DETAIL_PICTURE"];

				if ($imgCode > 0)
				{
					$arFile = CFile::GetFileArray($imgCode);
					$arImgProduct = CFile::ResizeImageGet($arFile, array('width'=>80, 'height'=>80), BX_RESIZE_IMAGE_PROPORTIONAL, false, false);
					if (is_array($arImgProduct))
					{
						$imgUrl = $arImgProduct["src"];
						$imgProduct = '<a href="'.$arProduct["EDIT_PAGE_URL"].'" target="_blank"><img src="'.$arImgProduct["src"].'" alt="" title="'.$arProduct["NAME"].'" ></a>';
					}
				}
				else
					$imgProduct = '<div class="no_foto">'.GetMessage('SOD_NO_FOTO')."</div>";

				$result .= '<tr><td class="tab_img">'.$imgProduct.'</td><td class="tab_text">
									<div class="order_name"><a href="'.$arProduct["EDIT_PAGE_URL"].'" target="_blank" title="'.$arProduct["NAME"].'">'.$arProduct["NAME"].'</a></div>
									<div class="order_price">'.GetMessage('SOD_ORDER_RECOM_PRICE').': <b>'.SaleFormatCurrency($arProduct["PRICE"], $currency).'</b>';

				if (!empty($arSet) && array_key_exists($arProduct["PRODUCT_ID"], $arSet)) // show/hide set item link
				{
					$result .= '<br/>
						<div>
							<a id="set_toggle_link_b'.$arProduct["ID"].'"
								href="javascript:void(0);"
								class="dashed-link show-set-link"
								title="'.GetMessage("SOD_SHOW_SET").'"
								onclick="fToggleSetItems(\'b'.$arProduct["ID"].'\');">'.GetMessage("SOD_SHOW_SET").'</a>
						</div>';
				}

				$result .= '</div>';

				$arResult = CSaleProduct::GetProductSku($USER_ID, $LID, $arProduct["PRODUCT_ID"], $arProduct["NAME"], '', $arProduct);

				$arResult["POPUP_MESSAGE"] = array(
					"PRODUCT_ADD" => GetMessage('SOD_POPUP_TO_BASKET'),
					"PRODUCT_NOT_ADD" => GetMessage('SOD_POPUP_TO_BASKET_NOT'),
					"PRODUCT_PRICE_FROM" => GetMessage('SOD_POPUP_FROM')
				);

				if (!$crmMode)
				{
					if (!empty($arResult["SKU_ELEMENTS"]))
					{
						$result .= '<a href="javascript:void(0);" class="get_new_order" onclick="fAddToBasketMoreProductSku('.CUtil::PhpToJsObject($arResult['SKU_ELEMENTS']).', '.CUtil::PhpToJsObject($arResult['SKU_PROPERTIES']).', \'\', '.CUtil::PhpToJsObject($arResult["POPUP_MESSAGE"]).');"><span></span>'.GetMessage('SOD_SUBTAB_ADD_ORDER').'</a>';
					}
					else
					{
						$cntProd = (floatval($arProduct["QUANTITY"]) > 0) ? floatval($arProduct["QUANTITY"]) : 1;
						$url = "/bitrix/admin/sale_order_new.php?lang=".LANGUAGE_ID."&user_id=".$USER_ID."&LID=".$LID."&product[".$arProduct["PRODUCT_ID"]."]=".$cntProd;
						$result .= "<a href=\"".$url."\" target=\"_blank\" class=\"get_new_order\"><span></span>".GetMessage('SOD_SUBTAB_ADD_ORDER')."</a>";
					}
				}

				$result .= "</td></tr>";

				// show set items
				if (!empty($arSet) && array_key_exists($arProduct["PRODUCT_ID"], $arSet))
				{
					foreach ($arSet[$arProduct["PRODUCT_ID"]] as $set)
					{
						$editUrl = CIBlock::GetAdminElementEditLink($set["IBLOCK_ID"], $set["ITEM_ID"], array(
							"find_section_section" => $set["IBLOCK_SECTION_ID"],
							'WF' => 'Y',
						));

						if ($set["PREVIEW_PICTURE"] > 0)
							$imgCode = $set["PREVIEW_PICTURE"];
						elseif ($set["DETAIL_PICTURE"] > 0)
							$imgCode = $set["DETAIL_PICTURE"];

						if ($imgCode > 0)
						{
							$arFile = CFile::GetFileArray($imgCode);
							$arImgProduct = CFile::ResizeImageGet($arFile, array('width'=>80, 'height'=>80), BX_RESIZE_IMAGE_PROPORTIONAL, false, false);
							if (is_array($arImgProduct))
							{
								$imgUrl = $arImgProduct["src"];
								$img = '<a href="'.$editUrl.'" target="_blank"><img src="'.$arImgProduct["src"].'" alt="" title="'.$set["NAME"].'" ></a>';
							}
						}
						else
							$img = '<div class="no_foto">'.GetMessage('SOD_NO_FOTO')."</div>";

						$result .= '
							<tr style="display:none" class="set_item_b'.$arProduct["ID"].'">
								<td class="tab_img">'.$img.'</td>
								<td class="tab_text">
									<div class="order_name">
										<a href="'.$editUrl.'" style="font-style:italic" target="_blank" title="'.$set["NAME"].'">'.$set["NAME"].'</a>
									</div>
									<div class="order_price">'.GetMessage('SOD_ORDER_RECOM_PRICE').': <b>'.SaleFormatCurrency($set["PRICE"], $currency).'</b></div>
								</td>
							</tr>';
					}
				}
			}
		}
	}//end if

	$result .= '<tr><td colspan="2" align="right" class="more_product">';
	if ($CNT > 2)
		$result .= "<a href='javascript:void(0);' onClick=\"fGetMoreProduct('".$type."');\"  class=\"get_more\">".GetMessage('SOD_SUBTAB_MORE')."<span></span></a>";
	$result .= "</td></tr>";
	$result .= "</table>";

	return $result;
}

function fChangeOrderStatus($ID, $STATUS_ID)
{
	global $APPLICATION;
	global $crmMode;

	$errorMessageTmp = "";

	$STATUS_ID = trim($STATUS_ID);
	if (strlen($STATUS_ID) <= 0)
		$errorMessageTmp .= GetMessage("ERROR_NO_STATUS").". ";

	if ('' == $errorMessageTmp)
	{
		if (!CSaleOrder::CanUserChangeOrderStatus($ID, $STATUS_ID, $GLOBALS["USER"]->GetUserGroupArray()))
			$errorMessageTmp .= GetMessage("SOD_NO_PERMS2STATUS").". ";
	}

	if ('' == $errorMessageTmp)
	{
		if (!CSaleOrder::StatusOrder($ID, $STATUS_ID))
		{
			if ($ex = $APPLICATION->GetException())
			{
				if ($ex->GetID() != "ALREADY_FLAG")
					$errorMessageTmp .= $ex->GetString();
			}
			else
				$errorMessageTmp .= GetMessage("ERROR_CHANGE_STATUS").". ";
		}
	}

	$arResult = array(
		'STATUS_ERR' => false,
		'STATUS_ERR_MESS' => '',
	);

	$dbOrder = CSaleOrder::GetList(
			array("ID" => "DESC"),
			array("ID" => $ID),
			false,
			false,
			array("DATE_STATUS", "EMP_STATUS_ID", "STATUS_ID")
		);
	if ($arOrder = $dbOrder->Fetch())
	{
		$arResult["DATE_STATUS"] = $arOrder["DATE_STATUS"];
		if (!$crmMode && IntVal($arOrder["EMP_STATUS_ID"]) > 0)
			$arResult["EMP_STATUS_ID"] = GetFormatedUserName($arOrder["EMP_STATUS_ID"], false);

		$arResult["STATUS_ID"] = $arOrder["STATUS_ID"];
	}
	if ('' != $errorMessageTmp)
	{
		$arResult['STATUS_ERR'] = true;
		$arResult['STATUS_ERR_MESS'] = $errorMessageTmp;
	}

	return $arResult;
}

/*
 * shows file property input control
 */
function fShowFilePropertyField($name, $property_fields, $values, $max_file_size_show=50000)
{
	global $crmMode;
	$disableFiles = (isset($crmMode) && $crmMode);
	$res = "";
	if (CModule::IncludeModule('fileman'))
	{
		if (!is_array($values) || empty($values))
			$values = array("n0" => 0);

		if ($property_fields["MULTIPLE"] == "N")
		{
			foreach($values as $key => $val)
			{
				if(is_array($val))
					$file_id = $val["VALUE"];
				else
					$file_id = $val;

				$res = CFileInput::Show(
					$name."[".$key."]",
					$file_id,
					array(
						"IMAGE" => "Y",
						"PATH" => "Y",
						"FILE_SIZE" => "Y",
						"DIMENSIONS" => "Y",
						"IMAGE_POPUP" => "Y",
						"MAX_SIZE" => array("W" => 200, "H" => 170),
					),
					array(
						'upload' => !$disableFiles,
						'del' => !$disableFiles,
						'medialib' => false,
						'file_dialog' => false,
						'cloud' => false,
						'description' => false
					)
				);
			}
		}
		else
		{
			$inputName = array();
			foreach($values as $key=>$val)
			{
				if(is_array($val))
					$inputName[$name."[".$key."]"] = $val["VALUE"];
				else
					$inputName[$name."[".$key."]"] = $val;
			}

			$res = CFileInput::ShowMultiple($inputName, $name."[n#IND#]", array(
				"IMAGE" => "Y",
				"PATH" => "Y",
				"FILE_SIZE" => "Y",
				"DIMENSIONS" => "Y",
				"IMAGE_POPUP" => "Y",
				"MAX_SIZE" => array("W" => 200, "H" => 170),
			), false, array(
					'upload' => !$disableFiles,
					'del' => !$disableFiles,
					'medialib' => false,
					'file_dialog' => false,
					'cloud' => false,
					'description' => false
			));
		}
	}

	return $res;
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
		if (intval($arPersonType["ID"]) == intval($PERSON_TYPE_ID))
			$class = " selected";

		$personTypeSelect .= "<option value=\"".$arPersonType["ID"]."\" ".$class.">".$arPersonType["NAME"]." [".$arPersonType["ID"]."]</option>";
	}
	$personTypeSelect .= "</select>";

	$userComment = "";
	$userDisplay = "none";
	if (intval($ORDER_ID) > 0)
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
				$resultHtml .= fUserProfile(intval($_POST["user_id"]), intval($_POST["buyer_type_id"]), $default = '');
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
					if (intval($arCountProps["IS_EMAIL"]) <= 0)
						$resultHtml .= "<tr class=\"adm-detail-required-field\">
							<td class=\"adm-detail-content-cell-l\" width=\"40%\">".GetMessage("NEWO_BUYER_REG_MAIL")."</td>
							<td class=\"adm-detail-content-cell-r\"><input type=\"text\" name=\"NEW_BUYER_EMAIL\" size=\"30\" value=\"".htmlspecialcharsbx(trim($_REQUEST["NEW_BUYER_EMAIL"]))."\" tabindex=\"1\" /></td>
						</tr>";
					if (intval($arCountProps["IS_PAYER"]) <= 0)
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

	$arPropertiesList = array();
	$dbProperties = CSaleOrderProps::GetList(
		array("GROUP_SORT" => "ASC", "PROPS_GROUP_ID" => "ASC", "SORT" => "ASC", "NAME" => "ASC"),
		array("PERSON_TYPE_ID" => $PERSON_TYPE_ID, "ACTIVE" => "Y", "RELATED" => false),
		false,
		false,
		array("*")
	);
	while($property = $dbProperties->GetNext())
	{
		$arPropertiesList[$property['ID']] = $property;
	}

	// getting values
	$arPropValues = array();
	if ($formVarsSubmit) // from request
	{
		$locationIndexForm = "";
		foreach ($_POST as $key => $value)
		{
			if (substr($key, 0, strlen("CITY_ORDER_PROP_")) == "CITY_ORDER_PROP_")
			{
				$arPropValues[intval(substr($key, strlen("CITY_ORDER_PROP_")))] = htmlspecialcharsbx($value);
				$locationIndexForm = intval(substr($key, strlen("CITY_ORDER_PROP_")));
			}
			if (substr($key, 0, strlen("ORDER_PROP_")) == "ORDER_PROP_")
			{
				if ($locationIndexForm != intval(substr($key, strlen("ORDER_PROP_"))) && !is_array($value))
					$arPropValues[intval(substr($key, strlen("ORDER_PROP_")))] = htmlspecialcharsbx($value);
			}
		}
		$userComment = $_POST["USER_DESCRIPTION"];
	}
	elseif ($ORDER_ID == "" AND $USER_ID != "") // from profile
	{
		//profile
		$userProfile = array();
		$userProfile = CSaleOrderUserProps::DoLoadProfiles($USER_ID, $PERSON_TYPE_ID);
		$arPropValues = $userProfile[$PERSON_TYPE_ID]["VALUES"];
	}
	elseif ($ORDER_ID != "") // from order properties
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
			$arPropValues[intval($arPropValuesList["ORDER_PROPS_ID"])] = htmlspecialcharsbx($arPropValuesList["VALUE"]);
		}
	}

	$location2townFldMap = array();
	$arDisableFieldForLocation = array();
	//select field (town) for disable
	$dbProperties = CSaleOrderProps::GetList(
		array(),
		array("PERSON_TYPE_ID" => $PERSON_TYPE_ID, "ACTIVE" => "Y", ">INPUT_FIELD_LOCATION" => 0),
		false,
		false,
		array("INPUT_FIELD_LOCATION")
	);
	while ($arProperties = $dbProperties->Fetch())
	{
		$arDisableFieldForLocation[$arProperties["INPUT_FIELD_LOCATION"]] = $arProperties["INPUT_FIELD_LOCATION"];
	}

	//show town if location is another
	$arEnableTownProps = array();
	if ($ORDER_ID > 0)
	{
		$dbOrderProps = CSaleOrderPropsValue::GetOrderProps($ORDER_ID);
		while ($arOrderProps = $dbOrderProps->Fetch())
		{
			if ($arOrderProps["TYPE"] == "LOCATION" && $arOrderProps["ACTIVE"] == "Y" && $arOrderProps["IS_LOCATION"] == "Y")
			{
				if (in_array($arOrderProps["INPUT_FIELD_LOCATION"], $arDisableFieldForLocation))
				{
					if (CSaleLocation::isLocationProMigrated())
					{
						//if(CSaleLocation::checkLocationIsAboveCity($arPropValues[$arOrderProps["ORDER_PROPS_ID"]]))
						unset($arDisableFieldForLocation[$arOrderProps["INPUT_FIELD_LOCATION"]]);
					}
					else
					{
						$arLocation = CSaleLocation::GetByID($arPropValues[$arOrderProps["ORDER_PROPS_ID"]]);
						if (intval($arLocation["CITY_ID"]) <= 0)
							unset($arDisableFieldForLocation[$arOrderProps["INPUT_FIELD_LOCATION"]]);
					}
				}

				$location2townFldMap[$arOrderProps['ORDER_PROPS_ID']] = $arOrderProps['INPUT_FIELD_LOCATION'];
			}
		}
	}

	$propertyGroupID = -1;

	foreach($arPropertiesList as $arProperties)
	{
		if (intval($arProperties["PROPS_GROUP_ID"]) != $propertyGroupID)
		{
			$resultHtml .= "<tr><td colspan=\"2\" style=\"text-align:center;font-weight:bold;font-size:14px;color:rgb(75, 98, 103);\" >".htmlspecialcharsEx($arProperties["GROUP_NAME"])."\n</td>\n</tr>";
			$propertyGroupID = intval($arProperties["PROPS_GROUP_ID"]);
		}

		if (intval($arProperties["PROPS_GROUP_ID"]) != $propertyGroupID)
			$propertyGroupID = intval($arProperties["PROPS_GROUP_ID"]);

		$adit = "";
		$requiredField = "";
		if ($arProperties["REQUIED"] == "Y" || $arProperties["IS_PROFILE_NAME"] == "Y" || $arProperties["IS_LOCATION"] == "Y" || $arProperties["IS_LOCATION4TAX"] == "Y" || $arProperties["IS_PAYER"] == "Y" || $arProperties["IS_ZIP"] == "Y")
		{
			$adit = " class=\"adm-detail-required-field\"";
			$requiredField = " class=\"adm-detail-content-cell-l\"";
		}

		$isTownProperty = in_array($arProperties["ID"], $location2townFldMap) || $arProperties['CODE'] == 'CITY';

		//delete town from location
		if (in_array($arProperties["ID"], $arDisableFieldForLocation))
			$resultHtml .= "<tr style=\"display:none;\" id=\"town_location_".$arProperties["ID"]."\"".$adit.">\n";
		else
			$resultHtml .= "<tr id=\"town_location_".$arProperties["ID"]."\"".$adit.($isTownProperty ? " class=\"-bx-order-property-city\"" : '').">\n";

		if(($arProperties["TYPE"] == "MULTISELECT" || $arProperties["TYPE"] == "TEXTAREA" || $arProperties["TYPE"] == "FILE") || ($ORDER_ID <= 0 && $arProperties["IS_PROFILE_NAME"] == "Y") )
			$resultHtml .= "<td valign=\"top\" class=\"adm-detail-content-cell-l\" width=\"40%\">\n";
		else
			$resultHtml .= "<td align=\"right\" width=\"40%\" ".$requiredField.">\n";

		$resultHtml .= $arProperties["NAME"].":</td>";

		$curVal = $arPropValues[intval($arProperties["ID"])];

		if($arProperties["IS_EMAIL"] == "Y" || $arProperties["IS_PAYER"] == "Y")
		{
			if(strlen($arProperties["DEFAULT_VALUE"]) <= 0 && intval($USER_ID) > 0)
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

			if ($arProperties["IS_PAYER"] == "Y" && intval($USER_ID) <= 0)
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

				$resultHtml .= "<div class=\"fio newo_break_active\"><input onblur=\"if (this.value==''){this.value='".CUtil::JSEscape(GetMessage('NEWO_BREAK_LAST_NAME'))."';BX.addClass(this.parentNode,'newo_break_active');}\" onfocus=\"if (this.value=='".CUtil::JSEscape(GetMessage('NEWO_BREAK_LAST_NAME'))."') {this.value='';BX.removeClass(this.parentNode,'newo_break_active');}\" type=\"text\" name=\"BREAK_LAST_NAME\" id=\"BREAK_LAST_NAME\" size=\"30\" value=\"".$BREAK_LAST_NAME_TMP."\" /></div>";
				$resultHtml .= "<div class=\"fio newo_break_active\"><input onblur=\"if (this.value==''){this.value='".CUtil::JSEscape(GetMessage('NEWO_BREAK_NAME'))."';BX.addClass(this.parentNode,'newo_break_active');}\" onfocus=\"if (this.value=='".CUtil::JSEscape(GetMessage('NEWO_BREAK_NAME'))."') {this.value='';BX.removeClass(this.parentNode,'newo_break_active');}\" type=\"text\" name=\"BREAK_NAME\" id=\"BREAK_NAME_BUYER\" size=\"30\" value=\"".$NEWO_BREAK_NAME_TMP."\" /></div>";
				$resultHtml .= "<div class=\"fio newo_break_active\"><input onblur=\"if (this.value==''){this.value='".CUtil::JSEscape(GetMessage('NEWO_BREAK_SECOND_NAME'))."';BX.addClass(this.parentNode,'newo_break_active');}\" onfocus=\"if (this.value=='".CUtil::JSEscape(GetMessage('NEWO_BREAK_SECOND_NAME'))."') {this.value='';BX.removeClass(this.parentNode,'newo_break_active');}\" type=\"text\" name=\"BREAK_SECOND_NAME\" id=\"BREAK_SECOND_NAME\" size=\"30\" value=\"".$BREAK_SECOND_NAME_TMP."\" /></div>";
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
			$resultHtml .= ($arProperties["IS_ZIP"] == "Y" ? 'class="-bx-property-is-zip" ' : '');
			$resultHtml .= 'id="ORDER_PROP_'.$arProperties["ID"].'" '.$change.'>';

			if ($arProperties["IS_PAYER"] == "Y" && intval($USER_ID) <= 0)
				$resultHtml .= '</div>';
		}
		elseif ($arProperties["TYPE"] == "SELECT")
		{
			$size = (intval($arProperties["SIZE1"]) > 0) ? intval($arProperties["SIZE1"]) : 5;

			$resultHtml .= '<select name="ORDER_PROP_'.$arProperties["ID"].'" ';
			$resultHtml .= 'id="ORDER_PROP_'.$arProperties["ID"].'" ';
			$resultHtml .= 'size='.$size.' ';
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
			$size = (intval($arProperties["SIZE1"]) > 0) ? intval($arProperties["SIZE1"]) : 5;

			$resultHtml .= '<select multiple name="ORDER_PROP_'.$arProperties["ID"].'[]" ';
			$resultHtml .= 'id="ORDER_PROP_'.$arProperties["ID"].'" ';
			$resultHtml .= 'size='.$size.' ';
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
			$DELIVERY_LOCATION = $arPropValues[intval($arProperties["ID"])];

			$locationID = $curVal;

			$tmpLocation = '';
			ob_start();
			?>

			<?if($arProperties['IS_LOCATION'] == 'Y'):?>

				<?
				$funcId = 'changeLocationCity_'.$arProperties['ID'];
				?>

				<script>
					window['<?=$funcId?>'] = function(node, info){
						fChangeLocationCity(node, info, <?=intval($location2townFldMap[$arProperties['ID']])?>)
					}
					window.orderNewLocationPropId = <?=intval($arProperties['ID'])?>;
				</script>

			<?endif?>

			<?
			CSaleLocation::proxySaleAjaxLocationsComponent(
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
					"ONCITYCHANGE" => "fChangeLocationCity();",
					"PUBLIC" => "N",
				),
				array(
					"JS_CALLBACK" => $arProperties['IS_LOCATION'] == 'Y' ? $funcId : false,
					"ID" => $curVal,
					"CODE" => '',
					"SHOW_DEFAULT_LOCATIONS" => 'Y',
					"JS_CONTROL_GLOBAL_ID" => intval($arProperties["ID"]),

					"PRECACHE_LAST_LEVEL" => "Y",
					"PRESELECT_TREE_TRUNK" => "Y"
				),
				'',
				false,
				'location-selector-wrapper prop-'.intval($arProperties["ID"])
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
			$resultHtml .= '<div id="ORDER_PROP_'.$arProperties["ID"].'" type="radio">';// type="radio"
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
		elseif ($arProperties["TYPE"] == "FILE")
		{
			$arValues = array();
			$arTmpValues = array();
			if (isset($arPropValues[$arProperties["ID"]]))
			{
				$arTmpValues = explode(", ", $arPropValues[$arProperties["ID"]]);
				foreach ($arTmpValues as $key => $value)
					$arValues[$value] = $value;
			}

			$resultHtml .= fShowFilePropertyField("ORDER_PROP_".$arProperties["ID"], $arProperties, $arValues, $arProperties["SIZE1"], $formVarsSubmit);
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
 * Returns HTML controls of the order properties
 *
 * Currently is used to show order properties related to payment/delivery systems in the order_new form
 */
function getOrderPropertiesHTML($arOrderProps, $arPropValues = array(), $LID, $USER_ID = '', $ORDER_ID = 0, $formVarsSubmit = false)
{
	$propertyGroupID = -1;
	$arDisableFieldForLocation = array();
	$resultHtml = "<table id=\"order_related_props\">";

	// get order properties values
	if ($formVarsSubmit)
	{
		$locationIndexForm = "";
		foreach ($_POST as $key => $value)
		{
			if (substr($key, 0, strlen("CITY_ORDER_PROP_")) == "CITY_ORDER_PROP_")
			{
				$arPropValues[intval(substr($key, strlen("CITY_ORDER_PROP_")))] = htmlspecialcharsbx($value);
				$locationIndexForm = intval(substr($key, strlen("CITY_ORDER_PROP_")));
			}
			if (substr($key, 0, strlen("ORDER_PROP_")) == "ORDER_PROP_")
			{
				if ($locationIndexForm != intval(substr($key, strlen("ORDER_PROP_"))))
				{
					if (!is_array($value))
						$arPropValues[intval(substr($key, strlen("ORDER_PROP_")))] = htmlspecialcharsbx($value);
					else
					{
						$arValues = array();
						foreach ($value as $k => $v)
							$arValues[$key] = htmlspecialcharsbx($v);

						$arPropValues[intval(substr($key, strlen("ORDER_PROP_")))] = $arValues;
					}
				}
			}
		}
	}

	// iterate over list of properties
	if (is_array($arOrderProps))
	{
		foreach ($arOrderProps as $arProperties)
		{
			if (intval($arProperties["PROPS_GROUP_ID"]) != $propertyGroupID)
			{
				$resultHtml .= "<tr><td colspan=\"2\" style=\"text-align:center;font-weight:bold;font-size:14px;color:rgb(75, 98, 103);\" >".htmlspecialcharsEx($arProperties["GROUP_NAME"])."\n</td>\n</tr>";
				$propertyGroupID = intval($arProperties["PROPS_GROUP_ID"]);
			}

			if (intval($arProperties["PROPS_GROUP_ID"]) != $propertyGroupID)
				$propertyGroupID = intval($arProperties["PROPS_GROUP_ID"]);

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

			$resultHtml .= $arProperties["NAME"].":</td>";

			$curVal = $arPropValues[intval($arProperties["ID"])];

			if($arProperties["IS_EMAIL"] == "Y" || $arProperties["IS_PAYER"] == "Y")
			{
				if(strlen($arProperties["DEFAULT_VALUE"]) <= 0 && intval($USER_ID) > 0)
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

				if ($arProperties["IS_PAYER"] == "Y" && intval($USER_ID) <= 0)
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

					$resultHtml .= "<div class=\"fio newo_break_active\"><input onblur=\"if (this.value==''){this.value='".CUtil::JSEscape(GetMessage('NEWO_BREAK_LAST_NAME'))."';BX.addClass(this.parentNode,'newo_break_active');}\" onfocus=\"if (this.value=='".CUtil::JSEscape(GetMessage('NEWO_BREAK_LAST_NAME'))."') {this.value='';BX.removeClass(this.parentNode,'newo_break_active');}\" type=\"text\" name=\"BREAK_LAST_NAME\" id=\"BREAK_LAST_NAME\" size=\"30\" value=\"".$BREAK_LAST_NAME_TMP."\" /></div>";
					$resultHtml .= "<div class=\"fio newo_break_active\"><input onblur=\"if (this.value==''){this.value='".CUtil::JSEscape(GetMessage('NEWO_BREAK_NAME'))."';BX.addClass(this.parentNode,'newo_break_active');}\" onfocus=\"if (this.value=='".CUtil::JSEscape(GetMessage('NEWO_BREAK_NAME'))."') {this.value='';BX.removeClass(this.parentNode,'newo_break_active');}\" type=\"text\" name=\"BREAK_NAME\" id=\"BREAK_NAME_BUYER\" size=\"30\" value=\"".$NEWO_BREAK_NAME_TMP."\" /></div>";
					$resultHtml .= "<div class=\"fio newo_break_active\"><input onblur=\"if (this.value==''){this.value='".CUtil::JSEscape(GetMessage('NEWO_BREAK_SECOND_NAME'))."';BX.addClass(this.parentNode,'newo_break_active');}\" onfocus=\"if (this.value=='".CUtil::JSEscape(GetMessage('NEWO_BREAK_SECOND_NAME'))."') {this.value='';BX.removeClass(this.parentNode,'newo_break_active');}\" type=\"text\" name=\"BREAK_SECOND_NAME\" id=\"BREAK_SECOND_NAME\" size=\"30\" value=\"".$BREAK_SECOND_NAME_TMP."\" /></div>";
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

				if ($arProperties["IS_PAYER"] == "Y" && intval($USER_ID) <= 0)
					$resultHtml .= '</div>';
			}
			elseif ($arProperties["TYPE"] == "SELECT")
			{
				$size = (intval($arProperties["SIZE1"]) > 0) ? intval($arProperties["SIZE1"]) : 5;

				$resultHtml .= '<select name="ORDER_PROP_'.$arProperties["ID"].'" ';
				$resultHtml .= 'id="ORDER_PROP_'.$arProperties["ID"].'" ';
				$resultHtml .= 'size='.$size.' ';
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
				$size = (intval($arProperties["SIZE1"]) > 0) ? intval($arProperties["SIZE1"]) : 5;

				$resultHtml .= '<select multiple name="ORDER_PROP_'.$arProperties["ID"].'[]" ';
				$resultHtml .= 'id="ORDER_PROP_'.$arProperties["ID"].'" ';
				$resultHtml .= 'size='.$size.' ';
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
				$DELIVERY_LOCATION = $arPropValues[intval($arProperties["ID"])];
				$locationID = $curVal;
				$tmpLocation = '';

				ob_start();

				CSaleLocation::proxySaleAjaxLocationsComponent(
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
						"ONCITYCHANGE" => "",
						"PUBLIC" => "N",
					),
					array(
						"ID" => "",
						"CODE" => $curVal,
						"PROVIDE_LINK_BY" => "code",
					)
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
			elseif ($arProperties["TYPE"] == "FILE")
			{
				$arValues = array();
				$arTmpValues = array();
				if (isset($arPropValues[$arProperties["ID"]]) && !is_array($arPropValues[$arProperties["ID"]]))
				{
					$arTmpValues = explode(", ", $arPropValues[$arProperties["ID"]]);
					foreach ($arTmpValues as $key => $value)
						$arValues[$value] = $value;
				}

				$resultHtml .= fShowFilePropertyField("ORDER_PROP_".$arProperties["ID"], $arProperties, $arValues, $arProperties["SIZE1"], $formVarsSubmit);
			}

			if (strlen($arProperties["DESCRIPTION"]) > 0)
			{
				$resultHtml .= "<br><small>".htmlspecialcharsEx($arProperties["DESCRIPTION"])."</small>";
			}
			$resultHtml .= "\n</td>\n</tr>";

		}//end while
	}

	$resultHtml .= "</table>";

	return $resultHtml;
}

/*
 * Returns HTML control with payment systems data
 */
function fGetPaySystemsHTML($PERSON_TYPE_ID, $PAY_SYSTEM_ID)
{
	$resultHtml = "<table width=\"100%\">";
	$resultHtml .= "<tr class=\"adm-detail-required-field\">\n<td class=\"adm-detail-content-cell-l\" width=\"40%\">".GetMessage("SOE_PAY_SYSTEM").":</td><td class=\"adm-detail-content-cell-r\" width=\"60%\">";

	$arPaySystem = CSalePaySystem::DoLoadPaySystems($PERSON_TYPE_ID);

	$resultHtml .= "<select name=\"PAY_SYSTEM_ID\" id=\"PAY_SYSTEM_ID\" onChange=\"fChangePaymentSystem();\">\n";
	$resultHtml .= "<option value=\"\">(".GetMessage("SOE_SELECT").")</option>";
	foreach ($arPaySystem as $key => $val)
	{
		$resultHtml .= "<option value=\"".$key."\"";
		if ($key == intval($PAY_SYSTEM_ID))
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
	if (!empty($userProfile) && is_array($userProfile))
	{
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

/**
 * Returns HTML select control with delivery services data for admin pages
 * @deprecated
 */
function fGetDeliverySystemsHTML($location, $locationZip, $weight, $price, $currency, $siteId, $defaultDelivery, $arShoppingCart)
{
	$arResult = array();
	$description = "";
	$error = "";
	$setDeliveryPrice = false;

	$arDelivery = CSaleDelivery::DoLoadDelivery($location, $locationZip, $weight, $price, $currency, $siteId, $arShoppingCart);

	$deliveryHTML = "<select name=\"DELIVERY_ID\" id=\"DELIVERY_ID\" onchange=\"fChangeDelivery();\">";
	$deliveryHTML .= "<option value=\"\">".GetMessage('NEWO_DELIVERY_NO')."</option>";

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
						$setDeliveryPrice = true;
					}

					$deliveryHTML .= "<option".$selected." value=\"".$v["ID"]."\">".$val["TITLE"]." (".$v["TITLE"].") [".$v["ID"]."]</option>";
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
					$setDeliveryPrice = true;
					$description = $val["DESCRIPTION"];
				}

				$deliveryHTML .= "<option".$selected." value=\"".$val["ID"]."\">".$val["NAME"]." [".$val["ID"]."]</option>";
			}
		}
	}

	$deliveryHTML .= "</select>";

	$arResult["DELIVERY"] = $deliveryHTML;
	$arResult["DELIVERY_DEFAULT"] = $defaultDelivery;
	$arResult["DELIVERY_DEFAULT_PRICE"] = (count($arDelivery) > 0 && $setDeliveryPrice === true) ? $price : 0;
	$arResult["DELIVERY_DEFAULT_DESCRIPTION"] = $description;
	$arResult["DELIVERY_DEFAULT_ERR"] = $error;
	$arResult["CURRENCY"] = $currency;

	return $arResult;
}

/*
 * coupons
 */
function fGetCoupon($COUPON)
{
	$arCoupon = array();
	if (!empty($COUPON))
	{
		if (is_array($COUPON))
		{
			foreach ($COUPON as &$oneCoupon)
			{
				$oneCoupon = trim((string)$oneCoupon);
				if ($oneCoupon != '')
					$arCoupon[] = $oneCoupon;
			}
			unset($oneCoupon);
		}
		else
		{
			$coupons = explode(",", $COUPON);
			if (!empty($coupons))
			{
				foreach($coupons as &$val)
				{
					$val = trim($val);
					if ($val != '')
						$arCoupon[] = $val;
				}
				unset($val);
			}
		}
	}
	return $arCoupon;
}

/*
 * get location ID and ZIP
 */
function fGetLocationID($PERSON_TYPE_ID)
{
	$arResult = array();
	$dbProperties = CSaleOrderProps::GetList(
		array("SORT" => "ASC"),
		array("PERSON_TYPE_ID" => $PERSON_TYPE_ID),
		false,
		false,
		array("TYPE", "IS_ZIP", "ID", "SORT")
	);
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
	}

	return $arResult;
}

/*
 * product basket array
 */
function fGetUserShoppingCart($arProduct, $LID, $recalcOrder)
{
	$arOrderProductPrice = array();
	$i = 0;

	$arSortNum = array();
	foreach($arProduct as $key => $val)
	{
		$arSortNum[] = $val['PRICE_DEFAULT'];
		$arProduct[$key]["PRODUCT_ID"] = (int)$val["PRODUCT_ID"];
		$arProduct[$key]["TABLE_ROW_ID"] = $key;
	}
	if (!empty($arProduct) && !empty($arSortNum))
		array_multisort($arSortNum, SORT_DESC, $arProduct);

	$arBasketIds = array();
	$basketMap = array();
	foreach($arProduct as $key => $val)
	{
		$val["QUANTITY"] = abs(str_replace(",", ".", $val["QUANTITY"]));
		$val["QUANTITY_DEFAULT"] = $val["QUANTITY"];
		$val["PRICE"] = str_replace(",", ".", $val["PRICE"]);

		// Y is used when custom price was set in the admin form
		if ($val["CALLBACK_FUNC"] == "Y")
		{
			$val["CALLBACK_FUNC"] = false;
			$val["CUSTOM_PRICE"] = "Y";

			if (isset($val["BASKET_ID"]) && (int)$val["BASKET_ID"] > 0)
			{
				CSaleBasket::Update($val["BASKET_ID"], array("CUSTOM_PRICE" => "Y"));
			}

			//$val["DISCOUNT_PRICE"] = $val["PRICE_DEFAULT"] - $val["PRICE"];
		}

		$arOrderProductPrice[$i] = $val;
		$arOrderProductPrice[$i]["TABLE_ROW_ID"] = $val["TABLE_ROW_ID"];
		$arOrderProductPrice[$i]["NAME"] = htmlspecialcharsback($val["NAME"]);
		$arOrderProductPrice[$i]["LID"] = $LID;
		$arOrderProductPrice[$i]["CAN_BUY"] = "Y";
		$arOrderProductPrice[$i]['RESERVED'] = 'N';

		if (isset($val["BASKET_ID"]) && (int)$val["BASKET_ID"] > 0)
		{
			$basketId = (int)$val["BASKET_ID"];
			$arOrderProductPrice[$i]["ID"] = $basketId;

			$arBasketIds[] = $basketId;
			$basketMap[$basketId] = &$arOrderProductPrice[$i];

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

	// collect status of reservation elements basket
	if (!empty($arBasketIds))
	{
		$rsBasketItems = CSaleBasket::GetList(
			array(),
			array("ID" => $arBasketIds),
			false,
			false,
			array(
				"ID",
				"RESERVED",
			)
		);
		while ($arBasketItems = $rsBasketItems->Fetch())
		{
			$arBasketItems['ID'] = (int)$arBasketItems['ID'];
			if (!isset($basketMap[$arBasketItems['ID']]))
				continue;
			$basketMap[$arBasketItems['ID']]['RESERVED'] = $arBasketItems['RESERVED'];
		}
		unset($arBasketItems, $rsBasketItems);
	}
	unset($basketMap, $arBasketIds);

	return $arOrderProductPrice;
}

/*
 * Returns HTML for recommended product, basket product or product from the viewed list
 */
function fGetFormatedProduct($USER_ID, $LID, $arData, $currency, $type = '')
{
	global $crmMode;
	$result = "";
	$arSet = array();

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

		foreach ($arData["ITEMS"] as $item)
		{
			if (!empty($item["CURRENCY"]) && $item["CURRENCY"] != $currency)
			{
				if (floatval($item["PRICE"]) > 0)
					$item["PRICE"] = CCurrencyRates::ConvertCurrency($item["PRICE"], $item["CURRENCY"], $currency);

				if (floatval($item["DISCOUNT_PRICE"]) > 0)
					$item["DISCOUNT_PRICE"] = CCurrencyRates::ConvertCurrency($item["DISCOUNT_PRICE"], $item["CURRENCY"], $currency);

				$item["CURRENCY"] = $currency;
			}

			/** @var $productProvider IBXSaleProductProvider */
			if ($productProvider = CSaleBasket::GetProductProvider($item))
			{
				if (method_exists($productProvider, "GetSetItems"))
				{
					$itemInfo = (isset($item['ID']) ? array('BASKET_ID' => $item['ID']) : array());
					$arSets = $productProvider::GetSetItems($item["PRODUCT_ID"], CSaleBasket::TYPE_SET, $itemInfo);
					unset($itemInfo);

					if (is_array($arSets))
					{
						foreach ($arSets as $arSetData)
						{
							foreach ($arSetData["ITEMS"] as $setItem)
							{
								$setItem["FUSER_ID"] = $item["FUSER_ID"];
								$setItem["LID"] = $item["LID"];
								$setItem["MODULE"] = $item["MODULE"];
								$setItem["PRODUCT_PROVIDER_CLASS"] = $productProvider;
								$setItem["SET_PARENT_ID"] = $item["ID"];

								$arSet[$item["PRODUCT_ID"]][] = $setItem;
							}
						}
					}
				}
			}

			if ($item["MODULE"] == "catalog")
			{
				$arProductId[$item["PRODUCT_ID"]] = $item["PRODUCT_ID"];
				$arDataTab[$item["PRODUCT_ID"]] = $item;

				$arParent = CCatalogSku::GetProductInfo($item["PRODUCT_ID"]);
				if ($arParent)
				{
					$arSkuParentChildren[$item["PRODUCT_ID"]] = $arParent["ID"];
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
				$item = $arDataTab[$arProduct['ID']];

				if ($item["PREVIEW_PICTURE"] == "" && $item["DETAIL_PICTURE"] == "" && is_set($arSkuParentChildren[$item["PRODUCT_ID"]]))
				{
					$idTmp = $arSkuParentChildren[$item["PRODUCT_ID"]];
					$item["DETAIL_PICTURE"] = $arSkuParent[$idTmp]["DETAIL_PICTURE"];
					$item["PREVIEW_PICTURE"] = $arSkuParent[$idTmp]["PREVIEW_PICTURE"];
				}

				if ($item["DETAIL_PICTURE"] > 0)
					$imgCode = $item["DETAIL_PICTURE"];
				elseif ($item["PREVIEW_PICTURE"] > 0)
					$imgCode = $item["PREVIEW_PICTURE"];

				$arSkuProperty = CSaleProduct::GetProductSkuProps($item["PRODUCT_ID"], $item["IBLOCK_ID"]);

				$item["NAME"] = htmlspecialcharsex($item["NAME"]);
				$item["EDIT_PAGE_URL"] = htmlspecialcharsex($item["EDIT_PAGE_URL"]);
				$item["CURRENCY"] = htmlspecialcharsex($item["CURRENCY"]);

				if ($imgCode > 0)
				{
					$arFile = CFile::GetFileArray($imgCode);
					$arImgProduct = CFile::ResizeImageGet($arFile, array('width'=>80, 'height'=>80), BX_RESIZE_IMAGE_PROPORTIONAL, false, false);
				}

				if (is_array($arImgProduct))
				{
					$imgUrl = $arImgProduct["src"];
					$imgProduct = "<a href=\"".$item["EDIT_PAGE_URL"]."\" target=\"_blank\"><img src=\"".$imgUrl."\" alt=\"\" title=\"".$item["NAME"]."\" ></a>";
				}
				else
					$imgProduct = "<div class='no_foto'>".GetMessage('NO_FOTO')."</div>";

				$arCurFormat = CCurrencyLang::GetCurrencyFormat($item["CURRENCY"]);
				$priceValutaFormat = str_replace("#", '', $arCurFormat["FORMAT_STRING"]);

				$currentTotalPrice = ($item["PRICE"] + $item["DISCOUNT_PRICE"]);

				$discountPercent = 0;
				if ($item["DISCOUNT_PRICE"] > 0)
					$discountPercent = intval(($item["DISCOUNT_PRICE"] * 100) / $currentTotalPrice);

				$arProduct = CCatalogProduct::GetByID($item["PRODUCT_ID"]);
				$balance = floatval($arProduct["QUANTITY"]);

				$result .= "<tr id='more_".$type."_".$item["ID"]."'>
								<td class=\"tab_img\" >".$imgProduct."</td>
								<td class=\"tab_text\">
									<div class=\"order_name\"><a href=\"".$item["EDIT_PAGE_URL"]."\" target=\"_blank\" title=\"".$item["NAME"]."\">".$item["NAME"]."</a></div>
									<div class=\"order_price\">
										".GetMessage('NEWO_SUBTAB_PRICE').": <b>".SaleFormatCurrency($item["PRICE"], $currency)."</b>";

				if (!empty($arSet) && array_key_exists($arProduct["ID"], $arSet)) // show/hide set item link
				{
					$result .= '<br/>
						<div>
							<a id="set_toggle_link_b'.$arProduct["ID"].'"
								href="javascript:void(0);"
								class="dashed-link show-set-link"
								title="'.GetMessage("SOE_SHOW_SET").'"
								onclick="fToggleSetItems(\'b'.$arProduct["ID"].'\');">'.GetMessage("SOE_SHOW_SET").'</a>
						</div>';
				}

				$result .= "</div>";

				$arResult = CSaleProduct::GetProductSku($USER_ID, $LID, $item["PRODUCT_ID"], $item["NAME"], $currency, $arProduct);

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
					"PRODUCT_ADD" => GetMessage('NEWO_POPUP_TO_BASKET'),
					"PRODUCT_ORDER" => GetMessage('NEWO_POPUP_TO_ORDER'),
					"PRODUCT_NOT_ADD" => GetMessage('NEWO_POPUP_DONT_CAN_BUY'),
					"PRODUCT_PRICE_FROM" => GetMessage('NEWO_POPUP_FROM')
				);

				if (count($arResult["SKU_ELEMENTS"]) <= 0)
					$result .= "<a href=\"javascript:void(0);\" class=\"get_new_order\" onClick=\"fAddToBasketMoreProduct('".$type."', ".$item["PRODUCT_ID"].");return false;\"><span></span>".GetMessage('NEWO_SUBTAB_ADD_BASKET')."</a><br>";
				else
					$result .= "<a href=\"javascript:void(0);\" class=\"get_new_order\" onClick=\"fAddToBasketMoreProductSku(".CUtil::PhpToJsObject($arResult['SKU_ELEMENTS']).", ".CUtil::PhpToJsObject($arResult['SKU_PROPERTIES']).", 'basket', ".CUtil::PhpToJsObject($arResult["POPUP_MESSAGE"]).");\"><span></span>".GetMessage('NEWO_SUBTAB_ADD_BASKET')."</a><br>";

				if (!$crmMode)
				{
					if (count($arResult["SKU_ELEMENTS"]) > 0)
					{
						$result .= "<a href=\"javascript:void(0);\" class=\"get_new_order\" onClick=\"fAddToBasketMoreProductSku(".CUtil::PhpToJsObject($arResult['SKU_ELEMENTS']).", ".CUtil::PhpToJsObject($arResult['SKU_PROPERTIES']).", 'neworder', ".CUtil::PhpToJsObject($arResult["POPUP_MESSAGE"]).");\"><span></span>".GetMessage('NEWO_SUBTAB_ADD_ORDER')."</a>";
					}
					else
					{
						$cntProd = (floatval($item["QUANTITY"]) > 0) ? floatval($item["QUANTITY"]) : 1;
						$url = "/bitrix/admin/sale_order_new.php?lang=".LANGUAGE_ID."&user_id=".$USER_ID."&LID=".$LID."&product[".$item["PRODUCT_ID"]."]=".$cntProd;
						$result .= "<a href=\"".$url."\" target=\"_blank\" class=\"get_new_order\"><span></span>".GetMessage('NEWO_SUBTAB_ADD_ORDER')."</a>";
					}
				}

				$result .= "</td></tr>";

				// show set items
				if (!empty($arSet) && array_key_exists($arProduct["ID"], $arSet))
				{
					foreach ($arSet[$arProduct["ID"]] as $set)
					{
						$editUrl = CIBlock::GetAdminElementEditLink($set["IBLOCK_ID"], $set["ITEM_ID"], array(
							"find_section_section" => $set["IBLOCK_SECTION_ID"],
							'WF' => 'Y',
						));

						if ($set["PREVIEW_PICTURE"] > 0)
							$imgCode = $set["PREVIEW_PICTURE"];
						elseif ($set["DETAIL_PICTURE"] > 0)
							$imgCode = $set["DETAIL_PICTURE"];

						if ($imgCode > 0)
						{
							$arFile = CFile::GetFileArray($imgCode);
							$arImgProduct = CFile::ResizeImageGet($arFile, array('width'=>80, 'height'=>80), BX_RESIZE_IMAGE_PROPORTIONAL, false, false);
							if (is_array($arImgProduct))
							{
								$imgUrl = $arImgProduct["src"];
								$img = '<a href="'.$editUrl.'" target="_blank"><img src="'.$arImgProduct["src"].'" alt="" title="'.$set["NAME"].'" ></a>';
							}
						}
						else
							$img = '<div class="no_foto">'.GetMessage('SOD_NO_FOTO')."</div>";

						$result .= '
							<tr style="display:none" class="set_item_b'.$arProduct["ID"].'">
								<td class="tab_img">'.$img.'</td>
								<td class="tab_text">
									<div class="order_name">
										<a href="'.$editUrl.'" style="font-style:italic" target="_blank" title="'.$set["NAME"].'">'.$set["NAME"].'</a>
									</div>
									<div class="order_price">'.GetMessage('NEWO_SUBTAB_PRICE').': <b>'.SaleFormatCurrency($set["PRICE"], $currency).'</b></div>
								</td>
							</tr>';
					}
				}

			}//end foreach
		}
	}//end if

	if ($arData["CNT"] > 2 && $arData["CNT"] != count($arData["ITEMS"]))
	{
		$result .= "<tr><td colspan='2' align='right' class=\"more_product\">";
		if ($type == "basket")
			$result .= "<a href='javascript:void(0);' onClick='fGetMoreBasket(\"Y\");' class=\"get_more\">".GetMessage('NEWO_SUBTAB_MORE')."<span></span></a>";
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
				{
					$val["EDIT_PAGE_URL"] = CIBlock::GetAdminElementEditLink($arSection[$val["PRODUCT_ID"]]["IBLOCK_ID"], $val["PRODUCT_ID"], array(
						"find_section_section" => $arSection[$val["PRODUCT_ID"]]["IBLOCK_SECTION_ID"],
						'WF' => 'Y',
					));
				}

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

function getNameCount($propName, $propCode, $arProps)
{
	$count = 1;
	foreach ($arProps as &$arData)
	{
		if (isset($arData["NAME"]) && $arData["NAME"] == $propName && $propCode != $arData["CODE"])
			$count++;
	}
	unset($arData);
	return $count;
}

function getIblockNames($arIblockIDs, $arIblockNames)
{
	$str = '';
	foreach ($arIblockIDs as &$iblockID)
	{
		$str .= '"'.$arIblockNames[$iblockID].'", ';
	}
	unset($iblockID);
	$str .= '#';

	return str_replace(', #', '', $str);
}

function getAdditionalColumns()
{
	static $propList = null;

	if ($propList === null && Loader::includeModule('catalog'))
	{
		$arIblockIDs = array();
		$arIblockNames = array();
		$catalogIterator = Catalog\CatalogIblockTable::getList(array(
			'select' => array('IBLOCK_ID', 'NAME' => 'IBLOCK.NAME'),
			'order' => array('IBLOCK_ID' => 'ASC')
		));
		while ($catalog = $catalogIterator->fetch())
		{
			$catalog['IBLOCK_ID'] = (int)$catalog['IBLOCK_ID'];
			$arIblockIDs[] = $catalog['IBLOCK_ID'];
			$arIblockNames[$catalog['IBLOCK_ID']] = $catalog['NAME'];
		}
		unset($catalog, $catalogIterator);

		if (!empty($arIblockIDs))
		{
			$arProps = array();
			$propertyIterator = Iblock\PropertyTable::getList(array(
				'select' => array('ID', 'CODE', 'NAME', 'IBLOCK_ID'),
				'filter' => array('@IBLOCK_ID' => $arIblockIDs, '=ACTIVE' => 'Y'),
				'order' => array('IBLOCK_ID' => 'ASC', 'SORT' => 'ASC', 'ID' => 'ASC')
			));
			while ($property = $propertyIterator->fetch())
			{
				$property['ID'] = (int)$property['ID'];
				$property['IBLOCK_ID'] = (int)$property['IBLOCK_ID'];
				$property['CODE'] = (string)$property['CODE'];
				if ($property['CODE'] == '')
					$property['CODE'] = $property['ID'];
				if (!isset($arProps[$property['CODE']]))
				{
					$arProps[$property['CODE']] = array(
						'CODE' => $property['CODE'],
						'TITLE' => $property['NAME'].' ['.$property['CODE'].']',
						'ID' => array($property['ID']),
						'IBLOCK_ID' => array($property['IBLOCK_ID'] => $property['IBLOCK_ID']),
						'IBLOCK_TITLE' => array($property['IBLOCK_ID'] => $arIblockNames[$property['IBLOCK_ID']]),
						'COUNT' => 1
					);
				}
				else
				{
					$arProps[$property['CODE']]['ID'][] = $property['ID'];
					$arProps[$property['CODE']]['IBLOCK_ID'][$property['IBLOCK_ID']] = $property['IBLOCK_ID'];
					if ($arProps[$property['CODE']]['COUNT'] < 2)
						$arProps[$property['CODE']]['IBLOCK_TITLE'][$property['IBLOCK_ID']] = $arIblockNames[$property['IBLOCK_ID']];
					$arProps[$property['CODE']]['COUNT']++;
				}
			}
			unset($property, $propertyIterator, $arIblockNames, $arIblockIDs);

			$propList = array();
			foreach ($arProps as &$property)
			{
				$iblockList = '';
				if ($property['COUNT'] > 1)
				{
					$iblockList = ($property['COUNT'] > 2 ? ' ( ... )' : ' ('.implode(', ', $property['IBLOCK_TITLE']).')');
				}
				$propList['PROPERTY_'.$property['CODE']] = $property['TITLE'].$iblockList;
			}
			unset($property, $arProps);
		}
	}

	return (empty($propList) ? array() : $propList);
}

/*
 * Returns old history data records (used before august 2013) in the new format
 */
function convertHistoryToNewFormat($arFields)
{
	foreach ($arFields as $fieldname => $fieldvalue)
	{
		if (strlen($fieldvalue) > 0)
		{
			foreach (CSaleOrderChangeFormat::$operationTypes as $code => $arInfo)
			{
				if (in_array($fieldname, $arInfo["TRIGGER_FIELDS"]))
				{
					$arData = array();
					foreach ($arInfo["DATA_FIELDS"] as $field)
						$arData[$field] = $arFields["$field"];

					return array(
						"ID" => $arFields["ID"],
						"ORDER_ID" => $arFields["H_ORDER_ID"],
						"TYPE" => $code,
						"DATA" => serialize($arData),
						"DATE_CREATE" => $arFields["H_DATE_INSERT"],
						"DATE_MODIFY" => $arFields["H_DATE_INSERT"],
						"USER_ID" => $arFields["H_USER_ID"]
					);
				}
			}
		}
	}

	return false;
}

/*
 * Returns HTML to download or view file (if image) in the order_detail
 */
function showImageOrDownloadLink($fileId, $orderId = 0, $arSize = array("WIDTH" => 90, "HEIGHT" => 90))
{
	$resultHTML = "";
	$arFile = CFile::GetFileArray($fileId);

	if ($arFile)
	{
		$is_image = CFile::IsImage($arFile["FILE_NAME"], $arFile["CONTENT_TYPE"]);
		if ($is_image)
			$resultHTML = CFile::ShowImage($arFile["ID"], $arSize["WIDTH"], $arSize["HEIGHT"], "border=0", "", true);
		else
			$resultHTML = "<a href=\"sale_order_detail.php?ID=".$orderId."&download=Y&file_id=".$arFile["ID"]."&".bitrix_sessid_get()."\">".$arFile["ORIGINAL_NAME"]."</a>";
	}

	return $resultHTML;
}

function getIblockPropInfo($value, $propData, $arSize = array("WIDTH" => 90, "HEIGHT" => 90), $orderId = 0)
{
	$res = "";

	if ($propData["MULTIPLE"] == "Y")
	{
		$arVal = array();
		if (!is_array($value))
		{
			if (strpos($value, ",") !== false)
				$arVal = explode(",", $value);
			else
				$arVal[] = $value;
		}
		else
			$arVal = $value;

		if (count($arVal) > 0)
		{
			foreach ($arVal as $key => $val)
			{
				if ($propData["PROPERTY_TYPE"] == "F")
				{
					if (strlen($res) > 0)
						$res .= "<br/> ".showImageOrDownloadLink(trim($val), $orderId, $arSize);
					else
						$res = showImageOrDownloadLink(trim($val), $orderId, $arSize);
				}
				else
				{
					if (strlen($res) > 0)
						$res .= ", ".$val;
					else
						$res = $val;
				}
			}
		}
	}
	else
	{
		if ($propData["PROPERTY_TYPE"] == "F")
			$res = showImageOrDownloadLink($value, $orderId, $arSize);
		else
			$res = $value;
	}

	if (strlen($res) == 0)
		$res = "&nbsp";

	return $res;
}

/*
 * Returns HTML of columns names (<td></td>) for the basket items table
 * Used in the order_new and order_detail
 */
function getColumnsHeaders($arUserColumns, $page = "edit", $bWithStores = false)
{
	$prefix = ($page == "edit") ? "NEW_" : "SOD_";

	if ($page != "edit")
		$bWithStores = false;

	foreach ($arUserColumns as $columnCode => $columnName)
	{
		switch ($columnCode)
		{
			case "COLUMN_NUMBER":
				?>
				<td><?=GetMessage($prefix."COLUMN_NUMBER")?></td>
				<?
				break;

			case "COLUMN_NAME":
				?>
				<td><?=GetMessage($prefix."COLUMN_NAME")?></td>
				<?
				break;

			case "COLUMN_IMAGE":
				?>
				<td><?=GetMessage($prefix."COLUMN_IMAGE")?></td>
				<?
				break;

			case "COLUMN_QUANTITY":
				?>
				<td><?=GetMessage($prefix."COLUMN_QUANTITY")?></td>
				<?
				if ($bWithStores):
				?>
					<td><?=GetMessage("SALE_F_STORE")?></td>
					<td><?=GetMessage("SALE_F_STORE_CUR_AMOUNT")?></td>
					<td><?=GetMessage("SALE_F_STORE_AMOUNT")?></td>
					<td><?=GetMessage("SALE_F_STORE_BARCODE")?></td>
				<?
				endif;
				break;

			case "COLUMN_REMAINING_QUANTITY":
				?>
				<td><?=GetMessage($prefix."COLUMN_REMAINING_QUANTITY")?></td>
				<?
				break;

			case "COLUMN_PROPS":
				?>
				<td><?=GetMessage($prefix."COLUMN_PROPS")?></td>
				<?
				break;

			case "COLUMN_PRICE":
				?>
				<td><?=GetMessage($prefix."COLUMN_PRICE")?></td>
				<?
				break;

			case "COLUMN_SUM":
				?>
				<td><?=GetMessage($prefix."COLUMN_SUM")?></td>
				<?
				break;

			default:
				?>
				<td><?=$columnName?></td>
				<?
				break;
		}
	}
}

/*
 * Returns appropriate css class for the input control if barcode is valid or not
 */
function setBarcodeClass($barcodeValue)
{
	$result = "";

	if ($barcodeValue == "Y")
		$result = "store_barcode_found_input";
	elseif ($barcodeValue == "N")
		$result = "store_barcode_not_found";

	return $result;
}

/*
 * Returns array of product parameters to fill basket table row in the order_new form
 * Can be called recursively to get data about Set items
 */
function getProductDataToFillBasket($productId, $quantity, $userId, $LID, $userColumns, $tmpId = "")
{
	if (!\Bitrix\Main\Loader::includeModule("catalog"))
		return array();

	$arParams = array();

	static $proxyIblockElement = array();
	static $proxyCatalogMeasure = array();
	static $proxyParent = array();
	static $proxyIblockProperty = array();
	static $proxyProductData = array();
	static $proxyCatalogProduct = array();
	static $proxyCatalogMeasureRatio = array();

	$productId = (int)$productId;
	if ($productId <= 0)
	{
		return $arParams;
	}

	if (!empty($proxyIblockElement[$productId]))
	{
		$iblockId = $proxyIblockElement[$productId];
	}
	else
	{
		$iblockId = (int)CIBlockElement::GetIBlockByID($productId);

		if (intval($iblockId) > 0)
			$proxyIblockElement[$productId] = $iblockId;
	}

	if ($iblockId <= 0)
	{
		return $arParams;
	}

	$arSku2Parent = array();
	$arElementId = array();

	$arElementId[] = $productId;

	$proxyParentKey = $productId."|".$iblockId;

	if (!empty($proxyParent[$proxyParentKey]) && is_array($proxyParent[$proxyParentKey]))
	{
		$arParent = $proxyParent[$proxyParentKey];
	}
	else
	{
		$arParent = CCatalogSku::GetProductInfo($productId, $iblockId);
		$proxyParent[$proxyParentKey] = $arParent;
	}


	if ($arParent)
	{
		$arElementId[] = $arParent["ID"];
		$arSku2Parent[$productId] = $arParent["ID"];
	}

	$arPropertyInfo = array();
	$userColumns = (string)$userColumns;
	$arUserColumns = ($userColumns != '') ? explode(",", $userColumns) : array();
	foreach ($arUserColumns as $key => $column)
	{
		if (strncmp($column, 'PROPERTY_', 9) != 0)
		{
			unset($arUserColumns[$key]);
		}
		else
		{
			$column = strtoupper($column);
			$propertyCode = substr($column, 9);
			if ($propertyCode == '')
			{
				unset($arUserColumns[$key]);
				continue;
			}

			if (!empty($proxyIblockProperty[$propertyCode]) && is_array($proxyIblockProperty[$propertyCode]))
			{
				$arPropertyInfo[$column] = $proxyIblockProperty[$propertyCode];
			}
			else
			{
				$dbres = CIBlockProperty::GetList(array(), array("CODE" => $propertyCode));
				if ($arPropData = $dbres->GetNext())
				{
					$arPropertyInfo[$column] = $arPropData;
					$proxyIblockProperty[$propertyCode] = $arPropData;
				}
			}

		}
	}

	$arSelect = array_merge(
		array("ID", "NAME", "LID", "IBLOCK_ID", "IBLOCK_SECTION_ID", "DETAIL_PICTURE", "PREVIEW_PICTURE", "DETAIL_PAGE_URL", "XML_ID", "IBLOCK_XML_ID"),
		$arUserColumns
	);


	$proxyProductDataKey = md5(join('|', $arElementId)."_".join('|', $arSelect));
	if (!empty($proxyProductData[$proxyProductDataKey]) && is_array($proxyProductData[$proxyProductDataKey]))
	{
		$arProductData = $proxyProductData[$proxyProductDataKey];
	}
	else
	{
		$arProductData = getProductProps($arElementId, $arSelect);
		$proxyProductData[$proxyProductDataKey] = $arProductData;
	}

	$defaultMeasure = CCatalogMeasure::getDefaultMeasure(true, true);

	if (!empty($arProductData))
	{
		$arElementInfo = array();
		foreach ($arProductData as $elemId => &$arElement)
		{
			foreach ($arElement as $key => $value)
			{
				if (strncmp($key, 'PROPERTY_', 9) == 0 && substr($key, -6) == "_VALUE")
				{
					$columnCode = str_replace("_VALUE", "", $key);
					if (!isset($arPropertyInfo[$columnCode]))
						continue;
					$keyResult = 'PROPERTY_'.$arPropertyInfo[$columnCode]['CODE'].'_VALUE';
					$arElement[$key] = getIblockPropInfo($value, $arPropertyInfo[$columnCode], array("WIDTH" => 90, "HEIGHT" => 90));
					if ($keyResult != $key)
						$arElement[$keyResult] = $arElement[$key];
					unset($keyResult);
				}
			}
		}
		unset($arElement);

		if (isset($arProductData[$productId]))
			$arElementInfo = $arProductData[$productId];

		if (isset( $arSku2Parent[$productId]))
			$arParent = $arProductData[$arSku2Parent[$productId]];

		if (!empty($arSku2Parent)) // if sku element doesn't have value of some property - we'll show parent element value instead
		{
			foreach ($arUserColumns as $field)
			{
				$fieldVal = $field."_VALUE";
				$parentId = $arSku2Parent[$productId];

				if ((!isset($arElementInfo[$fieldVal]) || (isset($arElementInfo[$fieldVal]) && strlen($arElementInfo[$fieldVal]) == 0))
					&& (isset($arProductData[$parentId][$fieldVal]) && !empty($arProductData[$parentId][$fieldVal]))) // can be array or string
				{
					$arElementInfo[$fieldVal] = $arProductData[$parentId][$fieldVal];
				}
			}
			if (strpos($arElementInfo["~XML_ID"], '#') === false)
			{
				$arElementInfo["~XML_ID"] = $arParent['~XML_ID'].'#'.$arElementInfo["~XML_ID"];
			}
		}

		$arElementInfo["MODULE"] = "catalog";
		$arElementInfo["PRODUCT_PROVIDER_CLASS"] = "CCatalogProductProvider";

		$arElementInfo["PRODUCT_ID"] = $arElementInfo["ID"];

		if ($arElementInfo["IBLOCK_ID"] > 0)
		{
			$arElementInfo["EDIT_PAGE_URL"] = CIBlock::GetAdminElementEditLink($arElementInfo["IBLOCK_ID"], $arElementInfo["PRODUCT_ID"], array(
				"find_section_section" => $arElementInfo["IBLOCK_SECTION_ID"],
				'WF' => 'Y',
			));
		}

		$arBuyerGroups = CUser::GetUserGroup($userId);

		// price
		$currentVatMode = CCatalogProduct::getPriceVatIncludeMode();
		$currentUseDiscount = CCatalogProduct::getUseDiscount();
		CCatalogProduct::setUseDiscount(true);
		CCatalogProduct::setPriceVatIncludeMode(true);
		CCatalogProduct::setUsedCurrency(CSaleLang::GetLangCurrency($LID));
		$arPrice = CCatalogProduct::GetOptimalPrice($arElementInfo["ID"], 1, $arBuyerGroups, "N", array(), $LID);
		CCatalogProduct::clearUsedCurrency();
		CCatalogProduct::setPriceVatIncludeMode($currentVatMode);
		CCatalogProduct::setUseDiscount($currentUseDiscount);
		unset($currentUseDiscount, $currentVatMode);

		$currentPrice = $arPrice['RESULT_PRICE']['DISCOUNT_PRICE'];
		$arElementInfo['PRICE'] = $currentPrice;
		$arElementInfo['CURRENCY'] = $arPrice['RESULT_PRICE']['CURRENCY'];
		$arElementInfo['DISCOUNT_PRICE'] = $arPrice['RESULT_PRICE']['DISCOUNT'];
		$currentTotalPrice = $arPrice['RESULT_PRICE']['BASE_PRICE'];
		$discountPercent = (int)$arPrice['RESULT_PRICE']['PERCENT'];





		$arProduct = array();

		if (!empty($proxyCatalogProduct[$productId]) && is_array($proxyCatalogProduct[$productId]))
		{
			$arProduct = $proxyCatalogProduct[$productId];
		}
		else
		{
			$rsProducts = CCatalogProduct::GetList(
				array(),
				array('ID' => $productId),
				false,
				false,
				array('ID', 'QUANTITY', 'WEIGHT', 'MEASURE', 'TYPE', 'BARCODE_MULTI')
			);
			if ($arProduct = $rsProducts->Fetch())
			{
				$proxyCatalogProduct[$productId] = $arProduct;
			}
		}

		if (empty($arProduct) || !is_array($arProduct))
		{
			return array();
		}

		$balance = floatval($arProduct["QUANTITY"]);

		// sku props
		$arSkuData = array();
		$arProps[] = array(
			"NAME" => "Catalog XML_ID",
			"CODE" => "CATALOG.XML_ID",
			"VALUE" => $arElementInfo['~IBLOCK_XML_ID']
		);

		static $proxySkuProperty = array();

		if (!empty($proxySkuProperty[$productId]) && is_array($proxySkuProperty[$productId]))
		{
			$arSkuProperty = $proxySkuProperty[$productId];
		}
		else
		{
			$arSkuProperty = CSaleProduct::GetProductSkuProps($productId, '', true);
			$proxySkuProperty[$productId] = $arSkuProperty;
		}

		if (!empty($arSkuProperty))
		{
			foreach ($arSkuProperty as &$val)
			{
				$arSkuData[] = array(
					'NAME' => $val['NAME'],
					'VALUE' => $val['VALUE'],
					'CODE' => $val['CODE']
				);
			}
			unset($val);
		}


		$arSkuData[] = array(
			"NAME" => "Product XML_ID",
			"CODE" => "PRODUCT.XML_ID",
			"VALUE" => $arElementInfo["~XML_ID"]
		);

		// currency
		$arCurFormat = CCurrencyLang::GetCurrencyFormat($arElementInfo["CURRENCY"]);
		$priceValutaFormat = str_replace("#", "", $arCurFormat["FORMAT_STRING"]);

		$arElementInfo["WEIGHT"] = $arProduct["WEIGHT"];

		// measure
		$arElementInfo["MEASURE_TEXT"] = "";
		$arElementInfo["MEASURE_CODE"] = 0;
		if ((int)$arProduct["MEASURE"] > 0)
		{

			if (!empty($proxyCatalogMeasure[$arProduct["MEASURE"]]) && is_array($proxyCatalogMeasure[$arProduct["MEASURE"]]))
			{
				$arMeasure = $proxyCatalogMeasure[$arProduct["MEASURE"]];
			}
			else
			{
				$dbMeasure = CCatalogMeasure::GetList(array(), array("ID" => intval($arProduct["MEASURE"])), false, false, array("ID", "SYMBOL_RUS", "SYMBOL_INTL"));
				if ($arMeasure = $dbMeasure->Fetch())
				{
					$proxyCatalogMeasure[$arProduct["MEASURE"]] = $arMeasure;
				}
			}

			if (!empty($arMeasure) && is_array($arMeasure))
			{
				$arElementInfo["MEASURE_TEXT"] = ($arMeasure["SYMBOL_RUS"] != '' ? $arMeasure["SYMBOL_RUS"] : $arMeasure["SYMBOL_INTL"]);
				$arElementInfo["MEASURE_CODE"] = $arMeasure["CODE"];
			}
		}
		if ($arElementInfo["MEASURE_TEXT"] == '')
		{
			$arElementInfo["MEASURE_TEXT"] = ($defaultMeasure["SYMBOL_RUS"] != '' ? $defaultMeasure["SYMBOL_RUS"] : $defaultMeasure["SYMBOL_INTL"]);
		}


		// ratio
		$arElementInfo["RATIO"] = 1;

		if (!empty($proxyCatalogMeasureRatio[$productId]) && is_array($proxyCatalogMeasureRatio[$productId]))
		{
			$arRatio = $proxyCatalogMeasureRatio[$productId];
		}
		else
		{
			$dbratio = CCatalogMeasureRatio::GetList(array(), array("PRODUCT_ID" => $productId));
			if ($arRatio = $dbratio->Fetch())
			{
				$proxyCatalogMeasureRatio[$productId] = $arRatio;
			}

		}

		if (!empty($arRatio) && is_array($arRatio))
			$arElementInfo["RATIO"] = $arRatio["RATIO"];

		// image
		$imgCode = '';
		$imgUrl = '';
		if ($arElementInfo["PREVIEW_PICTURE"] > 0)
			$imgCode = $arElementInfo["PREVIEW_PICTURE"];
		elseif ($arElementInfo["DETAIL_PICTURE"] > 0)
			$imgCode = $arElementInfo["DETAIL_PICTURE"];

		if ($imgCode == "" && count($arParent) > 0)
		{
			if ($arParent["PREVIEW_PICTURE"] > 0)
				$imgCode = $arParent["PREVIEW_PICTURE"];
			elseif ($arParent["DETAIL_PICTURE"] > 0)
				$imgCode = $arParent["DETAIL_PICTURE"];
		}

		if ($imgCode > 0)
		{
			$arFile = CFile::GetFileArray($imgCode);
			$arImgProduct = CFile::ResizeImageGet($arFile, array('width'=>80, 'height'=>80), BX_RESIZE_IMAGE_PROPORTIONAL, false, false);
			if (is_array($arImgProduct))
				$imgUrl = $arImgProduct["src"];
		}

		$arSetInfo = array();
		$arStores = array();

		/** @var $productProvider IBXSaleProductProvider */
		if ($productProvider = CSaleBasket::GetProductProvider(array("MODULE" => $arElementInfo["MODULE"], "PRODUCT_PROVIDER_CLASS" => $arElementInfo["PRODUCT_PROVIDER_CLASS"])))
		{
			// get set items if it is set
			if ($arProduct["TYPE"] == CCatalogProduct::TYPE_SET)
			{
				if (method_exists($productProvider, "GetSetItems"))
				{
					$arSets = $productProvider::GetSetItems($productId, CSaleBasket::TYPE_SET);

					if ($tmpId == "")
						$tmpId = randString(7);

					if (!empty($arSets))
					{
						foreach ($arSets as $arSetData)
						{
							foreach ($arSetData["ITEMS"] as $setItem)
							{
								$arSetItemParams = getProductDataToFillBasket($setItem["PRODUCT_ID"], $setItem["QUANTITY"], $userId, $LID, $userColumns, $tmpId); // recursive call

								// re-define some fields with set data values
								$arSetItemParams["id"] = $setItem["PRODUCT_ID"];
								$arSetItemParams["name"] = $setItem["NAME"];
								$arSetItemParams["module"] = $setItem["MODULE"];
								$arSetItemParams["productProviderClass"] = $setItem["PRODUCT_PROVIDER_CLASS"];
								$arSetItemParams["url"] = $setItem["DETAIL_PAGE_URL"];
								$arSetItemParams["quantity"] = $setItem["QUANTITY"] * $quantity;
								$arSetItemParams["barcodeMulti"] = $setItem["BARCODE_MULTI"];
								$arSetItemParams["productType"] = $setItem["TYPE"];
								$arSetItemParams["weight"] = $setItem["WEIGHT"];
								$arSetItemParams["vatRate"] = $setItem["VAT_RATE"];
								$arSetItemParams["setItems"] = "";

								$arSetItemParams["setParentId"] = $productId."_tmp".$tmpId;
								$arSetItemParams["isSetItem"] = "Y";
								$arSetItemParams["isSetParent"] = "N";

								$arSetInfo[] = $arSetItemParams;
							}
						}
					}
				}
			}

			// get stores
			$storeCount = $productProvider::GetStoresCount(array("SITE_ID" => $LID)); // with exact SITE_ID or SITE_ID = NULL

			if ($storeCount > 0)
			{
				if ($arProductStore = $productProvider::GetProductStores(array("PRODUCT_ID" => $productId, "SITE_ID" => $LID)))
					$arStores = $arProductStore;
			}
		}

		$currentTotalPrice = (float)$currentTotalPrice;
		// params array
		$arParams["id"] = $productId;
		$arParams["name"] = $arElementInfo["~NAME"];
		$arParams["url"] = htmlspecialcharsex($arElementInfo["~DETAIL_PAGE_URL"]);
		$arParams["urlEdit"] = $arElementInfo["EDIT_PAGE_URL"];
		$arParams["urlImg"] = $imgUrl;
		$arParams["price"] = floatval($arElementInfo["PRICE"]);
		$arParams["priceBase"] = $currentTotalPrice;
		$arParams["priceBaseFormat"] = CCurrencyLang::CurrencyFormat($currentTotalPrice, $arElementInfo["CURRENCY"], false);
		$arParams["priceFormated"] = CCurrencyLang::CurrencyFormat(floatval($arElementInfo["PRICE"]), $arElementInfo["CURRENCY"], false);
		$arParams["valutaFormat"] = $priceValutaFormat;
		$arParams["dimensions"] = serialize(array("WIDTH" => $arElementInfo["WIDTH"], "HEIGHT" => $arElementInfo["HEIGHT"], "LENGTH" => $arElementInfo["LENGTH"]));
		$arParams["priceDiscount"] = floatval($arElementInfo["DISCOUNT_PRICE"]);
		$arParams["priceTotalFormated"] = CCurrencyLang::CurrencyFormat($currentTotalPrice, $arElementInfo["CURRENCY"], true);
		$arParams["discountPercent"] = $discountPercent;
		$arParams["summaFormated"] = CCurrencyLang::CurrencyFormat($arElementInfo["PRICE"], $arElementInfo["CURRENCY"], false);
		$arParams["quantity"] = $quantity;
		$arParams["module"] = $arElementInfo["MODULE"];
		$arParams["currency"] = $arElementInfo["CURRENCY"];
		$arParams["weight"] = $arElementInfo["WEIGHT"];
		$arParams["vatRate"] = $arPrice["PRICE"]["VAT_RATE"];
		$arParams["priceType"] = $arPrice["PRICE"]["CATALOG_GROUP_NAME"];
		$arParams["balance"] = $balance;
		$arParams["notes"] = (!empty($arPrice["PRICE"]["CATALOG_GROUP_NAME"]) ? $arPrice["PRICE"]["CATALOG_GROUP_NAME"] : "");
		$arParams["catalogXmlID"] = $arElementInfo["~IBLOCK_XML_ID"];
		$arParams["productXmlID"] = $arElementInfo["~XML_ID"];
		$arParams["callback"] = "";
		$arParams["orderCallback"] = "";
		$arParams["cancelCallback"] = "";
		$arParams["payCallback"] = "";
		$arParams["productProviderClass"] = $arElementInfo["PRODUCT_PROVIDER_CLASS"];
		$arParams["skuProps"] = $arSkuData;
		$arParams["measureText"] = $arElementInfo["MEASURE_TEXT"];
		$arParams["measureCode"] = $arElementInfo["MEASURE_CODE"];
		$arParams["ratio"] = $arElementInfo["RATIO"];
		$arParams["barcodeMulti"] = $arProduct["BARCODE_MULTI"];

		$arParams["productType"] = empty($arSetInfo) ? "" : CSaleBasket::TYPE_SET;
		$arParams["setParentId"] = empty($arSetInfo) ? "" : $productId."_tmp".$tmpId;

		$arParams["setItems"] = $arSetInfo;
		$arParams["isSetItem"] = "N";
		$arParams["isSetParent"] = empty($arSetInfo) ? "N" : "Y";

		$arParams["stores"] = empty($arSetInfo) ? $arStores : array();
		$arParams["productPropsValues"] = $arElementInfo; // along with other information also contains values of properties with correct keys (after getProductProps)
	}

	return $arParams;
}

?>