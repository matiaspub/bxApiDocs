<?
class CIBlockOffersTmp extends CAllIBlockOffersTmp
{
	public static function GetOldID($intProductIBlockID,$intOffersIBlockID,$intInterval = 1800)
	{
		global $DB;

		$intProductIBlockID = intval($intProductIBlockID);
		$intOffersIBlockID = intval($intOffersIBlockID);
		if ((0 >= $intProductIBlockID) || (0 >= $intOffersIBlockID))
			return false;
		$intInterval = intval($intInterval);
		if (0 >= $intInterval)
			$intInterval = 1800;
		$strQuery = 'select ID from b_iblock_offers_tmp where PRODUCT_IBLOCK_ID='.$intProductIBlockID.' and OFFERS_IBLOCK_ID='.$intOffersIBlockID.' and TIMESTAMP_X < (NOW()-'.$intInterval.')';
		return $DB->Query($strQuery);
	}

	public static function DeleteOldID($intProductIBlockID,$intOffersIBlockID,$intInterval = 86400)
	{
		global $DB;

		$intProductIBlockID = intval($intProductIBlockID);
		$intOffersIBlockID = intval($intOffersIBlockID);
		if (0 >= $intProductIBlockID)
			return false;
		$intInterval = intval($intInterval);
		if (0 >= $intInterval)
			$intInterval = 86400;
		$strQuery = 'delete from b_iblock_offers_tmp where PRODUCT_IBLOCK_ID='.$intProductIBlockID.(0 < $intOffersIBlockID ? ' and OFFERS_IBLOCK_ID='.$intOffersIBlockID : '').' and TIMESTAMP_X < (NOW()-'.$intInterval.')';
		if (!$DB->Query($strQuery, false, $err_mess.__LINE__))
		{
			return false;
		}
		return true;
	}
}
?>