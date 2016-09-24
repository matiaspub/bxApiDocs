<?
IncludeModuleLangFile(__FILE__);

/**
 * CSaleDeliveryHelper
 * Make some routines for delivery handlers
 */
class CSaleDeliveryHelper
{
	/**
	 * getRegionsList
	 * @return array regions (locations) list
	 */
	public static function getRegionsList($countryId = 0, $bFlip = false)
	{
		static $arRegions = array();
		$flipIndex = intval($bFlip);

		if(isset($arRegions[$countryId][$flipIndex]))
			return $arRegions[$countryId][$flipIndex];

		if(CSaleLocation::isLocationProMigrated())
		{
			$types = array();
			$res = \Bitrix\Sale\Location\TypeTable::getList(array(
				'select' => array('ID', 'CODE')
			));
			while($item = $res->fetch())
				$types[$item['CODE']] = $item['ID'];

			$filter = array(
				array(
					'LOGIC' => 'OR',
					array(
						'=TYPE_ID' => $types['CITY'], 
						'=NAME.LANGUAGE_ID' => LANGUAGE_ID,
						array(
							'LOGIC' => 'OR',
							array(
								'=PARENT.TYPE_ID' => $types['COUNTRY']
							),
							array(
								'=PARENT.TYPE_ID' => $types['COUNTRY_DISTRICT']
							),
							array(
								'=PARENT_ID' => '0'
							)
						)
					),
					array(
						'=TYPE_ID' => $types['REGION'],
					)
				)
			);

			if(intval($countryId))
			{
				$filter['=PARENTS.TYPE_ID'] = $types['COUNTRY'];
				$filter['=PARENTS.ID'] = $countryId;
			}

			$dbRegionList = \Bitrix\Sale\Location\LocationTable::getList(array(
				'filter' => $filter,
				'select' => array('ID', 'CODE', 'NAME_LANG' => 'NAME.NAME'),
				'order' => array('NAME.NAME' => 'asc')
			));
		}
		else
		{
			$arFilterRegion = array();
			if (intval($countryId) > 0)
				$arFilterRegion["COUNTRY_ID"] = $countryId;

			$dbRegionList = CSaleLocation::GetRegionList(array("NAME_LANG"=>"ASC"), $arFilterRegion, LANGUAGE_ID);
		}

		$key = 'ID';

		while ($arRegionList = $dbRegionList->Fetch())
		{
			if($key == 'ID' && isset($arRegionList['CODE']))
			{
				$key = 'CODE';
			}

			if($key == 'CODE' && strlen($arRegionList['CODE']) <= 0)
			{
				continue;
			}

			$arRegions[$countryId][0][$arRegionList[$key]] = $arRegionList["NAME_LANG"]; // $bFlip == false
			$arRegions[$countryId][1][$arRegionList["NAME_LANG"]] = $arRegionList[$key]; // $bFlip == true
		}

		return isset($arRegions[$countryId][$flipIndex]) ? $arRegions[$countryId][$flipIndex] : array();
	}

	public static function getDeliverySIDAndProfile($deliveryId)
	{
		$arResult = array();

		$dId = $dpId = false;

		if (strpos($deliveryId, ":") !== false)
		{
			$arId = explode(":", $deliveryId);
			$dId = $arId[0];
			$dpId = $arId[1];
		}
		else
		{
			$dId = $deliveryId;
		}

		$arResult["SID"] = $dId;

		if($dpId !== false)
			$arResult["PROFILE"] = $dpId;

		return $arResult;
	}

	public static function getConfValue($arField)
	{
		$retVal = false;

		if(isset($arField['VALUE']))
			$retVal = $arField['VALUE'];
		elseif(isset($arField['DEFAULT']))
			$retVal = $arField['DEFAULT'];

		return $retVal;
	}

	public static function getMaxDimensions($arDim1, $arDim2)
	{
		if(!is_array($arDim1) && !is_array($arDim2))
			return array();

		$dimCount = 3; //width, height, length
		$arResult = array();

		rsort($arDim1, SORT_NUMERIC);
		rsort($arDim2, SORT_NUMERIC);

		for ($i=0; $i<$dimCount; $i++)
		{
			if(!isset($arDim1[$i]))
				$arDim1[$i] = 0;

			if(!isset($arDim2[$i]))
				$arDim2[$i] = 0;

			if(floatval($arDim1[$i]) > floatval($arDim2[$i]))
				$arResult[$i] = $arDim1[$i];
			else
				$arResult[$i] = $arDim2[$i];
		}

		return $arResult;
	}

