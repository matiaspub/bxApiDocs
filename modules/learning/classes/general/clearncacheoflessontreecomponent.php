<?php

class CLearnCacheOfLessonTreeComponent
{
	const OPTION_NAME = '~CacheOfLessonTreeComponentIsDirty';
	const OPTION_TS   = '~CacheOfLessonTreeComponentTS';	// timestamp of last ditry cache
	const CACHE_PATH  = '/learning/LessonTreeComponent/';
	const TTL         = 3600;

	public static function IsDirty()
	{
		$isCacheDirty = COption::GetOptionInt(
			'learning', 
			self::OPTION_NAME, 
			1);

		return ( (boolean) $isCacheDirty );
	}


	public static function MarkAsDirty()
	{
		COption::SetOptionString(
			'learning', 
			self::OPTION_NAME, 
			1
		);

		COption::SetOptionString(
			'learning', 
			self::OPTION_TS, 
			time()
		);
	}


	public static function MarkAsClean()
	{
		COption::SetOptionString(
			'learning', 
			self::OPTION_NAME, 
			0
		);
	}


	public static function Purge()
	{
		$oCache = new CPHPCache();
		$oCache->CleanDir(self::CACHE_PATH);
	}


	public static function GetData($courseId)
	{
		$arContents = array();
		$cacheId = 'course_id_' . (string) ((int) $courseId);
		$oCache = new CPHPCache();

		if (
			$oCache->InitCache(self::TTL, $cacheId, self::CACHE_PATH)
			&& ( ! self::IsDirty() )
		)
		{
			$arCached = $oCache->GetVars();
			if (isset($arCached['arContents']) && is_array($arCached['arContents']))
				$arContents = $arCached['arContents'];
		}
		else
		{
			self::Purge();

			$arContents = self::GetDataWoCache($courseId);
			$oCache->StartDataCache(self::TTL, $cacheId, self::CACHE_PATH);
			$oCache->EndDataCache(array('arContents' => $arContents));

			self::MarkAsClean();
		}

		return ($arContents);
	}


	protected static function GetDataWoCache($courseId)
	{
		$rsContent = CCourse::GetCourseContent(
			$courseId, 
			array(), 
			array('LESSON_ID', 'NAME')
			);

		$arContents = array();
		while ($arContent = $rsContent->GetNext())
			$arContents[] = $arContent;

		return ($arContents);
	}
}
