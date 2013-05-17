<?
class CSeoKeywords
{ 
	public static function GetList($arOrder, $arFilter)
	{
		global $DB;
		
		$arAllFields = array('ID', 'URL', 'SITE_ID', 'KEYWORDS');
		
		$arWhere = array();
		
		foreach ($arFilter as $key => $value)
		{
			if (in_array($key, $arAllFields))
			{
				switch ($key)
				{
					case 'ID':
						$arWhere[] = 'ID=\''.intval($value).'\'';
					break;
					
					case 'SITE_ID':
						$arWhere[] = 'SITE_ID=\''.$DB->ForSql($value).'\'';
					break;
					
					case 'URL':
						if (array_key_exists('URL_EXACT_MATCH', $arFilter) && $arFilter['URL_EXACT_MATCH'] == 'N')
							$arWhere[] = 'URL LIKE \''.$DB->ForSql($value).'%\'';
						else
							$arWhere[] = 'URL=\''.$DB->ForSql($value).'\'';
					break;
					
					case 'KEYWORDS':
						$arWhere[] = 'KEYWORDS LIKE \'%'.$DB->ForSql($value).'%\'';
					break;
				}
			}
		}
		
		$strWhere = '';
		if (count($arWhere) > 0)
			$strWhere = 'WHERE '.implode(' AND ', $arWhere);
		
		$strOrder = '';
		foreach ($arOrder as $key => $dir)
		{
			$dir = ToUpper($dir);
			$key = ToUpper($key);
			
			if ($dir != 'DESC') $dir = 'ASC';
		
			if (in_array($key, $arAllFields))
			{
				$strOrder .= ($strOrder == '' ? '' : ', ').$DB->ForSql($key).' '.$dir;
			}
			
		}
		if ($strOrder != '') $strOrder = 'ORDER BY '.$strOrder;
		
		$query = 'SELECT * FROM b_seo_keywords ';
		$query .= $strWhere.' ';
		$query .= $strOrder;
	
		return $DB->Query($query);
	}
	
	public static function CheckFields($ACTION, &$arFields)
	{
		if ($ACTION == 'UPDATE' && isset($arFields['ID']))
			$arFields['ID'] = intval($arFields['ID']);
	
		$arFields['URL'] = CSeoUtils::CleanURL($arFields['URL']);
		
		if (isset($arFields['KEYWORDS']))
		{
			if (!is_array($arFields['KEYWORDS'])) 
				$arKeywords = explode(",", $arFields['KEYWORDS']);
			else 
				$arKeywords = array_values($arFields['KEYWORDS']);
			
			if (!is_array($arKeywords)) 
				$arKeywords = array();
			
			foreach ($arKeywords as $key => $value) 
			{
				$arKeywords[$key] = trim($value);
				if (strlen($arKeywords[$key]) <= 0) 
					unset($arKeywords[$key]);
			}
			
			$arFields['KEYWORDS'] = implode(', ', $arKeywords);
		}
		else
		{
			$arFields['KEYWORDS'] = '';
		}
		
		if (!isset($arFields['SITE_ID']) && defined('SITE_ID'))
			$arFields['SITE_ID'] = SITE_ID;
		
		return true;
	}
	
	public static function Add($arFields)
	{
		global $APPLICATION, $DB;
		
		if (!CSeoKeywords::CheckFields('ADD', $arFields))
		{
			return false;
		}
	
		$arAllFields = array('URL', 'SITE_ID', 'KEYWORDS');
		
		$arInsert = array();
		foreach ($arFields as $key => $value)
		{
			if (in_array($key, $arAllFields))
			{
				$arInsert[$key] = "'".($key == 'SITE_ID' ? $DB->ForSql($value, 2) : $DB->ForSql($value))."'";
			}
		}
		
		$ID = $DB->Insert('b_seo_keywords', $arInsert);
		return $ID;
	}
	
	public static function Update($arFields)
	{
		global $APPLICATION, $DB;
		
		if (!CSeoKeywords::CheckFields('UPDATE', $arFields))
		{
			return false;
		}
		
		$strUpdateBy = isset($arFields['ID']) ? 'ID' : 'URL';
		
		if ($strUpdateBy == 'ID')
		{
			$ID = $arFields['ID'];
			unset($arFields['ID']);
		}
		else
		{
			$URL = $DB->ForSql($arFields['URL']);
			unset($arFields['URL']);
		}
		
		$arAllFields = array('ID', 'URL', 'SITE_ID', 'KEYWORDS');
		
		$arUpdate = array();
		foreach ($arFields as $key => $value)
		{
			if (in_array($key, $arAllFields))
			{
				$arUpdate[$key] = "'".($key == 'SITE_ID' ? $DB->ForSql($value, 2) : $DB->ForSql($value))."'";
			}
		}
		
		$cnt = $DB->Update('b_seo_keywords', $arUpdate, $strUpdateBy == 'ID' ? 'WHERE ID=\''.$ID.'\'' : 'WHERE URL=\''.$URL.'\'');
		
		if ($cnt <= 0 && $strUpdateBy == 'URL')
		{
			$arUpdate['URL'] = "'".$URL."'";
			$cnt = intval(($DB->Insert('b_seo_keywords', $arUpdate)) > 0);
		}
		
		return $cnt;
	}
	
	public static function GetByURL($URL, $SITE_ID = false, $bPart = false, $bCleanUrl = false)
	{
		if ($bCleanUrl)
			$URL = CSeoUtils::CleanURL($URL);
	
		$arFilter = array('URL' => $URL);
		if ($bPart)
			$arFilter['URL_EXACT_MATCH'] = 'N';
		if ($SITE_ID)
			$arFilter['SITE_ID'] = $SITE_ID;
		
		$dbRes = CSeoKeywords::GetList(array('URL' => 'ASC', 'ID' => 'ASC'), $arFilter);
		$arKeywords = array();
		while ($arRes = $dbRes->Fetch())
		{
			$arKeywords[] = $arRes;
		}
		
		return $arKeywords;
	}
}
?>