	/**
	 * Divides order items to packages.
	 * @link http://en.wikipedia.org/wiki/Bin_packing_problem Bin packing problem	 *
	 * @param array $arItems Array of order's good's demensions
	 * 										array(
	 * 											array("WIDTH"=>100, "HEIGHT"=>95, "LENGTH"=>80),
	 * 											array("WIDTH"=>150, "HEIGHT"=>15, "LENGTH"=>800),
	 * 											...
	 * 										)	 *
	 * @param array $arPacks array(
	 * 							array("425", "265", "380")
	 *							);
	 * @param int max weight
	 * @param float $reservedSpace
	 * @return array pack params
	 */
	public static function getRequiredPacks(&$arItems, $arPacks, $maxWeight, $reservedSpace = 0.15)
	{
		$packCount = 1;
		$packVolume = 0;

		reset($arPacks);
		$FIRST_PACK = key($arPacks);

		$P_WIDTH_IDX = 0;
		$P_HEIGHT_IDX = 1;
		$P_LENGTH_IDX = 2;

		$arResultPacksParams  = array();

		if(
			isset($arPacks[$FIRST_PACK]['DIMENSIONS'][$P_WIDTH_IDX])
			&&
			isset($arPacks[$FIRST_PACK]['DIMENSIONS'][$P_HEIGHT_IDX])
			&&
			isset($arPacks[$FIRST_PACK]['DIMENSIONS'][$P_LENGTH_IDX]))
		{
			$packVolume = $arPacks[$FIRST_PACK]['DIMENSIONS'][$P_WIDTH_IDX]*$arPacks[$FIRST_PACK]['DIMENSIONS'][$P_HEIGHT_IDX]*$arPacks[$FIRST_PACK]['DIMENSIONS'][$P_LENGTH_IDX];
		}

		if(is_array($arItems))
		{
			$arTmpItems = array();

			foreach ($arItems as $itemId => $item)
			{
				$arTmpItems[$item["PRODUCT_ID"]]["VOLUME"] = self::calcItemVolume($item);
				$arTmpItems[$item["PRODUCT_ID"]]["WEIGHT"] = $item["WEIGHT"];
				$arTmpItems[$item["PRODUCT_ID"]]["PRICE"] = $item["PRICE"];

				// set items parameters
				$arTmpItems[$item["PRODUCT_ID"]]["SET_PARENT_ID"] = $item["SET_PARENT_ID"];
				$arTmpItems[$item["PRODUCT_ID"]]["TYPE"] = $item["TYPE"];

				if($item["QUANTITY"] > 1)
				{
					for ($i=$item["QUANTITY"]; $i > 1 ; $i--)
					{
						$arTmpItems[$item["PRODUCT_ID"]."_".$i] = $arTmpItems[$item["PRODUCT_ID"]];
					}
				}
			}

			// calculate set parent total volume
			foreach ($arTmpItems as $id => $item)
			{
				if (CSaleBasketHelper::isSetItem($item))
					$arTmpItems[$item["SET_PARENT_ID"]]["VOLUME"] += $item["VOLUME"];
			}

			// remove set items params
			foreach ($arTmpItems as $id => $item)
			{
				if (CSaleBasketHelper::isSetItem($item))
					unset($arTmpItems[$id]);
			}

			if(!empty($arTmpItems))
			{
				sortByColumn($arTmpItems, array(
											"VOLUME" => array(SORT_NUMERIC, SORT_DESC),
											"WEIGHT" => array(SORT_NUMERIC, SORT_DESC),
											)
				);

				$tmpPackageVolume = 0;
				$tmpPackageWeight = 0;
				$tmpPackagePrice = 0;
				foreach ($arTmpItems as $arItem)
				{
					if(
						(
							$packVolume > 0
							&&
							$arItem["VOLUME"] > $packVolume
						)
						||
						(
							$maxWeight > 0
							&&
							$arItem["WEIGHT"] > $maxWeight
						)
					)
					{
						return array();
					}

					$correctCoeff = $tmpPackageVolume > 0 ? (1 - $reservedSpace) : 1;

					if(
						(
							$packVolume <= 0
							||
							$tmpPackageVolume + $arItem["VOLUME"] <= $packVolume*$correctCoeff
						)
						&&
						(
							$maxWeight <= 0
							||
							$tmpPackageWeight + $arItem["WEIGHT"] <= $maxWeight
						)
					)
					{
						$tmpPackageVolume += $arItem["VOLUME"];
						$tmpPackageWeight += $arItem["WEIGHT"];
						$tmpPackagePrice += $arItem["PRICE"];
					}
					else
					{
						$arResultPacksParams[$packCount-1] = array();
						$arResultPacksParams[$packCount-1]["VOLUME"] = $packVolume;
						$arResultPacksParams[$packCount-1]["WEIGHT"] = $tmpPackageWeight;
						$arResultPacksParams[$packCount-1]["PRICE"] = $tmpPackagePrice;

						$tmpPackageVolume = $arItem["VOLUME"];
						$tmpPackageWeight = $arItem["WEIGHT"];
						$tmpPackagePrice = $arItem["PRICE"];
						$packCount += 1;
					}
				}


				$arResultPacksParams[$packCount-1] = array();
				$arResultPacksParams[$packCount-1]["VOLUME"] = $packVolume;
				$arResultPacksParams[$packCount-1]["WEIGHT"] = $tmpPackageWeight;
				$arResultPacksParams[$packCount-1]["PRICE"] = $tmpPackagePrice;
				$arResultPacksParams[$packCount-1]["DIMENSIONS"] = array(
					"WIDTH" => $arPacks[$FIRST_PACK]['DIMENSIONS'][$P_WIDTH_IDX],
					"HEIGHT" => $arPacks[$FIRST_PACK]['DIMENSIONS'][$P_HEIGHT_IDX],
					"LENGTH" => $arPacks[$FIRST_PACK]['DIMENSIONS'][$P_LENGTH_IDX]
				);

			}
		}

		return $arResultPacksParams;
	}

