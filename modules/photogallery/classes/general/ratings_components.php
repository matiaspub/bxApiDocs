<?
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/photogallery/general/ratings_components.php");

class CRatingsComponentsPhotogallery
{
	public static function BeforeIndex($arParams)
	{
		if (($arParams['MODULE_ID'] == 'iblock' || $arParams['MODULE_ID'] == 'socialnetwork') && $arParams['PARAM1'] == 'photos' && intval($arParams['PARAM2']) > 0 && intval($arParams['ITEM_ID']) > 0)
		{
			$arParams['ENTITY_TYPE_ID'] = 'IBLOCK_ELEMENT';
			$arParams['ENTITY_ID'] = intval($arParams['ITEM_ID']);

			return $arParams;
		}
	}
}

?>