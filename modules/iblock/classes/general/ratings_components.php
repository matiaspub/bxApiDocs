<?
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/iblock/general/ratings_components.php");

class CRatingsComponentsIBlock
{	
	function OnGetRatingContentOwner($arParams)
	{
		if ($arParams['ENTITY_TYPE_ID'] == 'IBLOCK_ELEMENT')
		{
			$arItem = CIBlockElement::GetList(array(), Array('ID' => intval($arParams['ENTITY_ID'])), false, false, array('CREATED_BY'));
			if($ar = $arItem->Fetch())	
				return $ar['CREATED_BY'];
			else
				return 0;
		}

		return false;
	}
}

?>