	public static function 	makeBoxConfig($boxId, $arBox, $group, &$arConfig)
	{
		if(
			!isset($arBox['NAME'])
			||
			!isset($arBox['DIMENSIONS'])
			||
			count($arBox['DIMENSIONS']) < 3
			||
			!is_array($arConfig)
		)
		{
			return false;
		}

		$arConfig['CONFIG']['box_section_'.$group] = array(
					'TYPE' => 'SECTION',
					'TITLE' => GetMessage('SALE_DHLP_AVIABLE_BOXES'),
					'GROUP' => $group
		);

		$arConfig['CONFIG']['BOX_AV_CONTROL_'.$group.'_'.$boxId] = array(
					'TYPE' => 'MULTI_CONTROL_STRING',
					'MCS_ID' => 'BOX_'.$group.'_'.$boxId,
					'TITLE' => $arBox['NAME'],
					'GROUP' => $group
		);

		$arConfig['CONFIG']['BOX_AV_LENGTH_'.$group.'_'.$boxId] = array(
					'TYPE' => 'STRING',
					'MCS_ID' => 'BOX_'.$group.'_'.$boxId,
					'POST_TEXT' => '&nbsp;',
					'SIZE' => 8,
					'DEFAULT' => $arBox['DIMENSIONS'][0],
					'GROUP' => $group,
					'CHECK_FORMAT' => 'NUMBER'
		);

		$arConfig['CONFIG']['BOX_AV_WIDTH_'.$group.'_'.$boxId] = array(
					'TYPE' => 'STRING',
					'MCS_ID' => 'BOX_'.$group.'_'.$boxId,
					'POST_TEXT' => '&nbsp;',
					'SIZE' => 8,
					'DEFAULT' => $arBox['DIMENSIONS'][1],
					'GROUP' => $group,
					'CHECK_FORMAT' => 'NUMBER'
		);
		$arConfig['CONFIG']['BOX_AV_HEIGHT_'.$group.'_'.$boxId] = array(
					'TYPE' => 'STRING',
					'MCS_ID' => 'BOX_'.$group.'_'.$boxId,
					'SIZE' => 8,
					'DEFAULT' => $arBox['DIMENSIONS'][2],
					'GROUP' => $group,
					'CHECK_FORMAT' => 'NUMBER'
		);
	}

	public static function getBoxesFromConfig($profile, &$arConfig)
	{
		$arBoxes = array();

		if(is_array($arConfig) && strlen($profile) > 0)
		{
			foreach ($arConfig as $key => $value)
			{
				if($profile != $value['GROUP'])
					continue;

				if(!isset($value['MCS_ID']))
					continue;

				$boxId = substr($value['MCS_ID'], 4);
				$subKey = substr($key, 0, 8);

				if($subKey == 'BOX_AV_C')
					$arBoxes[$boxId]['NAME'] = $value['TITLE'];

				if($subKey == 'BOX_AV_L')
					$arBoxes[$boxId]['DIMENSIONS'][0] = self::getConfValue($value);

				if($subKey == 'BOX_AV_W')
					$arBoxes[$boxId]['DIMENSIONS'][1] = self::getConfValue($value);

				if($subKey == 'BOX_AV_H')
					$arBoxes[$boxId]['DIMENSIONS'][2] = self::getConfValue($value);
			}
		}

		return $arBoxes;
	}

	public static function calcItemVolume($item)
	{
		$volume = 0;

		if(is_array($item))
		{
			$width = $height = $length = 0;

			if(isset($item["DIMENSIONS"]["WIDTH"]))
				$width = floatval($item["DIMENSIONS"]["WIDTH"]);

			if(isset($item["DIMENSIONS"]["HEIGHT"]))
				$height = floatval($item["DIMENSIONS"]["HEIGHT"]);

			if(isset($item["DIMENSIONS"]["LENGTH"]))
				$length = floatval($item["DIMENSIONS"]["LENGTH"]);

			$volume = $width*$height*$length;

			if($volume < 0)
				$volume = 0;
		}

		return $volume;
	}

