<?
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/learning/general/ratings_components.php");

// 2012-04-16 Checked/modified for compatibility with new data model
class CRatingsComponentsLearning
{	
	// 2012-04-16 Checked/modified for compatibility with new data model
	public static function OnGetRatingContentOwner($arParams)
	{
		if ($arParams['ENTITY_TYPE_ID'] == 'LEARN_LESSON')
		{
			$arFilter = Array(
				'LESSON_ID' => intval($arParams['ENTITY_ID']),
			);
			$Item = CLearnLesson::GetList($arOrder = array(), $arFilter);
			if($arItem = $Item->Fetch())	
				return $arItem['CREATED_BY'];
			else
				return 0;
		}
		return false;
	}
	
	// 2012-04-16 Checked/modified for compatibility with new data model
	public static function OnAddRatingVote($id, $arParams)
	{
		if (in_array($arParams['ENTITY_TYPE_ID'], Array('LEARN_CHAPTER', 'LEARN_LESSON', 'LEARN_COURSE')))
		{
			global $CACHE_MANAGER;
			$CACHE_MANAGER->ClearByTag($arParams['ENTITY_TYPE_ID'].'_'.intval($arParams['ENTITY_ID']));
		
			return true;
		}
		return false;
	}
	
	// 2012-04-16 Checked/modified for compatibility with new data model
	public static function OnCancelRatingVote($id, $arParams)
	{	
		return CRatingsComponentsLearning::OnAddRatingVote($id, $arParams);
	}
}
