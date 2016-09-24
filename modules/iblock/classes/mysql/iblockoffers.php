<?
class CIBlockOffersTmp extends CAllIBlockOffersTmp
{
	public static function GetOldID($intProductIBlockID, $intOffersIBlockID, $intInterval = 1800)
	{
		global $DB;

		$intProductIBlockID = (int)$intProductIBlockID;
		$intOffersIBlockID = (int)$intOffersIBlockID;
		$intInterval = (int)$intInterval;

		if ($intProductIBlockID <= 0 || $intOffersIBlockID <= 0)
			return false;

		if ($intInterval <= 0)
			$intInterval = 1800;

		$strQuery = '
			select ID
			from b_iblock_offers_tmp
			where PRODUCT_IBLOCK_ID = '.$intProductIBlockID.'
			and OFFERS_IBLOCK_ID = '.$intOffersIBlockID.'
			and TIMESTAMP_X < (NOW()-'.$intInterval.')
		';
		return $DB->Query($strQuery);
	}

	public static function DeleteOldID($intProductIBlockID, $intOffersIBlockID = 0, $intInterval = 86400)
	{
		global $DB;

		$intProductIBlockID = (int)$intProductIBlockID;
		$intOffersIBlockID = (int)$intOffersIBlockID;
		$intInterval = (int)$intInterval;

		if ($intProductIBlockID <= 0)
			return false;

		if ($intInterval <= 0)
			$intInterval = 86400;

		$strQuery = '
			delete from b_iblock_offers_tmp
			where PRODUCT_IBLOCK_ID = '.$intProductIBlockID.'
			'.(0 < $intOffersIBlockID ? 'and OFFERS_IBLOCK_ID = '.$intOffersIBlockID : '').'
			and TIMESTAMP_X < (NOW()-'.$intInterval.')
		';
		return is_object($DB->Query($strQuery));
	}
}