	public static function getFormatError($value, $formatName, $name)
	{
		static $locale = array();

		if(empty($locale))
			$locale = localeconv();

		$pattern = $error = $result = null;

		if($formatName == 'NUMBER')
		{
			$pattern = '#[^0-9\\';
			$pattern .= $locale["decimal_point"] == '.' ? '\.' : $locale["decimal_point"];
			$pattern .= $locale["thousands_sep"] == ' ' ? '\s' : $locale["thousands_sep"];
			$pattern .= ']#';
			$error = GetMessage("SALE_DHLP_FIELD")." \"".$name.
					"\" ".GetMessage("SALE_DHLP_CONTAIN")." \"".$locale["decimal_point"]."\"";

			if(strlen($locale["thousands_sep"]) > 0)
				$error .= " ".GetMessage("SALE_DHLP_SEPARATOR")." \"".$locale["thousands_sep"]."\"";
			$error .= "<br>\n";
		}

		if(!is_null($pattern) && !is_null($error))
		{
			if(preg_match($pattern, $value))
				$result = $error;
			else
				$result = null;
		}

		return $result;
	}

	public static function execHandlerAction($orderId, $actionId)
	{
		global $DB, $USER;
		$arResult = array();
		$arOrder = CSaleOrder::GetById($orderId);

		$handlerActions = CSaleDeliveryHandler::getActionsList($arOrder["DELIVERY_ID"]);

		if(!array_key_exists($actionId, $handlerActions))
		{
			$arResult = array(
				"RESULT" => "ERROR",
				"TEXT" => GetMessage("SALE_DHLP_HANDLER_HAS_NO_ACTION")
			);
		}

		$dt = new \Bitrix\Main\Type\DateTime();
		$depList = \Bitrix\Sale\Internals\OrderDeliveryReqTable::getList(array(
			'filter'=>array('=ORDER_ID' => $orderId),
		));

		$dep = $depList->fetch();

		if($dep && !is_null($dep["DATE_REQUEST"]))
		{
			$arResult = array(
				"RESULT" => "ERROR",
				"TEXT" => GetMessage("SALE_DHLP_HANDLER_REQUEST_ALREADY_SENT")
			);
		}

		if(empty($arResult))
		{
			$arUserGroups = $USER->GetUserGroupArray();
			$arOrder["ITEMS"] = array();

			$dbItemsList = CSaleBasket::GetList(
				array("SET_PARENT_ID" => "DESC", "TYPE" => "DESC", "NAME" => "ASC"),
				array("ORDER_ID" => $orderId)
			);

			while ($arItem = $dbItemsList->GetNext())
			{
				$arItem["DIMENSIONS"] = unserialize($arItem["~DIMENSIONS"]);
				unset($arItem["~DIMENSIONS"]);
				$arOrder["ITEMS"][] = $arItem;
			}

			$arResult = CSaleDeliveryHandler::executeAction($arOrder["DELIVERY_ID"], $actionId, $arOrder);

			if($actionId == "REQUEST_SELF" && isset($arResult["TRACKING_NUMBER"]))
			{
				$bUserCanEditOrder = CSaleOrder::CanUserUpdateOrder($orderId, $arUserGroups);

				if ($bUserCanEditOrder)
				{
					if($dep)
					{
						\Bitrix\Sale\Internals\OrderDeliveryReqTable::update(
							$dep["ID"],
							array(
								"DATE_REQUEST" => $dt
							)
						);
					}
					else
					{
						\Bitrix\Sale\Internals\OrderDeliveryReqTable::add(
							array(
								"ORDER_ID" => $orderId,
								"DATE_REQUEST" => $dt
							)
						);
					}

					$fields = array();

					if(isset($arResult["TRACKING_NUMBER"]))
						$fields["TRACKING_NUMBER"] =  $arResult["TRACKING_NUMBER"];

					if(isset($arResult["DELIVERY_DOC_NUM"]))
					{
						$fields["DELIVERY_DOC_NUM"] =  $arResult["DELIVERY_DOC_NUM"];
						$fields["DELIVERY_DOC_DATE"] =  Date($DB->DateFormatToPHP(CLang::GetDateFormat("SHORT", $arOrder["LID"])));
					}

					CSaleOrder::Update($orderId, $fields);
				}
			}

			CSaleOrderChange::AddRecord($orderId, "ORDER_DELIVERY_REQUEST_SENT", $arResult);
		}

		return $arResult;
	}
}
?>