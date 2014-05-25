<?
class CSaleBasketHelper
{
	/*
	* Checks if basket item belongs to set (is parent or item of the set)
	*
	* @param $arItem - basket item fields array with SET_PARENT_ID and TYPE fields
	* @return bool
	*/
	public static function isInSet($arItem)
	{
		if (!empty($arItem["SET_PARENT_ID"]))
			return true;

		return false;
	}

	/*
	* Checks if basket item is set item (belongs to set, but not parent)
	*
	* @param $arItem - basket item fields array with SET_PARENT_ID and TYPE fields
	* @return bool
	*/
	public static function isSetItem($arItem)
	{
		if (isset($arItem["SET_PARENT_ID"]) && intval($arItem["SET_PARENT_ID"]) > 0 && empty($arItem["TYPE"]))
			return true;

		return false;
	}

	/*
	* Checks if basket item is parent of the set
	*
	* @param $arItem - basket item fields array with SET_PARENT_ID and TYPE fields
	* @return bool
	*/
	public static function isSetParent($arItem)
	{
		if (isset($arItem["SET_PARENT_ID"]) && intval($arItem["SET_PARENT_ID"]) > 0 && isset($arItem["TYPE"]) && intval($arItem["TYPE"]) == CSaleBasket::TYPE_SET)
			return true;

		return false;
	}

	/*
	* Checks if ALL set items are deducted (to update DEDUCTED = Y of the set parent)
	*
	* @param int $setParentID - set parent id
	* @return bool
	*/
	public static function isSetDeducted($setParentID)
	{
		global $DB;

		$setParentID = intval($setParentID);
		$bItemFound = false;

		if ($setParentID <= 0)
			return false;

		$dbres = $DB->Query("SELECT ID, DEDUCTED FROM b_sale_basket WHERE SET_PARENT_ID = ".$setParentID." AND (TYPE IS NULL OR TYPE = '0')", true);

		while ($arItem = $dbres->GetNext())
		{
			$bItemFound = true;
			if ($arItem["DEDUCTED"] == "N")
				return false;
		}

		if ($bItemFound)
			return true;
		else
			return false;
	}
	
	/**
	 * Helper method. Is used to re-sort basket items data so Set parents will be added before Set items
	 * @param $arBasketItemA
	 * @param $arBasketItemB
	 * @return int
	 */
	public static function cmpSetData($arBasketItemA, $arBasketItemB)
	{
		if ($arBasketItemA["SET_PARENT_ID"] == "")
			return 0;

		if ($arBasketItemA["TYPE"] == CSaleBasket::TYPE_SET)
			return -1;
		else
			return 1;
	}

	public static function filterFields($field)
	{
		if ($field === false || $field === null)
		{
			return false;
		}

		return true;
	}

}
