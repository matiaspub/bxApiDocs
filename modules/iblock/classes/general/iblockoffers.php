<?
class CAllIBlockOffersTmp
{
	public static function Add($intProductIBlockID,$intOffersIBlockID)
	{
		global $DB;

		$intProductIBlockID = (int)$intProductIBlockID;
		$intOffersIBlockID = (int)$intOffersIBlockID;
		if ($intProductIBlockID <= 0 || $intOffersIBlockID <= 0)
			return false;
		$arFields = array(
			'PRODUCT_IBLOCK_ID' => $intProductIBlockID,
			'OFFERS_IBLOCK_ID' => $intOffersIBlockID,
		);
		return $DB->Add("b_iblock_offers_tmp", $arFields);
	}

	public static function Delete($ID)
	{
		global $DB;

		$ID = (int)$ID;
		if ($ID <= 0)
			return false;
		if(!$DB->Query("DELETE FROM b_iblock_offers_tmp WHERE ID=".$ID))
			return false;
		return true;
	}

	public static function GetOldID($intProductIBlockID,$intOffersIBlockID)
	{
		return false;
	}

	public static function DeleteOldID($intProductIBlockID,$intOffersIBlockID,$intInterval = 86400)
	{
		return true;
	}
}